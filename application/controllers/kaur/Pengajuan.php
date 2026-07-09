<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengajuan extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'download']);
        $this->load->model('kaur/Kaur_model');
        $this->guard_kaur();
    }

    private function guard_kaur() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (strtolower((string) $this->session->userdata('role')) !== 'kaur') {
            $this->session->set_flashdata('error', 'Akses ditolak. Panel ini khusus Kaur Laboratorium.');
            redirect('dashboard');
        }
    }

    private function parse_money($value) {
        $clean = preg_replace('/[^0-9,.-]/', '', (string) $value);
        return (float) str_replace(['.', ','], ['', '.'], $clean);
    }

    public function simpan() {
        $nama_lab = trim($this->input->post('nama_lab', true));
        $nama_pengajuan = trim($this->input->post('nama_pengajuan', true));

        if ($nama_lab === '' || $nama_pengajuan === '') {
            $this->session->set_flashdata('error', 'Nama lab dan nama pengajuan wajib diisi.');
            redirect('kaur/dashboard');
        }

        $uraian_input = (array) $this->input->post('uraian_barang');
        $vol_input = (array) $this->input->post('vol');
        $satuan_input = (array) $this->input->post('satuan');
        $harga_input = (array) $this->input->post('harga_penawaran_sat');
        $link_input = (array) $this->input->post('link_penawaran');
        $nego_vol_input = (array) $this->input->post('hasil_negosiasi_vol');
        $nego_sat_input = (array) $this->input->post('hasil_negosiasi_sat');
        $garansi_input = (array) $this->input->post('garansi');

        $items = [];
        foreach ($uraian_input as $i => $value) {
            $vol = ($vol_input[$i] ?? '') !== '' ? (float) $vol_input[$i] : 1;
            $nego_vol = ($nego_vol_input[$i] ?? '') !== '' ? (float) $nego_vol_input[$i] : $vol;
            $items[] = [
                'uraian_barang' => trim($value),
                'vol' => $vol,
                'satuan' => trim($satuan_input[$i] ?? 'unit'),
                'harga_penawaran_sat' => $this->parse_money($harga_input[$i] ?? 0),
                'link_penawaran' => trim($link_input[$i] ?? ''),
                'hasil_negosiasi_vol' => $nego_vol,
                'hasil_negosiasi_sat' => $this->parse_money($nego_sat_input[$i] ?? 0),
                'garansi' => trim($garansi_input[$i] ?? ''),
            ];
        }

        $header = [
            'kode_pengajuan' => $this->Kaur_model->generate_kode(),
            'id_user' => $this->session->userdata('id_user'),
            'nama_lab' => $nama_lab,
            'nama_pengajuan' => $nama_pengajuan,
            'kebutuhan_lab' => $this->input->post('kebutuhan_lab', true),
            'anak_perusahaan' => $this->input->post('anak_perusahaan', true),
            'status' => 'Pengajuan',
            'catatan_negosiasi' => $this->input->post('catatan_negosiasi', true),
        ];

        $id = $this->Kaur_model->create_pengajuan($header, $items);
        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Pengajuan kebutuhan lab berhasil dibuat.' : 'Gagal membuat pengajuan.');
        redirect('kaur/dashboard');
    }

    public function bast_tahap1($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('kaur/dashboard');
        }

        $this->Kaur_model->update_status($id_pengajuan, 'Approval Tahap 1 (BAST)', [
            'bast_nomor' => $this->input->post('bast_nomor', true),
            'bast_tanggal' => $this->input->post('bast_tanggal', true),
            'bast_penerima' => $this->input->post('bast_penerima', true),
            'bast_catatan' => $this->input->post('bast_catatan', true),
            'bast_disetujui_oleh' => null,
            'bast_disetujui_pada' => null,
        ]);
        $this->session->set_flashdata('success', 'BAST masuk ke Approval Tahap 1.');
        redirect('kaur/dashboard#approval-bast');
    }

    public function setujui_bast($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('kaur/dashboard');
        }

        $this->Kaur_model->update_status($id_pengajuan, 'BAST Disetujui', [
            'bast_disetujui_oleh' => $this->session->userdata('id_user'),
            'bast_disetujui_pada' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Approval Tahap 1 BAST berhasil disetujui.');
        redirect('kaur/dashboard#approval-bast');
    }

    public function negosiasi($id_pengajuan) {
        $this->Kaur_model->update_status($id_pengajuan, 'Negosiasi', [
            'catatan_negosiasi' => $this->input->post('catatan_negosiasi', true)
        ]);
        $this->session->set_flashdata('success', 'Pengajuan dinaikkan ke tahap negosiasi.');
        redirect('kaur/dashboard');
    }

    public function acc($id_pengajuan) {
        $this->Kaur_model->update_status($id_pengajuan, 'ACC Anak Perusahaan');
        $this->session->set_flashdata('success', 'Hasil negosiasi ditandai ACC anak perusahaan.');
        redirect('kaur/dashboard');
    }

    public function alokasi($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('kaur/dashboard');
        }

        foreach ((array) $this->input->post('alokasi_item') as $id_item => $alokasi) {
            $this->Kaur_model->update_alokasi_item($id_item, $alokasi);
        }

        $this->Kaur_model->update_status($id_pengajuan, 'Alokasi', [
            'catatan_alokasi' => $this->input->post('catatan_alokasi', true)
        ]);
        $this->session->set_flashdata('success', 'Sisa anggaran berhasil dicatat untuk alokasi.');
        redirect('kaur/dashboard');
    }

    public function selesai($id_pengajuan) {
        $this->Kaur_model->update_status($id_pengajuan, 'Selesai');
        $this->session->set_flashdata('success', 'Pengajuan ditandai selesai.');
        redirect('kaur/dashboard');
    }

    public function export_excel($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_by_id($id_pengajuan);
        if (!$pengajuan) {
            show_404();
        }

        $filename = 'laporan_pengajuan_kaur_' . $pengajuan->kode_pengajuan . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo "\xEF\xBB\xBF";
        $this->load->view('kaur/export_excel', ['pengajuan' => $pengajuan]);
    }
}