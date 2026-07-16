<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Peminjaman extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Peminjaman_model');
        $this->guard_kaur();
    }

    private function guard_kaur() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (strtolower((string) $this->session->userdata('role')) !== 'kaur') {
            $this->session->set_flashdata('error', 'Akses ditolak. Approval ini khusus Kaur Laboratorium.');
            redirect('dashboard');
        }
    }

    public function setujui($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('kaur/dashboard/peminjaman');
        }

        if ($peminjaman->status !== 'Menunggu ACC Kaur') {
            $this->session->set_flashdata('error', 'Pengajuan belum berada di tahap ACC Kaur.');
            redirect('kaur/dashboard/peminjaman');
        }

        $update = [
            'status' => 'Disetujui (Menunggu Pengambilan)',
            'status_kaur' => 'Disetujui',
            'catatan_kaur' => $this->input->post('catatan_kaur', true),
            'tgl_approve_kaur' => date('Y-m-d H:i:s'),
            'id_approver_kaur' => $this->session->userdata('id_user'),
        ];

        $ok = $this->Peminjaman_model->update_group_status($peminjaman->group_id, $update);
        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Peminjaman disetujui Kaur',
                'QR Code sudah tersedia. Silakan tunjukkan QR kepada Laboran saat pengambilan barang.',
                site_url('peminjaman/riwayat')
            );
        }
        if ($ok) {
            $this->Peminjaman_model->create_notifikasi(
                'laboran',
                null,
                'QR peminjaman aktif',
                ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah disetujui Kaur. Silakan scan QR saat serah terima barang.',
                site_url('admin/peminjaman/scanner')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan disetujui. QR peminjam sudah aktif.' : 'Gagal menyetujui pengajuan.');
        redirect('kaur/dashboard/peminjaman');
    }

    public function export_pengajuan_acc() {
        $filters = [
            'status' => $this->input->get('status', true),
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];

        $data['title'] = 'Laporan Pengajuan Sampai Tahap ACC';
        $data['rows'] = $this->Peminjaman_model->get_pengajuan_sampai_acc_report($filters);
        $filename = 'laporan_pengajuan_sampai_acc_' . date('Ymd_His') . '.xls';

        $this->output
            ->set_content_type('application/vnd.ms-excel')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: max-age=0');
        $this->load->view('admin/export_pengajuan_acc', $data);
    }

    public function tolak($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('kaur/dashboard/peminjaman');
        }

        $ok = $this->Peminjaman_model->update_group_status($peminjaman->group_id, [
            'status' => 'Ditolak',
            'status_kaur' => 'Ditolak',
            'catatan_kaur' => $this->input->post('catatan_kaur', true),
            'tgl_approve_kaur' => date('Y-m-d H:i:s'),
            'id_approver_kaur' => $this->session->userdata('id_user'),
        ]);

        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Peminjaman ditolak Kaur',
                'Pengajuan peminjaman Anda ditolak pada tahap ACC Kaur.',
                site_url('peminjaman/riwayat')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan berhasil ditolak.' : 'Gagal menolak pengajuan.');
        redirect('kaur/dashboard/peminjaman');
    }
}
