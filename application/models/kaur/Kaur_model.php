<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kaur_model extends CI_Model {
    private $table = 'kaur_pengajuan';
    private $itemTable = 'kaur_pengajuan_item';
    private $kaprodiTable = 'kaprodi_pengajuan';
    private $kaprodiItemTable = 'kaprodi_pengajuan_item';
    private $negosiasiTable = 'pengadaan_negosiasi';
    private $anggaranTable = 'pengadaan_anggaran';
    private $bastTable = 'pengadaan_bast';
    private $evidenceTable = 'pengadaan_evidence';
    private $inventoryLinkTable = 'pengadaan_inventory_link';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->ensure_tables();
    }

    private function ensure_tables() {
        $this->ensure_kaur_tables();
        $this->ensure_kaprodi_tables();
        $this->ensure_procurement_tables();
        $this->ensure_inventory_columns();
    }

    private function ensure_kaur_tables() {
        if (!$this->db->table_exists($this->table)) {
            $this->db->query("CREATE TABLE `kaur_pengajuan` (
                `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
                `kode_pengajuan` varchar(40) NOT NULL,
                `id_user` int(11) NOT NULL,
                `jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang',
                `nama_lab` varchar(150) NOT NULL,
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
                `bast_disetujui_oleh` int(11) DEFAULT NULL,
                `bast_disetujui_pada` datetime DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id_pengajuan`),
                UNIQUE KEY `kode_pengajuan` (`kode_pengajuan`),
                KEY `idx_kaur_user` (`id_user`),
                KEY `idx_kaur_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            $this->ensure_column($this->table, 'jenis_pengajuan', "`jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang' AFTER `id_user`");
            $this->ensure_status_varchar($this->table);
        }

        if (!$this->db->table_exists($this->itemTable)) {
            $this->db->query("CREATE TABLE `kaur_pengajuan_item` (
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
                KEY `idx_kaur_item_pengajuan` (`id_pengajuan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
    }

    private function ensure_kaprodi_tables() {
        if (!$this->db->table_exists($this->kaprodiTable)) {
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
                `catatan_approval` text DEFAULT NULL,
                `bast_nomor` varchar(100) DEFAULT NULL,
                `bast_tanggal` date DEFAULT NULL,
                `bast_penerima` varchar(150) DEFAULT NULL,
                `bast_catatan` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id_pengajuan`),
                UNIQUE KEY `kode_pengajuan` (`kode_pengajuan`),
                KEY `idx_kaprodi_user` (`id_user`),
                KEY `idx_kaprodi_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            $this->ensure_column($this->kaprodiTable, 'jenis_pengajuan', "`jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang' AFTER `id_user`");
            $this->ensure_column($this->kaprodiTable, 'catatan_approval', "`catatan_approval` text DEFAULT NULL AFTER `catatan_alokasi`");
            $this->ensure_status_varchar($this->kaprodiTable);
        }

        if (!$this->db->table_exists($this->kaprodiItemTable)) {
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

    private function ensure_procurement_tables() {
        if (!$this->db->table_exists($this->negosiasiTable)) {
            $this->db->query("CREATE TABLE `pengadaan_negosiasi` (
                `id_negosiasi` int(11) NOT NULL AUTO_INCREMENT,
                `sumber` enum('kaprodi','kaur') NOT NULL DEFAULT 'kaprodi',
                `id_pengajuan` int(11) NOT NULL,
                `id_item` int(11) NOT NULL,
                `vendor` varchar(180) DEFAULT NULL,
                `harga_awal` decimal(18,2) NOT NULL DEFAULT 0.00,
                `harga_negosiasi` decimal(18,2) NOT NULL DEFAULT 0.00,
                `volume_negosiasi` decimal(12,2) NOT NULL DEFAULT 1.00,
                `garansi` varchar(150) DEFAULT NULL,
                `catatan` text DEFAULT NULL,
                `status` enum('Belum Negosiasi','Sedang Negosiasi','Deal','Ditolak') NOT NULL DEFAULT 'Belum Negosiasi',
                `created_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_negosiasi`),
                KEY `idx_negosiasi_item` (`id_item`),
                KEY `idx_negosiasi_pengajuan` (`id_pengajuan`),
                KEY `idx_negosiasi_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!$this->db->table_exists($this->anggaranTable)) {
            $this->db->query("CREATE TABLE `pengadaan_anggaran` (
                `id_anggaran` int(11) NOT NULL AUTO_INCREMENT,
                `tahun` int(4) NOT NULL,
                `total_anggaran` decimal(18,2) NOT NULL DEFAULT 0.00,
                `catatan` text DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_anggaran`),
                KEY `idx_anggaran_tahun` (`tahun`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!$this->db->table_exists($this->bastTable)) {
            $this->db->query("CREATE TABLE `pengadaan_bast` (
                `id_bast` int(11) NOT NULL AUTO_INCREMENT,
                `id_pengajuan` int(11) NOT NULL,
                `nomor_bast` varchar(120) NOT NULL,
                `tanggal_bast` date NOT NULL,
                `jenis_bast` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang',
                `file_bast` varchar(255) DEFAULT NULL,
                `catatan` text DEFAULT NULL,
                `input_by` int(11) DEFAULT NULL,
                `inventory_processed_at` datetime DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_bast`),
                KEY `idx_bast_pengajuan` (`id_pengajuan`),
                KEY `idx_bast_nomor` (`nomor_bast`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!$this->db->table_exists($this->evidenceTable)) {
            $this->db->query("CREATE TABLE `pengadaan_evidence` (
                `id_evidence` int(11) NOT NULL AUTO_INCREMENT,
                `ref_type` varchar(60) NOT NULL,
                `ref_id` int(11) NOT NULL,
                `judul` varchar(180) DEFAULT NULL,
                `file_path` varchar(255) NOT NULL,
                `mime` varchar(120) DEFAULT NULL,
                `catatan` text DEFAULT NULL,
                `uploaded_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_evidence`),
                KEY `idx_evidence_ref` (`ref_type`, `ref_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!$this->db->table_exists($this->inventoryLinkTable)) {
            $this->db->query("CREATE TABLE `pengadaan_inventory_link` (
                `id_link` int(11) NOT NULL AUTO_INCREMENT,
                `id_bast` int(11) NOT NULL,
                `id_pengajuan` int(11) NOT NULL,
                `id_item` int(11) NOT NULL,
                `id_aset` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_link`),
                UNIQUE KEY `uniq_bast_item` (`id_bast`, `id_item`),
                KEY `idx_inventory_pengajuan` (`id_pengajuan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
    }

    private function ensure_inventory_columns() {
        if (!$this->db->table_exists('aset')) {
            return;
        }

        $this->ensure_column('aset', 'qr_code', "`qr_code` varchar(120) DEFAULT NULL AFTER `kode_aset`");
        $this->ensure_column('aset', 'qr_url', "`qr_url` text DEFAULT NULL AFTER `qr_code`");
        $this->ensure_column('aset', 'sumber_bast_id', "`sumber_bast_id` int(11) DEFAULT NULL AFTER `qr_url`");
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
        $this->db->select('kaur_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id_user = kaur_pengajuan.id_user', 'left');
        if ($id_user !== null) {
            $this->db->where('kaur_pengajuan.id_user', $id_user);
        }
        $this->db->order_by('kaur_pengajuan.created_at', 'DESC');
        $rows = $this->db->get()->result();

        foreach ($rows as $row) {
            $row->items = $this->get_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }

        return $rows;
    }

    public function count_kaprodi_pengajuan($filters = []) {
        $this->db->from($this->kaprodiTable);
        $this->apply_kaprodi_filters($filters);
        return $this->db->count_all_results();
    }

    private function count_kaprodi_statuses($statuses) {
        $this->db->from($this->kaprodiTable);
        $this->db->where_in('status', (array) $statuses);
        return $this->db->count_all_results();
    }

    public function get_kaprodi_pengajuan($filters = [], $limit = 10, $offset = 0) {
        $this->db->select('kaprodi_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->kaprodiTable);
        $this->db->join('users', 'users.id_user = kaprodi_pengajuan.id_user', 'left');
        $this->apply_kaprodi_filters($filters);
        $this->db->order_by('kaprodi_pengajuan.updated_at', 'DESC');
        $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }

        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->items = $this->get_kaprodi_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }

        return $rows;
    }

    public function get_kaprodi_pengajuan_acc_report($filters = []) {
        $allowed_status = ['Pengajuan', 'Revisi', 'Sedang Negosiasi', 'Deal', 'Disetujui', 'Approval'];
        $this->db->select('kaprodi_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->kaprodiTable);
        $this->db->join('users', 'users.id_user = kaprodi_pengajuan.id_user', 'left');
        $this->db->where_in('kaprodi_pengajuan.status', $allowed_status);
        $this->apply_kaprodi_filters($filters);
        $this->db->order_by('kaprodi_pengajuan.updated_at', 'DESC');
        $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');

        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->items = $this->get_kaprodi_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }

        return $rows;
    }

    private function apply_kaprodi_filters($filters) {
        if (!empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $this->db->group_start();
            $this->db->like('kaprodi_pengajuan.kode_pengajuan', $keyword);
            $this->db->or_like('kaprodi_pengajuan.nama_pengajuan', $keyword);
            $this->db->or_like('kaprodi_pengajuan.nama_prodi', $keyword);
            $this->db->or_like('kaprodi_pengajuan.kebutuhan_lab', $keyword);
            $this->db->or_where("kaprodi_pengajuan.id_pengajuan IN (
                SELECT i.id_pengajuan
                FROM `{$this->kaprodiItemTable}` i
                WHERE i.uraian_barang LIKE " . $this->db->escape('%' . $keyword . '%') . "
            )", null, false);
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

        if (!empty($filters['vendor'])) {
            $vendor = trim($filters['vendor']);
            $this->db->where("kaprodi_pengajuan.id_pengajuan IN (
                SELECT n.id_pengajuan
                FROM `{$this->negosiasiTable}` n
                WHERE n.vendor LIKE " . $this->db->escape('%' . $vendor . '%') . "
            )", null, false);
        }

        if (!empty($filters['status_negosiasi'])) {
            $this->db->where("kaprodi_pengajuan.id_pengajuan IN (
                SELECT n.id_pengajuan
                FROM `{$this->negosiasiTable}` n
                INNER JOIN (
                    SELECT id_item, MAX(id_negosiasi) AS max_id
                    FROM `{$this->negosiasiTable}`
                    GROUP BY id_item
                ) latest ON latest.max_id = n.id_negosiasi
                WHERE n.status = " . $this->db->escape($filters['status_negosiasi']) . "
            )", null, false);
        }
    }

    public function get_kaprodi_by_id($id_pengajuan) {
        $pengajuan = $this->db->get_where($this->kaprodiTable, ['id_pengajuan' => $id_pengajuan])->row();
        if (!$pengajuan) {
            return null;
        }

        $pengajuan->items = $this->get_kaprodi_items($id_pengajuan);
        $pengajuan->summary = $this->calculate_summary($pengajuan->items);
        return $pengajuan;
    }

    public function get_bast_ready_pengajuan($limit = null) {
        $this->db->select('kaprodi_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->kaprodiTable);
        $this->db->join('users', 'users.id_user = kaprodi_pengajuan.id_user', 'left');
        $this->db->where_in('kaprodi_pengajuan.status', ['Disetujui', 'Approval']);
        $this->db->where("kaprodi_pengajuan.id_pengajuan NOT IN (SELECT id_pengajuan FROM `{$this->bastTable}`)", null, false);
        $this->db->order_by('kaprodi_pengajuan.updated_at', 'DESC');
        $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');
        if ($limit !== null) {
            $this->db->limit((int) $limit);
        }

        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->items = $this->get_kaprodi_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }

        return $rows;
    }

    public function pengajuan_has_bast($id_pengajuan) {
        if (!$this->db->table_exists($this->bastTable)) {
            return false;
        }

        $this->db->where('id_pengajuan', (int) $id_pengajuan);
        return $this->db->count_all_results($this->bastTable) > 0;
    }

    public function kaprodi_all_items_deal($id_pengajuan) {
        $items = $this->db->where('id_pengajuan', $id_pengajuan)->get($this->kaprodiItemTable)->result();
        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            $latest = $this->get_latest_negosiasi($item->id_item);
            if (!$latest || $latest->status !== 'Deal') {
                return false;
            }
        }

        return true;
    }

    public function get_kaprodi_items($id_pengajuan) {
        $items = $this->db
            ->where('id_pengajuan', $id_pengajuan)
            ->order_by('no_urut', 'ASC')
            ->get($this->kaprodiItemTable)
            ->result();

        foreach ($items as $item) {
            $item->latest_negosiasi = $this->get_latest_negosiasi($item->id_item);
            if ($item->latest_negosiasi) {
                $item->hasil_negosiasi_vol = $item->latest_negosiasi->volume_negosiasi;
                $item->hasil_negosiasi_sat = $item->latest_negosiasi->harga_negosiasi;
                $item->harga_penawaran_sat = $item->latest_negosiasi->harga_awal;
                $item->garansi = $item->latest_negosiasi->garansi;
            }
        }

        return $items;
    }

    public function get_latest_negosiasi($id_item) {
        if (!$this->db->table_exists($this->negosiasiTable)) {
            return null;
        }

        return $this->db
            ->where('id_item', $id_item)
            ->order_by('id_negosiasi', 'DESC')
            ->limit(1)
            ->get($this->negosiasiTable)
            ->row();
    }

    public function get_negosiasi_history($id_item) {
        return $this->db
            ->where('id_item', $id_item)
            ->order_by('id_negosiasi', 'DESC')
            ->get($this->negosiasiTable)
            ->result();
    }

    public function save_negosiasi($id_pengajuan, $id_item, $data) {
        $allowed = ['Belum Negosiasi', 'Sedang Negosiasi', 'Deal', 'Ditolak'];
        if (!in_array($data['status'], $allowed, true)) {
            $data['status'] = 'Belum Negosiasi';
        }

        $data['sumber'] = 'kaprodi';
        $data['id_pengajuan'] = (int) $id_pengajuan;
        $data['id_item'] = (int) $id_item;
        $data['created_by'] = $data['created_by'] ?? null;

        $this->db->trans_start();
        $this->db->insert($this->negosiasiTable, $data);
        $this->db
            ->where('id_item', $id_item)
            ->update($this->kaprodiItemTable, [
                'harga_penawaran_sat' => $data['harga_awal'],
                'hasil_negosiasi_vol' => $data['volume_negosiasi'],
                'hasil_negosiasi_sat' => $data['harga_negosiasi'],
                'garansi' => $data['garansi'],
            ]);
        $this->sync_pengajuan_status($id_pengajuan);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    private function sync_pengajuan_status($id_pengajuan) {
        $current = $this->db
            ->select('status')
            ->where('id_pengajuan', $id_pengajuan)
            ->get($this->kaprodiTable)
            ->row();
        $items = $this->db->where('id_pengajuan', $id_pengajuan)->get($this->kaprodiItemTable)->result();
        if (empty($items)) {
            return $this->update_kaprodi_status($id_pengajuan, 'Pengajuan');
        }

        $has_negosiasi = false;
        $all_deal = true;
        $all_rejected = true;
        foreach ($items as $item) {
            $latest = $this->get_latest_negosiasi($item->id_item);
            if (!$latest) {
                $all_deal = false;
                $all_rejected = false;
                continue;
            }

            $has_negosiasi = true;
            if ($latest->status !== 'Deal') {
                $all_deal = false;
            }
            if ($latest->status !== 'Ditolak') {
                $all_rejected = false;
            }
        }

        if ($current && in_array($current->status, ['Disetujui', 'Approval', 'BAST', 'Inventarisasi', 'Selesai'], true)) {
            return true;
        }

        if ($all_deal) {
            return $this->update_kaprodi_status($id_pengajuan, 'Deal');
        }

        if ($all_rejected) {
            return $this->update_kaprodi_status($id_pengajuan, 'Ditolak');
        }

        return $this->update_kaprodi_status($id_pengajuan, $has_negosiasi ? 'Sedang Negosiasi' : 'Pengajuan');
    }

    public function update_kaprodi_status($id_pengajuan, $status, $catatan = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($catatan !== null) {
            $data['catatan_approval'] = $catatan;
        }

        $this->db->where('id_pengajuan', $id_pengajuan);
        return $this->db->update($this->kaprodiTable, $data);
    }

    public function save_anggaran($data) {
        $this->db->insert($this->anggaranTable, $data);
        return $this->db->insert_id();
    }

    public function get_anggaran_summary($tahun = null) {
        $tahun = $tahun ?: (int) date('Y');
        $anggaran = $this->db
            ->where('tahun', $tahun)
            ->order_by('id_anggaran', 'DESC')
            ->limit(1)
            ->get($this->anggaranTable)
            ->row();

        $total = $anggaran ? (float) $anggaran->total_anggaran : 0;
        $deal = $this->get_total_deal_summary();
        $pengeluaran = (float) $deal['total_negosiasi'];
        $sisa = max(0, $total - $pengeluaran);
        $persen = $total > 0 ? min(100, ($pengeluaran / $total) * 100) : 0;

        return [
            'tahun' => $tahun,
            'total_anggaran' => $total,
            'total_pengadaan_deal' => (float) $deal['total_awal'],
            'total_pengeluaran' => $pengeluaran,
            'sisa_anggaran' => $sisa,
            'penghematan_capex' => max(0, (float) $deal['total_awal'] - $pengeluaran),
            'belum_terealisasi' => $this->count_kaprodi_pengajuan(['status' => 'Pengajuan']) + $this->count_kaprodi_pengajuan(['status' => 'Revisi']) + $this->count_kaprodi_pengajuan(['status' => 'Sedang Negosiasi']),
            'persentase_penggunaan' => $persen,
            'catatan' => $anggaran ? $anggaran->catatan : null,
        ];
    }

    private function get_total_deal_summary() {
        $sql = "SELECT
                COALESCE(SUM(n.harga_awal * n.volume_negosiasi), 0) AS total_awal,
                COALESCE(SUM(n.harga_negosiasi * n.volume_negosiasi), 0) AS total_negosiasi
            FROM `{$this->negosiasiTable}` n
            INNER JOIN (
                SELECT id_item, MAX(id_negosiasi) AS max_id
                FROM `{$this->negosiasiTable}`
                GROUP BY id_item
            ) latest ON latest.max_id = n.id_negosiasi
            WHERE n.status = 'Deal'";
        $row = $this->db->query($sql)->row();
        return [
            'total_awal' => $row ? (float) $row->total_awal : 0,
            'total_negosiasi' => $row ? (float) $row->total_negosiasi : 0,
        ];
    }

    public function save_bast($id_pengajuan, $data) {
        $data['id_pengajuan'] = (int) $id_pengajuan;

        $this->db->trans_start();
        $this->db->insert($this->bastTable, $data);
        $id_bast = $this->db->insert_id();
        $this->db->where('id_pengajuan', $id_pengajuan)->update($this->kaprodiTable, [
            'bast_nomor' => $data['nomor_bast'] ?? null,
            'bast_tanggal' => $data['tanggal_bast'] ?? null,
            'bast_catatan' => $data['catatan'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->update_kaprodi_status($id_pengajuan, 'BAST');
        $this->process_inventory_from_bast($id_bast, $id_pengajuan);
        $this->db->trans_complete();

        return $this->db->trans_status() ? $id_bast : false;
    }

    public function get_bast_list($limit = 12) {
        $this->db->select('pengadaan_bast.*, kaprodi_pengajuan.kode_pengajuan, kaprodi_pengajuan.nama_pengajuan, kaprodi_pengajuan.nama_prodi');
        $this->db->from($this->bastTable);
        $this->db->join($this->kaprodiTable, 'kaprodi_pengajuan.id_pengajuan = pengadaan_bast.id_pengajuan', 'left');
        $this->db->order_by('pengadaan_bast.created_at', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit);
        }
        return $this->db->get()->result();
    }

    private function process_inventory_from_bast($id_bast, $id_pengajuan) {
        $pengajuan = $this->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan || $pengajuan->jenis_pengajuan !== 'Barang') {
            $this->db->where('id_bast', $id_bast)->update($this->bastTable, ['inventory_processed_at' => date('Y-m-d H:i:s')]);
            return true;
        }

        $id_ruangan = $this->get_default_ruangan_id();
        if (!$id_ruangan || !$this->db->table_exists('aset')) {
            return false;
        }

        foreach ($pengajuan->items as $item) {
            $exists = $this->db
                ->where('id_bast', $id_bast)
                ->where('id_item', $item->id_item)
                ->get($this->inventoryLinkTable)
                ->row();
            if ($exists) {
                continue;
            }

            $latest = $item->latest_negosiasi;
            $qty = $latest && (float) $latest->volume_negosiasi > 0 ? (int) ceil((float) $latest->volume_negosiasi) : (int) ceil((float) $item->vol);
            $qty = max(1, $qty);
            $kode = 'INV-' . str_pad((string) $id_bast, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string) $item->id_item, 4, '0', STR_PAD_LEFT);

            $aset = [
                'id_ruangan' => $id_ruangan,
                'nama_aset' => $item->uraian_barang,
                'kode_aset' => $kode,
                'deskripsi' => 'Inventaris otomatis dari BAST pengajuan ' . $pengajuan->kode_pengajuan,
                'jumlah_total' => $qty,
                'jumlah_tersedia' => $qty,
                'kondisi' => 'Baik',
                'total_peminjaman' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($this->db->field_exists('sumber_bast_id', 'aset')) {
                $aset['sumber_bast_id'] = $id_bast;
            }

            $this->db->insert('aset', $aset);
            $id_aset = $this->db->insert_id();
            $qr_code = 'ASET-' . $id_aset . '-' . strtoupper(substr(md5($kode), 0, 6));
            $qr_url = site_url('peminjaman/detail_barang/' . $id_aset);

            $qr_update = [];
            if ($this->db->field_exists('qr_code', 'aset')) {
                $qr_update['qr_code'] = $qr_code;
            }
            if ($this->db->field_exists('qr_url', 'aset')) {
                $qr_update['qr_url'] = $qr_url;
            }
            if (!empty($qr_update)) {
                $this->db->where('id_aset', $id_aset)->update('aset', $qr_update);
            }

            $this->db->insert($this->inventoryLinkTable, [
                'id_bast' => $id_bast,
                'id_pengajuan' => $id_pengajuan,
                'id_item' => $item->id_item,
                'id_aset' => $id_aset,
            ]);
        }

        $this->db->where('id_bast', $id_bast)->update($this->bastTable, ['inventory_processed_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    private function get_default_ruangan_id() {
        $row = $this->db->order_by('id_ruangan', 'ASC')->limit(1)->get('ruangan')->row();
        return $row ? (int) $row->id_ruangan : null;
    }

    public function get_laporan_negosiasi_deal($filters = [], $limit = null, $offset = 0) {
        $sql = "SELECT n.*, i.uraian_barang, i.satuan, p.kode_pengajuan, p.nama_pengajuan, p.nama_prodi, p.jenis_pengajuan,
                (n.harga_awal - n.harga_negosiasi) AS selisih_harga
            FROM `{$this->negosiasiTable}` n
            INNER JOIN (
                SELECT id_item, MAX(id_negosiasi) AS max_id
                FROM `{$this->negosiasiTable}`
                GROUP BY id_item
            ) latest ON latest.max_id = n.id_negosiasi
            INNER JOIN `{$this->kaprodiItemTable}` i ON i.id_item = n.id_item
            INNER JOIN `{$this->kaprodiTable}` p ON p.id_pengajuan = n.id_pengajuan
            WHERE n.status = 'Deal'";

        $params = [];
        if (!empty($filters['vendor'])) {
            $sql .= " AND n.vendor LIKE ?";
            $params[] = '%' . $filters['vendor'] . '%';
        }
        if (!empty($filters['jenis_pengajuan'])) {
            $sql .= " AND p.jenis_pengajuan = ?";
            $params[] = $filters['jenis_pengajuan'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (p.kode_pengajuan LIKE ? OR p.nama_pengajuan LIKE ? OR i.uraian_barang LIKE ?)";
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['status_negosiasi'])) {
            $sql .= " AND n.status = ?";
            $params[] = $filters['status_negosiasi'];
        }
        if (!empty($filters['tanggal_dari'])) {
            $sql .= " AND DATE(n.created_at) >= ?";
            $params[] = $filters['tanggal_dari'];
        }
        if (!empty($filters['tanggal_sampai'])) {
            $sql .= " AND DATE(n.created_at) <= ?";
            $params[] = $filters['tanggal_sampai'];
        }

        $sql .= " ORDER BY n.created_at DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $offset . ", " . (int) $limit;
        }

        return $this->db->query($sql, $params)->result();
    }

    public function get_dashboard_stats() {
        return [
            'pengajuan' => $this->count_kaprodi_pengajuan([]),
            'negosiasi' => $this->count_kaprodi_pengajuan(['status' => 'Sedang Negosiasi']),
            'deal' => $this->count_kaprodi_statuses(['Deal', 'Disetujui', 'Approval']),
            'bast' => count($this->get_bast_list(null)),
            'laporan_deal' => count($this->get_laporan_negosiasi_deal()),
        ];
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

    public function get_approval_bast_queue($id_user = null) {
        $this->db->select('kaur_pengajuan.*, users.nama_lengkap');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id_user = kaur_pengajuan.id_user', 'left');
        $this->db->where('kaur_pengajuan.status', 'Approval Tahap 1 (BAST)');
        if ($id_user !== null) {
            $this->db->where('kaur_pengajuan.id_user', $id_user);
        }
        $this->db->order_by('kaur_pengajuan.updated_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_laporan_maintenance($limit = 12) {
        $this->db->select('maintenance.*, aset.nama_aset, aset.kode_aset, ruangan.nama_ruangan');
        $this->db->from('maintenance');
        $this->db->join('aset', 'aset.id_aset = maintenance.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->order_by('maintenance.tanggal_maintenance', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit);
        }
        return $this->db->get()->result();
    }

    public function get_laporan_laboratorium() {
        $this->db->select('ruangan.id_ruangan, ruangan.nama_ruangan, ruangan.icon, ruangan.warna, ruangan.deskripsi');
        $this->db->select('COUNT(aset.id_aset) as total_jenis, COALESCE(SUM(aset.jumlah_total), 0) as total_unit, COALESCE(SUM(aset.jumlah_tersedia), 0) as tersedia_unit', false);
        $this->db->select("COALESCE(SUM(CASE WHEN aset.kondisi <> 'Baik' THEN 1 ELSE 0 END), 0) as perlu_perhatian", false);
        $this->db->from('ruangan');
        $this->db->join('aset', 'aset.id_ruangan = ruangan.id_ruangan', 'left');
        $this->db->group_by('ruangan.id_ruangan, ruangan.nama_ruangan, ruangan.icon, ruangan.warna, ruangan.deskripsi');
        $this->db->order_by('ruangan.nama_ruangan', 'ASC');
        return $this->db->get()->result();
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

        $ppn_penawaran = $subtotal_markup * 0.11;
        $ppn_negosiasi = $subtotal_negosiasi * 0.11;
        $total_penawaran = $subtotal_markup + $ppn_penawaran;
        $total_negosiasi = $subtotal_negosiasi + $ppn_negosiasi;
        $pajak_20 = $subtotal_penawaran * 0.20;
        $total_setelah_pajak = $subtotal_penawaran + $pajak_20;

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

    public function generate_kode() {
        return 'KAUR-' . date('Ymd-His') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 4));
    }
}
