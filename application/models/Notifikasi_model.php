<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifikasi_model extends CI_Model
{
    private $table = 'notifications';
    private $primaryKey = 'id_notifikasi';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Tambah satu notifikasi
     */
    public function add($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Tambah banyak notifikasi
     */
    public function add_bulk($notifications)
    {
        if (empty($notifications)) {
            return false;
        }

        return $this->db->insert_batch($this->table, $notifications);
    }

    /**
     * Ambil notifikasi berdasarkan ID
     */
    public function get_by_id($id)
    {
        return $this->db
            ->where($this->primaryKey, $id)
            ->get($this->table)
            ->row();
    }

    /**
     * Ambil semua notifikasi user
     */
    public function get_by_user($id_user, $limit = 20)
    {
        return $this->db
            ->where('id_user', $id_user)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result();
    }

    /**
     * Hitung notifikasi belum dibaca
     */
    public function get_unread_count($id_user)
    {
        return $this->db
            ->where('id_user', $id_user)
            ->where('is_read', 0)
            ->count_all_results($this->table);
    }

    /**
     * Tandai satu notifikasi telah dibaca
     */
    public function mark_as_read($id_notifikasi, $id_user)
    {
        return $this->db
            ->where($this->primaryKey, $id_notifikasi)
            ->where('id_user', $id_user)
            ->update($this->table, [
                'is_read' => 1
            ]);
    }

    /**
     * Tandai semua notifikasi user telah dibaca
     */
    public function mark_all_as_read($id_user)
    {
        return $this->db
            ->where('id_user', $id_user)
            ->update($this->table, [
                'is_read' => 1
            ]);
    }

    /**
     * Hapus notifikasi
     */
    public function delete($id_notifikasi)
    {
        return $this->db
            ->where($this->primaryKey, $id_notifikasi)
            ->delete($this->table);
    }

    /**
     * Hitung total notifikasi
     */
    public function count()
    {
        return $this->db->count_all($this->table);
    }
}