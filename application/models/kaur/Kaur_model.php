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
                `jenis_pengajuan` varchar(50) NOT NULL DEFAULT 'Barang',
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
            $this->ensure_column($this->table, 'jenis_pengajuan', "`jenis_pengajuan` varchar(50) NOT NULL DEFAULT 'Barang' AFTER `id_user`");
            $this->ensure_varchar($this->table, 'jenis_pengajuan', 50, 'Barang');
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
                `jenis_pengajuan` varchar(50) NOT NULL DEFAULT 'Barang',
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
            $this->ensure_column($this->kaprodiTable, 'jenis_pengajuan', "`jenis_pengajuan` varchar(50) NOT NULL DEFAULT 'Barang' AFTER `id_user`");
            $this->ensure_varchar($this->kaprodiTable, 'jenis_pengajuan', 50, 'Barang');
            $this->ensure_column($this->kaprodiTable, 'catatan_approval', "`catatan_approval` text DEFAULT NULL AFTER `catatan_alokasi`");
            $this->ensure_status_varchar($this->kaprodiTable);
        }

        if (!$this->db->table_exists($this->kaprodiItemTable)) {
            $this->db->query("CREATE TABLE `kaprodi_pengajuan_item` (
                `id_item` int(11) NOT NULL AUTO_INCREMENT,
                `id_pengajuan` int(11) NOT NULL,
                `no_urut` int(11) NOT NULL DEFAULT 1,
                `jenis_item` varchar(30) NOT NULL DEFAULT 'Barang',
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
        } else {
            $this->ensure_column($this->kaprodiItemTable, 'jenis_item', "`jenis_item` varchar(30) NOT NULL DEFAULT 'Barang' AFTER `no_urut`");
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
                `volume_awal` decimal(12,2) NOT NULL DEFAULT 1.00,
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
        } else {
            $this->ensure_column($this->negosiasiTable, 'volume_awal', "`volume_awal` decimal(12,2) NOT NULL DEFAULT 1.00 AFTER `harga_awal`");
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
                `jenis_bast` varchar(50) NOT NULL DEFAULT 'Barang',
                `file_bast` varchar(255) DEFAULT NULL,
                `catatan` text DEFAULT NULL,
                `input_by` int(11) DEFAULT NULL,
                `inventory_processed_at` datetime DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_bast`),
                KEY `idx_bast_pengajuan` (`id_pengajuan`),
                KEY `idx_bast_nomor` (`nomor_bast`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            $this->ensure_varchar($this->bastTable, 'jenis_bast', 50, 'Barang');
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
                `id_bast` int(11) DEFAULT NULL,
                `id_pengajuan` int(11) NOT NULL,
                `id_item` int(11) NOT NULL,
                `id_aset` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_link`),
                UNIQUE KEY `uniq_bast_item` (`id_bast`, `id_item`),
                UNIQUE KEY `uniq_pengajuan_item` (`id_pengajuan`, `id_item`),
                KEY `idx_inventory_pengajuan` (`id_pengajuan`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            $id_bast_column = $this->db->query("SHOW COLUMNS FROM `{$this->inventoryLinkTable}` LIKE 'id_bast'")->row();
            if ($id_bast_column && strtoupper((string) $id_bast_column->Null) !== 'YES') {
                $this->db->query("ALTER TABLE `{$this->inventoryLinkTable}` MODIFY `id_bast` int(11) DEFAULT NULL");
            }

            $duplicate_links = $this->db->query("SELECT 1 FROM `{$this->inventoryLinkTable}` GROUP BY `id_pengajuan`, `id_item` HAVING COUNT(*) > 1 LIMIT 1")->row();
            if (!$duplicate_links && !$this->index_exists($this->inventoryLinkTable, 'uniq_pengajuan_item')) {
                $this->db->query("ALTER TABLE `{$this->inventoryLinkTable}` ADD UNIQUE KEY `uniq_pengajuan_item` (`id_pengajuan`, `id_item`)");
            }
        }
    }

    private function ensure_inventory_columns() {
        if (!$this->db->table_exists('aset')) {
            return;
        }

        $this->ensure_column('aset', 'qr_code', "`qr_code` varchar(120) DEFAULT NULL AFTER `kode_aset`");
        $this->ensure_column('aset', 'qr_url', "`qr_url` text DEFAULT NULL AFTER `qr_code`");
        $this->ensure_column('aset', 'sumber_bast_id', "`sumber_bast_id` int(11) DEFAULT NULL AFTER `qr_url`");
        $this->ensure_column('aset', 'sumber_pengajuan_id', "`sumber_pengajuan_id` int(11) DEFAULT NULL AFTER `sumber_bast_id`");
        $this->ensure_column('aset', 'sumber_pengajuan_item_id', "`sumber_pengajuan_item_id` int(11) DEFAULT NULL AFTER `sumber_pengajuan_id`");

        // Aset hasil pengadaan belum mempunyai lokasi sampai Laboran melakukan
        // distribusi pertama. Foreign key tetap valid karena NULL diizinkan.
        $room_column = $this->db->query("SHOW COLUMNS FROM `aset` LIKE 'id_ruangan'")->row();
        if ($room_column && strtoupper((string) $room_column->Null) !== 'YES') {
            $this->db->query('ALTER TABLE `aset` MODIFY `id_ruangan` int(11) DEFAULT NULL');
        }
    }

    private function index_exists($table, $index) {
        return $this->db
            ->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index])
            ->num_rows() > 0;
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

    private function ensure_varchar($table, $field, $length, $default) {
        $column = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$field}'")->row();
        if ($column && (stripos((string) $column->Type, 'enum') !== false || stripos((string) $column->Type, 'varchar') === false)) {
            $this->db->query("ALTER TABLE `{$table}` MODIFY `{$field}` varchar({$length}) NOT NULL DEFAULT '" . $this->db->escape_str($default) . "'");
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
        $this->apply_kaprodi_sort($filters);
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

        $multi_fields = (array) ($filters['filter_field'] ?? []);
        $multi_values = (array) ($filters['filter_value'] ?? []);
        foreach (array_slice($multi_fields, 0, 4) as $index => $field) {
            $field = trim((string) $field);
            $value = trim((string) ($multi_values[$index] ?? ''));
            if ($value === '') {
                continue;
            }
            $like = '%' . $value . '%';

            switch ($field) {
                case 'kode':
                    $this->db->like('kaprodi_pengajuan.kode_pengajuan', $value);
                    break;
                case 'pengajuan':
                    $this->db->group_start();
                    $this->db->like('kaprodi_pengajuan.kode_pengajuan', $value);
                    $this->db->or_like('kaprodi_pengajuan.nama_pengajuan', $value);
                    $this->db->group_end();
                    break;
                case 'prodi':
                    $this->db->like('kaprodi_pengajuan.nama_prodi', $value);
                    break;
                case 'jenis':
                    $this->db->like('kaprodi_pengajuan.jenis_pengajuan', $value);
                    break;
                case 'kebutuhan':
                    $this->db->group_start();
                    $this->db->like('kaprodi_pengajuan.kebutuhan_lab', $value);
                    $this->db->or_where("kaprodi_pengajuan.id_pengajuan IN (
                        SELECT i.id_pengajuan FROM `{$this->kaprodiItemTable}` i
                        WHERE i.uraian_barang LIKE " . $this->db->escape($like) . "
                    )", null, false);
                    $this->db->group_end();
                    break;
                case 'item':
                    $this->db->where("kaprodi_pengajuan.id_pengajuan IN (
                        SELECT i.id_pengajuan FROM `{$this->kaprodiItemTable}` i
                        WHERE i.uraian_barang LIKE " . $this->db->escape($like) . "
                    )", null, false);
                    break;
                case 'vendor':
                    $this->db->where("kaprodi_pengajuan.id_pengajuan IN (
                        SELECT n.id_pengajuan FROM `{$this->negosiasiTable}` n
                        INNER JOIN (
                            SELECT id_item, MAX(id_negosiasi) AS max_id
                            FROM `{$this->negosiasiTable}` GROUP BY id_item
                        ) latest ON latest.max_id = n.id_negosiasi
                        WHERE n.vendor LIKE " . $this->db->escape($like) . "
                    )", null, false);
                    break;
                case 'status_negosiasi':
                    $this->db->where("kaprodi_pengajuan.id_pengajuan IN (
                        SELECT n.id_pengajuan FROM `{$this->negosiasiTable}` n
                        INNER JOIN (
                            SELECT id_item, MAX(id_negosiasi) AS max_id
                            FROM `{$this->negosiasiTable}` GROUP BY id_item
                        ) latest ON latest.max_id = n.id_negosiasi
                        WHERE n.status LIKE " . $this->db->escape($like) . "
                    )", null, false);
                    break;
                case 'status':
                    $this->db->like('kaprodi_pengajuan.status', $value);
                    break;
                case 'tanggal':
                    $date_range = scm_parse_date_range($value);
                    if ($date_range) {
                        $this->db->where('DATE(kaprodi_pengajuan.created_at) >=', $date_range['start']);
                        $this->db->where('DATE(kaprodi_pengajuan.created_at) <=', $date_range['end']);
                    }
                    break;
                case 'total_harga':
                    $numeric = preg_replace('/[^0-9]/', '', $value);
                    if ($numeric !== '') {
                        $numeric_like = '%' . $numeric . '%';
                        $tax_multiplier = 1 + (defined('SCM_TAX_RATE') ? (float) SCM_TAX_RATE : 0.11);
                        $this->db->where("CAST(COALESCE((
                            SELECT CASE
                                WHEN COALESCE(SUM(CASE WHEN n.status = 'Ditolak' THEN 0 ELSE COALESCE(n.harga_negosiasi, 0) * COALESCE(n.volume_negosiasi, i.vol) END), 0) > 0
                                THEN SUM(CASE WHEN n.status = 'Ditolak' THEN 0 ELSE COALESCE(n.harga_negosiasi, 0) * COALESCE(n.volume_negosiasi, i.vol) END) * {$tax_multiplier}
                                ELSE SUM(i.harga_penawaran_sat * i.vol) * {$tax_multiplier}
                            END
                            FROM `{$this->kaprodiItemTable}` i
                            LEFT JOIN `{$this->negosiasiTable}` n ON n.id_negosiasi = (
                                SELECT MAX(n2.id_negosiasi) FROM `{$this->negosiasiTable}` n2 WHERE n2.id_item = i.id_item
                            )
                            WHERE i.id_pengajuan = kaprodi_pengajuan.id_pengajuan
                        ), 0) AS CHAR) LIKE " . $this->db->escape($numeric_like), null, false);
                    }
                    break;
            }
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

    private function apply_kaprodi_sort($filters) {
        $sort_map = [
            'kode' => 'kaprodi_pengajuan.kode_pengajuan',
            'tanggal' => 'kaprodi_pengajuan.created_at',
            'prodi' => 'kaprodi_pengajuan.nama_prodi',
            'jenis' => 'kaprodi_pengajuan.jenis_pengajuan',
            'status' => 'kaprodi_pengajuan.status',
        ];
        $sort_key = $filters['sort_by'] ?? '';
        $sort_dir = strtoupper((string) ($filters['sort_dir'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';

        if (isset($sort_map[$sort_key])) {
            $this->db->order_by($sort_map[$sort_key], $sort_dir);
            $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');
        } else {
            $this->db->order_by('kaprodi_pengajuan.updated_at', 'DESC');
            $this->db->order_by('kaprodi_pengajuan.created_at', 'DESC');
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

    private function build_bast_rows_query($filters = []) {
        $this->db->from($this->kaprodiTable . ' p');
        $this->db->join('users u', 'u.id_user = p.id_user', 'left');
        $this->db->join($this->bastTable . ' b', "b.id_bast = (SELECT MAX(b2.id_bast) FROM `{$this->bastTable}` b2 WHERE b2.id_pengajuan = p.id_pengajuan)", 'left', false);
        $this->db->group_start()->where_in('p.status', ['Disetujui', 'Approval'])->or_where('b.id_bast IS NOT NULL', null, false)->group_end();

        foreach ((array) $filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'kode') $this->db->like('p.kode_pengajuan', $value);
            elseif ($field === 'prodi') $this->db->group_start()->like('p.nama_prodi', $value)->or_like('p.nama_pengajuan', $value)->group_end();
            elseif ($field === 'jenis') $this->db->like('p.jenis_pengajuan', $value);
            elseif ($field === 'nomor_bast') $this->db->like('b.nomor_bast', $value);
            elseif ($field === 'tanggal_bast') $this->db->where('b.tanggal_bast', $value);
        }
    }

    public function get_bast_rows($filters = [], $limit = 10, $offset = 0) {
        $this->db->select('p.*, u.nama_lengkap, b.id_bast, b.nomor_bast, b.tanggal_bast, b.jenis_bast, b.file_bast, b.catatan AS catatan_bast, b.created_at AS bast_created_at');
        $this->build_bast_rows_query($filters);
        $this->db->order_by('COALESCE(b.created_at, p.updated_at, p.created_at)', 'DESC', false);
        $this->db->limit((int) $limit, (int) $offset);
        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->items = $this->get_kaprodi_items($row->id_pengajuan);
            $row->summary = $this->calculate_summary($row->items);
        }
        return $rows;
    }

    public function count_bast_rows($filters = []) {
        $this->build_bast_rows_query($filters);
        return (int) $this->db->count_all_results();
    }

    public function count_pending_bast_rows() {
        $this->build_bast_rows_query([]);
        $this->db->where('b.id_bast IS NULL', null, false);
        return (int) $this->db->count_all_results();
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

        $has_deal = false;
        foreach ($items as $item) {
            $latest = $this->get_latest_negosiasi($item->id_item);
            $status = $latest->status ?? null;
            if (!in_array($status, ['Deal', 'Ditolak'], true)) {
                return false;
            }
            if ($status === 'Deal') {
                $has_deal = true;
            }
        }

        return $has_deal;
    }

    public function get_kaprodi_items($id_pengajuan) {
        $items = $this->db
            ->where('id_pengajuan', $id_pengajuan)
            ->order_by('no_urut', 'ASC')
            ->get($this->kaprodiItemTable)
            ->result();

        foreach ($items as $item) {
            $item->latest_negosiasi = $this->get_latest_negosiasi($item->id_item);
            $item->harga_awal_referensi = (float) $item->harga_penawaran_sat;
            $item->volume_awal_referensi = (float) $item->vol;
            if ($item->latest_negosiasi) {
                $item->hasil_negosiasi_vol = $item->latest_negosiasi->volume_negosiasi;
                $item->hasil_negosiasi_sat = $item->latest_negosiasi->harga_negosiasi;
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

    public function get_kaprodi_item($id_pengajuan, $id_item) {
        return $this->db
            ->where('id_pengajuan', (int) $id_pengajuan)
            ->where('id_item', (int) $id_item)
            ->get($this->kaprodiItemTable)
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

        $data['sumber'] = 'kaur';
        $data['id_pengajuan'] = (int) $id_pengajuan;
        $data['id_item'] = (int) $id_item;
        $data['created_by'] = $data['created_by'] ?? null;

        $item = $this->get_kaprodi_item($id_pengajuan, $id_item);
        if (!$item) {
            return false;
        }
        $data['harga_awal'] = (float) $item->harga_penawaran_sat;
        $data['volume_awal'] = (float) $item->vol;

        $this->db->trans_start();
        $this->db->insert($this->negosiasiTable, $data);
        $this->db
            ->where('id_item', $id_item)
            ->update($this->kaprodiItemTable, [
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
        $has_pending = false;
        $has_deal = false;
        $all_rejected = true;
        foreach ($items as $item) {
            $latest = $this->get_latest_negosiasi($item->id_item);
            if (!$latest) {
                $has_pending = true;
                $all_rejected = false;
                continue;
            }

            $has_negosiasi = true;
            if ($latest->status === 'Deal') {
                $has_deal = true;
                $all_rejected = false;
            } elseif ($latest->status === 'Ditolak') {
                // Item selesai (final) sebagai ditolak - tidak menahan status pengajuan.
            } else {
                // 'Sedang Negosiasi' atau status lain masih menunggu keputusan.
                $has_pending = true;
                $all_rejected = false;
            }
        }

        if ($current && in_array($current->status, ['BAST', 'Inventarisasi', 'Selesai'], true)) {
            return true;
        }

        // Semua item sudah final (Deal/Ditolak) dan minimal satu Deal -> siap Approval,
        // walaupun ada item lain yang ditolak.
        if (!$has_pending && $has_deal) {
            return $this->update_kaprodi_status($id_pengajuan, 'Approval');
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

    /**
     * Persetujuan final Kaur dan pembuatan master aset harus atomik. Dengan
     * begitu pengajuan tidak pernah terlihat sudah disetujui tanpa aset yang
     * siap didistribusikan oleh Laboran.
     */
    public function finalize_kaprodi_approval($id_pengajuan, $catatan = null) {
        $this->db->trans_begin();

        $locked = $this->db
            ->query("SELECT `id_pengajuan` FROM `{$this->kaprodiTable}` WHERE `id_pengajuan` = ? LIMIT 1 FOR UPDATE", [(int) $id_pengajuan])
            ->row();
        if (!$locked || !$this->update_kaprodi_status($id_pengajuan, 'Disetujui', $catatan)) {
            $this->db->trans_rollback();
            return false;
        }

        $inventory_count = $this->sync_inventory_from_approval($id_pengajuan);
        if ($inventory_count === false || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return ['inventory_count' => (int) $inventory_count];
    }

    /**
     * Menangani data lama yang sudah berstatus Disetujui tetapi sebelumnya
     * masih menunggu BAST untuk masuk ke master aset.
     */
    public function sync_approved_inventory() {
        $rows = $this->db
            ->select('p.id_pengajuan')
            ->distinct()
            ->from($this->kaprodiTable . ' p')
            ->join($this->kaprodiItemTable . ' i', 'i.id_pengajuan = p.id_pengajuan')
            ->join($this->inventoryLinkTable . ' l', 'l.id_pengajuan = p.id_pengajuan AND l.id_item = i.id_item', 'left')
            ->where('p.status', 'Disetujui')
            ->where_in('p.jenis_pengajuan', ['Barang', 'Barang dan Jasa'])
            ->where('l.id_link IS NULL', null, false)
            ->get()
            ->result();

        $created = 0;
        foreach ($rows as $row) {
            $result = $this->sync_inventory_from_approval((int) $row->id_pengajuan);
            if ($result !== false) {
                $created += (int) $result;
            }
        }
        return $created;
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
        $deal = $this->get_total_deal_summary($tahun);
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

    private function get_total_deal_summary($tahun = null) {
        $year_condition = $tahun ? ' AND YEAR(p.created_at) = ' . (int) $tahun : '';
        $tax_multiplier = 1 + SCM_TAX_RATE;
        $sql = "SELECT
                COALESCE(SUM(n.harga_awal * n.volume_awal), 0) * ? AS total_awal,
                COALESCE(SUM(n.harga_negosiasi * n.volume_negosiasi), 0) * ? AS total_negosiasi
            FROM `{$this->negosiasiTable}` n
            INNER JOIN (
                SELECT id_item, MAX(id_negosiasi) AS max_id
                FROM `{$this->negosiasiTable}`
                GROUP BY id_item
            ) latest ON latest.max_id = n.id_negosiasi
            INNER JOIN `{$this->kaprodiTable}` p ON p.id_pengajuan = n.id_pengajuan
            WHERE n.status = 'Deal'{$year_condition}";
        $row = $this->db->query($sql, [$tax_multiplier, $tax_multiplier])->row();
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
        $result = $this->sync_inventory_from_approval($id_pengajuan, $id_bast);
        if ($result === false) {
            return false;
        }

        $this->db->where('id_bast', $id_bast)->update($this->bastTable, ['inventory_processed_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * Membuat satu master aset untuk setiap item barang berstatus Deal.
     * id_ruangan sengaja NULL sampai distribusi pertama dilakukan Laboran.
     */
    private function sync_inventory_from_approval($id_pengajuan, $id_bast = null) {
        $pengajuan = $this->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan || !$this->db->table_exists('aset')) {
            return false;
        }
        if (!in_array($pengajuan->jenis_pengajuan, ['Barang', 'Barang dan Jasa'], true)) {
            return 0;
        }

        $created = 0;
        foreach ($pengajuan->items as $item) {
            if (($item->jenis_item ?? 'Barang') !== 'Barang' || ($item->latest_negosiasi->status ?? null) !== 'Deal') {
                continue;
            }

            $link = $this->db
                ->where('id_pengajuan', (int) $id_pengajuan)
                ->where('id_item', (int) $item->id_item)
                ->get($this->inventoryLinkTable)
                ->row();
            if ($link) {
                if ($id_bast && empty($link->id_bast)) {
                    $this->db->where('id_link', $link->id_link)->update($this->inventoryLinkTable, ['id_bast' => (int) $id_bast]);
                    if ($this->db->field_exists('sumber_bast_id', 'aset')) {
                        $this->db->where('id_aset', $link->id_aset)->update('aset', ['sumber_bast_id' => (int) $id_bast]);
                    }
                }
                continue;
            }

            $latest = $item->latest_negosiasi;
            $qty = max(1, (int) ceil((float) $latest->volume_negosiasi));
            $kode = 'INV-P' . str_pad((string) $id_pengajuan, 4, '0', STR_PAD_LEFT) . '-I' . str_pad((string) $item->id_item, 4, '0', STR_PAD_LEFT);

            // Pulihkan secara idempoten bila aset sempat terbentuk tetapi link
            // gagal tersimpan pada instalasi lama.
            $existing_asset = $this->db->where('kode_aset', $kode)->get('aset')->row();
            if ($existing_asset) {
                $id_aset = (int) $existing_asset->id_aset;
            } else {
                $aset = [
                    'id_ruangan' => null,
                    'nama_aset' => $item->uraian_barang,
                    'kode_aset' => $kode,
                    'deskripsi' => 'Inventaris otomatis dari persetujuan pengajuan ' . $pengajuan->kode_pengajuan,
                    'jumlah_total' => $qty,
                    'jumlah_tersedia' => $qty,
                    'kondisi' => 'Baik',
                    'total_peminjaman' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ($this->db->field_exists('jumlah_reserved', 'aset')) $aset['jumlah_reserved'] = 0;
                if ($this->db->field_exists('jumlah_dipinjam', 'aset')) $aset['jumlah_dipinjam'] = 0;
                if ($this->db->field_exists('sumber_bast_id', 'aset')) $aset['sumber_bast_id'] = $id_bast ? (int) $id_bast : null;
                if ($this->db->field_exists('sumber_pengajuan_id', 'aset')) $aset['sumber_pengajuan_id'] = (int) $id_pengajuan;
                if ($this->db->field_exists('sumber_pengajuan_item_id', 'aset')) $aset['sumber_pengajuan_item_id'] = (int) $item->id_item;

                if (!$this->db->insert('aset', $aset)) {
                    return false;
                }
                $id_aset = (int) $this->db->insert_id();
                $created++;
            }

            $qr_update = [];
            if ($this->db->field_exists('qr_code', 'aset')) {
                $qr_update['qr_code'] = 'ASET-' . $id_aset . '-' . strtoupper(substr(md5($kode), 0, 6));
            }
            if ($this->db->field_exists('qr_url', 'aset')) {
                $qr_update['qr_url'] = site_url('peminjaman/detail_barang/' . $id_aset);
            }
            if (!empty($qr_update)) {
                $this->db->where('id_aset', $id_aset)->update('aset', $qr_update);
            }

            if (!$this->db->insert($this->inventoryLinkTable, [
                'id_bast' => $id_bast ? (int) $id_bast : null,
                'id_pengajuan' => (int) $id_pengajuan,
                'id_item' => (int) $item->id_item,
                'id_aset' => $id_aset,
            ])) {
                return false;
            }
        }

        return $created;
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
        $sql = $this->append_laporan_negosiasi_filters($sql, $params, $filters);

        $sort_map = [
            'kode_pengajuan' => 'p.kode_pengajuan',
            'item' => 'i.uraian_barang',
            'vendor' => 'n.vendor',
            'harga_awal' => 'n.harga_awal',
            'harga_akhir' => 'n.harga_negosiasi',
            'selisih' => '(n.harga_awal - n.harga_negosiasi)',
            'volume' => 'n.volume_negosiasi',
            'garansi' => 'n.garansi',
            'catatan' => 'n.catatan',
        ];
        $sort_key = (string) ($filters['sort_by'] ?? '');
        $sort_column = $sort_map[$sort_key] ?? 'n.created_at';
        $sort_dir = strtoupper((string) ($filters['sort_dir'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$sort_column} {$sort_dir}, n.id_negosiasi DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $offset . ", " . (int) $limit;
        }

        return $this->db->query($sql, $params)->result();
    }

    public function count_laporan_negosiasi_deal($filters = []) {
        $sql = "SELECT COUNT(*) AS total
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
        $sql = $this->append_laporan_negosiasi_filters($sql, $params, $filters);
        $row = $this->db->query($sql, $params)->row();

        return $row ? (int) $row->total : 0;
    }

    private function append_laporan_negosiasi_filters($sql, &$params, $filters) {
        if (!empty($filters['vendor'])) {
            $sql .= " AND n.vendor LIKE ?";
            $params[] = '%' . $filters['vendor'] . '%';
        }
        if (!empty($filters['jenis_pengajuan'])) {
            $sql .= " AND p.jenis_pengajuan = ?";
            $params[] = $filters['jenis_pengajuan'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (p.kode_pengajuan LIKE ? OR p.nama_pengajuan LIKE ? OR p.nama_prodi LIKE ? OR i.uraian_barang LIKE ? OR n.vendor LIKE ? OR n.garansi LIKE ? OR n.catatan LIKE ?)";
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
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

        $multi_fields = (array) ($filters['filter_field'] ?? []);
        $multi_values = (array) ($filters['filter_value'] ?? []);
        foreach (array_slice($multi_fields, 0, 4) as $index => $field) {
            $field = trim((string) $field);
            $value = trim((string) ($multi_values[$index] ?? ''));
            if ($value === '') {
                continue;
            }

            if ($field === 'pengajuan') {
                $sql .= " AND (p.kode_pengajuan LIKE ? OR p.nama_pengajuan LIKE ? OR p.nama_prodi LIKE ?)";
                $like = '%' . $value . '%';
                array_push($params, $like, $like, $like);
            } elseif ($field === 'item') {
                $sql .= " AND i.uraian_barang LIKE ?";
                $params[] = '%' . $value . '%';
            } elseif ($field === 'vendor') {
                $sql .= " AND n.vendor LIKE ?";
                $params[] = '%' . $value . '%';
            } elseif ($field === 'garansi') {
                $sql .= " AND n.garansi LIKE ?";
                $params[] = '%' . $value . '%';
            } elseif ($field === 'catatan') {
                $sql .= " AND n.catatan LIKE ?";
                $params[] = '%' . $value . '%';
            } elseif (in_array($field, ['harga_awal', 'harga_akhir', 'selisih', 'volume'], true)) {
                $numeric = $field === 'volume'
                    ? str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $value))
                    : preg_replace('/[^0-9]/', '', $value);
                if ($numeric === '') {
                    continue;
                }
                $expression = [
                    'harga_awal' => 'n.harga_awal',
                    'harga_akhir' => 'n.harga_negosiasi',
                    'selisih' => '(n.harga_awal - n.harga_negosiasi)',
                    'volume' => 'n.volume_negosiasi',
                ][$field];
                $sql .= " AND CAST({$expression} AS CHAR) LIKE ?";
                $params[] = '%' . $numeric . '%';
            }
        }

        return $sql;
    }

    public function get_dashboard_years() {
        $years = [];
        $sources = [
            ['table' => $this->kaprodiTable, 'column' => 'created_at'],
            ['table' => $this->anggaranTable, 'column' => 'tahun'],
            ['table' => $this->bastTable, 'column' => 'created_at'],
        ];

        foreach ($sources as $source) {
            if (!$this->db->table_exists($source['table'])) {
                continue;
            }

            if ($source['column'] === 'tahun') {
                $rows = $this->db
                    ->select('tahun')
                    ->where('tahun IS NOT NULL', null, false)
                    ->group_by('tahun')
                    ->get($source['table'])
                    ->result();
            } else {
                $rows = $this->db
                    ->select('YEAR(' . $source['column'] . ') AS tahun', false)
                    ->where($source['column'] . ' IS NOT NULL', null, false)
                    ->group_by('YEAR(' . $source['column'] . ')', false)
                    ->get($source['table'])
                    ->result();
            }

            foreach ($rows as $row) {
                $year = (int) ($row->tahun ?? 0);
                if ($year >= 2000) {
                    $years[] = $year;
                }
            }
        }

        $years[] = (int) date('Y');
        $years = array_values(array_unique($years));
        rsort($years, SORT_NUMERIC);
        return $years;
    }

    public function get_dashboard_stats($tahun = null) {
        $tahun = (int) ($tahun ?: date('Y'));
        $counts = [];

        if ($this->db->table_exists($this->kaprodiTable)) {
            $rows = $this->db
                ->select('status, COUNT(*) AS total', false)
                ->where('YEAR(created_at) = ' . $tahun, null, false)
                ->group_by('status')
                ->get($this->kaprodiTable)
                ->result();

            foreach ($rows as $row) {
                $counts[(string) $row->status] = (int) $row->total;
            }
        }

        $deal_statuses = ['Deal', 'Approval', 'Disetujui', 'BAST', 'Inventarisasi', 'Selesai'];
        $deal = 0;
        foreach ($deal_statuses as $status) {
            $deal += $counts[$status] ?? 0;
        }

        $bast = 0;
        if ($this->db->table_exists($this->bastTable)) {
            $bast = (int) $this->db
                ->where('YEAR(created_at) = ' . $tahun, null, false)
                ->count_all_results($this->bastTable);
        }

        return [
            'pengajuan' => array_sum($counts),
            'total_pengajuan' => array_sum($counts),
            'negosiasi' => $counts['Sedang Negosiasi'] ?? 0,
            'sedang_negosiasi' => $counts['Sedang Negosiasi'] ?? 0,
            'deal' => $deal,
            'deal_approval' => $deal,
            'menunggu_approval' => $counts['Pengajuan'] ?? 0,
            'bast' => $bast,
            'total_bast' => $bast,
            'laporan_deal' => count($this->get_laporan_negosiasi_deal([
                'tanggal_dari' => $tahun . '-01-01',
                'tanggal_sampai' => $tahun . '-12-31',
            ])),
        ];
    }

    public function get_dashboard_monthly_submissions($tahun = null) {
        $tahun = (int) ($tahun ?: date('Y'));
        $monthly = array_fill(1, 12, 0);

        if (!$this->db->table_exists($this->kaprodiTable)) {
            return array_values($monthly);
        }

        $rows = $this->db
            ->select('MONTH(created_at) AS bulan, COUNT(*) AS total', false)
            ->where('YEAR(created_at) = ' . $tahun, null, false)
            ->group_by('MONTH(created_at)', false)
            ->order_by('bulan', 'ASC')
            ->get($this->kaprodiTable)
            ->result();

        foreach ($rows as $row) {
            $month = (int) $row->bulan;
            if ($month >= 1 && $month <= 12) {
                $monthly[$month] = (int) $row->total;
            }
        }

        return array_values($monthly);
    }

    public function get_dashboard_status_breakdown($tahun = null) {
        $tahun = (int) ($tahun ?: date('Y'));
        $breakdown = [
            'Pengajuan' => 0,
            'Sedang Negosiasi' => 0,
            'Deal' => 0,
            'Ditolak' => 0,
            'Revisi' => 0,
        ];

        if (!$this->db->table_exists($this->kaprodiTable)) {
            return $breakdown;
        }

        $rows = $this->db
            ->select('status, COUNT(*) AS total', false)
            ->where('YEAR(created_at) = ' . $tahun, null, false)
            ->group_by('status')
            ->get($this->kaprodiTable)
            ->result();

        foreach ($rows as $row) {
            $status = (string) $row->status;
            $key = $status;
            if (in_array($status, ['Disetujui', 'Approval', 'BAST', 'Inventarisasi', 'Selesai'], true)) {
                $key = 'Deal';
            }
            if (isset($breakdown[$key])) {
                $breakdown[$key] += (int) $row->total;
            }
        }

        return $breakdown;
    }

    public function get_dashboard_negotiation_summary($tahun = null) {
        $tahun = (int) ($tahun ?: date('Y'));
        $summary = $this->get_total_deal_summary($tahun);
        return [
            'harga_awal' => (float) $summary['total_awal'],
            'harga_negosiasi' => (float) $summary['total_negosiasi'],
            'penghematan' => max(0, (float) $summary['total_awal'] - (float) $summary['total_negosiasi']),
        ];
    }

    public function get_dashboard_recent_activity($limit = 12) {
        $activities = [];

        if ($this->db->table_exists($this->kaprodiTable)) {
            $rows = $this->db
                ->select('id_pengajuan, nama_pengajuan, nama_prodi, status, updated_at, created_at')
                ->order_by('updated_at', 'DESC')
                ->limit(20)
                ->get($this->kaprodiTable)
                ->result();

            foreach ($rows as $row) {
                $status = (string) ($row->status ?? 'Pengajuan');
                $title = 'Pengajuan diperbarui';
                $icon = 'bi-inboxes';
                if ($status === 'Pengajuan') {
                    $title = 'Pengajuan baru diterima';
                } elseif ($status === 'Ditolak') {
                    $title = 'Pengajuan ditolak';
                    $icon = 'bi-x-circle';
                } elseif (in_array($status, ['Deal', 'Disetujui', 'Approval'], true)) {
                    $title = 'Approval pengadaan selesai';
                    $icon = 'bi-check2-circle';
                }
                $activities[] = [
                    'title' => $title,
                    'description' => ($row->nama_pengajuan ?? 'Pengajuan') . ' - ' . ($row->nama_prodi ?? 'Prodi'),
                    'time' => $row->updated_at ?: $row->created_at,
                    'status' => $status,
                    'icon' => $icon,
                ];
            }
        }

        if ($this->db->table_exists($this->negosiasiTable)) {
            $rows = $this->db
                ->select('n.status, n.vendor, n.created_at, p.nama_pengajuan, p.nama_prodi')
                ->from($this->negosiasiTable . ' n')
                ->join($this->kaprodiTable . ' p', 'p.id_pengajuan = n.id_pengajuan', 'left')
                ->order_by('n.created_at', 'DESC')
                ->limit(20)
                ->get()
                ->result();

            foreach ($rows as $row) {
                $status = (string) ($row->status ?? 'Sedang Negosiasi');
                $activities[] = [
                    'title' => $status === 'Deal' ? 'Negosiasi berhasil dilakukan' : ($status === 'Ditolak' ? 'Negosiasi ditolak' : 'Negosiasi diperbarui'),
                    'description' => ($row->nama_pengajuan ?? 'Pengadaan') . (!empty($row->vendor) ? ' - ' . $row->vendor : ''),
                    'time' => $row->created_at,
                    'status' => $status,
                    'icon' => $status === 'Deal' ? 'bi-hand-thumbs-up' : 'bi-chat-square-text',
                ];
            }
        }

        if ($this->db->table_exists($this->anggaranTable)) {
            $rows = $this->db
                ->select('tahun, total_anggaran, created_at')
                ->order_by('created_at', 'DESC')
                ->limit(12)
                ->get($this->anggaranTable)
                ->result();
            foreach ($rows as $row) {
                $activities[] = [
                    'title' => 'Alokasi anggaran berhasil dibuat',
                    'description' => 'Pagu tahun ' . (int) $row->tahun . ' - Rp ' . number_format((float) $row->total_anggaran, 0, ',', '.'),
                    'time' => $row->created_at,
                    'status' => 'Tersimpan',
                    'icon' => 'bi-cash-coin',
                ];
            }
        }

        if ($this->db->table_exists($this->bastTable)) {
            $rows = $this->db
                ->select('b.nomor_bast, b.created_at, p.nama_pengajuan')
                ->from($this->bastTable . ' b')
                ->join($this->kaprodiTable . ' p', 'p.id_pengajuan = b.id_pengajuan', 'left')
                ->order_by('b.created_at', 'DESC')
                ->limit(12)
                ->get()
                ->result();
            foreach ($rows as $row) {
                $activities[] = [
                    'title' => 'Dokumen BAST berhasil diinput',
                    'description' => ($row->nama_pengajuan ?? 'Pengadaan') . ' - ' . ($row->nomor_bast ?? '-'),
                    'time' => $row->created_at,
                    'status' => 'BAST',
                    'icon' => 'bi-file-earmark-pdf',
                ];
            }
        }

        if ($this->db->table_exists('notifikasi_progress')) {
            $rows = $this->db
                ->where('recipient_role', 'kaur')
                ->order_by('created_at', 'DESC')
                ->limit(20)
                ->get('notifikasi_progress')
                ->result();
            foreach ($rows as $row) {
                $activities[] = [
                    'title' => $row->judul,
                    'description' => $row->pesan,
                    'time' => $row->created_at,
                    'status' => ((int) $row->is_read === 1) ? 'Dibaca' : 'Baru',
                    'icon' => 'bi-bell',
                ];
            }
        }

        usort($activities, static function ($left, $right) {
            return strtotime((string) ($right['time'] ?? '')) <=> strtotime((string) ($left['time'] ?? ''));
        });

        return array_slice($activities, 0, max(1, (int) $limit));
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
        $subtotal_negosiasi = 0;

        foreach ($items as $item) {
            $vol = (float) ($item->vol ?? 0);
            $harga = (float) ($item->harga_penawaran_sat ?? 0);
            $subtotal_penawaran += $vol * $harga;

            // Item yang statusnya Ditolak dikeluarkan dari total negosiasi karena tidak jadi diadakan.
            $status = $item->latest_negosiasi->status ?? null;
            if ($status === 'Ditolak') {
                continue;
            }

            $nego_vol = isset($item->hasil_negosiasi_vol) && $item->hasil_negosiasi_vol !== null ? (float) $item->hasil_negosiasi_vol : $vol;
            $nego_harga = isset($item->hasil_negosiasi_sat) && $item->hasil_negosiasi_sat !== null ? (float) $item->hasil_negosiasi_sat : 0;
            $subtotal_negosiasi += $nego_vol * $nego_harga;
        }

        $pajak = $subtotal_penawaran * SCM_TAX_RATE;
        $total_setelah_pajak = $subtotal_penawaran + $pajak;
        $subtotal_markup = $subtotal_penawaran;
        $ppn_penawaran = $pajak;
        $ppn_negosiasi = $subtotal_negosiasi * SCM_TAX_RATE;
        $total_penawaran = $total_setelah_pajak;
        $total_negosiasi = $subtotal_negosiasi + $ppn_negosiasi;

        return [
            'subtotal_penawaran' => $subtotal_penawaran,
            'pajak_20' => $pajak,
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
