<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller: Peminjaman
 * Menangani fitur katalog dan pengajuan peminjaman untuk User
 * * @property CI_Session $session
 * @property CI_Input $input
 * @property CI_Upload $upload
 * @property Peminjaman_model $Peminjaman_model
 * @property Aset_model $Aset_model 
 */
#[\AllowDynamicProperties]
class Peminjaman extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // 1. Proteksi Halaman: Wajib login
        if(!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Akses ditolak! Silakan login terlebih dahulu.');
            redirect('auth');
        }
        
        // 2. Load Model yang menangani query ke tabel aset & peminjaman
        $this->load->helper(['loan_progress', 'scm_pagination', 'scm_sort']);
        $this->load->model('Peminjaman_model');
        // DITAMBAHKAN: Load Aset_model untuk mengambil fungsi get_aset_by_ruangan
        $this->load->model('Aset_model'); 
    }

    private function attach_notifikasi(&$data) {
        $role = strtolower((string) $this->session->userdata('role'));
        if (in_array($role, ['admin', 'laboran'], true)) {
            $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
            $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        } elseif ($role === 'kaur') {
            $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('kaur', null);
            $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('kaur', null);
        } else {
            $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi(null, $this->session->userdata('id_user'));
            $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread(null, $this->session->userdata('id_user'));
        }
    }

    private function get_active_block() {
        if (strtolower((string) $this->session->userdata('role')) !== 'user') {
            return null;
        }

        return $this->Peminjaman_model->get_active_block_by_user(
            $this->session->userdata('id_user'),
            $this->session->userdata('username')
        );
    }

    private function flash_block_message($block) {
        $until = !empty($block->batas_blokir) ? ' sampai ' . date('d/m/Y', strtotime($block->batas_blokir)) : ' tanpa batas waktu';
        $this->session->set_flashdata('error', 'Akun Anda sedang diblokir' . $until . '. Alasan: ' . ($block->alasan ?? '-'));
    }

    /**
     * Halaman Default (Katalog Barang)
     * URL: http://localhost/supply_chain_management_fik/index.php/peminjaman
     */
    public function index() {
        $id_ruangan = $this->input->get('id_ruangan', true);
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $filters = [];
        foreach (array_slice($fields, 0, 4) as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));
            if ($value !== '') $filters[] = ['field' => (string) $field, 'value' => $value];
        }
        $per_page = scm_read_per_page($this->input->get('per_page', true));
        $total = $this->Peminjaman_model->count_katalog_barang($filters, $id_ruangan);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min(max(1, (int) $this->input->get('page', true)), $total_pages);
        $data['barang'] = $this->Peminjaman_model->get_katalog_barang($filters, $per_page, ($page - 1) * $per_page, $id_ruangan);
        $data['filter_rows'] = $filters;
        $data['pagination'] = compact('page', 'per_page', 'total', 'total_pages');
        
        if ($id_ruangan) {
            $data['ruangan_aktif'] = $this->db->get_where('ruangan', ['id_ruangan' => $id_ruangan])->row();
        } else {
            $data['ruangan_aktif'] = null;
        }
        
        // Memanggil file view utama yang sudah Anda ubah namanya menjadi index.php
        $this->attach_notifikasi($data);
        $this->load->view('peminjaman/index', $data);
    }

    /**
     * Menampilkan Form Pengajuan berdasarkan ID Aset
     * URL: http://localhost/supply_chain_management_fik/index.php/peminjaman/ajukan/1
     */
    public function ajukan($id_aset) {
        $block = $this->get_active_block();
        if ($block) {
            $this->flash_block_message($block);
            redirect('peminjaman');
        }

        $data['aset'] = $this->Peminjaman_model->get_aset_by_id($id_aset);
        
        // Validasi jika ID aset tidak ditemukan
        if(!$data['aset']) {
            $this->session->set_flashdata('error', 'Aset tidak ditemukan!');
            redirect('peminjaman');
        }

        // Tampilkan form pengajuan (views/peminjaman/ajukan.php)
        $this->attach_notifikasi($data);
        $this->load->view('peminjaman/ajukan', $data);
    }

    /**
     * Memproses Data Pengajuan & Upload Foto Kondisi Awal
     */
    public function proses_pengajuan() {
        $block = $this->get_active_block();
        if ($block) {
            $this->flash_block_message($block);
            redirect('peminjaman');
        }

        $id_aset = $this->input->post('id_aset');
        $jumlah_pinjam = (int) $this->input->post('jumlah_pinjam');
        $tanggal_pinjam = $this->input->post('tanggal_pinjam');
        $tanggal_kembali = $this->input->post('tanggal_kembali_rencana');
        
        $aset = $this->Peminjaman_model->get_aset_by_id($id_aset);
        if (!$aset) {
            $this->session->set_flashdata('error', 'Aset tidak ditemukan.');
            redirect('peminjaman');
        }

        // 1. Validasi Keamanan: Stok tidak boleh kurang
        if ($jumlah_pinjam < 1 || $jumlah_pinjam > (int) $aset->jumlah_tersedia) {
            $this->session->set_flashdata('error', 'Gagal: Jumlah pinjam melebihi stok yang tersedia!');
            redirect('peminjaman/ajukan/'.$id_aset);
        }

        // 2. Validasi Keamanan: Tanggal kembali tidak boleh mendahului tanggal pinjam
        if (strtotime($tanggal_kembali) < strtotime($tanggal_pinjam)) {
            $this->session->set_flashdata('error', 'Gagal: Tanggal kembali tidak valid!');
            redirect('peminjaman/ajukan/'.$id_aset);
        }

        // 3. Konfigurasi Upload Foto Bukti ke Folder yang telah dibuat via CMD
        $config['upload_path']   = './assets/uploads/bukti_peminjaman/';
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size']      = 2048; // Max 2MB
        $config['file_name']     = 'AWAL_'.time().'_'.$this->session->userdata('username');
        
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('foto_kondisi')) {
            // Mengembalikan pesan error jika berkas tidak sesuai kriteria
            $this->session->set_flashdata('error', 'Upload Gagal: ' . $this->upload->display_errors('',''));
            redirect('peminjaman/ajukan/'.$id_aset);
        } else {
            $upload_data = $this->upload->data();
            
            // Integrasi data peminjam secara otomatis dari session login
            $nama_peminjam = (strtolower((string) $this->session->userdata('role')) === 'admin')
                ? 'Laboran'
                : $this->session->userdata('nama');

            $id_peminjam = $this->Peminjaman_model->get_or_create_peminjam(
                $this->session->userdata('username'),
                $nama_peminjam
            );

            $group_id = uniqid('PJM_');

            // Mapping data penampung database
            $data_peminjaman = [
                'group_id' => $group_id,
                'id_aset' => $id_aset,
                'id_peminjam' => $id_peminjam,
                'id_user' => $this->session->userdata('id_user'),
                'jumlah_pinjam' => $jumlah_pinjam,
                'tanggal_pinjam' => $tanggal_pinjam,
                'tanggal_kembali_rencana' => $tanggal_kembali,
                'keperluan' => $this->input->post('keperluan'),
                'kondisi_saat_pinjam' => $this->input->post('kondisi_saat_pinjam'),
                'foto_bukti' => $upload_data['file_name'],
                'status' => 'Menunggu ACC Kaprodi',
                'status_kaprodi' => 'Pending',
                'status_laboran' => 'Pending',
                'status_kaur' => 'Pending',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // INSERT dan reservasi stok wajib berhasil dalam satu transaksi.
            $id_peminjaman = $this->Peminjaman_model->create_with_stock_reservation($data_peminjaman);
            if (!$id_peminjaman) {
                if (!empty($upload_data['full_path']) && is_file($upload_data['full_path'])) {
                    @unlink($upload_data['full_path']);
                }
                $this->session->set_flashdata('error', 'Stok baru saja dialokasikan oleh pengajuan lain atau sudah tidak mencukupi. Silakan periksa stok terbaru.');
                redirect('peminjaman/ajukan/'.$id_aset);
            }
            $this->Peminjaman_model->create_notifikasi(
                'kaprodi',
                null,
                'Pengajuan peminjaman baru',
                $nama_peminjam . ' mengajukan peminjaman dan menunggu persetujuan Kaprodi.',
                site_url('kaprodi/peminjaman')
            );
            
            $this->session->set_flashdata('success', 'Berhasil! Pengajuan terkirim, stok sudah direservasi, dan menunggu persetujuan Kaprodi.');
            
            // UBAH redirect INI agar setelah submit form langsung masuk ke halaman riwayat
            redirect('peminjaman/riwayat'); 
        }
    }

    /**
     * Menampilkan Halaman Riwayat Peminjaman User
     * URL: http://localhost/supply_chain_management_fik/index.php/peminjaman/riwayat
     */
    public function riwayat() {
        // Ambil data peminjam berdasarkan session username (nim/nip)
        $nim_nip = $this->session->userdata('username');
        $peminjam = $this->Peminjaman_model->get_peminjam_by_nim_nip($nim_nip);
        
        $filter_fields = (array) $this->input->get('filter_field', true);
        $filter_values = (array) $this->input->get('filter_value', true);
        $filter_rows = [];
        foreach ($filter_fields as $index => $field) {
            if (count($filter_rows) >= 4 || !in_array($field, ['all', 'barang', 'kode', 'status', 'tanggal'], true)) continue;
            $filter_rows[] = ['field' => $field, 'value' => trim((string) ($filter_values[$index] ?? ''))];
        }
        if (empty($filter_rows)) $filter_rows = [['field' => 'all', 'value' => '']];
        $history_sort = in_array($this->input->get('sort_by', true), ['tanggal', 'barang', 'masa', 'status', 'qr'], true) ? $this->input->get('sort_by', true) : '';
        $history_dir = strtolower((string) $this->input->get('sort_dir', true)) === 'asc' ? 'asc' : 'desc';
        $filters = [
            'criteria' => array_values(array_filter($filter_rows, static function ($row) { return $row['value'] !== ''; })),
            'sort_by' => $history_sort,
            'sort_dir' => $history_dir,
        ];
        $per_page = scm_read_per_page($this->input->get('per_page', true));
        $page = max(1, (int) $this->input->get('page', true));

        if ($peminjam) {
            $total = $this->Peminjaman_model->count_peminjaman_by_peminjam($peminjam->id_peminjam, $filters);
            $total_pages = max(1, (int) ceil($total / $per_page));
            $page = min($page, $total_pages);
            $data['riwayat'] = $this->Peminjaman_model->get_peminjaman_by_peminjam($peminjam->id_peminjam, $filters, $per_page, ($page - 1) * $per_page);
        } else {
            $total = 0;
            $total_pages = 1;
            $data['riwayat'] = [];
        }
        $data['filter_rows'] = $filter_rows;
        $data['history_sort'] = $history_sort;
        $data['history_dir'] = $history_dir;
        $data['pagination'] = ['page' => $page, 'per_page' => $per_page, 'total' => $total, 'total_pages' => $total_pages];
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi(null, $this->session->userdata('id_user'));
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread(null, $this->session->userdata('id_user'));
        
        // Tampilkan halaman view riwayat
        $this->load->view('peminjaman/riwayat', $data);
    }

    public function detail_barang($id_aset) {
        $data['aset'] = $this->Aset_model->get_aset_by_id($id_aset);
        if (!$data['aset']) {
            show_404();
        }

        $this->load->view('peminjaman/detail_barang', $data);
    }
}
