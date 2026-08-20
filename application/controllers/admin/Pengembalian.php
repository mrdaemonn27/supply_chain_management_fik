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

    private function read_multi_filters() {
        $allowed_fields = ['peminjam', 'barang', 'status', 'tanggal', 'keperluan'];
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $multi_filters = [];
        $filter_rows = [];

        foreach ($fields as $index => $field) {
            if (count($filter_rows) >= 4) {
                break;
            }
            $field = trim((string) $field);
            $value = trim((string) ($values[$index] ?? ''));
            if (!in_array($field, $allowed_fields, true)) {
                continue;
            }

            $filter_rows[] = ['field' => $field, 'value' => $value];
            if ($value !== '') {
                $multi_filters[] = ['field' => $field, 'value' => $value];
            }
        }

        return [$multi_filters, $filter_rows];
    }

    private function build_filter_suggestions($rows) {
        $suggestions = ['peminjam' => [], 'barang' => [], 'status' => [], 'tanggal' => [], 'keperluan' => []];
        foreach ((array) $rows as $row) {
            foreach (['nama_peminjam', 'nim_nip'] as $property) {
                if (!empty($row->{$property})) $suggestions['peminjam'][] = (string) $row->{$property};
            }
            if (!empty($row->status)) $suggestions['status'][] = (string) $row->status;
            if (!empty($row->tanggal_pinjam)) $suggestions['tanggal'][] = date('Y-m-d', strtotime($row->tanggal_pinjam));
            if (!empty($row->keperluan)) $suggestions['keperluan'][] = (string) $row->keperluan;
            foreach ((array) ($row->detail_barang ?? []) as $detail) {
                if (!empty($detail->nama_aset)) $suggestions['barang'][] = (string) $detail->nama_aset;
                if (!empty($detail->kode_aset)) $suggestions['barang'][] = (string) $detail->kode_aset;
            }
        }
        foreach ($suggestions as $key => $values) {
            $values = array_values(array_unique(array_filter($values)));
            natcasesort($values);
            $suggestions[$key] = array_values($values);
        }
        return $suggestions;
    }

    public function index() {
        $status_options = ['', 'Sedang Dipinjam', 'Dipinjam', 'Terlambat'];
        $status = $this->input->get('status', true);
        if (!in_array($status, $status_options, true)) {
            $status = '';
        }

        list($multi_filters, $filter_rows) = $this->read_multi_filters();
        $filters = [
            'status' => $status,
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
            'multi_filters' => $multi_filters,
        ];
        if ($status === '') {
            $filters['status_in'] = ['Sedang Dipinjam', 'Dipinjam'];
        }

        $page = max(1, (int) $this->input->get('page', true));
        $per_page_value = (string) $this->input->get('per_page', true);
        if (!in_array($per_page_value, ['10', '25', '50', '100'], true)) {
            $per_page_value = '10';
        }
        $rows = $this->Peminjaman_model->search_peminjaman($filters);
        $per_page = (int) $per_page_value;

        $data['title'] = 'Data Pengembalian';
        $data['filters'] = $filters;
        $data['filter_rows'] = $filter_rows;
        $data['filter_suggestions'] = $this->build_filter_suggestions($rows);
        $data['status_options'] = $status_options;
        $data['peminjaman'] = array_slice($rows, ($page - 1) * $per_page, $per_page);
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page_value,
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
