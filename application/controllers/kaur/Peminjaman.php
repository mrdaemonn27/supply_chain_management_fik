<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Peminjaman extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('loan_progress');
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

        if (!scm_loan_can_act($peminjaman, 'kaur')) {
            $this->session->set_flashdata('error', 'Pengajuan belum menyelesaikan tahap Kaprodi dan Laboran.');
            redirect('kaur/dashboard/peminjaman');
        }

        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $update = [
            'status' => 'Disetujui (Menunggu Finalisasi QR)',
            'status_kaur' => 'Disetujui',
            'catatan_kaur' => $this->input->post('catatan_kaur', true),
            'tgl_approve_kaur' => date('Y-m-d H:i:s'),
            'id_approver_kaur' => $this->session->userdata('id_user'),
            'qr_locked' => 0,
            'qr_finalized_at' => null,
            'qr_finalized_by' => null,
        ];

        $ok = $this->Peminjaman_model->approve_group_with_reservation($group_id, ['Menunggu ACC Kaur'], $update);
        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Peminjaman disetujui Kaur',
                'Peminjaman sudah di-ACC Kaur dan sedang difinalkan Laboran sebelum QR transaksi ditampilkan.',
                site_url('peminjaman/riwayat')
            );
        }
        if ($ok) {
            $this->Peminjaman_model->create_notifikasi(
                'laboran',
                null,
                'Finalisasi QR transaksi',
                ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah disetujui Kaur. Cek data lalu finalkan QR transaksi sebelum serah terima.',
                site_url('admin/peminjaman')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan disetujui. Data menunggu finalisasi QR oleh Laboran.' : 'Gagal menyetujui pengajuan.');
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
        $filename = 'laporan_pengajuan_sampai_acc_' . date('Ymd_His') . '.xlsx';

        if ($this->input->get('download') !== '1' && $this->input->get('inline') !== '1') {
            $query = $this->input->get();
            $this->load->view('shared/export_preview', [
                'title' => 'Preview Laporan Pengajuan Sampai ACC',
                'download_url' => current_url() . '?' . http_build_query(array_merge($query, ['download' => 1])),
                'iframe_url' => current_url() . '?' . http_build_query(array_merge($query, ['inline' => 1])),
                'back_url' => site_url('kaur/dashboard/peminjaman'),
            ]);
            return;
        }

        if ($this->input->get('download') === '1') {
            $this->load->helper('scm_xlsx');
            scm_download_xlsx($filename, $this->load->view('admin/export_pengajuan_acc', $data, true));
            return;
        }
        $this->load->view('admin/export_pengajuan_acc', $data);
    }

    public function tolak($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('kaur/dashboard/peminjaman');
        }

        if (!scm_loan_can_act($peminjaman, 'kaur')) {
            $this->session->set_flashdata('error', 'Pengajuan belum berada di tahap ACC Kaur.');
            redirect('kaur/dashboard/peminjaman');
        }

        $catatan = trim((string) $this->input->post('catatan_kaur', true));
        if ($catatan === '') {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi.');
            redirect('kaur/dashboard/peminjaman');
        }

        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $ok = $this->Peminjaman_model->reject_group_and_release($group_id, [
            'status' => 'Ditolak',
            'status_kaur' => 'Ditolak',
            'catatan_kaur' => $catatan,
            'tgl_approve_kaur' => date('Y-m-d H:i:s'),
            'id_approver_kaur' => $this->session->userdata('id_user'),
        ], ['Menunggu ACC Kaur']);

        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Peminjaman ditolak Kaur',
                'Pengajuan peminjaman Anda ditolak pada tahap ACC Kaur. Catatan: ' . $catatan,
                site_url('peminjaman/riwayat')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan berhasil ditolak.' : 'Gagal menolak pengajuan.');
        redirect('kaur/dashboard/peminjaman');
    }

    public function bulk() {
        if (strtoupper((string) $this->input->method()) !== 'POST') redirect('kaur/dashboard/peminjaman');

        $action = strtolower(trim((string) $this->input->post('action', true)));
        $ids = $this->bulk_ids();
        $catatan = trim((string) $this->input->post('bulk_note', true));
        if (!in_array($action, ['approve', 'reject'], true) || empty($ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu pengajuan yang dapat diproses.');
            redirect('kaur/dashboard/peminjaman');
        }
        if ($action === 'reject' && $catatan === '') {
            $this->session->set_flashdata('error', 'Alasan penolakan terpilih wajib diisi.');
            redirect('kaur/dashboard/peminjaman');
        }

        $processed = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id);
            if (!$peminjaman || !scm_loan_can_act($peminjaman, 'kaur')) {
                $skipped++;
                continue;
            }
            $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
            if ($action === 'approve') {
                $ok = $this->Peminjaman_model->approve_group_with_reservation($group_id, ['Menunggu ACC Kaur'], [
                    'status' => 'Disetujui (Menunggu Finalisasi QR)',
                    'status_kaur' => 'Disetujui',
                    'catatan_kaur' => '',
                    'tgl_approve_kaur' => date('Y-m-d H:i:s'),
                    'id_approver_kaur' => $this->session->userdata('id_user'),
                    'qr_locked' => 0,
                    'qr_finalized_at' => null,
                    'qr_finalized_by' => null,
                ]);
                if ($ok && !empty($peminjaman->id_user)) {
                    $this->Peminjaman_model->create_notifikasi(null, $peminjaman->id_user, 'Peminjaman disetujui Kaur',
                        'Peminjaman sudah di-ACC Kaur dan sedang difinalkan Laboran sebelum QR transaksi ditampilkan.',
                        site_url('peminjaman/riwayat'));
                }
                if ($ok) {
                    $this->Peminjaman_model->create_notifikasi('laboran', null, 'Finalisasi QR transaksi',
                        ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah disetujui Kaur. Cek data lalu finalkan QR transaksi sebelum serah terima.',
                        site_url('admin/peminjaman'));
                }
            } else {
                $ok = $this->Peminjaman_model->reject_group_and_release($group_id, [
                    'status' => 'Ditolak',
                    'status_kaur' => 'Ditolak',
                    'catatan_kaur' => $catatan,
                    'tgl_approve_kaur' => date('Y-m-d H:i:s'),
                    'id_approver_kaur' => $this->session->userdata('id_user'),
                ], ['Menunggu ACC Kaur']);
                if ($ok && !empty($peminjaman->id_user)) {
                    $this->Peminjaman_model->create_notifikasi(null, $peminjaman->id_user, 'Peminjaman ditolak Kaur',
                        'Pengajuan peminjaman Anda ditolak pada tahap ACC Kaur. Catatan: ' . $catatan,
                        site_url('peminjaman/riwayat'));
                }
            }
            if ($ok) $processed++; else $skipped++;
        }

        $label = $action === 'approve' ? 'disetujui' : 'ditolak';
        $message = $processed . ' pengajuan berhasil ' . $label . '.';
        if ($skipped > 0) $message .= ' ' . $skipped . ' dilewati karena statusnya berubah atau bukan kewenangan Kaur.';
        $this->session->set_flashdata($processed > 0 ? 'success' : 'error', $message);
        redirect('kaur/dashboard/peminjaman');
    }

    private function bulk_ids() {
        $ids = $this->input->post('loan_ids', true);
        if (!is_array($ids)) return [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));
        return array_slice($ids, 0, 100);
    }
}
