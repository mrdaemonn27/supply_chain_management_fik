<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property Ruangan_model $Ruangan_model
 */
#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // PROTEKSI DIHAPUS / DIKOMENTARI AGAR GUEST BISA MASUK
        // if(!$this->session->userdata('logged_in')) {
        //     $this->session->set_flashdata('error', 'Akses ditolak! Silakan login terlebih dahulu.');
        //     redirect('auth');
        // }

        // Memuat Model Ruangan agar bisa mengambil data
        $this->load->model('admin/Ruangan_model');
    }

    public function index() {
        // Mengambil semua data ruangan dari database
        $data['ruangan_list'] = $this->Ruangan_model->get_all();

        // PERINTAH INI YANG MENGUBAH TAMPILAN:
        // Memanggil file UI dari folder views/dashboard/index.php beserta datanya
        $this->load->view('dashboard/index', $data);
    }
}