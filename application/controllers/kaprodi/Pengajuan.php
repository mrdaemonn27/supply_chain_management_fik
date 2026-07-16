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
        $jenis_pengajuan = trim($this->input->post('jenis_pengajuan', true));

        if (!in_array($jenis_pengajuan, ['Barang', 'Jasa'], true)) {
            $jenis_pengajuan = 'Barang';
        }

        if ($nama_prodi === '' || $nama_pengajuan === '') {
            $this->session->set_flashdata('error', 'Nama prodi dan nama pengajuan wajib diisi.');
            redirect('kaprodi/dashboard');
        }

        $uraian = $this->input->post('uraian_barang');
        $vol_input = (array) $this->input->post('vol');
        $satuan_input = (array) $this->input->post('satuan');
        $link_input = (array) $this->input->post('link_penawaran');
        $items = [];
        foreach ((array) $uraian as $i => $value) {
            $nama_item = trim((string) $value);
            if ($nama_item === '') {
                continue;
            }

            $vol = ($vol_input[$i] ?? '') !== '' ? (float) $vol_input[$i] : 1;
            $items[] = [
                'uraian_barang' => $nama_item,
                'vol' => max(1, $vol),
                'satuan' => trim($satuan_input[$i] ?? 'unit'),
                'harga_penawaran_sat' => 0,
                'link_penawaran' => trim($link_input[$i] ?? ''),
                'hasil_negosiasi_vol' => null,
                'hasil_negosiasi_sat' => null,
                'garansi' => null,
            ];
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Minimal satu kebutuhan barang atau jasa wajib diisi.');
            redirect('kaprodi/dashboard');
        }

        $header = [
            'kode_pengajuan' => $this->Kaprodi_model->generate_kode(),
            'id_user' => $this->session->userdata('id_user'),
            'jenis_pengajuan' => $jenis_pengajuan,
            'nama_prodi' => $nama_prodi,
            'nama_pengajuan' => $nama_pengajuan,
            'kebutuhan_lab' => $this->input->post('kebutuhan_lab', true),
            'anak_perusahaan' => null,
            'status' => 'Pengajuan',
            'catatan_negosiasi' => null,
        ];

        $id = $this->Kaprodi_model->create_pengajuan($header, $items);
        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Pengajuan kebutuhan prodi berhasil dibuat.' : 'Gagal membuat pengajuan.');
        redirect('kaprodi/dashboard');
    }

    public function negosiasi($id_pengajuan) {
        $this->session->set_flashdata('error', 'Tahap negosiasi sekarang menjadi kewenangan Kaur Laboratorium.');
        redirect('kaprodi/dashboard');
    }

    public function acc($id_pengajuan) {
        $this->session->set_flashdata('error', 'Approval hasil negosiasi dilakukan oleh Kaur Laboratorium.');
        redirect('kaprodi/dashboard');
    }

    public function alokasi($id_pengajuan) {
        $pengajuan = $this->Kaprodi_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('kaprodi/dashboard');
        }

        $this->session->set_flashdata('error', 'Alokasi anggaran sekarang dikelola oleh Kaur Laboratorium.');
        redirect('kaprodi/dashboard');
    }

    public function bast($id_pengajuan) {
        $this->session->set_flashdata('error', 'Dokumen BAST diinput oleh Laboran atau Kaur sesuai alur internal.');
        redirect('kaprodi/dashboard');
    }

    public function selesai($id_pengajuan) {
        $this->session->set_flashdata('error', 'Status selesai ditentukan setelah proses BAST dan inventarisasi.');
        redirect('kaprodi/dashboard');
    }

    public function export_pengajuan() {
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
        ];

        $rows = $this->Kaprodi_model->get_filtered_by_user($this->session->userdata('id_user'), $filters, null, null);
        $filename = 'berita_acara_klarifikasi_kaprodi_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $this->load->view('kaur/export_ba_klarifikasi', [
            'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
            'pengajuan_list' => $rows,
            'show_negosiasi' => false,
            'role_label' => 'Kaprodi',
        ]);
    }

    public function export_excel($id_pengajuan) {
        $pengajuan = $this->Kaprodi_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            show_404();
        }

        $filename = 'berita_acara_klarifikasi_' . $pengajuan->kode_pengajuan . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $this->load->view('kaur/export_ba_klarifikasi', [
            'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
            'pengajuan' => $pengajuan,
            'show_negosiasi' => false,
            'role_label' => 'Kaprodi',
        ]);
    }
}
