<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ruangan extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('admin/Ruangan_model');
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->helper(['url', 'scm_pagination']);
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ruangan khusus Laboran.');
            redirect('dashboard');
        }
    }

    public function index() {
        $fields = (array) $this->input->get('filter_field', true);
        $values = (array) $this->input->get('filter_value', true);
        $filters = [];
        foreach (array_slice($fields, 0, 4) as $index => $field) {
            $value = trim((string) ($values[$index] ?? ''));
            if ($value !== '') $filters[] = ['field' => (string) $field, 'value' => $value];
        }
        $allowed_sort = ['index', 'name', 'description'];
        $sort = in_array($this->input->get('sort', true), $allowed_sort, true) ? $this->input->get('sort', true) : 'index';
        $direction = strtolower((string) $this->input->get('dir', true)) === 'asc' ? 'asc' : 'desc';
        $per_page = scm_read_per_page($this->input->get('per_page', true));
        $total = $this->Ruangan_model->count_all($filters);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min(max(1, (int) $this->input->get('page', true)), $total_pages);

        $data['title']        = 'Manajemen Ruangan';
        $data['page']         = 'index'; 
        $data['ruangan_list'] = $this->Ruangan_model->get_all($per_page, ($page - 1) * $per_page, $filters, $sort, $direction);
        $data['filter_rows'] = $filters;
        $data['sort'] = $sort;
        $data['direction'] = $direction;
        $data['pagination'] = compact('page', 'per_page', 'total', 'total_pages');
        
        $this->load->view('admin/ruangan', $data); 
    }

    public function tambah() {
        $this->form_validation->set_rules('nama_ruangan', 'Nama Ruangan', 'required|is_unique[ruangan.nama_ruangan]', [
            'required'  => '%s wajib diisi!',
            'is_unique' => '%s sudah terdaftar di sistem!'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $data['title'] = 'Tambah Ruangan Baru';
            $data['page']  = 'tambah'; 
            $this->load->view('admin/ruangan', $data); 
        } else {
            $foto = null;
            
            // LOGIKA UPLOAD GAMBAR OTOMATIS (Ala Barang.php)
            if (!empty($_FILES['foto']['name'])) {
                $config['upload_path']   = './assets/uploads/ruangan/'; 
                
                // Buat folder otomatis jika belum ada
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0777, TRUE);
                }

                $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
                $config['max_size']      = 2048; // 2MB
                $config['encrypt_name']  = TRUE; // Acak nama file

                $this->load->library('upload', $config);

                if ($this->upload->do_upload('foto')) {
                    $upload_data = $this->upload->data();
                    $foto = $upload_data['file_name']; // Simpan nama filenya saja ke DB
                } else {
                    $this->session->set_flashdata('error', 'Gagal upload foto: ' . $this->upload->display_errors('',''));
                    redirect('admin/ruangan/tambah');
                    return; // Hentikan eksekusi jika error
                }
            }

            $insert_data = [
                'nama_ruangan' => $this->input->post('nama_ruangan', true),
                'deskripsi'    => $this->input->post('deskripsi', true),
                'foto'         => $foto // Masuk ke Database
            ];

            $this->Ruangan_model->insert($insert_data);
            $this->session->set_flashdata('success', 'Data ruangan berhasil disimpan!');
            redirect('admin/ruangan');
        }
    }

    public function ubah($id) {
        $data['ruangan_detail'] = $this->Ruangan_model->get_by_id($id); 
        if (empty($data['ruangan_detail'])) {
            $this->session->set_flashdata('error', 'Data ruangan tidak ditemukan!');
            redirect('admin/ruangan');
        }

        $nama_input = $this->input->post('nama_ruangan');
        $is_unique = ($nama_input != $data['ruangan_detail']['nama_ruangan']) ? '|is_unique[ruangan.nama_ruangan]' : '';

        $this->form_validation->set_rules('nama_ruangan', 'Nama Ruangan', 'required' . $is_unique, [
            'required'  => '%s wajib diisi!',
            'is_unique' => '%s sudah digunakan!'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $data['title'] = 'Ubah Data Ruangan';
            $data['page']  = 'ubah'; 
            $this->load->view('admin/ruangan', $data); 
        } else {
            $update_data = [
                'nama_ruangan' => $this->input->post('nama_ruangan', true),
                'deskripsi'    => $this->input->post('deskripsi', true)
            ];

            // LOGIKA UPLOAD GAMBAR EDIT
            if (!empty($_FILES['foto']['name'])) {
                $config['upload_path']   = './assets/uploads/ruangan/'; 
                
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0777, TRUE);
                }

                $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
                $config['max_size']      = 2048;
                $config['encrypt_name']  = TRUE;
                
                $this->load->library('upload', $config);
                
                if ($this->upload->do_upload('foto')) {
                    $upload_data = $this->upload->data();
                    
                    // Hapus foto lama jika ada
                    if ($data['ruangan_detail']['foto'] && file_exists('./assets/uploads/ruangan/' . $data['ruangan_detail']['foto'])) {
                        unlink('./assets/uploads/ruangan/' . $data['ruangan_detail']['foto']);
                    }
                    
                    $update_data['foto'] = $upload_data['file_name'];
                } else {
                    $this->session->set_flashdata('error', 'Gagal upload foto: ' . $this->upload->display_errors('',''));
                    redirect('admin/ruangan/ubah/'.$id);
                    return;
                }
            }

            $this->Ruangan_model->update($id, $update_data);
            $this->session->set_flashdata('success', 'Data ruangan berhasil diperbarui!');
            redirect('admin/ruangan');
        }
    }

    public function hapus($id) {
        $ruangan = $this->Ruangan_model->get_by_id($id);
        if (!empty($ruangan)) {
            // Hapus file foto dari server
            if ($ruangan['foto'] && file_exists('./assets/uploads/ruangan/' . $ruangan['foto'])) {
                unlink('./assets/uploads/ruangan/' . $ruangan['foto']);
            }
            $this->Ruangan_model->delete($id);
            $this->session->set_flashdata('success', 'Data ruangan berhasil dihapus!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus! Data tidak ditemukan.');
        }
        redirect('admin/ruangan');
    }
}
