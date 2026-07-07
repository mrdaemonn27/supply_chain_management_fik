<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ruangan_model extends CI_Model
{
    private $table = 'ruangan';
    private $assetTable = 'aset';
    private $primaryKey = 'id_ruangan';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Ambil semua ruangan beserta jumlah aset
     */
    public function get_all_ruangan()
    {
        return $this->db
            ->select('ruangan.*, COUNT(aset.id_aset) as jumlah_aset')
            ->from($this->table)
            ->join($this->assetTable, 'aset.id_ruangan = ruangan.id_ruangan', 'left')
            ->group_by('ruangan.id_ruangan')
            ->order_by('ruangan.id_ruangan', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Ambil satu ruangan
     */
    public function get_ruangan_by_id($id)
    {
        return $this->db
            ->select('ruangan.*, COUNT(aset.id_aset) as jumlah_aset')
            ->from($this->table)
            ->join($this->assetTable, 'aset.id_ruangan = ruangan.id_ruangan', 'left')
            ->where('ruangan.id_ruangan', $id)
            ->group_by('ruangan.id_ruangan')
            ->get()
            ->row();
    }

    /**
     * Tambah ruangan
     */
    public function insert_ruangan($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update ruangan
     */
    public function update_ruangan($id, $data)
    {
        return $this->db
            ->where($this->primaryKey, $id)
            ->update($this->table, $data);
    }

    /**
     * Hapus ruangan
     */
    public function delete_ruangan($id)
    {
        if ($this->count_aset($id) > 0)
        {
            return false;
        }

        return $this->db
            ->where($this->primaryKey, $id)
            ->delete($this->table);
    }

    /**
     * Hitung aset pada ruangan
     */
    public function count_aset($id_ruangan)
    {
        return $this->db
            ->where('id_ruangan', $id_ruangan)
            ->count_all_results($this->assetTable);
    }

    /**
     * Total ruangan
     */
    public function count_ruangan()
    {
        return $this->db->count_all($this->table);
    }

}