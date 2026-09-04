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
        $this->load->helper(['loan_progress', 'scm_pagination', 'scm_sort', 'fik_prodi']);
        $this->load->model('Peminjaman_model');
        $this->load->model('User_model');
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
     * Membaca jumlah data per halaman.
     * Harus sama dengan opsi dropdown di view: 10, 25, 50, 100.
     *
     * Tidak memakai scm_read_per_page() agar nilai 25 tidak diubah
     * oleh konfigurasi/helper pagination lain.
     */
    private function read_per_page($value, $default = 10) {
        $allowed = [10, 25, 50, 100];

        if (!is_scalar($value) || $value === '') {
            return $default;
        }

        $per_page = (int) $value;

        return in_array($per_page, $allowed, true)
            ? $per_page
            : $default;
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
        $per_page = $this->read_per_page($this->input->get('per_page', true));
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
        $account = $this->User_model->get_user_by_id($this->session->userdata('id_user'));
        $data['program_studi'] = fik_program_studi();
        $data['jenis_peminjam_options'] = fik_jenis_peminjam();
        $data['user_prodi'] = fik_normalize_prodi($account->prodi ?? null);
        $data['user_jenis'] = fik_normalize_jenis_peminjam($account->jenis_pengguna ?? null);
        
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

        $account = $this->User_model->get_user_by_id($this->session->userdata('id_user'));
        $prodi = fik_normalize_prodi($account->prodi ?? null);
        $jenis_peminjam = fik_normalize_jenis_peminjam($account->jenis_pengguna ?? null);
        if (!$prodi) $prodi = fik_normalize_prodi($this->input->post('prodi', true));
        if (!$jenis_peminjam) $jenis_peminjam = fik_normalize_jenis_peminjam($this->input->post('jenis_pengguna', true));
        if (!$prodi || !$jenis_peminjam) {
            $this->session->set_flashdata('error', 'Program studi dan status peminjam wajib dipilih agar pengajuan diarahkan ke Kaprodi yang benar.');
            redirect('peminjaman/ajukan/' . (int) $id_aset);
        }
        $kaprodi = $this->User_model->get_kaprodi_by_prodi($prodi);
        if (!$kaprodi) {
            $this->session->set_flashdata('error', 'Akun Kaprodi untuk ' . $prodi . ' belum tersedia. Hubungi administrator.');
            redirect('peminjaman/ajukan/' . (int) $id_aset);
        }
        if ($account && (($account->prodi ?? null) !== $prodi || ($account->jenis_pengguna ?? null) !== $jenis_peminjam)) {
            $this->User_model->update_user($account->id_user, [
                'prodi' => $prodi,
                'jenis_pengguna' => $jenis_peminjam,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->session->set_userdata(['prodi' => $prodi, 'jenis_pengguna' => $jenis_peminjam]);
        
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
                $nama_peminjam,
                $prodi,
                $jenis_peminjam
            );

            $group_id = uniqid('PJM_');

            // Mapping data penampung database
            $data_peminjaman = [
                'group_id' => $group_id,
                'id_aset' => $id_aset,
                'id_peminjam' => $id_peminjam,
                'id_user' => $this->session->userdata('id_user'),
                'prodi' => $prodi,
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
                null,
                $kaprodi->id_user,
                'Pengajuan peminjaman baru',
                $nama_peminjam . ' dari ' . $prodi . ' mengajukan peminjaman dan menunggu persetujuan Anda.',
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
        // ============================================================
        // 1. Identitas peminjam
        // ============================================================
        $nim_nip = (string) $this->session->userdata('username');
        $peminjam = $this->Peminjaman_model->get_peminjam_by_nim_nip($nim_nip);

        // ============================================================
        // 2. Filter pencarian
        // ============================================================
        $filter_fields = (array) $this->input->get('filter_field', true);
        $filter_values = (array) $this->input->get('filter_value', true);

        $allowed_filter_fields = ['all', 'barang', 'kode', 'status', 'tanggal'];
        $filter_rows = [];

        foreach ($filter_fields as $index => $field) {
            if (count($filter_rows) >= 4) {
                break;
            }

            $field = (string) $field;

            if (!in_array($field, $allowed_filter_fields, true)) {
                continue;
            }

            $filter_rows[] = [
                'field' => $field,
                'value' => trim((string) ($filter_values[$index] ?? '')),
            ];
        }

        // View tetap membutuhkan minimal satu baris filter kosong.
        if (empty($filter_rows)) {
            $filter_rows = [
                ['field' => 'all', 'value' => ''],
            ];
        }

        // Hanya filter yang memiliki nilai yang dikirim ke model.
        $criteria = array_values(array_filter(
            $filter_rows,
            static function ($row) {
                return isset($row['value']) && $row['value'] !== '';
            }
        ));

        // ============================================================
        // 3. Sorting
        // ============================================================
        $requested_sort = (string) $this->input->get('sort_by', true);
        $allowed_sort = ['tanggal', 'barang', 'masa', 'status', 'qr'];

        $history_sort = in_array($requested_sort, $allowed_sort, true)
            ? $requested_sort
            : '';

        $history_dir = strtolower((string) $this->input->get('sort_dir', true)) === 'asc'
            ? 'asc'
            : 'desc';

        $filters = [
            'criteria' => $criteria,
            'sort_by' => $history_sort,
            'sort_dir' => $history_dir,
        ];

        // ============================================================
        // 4. Pagination
        // ============================================================
        // PENTING:
        // Opsi ini HARUS sama dengan dropdown view:
        // 10, 25, 50, 100.
        //
        // Jangan memakai scm_read_per_page() di sini karena jika helper
        // tersebut mempunyai daftar opsi berbeda, pilihan dropdown bisa
        // selalu kembali/fallback ke 10.
        $per_page = $this->read_per_page(
            $this->input->get('per_page', true),
            10
        );

        $requested_page = $this->input->get('page', true);
        $page = is_scalar($requested_page)
            ? max(1, (int) $requested_page)
            : 1;

        $total = 0;
        $total_pages = 1;
        $offset = 0;
        $data['riwayat'] = [];

        if ($peminjam) {
            // Total WAJIB dihitung tanpa LIMIT/OFFSET.
            $total = (int) $this->Peminjaman_model->count_peminjaman_by_peminjam(
                $peminjam->id_peminjam,
                $filters
            );

            // Minimal satu halaman agar pagination tetap mempunyai state valid.
            $total_pages = max(1, (int) ceil($total / $per_page));

            // Bila page pada URL lebih besar dari halaman terakhir,
            // kembalikan ke halaman terakhir.
            $page = min($page, $total_pages);

            // Contoh:
            // page 1, per_page 10 => offset 0
            // page 2, per_page 10 => offset 10
            // page 1, per_page 25 => offset 0
            // page 2, per_page 25 => offset 25
            $offset = ($page - 1) * $per_page;

            // Model harus memakai $limit dan $offset ini pada query SQL.
            $data['riwayat'] = $this->Peminjaman_model->get_peminjaman_by_peminjam(
                $peminjam->id_peminjam,
                $filters,
                $per_page,
                $offset
            );
        } else {
            // Peminjam tidak ditemukan: paksa state pagination kembali valid.
            $page = 1;
        }

        // ============================================================
        // 5. Data untuk view
        // ============================================================
        $data['filter_rows'] = $filter_rows;
        $data['history_sort'] = $history_sort;
        $data['history_dir'] = $history_dir;

        $data['pagination'] = [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => $total_pages,
        ];

        // Gunakan mekanisme notifikasi controller yang sama seperti halaman lain.
        $this->attach_notifikasi($data);

        // ============================================================
        // 6. Render
        // ============================================================
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
