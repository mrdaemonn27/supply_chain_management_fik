<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('kaprodi/Kaprodi_model');
        $this->guard_kaprodi();
    }

    private function guard_kaprodi() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (strtolower((string) $this->session->userdata('role')) !== 'kaprodi') {
            $this->session->set_flashdata('error', 'Akses ditolak. Panel ini khusus Kaprodi.');
            redirect('dashboard');
        }
    }

    public function index() {
        $data['title'] = 'Dashboard Kaprodi';
        $data['pengajuan'] = $this->Kaprodi_model->get_all_by_user($this->session->userdata('id_user'));
        $this->load->view('kaprodi/dashboard', $data);
    }
}