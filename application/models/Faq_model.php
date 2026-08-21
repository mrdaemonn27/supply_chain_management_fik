<?php
defined('BASEPATH') OR exit('No direct script access allowed');

<<<<<<< HEAD
=======
/**
 * Sumber data FAQ Assistant pada landing page.
 *
 * Tabel dibuat dan diisi satu kali agar instalasi lama tetap langsung dapat
 * memakai FAQ. Kolom status dan urutan disiapkan supaya pengelolaan oleh
 * Laboran/Admin dapat ditambahkan tanpa mengubah struktur datanya lagi.
 */
>>>>>>> origin/main
class Faq_model extends CI_Model
{
    private $table = 'faq';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
<<<<<<< HEAD
    }

    public function get_active()
    {
        return $this->db
            ->select('id_faq, question, answer, keywords, category, source_reference, source_url')
=======
        $this->ensure_schema();
    }

    public function get_active_faqs()
    {
        return $this->db
            ->select('id_faq, slug, category, question, answer, keywords, source_reference, sort_order')
>>>>>>> origin/main
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->order_by('id_faq', 'ASC')
            ->get()
<<<<<<< HEAD
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
=======
            ->result_array();
    }

    private function ensure_schema()
    {
        if ($this->db->table_exists($this->table)) {
            if ((int) $this->db->count_all($this->table) === 0) {
                $this->db->insert_batch($this->table, $this->default_faqs());
            }
            return;
        }

        $created = $this->db->query("CREATE TABLE `faq` (
            `id_faq` int(11) NOT NULL AUTO_INCREMENT,
            `slug` varchar(120) NOT NULL,
            `category` varchar(80) NOT NULL DEFAULT 'Umum',
            `question` varchar(255) NOT NULL,
            `answer` text NOT NULL,
            `keywords` varchar(500) DEFAULT NULL,
            `source_reference` varchar(255) NOT NULL DEFAULT 'Fitur SCM FIK',
            `sort_order` int(11) NOT NULL DEFAULT 0,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_by` int(11) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_faq`),
            UNIQUE KEY `uq_faq_slug` (`slug`),
            KEY `idx_faq_active_order` (`is_active`, `sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        if ($created) {
            $this->db->insert_batch($this->table, $this->default_faqs());
        }
    }

    private function default_faqs()
    {
        return [
            [
                'slug' => 'cara-meminjam-barang',
                'category' => 'Pengajuan',
                'question' => 'Bagaimana cara meminjam barang?',
                'answer' => 'Masuk ke akun SCM FIK, buka katalog peminjaman, pilih ruangan atau laboratorium lalu pilih barang. Tekan Ajukan Peminjaman, isi jumlah, tanggal pinjam dan kembali, keperluan, kondisi awal, serta unggah foto kondisi barang. Setelah dikirim, pengajuan dapat dipantau melalui Riwayat Peminjaman.',
                'keywords' => 'pinjam barang ajukan katalog alat tanggal foto kondisi awal',
                'source_reference' => 'Katalog dan Form Pengajuan Peminjaman SCM FIK',
                'sort_order' => 10,
            ],
            [
                'slug' => 'mengetahui-status-pengajuan',
                'category' => 'Status',
                'question' => 'Bagaimana mengetahui status pengajuan?',
                'answer' => 'Buka menu Riwayat Peminjaman setelah login. Status terbaru tampil pada setiap transaksi dan dapat dicari berdasarkan nama barang, status, atau tanggal. Sistem juga mengirim notifikasi ketika pengajuan berpindah tahap, disetujui, ditolak, QR diaktifkan, atau barang diserahkan.',
                'keywords' => 'status riwayat notifikasi proses cek pengajuan',
                'source_reference' => 'Riwayat Peminjaman dan Notifikasi SCM FIK',
                'sort_order' => 20,
            ],
            [
                'slug' => 'pihak-yang-menyetujui',
                'category' => 'Persetujuan',
                'question' => 'Siapa yang menyetujui peminjaman?',
                'answer' => 'Persetujuan berjalan berurutan: Kaprodi memberi persetujuan awal, Laboran memverifikasi barang, lalu Kaur memberi persetujuan akhir. Stok sudah direservasi sejak pengajuan berhasil dikirim dan tetap teralokasi selama proses. Setelah seluruh tahap selesai, Laboran memfinalkan QR transaksi sebelum barang dapat diambil.',
                'keywords' => 'setuju approval kaprodi laboran admin kaur urutan',
                'source_reference' => 'Alur Approval Peminjaman SCM FIK',
                'sort_order' => 30,
            ],
            [
                'slug' => 'alasan-peminjaman-ditolak',
                'category' => 'Persetujuan',
                'question' => 'Kenapa peminjaman saya ditolak?',
                'answer' => 'Pengajuan dapat ditolak oleh Kaprodi, Laboran, atau Kaur karena keperluan atau pertimbangan pada tahap pemeriksaan. Periksa notifikasi dan status di Riwayat Peminjaman untuk mengetahui tahap penolakannya. Reservasi stok akan dilepas otomatis ketika pengajuan ditolak. Jika alasan rinci belum terlihat, hubungi Laboran.',
                'keywords' => 'ditolak gagal alasan stok catatan kaprodi laboran kaur',
                'source_reference' => 'Status Approval dan Notifikasi Peminjaman SCM FIK',
                'sort_order' => 40,
            ],
            [
                'slug' => 'lama-proses-persetujuan',
                'category' => 'Persetujuan',
                'question' => 'Berapa lama proses persetujuan?',
                'answer' => 'SCM FIK belum menetapkan durasi atau SLA otomatis. Lama proses bergantung pada waktu respons Kaprodi, pemeriksaan stok oleh Laboran, dan persetujuan Kaur. Pantau tahap aktif melalui Riwayat Peminjaman dan notifikasi.',
                'keywords' => 'berapa lama durasi waktu sla proses menunggu',
                'source_reference' => 'Alur Status Persetujuan SCM FIK',
                'sort_order' => 50,
            ],
            [
                'slug' => 'persetujuan-kaprodi-kedaluwarsa',
                'category' => 'Persetujuan',
                'question' => 'Bagaimana jika persetujuan Kaprodi kedaluwarsa?',
                'answer' => 'Setiap pengajuan mempunyai batas waktu persetujuan Kaprodi sesuai pengaturan Laboran. Jika Kaprodi belum memberi keputusan sampai tenggat, status berubah menjadi Kedaluwarsa / Ditolak Otomatis, reservasi stok dilepas, dan user menerima notifikasi. Jika barang masih dibutuhkan, lakukan pengajuan peminjaman baru.',
                'keywords' => 'kaprodi kedaluwarsa kadaluarsa expired menunggu acc',
                'source_reference' => 'Pengaturan Tenggat dan Status Approval Kaprodi SCM FIK',
                'sort_order' => 60,
            ],
            [
                'slug' => 'cara-mengembalikan-barang',
                'category' => 'Pengembalian',
                'question' => 'Bagaimana cara mengembalikan barang?',
                'answer' => 'Bawa barang ke Laboran sesuai tanggal pengembalian dan tunjukkan QR transaksi yang sama dengan saat pengambilan. Laboran memindai QR, memeriksa jumlah dan kondisi barang, mengunggah bukti bila diperlukan, lalu menyelesaikan pengembalian di sistem.',
                'keywords' => 'kembali pengembalian barang laboran kondisi tanggal',
                'source_reference' => 'Validasi Pengembalian SCM FIK',
                'sort_order' => 70,
            ],
            [
                'slug' => 'menggunakan-qr-peminjaman',
                'category' => 'QR',
                'question' => 'Bagaimana menggunakan QR peminjaman?',
                'answer' => 'QR muncul di Riwayat Peminjaman setelah Kaprodi, Laboran, dan Kaur menyetujui pengajuan serta Laboran memfinalkan transaksi. Tunjukkan QR kepada Laboran saat pengambilan barang. Simpan QR tersebut karena QR yang sama digunakan kembali saat pengembalian.',
                'keywords' => 'qr kode scan pindai ambil serah terima riwayat finalisasi',
                'source_reference' => 'QR Serah Terima Peminjaman SCM FIK',
                'sort_order' => 80,
            ],
            [
                'slug' => 'mencari-ruangan-laboratorium',
                'category' => 'Katalog',
                'question' => 'Bagaimana mencari ruangan atau laboratorium?',
                'answer' => 'Setelah login, pilih kartu ruangan atau laboratorium pada Dashboard lalu tekan Masuk Ruangan untuk melihat aset di lokasi tersebut. Di katalog, kolom pencarian dapat digunakan untuk mencari berdasarkan nama alat, kode aset, ruangan, kondisi, atau stok.',
                'keywords' => 'cari ruangan laboratorium lab lokasi masuk ruangan katalog',
                'source_reference' => 'Dashboard Ruangan dan Pencarian Katalog SCM FIK',
                'sort_order' => 90,
            ],
            [
                'slug' => 'barang-rusak',
                'category' => 'Pengembalian',
                'question' => 'Apa yang harus dilakukan jika barang rusak?',
                'answer' => 'Segera laporkan kerusakan kepada Laboran dan jangan menyembunyikan kondisi barang. Saat pengembalian, Laboran memilih kondisi Rusak, mengisi keterangan wajib, dan mengunggah bukti kerusakan. Kondisi aset kemudian tercatat di SCM FIK untuk tindak lanjut.',
                'keywords' => 'rusak kerusakan hilang lapor bukti foto kondisi',
                'source_reference' => 'Pencatatan Kondisi Pengembalian SCM FIK',
                'sort_order' => 100,
            ],
        ];
>>>>>>> origin/main
    }
}
