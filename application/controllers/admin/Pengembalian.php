<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengembalian extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'scm_ajax', 'scm_sort']);
        $this->load->model('Peminjaman_model');
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
                scm_json_abort(['success' => false, 'message' => 'Anda tidak memiliki izin untuk memproses pengembalian.'], 403);
            }
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('dashboard');
        }
    }

    private function read_multi_filters() {
        $allowed_fields = ['peminjam', 'barang', 'status', 'tanggal', 'keperluan'];
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $multi_filters = [];
        $filter_rows = [];

        foreach ($fields as $index => $field) {
            if (count($filter_rows) >= 4) {
                break;
            }
            $field = trim((string) $field);
            $value = trim((string) ($values[$index] ?? ''));
            if (!in_array($field, $allowed_fields, true)) {
                continue;
            }

            $filter_rows[] = ['field' => $field, 'value' => $value];
            if ($value !== '') {
                $multi_filters[] = ['field' => $field, 'value' => $value];
            }
        }

        return [$multi_filters, $filter_rows];
    }

    private function build_filter_suggestions($rows) {
        $suggestions = ['peminjam' => [], 'barang' => [], 'status' => [], 'tanggal' => [], 'keperluan' => []];
        foreach ((array) $rows as $row) {
            foreach (['nama_peminjam', 'nim_nip'] as $property) {
                if (!empty($row->{$property})) $suggestions['peminjam'][] = (string) $row->{$property};
            }
            if (!empty($row->status)) $suggestions['status'][] = (string) $row->status;
            if (!empty($row->tanggal_pinjam)) $suggestions['tanggal'][] = date('Y-m-d', strtotime($row->tanggal_pinjam));
            if (!empty($row->keperluan)) $suggestions['keperluan'][] = (string) $row->keperluan;
            foreach ((array) ($row->detail_barang ?? []) as $detail) {
                if (!empty($detail->nama_aset)) $suggestions['barang'][] = (string) $detail->nama_aset;
                if (!empty($detail->kode_aset)) $suggestions['barang'][] = (string) $detail->kode_aset;
            }
        }
        foreach ($suggestions as $key => $values) {
            $values = array_values(array_unique(array_filter($values)));
            natcasesort($values);
            $suggestions[$key] = array_values($values);
        }
        return $suggestions;
    }

    public function index() {
        $status_options = ['', 'Sedang Dipinjam', 'Dipinjam', 'Terlambat'];
        $status = $this->input->get('status', true);
        if (!in_array($status, $status_options, true)) {
            $status = '';
        }

        list($multi_filters, $filter_rows) = $this->read_multi_filters();
        $filters = [
            'status' => $status,
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
            'multi_filters' => $multi_filters,
        ];
        $allowed_sort = ['number', 'peminjam', 'barang', 'masa', 'status'];
        $filters['sort_by'] = in_array($this->input->get('sort_by', true), $allowed_sort, true) ? $this->input->get('sort_by', true) : '';
        $filters['sort_dir'] = strtolower((string) $this->input->get('sort_dir', true)) === 'asc' ? 'asc' : 'desc';
        if ($status === '') {
            $filters['status_in'] = ['Sedang Dipinjam', 'Dipinjam'];
        }

        $page = max(1, (int) $this->input->get('page', true));
        $per_page_value = (string) $this->input->get('per_page', true);
        if (!in_array($per_page_value, ['10', '25'], true)) {
            $per_page_value = '10';
        }
        $per_page = (int) $per_page_value;
        $total_rows = $this->Peminjaman_model->count_visible_peminjaman($filters);
        $total_pages = max(1, (int) ceil($total_rows / $per_page));
        $page = min($page, $total_pages);
        $rows = $this->Peminjaman_model->search_peminjaman($filters, $per_page, ($page - 1) * $per_page);

        $data['title'] = 'Data Pengembalian';
        $data['filters'] = $filters;
        $data['filter_rows'] = $filter_rows;
        $data['filter_suggestions'] = $this->build_filter_suggestions($rows);
        $data['status_options'] = $status_options;
        $data['peminjaman'] = $rows;
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page_value,
            'total' => $total_rows,
            'total_pages' => $total_pages,
        ];
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        $this->load->view('admin/pengembalian', $data);
    }

    public function scanner() {
        $this->load->view('admin/scanner_qr', [
            'title' => 'Scanner Pengembalian',
            'scanner_label' => 'Scanner QR Transaksi',
            'scanner_desc' => 'Scan QR transaksi yang sama dari akun peminjam untuk validasi pengembalian.',
            'back_url' => site_url('admin/pengembalian'),
            'back_label' => 'Data Pengembalian',
        ]);
    }

    public function bulk() {
        $ajax = scm_is_ajax();
        if (strtoupper((string) $this->input->method()) !== 'POST') {
            if ($ajax) {
                scm_json_response(['success' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
                return;
            }
            redirect('admin/pengembalian');
        }

        $action = strtolower(trim((string) $this->input->post('action', true)));
        $ids = $this->bulk_ids();
        if ($action !== 'return_good' || empty($ids)) {
            if ($ajax) {
                scm_json_response(['success' => false, 'message' => 'Pilih minimal satu pengembalian yang dapat diproses.'], 422);
                return;
            }
            $this->session->set_flashdata('error', 'Pilih minimal satu pengembalian yang dapat diproses.');
            redirect('admin/pengembalian');
        }

        $processed = 0;
        $skipped = 0;
        $processed_ids = [];
        $skipped_ids = [];
        foreach ($ids as $id) {
            $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id);
            if (!$peminjaman || !in_array((string) ($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true)) {
                $skipped++;
                $skipped_ids[] = $id;
                continue;
            }

            $group_id = $peminjaman->group_id ?: 'single-' . $id;
            $this->db->trans_begin();
            $locked = $this->Peminjaman_model->get_peminjaman_by_group_id_for_update($group_id);
            $ok = $locked && in_array((string) ($locked->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true);
            $items = $ok && !empty($locked->detail_barang) ? $locked->detail_barang : ($ok ? [$locked] : []);

            foreach ($items as $item) {
                if (empty($item->id_aset)) continue;
                if (!$this->Peminjaman_model->return_stock_allocation($item->id_peminjaman, $item->jumlah_pinjam, true)) {
                    $ok = false;
                    break;
                }
                $ok = $this->db->where('id_aset', $item->id_aset)->update('aset', [
                    'kondisi' => 'Baik',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if (!$ok) break;
            }

            if ($ok) {
                $update = [
                    'status' => 'Dikembalikan',
                    'tanggal_kembali_actual' => date('Y-m-d'),
                    'kondisi_saat_kembali' => 'Baik',
                    'catatan' => '',
                    'qr_locked' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $ok = !empty($locked->group_id)
                    ? $this->db->where('group_id', $locked->group_id)->update('peminjaman', $update)
                    : $this->db->where('id_peminjaman', $id)->update('peminjaman', $update);
            }

            if (!$ok || $this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $skipped++;
                $skipped_ids[] = $id;
                continue;
            }

            $this->db->trans_commit();
            $processed++;
            $processed_ids[] = $id;
            if (!empty($locked->id_user)) {
                $this->Peminjaman_model->create_notifikasi(null, $locked->id_user, 'Barang sudah dikembalikan',
                    'Pengembalian peminjaman Anda sudah dikonfirmasi oleh Laboran.', site_url('peminjaman/riwayat'));
            }
            $this->Peminjaman_model->create_notifikasi('kaur', null, 'Barang sudah dikembalikan',
                ($locked->nama_peminjam ?? 'Peminjam') . ' sudah mengembalikan barang ke Laboran.', site_url('kaur/dashboard/peminjaman'));
            $this->Peminjaman_model->create_notifikasi('kaprodi', null, 'Barang sudah dikembalikan',
                ($locked->nama_peminjam ?? 'Peminjam') . ' sudah mengembalikan barang dan telah dikonfirmasi Laboran.', site_url('kaprodi/peminjaman'));
        }

        $message = $processed . ' pengembalian berhasil ditandai kembali dengan kondisi Baik.';
        if ($skipped > 0) $message .= ' ' . $skipped . ' dilewati karena status atau alokasi stok sudah berubah.';
        if ($ajax) {
            scm_json_response([
                'success' => $processed > 0,
                'partial' => $processed > 0 && $skipped > 0,
                'message' => $message,
                'action' => $action,
                'status' => 'Dikembalikan',
                'processed' => $processed,
                'skipped' => $skipped,
                'processed_ids' => $processed_ids,
                'skipped_ids' => $skipped_ids,
                'actionable_remaining' => $this->Peminjaman_model->count_visible_peminjaman(['status_in' => ['Sedang Dipinjam', 'Dipinjam']]),
            ], $processed > 0 ? 200 : 409);
            return;
        }

        $this->session->set_flashdata($processed > 0 ? 'success' : 'error', $message);
        redirect('admin/pengembalian');
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
