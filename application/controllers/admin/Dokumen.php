<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dokumen extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library(['session', 'upload']);
        $this->load->helper('url');
        $this->load->model('Dokumen_model');
        $this->load->model('Peminjaman_model');
        $this->guard_laboran();
    }

    private function guard_laboran() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth');
        }

        if (!in_array(strtolower((string) $this->session->userdata('role')), ['admin', 'laboran'], true)) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('dashboard');
        }
    }

    public function index() {
        $data['title'] = 'Dokumen Laboran';
        $data['dokumen'] = $this->Dokumen_model->get_all();
        $data['peminjaman'] = $this->Peminjaman_model->get_peminjaman_filtered(['limit' => 50]);
        $this->load->view('admin/dokumen', $data);
    }

    public function simpan() {
        $upload_path = './assets/uploads/dokumen/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $config = [
            'upload_path' => $upload_path,
            'allowed_types' => 'pdf|doc|docx|xls|xlsx|jpg|jpeg|png',
            'max_size' => 5120,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($config);
        if (!$this->upload->do_upload('dokumen')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('admin/dokumen');
        }

        $file = $this->upload->data();
        $this->Dokumen_model->insert([
            'id_peminjaman' => $this->input->post('id_peminjaman') ?: null,
            'judul' => $this->input->post('judul', true),
            'jenis' => $this->input->post('jenis', true) ?: 'Lainnya',
            'nama_file' => $file['file_name'],
            'original_name' => $file['orig_name'],
            'keterangan' => $this->input->post('keterangan', true),
            'uploaded_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata('success', 'Dokumen berhasil diunggah.');
        redirect('admin/dokumen');
    }

    public function hapus($id) {
        $dokumen = $this->Dokumen_model->get_by_id($id);
        if ($dokumen) {
            $path = './assets/uploads/dokumen/' . $dokumen->nama_file;
            if (is_file($path)) {
                unlink($path);
            }
            $this->Dokumen_model->delete($id);
            $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Dokumen tidak ditemukan.');
        }
        redirect('admin/dokumen');
    }
}
