<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengajuan extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'download']);
        $this->load->model('kaur/Kaur_model');
        $this->load->model('Peminjaman_model');
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
            redirect('kaur/dashboard/negosiasi');
        }

        $vendor = trim($this->input->post('vendor', true));
        $status = trim($this->input->post('status', true));
        $harga_awal = $this->parse_money($this->input->post('harga_awal'));
        $harga_negosiasi = $this->parse_money($this->input->post('harga_negosiasi'));
        $volume_negosiasi = (float) $this->input->post('volume_negosiasi');

        if ($vendor === '' || $harga_awal < 0 || $harga_negosiasi < 0 || $volume_negosiasi <= 0) {
            $this->session->set_flashdata('error', 'Vendor, harga, dan volume negosiasi wajib diisi dengan benar.');
            redirect('kaur/dashboard/negosiasi');
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
        if ($ok && $status === 'Deal') {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $pengajuan->id_user,
                'Negosiasi selesai',
                'Negosiasi pengajuan ' . $pengajuan->nama_pengajuan . ' sudah berstatus Deal.',
                site_url('kaprodi/dashboard?tab=riwayat')
            );
        }
        redirect('kaur/dashboard/negosiasi');
    }

    public function approval($id_pengajuan, $aksi = 'approve') {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan Kaprodi tidak ditemukan.');
            redirect('kaur/dashboard/approval');
        }

        if ($aksi === 'approve' && !$this->Kaur_model->kaprodi_all_items_deal($id_pengajuan)) {
            $this->session->set_flashdata('error', 'Pengajuan baru bisa disetujui setelah seluruh item berstatus Deal pada tahap negosiasi.');
            redirect('kaur/dashboard/approval');
        }

        $map = [
            'approve' => 'Disetujui',
            'revisi' => 'Revisi',
            'tolak' => 'Ditolak',
        ];
        $status = $map[$aksi] ?? 'Disetujui';
        $catatan = trim($this->input->post('catatan_approval', true));
        $ok = $this->Kaur_model->update_kaprodi_status($id_pengajuan, $status, $catatan);
        if ($ok) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $pengajuan->id_user,
                'Approval pengadaan diperbarui',
                'Pengajuan ' . $pengajuan->nama_pengajuan . ' berstatus ' . $status . '.',
                site_url('kaprodi/dashboard?tab=riwayat')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Status pengajuan berhasil diperbarui.' : 'Gagal memperbarui status.');
        redirect('kaur/dashboard/approval');
    }

    public function simpan_anggaran() {
        $tahun = (int) $this->input->post('tahun');
        $total = $this->parse_money($this->input->post('total_anggaran'));

        if ($tahun < 2000 || $total <= 0) {
            $this->session->set_flashdata('error', 'Tahun dan total anggaran wajib diisi dengan benar.');
            redirect('kaur/dashboard/anggaran');
        }

        $id = $this->Kaur_model->save_anggaran([
            'tahun' => $tahun,
            'total_anggaran' => $total,
            'catatan' => trim($this->input->post('catatan', true)),
            'created_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Total anggaran berhasil disimpan.' : 'Gagal menyimpan anggaran.');
        redirect('kaur/dashboard/anggaran');
    }

    public function simpan_bast($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Pengajuan Kaprodi tidak ditemukan.');
            redirect('kaur/dashboard/bast');
        }

        if ($this->Kaur_model->pengajuan_has_bast($id_pengajuan)) {
            $this->session->set_flashdata('error', 'Pengajuan ini sudah memiliki dokumen BAST.');
            redirect('kaur/dashboard/bast');
        }

        $nomor = trim($this->input->post('nomor_bast', true));
        $tanggal = trim($this->input->post('tanggal_bast', true));
        $jenis = trim($this->input->post('jenis_bast', true));

        if ($nomor === '' || $tanggal === '' || !in_array($jenis, ['Barang', 'Jasa'], true)) {
            $this->session->set_flashdata('error', 'Nomor, tanggal, dan jenis BAST wajib diisi.');
            redirect('kaur/dashboard/bast');
        }

        if (empty($_FILES['file_bast']['name'])) {
            $this->session->set_flashdata('error', 'Dokumen BAST wajib diupload dalam format PDF atau hasil scan.');
            redirect('kaur/dashboard/bast');
        }

        if (!in_array($pengajuan->status, ['Disetujui', 'Approval'], true)) {
            $this->session->set_flashdata('error', 'BAST hanya bisa diinput setelah pengajuan disetujui Kaur.');
            redirect('kaur/dashboard/bast');
        }

        if ($jenis !== ($pengajuan->jenis_pengajuan ?? 'Barang')) {
            $this->session->set_flashdata('error', 'Jenis BAST harus sama dengan jenis pengajuan.');
            redirect('kaur/dashboard/bast');
        }

        $file_path = $this->upload_bast_file();
        if ($file_path === false && !empty($_FILES['file_bast']['name'])) {
            redirect('kaur/dashboard/bast');
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
        if ($id) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $pengajuan->id_user,
                'Barang masuk Inventory',
                'BAST pengajuan ' . $pengajuan->nama_pengajuan . ' sudah diinput dan diproses ke inventory.',
                site_url('kaprodi/dashboard?tab=riwayat')
            );
        }
        redirect('kaur/dashboard/bast');
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
        redirect('kaur/dashboard/bast');
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
        redirect('kaur/dashboard/bast');
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

    public function export_pengajuan_acc() {
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'vendor' => trim((string) $this->input->get('vendor', true)),
            'status_negosiasi' => trim((string) $this->input->get('status_negosiasi', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
        ];

        $data['title'] = 'Berita Acara Klarifikasi Pengajuan Barang/Jasa';
        $data['rows'] = $this->Kaur_model->get_kaprodi_pengajuan_acc_report($filters);
        $data['pengajuan_list'] = $data['rows'];
        $data['show_negosiasi'] = true;
        $data['role_label'] = 'Kaur Laboratorium';
        $filename = 'laporan_pengajuan_barang_jasa_sampai_acc_' . date('Ymd_His') . '.xls';

        if ($this->input->get('download') !== '1' && $this->input->get('inline') !== '1') {
            $query = $this->input->get();
            $this->load->view('shared/export_preview', [
                'title' => 'Preview Berita Acara Klarifikasi',
                'download_url' => current_url() . '?' . http_build_query(array_merge($query, ['download' => 1])),
                'iframe_url' => current_url() . '?' . http_build_query(array_merge($query, ['inline' => 1])),
                'back_url' => site_url('kaur/dashboard/pengajuan'),
            ]);
            return;
        }

        if ($this->input->get('download') === '1') {
            $this->output
                ->set_content_type('application/vnd.ms-excel')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_header('Cache-Control: max-age=0');
        }
        $this->load->view('kaur/export_ba_klarifikasi', $data);
    }

    public function export_excel($id_pengajuan) {
        $pengajuan = $this->Kaur_model->get_kaprodi_by_id($id_pengajuan);
        if (!$pengajuan) {
            $pengajuan = $this->Kaur_model->get_by_id($id_pengajuan);
        }
        if (!$pengajuan) {
            show_404();
        }

        if ($this->input->get('download') !== '1' && $this->input->get('inline') !== '1') {
            $this->load->view('shared/export_preview', [
                'title' => 'Preview Berita Acara Klarifikasi',
                'download_url' => current_url() . '?' . http_build_query(['download' => 1]),
                'iframe_url' => current_url() . '?' . http_build_query(['inline' => 1]),
                'back_url' => site_url('kaur/dashboard/pengajuan'),
            ]);
            return;
        }

        $filename = 'berita_acara_klarifikasi_' . $pengajuan->kode_pengajuan . '.xls';
        if ($this->input->get('download') === '1') {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
        }
        $this->load->view('kaur/export_ba_klarifikasi', [
            'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
            'pengajuan' => $pengajuan,
            'show_negosiasi' => true,
            'role_label' => 'Kaur Laboratorium',
        ]);
    }
}
