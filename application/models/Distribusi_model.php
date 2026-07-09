<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Distribusi_model extends CI_Model {
    private $table = 'distribusi_barang';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_table();
    }

    private function ensure_table() {
        if ($this->db->table_exists($this->table)) {
            return;
        }

        $this->db->query("CREATE TABLE `distribusi_barang` (
            `id_distribusi` int(11) NOT NULL AUTO_INCREMENT,
            `id_aset` int(11) NOT NULL,
            `id_ruangan_asal` int(11) DEFAULT NULL,
            `id_ruangan_tujuan` int(11) NOT NULL,
            `jumlah` int(11) NOT NULL DEFAULT 1,
            `tanggal_distribusi` date NOT NULL,
            `keterangan` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id_distribusi`),
            KEY `idx_distribusi_aset` (`id_aset`),
            KEY `idx_distribusi_asal` (`id_ruangan_asal`),
            KEY `idx_distribusi_tujuan` (`id_ruangan_tujuan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    public function get_all($limit = 100) {
        $this->db->select('distribusi_barang.*, aset.nama_aset, aset.kode_aset, asal.nama_ruangan AS ruangan_asal, tujuan.nama_ruangan AS ruangan_tujuan, users.nama_lengkap AS nama_petugas');
        $this->db->from($this->table);
        $this->db->join('aset', 'aset.id_aset = distribusi_barang.id_aset', 'left');
        $this->db->join('ruangan asal', 'asal.id_ruangan = distribusi_barang.id_ruangan_asal', 'left');
        $this->db->join('ruangan tujuan', 'tujuan.id_ruangan = distribusi_barang.id_ruangan_tujuan', 'left');
        $this->db->join('users', 'users.id_user = distribusi_barang.created_by', 'left');
        $this->db->order_by('distribusi_barang.tanggal_distribusi', 'DESC');
        $this->db->order_by('distribusi_barang.id_distribusi', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }
}