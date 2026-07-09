<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dokumen_internal_model extends CI_Model {
    private $table = 'dokumen_internal';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_table();
    }

    private function ensure_table() {
        if ($this->db->table_exists($this->table)) {
            return;
        }

        $this->db->query("CREATE TABLE `dokumen_internal` (
            `id_dokumen` int(11) NOT NULL AUTO_INCREMENT,
            `judul` varchar(180) NOT NULL,
            `kategori` enum('SOP','Instruksi Kerja','Tata Tertib','Lainnya') NOT NULL DEFAULT 'SOP',
            `deskripsi` text DEFAULT NULL,
            `nama_file` varchar(255) NOT NULL,
            `original_name` varchar(255) DEFAULT NULL,
            `mime_type` varchar(120) DEFAULT NULL,
            `file_size` int(11) DEFAULT 0,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `uploaded_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id_dokumen`),
            KEY `idx_internal_kategori` (`kategori`),
            KEY `idx_internal_active` (`is_active`),
            KEY `idx_internal_uploaded_by` (`uploaded_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    public function get_all($active_only = false) {
        $this->db->select('dokumen_internal.*, users.nama_lengkap as uploader');
        $this->db->from($this->table);
        $this->db->join('users', 'users.id_user = dokumen_internal.uploaded_by', 'left');
        if ($active_only) {
            $this->db->where('dokumen_internal.is_active', 1);
        }
        $this->db->order_by('dokumen_internal.kategori', 'ASC');
        $this->db->order_by('dokumen_internal.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_active() {
        return $this->get_all(true);
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id_dokumen' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        $this->db->where('id_dokumen', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id) {
        $this->db->where('id_dokumen', $id);
        return $this->db->delete($this->table);
    }

    public function exists_file($nama_file) {
        return $this->db->where('nama_file', $nama_file)->count_all_results($this->table) > 0;
    }
}
