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
    public function get_all() {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from('aset');
        // Join dengan tabel ruangan untuk mendapatkan nama lab yang sesuai
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->order_by('aset.nama_aset', 'ASC');
        
        return $this->db->get()->result();
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