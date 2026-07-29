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

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('dashboard');
        }
    }

    public function index() {
        $allowed_status = [
            'Menunggu Verifikasi Laboran',
            'Menunggu Pengecekan Laboran',
            'Menunggu Persetujuan',
            'Menunggu ACC Kaur',
            'Disetujui (Menunggu Finalisasi QR)',
            'Disetujui (Menunggu Pengambilan)',
            'Sedang Dipinjam',
            'Dipinjam',
            'Dikembalikan',
            'Terlambat',
            'Ditolak',
        ];
        $status = $this->input->get('status', true);
        if (!in_array($status, $allowed_status, true)) {
            $status = '';
        }

        $filters = [
            'status' => $status,
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];
        if (empty($filters['status'])) {
            $filters['status_in'] = array_values(array_diff($allowed_status, ['Terlambat']));
        }

        $page = max(1, (int) $this->input->get('page', true));
        $requested_per_page = strtolower(trim((string) $this->input->get('per_page', true)));
        if ($requested_per_page === 'all') {
            $per_page = 'all';
            $page = 1;
        } else {
            $requested_limit = (int) $requested_per_page;
            $per_page = in_array($requested_limit, [10, 25, 50], true) ? $requested_limit : 10;
        }
        $rows = $this->Peminjaman_model->search_peminjaman($filters);
        $total_rows = count($rows);
        $total_pages = $per_page === 'all' ? 1 : max(1, (int) ceil($total_rows / $per_page));
        $page = min($page, $total_pages);
        $visible_rows = $per_page === 'all' ? $rows : array_slice($rows, ($page - 1) * $per_page, $per_page);

        $data['title'] = 'Data Peminjaman';
        $data['filters'] = $filters;
        $data['status_options'] = array_merge([''], $allowed_status);
        $data['peminjaman'] = $visible_rows;
        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total_rows,
            'total_pages' => $total_pages,
        ];
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        $this->load->view('admin/peminjaman', $data);
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
                'back_url' => site_url('admin/peminjaman'),
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

    public function scanner() {
        $data['title'] = 'Scanner QR Serah Terima';
        $data['scanner_label'] = 'Scanner QR Peminjaman';
        $data['scanner_desc'] = 'Scan QR transaksi dari akun peminjam untuk proses serah barang.';
        $data['back_url'] = site_url('admin/peminjaman');
        $data['back_label'] = 'Data Peminjaman';
        $this->load->view('admin/scanner_qr', $data);
    }

    public function serah_terima($group_id) {
        $group_id = rawurldecode($group_id);
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id($group_id);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Transaksi dari QR tidak ditemukan.');
            redirect('admin/peminjaman/scanner');
        }

        if (in_array(($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true) && (int) ($peminjaman->qr_locked ?? 0) === 1) {
            $data['title'] = 'Validasi Pengembalian Barang';
            $data['peminjaman'] = $peminjaman;
            $data['qr_valid'] = true;
            $this->load->view('admin/validasi_pengembalian', $data);
            return;
        }

        $data['title'] = 'Serah Terima Barang';
        $data['peminjaman'] = $peminjaman;
        $data['qr_payload'] = $this->Peminjaman_model->get_qr_payload($group_id);
        $data['qr_valid'] = ($peminjaman->status ?? '') === 'Disetujui (Menunggu Pengambilan)' && (int) ($peminjaman->qr_locked ?? 0) === 1;
        $data['qr_message'] = $this->qr_message_for($peminjaman);
        $this->load->view('admin/serah_terima', $data);
    }

    public function proses_serah($group_id) {
        $group_id = rawurldecode($group_id);
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id($group_id);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Transaksi tidak ditemukan.');
            redirect('admin/peminjaman/scanner');
        }

        if ($peminjaman->status !== 'Disetujui (Menunggu Pengambilan)' || (int) ($peminjaman->qr_locked ?? 0) !== 1) {
            $this->session->set_flashdata('error', 'Barang hanya bisa diserahkan setelah ACC Kaur dan QR aktif. QR yang sudah digunakan tidak dapat dipakai lagi untuk serah terima.');
            redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
        }

        $this->db->trans_start();
        $locked_peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id_for_update($group_id);
        if (!$locked_peminjaman || $locked_peminjaman->status !== 'Disetujui (Menunggu Pengambilan)' || (int) ($locked_peminjaman->qr_locked ?? 0) !== 1) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'QR transaksi sudah dipakai atau tidak lagi aktif.');
            redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
        }
        $peminjaman = $locked_peminjaman;
        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];

        // Terapkan jumlah yang diedit laboran (tidak boleh melebihi jumlah pinjam asli)
        $items = $this->apply_edited_jumlah($items);

        foreach ($items as $item) {
            $aset = $this->Aset_model->get_aset_by_id_for_update($item->id_aset);
            if (!$aset || $item->jumlah_pinjam > $aset->jumlah_tersedia) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Stok ' . ($item->nama_aset ?? 'barang') . ' tidak cukup saat serah terima.');
                redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
            }
        }

        $evidence = $this->upload_multiple_evidence('foto_serah');
        if ($evidence === false) {
            $this->db->trans_rollback();
            redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
        }

        foreach ($items as $item) {
            if (!$this->Aset_model->update_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam)) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Stok berubah saat proses serah terima. Silakan periksa kembali transaksi.');
                redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
            }
            $this->Aset_model->increment_total_peminjaman($item->id_aset);
        }
        $this->Peminjaman_model->update_group_status($group_id, [
            'status' => 'Sedang Dipinjam',
            'catatan_laboran' => trim($this->input->post('catatan_serah', true)),
        ]);
        foreach ($evidence as $file) {
            $this->Peminjaman_model->insert_evidence([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'group_id' => $peminjaman->group_id,
                'jenis' => 'serah_terima',
                'nama_file' => $file['path'],
                'original_name' => $file['original_name'],
                'uploaded_by' => $this->session->userdata('id_user'),
            ]);
        }
        if (!empty($evidence)) {
            $this->db->where('id_peminjaman', $peminjaman->id_peminjaman)->update('peminjaman', ['foto_bukti' => $evidence[0]['path']]);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'Barang sudah dipinjam',
                'Serah terima barang sudah dikonfirmasi Laboran. Gunakan QR transaksi yang sama saat pengembalian.',
                site_url('peminjaman/riwayat')
            );
        }

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Barang berhasil diserahkan ke peminjam.' : 'Gagal memproses serah terima.');
        redirect('admin/peminjaman');
    }

    public function finalkan_qr($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('admin/peminjaman');
        }

        if (($peminjaman->status ?? '') !== 'Disetujui (Menunggu Finalisasi QR)') {
            $this->session->set_flashdata('error', 'QR hanya bisa difinalkan setelah ACC Kaur dan sebelum serah terima.');
            redirect('admin/peminjaman');
        }

        $ok = $this->Peminjaman_model->finalize_qr($peminjaman->group_id, $this->session->userdata('id_user'));
        if ($ok && !empty($peminjaman->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $peminjaman->id_user,
                'QR transaksi aktif',
                'Data peminjaman sudah final. QR yang sama digunakan saat pengambilan dan pengembalian barang.',
                site_url('peminjaman/riwayat')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'QR peminjaman berhasil difinalkan dan data transaksi dikunci.' : 'Gagal memfinalkan QR.');
        redirect('admin/peminjaman');
    }

    public function validasi_pengembalian($group_id) {
        $group_id = rawurldecode($group_id);
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id($group_id);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Transaksi dari QR pengembalian tidak ditemukan.');
            redirect('admin/pengembalian/scanner');
        }

        $data['title'] = 'Validasi Pengembalian Barang';
        $data['peminjaman'] = $peminjaman;
        $data['qr_valid'] = in_array(($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true) && (int) ($peminjaman->qr_locked ?? 0) === 1;
        $data['qr_message'] = $this->qr_message_for($peminjaman);
        $this->load->view('admin/validasi_pengembalian', $data);
    }

    public function kembalikan($id_peminjaman) {
        $redirect_to = $this->input->post('return_to', true) === 'admin/peminjaman' ? 'admin/peminjaman' : 'admin/pengembalian';
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect($redirect_to);
        }

        $from_qr = $this->input->post('from_qr', true) === '1';
        if (!in_array($peminjaman->status, ['Sedang Dipinjam', 'Dipinjam'], true) || ($from_qr && (int) ($peminjaman->qr_locked ?? 0) !== 1)) {
            $this->session->set_flashdata('error', 'QR transaksi sudah tidak berlaku atau peminjaman tidak sedang aktif.');
            redirect($redirect_to);
        }

        $kondisi_kembali = $this->input->post('kondisi_saat_kembali', true) ?: null;
        if (!in_array($kondisi_kembali, ['Baik', 'Rusak', 'Hilang'], true)) {
            $this->session->set_flashdata('error', 'Kondisi pengembalian wajib dipilih dengan benar.');
            redirect($redirect_to);
        }

        $catatan_pengembalian = trim((string) $this->input->post('catatan_pengembalian', true));
        if (in_array($kondisi_kembali, ['Rusak', 'Hilang'], true)) {
            if ($catatan_pengembalian === '') {
                $this->session->set_flashdata('error', 'Keterangan wajib diisi jika kondisi barang Rusak atau Hilang.');
                redirect($redirect_to);
            }
            if (!$this->has_uploaded_files('foto_pengembalian')) {
                $this->session->set_flashdata('error', 'Evidence wajib diupload jika kondisi barang Rusak atau Hilang.');
                redirect($redirect_to);
            }
        }
        $this->db->trans_start();
        $locked_peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id_for_update($peminjaman->group_id ?: 'single-' . $id_peminjaman);
        if (!$locked_peminjaman || !in_array($locked_peminjaman->status, ['Sedang Dipinjam', 'Dipinjam'], true) || ($from_qr && (int) ($locked_peminjaman->qr_locked ?? 0) !== 1)) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'QR transaksi sudah dipakai atau peminjaman sudah selesai dikembalikan.');
            redirect($redirect_to);
        }
        $peminjaman = $locked_peminjaman;
        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];

        // Terapkan jumlah yang diedit laboran (tidak boleh melebihi jumlah pinjam asli)
        $items = $this->apply_edited_jumlah($items);

        $foto_pengembalian = $this->upload_evidence_pengembalian();
        if ($foto_pengembalian === false) {
            $this->db->trans_rollback();
            redirect($redirect_to);
        }

        foreach ($items as $item) {
            if (!empty($item->id_aset) && !empty($item->jumlah_pinjam)) {
                if ($kondisi_kembali === 'Baik') {
                    $this->Aset_model->kembalikan_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam);
                }
                if ($kondisi_kembali) {
                    $this->db->where('id_aset', $item->id_aset)->update('aset', [
                        'kondisi' => $kondisi_kembali,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $update = [
            'status' => 'Dikembalikan',
            'tanggal_kembali_actual' => date('Y-m-d'),
            'kondisi_saat_kembali' => $kondisi_kembali,
            'catatan' => $catatan_pengembalian,
            'qr_locked' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if (!empty($foto_pengembalian)) {
            $update['foto_pengembalian'] = $foto_pengembalian[0]['path'];
        }

        if (!empty($peminjaman->group_id)) {
            $this->db->where('group_id', $peminjaman->group_id)->update('peminjaman', $update);
        } else {
            $this->db->where('id_peminjaman', $id_peminjaman)->update('peminjaman', $update);
        }

        foreach ($foto_pengembalian as $file) {
            $this->Peminjaman_model->insert_evidence([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'group_id' => $peminjaman->group_id,
                'jenis' => 'pengembalian',
                'nama_file' => $file['path'],
                'original_name' => $file['original_name'],
                'uploaded_by' => $this->session->userdata('id_user'),
            ]);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            if (!empty($peminjaman->id_user)) {
                $this->Peminjaman_model->create_notifikasi(
                    null,
                    $peminjaman->id_user,
                    'Barang sudah dikembalikan',
                    'Pengembalian peminjaman Anda sudah dikonfirmasi oleh Laboran.',
                    site_url('peminjaman/riwayat')
                );
            }
            $this->Peminjaman_model->create_notifikasi(
                'kaur',
                null,
                'Barang sudah dikembalikan',
                ($peminjaman->nama_peminjam ?? 'Peminjam') . ' sudah mengembalikan barang ke Laboran.',
                site_url('kaur/dashboard/peminjaman')
            );
        }

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Barang berhasil ditandai kembali.' : 'Gagal memproses pengembalian.');
        redirect($redirect_to);
    }

    public function upload_evidence_serah($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman || !in_array(($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true)) {
            $this->session->set_flashdata('error', 'Evidence hanya dapat ditambahkan pada transaksi yang sedang dipinjam.');
            redirect('admin/pengembalian');
        }

        $files = $this->upload_multiple_evidence('foto_serah');
        if ($files === false) {
            redirect('admin/pengembalian');
        }
        if (empty($files)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu foto dokumentasi.');
            redirect('admin/pengembalian');
        }

        foreach ($files as $file) {
            $this->Peminjaman_model->insert_evidence([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'group_id' => $peminjaman->group_id,
                'jenis' => 'serah_terima',
                'nama_file' => $file['path'],
                'original_name' => $file['original_name'],
                'uploaded_by' => $this->session->userdata('id_user'),
            ]);
        }
        $this->session->set_flashdata('success', count($files) . ' foto dokumentasi serah terima berhasil disimpan.');
        redirect('admin/pengembalian');
    }

    private function qr_message_for($peminjaman) {
        if (($peminjaman->status ?? '') === 'Dikembalikan') {
            return 'Transaksi sudah selesai dikembalikan. QR ini sudah tidak berlaku.';
        }
        if ((int) ($peminjaman->qr_locked ?? 0) !== 1) {
            return 'QR belum aktif atau sudah dinonaktifkan. QR hanya bisa digunakan setelah finalisasi transaksi.';
        }
        if (($peminjaman->status ?? '') !== 'Disetujui (Menunggu Pengambilan)') {
            return 'QR belum dapat digunakan karena transaksi belum berada pada tahap pengambilan.';
        }
        return 'QR terbaca, tetapi transaksi belum dapat diproses.';
    }

    /**
     * Terapkan jumlah_barang (hasil edit laboran di form) ke tiap item,
     * dikunci maksimal ke jumlah_pinjam asli supaya tidak bisa dinaikkan.
     * Key posting: jumlah_barang[<kode_aset>].
     */
    private function apply_edited_jumlah(array $items) {
        $jumlah_edit = $this->input->post('jumlah_barang', true);
        if (empty($jumlah_edit) || !is_array($jumlah_edit)) {
            return $items;
        }

        foreach ($items as $item) {
            $kode = $item->kode_aset ?? null;
            $jumlah_asli = (int) ($item->jumlah_pinjam ?? 0);

            if ($kode !== null && array_key_exists($kode, $jumlah_edit)) {
                $jumlah_baru = (int) $jumlah_edit[$kode];
                $jumlah_baru = max(0, min($jumlah_baru, $jumlah_asli));
                $item->jumlah_pinjam = $jumlah_baru;
            }
        }

        return $items;
    }

    /**
     * Cek apakah field file (bisa single atau array) benar-benar berisi file.
     */
    private function has_uploaded_files($field) {
        if (empty($_FILES[$field]['name'])) {
            return false;
        }
        foreach ((array) $_FILES[$field]['name'] as $name) {
            if (trim((string) $name) !== '') {
                return true;
            }
        }
        return false;
    }

    private function upload_multiple_evidence($field) {
        if (empty($_FILES[$field]['name'])) {
            return [];
        }

        $path = './assets/uploads/bukti_serah/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $files = [];
        $this->load->library('upload');
        foreach ((array) $_FILES[$field]['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $_FILES['single_evidence'] = [
                'name' => $_FILES[$field]['name'][$index],
                'type' => $_FILES[$field]['type'][$index],
                'tmp_name' => $_FILES[$field]['tmp_name'][$index],
                'error' => $_FILES[$field]['error'][$index],
                'size' => $_FILES[$field]['size'][$index],
            ];
            $this->upload->initialize([
                'upload_path' => $path,
                'allowed_types' => 'jpg|jpeg|png',
                'max_size' => 5120,
                'encrypt_name' => true,
            ]);
            if (!$this->upload->do_upload('single_evidence')) {
                $this->session->set_flashdata('error', 'Upload foto gagal: ' . $this->upload->display_errors('', ''));
                return false;
            }
            $uploaded = $this->upload->data();
            $files[] = [
                'path' => 'assets/uploads/bukti_serah/' . $uploaded['file_name'],
                'original_name' => $uploaded['client_name'],
            ];
        }
        return $files;
    }

    /**
     * Upload evidence pengembalian. Mendukung banyak file (foto_pengembalian[]).
     * Return array of ['path' => ..., 'original_name' => ...], [] jika tidak ada file,
     * atau false jika salah satu upload gagal.
     */
    private function upload_evidence_pengembalian() {
        $field = 'foto_pengembalian';
        if (empty($_FILES[$field]['name'])) {
            return [];
        }

        $path = './assets/uploads/bukti_pengembalian/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $files = [];
        $this->load->library('upload');
        foreach ((array) $_FILES[$field]['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $_FILES['single_evidence'] = [
                'name' => $_FILES[$field]['name'][$index],
                'type' => $_FILES[$field]['type'][$index],
                'tmp_name' => $_FILES[$field]['tmp_name'][$index],
                'error' => $_FILES[$field]['error'][$index],
                'size' => $_FILES[$field]['size'][$index],
            ];
            $this->upload->initialize([
                'upload_path' => $path,
                'allowed_types' => 'jpg|jpeg|png|pdf',
                'max_size' => 5120,
                'encrypt_name' => true,
            ]);
            if (!$this->upload->do_upload('single_evidence')) {
                $this->session->set_flashdata('error', 'Upload evidence pengembalian gagal: ' . $this->upload->display_errors('', ''));
                return false;
            }
            $uploaded = $this->upload->data();
            $files[] = [
                'path' => 'assets/uploads/bukti_pengembalian/' . $uploaded['file_name'],
                'original_name' => $uploaded['client_name'],
            ];
        }
        return $files;
    }
}
