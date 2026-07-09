<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Distribusi extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Distribusi_model');
        $this->load->model('Aset_model');
        $this->load->model('admin/Ruangan_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses distribusi khusus Laboran.');
            redirect('admin/dashboard');
        }
    }

    public function index() {
        $data['title'] = 'Distribusi Barang';
        $data['distribusi'] = $this->Distribusi_model->get_all();
        $data['aset'] = $this->Aset_model->get_all_aset_ordered('nama_aset', 'ASC');
        $data['ruangan'] = $this->Ruangan_model->get_all();
        $this->load->view('admin/distribusi', $data);
    }

    public function simpan() {
        $id_aset = $this->input->post('id_aset', true);
        $id_ruangan_tujuan = $this->input->post('id_ruangan_tujuan', true);
        $jumlah = max(1, (int) $this->input->post('jumlah', true));

        $aset = $this->Aset_model->get_aset_by_id($id_aset);
        if (!$aset || !$id_ruangan_tujuan) {
            $this->session->set_flashdata('error', 'Aset dan ruangan tujuan wajib dipilih.');
            redirect('admin/distribusi');
        }

        if ((int) $aset->id_ruangan === (int) $id_ruangan_tujuan) {
            $this->session->set_flashdata('error', 'Ruangan tujuan sama dengan ruangan saat ini.');
            redirect('admin/distribusi');
        }

        $this->db->trans_start();
        $this->Distribusi_model->insert([
            'id_aset' => $id_aset,
            'id_ruangan_asal' => $aset->id_ruangan,
            'id_ruangan_tujuan' => $id_ruangan_tujuan,
            'jumlah' => $jumlah,
            'tanggal_distribusi' => $this->input->post('tanggal_distribusi', true) ?: date('Y-m-d'),
            'keterangan' => $this->input->post('keterangan', true),
            'created_by' => $this->session->userdata('id_user'),
        ]);
        $this->db->where('id_aset', $id_aset)->update('aset', ['id_ruangan' => $id_ruangan_tujuan]);
        $this->db->trans_complete();

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Distribusi barang berhasil dicatat dan lokasi aset diperbarui.' : 'Gagal menyimpan distribusi barang.');
        redirect('admin/distribusi');
    }
}