<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ruangan_model extends CI_Model {

    // Mendefinisikan nama tabel dan primary key agar dinamis
    private $table = 'ruangan';
    private $pk = 'id_ruangan';

    /**
     * 1. READ: Ambil semua data ruangan
     * Menampilkan semua ruangan dari database, diurutkan berdasarkan data terbaru (DESC)
     */
    public function get_all() {
        $this->db->order_by($this->pk, 'DESC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * 2. READ: Ambil data ruangan berdasarkan ID spesifik
     * Berguna untuk menampilkan data di form edit atau detail
     */
    public function get_by_id($id) {
        return $this->db->get_where($this->table, [$this->pk => $id])->row_array();
    }

    /**
     * 3. CREATE: Simpan data ruangan baru ke database
     * Parameter $data berupa array associative yang dikirim dari Controller
     */
    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    /**
     * 4. UPDATE: Perbarui data ruangan yang sudah ada
     * Membutuhkan ID ruangan yang akan diubah dan data barunya
     */
    public function update($id, $data) {
        // Karena kolom 'updated_at' tidak ada di tabel ruangan, 
        // kita hanya mengupdate data yang dikirimkan oleh form
        $this->db->where($this->pk, $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * 5. DELETE: Hapus data ruangan dari database
     * Peringatan: Karena ada 'ON DELETE CASCADE' di tabel aset, 
     * menghapus ruangan akan otomatis menghapus aset yang ada di dalamnya.
     */
    public function delete($id) {
        $this->db->where($this->pk, $id);
        return $this->db->delete($this->table);
    }

    /**
     * 6. VALIDATION UTILITY (Opsional tapi disarankan)
     * Untuk mengecek apakah nama ruangan sudah digunakan (mencegah duplikat saat Edit)
     */
    public function check_duplicate_nama($nama_ruangan, $id_pengecualian = null) {
        $this->db->where('nama_ruangan', $nama_ruangan);
        if ($id_pengecualian !== null) {
            $this->db->where($this->pk . ' !=', $id_pengecualian);
        }
        $query = $this->db->get($this->table);
        return $query->num_rows() > 0;
    }
}