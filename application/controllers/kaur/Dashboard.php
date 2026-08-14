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

    private function multi_filter_fields($module) {
        $fields = [
            'pengajuan' => ['kode', 'prodi', 'jenis', 'kebutuhan', 'status', 'tanggal'],
            'negosiasi' => ['kode', 'pengajuan', 'prodi', 'jenis', 'item', 'vendor', 'status_negosiasi'],
            'approval' => ['kode', 'tanggal', 'prodi', 'jenis', 'vendor', 'total_harga', 'status_negosiasi', 'status'],
            'peminjaman' => ['peminjam', 'barang', 'tanggal', 'status_approval'],
            'bast' => ['kode', 'prodi', 'jenis', 'nomor_bast', 'tanggal_bast'],
            'laporan' => ['pengajuan', 'item', 'vendor', 'harga_awal', 'harga_akhir', 'selisih', 'volume', 'garansi', 'catatan'],
        ];
        return $fields[$module] ?? [];
    }

    private function read_multi_filters($module) {
        $allowed_fields = $this->multi_filter_fields($module);
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $rows = [];

        foreach ($fields as $index => $field) {
            if (count($rows) >= 4) {
                break;
            }
            $field = trim((string) $field);
            if (!in_array($field, $allowed_fields, true)) {
                continue;
            }
            $rows[] = [
                'field' => $field,
                'value' => trim((string) ($values[$index] ?? '')),
            ];
        }

        if (empty($rows) && !empty($allowed_fields)) {
            $rows[] = ['field' => $allowed_fields[0], 'value' => ''];
        }

        return $rows;
    }

    private function merge_bast_rows($ready_rows, $bast_rows) {
        $rows = [];
        $bast_by_submission = [];
        foreach ((array) $bast_rows as $bast) {
            if (!empty($bast->id_pengajuan)) {
                $bast_by_submission[(int) $bast->id_pengajuan] = $bast;
            }
        }

        foreach ((array) $ready_rows as $submission) {
            $match = $bast_by_submission[(int) $submission->id_pengajuan] ?? null;
            $submission->nomor_bast = $match->nomor_bast ?? null;
            $submission->tanggal_bast = $match->tanggal_bast ?? null;
            $submission->file_bast = $match->file_bast ?? null;
            $submission->catatan_bast = $match->catatan ?? null;
            unset($bast_by_submission[(int) $submission->id_pengajuan]);
            $rows[] = $submission;
        }

        foreach ($bast_by_submission as $bast) {
            $submission = $this->Kaur_model->get_kaprodi_by_id((int) $bast->id_pengajuan);
            if ($submission) {
                $submission->nomor_bast = $bast->nomor_bast ?? null;
                $submission->tanggal_bast = $bast->tanggal_bast ?? null;
                $submission->file_bast = $bast->file_bast ?? null;
                $submission->catatan_bast = $bast->catatan ?? null;
                $rows[] = $submission;
            } else {
                $bast->kode_pengajuan = $bast->kode_pengajuan ?? ($bast->nomor_bast ?? '-');
                $rows[] = $bast;
            }
        }
        return $rows;
    }

    private function filter_bast_rows($rows, $filter_rows) {
        return array_values(array_filter((array) $rows, static function ($row) use ($filter_rows) {
            foreach ((array) $filter_rows as $filter) {
                $field = (string) ($filter['field'] ?? '');
                $value = trim((string) ($filter['value'] ?? ''));
                if ($value === '') {
                    continue;
                }

                if ($field === 'tanggal_bast') {
                    $date = !empty($row->tanggal_bast) ? date('Y-m-d', strtotime($row->tanggal_bast)) : '';
                    if ($date !== $value) {
                        return false;
                    }
                    continue;
                }

                $haystack = '';
                if ($field === 'kode') {
                    $haystack = (string) ($row->kode_pengajuan ?? '');
                } elseif ($field === 'prodi') {
                    $haystack = trim((string) ($row->nama_prodi ?? '') . ' ' . (string) ($row->nama_pengajuan ?? ''));
                } elseif ($field === 'jenis') {
                    $haystack = (string) ($row->jenis_pengajuan ?? '');
                } elseif ($field === 'nomor_bast') {
                    $haystack = (string) ($row->nomor_bast ?? '');
                }

                if ($haystack === '' || stripos($haystack, $value) === false) {
                    return false;
                }
            }
            return true;
        }));
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
        $allowed_modules = ['overview', 'pengajuan', 'negosiasi', 'approval', 'peminjaman', 'anggaran', 'bast', 'laporan'];
        if (!in_array($active_module, $allowed_modules, true)) {
            $active_module = 'overview';
        }
        $multi_filter_rows = $this->read_multi_filters($active_module);
        $id_user = $this->session->userdata('id_user');
        $current_year = (int) date('Y');
        $dashboard_year = (int) $this->input->get('tahun');
        if ($dashboard_year < 2000 || $dashboard_year > $current_year + 1) {
            $dashboard_year = $current_year;
        }
        $dashboard_years = $this->Kaur_model->get_dashboard_years();
        if (!in_array($dashboard_year, $dashboard_years, true)) {
            $dashboard_years[] = $dashboard_year;
            rsort($dashboard_years, SORT_NUMERIC);
        }
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'vendor' => trim((string) $this->input->get('vendor', true)),
            'status_negosiasi' => trim((string) $this->input->get('status_negosiasi', true)),
            'status_bast' => trim((string) $this->input->get('status_bast', true)),
            'tahun' => trim((string) $this->input->get('tahun', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
            'sort_by' => trim((string) $this->input->get('sort_by', true)),
            'sort_dir' => trim((string) $this->input->get('sort_dir', true)),
            'filter_field' => array_column($multi_filter_rows, 'field'),
            'filter_value' => array_column($multi_filter_rows, 'value'),
        ];
        $page = max(1, (int) $this->input->get('page'));
        $per_page = '8';
        $limit = 8;
        if (in_array($active_module, ['pengajuan', 'negosiasi', 'approval', 'bast', 'laporan'], true)) {
            $requested_per_page = strtolower(trim((string) $this->input->get('per_page', true)));
            if ($requested_per_page === 'all') {
                $per_page = 'all';
                $limit = null;
                $page = 1;
            } else {
                $requested_limit = (int) $requested_per_page;
                $limit = in_array($requested_limit, [10, 25, 50], true) ? $requested_limit : 10;
                $per_page = (string) $limit;
            }
        }
        $offset = $limit === null ? 0 : (($page - 1) * $limit);

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
        $data['filter_rows'] = $multi_filter_rows;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['per_page'] = $per_page;
        $data['total_rows'] = $this->Kaur_model->count_kaprodi_pengajuan($filters);
        $data['total_pages'] = $limit === null ? 1 : max(1, (int) ceil($data['total_rows'] / $limit));
        $data['pengajuan_kaprodi'] = $this->Kaur_model->get_kaprodi_pengajuan($filters, $limit, $offset);
        $data['dashboard_year'] = $dashboard_year;
        $data['dashboard_years'] = $dashboard_years;
        $data['stats'] = $this->Kaur_model->get_dashboard_stats($dashboard_year);
        $data['anggaran'] = $this->Kaur_model->get_anggaran_summary($dashboard_year);
        $data['dashboard_monthly_submissions'] = $this->Kaur_model->get_dashboard_monthly_submissions($dashboard_year);
        $data['dashboard_status_breakdown'] = $this->Kaur_model->get_dashboard_status_breakdown($dashboard_year);
        $data['dashboard_negotiation'] = $this->Kaur_model->get_dashboard_negotiation_summary($dashboard_year);
        $data['dashboard_activity'] = $this->Kaur_model->get_dashboard_recent_activity(12);
        $data['total_rows_laporan'] = $this->Kaur_model->count_laporan_negosiasi_deal($filters);
        $data['total_pages_laporan'] = $limit === null ? 1 : max(1, (int) ceil($data['total_rows_laporan'] / $limit));
        if ($active_module === 'laporan' && $page > $data['total_pages_laporan']) {
            $page = $data['total_pages_laporan'];
            $offset = $limit === null ? 0 : (($page - 1) * $limit);
            $data['page'] = $page;
        }
        $laporan_limit = $active_module === 'laporan' ? $limit : 20;
        $laporan_offset = $active_module === 'laporan' ? $offset : 0;
        $data['laporan_negosiasi'] = $this->Kaur_model->get_laporan_negosiasi_deal($filters, $laporan_limit, $laporan_offset);
        $bast_source_limit = $active_module === 'bast' ? null : 12;
        $data['bast_ready'] = $this->Kaur_model->get_bast_ready_pengajuan($bast_source_limit);
        $data['bast_list'] = $this->Kaur_model->get_bast_list($bast_source_limit);
        if ($active_module === 'bast') {
            $data['bast_rows'] = $this->filter_bast_rows(
                $this->merge_bast_rows($data['bast_ready'], $data['bast_list']),
                $multi_filter_rows
            );
        }
        $loan_filters = $filters;
        if ($active_module === 'peminjaman') {
            $loan_filters['q'] = '';
            $loan_filters['pencarian'] = '';
            $loan_filters['multi_filters'] = $multi_filter_rows;
        }
        $data['peminjaman_pending_kaur'] = $this->Peminjaman_model->get_pending_kaur($loan_filters);
        $return_filters = $loan_filters;
        unset($return_filters['multi_filters']);
        $data['pengembalian_readonly'] = $this->Peminjaman_model->get_pengembalian_readonly($return_filters);
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('kaur', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('kaur', null);
        $data['pengajuan'] = $this->Kaur_model->get_all_by_user($id_user);
        $data['approval_bast'] = $this->Kaur_model->get_approval_bast_queue($id_user);
        $data['maintenance'] = $this->Kaur_model->get_laporan_maintenance(12);
        $data['laboratorium'] = $this->Kaur_model->get_laporan_laboratorium();
        $this->load->view('kaur/dashboard', $data);
    }
}
