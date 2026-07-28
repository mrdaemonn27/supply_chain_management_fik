<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    private function count_table($table) {
        return $this->db->table_exists($table) ? $this->db->count_all($table) : 0;
    }

    public function get_statistik() {
        $total_aset = $this->count_table('aset');

        $total_aset_fisik = 0;
        if ($this->db->table_exists('aset')) {
            $this->db->select_sum('jumlah_total');
            $query_fisik = $this->db->get('aset')->row();
            $total_aset_fisik = (int) ($query_fisik->jumlah_total ?? 0);
        }

        $total_ruangan = $this->count_table('ruangan');
        $total_user = $this->count_table('users');
        $total_maintenance = $this->count_table('maintenance');
        $total_dokumen = $this->count_table('dokumen_laboran');
        $total_distribusi = $this->count_table('distribusi_barang');

        $peminjaman_aktif = 0;
        $peminjaman_proses = 0;
        $pengembalian_aktif = 0;
        $menunggu_persetujuan = 0;
        $peminjaman_selesai = 0;
        $blokir_aktif = 0;
        $stok_habis = 0;
        $stok_menipis = 0;

        if ($this->db->table_exists('peminjaman')) {
            $this->db->select('COUNT(DISTINCT COALESCE(NULLIF(group_id, ""), id_peminjaman)) as total', false);
            $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
            $peminjaman_aktif = (int) (($this->db->get('peminjaman')->row()->total ?? 0));

            $this->db->select('COUNT(DISTINCT COALESCE(NULLIF(group_id, ""), id_peminjaman)) as total', false);
            $this->db->where_in('status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan', 'Menunggu ACC Kaur', 'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)']);
            $peminjaman_proses = (int) (($this->db->get('peminjaman')->row()->total ?? 0));

            $pengembalian_aktif = $peminjaman_aktif;

            $this->db->where_in('status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan']);
            $menunggu_persetujuan = $this->db->count_all_results('peminjaman');

            $this->db->where('status', 'Dikembalikan');
            $peminjaman_selesai = $this->db->count_all_results('peminjaman');
        }

        if ($this->db->table_exists('blokir_pengguna')) {
            $this->db->where('status', 'Aktif');
            $this->db->group_start();
            $this->db->where('batas_blokir IS NULL', null, false);
            $this->db->or_where('batas_blokir >=', date('Y-m-d'));
            $this->db->group_end();
            $blokir_aktif = $this->db->count_all_results('blokir_pengguna');
        }

        if ($this->db->table_exists('aset')) {
            $this->db->where('jumlah_tersedia', 0);
            $stok_habis = $this->db->count_all_results('aset');

            $this->db->where('jumlah_tersedia >', 0);
            $this->db->where('jumlah_tersedia <', 3);
            $stok_menipis = $this->db->count_all_results('aset');
        }

        return [
            'total_aset'           => $total_aset,
            'total_aset_fisik'     => $total_aset_fisik,
            'total_ruangan'        => $total_ruangan,
            'peminjaman_aktif'     => $peminjaman_aktif,
            'peminjaman_proses'    => $peminjaman_proses,
            'pengembalian_aktif'   => $pengembalian_aktif,
            'menunggu_persetujuan' => $menunggu_persetujuan,
            'peminjaman_selesai'   => $peminjaman_selesai,
            'blokir_aktif'         => $blokir_aktif,
            'total_user'           => $total_user,
            'total_maintenance'    => $total_maintenance,
            'total_dokumen'        => $total_dokumen,
            'total_distribusi'     => $total_distribusi,
            'stok_habis'           => $stok_habis,
            'stok_menipis'         => $stok_menipis,
        ];
    }

    public function get_dashboard_years() {
        $years = [(int) date('Y')];
        $sources = [
            ['table' => 'peminjaman', 'field' => 'created_at'],
            ['table' => 'maintenance', 'field' => 'tanggal_maintenance'],
            ['table' => 'distribusi_barang', 'field' => 'tanggal_distribusi'],
        ];

        foreach ($sources as $source) {
            if (!$this->db->table_exists($source['table'])) {
                continue;
            }
            $rows = $this->db
                ->select('YEAR(' . $source['field'] . ') as tahun', false)
                ->where($source['field'] . ' IS NOT NULL', null, false)
                ->group_by('YEAR(' . $source['field'] . ')')
                ->get($source['table'])
                ->result();
            foreach ($rows as $row) {
                if ((int) $row->tahun > 0) {
                    $years[] = (int) $row->tahun;
                }
            }
        }

        $years = array_values(array_unique($years));
        rsort($years);
        return $years;
    }

    private function apply_dashboard_period($field, $tahun, $bulan = 0) {
        if ((int) $tahun > 0) {
            $this->db->where('YEAR(' . $field . ')', (int) $tahun);
        }
        if ((int) $bulan > 0) {
            $this->db->where('MONTH(' . $field . ')', (int) $bulan);
        }
    }

    private function dashboard_empty_overview() {
        return [
            'stats' => [
                'total_aset' => 0,
                'total_unit_barang' => 0,
                'barang_dipinjam' => 0,
                'menunggu_approval' => 0,
                'menunggu_pengembalian' => 0,
                'barang_maintenance' => 0,
                'barang_rusak' => 0,
                'barang_hilang' => 0,
                'distribusi_barang' => 0,
                'pengguna_diblokir' => 0,
            ],
            'inventory_status' => ['Baik' => 0, 'Rusak' => 0, 'Hilang' => 0, 'Maintenance' => 0],
            'peminjaman_bulanan' => array_fill(0, 12, 0),
            'pengembalian' => ['Sudah Dikembalikan' => 0, 'Belum Dikembalikan' => 0, 'Terlambat' => 0],
            'distribusi_ruangan' => ['labels' => [], 'values' => []],
            'maintenance_bulanan' => array_fill(0, 12, 0),
            'approval' => ['Menunggu Approval' => 0, 'Sudah Dicek Laboran' => 0, 'Diteruskan ke Kaur' => 0, 'Disetujui' => 0, 'Ditolak' => 0],
            'recent_activity' => [],
        ];
    }

    public function get_dashboard_overview($tahun = null, $bulan = 0) {
        $overview = $this->dashboard_empty_overview();
        $tahun = (int) ($tahun ?: date('Y'));
        $bulan = (int) $bulan;
        $overview['recent_activity'] = $this->get_dashboard_recent_activity(12);

        if ($this->db->table_exists('aset')) {
            $inventory = $this->db->select("\n                COUNT(*) as total_aset,\n                COALESCE(SUM(jumlah_total), 0) as total_unit_barang,\n                SUM(CASE WHEN LOWER(TRIM(COALESCE(kondisi, ''))) IN ('baik', 'tersedia') THEN 1 ELSE 0 END) as baik,\n                SUM(CASE WHEN LOWER(TRIM(COALESCE(kondisi, ''))) LIKE 'rusak%' THEN 1 ELSE 0 END) as rusak,\n                SUM(CASE WHEN LOWER(TRIM(COALESCE(kondisi, ''))) = 'hilang' THEN 1 ELSE 0 END) as hilang,\n                SUM(CASE WHEN LOWER(TRIM(COALESCE(kondisi, ''))) LIKE '%maintenance%' THEN 1 ELSE 0 END) as maintenance\n            ", false)->get('aset')->row();
            $overview['stats']['total_aset'] = (int) ($inventory->total_aset ?? 0);
            $overview['stats']['total_unit_barang'] = (int) ($inventory->total_unit_barang ?? 0);
            $overview['stats']['barang_maintenance'] = (int) ($inventory->maintenance ?? 0);
            $overview['stats']['barang_rusak'] = (int) ($inventory->rusak ?? 0);
            $overview['stats']['barang_hilang'] = (int) ($inventory->hilang ?? 0);
            $overview['inventory_status'] = [
                'Baik' => (int) ($inventory->baik ?? 0),
                'Rusak' => (int) ($inventory->rusak ?? 0),
                'Hilang' => (int) ($inventory->hilang ?? 0),
                'Maintenance' => (int) ($inventory->maintenance ?? 0),
            ];
        }

        if ($this->db->table_exists('peminjaman')) {
            $active = $this->db->select("\n                COUNT(DISTINCT COALESCE(NULLIF(group_id, ''), id_peminjaman)) as transaksi,\n                COALESCE(SUM(jumlah_pinjam), 0) as unit\n            ", false)->where_in('status', ['Sedang Dipinjam', 'Dipinjam'])->get('peminjaman')->row();
            $pending = $this->db->select('COUNT(DISTINCT COALESCE(NULLIF(group_id, ""), id_peminjaman)) as total', false)
                ->where_in('status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan', 'Menunggu ACC Kaur'])
                ->get('peminjaman')->row();
            $overview['stats']['barang_dipinjam'] = (int) ($active->unit ?? 0);
            $overview['stats']['menunggu_pengembalian'] = (int) ($active->transaksi ?? 0);
            $overview['stats']['menunggu_approval'] = (int) ($pending->total ?? 0);
        }

        if ($this->db->table_exists('distribusi_barang')) {
            $overview['stats']['distribusi_barang'] = (int) $this->db->count_all('distribusi_barang');
        }
        if ($this->db->table_exists('blokir_pengguna')) {
            $this->db->where('status', 'Aktif')->group_start();
            $this->db->where('batas_blokir IS NULL', null, false)->or_where('batas_blokir >=', date('Y-m-d'));
            $this->db->group_end();
            $overview['stats']['pengguna_diblokir'] = (int) $this->db->count_all_results('blokir_pengguna');
        }

        if ($this->db->table_exists('peminjaman')) {
            $rows = $this->db->select('MONTH(created_at) as bulan, COUNT(DISTINCT COALESCE(NULLIF(group_id, ""), id_peminjaman)) as total', false)
                ->where('YEAR(created_at)', $tahun);
            if ($bulan > 0) {
                $rows->where('MONTH(created_at)', $bulan);
            }
            $rows = $rows->group_by('MONTH(created_at)')->get('peminjaman')->result();
            foreach ($rows as $row) {
                $index = (int) $row->bulan - 1;
                if ($index >= 0 && $index < 12) {
                    $overview['peminjaman_bulanan'][$index] = (int) $row->total;
                }
            }

            $returns = $this->db->select("\n                SUM(CASE WHEN status = 'Dikembalikan' THEN 1 ELSE 0 END) as dikembalikan,\n                SUM(CASE WHEN status IN ('Sedang Dipinjam', 'Dipinjam') AND (tanggal_kembali_rencana IS NULL OR tanggal_kembali_rencana >= CURDATE()) THEN 1 ELSE 0 END) as belum,\n                SUM(CASE WHEN status IN ('Sedang Dipinjam', 'Dipinjam') AND tanggal_kembali_rencana IS NOT NULL AND tanggal_kembali_rencana < CURDATE() THEN 1 ELSE 0 END) as terlambat\n            ", false)->where('YEAR(created_at)', $tahun);
            if ($bulan > 0) {
                $returns->where('MONTH(created_at)', $bulan);
            }
            $returns = $returns->get('peminjaman')->row();
            $overview['pengembalian'] = [
                'Sudah Dikembalikan' => (int) ($returns->dikembalikan ?? 0),
                'Belum Dikembalikan' => (int) ($returns->belum ?? 0),
                'Terlambat' => (int) ($returns->terlambat ?? 0),
            ];

            $approval_rows = $this->db->select('status, status_laboran, status_kaur')->where('YEAR(created_at)', $tahun);
            if ($bulan > 0) {
                $approval_rows->where('MONTH(created_at)', $bulan);
            }
            foreach ($approval_rows->get('peminjaman')->result() as $row) {
                $status = (string) ($row->status ?? '');
                $status_laboran = (string) ($row->status_laboran ?? '');
                $status_kaur = (string) ($row->status_kaur ?? '');
                if ($status === 'Ditolak' || $status_laboran === 'Ditolak' || $status_kaur === 'Ditolak') {
                    $key = 'Ditolak';
                } elseif ($status_kaur === 'Disetujui' || in_array($status, ['Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam', 'Dikembalikan'], true)) {
                    $key = 'Disetujui';
                } elseif ($status === 'Menunggu ACC Kaur') {
                    $key = 'Diteruskan ke Kaur';
                } elseif ($status_laboran === 'Disetujui') {
                    $key = 'Sudah Dicek Laboran';
                } else {
                    $key = 'Menunggu Approval';
                }
                $overview['approval'][$key]++;
            }
        }

        if ($this->db->table_exists('distribusi_barang')) {
            $distribution = $this->db->select('COALESCE(r.nama_ruangan, "Belum ditentukan") as label, COALESCE(SUM(d.jumlah), 0) as total', false)
                ->from('distribusi_barang d')->join('ruangan r', 'r.id_ruangan = d.id_ruangan_tujuan', 'left');
            $this->apply_dashboard_period('d.tanggal_distribusi', $tahun, $bulan);
            $distribution = $distribution->group_by(['d.id_ruangan_tujuan', 'r.nama_ruangan'])->order_by('total', 'DESC')->limit(8)->get()->result();
            foreach ($distribution as $row) {
                $overview['distribusi_ruangan']['labels'][] = (string) $row->label;
                $overview['distribusi_ruangan']['values'][] = (int) $row->total;
            }
        }

        if ($this->db->table_exists('maintenance')) {
            $maintenance = $this->db->select('MONTH(tanggal_maintenance) as bulan, COUNT(*) as total', false);
            $this->apply_dashboard_period('tanggal_maintenance', $tahun, $bulan);
            $maintenance = $maintenance->group_by('MONTH(tanggal_maintenance)')->get('maintenance')->result();
            foreach ($maintenance as $row) {
                $index = (int) $row->bulan - 1;
                if ($index >= 0 && $index < 12) {
                    $overview['maintenance_bulanan'][$index] = (int) $row->total;
                }
            }
        }

        return $overview;
    }

    public function get_dashboard_recent_activity($limit = 12) {
        $activity = [];
        if ($this->db->table_exists('peminjaman')) {
            $rows = $this->db->select('p.created_at, p.status, p.status_laboran, p.status_kaur, a.nama_aset, u.nama_lengkap')
                ->from('peminjaman p')->join('aset a', 'a.id_aset = p.id_aset', 'left')->join('users u', 'u.id_user = p.id_user', 'left')
                ->order_by('p.created_at', 'DESC')->limit(6)->get()->result();
            foreach ($rows as $row) {
                $status = (string) ($row->status ?? '');
                if ($status === 'Dikembalikan') {
                    $title = 'Barang berhasil dikembalikan'; $icon = 'bi-arrow-return-left'; $link = 'admin/pengembalian'; $badge = 'Dikembalikan';
                } elseif (in_array($status, ['Sedang Dipinjam', 'Dipinjam'], true)) {
                    $title = 'Barang berhasil dipinjam'; $icon = 'bi-box-arrow-up-right'; $link = 'admin/peminjaman'; $badge = 'Dipinjam';
                } elseif ($status === 'Menunggu ACC Kaur') {
                    $title = 'Approval diteruskan ke Kaur'; $icon = 'bi-send-check'; $link = 'admin/approval'; $badge = 'Diteruskan';
                } elseif ($status === 'Ditolak') {
                    $title = 'Pengajuan peminjaman ditolak'; $icon = 'bi-x-circle'; $link = 'admin/approval'; $badge = 'Ditolak';
                } else {
                    $title = 'Pengajuan peminjaman baru masuk'; $icon = 'bi-bell'; $link = 'admin/approval'; $badge = 'Baru';
                }
                $activity[] = ['title' => $title, 'description' => trim(($row->nama_lengkap ?? 'Peminjam') . ' - ' . ($row->nama_aset ?? 'Aset')), 'time' => $row->created_at, 'status' => $badge, 'icon' => $icon, 'link' => $link];
            }
        }

        $sources = [
            ['table' => 'aset', 'date' => 'created_at', 'title' => 'Aset baru berhasil ditambahkan', 'icon' => 'bi-boxes', 'link' => 'admin/barang', 'badge' => 'Aset', 'description' => 'Data inventaris baru masuk ke sistem.'],
            ['table' => 'maintenance', 'date' => 'created_at', 'title' => 'Maintenance barang dicatat', 'icon' => 'bi-tools', 'link' => 'admin/maintenance', 'badge' => 'Maintenance', 'description' => 'Riwayat perawatan aset diperbarui.'],
            ['table' => 'distribusi_barang', 'date' => 'created_at', 'title' => 'Barang berhasil didistribusikan', 'icon' => 'bi-truck', 'link' => 'admin/distribusi', 'badge' => 'Distribusi', 'description' => 'Lokasi aset berhasil diperbarui.'],
            ['table' => 'dokumen_laboran', 'date' => 'created_at', 'title' => 'Dokumen berhasil diunggah', 'icon' => 'bi-file-earmark-arrow-up', 'link' => 'admin/dokumen', 'badge' => 'Dokumen', 'description' => 'Dokumen internal tersimpan di file manager.'],
            ['table' => 'blokir_pengguna', 'date' => 'updated_at', 'title' => 'Status pengguna diperbarui', 'icon' => 'bi-shield-lock', 'link' => 'admin/blokir', 'badge' => 'Pengguna', 'description' => 'Riwayat blokir pengguna berubah.'],
        ];
        foreach ($sources as $source) {
            if (!$this->db->table_exists($source['table'])) {
                continue;
            }
            $rows = $this->db->order_by($source['date'], 'DESC')->limit(3)->get($source['table'])->result();
            foreach ($rows as $row) {
                $activity[] = [
                    'title' => $source['title'], 'description' => $source['description'], 'time' => $row->{$source['date']} ?? null,
                    'status' => $source['badge'], 'icon' => $source['icon'], 'link' => $source['link'],
                ];
            }
        }
        usort($activity, static function ($left, $right) {
            return strtotime((string) ($right['time'] ?? '')) <=> strtotime((string) ($left['time'] ?? ''));
        });
        return array_slice($activity, 0, max(1, (int) $limit));
    }
}
