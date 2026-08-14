<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Pengajuan extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'download']);
        $this->load->model('kaprodi/Kaprodi_model');
        $this->load->model('Peminjaman_model');
        $this->guard_kaprodi();
    }

    private function parse_money($value) {
        $clean = preg_replace('/[^0-9,.-]/', '', (string) $value);
        return (float) str_replace(['.', ','], ['', '.'], $clean);
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

    private function export_multi_filters() {
        $allowed_fields = ['kode', 'pengajuan', 'jenis', 'kebutuhan', 'status', 'tanggal'];
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $valid_fields = [];
        $valid_values = [];

        foreach ($fields as $index => $field) {
            if (count($valid_fields) >= 4) {
                break;
            }
            $field = trim((string) $field);
            if (!in_array($field, $allowed_fields, true)) {
                continue;
            }
            $valid_fields[] = $field;
            $valid_values[] = trim((string) ($values[$index] ?? ''));
        }
        return [$valid_fields, $valid_values];
    }

    public function simpan() {
        $nama_prodi = trim($this->input->post('nama_prodi', true));
        $nama_pengajuan = trim($this->input->post('nama_pengajuan', true));
        $jenis_pengajuan = trim($this->input->post('jenis_pengajuan', true));

        if (!in_array($jenis_pengajuan, ['Barang', 'Jasa', 'Barang dan Jasa'], true)) {
            $jenis_pengajuan = 'Barang';
        }

        if ($nama_prodi === '' || $nama_pengajuan === '') {
            $this->session->set_flashdata('error', 'Nama prodi dan nama pengajuan wajib diisi.');
            redirect('kaprodi/dashboard');
        }

        $uraian = $this->input->post('uraian_barang');
        $vol_input = (array) $this->input->post('vol');
        $satuan_input = (array) $this->input->post('satuan');
        $harga_input = (array) $this->input->post('harga_penawaran_sat');
        $link_input = (array) $this->input->post('link_penawaran');
        $jenis_item_input = (array) $this->input->post('jenis_item');
        $items = [];
        foreach ((array) $uraian as $i => $value) {
            $nama_item = trim((string) $value);
            if ($nama_item === '') {
                continue;
            }

            $vol = ($vol_input[$i] ?? '') !== '' ? (float) $vol_input[$i] : 1;
            $jenis_item = $jenis_pengajuan === 'Barang dan Jasa'
                ? trim((string) ($jenis_item_input[$i] ?? 'Barang'))
                : $jenis_pengajuan;
            if (!in_array($jenis_item, ['Barang', 'Jasa'], true)) {
                $jenis_item = 'Barang';
            }

            $items[] = [
                'jenis_item' => $jenis_item,
                'uraian_barang' => $nama_item,
                'vol' => max(1, $vol),
                'satuan' => trim($satuan_input[$i] ?? 'unit'),
                'harga_penawaran_sat' => max(0, $this->parse_money($harga_input[$i] ?? 0)),
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
        if ($id) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $this->session->userdata('id_user'),
                'Pengajuan berhasil dibuat',
                'Pengajuan ' . $nama_pengajuan . ' berhasil diinput dan masuk ke riwayat Kaprodi.',
                null,
                'kaprodi_pengajuan',
                $id
            );
            $this->Peminjaman_model->create_notifikasi(
                'kaur',
                null,
                'Pengajuan Kaprodi baru',
                $nama_pengajuan . ' dari ' . $nama_prodi . ' menunggu proses Kaur Laboratorium.',
                site_url('kaur/dashboard/pengajuan')
            );
        }
        $this->session->set_flashdata($id ? 'success' : 'error', $id ? 'Pengajuan kebutuhan prodi berhasil dibuat.' : 'Gagal membuat pengajuan.');
        redirect('kaprodi/dashboard?tab=riwayat');
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
        [$filter_fields, $filter_values] = $this->export_multi_filters();
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
            'id_pengajuan' => max(0, (int) $this->input->get('id_pengajuan')),
            'filter_field' => $filter_fields,
            'filter_value' => $filter_values,
        ];

        $rows = $this->Kaprodi_model->get_filtered_by_user($this->session->userdata('id_user'), $filters, null, null);
        if ($this->input->get('download') !== '1' && $this->input->get('inline') !== '1') {
            $this->load->view('shared/export_preview', [
                'title' => 'Preview Berita Acara Klarifikasi',
                'download_url' => current_url() . '?' . http_build_query(array_merge($this->input->get(), ['download' => 1])),
                'iframe_url' => current_url() . '?' . http_build_query(array_merge($this->input->get(), ['inline' => 1])),
                'back_url' => site_url('kaprodi/dashboard?tab=riwayat'),
            ]);
            return;
        }

        $filename = 'berita_acara_klarifikasi_kaprodi_' . date('Ymd_His') . '.xlsx';
        if ($this->input->get('download') === '1') {
            $this->load->helper('scm_xlsx');
            scm_download_xlsx($filename, $this->load->view('kaur/export_ba_klarifikasi', [
                'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
                'pengajuan_list' => $rows,
                'show_negosiasi' => false,
                'role_label' => 'Kaprodi',
            ], true));
            return;
        }
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

        if ($this->input->get('download') !== '1' && $this->input->get('inline') !== '1') {
            $this->load->view('shared/export_preview', [
                'title' => 'Preview Berita Acara Klarifikasi',
                'download_url' => current_url() . '?' . http_build_query(['download' => 1]),
                'iframe_url' => current_url() . '?' . http_build_query(['inline' => 1]),
                'back_url' => site_url('kaprodi/dashboard?tab=riwayat'),
            ]);
            return;
        }

        $filename = 'berita_acara_klarifikasi_' . $pengajuan->kode_pengajuan . '.xlsx';
        if ($this->input->get('download') === '1') {
            $this->load->helper('scm_xlsx');
            scm_download_xlsx($filename, $this->load->view('kaur/export_ba_klarifikasi', [
                'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
                'pengajuan' => $pengajuan,
                'show_negosiasi' => false,
                'role_label' => 'Kaprodi',
            ], true));
            return;
        }
        $this->load->view('kaur/export_ba_klarifikasi', [
            'title' => 'Berita Acara Klarifikasi Pengajuan Barang/Jasa',
            'pengajuan' => $pengajuan,
            'show_negosiasi' => false,
            'role_label' => 'Kaprodi',
        ]);
    }
}
