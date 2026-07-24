<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengembalian extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Peminjaman_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('dashboard');
        }
    }

    public function index() {
        $status_options = ['', 'Sedang Dipinjam', 'Dipinjam', 'Terlambat'];
        $status = $this->input->get('status', true);
        if (!in_array($status, $status_options, true)) {
            $status = '';
        }

        $filters = [
            'status' => $status,
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];
        if ($status === '') {
            $filters['status_in'] = ['Sedang Dipinjam', 'Dipinjam'];
        }

        $page = max(1, (int) $this->input->get('page', true));
        $per_page = 10;
        $rows = $this->Peminjaman_model->search_peminjaman($filters);

        $data['title'] = 'Data Pengembalian';
        $data['filters'] = $filters;
        $data['status_options'] = $status_options;
        $data['peminjaman'] = array_slice($rows, ($page - 1) * $per_page, $per_page);
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => count($rows),
            'total_pages' => max(1, (int) ceil(count($rows) / $per_page)),
        ];
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        $this->load->view('admin/pengembalian', $data);
    }

    public function scanner() {
        $this->load->view('admin/scanner_qr', [
            'title' => 'Scanner Pengembalian',
            'scanner_label' => 'Scanner QR Transaksi',
            'scanner_desc' => 'Scan QR transaksi yang sama dari akun peminjam untuk validasi pengembalian.',
            'back_url' => site_url('admin/pengembalian'),
            'back_label' => 'Data Pengembalian',
        ]);
    }
}
