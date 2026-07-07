<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kategori_model extends CI_Model {

    private $table = 'kategori';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Ambil semua kategori (tanpa join aset untuk performa)
     */
    public function get_all_kategori() {
        $this->db->select('
            kategori.*, 
            COUNT(aset.id_aset) as jumlah_aset
        ');
        $this->db->from($this->table);
        $this->db->join('aset', 'aset.id_kategori = kategori.id_kategori', 'left');
        $this->db->group_by('kategori.id_kategori');
        $this->db->order_by('kategori.id_kategori', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * TAMBAHKAN METHOD INI - Untuk dashboard yang membutuhkan data dengan count
     */
    public function get_all_kategori_with_count() {
        return $this->get_all_kategori(); // Reuse method yang sudah ada
    }

    /**
     * Ambil kategori berdasarkan ID
     */
    public function get_kategori_by_id($id) {
        $this->db->select('
            kategori.*, 
            COUNT(aset.id_aset) as jumlah_aset
        ');
        $this->db->from($this->table);
        $this->db->join('aset', 'aset.id_kategori = kategori.id_kategori', 'left');
        $this->db->where('kategori.id_kategori', $id);
        $this->db->group_by('kategori.id_kategori');
        return $this->db->get()->row();
    }

    /**
     * Insert kategori baru
     */
    public function insert_kategori($data) {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update kategori
     */
    public function update_kategori($id, $data) {
        $this->db->where('id_kategori', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hapus kategori
     */
    public function delete_kategori($id) {
        // Cek apakah kategori masih memiliki aset
        $this->db->where('id_kategori', $id);
        $aset_count = $this->db->count_all_results('aset');
        
        if ($aset_count > 0) {
            return false;
        }
        
        $this->db->where('id_kategori', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Get total kategori
     */
    public function get_total_kategori() {
        return $this->db->count_all($this->table);
    }
}