<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
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

    public function index() {
        $this->render('overview');
    }

    public function pengajuan() {
        $this->render('pengajuan');
    }

    public function negosiasi() {
        $this->render('negosiasi');
    }

    public function approval() {
        $this->render('approval');
    }

    public function peminjaman() {
        $this->render('peminjaman');
    }

    public function anggaran() {
        $this->render('anggaran');
    }

    public function bast() {
        $this->render('bast');
    }

    public function laporan() {
        $this->render('laporan');
    }

    private function render($active_module = 'overview') {
        $id_user = $this->session->userdata('id_user');
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
        ];
        $page = max(1, (int) $this->input->get('page'));
        $limit = 8;
        $offset = ($page - 1) * $limit;

        $titles = [
            'overview' => 'Dashboard Kaur Laboratorium',
            'pengajuan' => 'Pengajuan Kaprodi',
            'negosiasi' => 'Negosiasi Pengadaan',
            'approval' => 'Approval Pengadaan',
            'peminjaman' => 'ACC Peminjaman',
            'anggaran' => 'Alokasi Anggaran',
            'bast' => 'Input BAST',
            'laporan' => 'Laporan Kaur',
        ];

        $data['active_module'] = array_key_exists($active_module, $titles) ? $active_module : 'overview';
        $data['title'] = $titles[$data['active_module']] . ' - Kaur Laboratorium';
        $data['filters'] = $filters;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['total_rows'] = $this->Kaur_model->count_kaprodi_pengajuan($filters);
        $data['total_pages'] = max(1, (int) ceil($data['total_rows'] / $limit));
        $data['pengajuan_kaprodi'] = $this->Kaur_model->get_kaprodi_pengajuan($filters, $limit, $offset);
        $data['stats'] = $this->Kaur_model->get_dashboard_stats();
        $data['anggaran'] = $this->Kaur_model->get_anggaran_summary((int) date('Y'));
        $data['laporan_negosiasi'] = $this->Kaur_model->get_laporan_negosiasi_deal([], 20);
        $data['bast_ready'] = $this->Kaur_model->get_bast_ready_pengajuan(12);
        $data['bast_list'] = $this->Kaur_model->get_bast_list(12);
        $data['peminjaman_pending_kaur'] = $this->Peminjaman_model->get_pending_kaur();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('kaur', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('kaur', null);
        $data['pengajuan'] = $this->Kaur_model->get_all_by_user($id_user);
        $data['approval_bast'] = $this->Kaur_model->get_approval_bast_queue($id_user);
        $data['maintenance'] = $this->Kaur_model->get_laporan_maintenance(12);
        $data['laboratorium'] = $this->Kaur_model->get_laporan_laboratorium();
        $this->load->view('kaur/dashboard', $data);
    }
}
