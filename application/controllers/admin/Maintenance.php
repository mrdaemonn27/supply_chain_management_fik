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
        $data['title'] = 'Maintenance Barang';
        $data['maintenance'] = $this->Maintenance_model->get_all_maintenance();
        $data['aset'] = $this->Aset_model->get_all_aset_ordered('nama_aset', 'ASC');
        $this->load->view('admin/maintenance', $data);
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