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

    public function simpan_negosiasi($id_pengajuan, $id_item) {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan Kaprodi tidak ditemukan.');
            redirect('kaur/dashboard#negosiasi');
        }

        $vendor = trim($this->input->post('vendor', true));
        $status = trim($this->input->post('status', true));
        $harga_awal = $this->parse_money($this->input->post('harga_awal'));
        $harga_negosiasi = $this->parse_money($this->input->post('harga_negosiasi'));
        $volume_negosiasi = (float) $this->input->post('volume_negosiasi');

        if ($vendor === '' || $harga_awal < 0 || $harga_negosiasi < 0 || $volume_negosiasi <= 0) {
            $this->session->set_flashdata('error', 'Vendor, harga, dan volume negosiasi wajib diisi dengan benar.');
            redirect('kaur/dashboard#negosiasi');
        }

        $ok = $this->Kaur_model->save_negosiasi($id_pengajuan, $id_item, [
            'vendor' => $vendor,
            'harga_awal' => $harga_awal,
            'harga_negosiasi' => $harga_negosiasi,
            'volume_negosiasi' => $volume_negosiasi,
            'garansi' => trim($this->input->post('garansi', true)),
            'catatan' => trim($this->input->post('catatan', true)),
            'status' => $status ?: 'Belum Negosiasi',
            'created_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Riwayat negosiasi berhasil disimpan.' : 'Gagal menyimpan negosiasi.');
        redirect('kaur/dashboard#negosiasi');
    }

    public function approval($id_pengajuan, $aksi = 'approve') {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan Kaprodi tidak ditemukan.');
            redirect('kaur/dashboard#approval');
        }

        $map = [
            'approve' => 'Approval',
            'revisi' => 'Revisi',
            'tolak' => 'Ditolak',
        ];
        $status = $map[$aksi] ?? 'Approval';
        $catatan = trim($this->input->post('catatan_approval', true));
        $ok = $this->Kaur_model->update_kaprodi_status($id_pengajuan, $status, $catatan);

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Status pengajuan berhasil diperbarui.' : 'Gagal memperbarui status.');
        redirect('kaur/dashboard#approval');
    }

    public function simpan_anggaran() {
        $tahun = (int) $this->input->post('tahun');
        $total = $this->parse_money($this->input->post('total_anggaran'));

        if ($tahun < 2000 || $total <= 0) {
            $this->session->set_flashdata('error', 'Tahun dan total anggaran wajib diisi dengan benar.');
            redirect('kaur/dashboard#anggaran');
        }

        $id = $this->Kaur_model->save_anggaran([
            'tahun' => $tahun,
            'total_anggaran' => $total,
            'catatan' => trim($this->input->post('catatan', true)),
            'created_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Total anggaran berhasil disimpan.' : 'Gagal menyimpan anggaran.');
        redirect('kaur/dashboard#anggaran');
    }

    public function simpan_bast($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan Kaprodi tidak ditemukan.');
            redirect('kaur/dashboard#bast');
        }

        $nomor = trim($this->input->post('nomor_bast', true));
        $tanggal = trim($this->input->post('tanggal_bast', true));
        $jenis = trim($this->input->post('jenis_bast', true));

        if ($nomor === '' || $tanggal === '' || !in_array($jenis, ['Barang', 'Jasa'], true)) {
            $this->session->set_flashdata('error', 'Nomor, tanggal, dan jenis BAST wajib diisi.');
            redirect('kaur/dashboard#bast');
        }

        $file_path = $this->upload_bast_file();
        if ($file_path === false && !empty($_FILES['file_bast']['name'])) {
            redirect('kaur/dashboard#bast');
        }

        $id = $this->Kaur_model->save_bast($id_pengajuan, [
            'nomor_bast' => $nomor,
            'tanggal_bast' => $tanggal,
            'jenis_bast' => $jenis,
            'file_bast' => $file_path,
            'catatan' => trim($this->input->post('catatan', true)),
            'input_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'BAST berhasil disimpan dan barang diproses ke inventory bila jenisnya Barang.' : 'Gagal menyimpan BAST.');
        redirect('kaur/dashboard#bast');
    }

    private function upload_bast_file() {
        if (empty($_FILES['file_bast']['name'])) {
            return null;
        }

        $path = './uploads/bast/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $config = [
            'upload_path' => $path,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size' => 10240,
            'encrypt_name' => true,
        ];

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('file_bast')) {
            $this->session->set_flashdata('error', 'Upload BAST gagal: ' . $this->upload->display_errors('', ''));
            return false;
        }

        $file = $this->upload->data();
        return 'uploads/bast/' . $file['file_name'];
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
