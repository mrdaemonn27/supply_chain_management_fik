<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kaur_model extends CI_Model {
    private $table = 'kaur_pengajuan';
    private $itemTable = 'kaur_pengajuan_item';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_tables();
    }

    private function ensure_tables() {
        if (!$this->db->table_exists($this->table)) {
            $this->db->query("CREATE TABLE `kaur_pengajuan` (
                `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
                `kode_pengajuan` varchar(40) NOT NULL,
                `id_user` int(11) NOT NULL,
                `nama_lab` varchar(150) NOT NULL,
                `nama_pengajuan` varchar(200) NOT NULL,
                `kebutuhan_lab` text DEFAULT NULL,
                `anak_perusahaan` varchar(150) DEFAULT NULL,
                `status` enum('Pengajuan','Approval Tahap 1 (BAST)','BAST Disetujui','Negosiasi','ACC Anak Perusahaan','Alokasi','Selesai') NOT NULL DEFAULT 'Pengajuan',
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
            $vol = (float) $item->vol;
            $harga = (float) $item->harga_penawaran_sat;
            $nego_vol = $item->hasil_negosiasi_vol !== null ? (float) $item->hasil_negosiasi_vol : $vol;
            $nego_harga = $item->hasil_negosiasi_sat !== null ? (float) $item->hasil_negosiasi_sat : 0;

            $subtotal_penawaran += $vol * $harga;
            $subtotal_markup += $vol * ($harga * 1.2);
            $subtotal_negosiasi += $nego_vol * $nego_harga;
        }

        $ppn_penawaran = $subtotal_markup * 0.11;
        $ppn_negosiasi = $subtotal_negosiasi * 0.11;
        $total_penawaran = $subtotal_markup + $ppn_penawaran;
        $total_negosiasi = $subtotal_negosiasi + $ppn_negosiasi;

        return [
            'subtotal_penawaran' => $subtotal_penawaran,
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