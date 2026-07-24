<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('kaprodi/Kaprodi_model');
        $this->load->model('Peminjaman_model');
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

    public function index() {
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
        ];
        $kategori = trim((string) $this->input->get('kategori', true));
        if (in_array($kategori, ['barang', 'jasa'], true)) {
            $filters['jenis_pengajuan'] = ucfirst($kategori);
        } elseif ($kategori !== 'gabungan') {
            $kategori = 'gabungan';
        }
        $page = max(1, (int) $this->input->get('page'));
        $requested_tab = trim((string) $this->input->get('tab', true));
        $limit = 8;
        $offset = ($page - 1) * $limit;
        $id_user = $this->session->userdata('id_user');
        $has_filter = (bool) array_filter($filters, static function ($value) {
            return $value !== '' && $value !== null;
        });

        $data['title'] = 'Dashboard Kaprodi';
        $data['filters'] = $filters;
        $data['active_category'] = $kategori;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['active_tab'] = in_array($requested_tab, ['ajukan', 'riwayat'], true)
            ? $requested_tab
            : (($page > 1 || $has_filter) ? 'riwayat' : 'ajukan');
        $data['total_rows'] = $this->Kaprodi_model->count_filtered_by_user($id_user, $filters);
        $data['total_pages'] = max(1, (int) ceil($data['total_rows'] / $limit));
        $data['pengajuan'] = $this->Kaprodi_model->get_filtered_by_user($id_user, $filters, $limit, $offset);
        $data['stats'] = $this->Kaprodi_model->get_stats_by_user($id_user);
        $data['status_options'] = $this->Kaprodi_model->get_status_options();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi(null, $id_user);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread(null, $id_user);
        $this->load->view('kaprodi/dashboard', $data);
    }
}
