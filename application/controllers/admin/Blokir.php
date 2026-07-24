<?php
defined('BASEPATH') OR exit('No direct script access allowed');

#[\AllowDynamicProperties]
class Blokir extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Peminjaman_model');
        $this->load->model('User_model');
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
        $filters = [
            'status' => $this->input->get('status', true),
            'pencarian' => $this->input->get('q', true),
            'tanggal' => $this->input->get('tanggal', true),
        ];

        $data['title'] = 'Blokir Pengguna';
        $data['filters'] = $filters;
        $data['blokir'] = $this->Peminjaman_model->get_blokir_pengguna($filters);
        $data['peminjam_options'] = $this->Peminjaman_model->get_all_peminjam();
        $data['notifikasi'] = $this->Peminjaman_model->get_notifikasi('laboran', null);
        $data['unread_notifikasi'] = $this->Peminjaman_model->count_notifikasi_unread('laboran', null);
        $this->load->view('admin/blokir_pengguna', $data);
    }

    public function simpan() {
        $nim_nip = trim((string) $this->input->post('nim_nip', true));
        $nama_peminjam = trim((string) $this->input->post('nama_peminjam', true));
        $alasan = trim((string) $this->input->post('alasan', true));
        $tanggal_blokir = $this->input->post('tanggal_blokir', true) ?: date('Y-m-d');
        $batas_blokir = $this->input->post('batas_blokir', true) ?: null;
        $return_to = $this->input->post('return_to', true) === 'admin/pengembalian' ? 'admin/pengembalian' : 'admin/blokir';

        if ($nim_nip === '' || $alasan === '') {
            $this->session->set_flashdata('error', 'NIM/NIP dan alasan blokir wajib diisi.');
            redirect($return_to);
        }

        if ($batas_blokir && strtotime($batas_blokir) < strtotime($tanggal_blokir)) {
            $this->session->set_flashdata('error', 'Batas blokir tidak boleh sebelum tanggal blokir.');
            redirect($return_to);
        }

        $id_blokir = $this->Peminjaman_model->create_blokir_pengguna([
            'nim_nip' => $nim_nip,
            'nama_peminjam' => $nama_peminjam,
            'alasan' => $alasan,
            'tanggal_blokir' => $tanggal_blokir,
            'batas_blokir' => $batas_blokir,
            'dibuat_oleh' => $this->session->userdata('id_user'),
        ]);

        if ($id_blokir) {
            $user = $this->User_model->get_user_by_username($nim_nip);
            if ($user) {
                $this->Peminjaman_model->create_notifikasi(
                    null,
                    $user->id_user,
                    'Akun peminjaman diblokir',
                    'Akun Anda sementara tidak dapat mengajukan peminjaman. Alasan: ' . $alasan,
                    site_url('peminjaman')
                );
            }
        }

        $this->session->set_flashdata($id_blokir ? 'success' : 'error', $id_blokir ? 'Pengguna berhasil diblokir dan histori tersimpan.' : 'Gagal menyimpan blokir pengguna.');
        redirect($return_to);
    }

    public function buka($id_blokir) {
        $row = $this->db->get_where('blokir_pengguna', ['id_blokir' => (int) $id_blokir])->row();
        if (!$row) {
            $this->session->set_flashdata('error', 'Data blokir tidak ditemukan.');
            redirect('admin/blokir');
        }

        $ok = $this->Peminjaman_model->buka_blokir_pengguna(
            $id_blokir,
            $this->session->userdata('id_user'),
            trim((string) $this->input->post('catatan_buka', true))
        );

        if ($ok && !empty($row->id_user)) {
            $this->Peminjaman_model->create_notifikasi(
                null,
                $row->id_user,
                'Blokir akun dibuka',
                'Akun Anda sudah dapat kembali mengajukan peminjaman.',
                site_url('peminjaman')
            );
        }

        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Blokir pengguna berhasil dibuka.' : 'Gagal membuka blokir pengguna.');
        redirect('admin/blokir');
    }
}
