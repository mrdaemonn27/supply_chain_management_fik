<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('kaur/Kaur_model');
        $this->guard_kaur();
    }

    private function guard_kaur() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (strtolower((string) $this->session->userdata('role')) !== 'kaur') {
            $this->session->set_flashdata('error', 'Akses ditolak. Panel ini khusus Kaur Laboratorium.');
            redirect('dashboard');
        }
    }

    public function index() {
        $id_user = $this->session->userdata('id_user');
        $data['title'] = 'Dashboard Kaur Laboratorium';
        $data['pengajuan'] = $this->Kaur_model->get_all_by_user($id_user);
        $data['approval_bast'] = $this->Kaur_model->get_approval_bast_queue($id_user);
        $data['maintenance'] = $this->Kaur_model->get_laporan_maintenance(12);
        $data['laboratorium'] = $this->Kaur_model->get_laporan_laboratorium();
        $this->load->view('kaur/dashboard', $data);
    }
}