<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model: Peminjaman_model (WORKING SEARCH VERSION - WITH LATEST DATA FIRST)
 * Mengelola data peminjaman dan peminjam
 * FIX: Data diurutkan dari yang TERBARU (DESC) berdasarkan tanggal_pinjam dan id_peminjaman
 */
class Peminjaman_model extends CI_Model {

    private $table_peminjaman = 'peminjaman';
    private $table_peminjam = 'peminjam';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ===================== PEMINJAM =====================
    
    public function insert_peminjam($data) {
        $this->db->insert($this->table_peminjam, $data);
        return $this->db->insert_id();
    }

    public function get_peminjam_by_nim_nip($nim_nip) {
        $this->db->where('nim_nip', $nim_nip);
        return $this->db->get($this->table_peminjam)->row();
    }

    public function get_peminjam_by_id($id_peminjam) {
        $this->db->where('id_peminjam', $id_peminjam);
        return $this->db->get($this->table_peminjam)->row();
    }

    public function update_peminjam($id_peminjam, $data) {
        $this->db->where('id_peminjam', $id_peminjam);
        return $this->db->update($this->table_peminjam, $data);
    }

    public function get_all_peminjam() {
        $this->db->order_by('nama_peminjam', 'ASC');
        return $this->db->get($this->table_peminjam)->result();
    }

    public function delete_peminjam($id_peminjam) {
        $this->db->where('id_peminjam', $id_peminjam);
        $this->db->where('status', 'Dipinjam');
        $active_loans = $this->db->count_all_results($this->table_peminjaman);
        
        if ($active_loans > 0) {
            return false;
        }
        
        $this->db->where('id_peminjam', $id_peminjam);
        return $this->db->delete($this->table_peminjam);
    }

    // ===================== PEMINJAMAN =====================
    
    public function insert_peminjaman($data) {
        $this->db->insert($this->table_peminjaman, $data);
        return $this->db->insert_id();
    }

