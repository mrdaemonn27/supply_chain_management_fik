<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('admin/Dashboard_model');
        $this->load->model('Peminjaman_model');

        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak. Panel ini khusus Laboran.');
            redirect('dashboard');
        }
    }

    public function index() {
        $role = strtolower((string) $this->session->userdata('role'));

        $data['title'] = 'Dashboard Laboran - SCM FIK';
        $data['user_role'] = ($role === 'admin') ? 'Laboran' : ($role ? ucfirst($role) : 'Laboran');
        $data['stats'] = $this->Dashboard_model->get_statistik();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);

        $this->load->view('admin/dashboard', $data);
    }
}
