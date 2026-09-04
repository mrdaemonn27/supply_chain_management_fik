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
        $requested_limit = (int) $requested_per_page;
        $limit = in_array($requested_limit, [10, 25, 50, 100], true) ? $requested_limit : 10;
        $per_page = (string) $limit;

        $total_rows = $this->Maintenance_model->count($criteria);
        $total_pages = max(1, (int) ceil($total_rows / $limit));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $limit;

        $data['title'] = 'Maintenance Barang';
        $data['maintenance'] = $this->Maintenance_model->get_all_maintenance($limit, $offset, $criteria);
        $data['filter_criteria'] = $criteria;
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_rows,
            'total_pages' => $total_pages,
        ];
        $this->load->view('admin/maintenance', $data);
    }

    public function cari_aset() {
        $keyword = trim((string) $this->input->get('q', true));
        $rows = $this->Aset_model->search_for_maintenance($keyword, 20);
        $results = array_map(static function ($asset) {
            return [
                'id' => (int) $asset->id_aset,
                'name' => (string) $asset->nama_aset,
                'code' => (string) $asset->kode_aset,
                'room' => trim((string) ($asset->nama_ruangan ?? '')) ?: 'Belum ditempatkan',
                'condition' => trim((string) ($asset->kondisi ?? '')) ?: '-',
                'total' => (int) ($asset->jumlah_total ?? 0),
            ];
        }, $rows);

        return $this->json_response(['success' => true, 'results' => $results]);
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
        $id_aset = (int) $this->input->post('id_aset', true);
        $tanggal_maintenance = trim((string) $this->input->post('tanggal_maintenance', true));
        $deskripsi = trim((string) $this->input->post('deskripsi', true));

        if ($id_aset < 1 || !$this->is_valid_date($tanggal_maintenance) || $deskripsi === '') {
            $this->session->set_flashdata('error', 'Aset, tanggal, dan deskripsi maintenance wajib diisi.');
            redirect('admin/maintenance');
        }

        if ($this->db->where('id_aset', $id_aset)->count_all_results('aset') < 1) {
            $this->session->set_flashdata('error', 'Aset yang dipilih tidak ditemukan.');
            redirect('admin/maintenance');
        }

        $this->Maintenance_model->insert_maintenance([
            'id_aset' => $id_aset,
            'tanggal_maintenance' => $tanggal_maintenance,
            'deskripsi' => $deskripsi,
            'kondisi_setelah' => $kondisi_setelah,
            'catatan' => $this->input->post('catatan', true),
            'created_by' => $this->session->userdata('id_user'),
            'created_at' => date('Y-m-d H:i:s'),
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

    private function is_valid_date($value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function json_response($payload, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
