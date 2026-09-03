<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Distribusi_model extends CI_Model {
    private $table = 'distribusi_barang';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_table();
    }

    /**
     * Skema lama tetap dipakai sebagai ledger perpindahan aset. Kolom tambahan
     * bersifat aditif agar riwayat distribusi yang sudah ada tidak hilang.
     */
    private function ensure_table() {
        if (!$this->db->table_exists($this->table)) {
            $this->db->query("CREATE TABLE `distribusi_barang` (
                `id_distribusi` int(11) NOT NULL AUTO_INCREMENT,
                `id_aset` int(11) NOT NULL,
                `id_ruangan_asal` int(11) DEFAULT NULL,
                `id_ruangan_tujuan` int(11) NOT NULL,
                `jumlah` int(11) NOT NULL DEFAULT 1,
                `kondisi_aset` varchar(50) DEFAULT NULL,
                `tanggal_distribusi` date NOT NULL,
                `waktu_distribusi` datetime DEFAULT NULL,
                `keterangan` text DEFAULT NULL,
                `penerima` varchar(150) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
                PRIMARY KEY (`id_distribusi`),
                KEY `idx_distribusi_aset` (`id_aset`),
                KEY `idx_distribusi_asal` (`id_ruangan_asal`),
                KEY `idx_distribusi_tujuan` (`id_ruangan_tujuan`),
                KEY `idx_distribusi_waktu` (`waktu_distribusi`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            return;
        }

        $columns = [
            'kondisi_aset' => 'ALTER TABLE `distribusi_barang` ADD COLUMN `kondisi_aset` varchar(50) DEFAULT NULL AFTER `jumlah`',
            'waktu_distribusi' => 'ALTER TABLE `distribusi_barang` ADD COLUMN `waktu_distribusi` datetime DEFAULT NULL AFTER `tanggal_distribusi`',
            'penerima' => 'ALTER TABLE `distribusi_barang` ADD COLUMN `penerima` varchar(150) DEFAULT NULL AFTER `keterangan`',
            'updated_at' => 'ALTER TABLE `distribusi_barang` ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`',
        ];

        foreach ($columns as $column => $query) {
            if (!$this->db->field_exists($column, $this->table)) {
                $this->db->query($query);
            }
        }

        if (!$this->index_exists('idx_distribusi_waktu')) {
            $this->db->query('ALTER TABLE `distribusi_barang` ADD KEY `idx_distribusi_waktu` (`waktu_distribusi`)');
        }

        // Riwayat lama tidak mempunyai jam. Waktu pembuatan menjadi fallback yang akurat.
        $this->db->query('UPDATE `distribusi_barang` SET `waktu_distribusi` = `created_at` WHERE `waktu_distribusi` IS NULL');
    }

    private function index_exists($index) {
        return $this->db
            ->query('SHOW INDEX FROM `' . $this->table . '` WHERE Key_name = ?', [$index])
            ->num_rows() > 0;
    }

    private function build_list_query($filters = [], $select = '*') {
        $this->db->select($select);
        $this->db->from($this->table);
        $this->db->join('aset', 'aset.id_aset = distribusi_barang.id_aset', 'left');
        $this->db->join('ruangan asal', 'asal.id_ruangan = distribusi_barang.id_ruangan_asal', 'left');
        $this->db->join('ruangan tujuan', 'tujuan.id_ruangan = distribusi_barang.id_ruangan_tujuan', 'left');
        $this->db->join('ruangan lokasi_terakhir', 'lokasi_terakhir.id_ruangan = aset.id_ruangan', 'left');
        $this->db->join('users', 'users.id_user = distribusi_barang.created_by', 'left');

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') continue;

            if ($field === 'aset') {
                $this->db->group_start()
                    ->like('aset.nama_aset', $value)
                    ->or_like('aset.kode_aset', $value)
                    ->group_end();
            } elseif ($field === 'asal') {
                $this->db->like('asal.nama_ruangan', $value);
            } elseif ($field === 'tujuan') {
                $this->db->like('tujuan.nama_ruangan', $value);
            } elseif ($field === 'lokasi_terakhir') {
                $this->db->like('lokasi_terakhir.nama_ruangan', $value);
            } elseif ($field === 'jumlah' && ctype_digit($value)) {
                $this->db->where('distribusi_barang.jumlah', (int) $value);
            } elseif ($field === 'tanggal') {
                $this->apply_date_filter($value);
            } elseif ($field === 'petugas') {
                $this->db->like('users.nama_lengkap', $value);
            }
        }
    }

    private function apply_date_filter($value) {
        $parts = array_map('trim', explode('..', $value, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $this->db->where('distribusi_barang.tanggal_distribusi >=', $parts[0]);
            $this->db->where('distribusi_barang.tanggal_distribusi <=', $parts[1]);
            return;
        }

        $this->db->where('distribusi_barang.tanggal_distribusi', $value);
    }

    public function get_all($limit = 10, $offset = 0, $filters = []) {
        $this->build_list_query($filters, 'distribusi_barang.*, aset.nama_aset, aset.kode_aset, aset.kondisi AS kondisi_terkini, asal.nama_ruangan AS ruangan_asal, tujuan.nama_ruangan AS ruangan_tujuan, lokasi_terakhir.nama_ruangan AS lokasi_terakhir, users.nama_lengkap AS nama_petugas');
        $this->db->order_by('COALESCE(distribusi_barang.waktu_distribusi, distribusi_barang.created_at)', 'DESC', false);
        $this->db->order_by('distribusi_barang.id_distribusi', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }

    public function count_all($filters = []) {
        $this->build_list_query($filters, 'distribusi_barang.id_distribusi');
        return (int) $this->db->count_all_results();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function get_asset_tracking($id_aset) {
        return $this->db
            ->select('aset.id_aset, aset.nama_aset, aset.kode_aset, aset.kondisi, aset.jumlah_total, aset.jumlah_tersedia, ruangan.nama_ruangan AS lokasi_terakhir')
            ->from('aset')
            ->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left')
            ->where('aset.id_aset', (int) $id_aset)
            ->get()
            ->row();
    }

    public function get_tracking_history($id_aset) {
        return $this->db
            ->select('distribusi_barang.*, asal.nama_ruangan AS ruangan_asal, tujuan.nama_ruangan AS ruangan_tujuan, users.nama_lengkap AS nama_petugas')
            ->from($this->table)
            ->join('ruangan asal', 'asal.id_ruangan = distribusi_barang.id_ruangan_asal', 'left')
            ->join('ruangan tujuan', 'tujuan.id_ruangan = distribusi_barang.id_ruangan_tujuan', 'left')
            ->join('users', 'users.id_user = distribusi_barang.created_by', 'left')
            ->where('distribusi_barang.id_aset', (int) $id_aset)
            ->order_by('COALESCE(distribusi_barang.waktu_distribusi, distribusi_barang.created_at)', 'ASC', false)
            ->order_by('distribusi_barang.id_distribusi', 'ASC')
            ->get()
            ->result();
    }
}
