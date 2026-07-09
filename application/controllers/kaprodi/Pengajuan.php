<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengajuan extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'download']);
        $this->load->model('kaprodi/Kaprodi_model');
        $this->guard_kaprodi();
    }

    private function guard_kaprodi() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (strtolower((string) $this->session->userdata('role')) !== 'kaprodi') {
            $this->session->set_flashdata('error', 'Akses ditolak. Panel ini khusus Kaprodi.');
            redirect('dashboard');
        }
    }

    public function simpan() {
        $nama_prodi = trim($this->input->post('nama_prodi', true));
        $nama_pengajuan = trim($this->input->post('nama_pengajuan', true));

        if ($nama_prodi === '' || $nama_pengajuan === '') {
            $this->session->set_flashdata('error', 'Nama prodi dan nama pengajuan wajib diisi.');
            redirect('kaprodi/dashboard');
        }

        $uraian = $this->input->post('uraian_barang');
        $vol_input = (array) $this->input->post('vol');
        $nego_vol_input = (array) $this->input->post('hasil_negosiasi_vol');
        $items = [];
        foreach ((array) $uraian as $i => $value) {
            $vol = ($vol_input[$i] ?? '') !== '' ? (float) $vol_input[$i] : 1;
            $nego_vol = ($nego_vol_input[$i] ?? '') !== '' ? (float) $nego_vol_input[$i] : $vol;
            $harga_penawaran = (float) str_replace(['.', ','], ['', '.'], (string) ($this->input->post('harga_penawaran_sat')[$i] ?? 0));
            $harga_negosiasi = (float) str_replace(['.', ','], ['', '.'], (string) ($this->input->post('hasil_negosiasi_sat')[$i] ?? 0));
            $items[] = [
                'uraian_barang' => trim($value),
                'vol' => $vol,
                'satuan' => trim($this->input->post('satuan')[$i] ?? 'unit'),
                'harga_penawaran_sat' => $harga_penawaran,
                'link_penawaran' => trim($this->input->post('link_penawaran')[$i] ?? ''),
                'hasil_negosiasi_vol' => $nego_vol,
                'hasil_negosiasi_sat' => $harga_negosiasi,
                'garansi' => trim($this->input->post('garansi')[$i] ?? ''),
            ];
        }

        $header = [
            'kode_pengajuan' => $this->Kaprodi_model->generate_kode(),
            'id_user' => $this->session->userdata('id_user'),
            'nama_prodi' => $nama_prodi,
            'nama_pengajuan' => $nama_pengajuan,
            'kebutuhan_lab' => $this->input->post('kebutuhan_lab', true),
            'anak_perusahaan' => $this->input->post('anak_perusahaan', true),
            'status' => 'Pengajuan',
            'catatan_negosiasi' => $this->input->post('catatan_negosiasi', true),
        ];

        $id = $this->Kaprodi_model->create_pengajuan($header, $items);
        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Pengajuan kebutuhan prodi berhasil dibuat.' : 'Gagal membuat pengajuan.');
        redirect('kaprodi/dashboard');
    }

    public function negosiasi($id_pengajuan) {
        $this->Kaprodi_model->update_status($id_pengajuan, 'Negosiasi', [
            'catatan_negosiasi' => $this->input->post('catatan_negosiasi', true)
        ]);
        $this->session->set_flashdata('success', 'Pengajuan dinaikkan ke tahap negosiasi.');
        redirect('kaprodi/dashboard');
    }

    public function acc($id_pengajuan) {
        $this->Kaprodi_model->update_status($id_pengajuan, 'ACC Anak Perusahaan');
        $this->session->set_flashdata('success', 'Hasil negosiasi ditandai ACC anak perusahaan.');
        redirect('kaprodi/dashboard');
    }

    public function alokasi($id_pengajuan) {
        $pengajuan = $this->Kaprodi_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('kaprodi/dashboard');
        }

        foreach ((array) $this->input->post('alokasi_item') as $id_item => $alokasi) {
            $this->Kaprodi_model->update_alokasi_item($id_item, $alokasi);
        }

        $this->Kaprodi_model->update_status($id_pengajuan, 'Alokasi', [
            'catatan_alokasi' => $this->input->post('catatan_alokasi', true)
        ]);
        $this->session->set_flashdata('success', 'Sisa anggaran berhasil dicatat untuk alokasi.');
        redirect('kaprodi/dashboard');
    }

    public function bast($id_pengajuan) {
        $this->Kaprodi_model->update_status($id_pengajuan, 'BAST', [
            'bast_nomor' => $this->input->post('bast_nomor', true),
            'bast_tanggal' => $this->input->post('bast_tanggal', true),
            'bast_penerima' => $this->input->post('bast_penerima', true),
            'bast_catatan' => $this->input->post('bast_catatan', true),
        ]);
        $this->session->set_flashdata('success', 'BAST distribusi barang berhasil dicatat.');
        redirect('kaprodi/dashboard');
    }

    public function selesai($id_pengajuan) {
        $this->Kaprodi_model->update_status($id_pengajuan, 'Selesai');
        $this->session->set_flashdata('success', 'Pengajuan ditandai selesai.');
        redirect('kaprodi/dashboard');
    }

    public function export_excel($id_pengajuan) {
        $pengajuan = $this->Kaprodi_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            show_404();
        }

        $filename = 'klarifikasi_pengadaan_' . $pengajuan->kode_pengajuan . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo "\xEF\xBB\xBF";
        $this->load->view('kaprodi/export_excel', ['pengajuan' => $pengajuan]);
    }
}