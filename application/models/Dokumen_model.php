<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dokumen_model extends CI_Model {
    private $table = 'dokumen_laboran';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->ensure_table();
    }

    private function ensure_table() {
        if ($this->db->table_exists($this->table)) {
            return;
        }

        $this->db->query("CREATE TABLE `dokumen_laboran` (
            `id_dokumen` int(11) NOT NULL AUTO_INCREMENT,
            `id_peminjaman` int(11) DEFAULT NULL,
            `judul` varchar(150) NOT NULL,
            `jenis` enum('SOP','Bukti','Berita Acara','Lainnya') NOT NULL DEFAULT 'Lainnya',
            `nama_file` varchar(255) NOT NULL,
            `original_name` varchar(255) DEFAULT NULL,
            `keterangan` text DEFAULT NULL,
            `uploaded_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id_dokumen`),
            KEY `idx_dokumen_peminjaman` (`id_peminjaman`),
            KEY `idx_dokumen_uploaded_by` (`uploaded_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    private function build_list_query(array $filters = [], $select = true) {
        if ($select) {
            $this->db->select('dokumen_laboran.*, peminjam.nama_peminjam, peminjam.nim_nip, peminjaman.tanggal_pinjam');
        }
        $this->db->from($this->table);
        $this->db->join('peminjaman', 'peminjaman.id_peminjaman = dokumen_laboran.id_peminjaman', 'left');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        foreach ((array) ($filters['criteria'] ?? []) as $criterion) {
            $field = (string) ($criterion['field'] ?? 'dokumen');
            $value = trim((string) ($criterion['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'jenis') { $this->db->like('dokumen_laboran.jenis', $value); continue; }
            if ($field === 'tanggal') { $this->db->where('DATE(dokumen_laboran.created_at)', $value); continue; }
            if ($field === 'relasi') {
                $this->db->group_start()->like('peminjam.nama_peminjam', $value)->or_like('peminjam.nim_nip', $value)->group_end();
                continue;
            }
            $this->db->group_start()->like('dokumen_laboran.judul', $value)->or_like('dokumen_laboran.original_name', $value)->or_like('dokumen_laboran.nama_file', $value)->or_like('dokumen_laboran.keterangan', $value)->group_end();
        }
    }

    public function get_all($limit = 100, $offset = 0, $filters = []) {
        $this->build_list_query((array) $filters);
        $this->db->order_by('dokumen_laboran.created_at', 'DESC');
        $this->db->order_by('dokumen_laboran.id_dokumen', 'DESC');
        $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));
        return $this->db->get()->result();
    }

    public function count_all($filters = []) {
        $this->build_list_query((array) $filters, false);
        return (int) $this->db->count_all_results();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id_dokumen' => $id])->row();
    }

    public function delete($id) {
        $this->db->where('id_dokumen', $id);
        return $this->db->delete($this->table);
    }
}
