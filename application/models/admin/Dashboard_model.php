<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    private function count_table($table) {
        return $this->db->table_exists($table) ? $this->db->count_all($table) : 0;
    }

    public function get_statistik() {
        $total_aset = $this->count_table('aset');

        $total_aset_fisik = 0;
        if ($this->db->table_exists('aset')) {
            $this->db->select_sum('jumlah_total');
            $query_fisik = $this->db->get('aset')->row();
            $total_aset_fisik = (int) ($query_fisik->jumlah_total ?? 0);
        }

        $total_ruangan = $this->count_table('ruangan');
        $total_user = $this->count_table('users');
        $total_maintenance = $this->count_table('maintenance');
        $total_dokumen = $this->count_table('dokumen_laboran');
        $total_distribusi = $this->count_table('distribusi_barang');

        $peminjaman_aktif = 0;
        $menunggu_persetujuan = 0;
        $peminjaman_selesai = 0;
        $stok_habis = 0;
        $stok_menipis = 0;

        if ($this->db->table_exists('peminjaman')) {
            $this->db->where('status', 'Dipinjam');
            $peminjaman_aktif = $this->db->count_all_results('peminjaman');

            $this->db->where('status', 'Menunggu Persetujuan');
            $menunggu_persetujuan = $this->db->count_all_results('peminjaman');

            $this->db->where('status', 'Dikembalikan');
            $peminjaman_selesai = $this->db->count_all_results('peminjaman');
        }

        if ($this->db->table_exists('aset')) {
            $this->db->where('jumlah_tersedia', 0);
            $stok_habis = $this->db->count_all_results('aset');

            $this->db->where('jumlah_tersedia >', 0);
            $this->db->where('jumlah_tersedia <', 3);
            $stok_menipis = $this->db->count_all_results('aset');
        }

        return [
            'total_aset'           => $total_aset,
            'total_aset_fisik'     => $total_aset_fisik,
            'total_ruangan'        => $total_ruangan,
            'peminjaman_aktif'     => $peminjaman_aktif,
            'menunggu_persetujuan' => $menunggu_persetujuan,
            'peminjaman_selesai'   => $peminjaman_selesai,
            'total_user'           => $total_user,
            'total_maintenance'    => $total_maintenance,
            'total_dokumen'        => $total_dokumen,
            'total_distribusi'     => $total_distribusi,
            'stok_habis'           => $stok_habis,
            'stok_menipis'         => $stok_menipis,
        ];
    }
}