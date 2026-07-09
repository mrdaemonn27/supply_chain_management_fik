<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller: Barang
 * Path: application/controllers/admin/Barang.php
 * Khusus untuk ROLE ADMIN mengelola Master Data Aset & Laboratorium
 */
#[\AllowDynamicProperties]
class Barang extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Proteksi: Wajib Login
        if(!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        // Proteksi: WAJIB ROLE ADMIN
        if(strtolower($this->session->userdata('role')) != 'admin') {
            $this->session->set_flashdata('error', 'Akses ditolak! Halaman ini khusus Administrator.');
            redirect('dashboard');
        }

        // Memanggil Model sesuai struktur folder Anda
        $this->load->model('admin/Barang_model', 'Barang_model');
    }

    public function index() {
        $data['barang'] = $this->Barang_model->get_all();
        $this->load->view('admin/barang_list', $data);
    }

    public function tambah() {
        $data['ruangan'] = $this->Barang_model->get_all_ruangan();
        $this->load->view('admin/barang_form', $data);
    }

    public function edit($id_aset) {
        $data['ruangan'] = $this->Barang_model->get_all_ruangan();
        $data['aset'] = $this->Barang_model->get_by_id($id_aset);
        
        if(!$data['aset']) {
            $this->session->set_flashdata('error', 'Data barang tidak ditemukan!');
            redirect('admin/barang');
        }
        
        $this->load->view('admin/barang_form', $data);
    }

    public function simpan() {
        $id_aset = $this->input->post('id_aset'); 
        
        $data = [
            'kode_aset'       => $this->input->post('kode_aset'),
            'nama_aset'       => $this->input->post('nama_aset'),
            'id_ruangan'      => $this->input->post('id_ruangan'),
            'jumlah_total'    => $this->input->post('jumlah_total'),
            // PERBAIKAN STOK: Memastikan jumlah_tersedia ikut diperbarui baik saat Tambah maupun Edit
            'jumlah_tersedia' => $this->input->post('jumlah_total'),
            'kondisi'         => $this->input->post('kondisi')
        ];

        // Menyimpan deskripsi jika fieldnya ada di form
        if($this->input->post('deskripsi') !== null) {
            $data['deskripsi'] = $this->input->post('deskripsi');
        }

        // LOGIKA UPLOAD GAMBAR DINAMIS
        if (!empty($_FILES['gambar']['name'])) {
            // PERBAIKAN PATH: Disamakan dengan yang dipanggil di View User (assets/uploads/barang/)
            $config['upload_path']   = './assets/uploads/barang/'; 
            
            // Buat folder otomatis jika belum ada di dalam project Anda
            if (!is_dir($config['upload_path'])) {
                mkdir($config['upload_path'], 0777, TRUE);
            }

            $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
            $config['max_size']      = 2048; // 2MB
            $config['encrypt_name']  = TRUE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('gambar')) {
                $upload_data = $this->upload->data();
                
                // PERBAIKAN DB: Hanya simpan nama file-nya saja agar URL di view User tidak rusak
                $data['gambar'] = $upload_data['file_name'];
                
                // Jika edit, hapus gambar lama dari folder agar tidak menumpuk di server
                if (!empty($id_aset)) {
                    $old_data = $this->Barang_model->get_by_id($id_aset);
                    if ($old_data && !empty($old_data->gambar) && file_exists('./assets/uploads/barang/' . $old_data->gambar)) {
                        unlink('./assets/uploads/barang/' . $old_data->gambar);
                    }
                }
            } else {
                // Jika error upload
                $this->session->set_flashdata('error', 'Gagal upload foto: ' . $this->upload->display_errors('', ''));
                if (empty($id_aset)) {
                    redirect('admin/barang/tambah');
                } else {
                    redirect('admin/barang/edit/' . $id_aset); 
                }
                return; // Hentikan eksekusi
            }
        }

        // SIMPAN KE DATABASE
        if(empty($id_aset)) {
            $this->Barang_model->insert($data);
            $this->session->set_flashdata('success', 'Barang berhasil ditambahkan!');
        } else {
            $this->Barang_model->update($id_aset, $data);
            $this->session->set_flashdata('success', 'Master data berhasil diperbarui!');
        }

        redirect('admin/barang'); 
    }

    public function hapus($id_aset) {
        $old_data = $this->Barang_model->get_by_id($id_aset);
        
        // PERBAIKAN PATH HAPUS: Hapus file gambar dari server jika ada sesuai path yang benar
        if ($old_data && !empty($old_data->gambar) && file_exists('./assets/uploads/barang/' . $old_data->gambar)) {
            unlink('./assets/uploads/barang/' . $old_data->gambar);
        }
        
        $this->Barang_model->delete($id_aset);
        $this->session->set_flashdata('success', 'Data barang berhasil dihapus!');
        redirect('admin/barang');
    }
}