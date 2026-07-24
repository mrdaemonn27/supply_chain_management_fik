<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kaprodi_model extends CI_Model {
    private $table = 'kaprodi_pengajuan';
    private $itemTable = 'kaprodi_pengajuan_item';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_tables();
    }

    private function ensure_tables() {
        if (!$this->db->table_exists($this->table)) {
            $this->db->query("CREATE TABLE `kaprodi_pengajuan` (
                `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
                `kode_pengajuan` varchar(40) NOT NULL,
                `id_user` int(11) NOT NULL,
                `jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang',
                `nama_prodi` varchar(150) NOT NULL,
                `nama_pengajuan` varchar(200) NOT NULL,
                `kebutuhan_lab` text DEFAULT NULL,
                `anak_perusahaan` varchar(150) DEFAULT NULL,
                `status` varchar(60) NOT NULL DEFAULT 'Pengajuan',
                `catatan_negosiasi` text DEFAULT NULL,
                `catatan_alokasi` text DEFAULT NULL,
                `bast_nomor` varchar(100) DEFAULT NULL,
                `bast_tanggal` date DEFAULT NULL,
                `bast_penerima` varchar(150) DEFAULT NULL,
                `bast_catatan` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id_pengajuan`),
                UNIQUE KEY `kode_pengajuan` (`kode_pengajuan`),
                KEY `idx_kaprodi_user` (`id_user`),
                KEY `idx_kaprodi_status` (`status`),
                KEY `idx_kaprodi_jenis` (`jenis_pengajuan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            $this->ensure_column($this->table, 'jenis_pengajuan', "`jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang' AFTER `id_user`");
            $this->ensure_status_varchar($this->table);
        }

        if (!$this->db->table_exists($this->itemTable)) {
            $this->db->query("CREATE TABLE `kaprodi_pengajuan_item` (
                `id_item` int(11) NOT NULL AUTO_INCREMENT,
                `id_pengajuan` int(11) NOT NULL,
                `no_urut` int(11) NOT NULL DEFAULT 1,
                `uraian_barang` varchar(255) NOT NULL,
                `vol` decimal(12,2) NOT NULL DEFAULT 1.00,
                `satuan` varchar(50) NOT NULL DEFAULT 'unit',
                `harga_penawaran_sat` decimal(18,2) NOT NULL DEFAULT 0.00,
                `link_penawaran` text DEFAULT NULL,
                `hasil_negosiasi_vol` decimal(12,2) DEFAULT NULL,
                `hasil_negosiasi_sat` decimal(18,2) DEFAULT NULL,
                `garansi` varchar(150) DEFAULT NULL,
                `alokasi_sisa` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_item`),
                KEY `idx_item_pengajuan` (`id_pengajuan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
    }

    private function ensure_column($table, $field, $definition) {
        if (!$this->db->field_exists($field, $table)) {
            $this->db->query("ALTER TABLE `{$table}` ADD {$definition}");
        }
    }

    private function ensure_status_varchar($table) {
        $column = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE 'status'")->row();
        if ($column && stripos((string) $column->Type, 'enum') !== false) {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `status` varchar(60) NOT NULL DEFAULT 'Pengajuan'");
        }
    }

    public function create_pengajuan($header, $items) {
        $this->db->trans_start();
        $this->db->insert($this->table, $header);
        $id_pengajuan = $this->db->insert_id();

        foreach ($items as $index => $item) {
            if (trim($item['uraian_barang']) === '') {
                continue;
            }

            $item['id_pengajuan'] = $id_pengajuan;
            $item['no_urut'] = $index + 1;
            $this->db->insert($this->itemTable, $item);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $id_pengajuan : false;
    }

    public function get_all_by_user($id_user = null) {
        return $this->get_filtered_by_user($id_user, [], null, null);
    }

    public function count_filtered_by_user($id_user, $filters = []) {
        $this->db->from($this->table);
        $this->apply_filters($id_user, $filters);
        return $this->db->count_all_results();
    }

    public function get_filtered_by_user($id_user, $filters = [], $limit = 10, $offset = 0) {
        $this->db->select('kaprodi_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id_user = kaprodi_pengajuan.id_user', 'left');
        $this->apply_filters($id_user, $filters);
        $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }

        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->items = $this->get_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }

        return $rows;
    }

    private function apply_filters($id_user, $filters) {
        if ($id_user !== null) {
            $this->db->where('kaprodi_pengajuan.id_user', $id_user);
        }

        if (!empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $this->db->group_start();
            $this->db->like('kaprodi_pengajuan.kode_pengajuan', $keyword);
            $this->db->or_like('kaprodi_pengajuan.nama_pengajuan', $keyword);
            $this->db->or_like('kaprodi_pengajuan.nama_prodi', $keyword);
            $this->db->or_like('kaprodi_pengajuan.kebutuhan_lab', $keyword);
            $this->db->group_end();
        }

        if (!empty($filters['status'])) {
            $this->db->where('kaprodi_pengajuan.status', $filters['status']);
        }

        if (!empty($filters['jenis_pengajuan'])) {
            $this->db->where('kaprodi_pengajuan.jenis_pengajuan', $filters['jenis_pengajuan']);
        }

        if (!empty($filters['tanggal_dari'])) {
            $this->db->where('DATE(kaprodi_pengajuan.created_at) >=', $filters['tanggal_dari']);
        }

        if (!empty($filters['tanggal_sampai'])) {
            $this->db->where('DATE(kaprodi_pengajuan.created_at) <=', $filters['tanggal_sampai']);
        }
    }

    public function get_stats_by_user($id_user) {
        $rows = $this->get_filtered_by_user($id_user, [], null, null);
        $stats = [
            'total' => count($rows),
            'pengajuan' => 0,
            'negosiasi' => 0,
            'deal' => 0,
            'selesai' => 0,
        ];

        foreach ($rows as $row) {
            $status = strtolower((string) $row->status);
            if ($status === 'pengajuan') {
                $stats['pengajuan']++;
            } elseif (strpos($status, 'negosiasi') !== false) {
                $stats['negosiasi']++;
            } elseif (in_array($row->status, ['Deal', 'Disetujui', 'Approval', 'BAST'], true)) {
                $stats['deal']++;
            } elseif ($row->status === 'Selesai') {
                $stats['selesai']++;
            }
        }

        return $stats;
    }

    public function get_by_id($id_pengajuan) {
        $pengajuan = $this->db->get_where($this->table, ['id_pengajuan' => $id_pengajuan])->row();
        if (!$pengajuan) {
            return null;
        }
        $pengajuan->items = $this->get_items($id_pengajuan);
        $pengajuan->summary = $this->calculate_summary($pengajuan->items);
        return $pengajuan;
    }

    public function get_items($id_pengajuan) {
        return $this->db
            ->where('id_pengajuan', $id_pengajuan)
            ->order_by('no_urut', 'ASC')
            ->get($this->itemTable)
            ->result();
    }

    public function update_status($id_pengajuan, $status, $extra = []) {
        $extra['status'] = $status;
        $this->db->where('id_pengajuan', $id_pengajuan);
        return $this->db->update($this->table, $extra);
    }

    public function update_alokasi_item($id_item, $alokasi_sisa) {
        $this->db->where('id_item', $id_item);
        return $this->db->update($this->itemTable, ['alokasi_sisa' => $alokasi_sisa]);
    }

    public function calculate_summary($items) {
        $subtotal_penawaran = 0;
        $subtotal_markup = 0;
        $subtotal_negosiasi = 0;

        foreach ($items as $item) {
            $vol = (float) ($item->vol ?? 0);
            $harga = (float) ($item->harga_penawaran_sat ?? 0);
            $nego_vol = isset($item->hasil_negosiasi_vol) && $item->hasil_negosiasi_vol !== null ? (float) $item->hasil_negosiasi_vol : $vol;
            $nego_harga = isset($item->hasil_negosiasi_sat) && $item->hasil_negosiasi_sat !== null ? (float) $item->hasil_negosiasi_sat : 0;

            $subtotal_penawaran += $vol * $harga;
            $subtotal_markup += $vol * ($harga * 1.2);
            $subtotal_negosiasi += $nego_vol * $nego_harga;
        }

        $pajak_20 = $subtotal_penawaran * 0.20;
        $total_setelah_pajak = $subtotal_penawaran + $pajak_20;
        $ppn_penawaran = $subtotal_markup * 0.11;
        $ppn_negosiasi = $subtotal_negosiasi * 0.11;
        $total_penawaran = $subtotal_markup + $ppn_penawaran;
        $total_negosiasi = $subtotal_negosiasi + $ppn_negosiasi;

        return [
            'subtotal_penawaran' => $subtotal_penawaran,
            'pajak_20' => $pajak_20,
            'total_setelah_pajak' => $total_setelah_pajak,
            'subtotal_markup' => $subtotal_markup,
            'ppn_penawaran' => $ppn_penawaran,
            'total_penawaran' => $total_penawaran,
            'subtotal_negosiasi' => $subtotal_negosiasi,
            'ppn_negosiasi' => $ppn_negosiasi,
            'total_negosiasi' => $total_negosiasi,
            'sisa_alokasi' => max(0, $total_penawaran - $total_negosiasi),
        ];
    }

    public function get_status_options() {
        return [
            'Pengajuan',
            'Revisi',
            'Negosiasi',
            'Sedang Negosiasi',
            'Deal',
            'Disetujui',
            'Ditolak',
            'Approval',
            'BAST',
            'Inventarisasi',
            'Selesai',
        ];
    }

    public function generate_kode() {
        return 'KPRD-' . date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    }
}
