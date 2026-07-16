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
        $filters = [
            'status' => $this->input->get('status', true),
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];

        $data['title'] = 'Data Peminjaman';
        $data['filters'] = $filters;
        $data['peminjaman'] = $this->Peminjaman_model->search_peminjaman($filters);
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
        $filename = 'laporan_pengajuan_sampai_acc_' . date('Ymd_His') . '.xls';

        $this->output
            ->set_content_type('application/vnd.ms-excel')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: max-age=0');
        $this->load->view('admin/export_pengajuan_acc', $data);
    }

    public function scanner() {
        $data['title'] = 'Scanner QR Serah Terima';
        $this->load->view('admin/scanner_qr', $data);
    }

    public function serah_terima($group_id) {
        $group_id = rawurldecode($group_id);
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id($group_id);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Transaksi dari QR tidak ditemukan.');
            redirect('admin/peminjaman/scanner');
        }

        $data['title'] = 'Serah Terima Barang';
        $data['peminjaman'] = $peminjaman;
        $data['qr_payload'] = $this->Peminjaman_model->get_qr_payload($group_id);
        $this->load->view('admin/serah_terima', $data);
    }

    public function proses_serah($group_id) {
        $group_id = rawurldecode($group_id);
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_group_id($group_id);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Transaksi tidak ditemukan.');
            redirect('admin/peminjaman/scanner');
        }

        if ($peminjaman->status !== 'Disetujui (Menunggu Pengambilan)') {
            $this->session->set_flashdata('error', 'Barang hanya bisa diserahkan setelah ACC Kaur.');
            redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
        }

        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];
        foreach ($items as $item) {
            $aset = $this->Aset_model->get_aset_by_id($item->id_aset);
            if (!$aset || $item->jumlah_pinjam > $aset->jumlah_tersedia) {
                $this->session->set_flashdata('error', 'Stok ' . ($item->nama_aset ?? 'barang') . ' tidak cukup saat serah terima.');
                redirect('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
            }
        }

        $this->db->trans_start();
        foreach ($items as $item) {
            $this->Aset_model->update_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam);
            $this->Aset_model->increment_total_peminjaman($item->id_aset);
        }
        $this->Peminjaman_model->update_group_status($group_id, [
            'status' => 'Sedang Dipinjam',
            'catatan_laboran' => trim($this->input->post('catatan_serah', true)),
        ]);
        $this->db->trans_complete();

        $this->session->set_flashdata($this->db->trans_status() ? 'success' : 'error', $this->db->trans_status() ? 'Barang berhasil diserahkan ke peminjam.' : 'Gagal memproses serah terima.');
        redirect('admin/peminjaman');
    }

    public function kembalikan($id_peminjaman) {
        $peminjaman = $this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman);
        if (!$peminjaman) {
            $this->session->set_flashdata('error', 'Data peminjaman tidak ditemukan.');
            redirect('admin/peminjaman');
        }

        if (!in_array($peminjaman->status, ['Sedang Dipinjam', 'Dipinjam'], true)) {
            $this->session->set_flashdata('error', 'Hanya peminjaman yang sedang dipinjam yang bisa dikembalikan.');
            redirect('admin/peminjaman');
        }

        $items = !empty($peminjaman->detail_barang) ? $peminjaman->detail_barang : [$peminjaman];
        $kondisi_kembali = $this->input->post('kondisi_saat_kembali', true) ?: null;
        $foto_pengembalian = $this->upload_evidence_pengembalian();
        if ($foto_pengembalian === false) {
            redirect('admin/peminjaman');
        }

        $this->db->trans_start();
        foreach ($items as $item) {
            if (!empty($item->id_aset) && !empty($item->jumlah_pinjam)) {
                $this->Aset_model->kembalikan_jumlah_tersedia($item->id_aset, $item->jumlah_pinjam);
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
            'catatan' => $this->input->post('catatan_pengembalian', true),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        if ($foto_pengembalian) {
            $update['foto_pengembalian'] = $foto_pengembalian;
        }

        if (!empty($peminjaman->group_id)) {
            $this->db->where('group_id', $peminjaman->group_id)->update('peminjaman', $update);
        } else {
            $this->db->where('id_peminjaman', $id_peminjaman)->update('peminjaman', $update);
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
        redirect('admin/peminjaman');
    }

    private function upload_evidence_pengembalian() {
        if (empty($_FILES['foto_pengembalian']['name'])) {
            return null;
        }

        $path = './assets/uploads/bukti_pengembalian/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $config = [
            'upload_path' => $path,
            'allowed_types' => 'jpg|jpeg|png|pdf',
            'max_size' => 5120,
            'encrypt_name' => true,
        ];

        $this->load->library('upload');
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('foto_pengembalian')) {
            $this->session->set_flashdata('error', 'Upload evidence pengembalian gagal: ' . $this->upload->display_errors('', ''));
            return false;
        }

        $file = $this->upload->data();
        return 'assets/uploads/bukti_pengembalian/' . $file['file_name'];
    }
}
