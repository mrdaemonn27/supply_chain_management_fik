<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Memastikan library session dan url di-load
        $this->load->library('session');
        $this->load->helper('url');
        
        // --- BLOK KEAMANAN (LOGIN) ---
        // Jika fitur login Anda sudah berjalan sempurna, hapus tanda komentar (/* dan */) di bawah ini
        // agar halaman admin ini tidak bisa diakses oleh orang yang belum login.
        /*
        if (!$this->session->userdata('nim_nip') || !in_array($this->session->userdata('role'), ['admin', 'laboran', 'kaur'])) {
            redirect('auth/login');
        }
        */
    }

    public function index() {
        // Menyiapkan data untuk dikirim ke View
        $data['title'] = 'Dashboard Administrator - SCM FIK';
        
        // Mengambil role user dari session (jika kosong, default ke 'Admin')
        $data['user_role'] = $this->session->userdata('role') ? ucfirst($this->session->userdata('role')) : 'Admin';
        
        // Memanggil View 'admin/dashboard'
        // Halaman inilah yang nantinya akan berisi kotak-kotak pilihan menu CRUD
        $this->load->view('admin/dashboard', $data);
    }
}