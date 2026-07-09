<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Approval extends CI_Controller {
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

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses approval khusus Laboran.');
            redirect('admin/dashboard');
        }
    }

    public function index() {
        $data['title'] = 'Approval Peminjaman';
        $data['pengajuan'] = $this->Peminjaman_model->search_peminjaman(['status' => 'Menunggu Persetujuan']);
        $this->load->view('admin/approval', $data);
    }

    public function setujui($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect('admin/approval');
        }

        if ($peminjaman->status !== 'Menunggu Persetujuan') {
            $this->session->set_flashdata('error', 'Pengajuan ini sudah diproses sebelumnya.');
            redirect('admin/approval');
        }

        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];
        foreach ($items as $item) {
            $aset = $this->Aset_model->get_aset_by_id($item->id_aset);
            if (!$aset || $item->jumlah_pinjam > $aset->jumlah_tersedia) {
                $this->session->set_flashdata('error', 'Stok ' . ($item->nama_aset ?? 'barang') . ' tidak mencukupi untuk disetujui.');
                redirect('admin/approval');
            }
        }

        $this->db->trans_start();
        foreach ($items as $item) {
            $this->Aset_model->update_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam);
            $this->Aset_model->increment_total_peminjaman($item->id_aset);
        }

        $update = [
            'status' => 'Dipinjam',
            'status_laboran' => 'Disetujui',
            'catatan_laboran' => $this->input->post('catatan_laboran', true),
            'tgl_approve_laboran' => date('Y-m-d H:i:s'),
            'id_approver_laboran' => $this->session->userdata('id_user'),
            'status_kaur' => 'Disetujui',
            'tgl_approve_kaur' => date('Y-m-d H:i:s'),
            'id_approver_kaur' => $this->session->userdata('id_user'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($peminjaman->group_id)) {
            $this->db->where('group_id', $peminjaman->group_id)->update('peminjaman', $update);
        } else {
            $this->db->where('id_peminjaman', $id_peminjaman)->update('peminjaman', $update);
        }
        $this->db->trans_complete();

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Pengajuan berhasil disetujui dan stok diperbarui.' : 'Gagal menyetujui pengajuan.');
        redirect('admin/approval');
    }

    public function tolak($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect('admin/approval');
        }

        $update = [
            'status' => 'Ditolak',
            'status_laboran' => 'Ditolak',
            'catatan_laboran' => $this->input->post('catatan_laboran', true),
            'tgl_approve_laboran' => date('Y-m-d H:i:s'),
            'id_approver_laboran' => $this->session->userdata('id_user'),
            'status_kaur' => 'Ditolak',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($peminjaman->group_id)) {
            $this->db->where('group_id', $peminjaman->group_id)->update('peminjaman', $update);
        } else {
            $this->db->where('id_peminjaman', $id_peminjaman)->update('peminjaman', $update);
        }

        $this->session->set_flashdata('success', 'Pengajuan berhasil ditolak.');
        redirect('admin/approval');
    }
}