    /**
     * SEARCH PEMINJAMAN - DENGAN URUTAN TERBARU DI ATAS
     * FIX: Menambahkan ORDER BY yang benar untuk menampilkan data terbaru di paling atas
     */
    public function search_peminjaman($filters = []) {
        // Select dengan GROUP BY group_id
        $this->db->select('
            p.group_id,
            MIN(p.id_peminjaman) as id_peminjaman,
            MAX(p.tanggal_pinjam) as tanggal_pinjam,
            MAX(p.tanggal_kembali_rencana) as tanggal_kembali_rencana,
            MAX(p.tanggal_kembali_actual) as tanggal_kembali_actual,
            MAX(p.status) as status,
            MAX(p.keperluan) as keperluan,
            MAX(p.created_at) as created_at,
            MAX(peminjam.nama_peminjam) as nama_peminjam,
            MAX(peminjam.nim_nip) as nim_nip,
            COUNT(p.id_peminjaman) as total_jenis,
            SUM(p.jumlah_pinjam) as total_jumlah
        ');
        
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('peminjam', 'peminjam.id_peminjam = p.id_peminjam', 'left');
        
        // Filter status
        if (!empty($filters['status'])) {
            if ($filters['status'] == 'Terlambat') {
                $this->db->where('p.status', 'Dipinjam');
                $this->db->where('p.tanggal_kembali_rencana <', date('Y-m-d'));
            } else {
                $this->db->where('p.status', $filters['status']);
            }
        }
        
        // Filter pencarian
        if (!empty($filters['pencarian']) && trim($filters['pencarian']) != '') {
            $search = '%' . trim($filters['pencarian']) . '%';
            $this->db->group_start();
            $this->db->like('peminjam.nama_peminjam', $search, 'both');
            $this->db->or_like('peminjam.nim_nip', $search, 'both');
            $this->db->or_like('p.keperluan', $search, 'both');
            $this->db->group_end();
        }
        
        // Filter tanggal
        if (!empty($filters['tanggal'])) {
            $this->db->where('DATE(p.tanggal_pinjam)', $filters['tanggal']);
        }
        
        $this->db->group_by('p.group_id');
        
        // ========== INI YANG PALING PENTING ==========
        // Urutkan dari yang TERBARU ke TERLAMA
        $this->db->order_by('MAX(p.tanggal_pinjam)', 'DESC');
        $this->db->order_by('MAX(p.id_peminjaman)', 'DESC');
        // ============================================
        
        $query = $this->db->get();
        $results = $query->result();
        
        // Ambil detail barang untuk setiap group
        foreach ($results as $result) {
            // Ambil SEMUA barang dalam group_id ini
            $this->db->select('
                p.id_peminjaman,
                p.id_aset,
                p.jumlah_pinjam,
                a.nama_aset,
                a.kode_aset,
                r.nama_ruangan
            ');
            $this->db->from($this->table_peminjaman . ' as p');
            $this->db->join('aset a', 'a.id_aset = p.id_aset', 'left');
            $this->db->join('ruangan r', 'r.id_ruangan = a.id_ruangan', 'left');
            $this->db->where('p.group_id', $result->group_id);
            $detail = $this->db->get()->result();
            
            $result->detail_barang = $detail;
            $result->kegiatan = $result->keperluan;
        }
        
        return $results;
    }

    /**
     * GET ALL PEMINJAMAN - Menggunakan search dengan filter kosong
     * Sekarang otomatis terurut dari yang TERBARU
     */
    public function get_all_peminjaman() {
        return $this->search_peminjaman([]);
    }

    /**
     * GET PEMINJAMAN AKTIF - Terurut dari terbaru
     */
    public function get_peminjaman_aktif() {
        return $this->search_peminjaman(['status' => 'Dipinjam']);
    }

    /**
     * GET PEMINJAMAN TERLAMBAT - Terurut dari terbaru
     */
    public function get_peminjaman_terlambat() {
        return $this->search_peminjaman(['status' => 'Terlambat']);
    }

    /**
     * GET PEMINJAMAN BY STATUS - Terurut dari terbaru
     */
    public function get_peminjaman_by_status($status) {
        return $this->search_peminjaman(['status' => $status]);
    }

    /**
     * Get peminjaman by ID dengan detail barang
     */
    public function get_peminjaman_by_id($id) {
        // Ambil data peminjaman berdasarkan ID
        $this->db->select('
            p.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip
        ');
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('peminjam', 'peminjam.id_peminjam = p.id_peminjam', 'left');
        $this->db->where('p.id_peminjaman', $id);
        
        $result = $this->db->get()->row();
        
        if ($result) {
            // Ambil SEMUA barang dengan group_id yang sama
            $this->db->select('
                p.id_peminjaman,
                p.id_aset,
                p.jumlah_pinjam,
                p.kondisi_saat_pinjam,
                a.nama_aset,
                a.kode_aset,
                r.nama_ruangan
            ');
            $this->db->from($this->table_peminjaman . ' as p');
            $this->db->join('aset a', 'a.id_aset = p.id_aset', 'left');
            $this->db->join('ruangan r', 'r.id_ruangan = a.id_ruangan', 'left');
            $this->db->where('p.group_id', $result->group_id);
            $detail = $this->db->get()->result();
            
            $result->detail_barang = $detail;
            $result->total_jenis = count($detail);
            $result->total_jumlah = 0;
            foreach ($detail as $d) {
                $result->total_jumlah += $d->jumlah_pinjam;
            }
            $result->kegiatan = $result->keperluan;
        }
        
        return $result;
    }

    /**
     * GET DETAIL BARANG UNTUK SATU PEMINJAMAN (MULTI-ITEM)
     * Untuk mendukung peminjaman multiple aset dalam satu sesi
     */
    public function get_detail_barang_by_peminjaman($id_peminjaman) {
        // Coba ambil berdasarkan id_peminjaman langsung
        $this->db->select('
            pd.*,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        $this->db->from('peminjaman_detail pd');
        $this->db->join('aset', 'aset.id_aset = pd.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('pd.id_peminjaman', $id_peminjaman);
        
        $results = $this->db->get()->result();
        
        // Jika tidak ada di peminjaman_detail, coba ambil dari peminjaman langsung (single item)
        if (empty($results)) {
            $main = $this->db->select('id_aset, jumlah_pinjam, created_at')
                             ->from('peminjaman')
                             ->where('id_peminjaman', $id_peminjaman)
                             ->get()
                             ->row();
            
            if ($main && $main->id_aset) {
                $this->db->select('
                    aset.nama_aset,
                    aset.kode_aset,
                    ruangan.nama_ruangan
                ');
                $this->db->from('aset');
                $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
                $this->db->where('aset.id_aset', $main->id_aset);
                $aset = $this->db->get()->row();
                
                $results[] = (object)[
                    'id_detail' => null,
                    'id_peminjaman' => $id_peminjaman,
                    'id_aset' => $main->id_aset,
                    'jumlah_pinjam' => $main->jumlah_pinjam,
                    'nama_aset' => $aset->nama_aset ?? 'Tidak diketahui',
                    'kode_aset' => $aset->kode_aset ?? '-',
                    'nama_ruangan' => $aset->nama_ruangan ?? '-',
                    'created_at' => $main->created_at
                ];
            }
        }
        
        return $results;
    }

    public function get_peminjaman_by_peminjam($id_peminjam) {
        $this->db->select('
            peminjaman.*,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('peminjaman.id_peminjam', $id_peminjam);
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        return $this->db->get()->result();
    }

    public function get_detail_by_group_id($group_id) {
        $this->db->select('
            p.id_peminjaman,
            p.id_aset,
            p.jumlah_pinjam,
            p.kondisi_saat_pinjam,
            a.nama_aset,
            a.kode_aset,
            r.nama_ruangan
        ');
        $this->db->from('peminjaman p');
        $this->db->join('aset a', 'a.id_aset = p.id_aset', 'left');
        $this->db->join('ruangan r', 'r.id_ruangan = a.id_ruangan', 'left');
        $this->db->where('p.group_id', $group_id);
        return $this->db->get()->result();
    }
    
    public function get_peminjaman_by_aset($id_aset, $limit = 10) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->where('peminjaman.id_aset', $id_aset);
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function update_status($id, $status, $tanggal_kembali_actual = null, $kondisi_saat_kembali = null) {
        $data = ['status' => $status];
        
        if ($tanggal_kembali_actual) {
            $data['tanggal_kembali_actual'] = $tanggal_kembali_actual;
        }
        
        if ($kondisi_saat_kembali) {
            $data['kondisi_saat_kembali'] = $kondisi_saat_kembali;
        }
        
        $this->db->where('id_peminjaman', $id);
        return $this->db->update($this->table_peminjaman, $data);
    }

    public function update_peminjaman($id, $data) {
        $this->db->where('id_peminjaman', $id);
        return $this->db->update($this->table_peminjaman, $data);
    }

    public function delete_peminjaman($id) {
        $peminjaman = $this->get_peminjaman_by_id($id);
        
        if (!$peminjaman) {
            return false;
        }
        
        $this->db->trans_start();
        
        $this->db->where('id_peminjaman', $id);
        $this->db->delete($this->table_peminjaman);
        
        if ($peminjaman->status == 'Dipinjam') {
            $this->load->model('Aset_model');
            $this->Aset_model->kembalikan_jumlah_tersedia($peminjaman->id_aset, $peminjaman->jumlah_pinjam);
        }
        
        $this->db->trans_complete();
        
        return $this->db->trans_status();
    }

    /**
     * GET STATISTIK
     */
    public function get_statistik() {
        $stats = [];
        
        // Total peminjaman
        $stats['total_peminjaman'] = $this->db->count_all($this->table_peminjaman);
        
        // Peminjaman aktif (status Dipinjam dan belum lewat tanggal kembali)
        $this->db->where('status', 'Dipinjam');
        $stats['peminjaman_aktif'] = $this->db->count_all_results($this->table_peminjaman);
        
        // Peminjaman selesai (Dikembalikan)
        $this->db->where('status', 'Dikembalikan');
        $stats['peminjaman_selesai'] = $this->db->count_all_results($this->table_peminjaman);
        
        // Peminjaman terlambat (status Dipinjam dan tanggal kembali rencana < hari ini)
        $this->db->where('status', 'Dipinjam');
        $this->db->where('tanggal_kembali_rencana <', date('Y-m-d'));
        $stats['peminjaman_terlambat'] = $this->db->count_all_results($this->table_peminjaman);
        
        // Total peminjam
        $stats['total_peminjam'] = $this->db->count_all($this->table_peminjam);
        
        return $stats;
    }

    public function get_peminjaman_hari_ini() {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            aset.nama_aset
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->where('DATE(peminjaman.created_at)', date('Y-m-d'));
        $this->db->order_by('peminjaman.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_peminjaman_by_date_range($start_date, $end_date) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            aset.nama_aset,
            aset.kode_aset
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->where('peminjaman.tanggal_pinjam >=', $start_date);
        $this->db->where('peminjaman.tanggal_pinjam <=', $end_date);
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        return $this->db->get()->result();
    }

    public function get_peminjaman_by_keyword($keyword) {
        if (empty($keyword)) {
            return $this->get_all_peminjaman();
        }
        return $this->search_peminjaman(['pencarian' => $keyword]);
    }

    public function get_peminjaman_filtered($params = []) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        
        if (!empty($params['status'])) {
            if ($params['status'] == 'Terlambat') {
                $this->db->where('peminjaman.status', 'Dipinjam');
                $this->db->where('peminjaman.tanggal_kembali_rencana <', date('Y-m-d'));
            } else {
                $this->db->where('peminjaman.status', $params['status']);
            }
        }
        
        if (!empty($params['dari_tanggal'])) {
            $this->db->where('peminjaman.tanggal_pinjam >=', $params['dari_tanggal']);
        }
        
        if (!empty($params['sampai_tanggal'])) {
            $this->db->where('peminjaman.tanggal_pinjam <=', $params['sampai_tanggal']);
        }
        
        if (!empty($params['ruangan'])) {
            $this->db->where('ruangan.id_ruangan', $params['ruangan']);
        }
        
        if (!empty($params['search'])) {
            $search_term = '%' . trim($params['search']) . '%';
            $this->db->group_start();
            $this->db->like('peminjam.nama_peminjam', $search_term, 'both');
            $this->db->or_like('peminjam.nim_nip', $search_term, 'both');
            $this->db->or_like('aset.nama_aset', $search_term, 'both');
            $this->db->or_like('aset.kode_aset', $search_term, 'both');
            $this->db->or_like('peminjaman.keperluan', $search_term, 'both');
            $this->db->group_end();
        }
        
        if (!empty($params['limit'])) {
            $this->db->limit($params['limit']);
        }
        
        if (!empty($params['offset'])) {
            $this->db->offset($params['offset']);
        }
        
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        $this->db->order_by('peminjaman.id_peminjaman', 'DESC');
        
        return $this->db->get()->result();
    }

    public function count_peminjaman_filtered($params = []) {
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        
        if (!empty($params['status'])) {
            if ($params['status'] == 'Terlambat') {
                $this->db->where('peminjaman.status', 'Dipinjam');
                $this->db->where('peminjaman.tanggal_kembali_rencana <', date('Y-m-d'));
            } else {
                $this->db->where('peminjaman.status', $params['status']);
            }
        }
        
        if (!empty($params['dari_tanggal'])) {
            $this->db->where('peminjaman.tanggal_pinjam >=', $params['dari_tanggal']);
        }
        if (!empty($params['sampai_tanggal'])) {
            $this->db->where('peminjaman.tanggal_pinjam <=', $params['sampai_tanggal']);
        }
        
        if (!empty($params['ruangan'])) {
            $this->db->where('ruangan.id_ruangan', $params['ruangan']);
        }
        
        if (!empty($params['search'])) {
            $search_term = '%' . trim($params['search']) . '%';
            $this->db->group_start();
            $this->db->like('peminjam.nama_peminjam', $search_term, 'both');
            $this->db->or_like('peminjam.nim_nip', $search_term, 'both');
            $this->db->or_like('aset.nama_aset', $search_term, 'both');
            $this->db->or_like('aset.kode_aset', $search_term, 'both');
            $this->db->or_like('peminjaman.keperluan', $search_term, 'both');
            $this->db->group_end();
        }
        
        return $this->db->count_all_results();
    }

    public function get_export_data($filters = []) {
        $this->db->select('
            peminjaman.id_peminjaman,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            peminjam.email,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan,
            peminjaman.jumlah_pinjam,
            peminjaman.tanggal_pinjam,
            peminjaman.tanggal_kembali_rencana,
            peminjaman.tanggal_kembali_actual,
            peminjaman.keperluan,
            peminjaman.status,
            peminjaman.kondisi_saat_pinjam,
            peminjaman.kondisi_saat_kembali,
            peminjaman.catatan as keterangan,
            peminjaman.created_at as tanggal_dibuat
        ');
        
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        
        if (!empty($filters)) {
            if (!empty($filters['status'])) {
                if ($filters['status'] == 'Terlambat') {
                    $this->db->where('peminjaman.status', 'Dipinjam');
                    $this->db->where('peminjaman.tanggal_kembali_rencana <', date('Y-m-d'));
                } else {
                    $this->db->where('peminjaman.status', $filters['status']);
                }
            }
            
            if (!empty($filters['pencarian'])) {
                $search_term = '%' . trim($filters['pencarian']) . '%';
                $this->db->group_start();
                $this->db->like('peminjam.nama_peminjam', $search_term, 'both');
                $this->db->or_like('peminjam.nim_nip', $search_term, 'both');
                $this->db->or_like('aset.nama_aset', $search_term, 'both');
                $this->db->or_like('aset.kode_aset', $search_term, 'both');
                $this->db->or_like('peminjaman.keperluan', $search_term, 'both');
                $this->db->group_end();
            }
            
            if (!empty($filters['tanggal'])) {
                $this->db->where('DATE(peminjaman.tanggal_pinjam)', $filters['tanggal']);
            }
        }
        
        $this->db->order_by('peminjaman.tanggal_pinjam', 'DESC');
        $this->db->order_by('peminjaman.id_peminjaman', 'DESC');
        
        return $this->db->get()->result();
    }

    public function get_peminjaman_terbaru($limit = 5) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            aset.nama_aset
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->order_by('peminjaman.created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function is_aset_dipinjam($id_aset) {
        $this->db->where('id_aset', $id_aset);
        $this->db->where('status', 'Dipinjam');
        $query = $this->db->get($this->table_peminjaman);
        return $query->num_rows() > 0;
    }

    public function get_statistik_per_status() {
        $result = [];
        $statuses = ['Dipinjam', 'Dikembalikan', 'Terlambat'];
        
        foreach ($statuses as $status) {
            if ($status == 'Terlambat') {
                $this->db->where('status', 'Dipinjam');
                $this->db->where('tanggal_kembali_rencana <', date('Y-m-d'));
                $result[$status] = $this->db->count_all_results($this->table_peminjaman);
            } else {
                $this->db->where('status', $status);
                $result[$status] = $this->db->count_all_results($this->table_peminjaman);
            }
        }
        
        return $result;
    }

    public function get_laporan_bulanan($bulan, $tahun) {
        $this->db->select('
            DATE(peminjaman.tanggal_pinjam) as tanggal,
            COUNT(*) as total_peminjaman,
            SUM(CASE WHEN peminjaman.status = "Dikembalikan" THEN 1 ELSE 0 END) as total_dikembalikan,
            SUM(CASE WHEN peminjaman.status = "Dipinjam" THEN 1 ELSE 0 END) as total_dipinjam,
            SUM(peminjaman.jumlah_pinjam) as total_barang
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->where('MONTH(peminjaman.tanggal_pinjam)', $bulan);
        $this->db->where('YEAR(peminjaman.tanggal_pinjam)', $tahun);
        $this->db->group_by('DATE(peminjaman.tanggal_pinjam)');
        $this->db->order_by('tanggal', 'ASC');
        
        return $this->db->get()->result();
    }

    public function get_laporan_tahunan($tahun) {
        $this->db->select('
            MONTH(peminjaman.tanggal_pinjam) as bulan,
            COUNT(*) as total_peminjaman,
            SUM(peminjaman.jumlah_pinjam) as total_barang
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->where('YEAR(peminjaman.tanggal_pinjam)', $tahun);
        $this->db->group_by('MONTH(peminjaman.tanggal_pinjam)');
        $this->db->order_by('bulan', 'ASC');
        
        return $this->db->get()->result();
    }

    public function get_top_peminjam($limit = 10) {
        $this->db->select('
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            COUNT(*) as total_peminjaman,
            SUM(peminjaman.jumlah_pinjam) as total_barang
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->group_by('peminjam.id_peminjam');
        $this->db->order_by('total_peminjaman', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_aset_populer($limit = 10) {
        $this->db->select('
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan,
            COUNT(*) as total_dipinjam,
            SUM(peminjaman.jumlah_pinjam) as total_barang
        ');
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->group_by('aset.id_aset');
        $this->db->order_by('total_dipinjam', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }
    
    public function get_peminjaman_full($id_peminjaman) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            peminjam.jenis as jenis_peminjam,
            u_laboran.nama_lengkap as nama_approver_laboran,
            u_kaur.nama_lengkap as nama_approver_kaur
        ');
        $this->db->from('peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->join('users u_laboran', 'u_laboran.id_user = peminjaman.id_approver_laboran', 'left');
        $this->db->join('users u_kaur', 'u_laboran.id_user = peminjaman.id_approver_kaur', 'left');
        $this->db->where('peminjaman.id_peminjaman', $id_peminjaman);
        return $this->db->get()->row();
    }
 
    /**
     * Ambil semua item aset dalam satu peminjaman
     */
    public function get_items_by_peminjaman($id_peminjaman) {
        $utama = $this->db->select('id_peminjam, tanggal_pinjam, created_at')
                          ->from('peminjaman')
                          ->where('id_peminjaman', $id_peminjaman)
                          ->get()->row();
 
        if (!$utama) return [];
 
        $this->db->select('
            peminjaman.*,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        $this->db->from('peminjaman');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('peminjaman.id_peminjam', $utama->id_peminjam);
        $this->db->where('DATE(peminjaman.created_at)', date('Y-m-d', strtotime($utama->created_at)));
        $this->db->where('peminjaman.tanggal_pinjam', $utama->tanggal_pinjam);
        return $this->db->get()->result();
    }
 
    public function get_pengajuan_pending($role) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            peminjam.jenis as jenis_peminjam
        ');
        $this->db->from('peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
        $this->db->where('peminjaman.status', 'Menunggu Persetujuan');
 
        if ($role === 'laboran') {
            $this->db->where('peminjaman.status_laboran', 'Pending');
        } elseif ($role === 'kaur') {
            $this->db->where('peminjaman.status_kaur', 'Pending');
        }
 
        $this->db->order_by('peminjaman.created_at', 'DESC');
        return $this->db->get()->result();
    }
 
    public function get_pengajuan_by_approval_status($approval_status, $role) {
        $this->db->select('
            peminjaman.*,
            peminjam.nama_peminjam,
            peminjam.nim_nip,
            peminjam.jenis as jenis_peminjam
        ');
        $this->db->from('peminjaman');
        $this->db->join('peminjam', 'peminjam.id_peminjam = peminjaman.id_peminjam', 'left');
 
        if ($role === 'laboran') {
            $this->db->where('peminjaman.status_laboran', $approval_status);
        } elseif ($role === 'kaur') {
            $this->db->where('peminjaman.status_kaur', $approval_status);
        } else {
            $this->db->group_start();
            $this->db->where('peminjaman.status_laboran', $approval_status);
            $this->db->or_where('peminjaman.status_kaur', $approval_status);
            $this->db->group_end();
        }
 
        $this->db->order_by('peminjaman.created_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * GET DETAIL BARANG BERDASARKAN GROUP_ID (UNTUK MULTI-ITEM)
     */
    public function get_detail_barang_by_group($group_id) {
        $this->db->select('
            pd.*,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        $this->db->from('peminjaman_detail pd');
        $this->db->join('peminjaman p', 'p.id_peminjaman = pd.id_peminjaman');
        $this->db->join('aset', 'aset.id_aset = pd.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('p.group_id', $group_id);
        
        return $this->db->get()->result();
    }

    /**
     * GET DETAIL BARANG BERDASARKAN ID PEMINJAMAN (SINGLE)
     */
    public function get_detail_barang_by_peminjaman_id($id_peminjaman) {
        $this->db->select('
            pd.*,
            aset.nama_aset,
            aset.kode_aset,
            ruangan.nama_ruangan
        ');
        $this->db->from('peminjaman_detail pd');
        $this->db->join('aset', 'aset.id_aset = pd.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('pd.id_peminjaman', $id_peminjaman);
        
        $results = $this->db->get()->result();
        
        // Jika tidak ada di peminjaman_detail, ambil dari peminjaman (single item mode)
        if (empty($results)) {
            $main = $this->db->select('id_aset, jumlah_pinjam')
                             ->from('peminjaman')
                             ->where('id_peminjaman', $id_peminjaman)
                             ->get()->row();
            
            if ($main && $main->id_aset) {
                $aset = $this->db->select('aset.nama_aset, aset.kode_aset, ruangan.nama_ruangan')
                                ->from('aset')
                                ->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left')
                                ->where('aset.id_aset', $main->id_aset)
                                ->get()->row();
                
                $results[] = (object)[
                    'id_detail' => null,
                    'id_peminjaman' => $id_peminjaman,
                    'id_aset' => $main->id_aset,
                    'jumlah_pinjam' => $main->jumlah_pinjam,
                    'nama_aset' => $aset->nama_aset ?? 'Tidak diketahui',
                    'kode_aset' => $aset->kode_aset ?? '-',
                    'nama_ruangan' => $aset->nama_ruangan ?? '-'
                ];
            }
        }
        
        return $results;
    }

    // =======================================================
    // FUNGSI UNTUK FITUR KATALOG BARANG (DASHBOARD USER)
    // =======================================================
    public function get_katalog_barang() {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from('aset');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('jumlah_tersedia >', 0);
        $this->db->order_by('nama_aset', 'ASC');
        
        return $this->db->get()->result();
    }

    // =======================================================
    // FUNGSI TAMBAHAN UNTUK FORM PENGAJUAN PEMINJAMAN
    // =======================================================
    public function get_aset_by_id($id_aset) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from('aset');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('id_aset', $id_aset);
        return $this->db->get()->row();
    }

    public function get_or_create_peminjam($nim_nip, $nama_lengkap) {
        $peminjam = $this->db->get_where($this->table_peminjam, ['nim_nip' => $nim_nip])->row();
        
        if (!$peminjam) {
            $this->db->insert($this->table_peminjam, [
                'nama_peminjam' => $nama_lengkap,
                'nim_nip'       => $nim_nip,
                'jenis'         => 'Mahasiswa'
            ]);
            return $this->db->insert_id();
        }

        if ($nama_lengkap && $peminjam->nama_peminjam !== $nama_lengkap) {
            $this->db->where('id_peminjam', $peminjam->id_peminjam);
            $this->db->update($this->table_peminjam, ['nama_peminjam' => $nama_lengkap]);
        }

        return $peminjam->id_peminjam;
    }

}