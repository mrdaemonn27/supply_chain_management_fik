<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property CI_Input $input
 * @property User_model $User_model
 */
#[\AllowDynamicProperties]
class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }

    public function index() {
        // Jika user sudah login sebelumnya, langsung arahkan ke dashboard yang sesuai rolenya
        if($this->session->userdata('logged_in')) {
            if ($this->session->userdata('role') == 'admin' || $this->session->userdata('role') == 'laboran' || $this->session->userdata('role') == 'kaur') {
                redirect('admin/dashboard');
            } else {
                redirect('dashboard');
            }
        }

        $this->load->view('auth/login');
    }

    public function signup() {
        if ($this->session->userdata('logged_in')) {
            if ($this->session->userdata('role') == 'admin' || $this->session->userdata('role') == 'laboran' || $this->session->userdata('role') == 'kaur') {
                redirect('admin/dashboard');
            } else {
                redirect('dashboard');
            }
        }

        $this->load->view('auth/signup');
    }

    public function process_login() {
        // Menangkap inputan dari form login
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        // Cari user berdasarkan nim_nip (karena di DB pakai nim_nip)
        $user = $this->User_model->get_user_by_username($username);

        if ($user && password_verify($password, $user->password)) {
            $session_data = array(
                'id_user'   => $user->id_user,
                'username'  => $user->nim_nip,
                'nama'      => $user->nama_lengkap,
                'role'      => $user->role,
                'logged_in' => TRUE
            );
            $this->session->set_userdata($session_data);

            // CEK ROLE DI SINI AGAR TIDAK SALAH REDIRECT
            if ($user->role == 'admin') {
                redirect('admin/dashboard'); // Arahkan ke folder admin
            } else if ($user->role == 'laboran' || $user->role == 'kaur') {
                redirect('admin/dashboard'); // Arahkan ke folder admin
            } else {
                redirect('dashboard'); // Arahkan user biasa ke halaman peminjaman
            }
        }

        $this->session->set_flashdata('error', 'NIM/NIP atau password tidak cocok.');
        redirect('auth');
    }

    public function process_signup() {
        $nim_nip = trim($this->input->post('nim_nip', TRUE));
        $nama_lengkap = trim($this->input->post('nama_lengkap', TRUE));
        $email = trim($this->input->post('email', TRUE));
        $password = $this->input->post('password', TRUE);
        $password_confirm = $this->input->post('password_confirm', TRUE);

        if ($nim_nip === '' || $nama_lengkap === '' || $email === '' || $password === '' || $password_confirm === '') {
            $this->session->set_flashdata('error', 'Semua field wajib diisi.');
            redirect('auth/signup');
        }

        if ($password !== $password_confirm) {
            $this->session->set_flashdata('error', 'Konfirmasi password tidak sesuai.');
            redirect('auth/signup');
        }

        if ($this->User_model->is_username_exists($nim_nip)) {
            $this->session->set_flashdata('error', 'NIM/NIP sudah terdaftar.');
            redirect('auth/signup');
        }

        if ($this->User_model->is_email_exists($email)) {
            $this->session->set_flashdata('error', 'Email sudah terdaftar.');
            redirect('auth/signup');
        }

        $data = array(
            'nim_nip' => $nim_nip,
            'nama_lengkap' => $nama_lengkap,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s')
        );

        if ($this->User_model->insert_user($data)) {
            $this->session->set_flashdata('success', 'Akun berhasil dibuat. Silakan login.');
            redirect('auth');
        }

        $this->session->set_flashdata('error', 'Gagal membuat akun. Silakan coba lagi.');
        redirect('auth/signup');
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('dashboard'); // UBAH: Diarahkan ke dashboard saat logout
    }
}