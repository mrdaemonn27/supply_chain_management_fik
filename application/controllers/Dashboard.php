<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 */
class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Proteksi: Cek apakah user sudah memiliki session 'logged_in'
        // Jika belum, tendang kembali ke halaman login
        if(!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Akses ditolak! Silakan login terlebih dahulu.');
            redirect('auth');
        }
    }

    public function index() {
        // Untuk sementara, kita tampilkan teks sederhana untuk memastikan session berjalan
        echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
        echo "<h1>Selamat datang di Dashboard, " . $this->session->userdata('nama') . "!</h1>";
        echo "<p>Anda login sebagai: <b style='color: blue;'>" . $this->session->userdata('role') . "</b></p>";
        echo "<hr style='width: 300px;'>";
        echo "<a href='".base_url('auth/logout')."' style='padding: 10px 20px; background: red; color: white; text-decoration: none; border-radius: 5px;'>Logout</a>";
        echo "</div>";
    }
}