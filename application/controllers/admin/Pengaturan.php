<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengaturan extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Peminjaman_model');

        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }
        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak. Pengaturan ini khusus Laboran.');
            redirect('dashboard');
        }
    }

    public function index() {
        $data['title'] = 'Pengaturan Peminjaman';
        $data['settings'] = $this->Peminjaman_model->get_loan_settings();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        $this->load->view('admin/pengaturan_peminjaman', $data);
    }

    public function simpan() {
        $days = (int) $this->input->post('kaprodi_approval_days', true);
        if ($days < 1 || $days > 30) {
            $this->session->set_flashdata('error', 'Batas persetujuan harus antara 1 sampai 30 hari.');
            redirect('admin/pengaturan');
        }

        $ok = $this->Peminjaman_model->update_kaprodi_approval_days(
            $days,
            $this->session->userdata('id_user')
        );
        $this->session->set_flashdata(
            $ok ? 'success' : 'error',
            $ok
                ? 'Batas persetujuan Kaprodi berhasil diubah menjadi ' . $days . ' hari. Pengajuan existing tetap menggunakan tenggat sebelumnya.'
                : 'Pengaturan gagal disimpan.'
        );
        redirect('admin/pengaturan');
    }
}
