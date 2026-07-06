<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    // Fungsi untuk mengambil satu baris data user berdasarkan username
    public function get_user_by_username($username) {
        $this->db->where('username', $username);
        
        // MENGGUNAKAN TABEL 'users' (sesuai dengan database Anda)
        $result = $this->db->get('users'); 
        
        return $result->row(); 
    }
    
}