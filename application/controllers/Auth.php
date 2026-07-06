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
        // Memanggil model User_model
        $this->load->model('User_model');
    }

    public function index() {
        // Jika user sudah login sebelumnya, langsung arahkan ke dashboard
        if($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }
        $this->load->view('auth/login');
    }

    public function process_login() {
        // Menangkap inputan dari form login
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        // Cari user berdasarkan nim_nip (karena di DB pakai nim_nip)
        $user = $this->User_model->get_user_by_username($username);

        if($user) {
            // Cek password hash
            if(password_verify($password, $user->password)) {
                
                // Jika password benar, buat session data Sesuai Database
                $session_data = array(
                    'id_user'   => $user->id_user,
                    'username'  => $user->nim_nip,
                    'nama'      => $user->nama_lengkap,
                    'role'      => $user->role,
                    'logged_in' => TRUE
                );
                $this->session->set_userdata($session_data);
                
                redirect('dashboard');
            } else {
                $this->session->set_flashdata('error', 'Password yang Anda masukkan salah!');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('error', 'Username / NIM / NIP tidak ditemukan!');
            redirect('auth');
        }
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('auth');
    }
}