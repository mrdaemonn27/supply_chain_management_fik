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

    private function read_history_filters() {
        $allowed_fields = ['kode', 'pengajuan', 'jenis', 'kebutuhan', 'status', 'tanggal'];
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

        if (empty($rows)) {
            $rows[] = ['field' => 'kode', 'value' => ''];
        }
        return $rows;
    }

    public function index() {
        $filter_rows = $this->read_history_filters();
        $filters = [
            'q' => trim((string) $this->input->get('q', true)),
            'status' => trim((string) $this->input->get('status', true)),
            'jenis_pengajuan' => trim((string) $this->input->get('jenis_pengajuan', true)),
            'tanggal_dari' => trim((string) $this->input->get('tanggal_dari', true)),
            'tanggal_sampai' => trim((string) $this->input->get('tanggal_sampai', true)),
            'filter_field' => array_column($filter_rows, 'field'),
            'filter_value' => array_column($filter_rows, 'value'),
        ];
        $kategori = trim((string) $this->input->get('kategori', true));
        if (in_array($kategori, ['barang', 'jasa'], true)) {
            $filters['jenis_pengajuan'] = ucfirst($kategori);
        } elseif ($kategori !== 'gabungan') {
            $kategori = 'gabungan';
        }
        $focus_id = max(
            0,
            (int) $this->input->get('focus_id'),
            (int) $this->input->get('id_pengajuan')
        );
        if ($focus_id > 0) {
            $filters['id_pengajuan'] = $focus_id;
        }
        $page = max(1, (int) $this->input->get('page'));
        $requested_tab = trim((string) $this->input->get('tab', true));
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
        $offset = $limit === null ? 0 : (($page - 1) * $limit);
        $id_user = $this->session->userdata('id_user');
        $has_filter = (bool) array_filter($filters, static function ($value, $key) {
            if (in_array($key, ['filter_field', 'filter_value'], true)) {
                return false;
            }
            return $value !== '' && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
        if (!$has_filter) {
            $has_filter = (bool) array_filter(array_column($filter_rows, 'value'), static function ($value) {
                return trim((string) $value) !== '';
            });
        }

        $data['title'] = 'Dashboard Kaprodi';
        $data['filters'] = $filters;
        $data['filter_rows'] = $filter_rows;
        $data['active_category'] = $kategori;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['per_page'] = $per_page;
        $data['active_tab'] = in_array($requested_tab, ['panel', 'ajukan', 'riwayat'], true)
            ? $requested_tab
            : (($page > 1 || $has_filter) ? 'riwayat' : 'panel');
        if ($data['active_tab'] === 'panel') {
            $data['total_rows'] = 0;
            $data['total_pages'] = 1;
            $data['pengajuan'] = [];
        } else {
            $data['total_rows'] = $this->Kaprodi_model->count_filtered_by_user($id_user, $filters);
            $data['total_pages'] = $limit === null ? 1 : max(1, (int) ceil($data['total_rows'] / $limit));
            if ($page > $data['total_pages']) {
                $page = $data['total_pages'];
                $offset = $limit === null ? 0 : (($page - 1) * $limit);
                $data['page'] = $page;
            }
            $data['pengajuan'] = $this->Kaprodi_model->get_filtered_by_user($id_user, $filters, $limit, $offset);
        }
        $data['status_options'] = $this->Kaprodi_model->get_status_options();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi(null, $id_user);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread(null, $id_user);

        $current_year = (int) date('Y');
        $dashboard_year = (int) $this->input->get('tahun');
        if ($dashboard_year < 2000 || $dashboard_year > $current_year + 1) {
            $dashboard_year = $current_year;
        }
        $data['dashboard_years'] = $this->Kaprodi_model->get_dashboard_years_by_user($id_user);
        if (!in_array($dashboard_year, $data['dashboard_years'], true)) {
            $data['dashboard_years'][] = $dashboard_year;
            rsort($data['dashboard_years']);
        }
        $data['dashboard_year'] = $dashboard_year;
        $data['dashboard_stats'] = $this->Kaprodi_model->get_dashboard_stats_by_user($id_user);
        $data['stats'] = $data['dashboard_stats'];
        $data['dashboard_monthly_submissions'] = $this->Kaprodi_model->get_dashboard_monthly_submissions_by_user($id_user, $dashboard_year);
        $data['dashboard_monthly_values'] = $this->Kaprodi_model->get_dashboard_monthly_values_by_user($id_user, $dashboard_year);
        $data['dashboard_status_breakdown'] = $this->Kaprodi_model->get_dashboard_status_breakdown_by_user($id_user, $dashboard_year);
        $data['dashboard_type_breakdown'] = $this->Kaprodi_model->get_dashboard_type_breakdown_by_user($id_user, $dashboard_year);
        $activity = $this->Kaprodi_model->get_dashboard_recent_activity_by_user($id_user, 12);
        foreach ((array) $data['notifikasi'] as $notification) {
            $activity[] = [
                'title' => (string) $notification->judul,
                'description' => (string) $notification->pesan,
                'time' => (string) $notification->created_at,
                'status' => empty($notification->is_read) ? 'Baru' : 'Sudah dibaca',
                'icon' => 'bi-bell',
                'link' => site_url('kaprodi/dashboard/notifikasi/' . (int) $notification->id_notifikasi),
            ];
        }
        usort($activity, static function ($left, $right) {
            return strtotime((string) ($right['time'] ?? '')) <=> strtotime((string) ($left['time'] ?? ''));
        });
        $data['dashboard_activity'] = array_slice($activity, 0, 12);
        $this->load->view('kaprodi/dashboard', $data);
    }

    public function notifikasi($id_notifikasi) {
        $id_user = (int) $this->session->userdata('id_user');
        $notification = $this->Peminjaman_model->get_notifikasi_by_id($id_notifikasi, null, $id_user);
        if (!$notification) {
            $this->session->set_flashdata('error', 'Notifikasi tidak ditemukan atau bukan milik akun ini.');
            redirect('kaprodi/dashboard?tab=riwayat');
        }

        $this->Peminjaman_model->mark_notifikasi_read($id_notifikasi, null, $id_user);
        $focus_id = (int) ($notification->reference_id ?? 0);
        if ($focus_id > 0 && ($notification->reference_type ?? '') === 'kaprodi_pengajuan') {
            redirect('kaprodi/dashboard?tab=riwayat&focus_id=' . $focus_id);
        }

        redirect($notification->link ?: 'kaprodi/dashboard?tab=riwayat');
    }
}
