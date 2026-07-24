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
    private $table_peminjaman_detail = 'peminjaman_detail';
    private $table_notifikasi = 'notifikasi_progress';
    private $table_blokir = 'blokir_pengguna';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->ensure_workflow_schema();
    }

    private function ensure_workflow_schema() {
        if ($this->db->table_exists($this->table_peminjaman)) {
            $column = $this->db->query("SHOW COLUMNS FROM `{$this->table_peminjaman}` LIKE 'status'")->row();
            if ($column && (
                stripos((string) $column->Type, 'enum') !== false
                || (string) $column->Default !== 'Menunggu Verifikasi Laboran'
            )) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu Verifikasi Laboran'");
            }

            if (!$this->db->field_exists('id_user', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `id_user` int(11) DEFAULT NULL AFTER `id_peminjam`");
            }

            if (!$this->db->field_exists('foto_pengembalian', $this->table_peminjaman)) {
                $after = $this->db->field_exists('foto_bukti', $this->table_peminjaman) ? ' AFTER `foto_bukti`' : '';
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `foto_pengembalian` varchar(255) DEFAULT NULL{$after}");
            }

            if (!$this->db->field_exists('qr_locked', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `qr_locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `foto_pengembalian`");
            }

            if (!$this->db->field_exists('qr_finalized_at', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `qr_finalized_at` datetime DEFAULT NULL AFTER `qr_locked`");
            }

            if (!$this->db->field_exists('qr_finalized_by', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `qr_finalized_by` int(11) DEFAULT NULL AFTER `qr_finalized_at`");
            }

            $return_condition = $this->db->query("SHOW COLUMNS FROM `{$this->table_peminjaman}` LIKE 'kondisi_saat_kembali'")->row();
            if ($return_condition && stripos((string) $return_condition->Type, 'enum') !== false) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` MODIFY `kondisi_saat_kembali` varchar(50) DEFAULT NULL");
            }
        }

        if ($this->db->table_exists('aset')) {
            $aset_condition = $this->db->query("SHOW COLUMNS FROM `aset` LIKE 'kondisi'")->row();
            if ($aset_condition && stripos((string) $aset_condition->Type, 'enum') !== false) {
                $this->db->query("ALTER TABLE `aset` MODIFY `kondisi` varchar(50) DEFAULT 'Baik'");
            }
        }

        if (!$this->db->table_exists($this->table_notifikasi)) {
            $this->db->query("CREATE TABLE `notifikasi_progress` (
                `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT,
                `recipient_role` varchar(30) DEFAULT NULL,
                `recipient_user_id` int(11) DEFAULT NULL,
                `judul` varchar(160) NOT NULL,
                `pesan` text DEFAULT NULL,
                `link` varchar(255) DEFAULT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_notifikasi`),
                KEY `idx_notif_role` (`recipient_role`),
                KEY `idx_notif_user` (`recipient_user_id`),
                KEY `idx_notif_read` (`is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (!$this->db->table_exists($this->table_blokir)) {
            $this->db->query("CREATE TABLE `blokir_pengguna` (
                `id_blokir` int(11) NOT NULL AUTO_INCREMENT,
                `id_user` int(11) DEFAULT NULL,
                `id_peminjam` int(11) DEFAULT NULL,
                `nim_nip` varchar(50) NOT NULL,
                `nama_peminjam` varchar(150) DEFAULT NULL,
                `alasan` text NOT NULL,
                `tanggal_blokir` date NOT NULL,
                `batas_blokir` date DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'Aktif',
                `dibuka_pada` datetime DEFAULT NULL,
                `dibuka_oleh` int(11) DEFAULT NULL,
                `catatan_buka` text DEFAULT NULL,
                `dibuat_oleh` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id_blokir`),
                KEY `idx_blokir_user` (`id_user`),
                KEY `idx_blokir_peminjam` (`id_peminjam`),
                KEY `idx_blokir_nim` (`nim_nip`),
                KEY `idx_blokir_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
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
        $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
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
            MAX(p.id_user) as id_user,
            MAX(p.tanggal_pinjam) as tanggal_pinjam,
            MAX(p.tanggal_kembali_rencana) as tanggal_kembali_rencana,
            MAX(p.tanggal_kembali_actual) as tanggal_kembali_actual,
            MAX(p.status) as status,
            MAX(p.status_laboran) as status_laboran,
            MAX(p.status_kaur) as status_kaur,
            MAX(p.keperluan) as keperluan,
            MAX(p.foto_pengembalian) as foto_pengembalian,
            MAX(p.qr_locked) as qr_locked,
            MAX(p.qr_finalized_at) as qr_finalized_at,
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
                $this->db->where_in('p.status', ['Sedang Dipinjam', 'Dipinjam']);
                $this->db->where('p.tanggal_kembali_rencana <', date('Y-m-d'));
            } else {
                $this->db->where('p.status', $filters['status']);
            }
        } elseif (!empty($filters['status_in']) && is_array($filters['status_in'])) {
            $this->db->where_in('p.status', $filters['status_in']);
        }

        if (!empty($filters['exclude_status']) && is_array($filters['exclude_status'])) {
            $this->db->where_not_in('p.status', $filters['exclude_status']);
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
        return $this->search_peminjaman(['status' => 'Sedang Dipinjam']);
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

    public function update_group_status($group_id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('group_id', $group_id);
        return $this->db->update($this->table_peminjaman, $data);
    }

    public function get_peminjaman_by_group_id($group_id) {
        $this->db->select('MIN(id_peminjaman) as id_peminjaman');
        $row = $this->db->where('group_id', $group_id)->get($this->table_peminjaman)->row();
        return $row && $row->id_peminjaman ? $this->get_peminjaman_by_id($row->id_peminjaman) : null;
    }

    public function get_pending_laboran() {
        return array_merge(
            $this->search_peminjaman(['status' => 'Menunggu Verifikasi Laboran']),
            $this->search_peminjaman(['status' => 'Menunggu Pengecekan Laboran']),
            $this->search_peminjaman(['status' => 'Menunggu Persetujuan'])
        );
    }

    public function get_pending_kaur() {
        return $this->search_peminjaman(['status' => 'Menunggu ACC Kaur']);
    }

    public function get_qr_payload($group_id) {
        return site_url('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
    }

    public function qr_is_visible($status, $qr_locked = 0) {
        return (int) $qr_locked === 1 && in_array((string) $status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam'], true);
    }

    public function finalize_qr($group_id, $id_user = null) {
        return $this->update_group_status($group_id, [
            'status' => 'Disetujui (Menunggu Pengambilan)',
            'qr_locked' => 1,
            'qr_finalized_at' => date('Y-m-d H:i:s'),
            'qr_finalized_by' => $id_user,
        ]);
    }

    public function create_notifikasi($recipient_role, $recipient_user_id, $judul, $pesan, $link = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return false;
        }

        return $this->db->insert($this->table_notifikasi, [
            'recipient_role' => $recipient_role ?: null,
            'recipient_user_id' => $recipient_user_id ?: null,
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_notifikasi($recipient_role = null, $recipient_user_id = null, $limit = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return [];
        }

        $this->db->from($this->table_notifikasi);
        $this->db->group_start();
        if ($recipient_role) {
            $this->db->where('recipient_role', $recipient_role);
        }
        if ($recipient_user_id) {
            if ($recipient_role) {
                $this->db->or_where('recipient_user_id', $recipient_user_id);
            } else {
                $this->db->where('recipient_user_id', $recipient_user_id);
            }
        }
        $this->db->group_end();
        $this->db->order_by('created_at', 'DESC');
        if ($limit !== null && (int) $limit > 0) {
            $this->db->limit((int) $limit);
        }
        return $this->db->get()->result();
    }

    public function count_notifikasi_unread($recipient_role = null, $recipient_user_id = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return 0;
        }

        $this->db->from($this->table_notifikasi);
        $this->db->where('is_read', 0);
        $this->db->group_start();
        if ($recipient_role) {
            $this->db->where('recipient_role', $recipient_role);
        }
        if ($recipient_user_id) {
            if ($recipient_role) {
                $this->db->or_where('recipient_user_id', $recipient_user_id);
            } else {
                $this->db->where('recipient_user_id', $recipient_user_id);
            }
        }
        $this->db->group_end();
        return $this->db->count_all_results();
    }

    public function get_active_block_by_user($id_user = null, $nim_nip = null) {
        if (!$this->db->table_exists($this->table_blokir)) {
            return null;
        }

        $this->db->from($this->table_blokir);
        $this->db->where('status', 'Aktif');
        $this->db->group_start();
        $this->db->where('batas_blokir IS NULL', null, false);
        $this->db->or_where('batas_blokir >=', date('Y-m-d'));
        $this->db->group_end();
        $this->db->group_start();
        if ($id_user) {
            $this->db->where('id_user', (int) $id_user);
        }
        if ($nim_nip) {
            if ($id_user) {
                $this->db->or_where('nim_nip', $nim_nip);
            } else {
                $this->db->where('nim_nip', $nim_nip);
            }
        }
        $this->db->group_end();
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get()->row();
    }

    public function create_blokir_pengguna($data) {
        if (!$this->db->table_exists($this->table_blokir)) {
            return false;
        }

        $nim_nip = trim((string) ($data['nim_nip'] ?? ''));
        if ($nim_nip === '') {
            return false;
        }

        $user = $this->db->where('nim_nip', $nim_nip)->get('users')->row();
        $peminjam = $this->get_peminjam_by_nim_nip($nim_nip);

        $payload = [
            'id_user' => $user->id_user ?? ($data['id_user'] ?? null),
            'id_peminjam' => $peminjam->id_peminjam ?? ($data['id_peminjam'] ?? null),
            'nim_nip' => $nim_nip,
            'nama_peminjam' => trim((string) ($data['nama_peminjam'] ?? ($user->nama_lengkap ?? ($peminjam->nama_peminjam ?? '')))),
            'alasan' => trim((string) ($data['alasan'] ?? '')),
            'tanggal_blokir' => $data['tanggal_blokir'] ?? date('Y-m-d'),
            'batas_blokir' => !empty($data['batas_blokir']) ? $data['batas_blokir'] : null,
            'status' => 'Aktif',
            'dibuat_oleh' => $data['dibuat_oleh'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($payload['alasan'] === '') {
            return false;
        }

        $this->db->insert($this->table_blokir, $payload);
        return $this->db->insert_id();
    }

    public function buka_blokir_pengguna($id_blokir, $id_user = null, $catatan = null) {
        if (!$this->db->table_exists($this->table_blokir)) {
            return false;
        }

        $this->db->where('id_blokir', (int) $id_blokir);
        return $this->db->update($this->table_blokir, [
            'status' => 'Dibuka',
            'dibuka_pada' => date('Y-m-d H:i:s'),
            'dibuka_oleh' => $id_user,
            'catatan_buka' => $catatan,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_blokir_pengguna($filters = []) {
        if (!$this->db->table_exists($this->table_blokir)) {
            return [];
        }

        $this->db->from($this->table_blokir);

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        if (!empty($filters['tanggal'])) {
            $this->db->where('tanggal_blokir', $filters['tanggal']);
        }
        if (!empty($filters['pencarian'])) {
            $search = trim((string) $filters['pencarian']);
            $this->db->group_start();
            $this->db->like('nama_peminjam', $search);
            $this->db->or_like('nim_nip', $search);
            $this->db->or_like('alasan', $search);
            $this->db->group_end();
        }

        $this->db->order_by("FIELD(status, 'Aktif', 'Dibuka')", '', false);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function count_blokir_aktif() {
        if (!$this->db->table_exists($this->table_blokir)) {
            return 0;
        }

        $this->db->where('status', 'Aktif');
        $this->db->group_start();
        $this->db->where('batas_blokir IS NULL', null, false);
        $this->db->or_where('batas_blokir >=', date('Y-m-d'));
        $this->db->group_end();
        return $this->db->count_all_results($this->table_blokir);
    }

    public function delete_peminjaman($id) {
        $peminjaman = $this->get_peminjaman_by_id($id);
        
        if (!$peminjaman) {
            return false;
        }
        
        $this->db->trans_start();

        if ($this->db->table_exists($this->table_peminjaman_detail)) {
            $this->db->where('id_peminjaman', $id);
            $this->db->delete($this->table_peminjaman_detail);
        }

        $this->db->where('id_peminjaman', $id);
        $this->db->delete($this->table_peminjaman);
        
        if (in_array($peminjaman->status, ['Sedang Dipinjam', 'Dipinjam'], true)) {
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
        
        // Peminjaman aktif
        $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
        $stats['peminjaman_aktif'] = $this->db->count_all_results($this->table_peminjaman);
        
        // Peminjaman selesai (Dikembalikan)
        $this->db->where('status', 'Dikembalikan');
        $stats['peminjaman_selesai'] = $this->db->count_all_results($this->table_peminjaman);
        
        // Peminjaman terlambat
        $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
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
                $this->db->where_in('peminjaman.status', ['Sedang Dipinjam', 'Dipinjam']);
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
                $this->db->where_in('peminjaman.status', ['Sedang Dipinjam', 'Dipinjam']);
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
                    $this->db->where_in('peminjaman.status', ['Sedang Dipinjam', 'Dipinjam']);
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

    public function get_pengajuan_sampai_acc_report($filters = []) {
        $this->db->select('
            p.group_id,
            MIN(p.id_peminjaman) as id_peminjaman,
            MAX(p.tanggal_pinjam) as tanggal_pinjam,
            MAX(p.tanggal_kembali_rencana) as tanggal_kembali_rencana,
            MAX(p.status) as status,
            MAX(p.status_laboran) as status_laboran,
            MAX(p.catatan_laboran) as catatan_laboran,
            MAX(p.tgl_approve_laboran) as tgl_approve_laboran,
            MAX(p.status_kaur) as status_kaur,
            MAX(p.catatan_kaur) as catatan_kaur,
            MAX(p.tgl_approve_kaur) as tgl_approve_kaur,
            MAX(p.kondisi_saat_kembali) as kondisi_saat_kembali,
            MAX(p.keperluan) as keperluan,
            MAX(p.created_at) as created_at,
            MAX(peminjam.nama_peminjam) as nama_peminjam,
            MAX(peminjam.nim_nip) as nim_nip,
            COUNT(p.id_peminjaman) as total_jenis,
            SUM(p.jumlah_pinjam) as total_jumlah,
            GROUP_CONCAT(CONCAT(COALESCE(aset.kode_aset, "-"), " - ", COALESCE(aset.nama_aset, "-"), " (", p.jumlah_pinjam, " unit)") SEPARATOR "; ") as daftar_barang
        ', false);
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('peminjam', 'peminjam.id_peminjam = p.id_peminjam', 'left');
        $this->db->join('aset', 'aset.id_aset = p.id_aset', 'left');
        $this->db->where_in('p.status', [
            'Menunggu Pengecekan Laboran',
            'Menunggu Verifikasi Laboran',
            'Menunggu Persetujuan',
            'Menunggu ACC Kaur',
            'Disetujui (Menunggu Finalisasi QR)',
            'Disetujui (Menunggu Pengambilan)',
            'Sedang Dipinjam',
            'Dipinjam',
            'Dikembalikan',
            'Ditolak',
        ]);

        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
        if (!empty($filters['tanggal'])) {
            $this->db->where('DATE(p.tanggal_pinjam)', $filters['tanggal']);
        }
        if (!empty($filters['pencarian'])) {
            $search = trim($filters['pencarian']);
            $this->db->group_start();
            $this->db->like('peminjam.nama_peminjam', $search);
            $this->db->or_like('peminjam.nim_nip', $search);
            $this->db->or_like('p.keperluan', $search);
            $this->db->or_like('aset.nama_aset', $search);
            $this->db->or_like('aset.kode_aset', $search);
            $this->db->group_end();
        }

        $this->db->group_by('COALESCE(p.group_id, p.id_peminjaman)', false);
        $this->db->order_by('created_at', 'DESC');
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
        $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
        $query = $this->db->get($this->table_peminjaman);
        return $query->num_rows() > 0;
    }

    public function get_statistik_per_status() {
        $result = [];
        $statuses = ['Sedang Dipinjam', 'Dikembalikan', 'Terlambat'];
        
        foreach ($statuses as $status) {
            if ($status == 'Terlambat') {
                $this->db->where_in('status', ['Sedang Dipinjam', 'Dipinjam']);
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
            SUM(CASE WHEN peminjaman.status IN ("Sedang Dipinjam", "Dipinjam") THEN 1 ELSE 0 END) as total_dipinjam,
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
        $this->db->join('users u_kaur', 'u_kaur.id_user = peminjaman.id_approver_kaur', 'left');
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
        if ($role === 'laboran') {
            $this->db->where_in('peminjaman.status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran']);
            $this->db->where('peminjaman.status_laboran', 'Pending');
        } elseif ($role === 'kaur') {
            $this->db->where('peminjaman.status', 'Menunggu ACC Kaur');
            $this->db->where('peminjaman.status_kaur', 'Pending');
        } else {
            $this->db->where_in('peminjaman.status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur', 'Menunggu Persetujuan']);
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
