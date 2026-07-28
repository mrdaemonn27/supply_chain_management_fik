<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dokumen_internal extends CI_Controller {
    private $upload_path = './assets/uploads/dokumen_internal/';

    public function __construct() {
        parent::__construct();
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url', 'download']);
        $this->load->model('Dokumen_internal_model');
    }

    private function guard_login() {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk mengakses dokumen internal.');
            redirect('auth');
        }
    }

    private function can_manage() {
        $role = strtolower((string) $this->session->userdata('role'));
        return in_array($role, ['admin', 'laboran', 'kaur'], true);
    }

    private function guard_manage() {
        $this->guard_login();
        if (!$this->can_manage()) {
            $this->session->set_flashdata('error', 'Akses kelola dokumen hanya untuk pengelola internal.');
            redirect('dokumen_internal');
        }
    }

    public function index() {
        $this->guard_login();
        $data['title'] = 'File Manager SOP & Instruksi Kerja';
        $data['dokumen'] = $this->Dokumen_internal_model->get_all(false);
        $data['can_manage'] = $this->can_manage();
        $this->load->view('dokumen_internal/index', $data);
    }

    public function popup() {
        $this->guard_login();
        $data['title'] = 'Dokumen Internal';
        $data['dokumen'] = $this->Dokumen_internal_model->get_active();
        $data['can_manage'] = $this->can_manage();
        $this->load->view('dokumen_internal/popup', $data);
    }

    public function simpan() {
        $this->guard_manage();

        $judul = trim((string) $this->input->post('judul', true));
        $kategori = trim((string) $this->input->post('kategori', true));
        $kategori_options = ['SOP', 'Instruksi Kerja', 'Tata Tertib', 'Lainnya'];
        if ($judul === '' || !in_array($kategori, $kategori_options, true)) {
            $this->session->set_flashdata('error', 'Judul dan kategori dokumen wajib diisi dengan benar.');
            redirect('dokumen_internal');
        }

        if (!is_dir($this->upload_path)) {
            mkdir($this->upload_path, 0777, true);
        }

        $config = [
            'upload_path' => $this->upload_path,
            'allowed_types' => 'pdf|doc|docx',
            'max_size' => 10240,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($config);
        if (!$this->upload->do_upload('dokumen')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('dokumen_internal');
        }

        $file = $this->upload->data();
        $this->Dokumen_internal_model->insert([
            'judul' => $judul,
            'kategori' => $kategori,
            'deskripsi' => $this->input->post('deskripsi', true),
            'nama_file' => $file['file_name'],
            'original_name' => $file['orig_name'],
            'mime_type' => $file['file_type'],
            'file_size' => (int) $file['file_size'] * 1024,
            'is_active' => 1,
            'uploaded_by' => $this->session->userdata('id_user'),
        ]);

        $this->session->set_flashdata('success', 'Dokumen internal berhasil diunggah.');
        redirect('dokumen_internal');
    }

    public function toggle($id) {
        $this->guard_manage();
        $dokumen = $this->Dokumen_internal_model->get_by_id($id);
        if (!$dokumen) {
            show_404();
        }

        $this->Dokumen_internal_model->update($id, ['is_active' => $dokumen->is_active ? 0 : 1]);
        $this->session->set_flashdata('success', 'Status dokumen berhasil diperbarui.');
        redirect('dokumen_internal');
    }

    public function hapus($id) {
        $this->guard_manage();
        $dokumen = $this->Dokumen_internal_model->get_by_id($id);
        if (!$dokumen) {
            show_404();
        }

        $path = $this->document_path($dokumen);
        if ($path) {
            unlink($path);
        }

        $this->Dokumen_internal_model->delete($id);
        $this->session->set_flashdata('success', 'Dokumen internal berhasil dihapus.');
        redirect('dokumen_internal');
    }

    public function lihat($id) {
        $this->guard_login();
        $dokumen = $this->Dokumen_internal_model->get_by_id($id);
        if (!$dokumen || !$dokumen->is_active) {
            show_404();
        }

        $path = $this->document_path($dokumen);
        if (!$path) {
            show_404();
        }

        $mime = $dokumen->mime_type ?: 'application/octet-stream';
        $filename = $this->safe_filename($dokumen->original_name ?: $dokumen->nama_file);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function preview($id) {
        $this->guard_login();
        $dokumen = $this->Dokumen_internal_model->get_by_id($id);
        if (!$dokumen || !$dokumen->is_active) {
            show_404();
        }

        $path = $this->document_path($dokumen);
        if (!$path) {
            show_404();
        }

        $extension = strtolower(pathinfo($dokumen->nama_file, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $this->load->view('dokumen_internal/pdf_preview', [
                'dokumen' => $dokumen,
                'pdf_data_uri' => null,
                'is_pdf' => false,
            ]);
            return;
        }

        $pdf_data_uri = 'data:application/pdf;base64,' . base64_encode(file_get_contents($path));
        $this->load->view('dokumen_internal/pdf_preview', [
            'dokumen' => $dokumen,
            'pdf_data_uri' => $pdf_data_uri,
            'is_pdf' => true,
        ]);
    }

    public function unduh($id) {
        $this->guard_login();
        $dokumen = $this->Dokumen_internal_model->get_by_id($id);
        if (!$dokumen || !$dokumen->is_active) {
            show_404();
        }

        $path = $this->document_path($dokumen);
        if (!$path) {
            show_404();
        }

        force_download($this->safe_filename($dokumen->original_name ?: $dokumen->nama_file), file_get_contents($path));
    }

    private function safe_filename($filename) {
        return str_replace(['"', "\r", "\n"], '', basename((string) $filename));
    }

    private function document_path($dokumen) {
        if (!$dokumen || empty($dokumen->nama_file)) {
            return false;
        }

        $base_path = realpath(FCPATH . 'assets/uploads/dokumen_internal');
        $path = realpath(FCPATH . 'assets/uploads/dokumen_internal/' . basename($dokumen->nama_file));
        if (!$base_path || !$path || strpos($path, $base_path . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            return false;
        }

        return $path;
    }
}
