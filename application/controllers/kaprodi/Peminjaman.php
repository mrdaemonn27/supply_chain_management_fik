<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Peminjaman extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Peminjaman_model');
        $this->guard_kaprodi();
    }

    private function guard_kaprodi() {
        if (!$this->session->userdata('logged_in')) {
            redirect('auth');
        }
        if (strtolower((string) $this->session->userdata('role')) !== 'kaprodi') {
            $this->session->set_flashdata('error', 'Akses approval peminjaman khusus Kaprodi.');
            redirect('dashboard');
        }
    }

    public function index() {
        // Kedua tabel difilter langsung di browser agar hasil, sorting, total,
        // dan pagination berubah seketika tanpa menyembunyikan filter server-side.
        $filters = ['q' => ''];
        $data = [
            'title' => 'Approval Peminjaman - Kaprodi',
            'filters' => $filters,
            'pengajuan' => $this->Peminjaman_model->get_pending_kaprodi($filters),
            'pengembalian' => $this->Peminjaman_model->get_pengembalian_readonly($filters),
            'notifikasi' => $this->Peminjaman_model->get_notifikasi('kaprodi', null),
            'unread_notifikasi' => $this->Peminjaman_model->count_notifikasi_unread('kaprodi', null),
        ];
        $this->load->view('kaprodi/peminjaman', $data);
    }

    public function setujui($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman || ($peminjaman->status ?? '') !== 'Menunggu ACC Kaprodi'
            || ($peminjaman->status_kaprodi ?? 'Pending') !== 'Pending') {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            redirect('kaprodi/peminjaman');
        }

        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $ok = $this->Peminjaman_model->update_group_status($group_id, [
            'status' => 'Menunggu Verifikasi Laboran',
            'status_kaprodi' => 'Disetujui',
            'catatan_kaprodi' => trim((string) $this->input->post('catatan_kaprodi', true)),
            'tgl_approve_kaprodi' => date('Y-m-d H:i:s'),
            'id_approver_kaprodi' => $this->session->userdata('id_user'),
        ]);
        if ($ok) {
            $this->Peminjaman_model->create_notifikasi('laboran', null, 'Peminjaman disetujui Kaprodi',
                ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah di-ACC Kaprodi dan menunggu pengecekan Laboran.',
                site_url('admin/approval'));
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan diteruskan ke Laboran.' : 'Gagal memproses pengajuan.');
        redirect('kaprodi/peminjaman');
    }

    public function tolak($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman || ($peminjaman->status ?? '') !== 'Menunggu ACC Kaprodi'
            || ($peminjaman->status_kaprodi ?? 'Pending') !== 'Pending') {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
            redirect('kaprodi/peminjaman');
        }

        $catatan = trim((string) $this->input->post('catatan_kaprodi', true));
        if ($catatan === '') {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi.');
            redirect('kaprodi/peminjaman');
        }
        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $ok = $this->Peminjaman_model->update_group_status($group_id, [
            'status' => 'Ditolak',
            'status_kaprodi' => 'Ditolak',
            'catatan_kaprodi' => $catatan,
            'tgl_approve_kaprodi' => date('Y-m-d H:i:s'),
            'id_approver_kaprodi' => $this->session->userdata('id_user'),
        ]);
        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(null, $peminjaman->id_user, 'Peminjaman ditolak Kaprodi',
                'Pengajuan peminjaman Anda ditolak Kaprodi. Catatan: ' . $catatan,
                site_url('peminjaman/riwayat'));
        }
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan ditolak.' : 'Gagal memproses pengajuan.');
        redirect('kaprodi/peminjaman');
    }
}
