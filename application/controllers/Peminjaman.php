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

    /**
     * Halaman Default (Katalog Barang)
     * URL: http://localhost/supply_chain_management_fik/index.php/peminjaman
     */
    public function index() {
        // DITAMBAHKAN: Tangkap parameter id_ruangan dari URL
        $id_ruangan = $this->input->get('id_ruangan');
        
        if ($id_ruangan) {
            // Jika user mengklik "Masuk Ruangan" (terdapat id_ruangan), filter datanya
            $data['barang'] = $this->Aset_model->get_aset_by_ruangan($id_ruangan);
            
            // Opsional: Ambil detail data ruangan untuk judul di halaman view nanti
            $data['ruangan_aktif'] = $this->db->get_where('ruangan', ['id_ruangan' => $id_ruangan])->row();
        } else {
            // Jika diakses dari navbar "Total Barang" (tanpa parameter), tampilkan semua barang 
            // Tetap menggunakan fungsi asli agar tidak merusak fungsi yang sudah ada
            $data['barang'] = $this->Peminjaman_model->get_katalog_barang();
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
        $id_aset = $this->input->post('id_aset');
        $jumlah_pinjam = $this->input->post('jumlah_pinjam');
        $tanggal_pinjam = $this->input->post('tanggal_pinjam');
        $tanggal_kembali = $this->input->post('tanggal_kembali_rencana');
        
        $aset = $this->Peminjaman_model->get_aset_by_id($id_aset);

        // 1. Validasi Keamanan: Stok tidak boleh kurang
        if ($jumlah_pinjam > $aset->jumlah_tersedia) {
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
                'status' => 'Menunggu Verifikasi Laboran',
                'status_laboran' => 'Pending',
                'status_kaur' => 'Pending',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Kirim array data ke Peminjaman_model
            $this->Peminjaman_model->insert_peminjaman($data_peminjaman);
            $this->Peminjaman_model->create_notifikasi(
                'laboran',
                null,
                'Pengajuan peminjaman baru',
                $nama_peminjam . ' mengajukan peminjaman dan menunggu verifikasi Laboran.',
                site_url('admin/approval')
            );
            
            $this->session->set_flashdata('success', 'Berhasil! Pengajuan terkirim dan menunggu verifikasi Laboran.');
            
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
        
        if ($peminjam) {
            // Jika sudah pernah meminjam / terdaftar di tabel peminjam
            $data['riwayat'] = $this->Peminjaman_model->get_peminjaman_by_peminjam($peminjam->id_peminjam);
        } else {
            // Jika sama sekali belum pernah minjam (tabel masih kosong untuk user ini)
            $data['riwayat'] = [];
        }
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
