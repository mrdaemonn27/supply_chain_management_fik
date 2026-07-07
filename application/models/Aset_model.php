<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model: Aset_model
 * Mengelola data aset/barang
 * 
 * FIXED VERSION - Memperbaiki method signature yang tidak konsisten
 */
class Aset_model extends CI_Model {

    private $table = 'aset';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Ambil semua aset
     */
    public function get_all_aset() {
        $this->db->select('aset.*, ruangan.nama_ruangan, ruangan.warna, ruangan.icon');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->order_by('aset.id_aset', 'DESC'); // Diubah agar terbaru di atas
        return $this->db->get()->result();
    }

    /**
     * Ambil semua aset dengan urutan tertentu
     */
    public function get_all_aset_ordered($order_by = 'id_aset', $order = 'DESC') {
        $this->db->select('aset.*, ruangan.nama_ruangan, ruangan.warna, ruangan.icon');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->order_by($order_by, $order);
        return $this->db->get()->result();
    }

    /**
     * Ambil barang yang sering dipinjam
     * @param int $limit - jumlah item yang ditampilkan
     * @param int $offset - offset untuk pagination
     */
    public function get_popular_items($limit = 10, $offset = 0) {
        $this->db->select('
            aset.*, 
            ruangan.nama_ruangan, 
            ruangan.warna, 
            ruangan.icon,
            (SELECT COUNT(*) FROM peminjaman WHERE id_aset = aset.id_aset) as total_peminjaman
        ');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.jumlah_total >', 0);
        $this->db->order_by('total_peminjaman', 'DESC');
        $this->db->order_by('aset.id_aset', 'DESC'); 
        $this->db->order_by('aset.nama_aset', 'ASC');
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    /**
     * Count popular items untuk pagination
     */
    public function count_popular_items() {
        $this->db->from($this->table);
        $this->db->where('jumlah_total >', 0);
        return $this->db->count_all_results();
    }

    /**
     * Ambil aset berdasarkan ID
     */
    public function get_aset_by_id($id) {
        $this->db->select('aset.*, ruangan.nama_ruangan, ruangan.warna, ruangan.icon');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.id_aset', $id);
        return $this->db->get()->row();
    }

    /**
     * Ambil aset berdasarkan ruangan
     * 
     * FIXED: Menambahkan parameter $limit dan $exclude_id yang tadinya tidak ada
     * 
     * @param int $id_ruangan - ID ruangan
     * @param int $limit - jumlah maksimal data (default null = semua)
     * @param int $exclude_id - ID aset yang ingin di-exclude (default null)
     * @return array
     */
    public function get_aset_by_ruangan($id_ruangan, $limit = null, $exclude_id = null) {
        $this->db->select('
            aset.*, 
            ruangan.nama_ruangan, 
            ruangan.warna,
            ruangan.icon,
            (SELECT COUNT(*) FROM peminjaman WHERE id_aset = aset.id_aset) as total_peminjaman
        ');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.id_ruangan', $id_ruangan);
        
        // Exclude aset tertentu jika ada (untuk related items)
        if ($exclude_id !== null) {
            $this->db->where('aset.id_aset !=', $exclude_id);
        }
        
        $this->db->order_by('aset.nama_aset', 'ASC');
        
        // Limit jika ada
        if ($limit !== null) {
            $this->db->limit($limit);
        }
        
        return $this->db->get()->result();
    }

    /**
     * Ambil aset terkait (berdasarkan kategori yang sama)
     * 
     * @param int $id_ruangan - ID ruangan
     * @param int $id_aset - ID aset yang sedang dilihat (untuk di-exclude)
     * @param int $limit - jumlah maksimal aset terkait
     * @return array
     */
    public function get_related_aset($id_ruangan, $id_aset, $limit = 4) {
        $this->db->select('aset.*, ruangan.nama_ruangan, ruangan.warna, ruangan.icon');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.id_ruangan', $id_ruangan);
        $this->db->where('aset.id_aset !=', $id_aset);
        $this->db->where('aset.jumlah_total >', 0);
        $this->db->order_by('RAND()');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Search/cari aset
     */
    public function search_aset($keyword) {
        $this->db->select('
            aset.*, 
            ruangan.nama_ruangan, 
            ruangan.warna,
            ruangan.icon,
            (SELECT COUNT(*) FROM peminjaman WHERE id_aset = aset.id_aset) as total_peminjaman
        ');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        
        // Group pencarian dengan OR
        $this->db->group_start();
        $this->db->like('aset.nama_aset', $keyword);
        $this->db->or_like('aset.kode_aset', $keyword);
        $this->db->or_like('ruangan.nama_ruangan', $keyword);
        $this->db->or_like('aset.deskripsi', $keyword);
        $this->db->group_end();
        
        $this->db->order_by('aset.nama_aset', 'ASC');
        $this->db->limit(50); // Tingkatkan limit dari 10 ke 50
        return $this->db->get()->result();
    }

    /**
     * Ambil riwayat peminjaman aset
     */
    public function get_riwayat_peminjaman($id_aset, $limit = 10) {
        $this->db->select('
            peminjaman.*, 
            peminjam.nama_peminjam, 
            peminjam.nim_nip
        ');
        $this->db->from('peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam');
        $this->db->where('peminjaman.id_aset', $id_aset);
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Update jumlah aset tersedia saat peminjaman
     */
    public function update_jumlah_tersedia($id_aset, $jumlah) {
        $this->db->set('jumlah_tersedia', 'jumlah_tersedia - ' . (int)$jumlah, FALSE);
        $this->db->where('id_aset', $id_aset);
        return $this->db->update($this->table);
    }

    /**
     * Kembalikan jumlah aset tersedia saat pengembalian
     */
    public function kembalikan_jumlah_tersedia($id_aset, $jumlah) {
        $this->db->set('jumlah_tersedia', 'jumlah_tersedia + ' . (int)$jumlah, FALSE);
        $this->db->where('id_aset', $id_aset);
        return $this->db->update('aset');
    }

    /**
     * Increment total peminjaman
     */
    public function increment_total_peminjaman($id_aset) {
        $this->db->set('total_peminjaman', 'total_peminjaman + 1', FALSE);
        $this->db->where('id_aset', $id_aset);
        return $this->db->update($this->table);
    }

    /**
     * Update kondisi aset
     */
    public function update_kondisi($id_aset, $kondisi) {
        $this->db->where('id_aset', $id_aset);
        return $this->db->update('aset', ['kondisi' => $kondisi]);
    }

    /**
     * ============== FITUR GAMBAR ==============
     */

    /**
     * Upload gambar aset
     * 
     * @param array $file - $_FILES['gambar']
     * @return string|false - Nama file jika sukses, false jika gagal
     */
    public function upload_gambar($file) {
        // Konfigurasi upload
        $config['upload_path'] = './uploads/aset/';
        $config['allowed_types'] = 'jpg|jpeg|png|gif|webp';
        $config['max_size'] = 2048; // 2MB
        $config['encrypt_name'] = TRUE; // Enkripsi nama file untuk keamanan
        
        // Buat folder jika belum ada
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }
        
        $this->load->library('upload', $config);
        
        if ($this->upload->do_upload('gambar')) {
            $upload_data = $this->upload->data();
            return 'uploads/aset/' . $upload_data['file_name'];
        } else {
            return false;
        }
    }

    /**
     * Hapus gambar aset
     * 
     * @param string $gambar_path - Path gambar yang akan dihapus
     * @return bool
     */
    public function hapus_gambar($gambar_path) {
        if (!empty($gambar_path) && file_exists('./' . $gambar_path)) {
            return unlink('./' . $gambar_path);
        }
        return false;
    }

    /**
     * Update gambar aset
     * 
     * @param int $id_aset - ID aset
     * @param string $gambar_baru - Path gambar baru
     * @return bool
     */
    public function update_gambar($id_aset, $gambar_baru) {
        // Ambil gambar lama
        $aset = $this->get_aset_by_id($id_aset);
        
        // Hapus gambar lama jika ada
        if ($aset && !empty($aset->gambar)) {
            $this->hapus_gambar($aset->gambar);
        }
        
        // Update dengan gambar baru
        $this->db->where('id_aset', $id_aset);
        return $this->db->update($this->table, ['gambar' => $gambar_baru]);
    }

    /**
     * Hapus gambar aset tanpa menghapus data aset
     * 
     * @param int $id_aset - ID aset
     * @return bool
     */
    public function hapus_gambar_aset($id_aset) {
        $aset = $this->get_aset_by_id($id_aset);
        
        if ($aset && !empty($aset->gambar)) {
            $this->hapus_gambar($aset->gambar);
            
            // Set gambar menjadi NULL di database
            $this->db->where('id_aset', $id_aset);
            return $this->db->update($this->table, ['gambar' => null]);
        }
        
        return false;
    }

    /**
     * Ambil semua aset yang memiliki gambar
     */
    public function get_aset_with_gambar() {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.gambar IS NOT NULL');
        $this->db->where('aset.gambar !=', '');
        $this->db->order_by('aset.id_aset', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Ambil aset berdasarkan gambar (untuk galeri)
     */
    public function get_aset_galeri($limit = 12, $offset = 0) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.gambar IS NOT NULL');
        $this->db->where('aset.gambar !=', '');
        $this->db->order_by('aset.id_aset', 'DESC');
        $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    /**
     * Hitung total aset yang memiliki gambar (untuk pagination galeri)
     */
    public function count_aset_galeri() {
        $this->db->from($this->table);
        $this->db->where('gambar IS NOT NULL');
        $this->db->where('gambar !=', '');
        return $this->db->count_all_results();
    }

    /**
     * ============== STATISTIK ==============
     */

    /**
     * Hitung total aset
     */
    public function count_all() {
        return $this->db->count_all($this->table);
    }

    /**
     * Hitung total aset berdasarkan ruangan
     */
    public function count_by_ruangan($id_ruangan) {
        $this->db->where('id_ruangan', $id_ruangan);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Hitung total aset yang tersedia
     */
    public function count_tersedia() {
        $this->db->where('jumlah_tersedia >', 0);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Hitung total aset yang dipinjam
     */
    public function count_dipinjam() {
        $sql = "SELECT COUNT(DISTINCT id_aset) as total FROM peminjaman WHERE status = 'Dipinjam'";
        $result = $this->db->query($sql)->row();
        return $result ? $result->total : 0;
    }

    /**
     * Ambil aset dengan stok menipis (kurang dari 3)
     */
    public function get_stok_menipis($limit = 10) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.jumlah_tersedia <', 3);
        $this->db->where('aset.jumlah_tersedia >', 0);
        $this->db->order_by('aset.jumlah_tersedia', 'ASC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Ambil aset dengan stok habis
     */
    public function get_stok_habis($limit = 10) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from($this->table);
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan');
        $this->db->where('aset.jumlah_tersedia', 0);
        $this->db->order_by('aset.nama_aset', 'ASC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * ============== BULK OPERATIONS ==============
     */

    /**
     * Insert multiple aset sekaligus
     * 
     * @param array $data_array - Array of aset data
     * @return int|false - Jumlah data yang berhasil diinsert atau false
     */
    public function insert_batch($data_array) {
        return $this->db->insert_batch($this->table, $data_array);
    }

    /**
     * Update multiple aset sekaligus
     * 
     * @param array $data_array - Array of aset data dengan id
     * @return int|false - Jumlah data yang berhasil diupdate atau false
     */
    public function update_batch($data_array, $key = 'id_aset') {
        return $this->db->update_batch($this->table, $data_array, $key);
    }

    /**
     * ============== VALIDASI ==============
     */

    /**
     * Cek apakah kode aset sudah ada
     * 
     * @param string $kode_aset - Kode aset yang akan dicek
     * @param int $exclude_id - ID aset yang di-exclude (untuk update)
     * @return bool
     */
    public function is_kode_aset_exists($kode_aset, $exclude_id = null) {
        $this->db->where('kode_aset', $kode_aset);
        
        if ($exclude_id !== null) {
            $this->db->where('id_aset !=', $exclude_id);
        }
        
        $count = $this->db->count_all_results($this->table);
        return $count > 0;
    }

    /**
     * Cek apakah aset bisa dihapus
     * 
     * @param int $id_aset - ID aset yang akan dicek
     * @return bool
     */
    public function can_delete($id_aset) {
        // Cek apakah ada peminjaman aktif
        $this->db->where('id_aset', $id_aset);
        $this->db->where('status', 'Dipinjam');
        $active_peminjaman = $this->db->count_all_results('peminjaman');
        
        return $active_peminjaman === 0;
    }

}