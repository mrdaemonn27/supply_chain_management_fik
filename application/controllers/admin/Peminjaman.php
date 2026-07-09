<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Peminjaman extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Peminjaman_model');
        $this->load->model('Aset_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran', 'kaur'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('dashboard');
        }
    }

    public function index() {
        $filters = [
            'status' => $this->input->get('status', true),
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];

        $data['title'] = 'Data Peminjaman';
        $data['filters'] = $filters;
        $data['peminjaman'] = $this->Peminjaman_model->search_peminjaman($filters);
        $this->load->view('admin/peminjaman', $data);
    }

    public function kembalikan($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('admin/peminjaman');
        }

        if ($peminjaman->status !== 'Dipinjam') {
            $this->session->set_flashdata('error', 'Hanya peminjaman berstatus Dipinjam yang bisa dikembalikan.');
            redirect('admin/peminjaman');
        }

        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];

        $this->db->trans_start();
        foreach ($items as $item) {
            if (!empty($item->id_aset) && !empty($item->jumlah_pinjam)) {
                $this->Aset_model->kembalikan_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam);
            }
        }

        $update = [
            'status' => 'Dikembalikan',
            'tanggal_kembali_actual' => date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($peminjaman->group_id)) {
            $this->db->where('group_id', $peminjaman->group_id)->update('peminjaman', $update);
        } else {
            $this->db->where('id_peminjaman', $id_peminjaman)->update('peminjaman', $update);
        }
        $this->db->trans_complete();

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Barang berhasil ditandai kembali.' : 'Gagal memproses pengembalian.');
        redirect('admin/peminjaman');
    }
}