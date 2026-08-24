<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Approval extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'scm_ajax', 'scm_pagination']);
        $this->load->helper('loan_progress');
        $this->load->model('Peminjaman_model');
        $this->load->model('Aset_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            if (scm_is_ajax()) {
                scm_json_abort(['success' => false, 'message' => 'Sesi Anda berakhir. Silakan login kembali.', 'redirect' => site_url('auth')], 401);
            }
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            if (scm_is_ajax()) {
                scm_json_abort(['success' => false, 'message' => 'Anda tidak memiliki izin untuk memproses approval Laboran.'], 403);
            }
            $this->session->set_flashdata('error', 'Akses approval khusus Laboran.');
            redirect('admin/dashboard');
        }
    }

    public function index() {
        $query = trim((string) $this->input->server('QUERY_STRING'));
        redirect('admin/peminjaman' . ($query !== '' ? '?' . $query : ''));
    }

    private function read_filters() {
        $allowed = ['peminjam', 'barang', 'jumlah', 'masa', 'keperluan', 'status'];
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $rows = [];
        foreach ($fields as $index => $field) {
            $field = trim((string) $field);
            if (count($rows) >= 4 || !in_array($field, $allowed, true)) continue;
            $rows[] = ['field' => $field, 'value' => trim((string) ($values[$index] ?? ''))];
        }
        return $rows ?: [['field' => 'peminjam', 'value' => '']];
    }

    private function read_per_page() {
        $value = (int) $this->input->get('per_page');
        return in_array($value, [10, 25], true) ? $value : 10;
    }

    public function setujui($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect('admin/peminjaman');
        }

        if (!scm_loan_can_act($peminjaman, 'laboran')) {
            $this->session->set_flashdata('error', 'Pengajuan belum disetujui Kaprodi atau sudah diproses sebelumnya.');
            redirect('admin/peminjaman');
        }

        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $update = [
            'status' => 'Menunggu ACC Kaur',
            'status_laboran' => 'Disetujui',
            'catatan_laboran' => $this->input->post('catatan_laboran', true),
            'tgl_approve_laboran' => date('Y-m-d H:i:s'),
            'id_approver_laboran' => $this->session->userdata('id_user'),
            'status_kaur' => 'Pending',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $ok = $this->Peminjaman_model->approve_group_with_reservation(
            $group_id,
            ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan'],
            $update
        );
        if ($ok) {
            $this->Peminjaman_model->create_notifikasi(
                'kaur',
                null,
                'Pengajuan menunggu ACC Kaur',
                ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah dicek Laboran dan menunggu persetujuan Kaur.',
                site_url('kaur/dashboard/peminjaman')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan diteruskan ke Kaur. Stok tetap teralokasi untuk transaksi ini.' : 'Gagal meneruskan pengajuan atau reservasi stok tidak tersedia.');
        redirect('admin/peminjaman');
    }

    public function tolak($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data pengajuan tidak ditemukan.');
            redirect('admin/peminjaman');
        }

        if (!scm_loan_can_act($peminjaman, 'laboran')) {
            $this->session->set_flashdata('error', 'Pengajuan belum berada pada tahap verifikasi Laboran atau sudah diproses.');
            redirect('admin/peminjaman');
        }

        $catatan = trim((string) $this->input->post('catatan_laboran', true));
        if ($catatan === '') {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi.');
            redirect('admin/peminjaman');
        }

        $update = [
            'status' => 'Ditolak',
            'status_laboran' => 'Ditolak',
            'catatan_laboran' => $catatan,
            'tgl_approve_laboran' => date('Y-m-d H:i:s'),
            'id_approver_laboran' => $this->session->userdata('id_user'),
            'status_kaur' => 'Pending',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
        $ok = $this->Peminjaman_model->reject_group_and_release(
            $group_id,
            $update,
            ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan']
        );
        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Pengajuan ditolak Laboran',
                'Pengajuan peminjaman Anda ditolak pada tahap pengecekan Laboran. Catatan: ' . $catatan,
                site_url('peminjaman/riwayat')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengajuan berhasil ditolak dan reservasi stok dilepas.' : 'Gagal menolak pengajuan.');
        redirect('admin/peminjaman');
    }

    public function bulk() {
        $ajax = scm_is_ajax();
        if (strtoupper((string) $this->input->method()) !== 'POST') {
            if ($ajax) {
                scm_json_response(['success' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
                return;
            }
            redirect('admin/peminjaman');
        }

        $action = strtolower(trim((string) $this->input->post('action', true)));
        $ids = $this->bulk_ids();
        $catatan = trim((string) $this->input->post('bulk_note', true));
        if (!in_array($action, ['approve', 'reject'], true) || empty($ids)) {
            if ($ajax) {
                scm_json_response(['success' => false, 'message' => 'Pilih minimal satu pengajuan yang dapat diproses.'], 422);
                return;
            }
            $this->session->set_flashdata('error', 'Pilih minimal satu pengajuan yang dapat diproses.');
            redirect('admin/peminjaman');
        }
        if ($action === 'reject' && $catatan === '') {
            if ($ajax) {
                scm_json_response(['success' => false, 'message' => 'Alasan penolakan terpilih wajib diisi.'], 422);
                return;
            }
            $this->session->set_flashdata('error', 'Alasan penolakan terpilih wajib diisi.');
            redirect('admin/peminjaman');
        }

        $expected = ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan'];
        $processed = 0;
        $skipped = 0;
        $processed_ids = [];
        $skipped_ids = [];
        foreach ($ids as $id) {
            $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id);
            if (!$peminjaman || !scm_loan_can_act($peminjaman, 'laboran')) {
                $skipped++;
                $skipped_ids[] = $id;
                continue;
            }
            $group_id = $peminjaman->group_id ?: 'single-' . (int) $peminjaman->id_peminjaman;
            if ($action === 'approve') {
                $ok = $this->Peminjaman_model->approve_group_with_reservation($group_id, $expected, [
                    'status' => 'Menunggu ACC Kaur',
                    'status_laboran' => 'Disetujui',
                    'catatan_laboran' => '',
                    'tgl_approve_laboran' => date('Y-m-d H:i:s'),
                    'id_approver_laboran' => $this->session->userdata('id_user'),
                    'status_kaur' => 'Pending',
                ]);
                if ($ok) {
                    $this->Peminjaman_model->create_notifikasi('kaur', null, 'Pengajuan menunggu ACC Kaur',
                        ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah dicek Laboran dan menunggu persetujuan Kaur.',
                        site_url('kaur/dashboard/peminjaman'));
                }
            } else {
                $ok = $this->Peminjaman_model->reject_group_and_release($group_id, [
                    'status' => 'Ditolak',
                    'status_laboran' => 'Ditolak',
                    'catatan_laboran' => $catatan,
                    'tgl_approve_laboran' => date('Y-m-d H:i:s'),
                    'id_approver_laboran' => $this->session->userdata('id_user'),
                    'status_kaur' => 'Pending',
                ], $expected);
                if ($ok && !empty($peminjaman->id_user)) {
                    $this->Peminjaman_model->create_notifikasi(null, $peminjaman->id_user, 'Pengajuan ditolak Laboran',
                        'Pengajuan peminjaman Anda ditolak pada tahap pengecekan Laboran. Catatan: ' . $catatan,
                        site_url('peminjaman/riwayat'));
                }
            }
            if ($ok) {
                $processed++;
                $processed_ids[] = $id;
            } else {
                $skipped++;
                $skipped_ids[] = $id;
            }
        }

        $label = $action === 'approve' ? 'disetujui' : 'ditolak';
        $message = $processed . ' pengajuan berhasil ' . $label . '.';
        if ($skipped > 0) $message .= ' ' . $skipped . ' dilewati karena statusnya berubah atau bukan kewenangan Laboran.';
        if ($ajax) {
            scm_json_response([
                'success' => $processed > 0,
                'partial' => $processed > 0 && $skipped > 0,
                'message' => $message,
                'action' => $action,
                'status' => $action === 'approve' ? 'Menunggu ACC Kaur' : 'Ditolak',
                'processed' => $processed,
                'skipped' => $skipped,
                'processed_ids' => $processed_ids,
                'skipped_ids' => $skipped_ids,
                'actionable_remaining' => $this->Peminjaman_model->count_actionable_peminjaman('laboran'),
            ], $processed > 0 ? 200 : 409);
            return;
        }
        $this->session->set_flashdata($processed > 0 ? 'success' : 'error', $message);
        redirect('admin/peminjaman');
    }

    private function bulk_ids() {
        $ids = $this->input->post('loan_ids', true);
        if (!is_array($ids)) return [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));
        return array_slice($ids, 0, 25);
    }
}
