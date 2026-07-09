<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Mengambil rekap jumlah data untuk ditampilkan di Dashboard Admin
     * Disesuaikan agar dinamis dengan struktur tabel peminjaman_aset
     */
    public function get_statistik() {
        // 1. Hitung total JENIS barang/aset (berdasarkan jumlah baris master data)
        $total_aset = $this->db->count_all('aset');
        
        // 1.b Hitung total FISIK keseluruhan barang (Sum dari kolom jumlah_total)
        $this->db->select_sum('jumlah_total');
        $query_fisik = $this->db->get('aset')->row();
        $total_aset_fisik = $query_fisik->jumlah_total ?? 0;
        
        // 2. Hitung total ruangan/lab
        $total_ruangan = $this->db->count_all('ruangan');
        
        // 3. Hitung total peminjaman yang sedang aktif (berstatus 'Dipinjam')
        $this->db->where('status', 'Dipinjam');
        $peminjaman_aktif = $this->db->count_all_results('peminjaman');

        // 3.b Hitung pengajuan yang 'Menunggu Persetujuan' (Cocok untuk notifikasi badge)
        $this->db->where('status', 'Menunggu Persetujuan');
        $menunggu_persetujuan = $this->db->count_all_results('peminjaman');
        
        // 4. Hitung total pengguna sistem (Admin, Laboran, Kaur, dll)
        $total_user = $this->db->count_all('users');
        
        // Kembalikan dalam bentuk array agar mudah dipanggil di View
        return [
            'total_aset'           => $total_aset,
            'total_aset_fisik'     => $total_aset_fisik,
            'total_ruangan'        => $total_ruangan,
            'peminjaman_aktif'     => $peminjaman_aktif,
            'menunggu_persetujuan' => $menunggu_persetujuan,
            'total_user'           => $total_user
        ];
    }
}