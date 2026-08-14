<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Maintenance extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Maintenance_model');
        $this->load->model('Aset_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses maintenance khusus Laboran.');
            redirect('admin/dashboard');
        }
    }

    public function index() {
        $criteria = $this->read_filter_criteria(['aset', 'ruangan', 'tanggal', 'kondisi', 'deskripsi']);
        $page = max(1, (int) $this->input->get('page', true));
        $requested_per_page = strtolower(trim((string) $this->input->get('per_page', true)));
        if ($requested_per_page === 'all') {
            $per_page = 'all';
            $limit = null;
            $page = 1;
        } else {
            $requested_limit = (int) $requested_per_page;
            $limit = in_array($requested_limit, [10, 25, 50], true) ? $requested_limit : 10;
            $per_page = (string) $limit;
        }

        $total_rows = $this->Maintenance_model->count($criteria);
        $total_pages = $limit === null ? 1 : max(1, (int) ceil($total_rows / $limit));
        $page = min($page, $total_pages);
        $offset = $limit === null ? 0 : (($page - 1) * $limit);

        $data['title'] = 'Maintenance Barang';
        $data['maintenance'] = $this->Maintenance_model->get_all_maintenance($limit, $offset, $criteria);
        $data['filter_criteria'] = $criteria;
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_rows,
            'total_pages' => $total_pages,
        ];
        $data['aset'] = $this->Aset_model->get_all_aset_ordered('nama_aset', 'ASC');
        $this->load->view('admin/maintenance', $data);
    }

    private function read_filter_criteria($allowed) {
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $criteria = [];
        foreach (array_slice($fields, 0, 4) as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));
            if (in_array($field, $allowed, true) && $value !== '') $criteria[] = ['field' => $field, 'value' => $value];
        }
        return $criteria;
    }

    public function simpan() {
        $kondisi_setelah = $this->input->post('kondisi_setelah', true);
        $id_aset = $this->input->post('id_aset', true);

        if (!$id_aset || !$this->input->post('tanggal_maintenance', true) || !$this->input->post('deskripsi', true)) {
            $this->session->set_flashdata('error', 'Aset, tanggal, dan deskripsi maintenance wajib diisi.');
            redirect('admin/maintenance');
        }

        $this->Maintenance_model->insert_maintenance([
            'id_aset' => $id_aset,
            'tanggal_maintenance' => $this->input->post('tanggal_maintenance', true),
            'deskripsi' => $this->input->post('deskripsi', true),
            'kondisi_setelah' => $kondisi_setelah,
            'catatan' => $this->input->post('catatan', true),
            'created_by' => $this->session->userdata('id_user'),
        ]);

        $map_kondisi = [
            'Baik' => 'Baik',
            'Sudah Diperbaiki' => 'Baik',
            'Perlu Perbaikan' => 'Rusak Ringan',
            'Rusak' => 'Rusak Berat',
        ];
        if (isset($map_kondisi[$kondisi_setelah])) {
            $this->Aset_model->update_kondisi($id_aset, $map_kondisi[$kondisi_setelah]);
        }

        $this->session->set_flashdata('success', 'Catatan maintenance berhasil disimpan.');
        redirect('admin/maintenance');
    }

    public function hapus($id) {
        $this->Maintenance_model->delete_maintenance($id);
        $this->session->set_flashdata('success', 'Catatan maintenance berhasil dihapus.');
        redirect('admin/maintenance');
    }
}
