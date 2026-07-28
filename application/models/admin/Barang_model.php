<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model: Barang_model
 * Path: application/models/Barang_model.php
 * Mengelola interaksi database untuk fitur Master Data Aset (Admin)
 */
class Barang_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Load database secara otomatis saat model dipanggil
        $this->load->database();
    }

    // ======================================================
    // READ (Tampilkan Data)
    // ======================================================

    /**
     * Tampilkan semua barang beserta nama laboratorium/ruangannya
     */
    public function get_all($filters = []) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from('aset');
        // Join dengan tabel ruangan untuk mendapatkan nama lab yang sesuai
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        if (!empty($filters['q'])) {
            $this->db->group_start();
            $this->db->like('aset.kode_aset', trim($filters['q']));
            $this->db->or_like('aset.nama_aset', trim($filters['q']));
            $this->db->or_like('ruangan.nama_ruangan', trim($filters['q']));
            $this->db->or_like('aset.kondisi', trim($filters['q']));
            $this->db->group_end();
        }
        $this->db->order_by('aset.nama_aset', 'ASC');
        if (!empty($filters['limit'])) {
            $this->db->limit((int) $filters['limit'], max(0, (int) ($filters['offset'] ?? 0)));
        }
        
        return $this->db->get()->result();
    }

    public function count_all($filters = []) {
        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $this->db->group_start();
            $this->db->like('aset.kode_aset', $q);
            $this->db->or_like('aset.nama_aset', $q);
            $this->db->or_like('ruangan.nama_ruangan', $q);
            $this->db->or_like('aset.kondisi', $q);
            $this->db->group_end();
        }
        $this->db->from('aset');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        return (int) $this->db->count_all_results();
    }

    public function find_duplicate($data, $exclude_id = null) {
        $kode = trim((string) ($data['kode_aset'] ?? ''));
        $nama = trim((string) ($data['nama_aset'] ?? ''));
        $id_ruangan = (int) ($data['id_ruangan'] ?? 0);
        if ($kode === '' && $nama === '') {
            return null;
        }
        $this->db->from('aset');
        $this->db->group_start();
        if ($kode !== '') {
            $this->db->where('kode_aset', $kode);
        }
        if ($nama !== '') {
            if ($kode !== '') {
                $this->db->or_group_start();
            }
            $this->db->where('nama_aset', $nama);
            if ($id_ruangan > 0) {
                $this->db->where('id_ruangan', $id_ruangan);
            }
            if ($kode !== '') {
                $this->db->group_end();
            }
        }
        $this->db->group_end();
        if ($exclude_id) {
            $this->db->where('id_aset !=', (int) $exclude_id);
        }
        return $this->db->get()->row();
    }

    /**
     * Ambil daftar semua ruangan untuk pilihan di Dropdown form Tambah/Edit
     */
    public function get_all_ruangan() {
        $this->db->order_by('nama_ruangan', 'ASC');
        return $this->db->get('ruangan')->result();
    }

    /**
     * Ambil detail 1 barang spesifik berdasarkan ID (digunakan untuk fitur Edit)
     */
    public function get_by_id($id_aset) {
        return $this->db->get_where('aset', ['id_aset' => $id_aset])->row();
    }

    // ======================================================
    // CREATE (Tambah Data)
    // ======================================================

    /**
     * Simpan data barang baru ke tabel aset
     */
    public function insert($data) {
        return $this->db->insert('aset', $data);
    }

    // ======================================================
    // UPDATE (Ubah Data)
    // ======================================================

    /**
     * Simpan pembaruan data barang berdasarkan ID
     */
    public function update($id_aset, $data) {
        $this->db->where('id_aset', $id_aset);
        return $this->db->update('aset', $data);
    }

    // ======================================================
    // DELETE (Hapus Data)
    // ======================================================

    /**
     * Hapus barang secara permanen dari database
     */
    public function delete($id_aset) {
        $this->db->where('id_aset', $id_aset);
        return $this->db->delete('aset');
    }
}
