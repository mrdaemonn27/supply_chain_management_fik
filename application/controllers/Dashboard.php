<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Session $session
 * @property Ruangan_model $Ruangan_model
 */
#[\AllowDynamicProperties]
class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Akses ditolak! Silakan login terlebih dahulu.');
            redirect('auth');
        }

        // Memuat Model Ruangan agar bisa mengambil data
        $this->load->model('admin/Ruangan_model');
        $this->load->model('Peminjaman_model');
    }

    public function index() {
        // Mengambil semua data ruangan dari database
        $data['ruangan_list'] = $this->Ruangan_model->get_all();
        $data['notifikasi'] = [];
        $data['unread_notifikasi'] = 0;

        if ($this->session->userdata('logged_in')) {
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

        // PERINTAH INI YANG MENGUBAH TAMPILAN:
        // Memanggil file UI dari folder views/dashboard/index.php beserta datanya
        $this->load->view('dashboard/index', $data);
    }

    public function notifikasi($id_notifikasi = 0) {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', 'Silakan login untuk membuka notifikasi.');
            redirect('auth');
        }

        $role = strtolower((string) $this->session->userdata('role'));
        $recipient_role = null;
        $recipient_user_id = null;

        if (in_array($role, ['admin', 'laboran'], true)) {
            $recipient_role = 'laboran';
        } elseif ($role === 'kaur') {
            $recipient_role = 'kaur';
        } else {
            $recipient_user_id = (int) $this->session->userdata('id_user');
        }

        $notification = $this->Peminjaman_model->get_notifikasi_by_id(
            (int) $id_notifikasi,
            $recipient_role,
            $recipient_user_id
        );
        $fallback = $this->notification_fallback($role);

        if (!$notification) {
            $this->session->set_flashdata('error', 'Notifikasi tidak ditemukan atau bukan milik akun ini.');
            redirect($fallback);
        }

        $this->Peminjaman_model->mark_notifikasi_read(
            (int) $id_notifikasi,
            $recipient_role,
            $recipient_user_id
        );
        redirect($this->notification_target($notification, $fallback));
    }

    private function notification_fallback($role) {
        if (in_array($role, ['admin', 'laboran'], true)) {
            return 'admin/dashboard';
        }
        if ($role === 'kaur') {
            return 'kaur/dashboard';
        }
        if ($role === 'kaprodi') {
            return 'kaprodi/dashboard?tab=riwayat';
        }
        return 'peminjaman/riwayat';
    }

    private function notification_target($notification, $fallback) {
        $target = trim((string) ($notification->link ?? ''));
        if ($target === '' || $target === '#') {
            return $fallback;
        }

        $legacy_targets = [
            'kaur/dashboard#approval-peminjaman' => 'kaur/dashboard/peminjaman',
            'kaur/dashboard#pengajuan' => 'kaur/dashboard/pengajuan',
            'admin/dashboard#approval' => 'admin/peminjaman',
        ];
        foreach ($legacy_targets as $legacy => $replacement) {
            if (strpos($target, $legacy) !== false) {
                return $replacement;
            }
        }

        return $target;
    }
}
