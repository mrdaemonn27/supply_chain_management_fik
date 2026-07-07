<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model: User_model
 * Mengelola data pengguna (CRUD, Login, Role)
 */
class User_model extends CI_Model {

    private $table = 'users';
    private $primaryKey = 'id_user';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /* ==========================================================
     * AUTHENTICATION
     * ==========================================================
     */

    /**
     * Login berdasarkan username
     */
    public function login($username)
    {
        return $this->db
                    ->where('username', $username)
                    ->get($this->table)
                    ->row();
    }

    /**
     * Ambil user berdasarkan username
     */
    public function get_user_by_username($username)
    {
        return $this->db
                    ->where('username', $username)
                    ->get($this->table)
                    ->row();
    }

    /**
     * Ambil user berdasarkan ID
     */
    public function get_user_by_id($id_user)
    {
        return $this->db
                    ->where($this->primaryKey, $id_user)
                    ->get($this->table)
                    ->row();
    }

    /* ==========================================================
     * CREATE
     * ==========================================================
     */

    /**
     * Tambah user baru
     */
    public function insert_user($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /* ==========================================================
     * READ
     * ==========================================================
     */

    /**
     * Semua user
     */
    public function get_all_users()
    {
        return $this->db
                    ->order_by('nama', 'ASC')
                    ->get($this->table)
                    ->result();
    }

    /**
     * User berdasarkan role
     */
    public function get_users_by_role($role)
    {
        return $this->db
                    ->where('role', $role)
                    ->order_by('nama', 'ASC')
                    ->get($this->table)
                    ->result();
    }

    /**
     * Cari user
     */
    public function search_user($keyword)
    {
        $this->db->group_start();
        $this->db->like('nama', $keyword);
        $this->db->or_like('username', $keyword);
        $this->db->group_end();

        return $this->db
                    ->order_by('nama', 'ASC')
                    ->get($this->table)
                    ->result();
    }

    /**
     * Total user
     */
    public function count_users()
    {
        return $this->db->count_all($this->table);
    }

    /* ==========================================================
     * UPDATE
     * ==========================================================
     */

    /**
     * Update data user
     */
    public function update_user($id_user, $data)
    {
        return $this->db
                    ->where($this->primaryKey, $id_user)
                    ->update($this->table, $data);
    }

    /* ==========================================================
     * DELETE
     * ==========================================================
     */

    /**
     * Hapus user
     * (Bisa diganti soft delete jika nanti ada field status)
     */
    public function delete_user($id_user)
    {
        return $this->db
                    ->where($this->primaryKey, $id_user)
                    ->delete($this->table);
    }

    /* ==========================================================
     * VALIDATION
     * ==========================================================
     */

    /**
     * Cek username sudah digunakan atau belum
     */
    public function is_username_exists($username, $exclude_id = null)
    {
        $this->db->where('username', $username);

        if ($exclude_id != null)
        {
            $this->db->where($this->primaryKey . ' !=', $exclude_id);
        }

        return $this->db->count_all_results($this->table) > 0;
    }

}