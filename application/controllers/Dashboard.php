<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 */
#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Proteksi: Jika belum login, tendang kembali ke halaman login
        if(!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Akses ditolak! Silakan login terlebih dahulu.');
            redirect('auth');
        }
    }

    public function index() {
        // PERINTAH INI YANG MENGUBAH TAMPILAN:
        // Memanggil file UI dari folder views/dashboard/index.php
        $this->load->view('dashboard/index');
    }
}