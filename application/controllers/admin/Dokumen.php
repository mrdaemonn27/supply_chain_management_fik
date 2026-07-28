<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Dokumen extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url', 'download']);
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
        $judul = trim((string) $this->input->post('judul', true));
        $jenis = trim((string) $this->input->post('jenis', true));
        $jenis_options = ['SOP', 'Bukti', 'Berita Acara', 'Lainnya'];
        $id_peminjaman = (int) $this->input->post('id_peminjaman');
        if ($judul === '' || !in_array($jenis, $jenis_options, true)) {
            $this->session->set_flashdata('error', 'Judul dan jenis dokumen wajib diisi dengan benar.');
            redirect('admin/dokumen');
        }
        if ($id_peminjaman > 0 && !$this->Peminjaman_model->get_peminjaman_by_id($id_peminjaman)) {
            $this->session->set_flashdata('error', 'Relasi peminjaman tidak ditemukan.');
            redirect('admin/dokumen');
        }

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
            'id_peminjaman' => $id_peminjaman > 0 ? $id_peminjaman : null,
            'judul' => $judul,
            'jenis' => $jenis,
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
            $path = $this->document_path($dokumen);
            if ($path) {
                unlink($path);
            }
            $this->Dokumen_model->delete($id);
            $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Dokumen tidak ditemukan.');
        }
        redirect('admin/dokumen');
    }

    /**
     * Streams a document for browser preview. This endpoint must never force
     * a download; the explicit download() action below is used for that.
     */
    public function lihat($id) {
        $this->stream_document($id, false);
    }

    public function download($id) {
        $dokumen = $this->Dokumen_model->get_by_id($id);
        $path = $this->document_path($dokumen);
        if (!$dokumen || !$path) {
            show_404();
        }

        force_download($dokumen->original_name ?: $dokumen->nama_file, file_get_contents($path));
    }

    private function stream_document($id, $force_download = false) {
        $dokumen = $this->Dokumen_model->get_by_id($id);
        $path = $this->document_path($dokumen);
        if (!$dokumen || !$path) {
            show_404();
        }

        $filename = str_replace(['"', "\r", "\n"], '', $dokumen->original_name ?: $dokumen->nama_file);
        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        if (!$mime || $mime === 'application/octet-stream') {
            $extension_mimes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            $mime = $extension_mimes[strtolower(pathinfo($filename, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($force_download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    private function document_path($dokumen) {
        if (!$dokumen || empty($dokumen->nama_file)) {
            return false;
        }

        $base_path = realpath(FCPATH . 'assets/uploads/dokumen');
        $path = realpath(FCPATH . 'assets/uploads/dokumen/' . basename($dokumen->nama_file));
        if (!$base_path || !$path || strpos($path, $base_path . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            return false;
        }

        return $path;
    }
}
