<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Distribusi extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'scm_pagination']);
        $this->load->model('Distribusi_model');
        $this->load->model('Aset_model');
        $this->load->model('admin/Ruangan_model');
        $this->load->model('kaur/Kaur_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses distribusi khusus Laboran.');
            redirect('admin/dashboard');
        }
    }

    public function index() {
        // Sekali jalan untuk pengajuan lama yang sudah disetujui sebelum alur
        // otomatis tersedia. Pengajuan baru tersinkron saat Kaur menekan ACC.
        $this->Kaur_model->sync_approved_inventory();

        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $filters = [];
        foreach (array_slice($fields, 0, 4) as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));
            if ($value !== '') $filters[] = ['field' => (string) $field, 'value' => $value];
        }
        $per_page = scm_read_per_page($this->input->get('per_page', true));
        $total = $this->Distribusi_model->count_all($filters);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min(max(1, (int) $this->input->get('page', true)), $total_pages);

        $data['title'] = 'Distribusi Barang';
        $data['distribusi'] = $this->Distribusi_model->get_all($per_page, ($page - 1) * $per_page, $filters);
        $data['filter_rows'] = $filters;
        $data['pagination'] = compact('page', 'per_page', 'total', 'total_pages');
        $data['ruangan'] = $this->Ruangan_model->get_all();
        $data['operator_name'] = (string) ($this->session->userdata('nama_lengkap') ?: $this->session->userdata('nama') ?: $this->session->userdata('username') ?: 'Petugas Laboratorium');
        $this->load->view('admin/distribusi', $data);
    }

    public function cari_aset() {
        $keyword = trim((string) $this->input->get('q', true));
        $rows = $this->Aset_model->search_for_distribution($keyword, 20);
        $results = array_map(static function ($asset) {
            return [
                'id' => (int) $asset->id_aset,
                'name' => (string) $asset->nama_aset,
                'code' => (string) $asset->kode_aset,
                'room_id' => $asset->id_ruangan !== null ? (int) $asset->id_ruangan : null,
                'room' => trim((string) ($asset->nama_ruangan ?? '')) ?: 'Belum ditempatkan',
                'stock' => (int) $asset->jumlah_tersedia,
                'condition' => (string) ($asset->kondisi ?: '-'),
            ];
        }, $rows);

        return $this->json_response(['success' => true, 'results' => $results]);
    }

    public function simpan() {
        $id_aset = (int) $this->input->post('id_aset', true);
        $id_ruangan_asal_input = (int) $this->input->post('id_ruangan_asal', true);
        $id_ruangan_tujuan = (int) $this->input->post('id_ruangan_tujuan', true);
        $jumlah = (int) $this->input->post('jumlah', true);
        $tanggal = (string) ($this->input->post('tanggal_distribusi', true) ?: date('Y-m-d'));
        $jam = (string) ($this->input->post('jam_distribusi', true) ?: date('H:i'));
        $keterangan = trim((string) $this->input->post('keterangan', true));
        $penerima = trim((string) $this->input->post('penerima', true));

        if ($id_aset < 1 || $id_ruangan_tujuan < 1 || $jumlah < 1 || !$this->is_valid_date($tanggal) || !$this->is_valid_time($jam)) {
            $this->session->set_flashdata('error', 'Lengkapi aset, jumlah, lokasi tujuan, tanggal, dan jam distribusi dengan benar.');
            redirect('admin/distribusi');
        }

        $this->db->trans_begin();
        $aset = $this->Aset_model->get_aset_by_id_for_update($id_aset);
        $id_ruangan_asal = $id_ruangan_asal_input > 0 ? $id_ruangan_asal_input : (int) ($aset->id_ruangan ?? 0);
        $asal = $id_ruangan_asal > 0 ? $this->Ruangan_model->get_by_id($id_ruangan_asal) : null;
        $tujuan = $this->Ruangan_model->get_by_id($id_ruangan_tujuan);

        if (!$aset || !$tujuan || ($id_ruangan_asal > 0 && !$asal)) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Aset atau ruangan tujuan tidak ditemukan.');
            redirect('admin/distribusi');
        }

        if ($id_ruangan_asal > 0 && $id_ruangan_asal === $id_ruangan_tujuan) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Ruangan tujuan harus berbeda dari ruangan asal.');
            redirect('admin/distribusi');
        }

        if ($jumlah > (int) $aset->jumlah_tersedia) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Jumlah distribusi melebihi stok aset yang tersedia.');
            redirect('admin/distribusi');
        }

        $saved = $this->Distribusi_model->insert([
            'id_aset' => $id_aset,
            'id_ruangan_asal' => $id_ruangan_asal > 0 ? $id_ruangan_asal : null,
            'id_ruangan_tujuan' => $id_ruangan_tujuan,
            'jumlah' => $jumlah,
            'kondisi_aset' => (string) ($aset->kondisi ?? ''),
            'tanggal_distribusi' => $tanggal,
            'waktu_distribusi' => $tanggal . ' ' . $jam . ':00',
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'penerima' => $penerima !== '' ? $penerima : null,
            'created_by' => (int) $this->session->userdata('id_user'),
        ]);
        $updated = $this->db->where('id_aset', $id_aset)->update('aset', ['id_ruangan' => $id_ruangan_tujuan]);

        if (!$saved || !$updated || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Gagal menyimpan distribusi. Lokasi aset dan riwayat tidak diubah.');
            redirect('admin/distribusi');
        }

        $this->db->trans_commit();
        $this->session->set_flashdata('success', 'Distribusi berhasil dicatat. Lokasi terakhir aset dan riwayat perpindahan telah diperbarui.');
        redirect('admin/distribusi');
    }

    public function detail($id_aset = 0) {
        $asset = $this->Distribusi_model->get_asset_tracking((int) $id_aset);
        if (!$asset) {
            return $this->json_response(['success' => false, 'message' => 'Aset tidak ditemukan.'], 404);
        }

        return $this->json_response([
            'success' => true,
            'asset' => $asset,
            'history' => $this->Distribusi_model->get_tracking_history((int) $id_aset),
        ]);
    }

    private function is_valid_date($value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function is_valid_time($value) {
        $time = DateTime::createFromFormat('H:i', $value);
        return $time && $time->format('H:i') === $value;
    }

    private function json_response($payload, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
