<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Faq_model extends CI_Model
{
    private $table = 'faq';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_active()
    {
        return $this->db
            ->select('id_faq, question, answer, keywords, category, source_reference, source_url')
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('id_faq', 'ASC')
            ->get()
            ->result();
    }

    /**
     * FAQ database adalah sumber utama. Knowledge tambahan di bawah berasal
     * dari alur resmi yang sudah diterapkan pada controller/model project.
     */
    private function local_knowledge()
    {
        return array(
            array(
                'question' => 'Apa fungsi BAST?',
                'answer' => 'BAST (Berita Acara Serah Terima) diinput setelah pengajuan disetujui Kaur. Nomor, tanggal, jenis, dan file BAST diisi sesuai dokumen. Setelah disimpan, pengajuan berjenis Barang diproses ke inventory.',
                'keywords' => 'bast berita acara serah terima dokumen pengadaan inventory inventaris aset',
                'category' => 'Pengadaan',
                'source_reference' => 'application/controllers/kaur/Pengajuan.php; application/models/kaur/Kaur_model.php',
                'source_url' => null,
            ),
            array(
                'question' => 'Kapan stok barang berkurang?',
                'answer' => 'Pada alur SCM FIK saat ini, stok tersedia belum dikurangi ketika pengajuan baru diteruskan. Pengurangan stok dilakukan saat Laboran memproses serah terima barang setelah persetujuan Kaur dan QR aktif.',
                'keywords' => 'stok berkurang tersedia reserved reservasi serah terima qr diserahkan laboran dipinjam',
                'category' => 'Peminjaman',
                'source_reference' => 'application/controllers/admin/Approval.php; application/controllers/admin/Peminjaman.php; application/models/Aset_model.php',
                'source_url' => null,
            ),
        );
    }

    /** Cari knowledge secara lokal memakai token overlap, phrase match,
     * kemiripan teks, dan context percakapan singkat. */
    public function search_faq($input, $context = array())
    {
        $query = $this->normalize($input);
        $context_query = $this->context_query($context);
        $search_query = $query;

        if ($context_query !== '' && count($this->tokens($query)) <= 3) {
            $search_query = trim($query . ' ' . $context_query);
        }

        $entries = array();
        foreach ($this->get_active() as $row) {
            $entries[] = array(
                'faq' => $this->public_faq($row),
                'question' => $this->normalize($row->question),
                'keywords' => $this->normalize((string) $row->keywords),
                'category' => $this->normalize((string) $row->category),
            );
        }

        foreach ($this->local_knowledge() as $row) {
            $entries[] = array(
                'faq' => $row,
                'question' => $this->normalize($row['question']),
                'keywords' => $this->normalize($row['keywords']),
                'category' => $this->normalize($row['category']),
            );
        }

        $scored = array();
        foreach ($entries as $entry) {
            $result = $this->score($search_query, $entry['question'], $entry['keywords'], $entry['category']);
            $scored[] = array(
                'score' => $result['score'],
                'coverage' => $result['coverage'],
                'faq' => $entry['faq'],
            );
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) return 0;
            return ($a['score'] > $b['score']) ? -1 : 1;
        });

        $best = !empty($scored) ? $scored[0] : null;
        $domain_hits = $this->domain_hits($query, $entries);
        $confidence = 'low';

        if ($best && $best['score'] >= 24 && $best['coverage'] >= .65) {
            $confidence = 'high';
        } elseif ($best && $best['score'] >= 9 && $best['coverage'] >= .3) {
            $confidence = 'medium';
        }

        $suggestions = array();
        foreach (array_slice(array_filter($scored, function ($item) {
            return $item['score'] > 0;
        }), 0, 3) as $item) {
            $suggestions[] = array(
                'question' => $item['faq']['question'],
                'category' => $item['faq']['category'],
            );
        }

        if (empty($suggestions)) {
            foreach (array_slice($entries, 0, 3) as $entry) {
                $suggestions[] = array(
                    'question' => $entry['faq']['question'],
                    'category' => $entry['faq']['category'],
                );
            }
        }

        return array(
            'match' => ($best && $confidence !== 'low') ? $best['faq'] : null,
            'confidence' => $confidence,
            'score' => $best ? round($best['score'], 2) : 0,
            'out_of_scope' => $domain_hits === 0,
            'suggestions' => $suggestions,
        );
    }

    private function public_faq($row)
    {
        return array(
            'question' => (string) $row->question,
            'answer' => (string) $row->answer,
            'category' => (string) $row->category,
            'source_reference' => (string) $row->source_reference,
            'source_url' => $row->source_url ? (string) $row->source_url : null,
        );
    }

    private function score($query, $question, $keywords, $category)
    {
        if ($query === '') return array('score' => 0, 'coverage' => 0);

        $query_tokens = $this->tokens($query);
        $question_tokens = $this->tokens($question);
        $keyword_tokens = $this->tokens($keywords);
        $category_tokens = $this->tokens($category);
        $score = 0;
        $matched = 0;

        if (strpos($question, $query) !== false) $score += 30;
        if ($keywords !== '' && strpos($keywords, $query) !== false) $score += 20;

        foreach ($query_tokens as $token) {
            if ($this->matches_token($token, $question_tokens)) {
                $score += 8;
                $matched++;
            } elseif ($this->matches_token($token, $keyword_tokens)) {
                $score += 5;
                $matched++;
            } elseif ($this->matches_token($token, $category_tokens)) {
                $score += 3;
                $matched++;
            }
        }

        $similarity = 0;
        similar_text($query, $question, $similarity);
        $score += min(10, $similarity * 0.08);

        return array(
            'score' => $score,
            'coverage' => empty($query_tokens) ? 0 : $matched / count($query_tokens),
        );
    }

    private function domain_hits($query, $entries)
    {
        $hits = 0;
        $query_tokens = $this->tokens($query);
        foreach ($query_tokens as $token) {
            foreach ($entries as $entry) {
                $pool = array_merge(
                    $this->tokens($entry['question']),
                    $this->tokens($entry['keywords']),
                    $this->tokens($entry['category'])
                );
                if ($this->matches_token($token, $pool)) {
                    $hits++;
                    break;
                }
            }
        }
        return $hits;
    }

    private function context_query($context)
    {
        if (!is_array($context)) return '';

        $parts = array();
        foreach (array_slice($context, -6) as $message) {
            if (!is_array($message) || ($message['role'] ?? '') !== 'user') continue;
            $content = trim((string) ($message['content'] ?? $message['text'] ?? ''));
            if ($content !== '') $parts[] = $content;
        }

        return $this->normalize(implode(' ', array_slice($parts, -2)));
    }

    private function matches_token($token, $candidates)
    {
        foreach ($candidates as $candidate) {
            if ($token === $candidate) return true;
            if (strlen($token) >= 4 && strlen($candidate) >= 4 &&
                (strpos($candidate, $token) !== false || strpos($token, $candidate) !== false)) {
                return true;
            }
        }
        return false;
    }

    private function tokens($text)
    {
        $stopwords = array(
            'apa', 'yang', 'bagaimana', 'gimana', 'kalau', 'kalo', 'saya',
            'mau', 'bisa', 'untuk', 'dari', 'dan', 'ini', 'itu', 'dengan', 'pada',
            'kapan', 'berapa', 'siapa', 'saja', 'harus', 'nya', 'dong', 'kah', 'ke',
            'di', 'ada', 'atau', 'lebih', 'seputar', 'tentang', 'tolong',
        );
        $tokens = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter($tokens, function ($token) use ($stopwords) {
            return strlen($token) >= 2 && !in_array($token, $stopwords, true);
        })));
    }

    private function normalize($text)
    {
        $text = strtolower(trim((string) $text));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        $synonyms = array(
            'minjem' => 'pinjam', 'minjam' => 'pinjam', 'pinjem' => 'pinjam',
            'meminjam' => 'pinjam', 'peminjaman' => 'pinjam',
            'acc' => 'setuju', 'approve' => 'setuju', 'approval' => 'setuju',
            'persetujuan' => 'setuju', 'menyetujui' => 'setuju', 'disetujui' => 'setuju',
            'balikin' => 'kembali', 'balik' => 'kembali', 'kembali' => 'kembali',
            'pengembalian' => 'kembali', 'mengembalikan' => 'kembali',
            'barang' => 'aset', 'alat' => 'aset', 'aset' => 'aset',
            'telat' => 'terlambat', 'terlambat' => 'terlambat', 'keterlambatan' => 'terlambat',
            'rusak' => 'rusak', 'kerusakan' => 'rusak',
            'daftar' => 'daftar', 'registrasi' => 'daftar',
            'masuk' => 'login', 'login' => 'login',
        );

        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as &$token) {
            if (isset($synonyms[$token])) $token = $synonyms[$token];
        }
        unset($token);

        return implode(' ', $tokens);
    }
}