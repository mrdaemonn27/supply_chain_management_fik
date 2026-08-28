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
    private $table_settings = 'peminjaman_settings';
    private $last_expired_count = 0;
    private $workflow_schema_version = 2;

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->model('Aset_model');
        if (!$this->workflow_schema_is_current()) {
            $this->ensure_workflow_schema();
        }
        $this->last_expired_count = $this->expire_overdue_kaprodi_approvals();
    }

    private function workflow_schema_is_current() {
        if (!$this->db->table_exists($this->table_settings)
            || !$this->db->field_exists('schema_version', $this->table_settings)) {
            return false;
        }
        $row = $this->db->select('schema_version')->where('id_setting', 1)->get($this->table_settings)->row();
        return $row && (int) $row->schema_version >= $this->workflow_schema_version;
    }

    private function ensure_workflow_schema() {
        if ($this->db->table_exists($this->table_peminjaman)) {
            $column = $this->db->query("SHOW COLUMNS FROM `{$this->table_peminjaman}` LIKE 'status'")->row();
            if ($column && (
                stripos((string) $column->Type, 'enum') !== false
                || (string) $column->Default !== 'Menunggu ACC Kaprodi'
            )) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu ACC Kaprodi'");
            }

            if (!$this->db->field_exists('id_user', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `id_user` int(11) DEFAULT NULL AFTER `id_peminjam`");
            }

            if (!$this->db->field_exists('status_kaprodi', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `status_kaprodi` varchar(20) NOT NULL DEFAULT 'Pending' AFTER `status`");
                $this->db->query("UPDATE `{$this->table_peminjaman}` SET `status_kaprodi` = 'Disetujui' WHERE `status` NOT IN ('Menunggu ACC Kaprodi', 'Ditolak', 'Kedaluwarsa / Ditolak Otomatis')");
            }
            if (!$this->db->field_exists('catatan_kaprodi', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `catatan_kaprodi` text DEFAULT NULL AFTER `status_kaprodi`");
            }
            if (!$this->db->field_exists('tgl_approve_kaprodi', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `tgl_approve_kaprodi` datetime DEFAULT NULL AFTER `catatan_kaprodi`");
            }
            if (!$this->db->field_exists('id_approver_kaprodi', $this->table_peminjaman)) {
                $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `id_approver_kaprodi` int(11) DEFAULT NULL AFTER `tgl_approve_kaprodi`");
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

            // Legacy records with an approved/active status already passed
            // QR finalization in the old workflow. Restore the QR flag so
            // those transactions do not become permanently unprocessable.
            $this->db->query("UPDATE `{$this->table_peminjaman}`
                SET `qr_locked` = 1,
                    `qr_finalized_at` = COALESCE(`qr_finalized_at`, `updated_at`, NOW())
                WHERE `qr_locked` = 0
                  AND `status` IN ('Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam')");
        }

        if ($this->db->table_exists('aset')) {
            $aset_condition = $this->db->query("SHOW COLUMNS FROM `aset` LIKE 'kondisi'")->row();
            if ($aset_condition && stripos((string) $aset_condition->Type, 'enum') !== false) {
                $this->db->query("ALTER TABLE `aset` MODIFY `kondisi` varchar(50) DEFAULT 'Baik'");
            }
        }

        $this->ensure_stock_schema();

        if (!$this->db->table_exists($this->table_notifikasi)) {
            $this->db->query("CREATE TABLE `notifikasi_progress` (
                `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT,
                `recipient_role` varchar(30) DEFAULT NULL,
                `recipient_user_id` int(11) DEFAULT NULL,
                `judul` varchar(160) NOT NULL,
                `pesan` text DEFAULT NULL,
                `link` varchar(255) DEFAULT NULL,
                `reference_type` varchar(60) DEFAULT NULL,
                `reference_id` int(11) DEFAULT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_notifikasi`),
                KEY `idx_notif_role` (`recipient_role`),
                KEY `idx_notif_user` (`recipient_user_id`),
                KEY `idx_notif_read` (`is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } else {
            if (!$this->db->field_exists('reference_type', $this->table_notifikasi)) {
                $this->db->query("ALTER TABLE `{$this->table_notifikasi}` ADD `reference_type` varchar(60) DEFAULT NULL AFTER `link`");
            }
            if (!$this->db->field_exists('reference_id', $this->table_notifikasi)) {
                $this->db->query("ALTER TABLE `{$this->table_notifikasi}` ADD `reference_id` int(11) DEFAULT NULL AFTER `reference_type`");
            }
        }

        if (!$this->db->table_exists('peminjaman_evidence')) {
            $this->db->query("CREATE TABLE `peminjaman_evidence` (
                `id_evidence` int(11) NOT NULL AUTO_INCREMENT,
                `id_peminjaman` int(11) DEFAULT NULL,
                `group_id` varchar(120) DEFAULT NULL,
                `jenis` varchar(40) NOT NULL DEFAULT 'serah_terima',
                `nama_file` varchar(255) NOT NULL,
                `original_name` varchar(255) DEFAULT NULL,
                `uploaded_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id_evidence`),
                KEY `idx_evidence_peminjaman` (`id_peminjaman`),
                KEY `idx_evidence_group` (`group_id`),
                KEY `idx_evidence_jenis` (`jenis`)
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

        $this->ensure_kaprodi_expiration_schema();
        $this->db->where('id_setting', 1)->update($this->table_settings, [
            'schema_version' => $this->workflow_schema_version,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function ensure_kaprodi_expiration_schema() {
        if (!$this->db->table_exists($this->table_settings)) {
            $this->db->query("CREATE TABLE `{$this->table_settings}` (
                `id_setting` tinyint(3) unsigned NOT NULL DEFAULT 1,
                `kaprodi_approval_days` int(11) NOT NULL DEFAULT 4,
                `schema_version` int(11) NOT NULL DEFAULT 0,
                `updated_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id_setting`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }
        if (!$this->db->field_exists('schema_version', $this->table_settings)) {
            $this->db->query("ALTER TABLE `{$this->table_settings}` ADD `schema_version` int(11) NOT NULL DEFAULT 0 AFTER `kaprodi_approval_days`");
        }
        $this->db->query("INSERT IGNORE INTO `{$this->table_settings}` (`id_setting`, `kaprodi_approval_days`, `updated_at`) VALUES (1, 4, NOW())");

        if (!$this->db->table_exists($this->table_peminjaman)) return;
        if (!$this->db->field_exists('kaprodi_approval_limit_days', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `kaprodi_approval_limit_days` int(11) DEFAULT NULL AFTER `status_kaprodi`");
        }
        if (!$this->db->field_exists('kaprodi_deadline_at', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `kaprodi_deadline_at` datetime DEFAULT NULL AFTER `kaprodi_approval_limit_days`");
        }
        if (!$this->db->field_exists('kaprodi_expired_at', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `kaprodi_expired_at` datetime DEFAULT NULL AFTER `kaprodi_deadline_at`");
        }
        $expiry_index = $this->db->query("SHOW INDEX FROM `{$this->table_peminjaman}` WHERE Key_name = 'idx_kaprodi_expiry'")->row();
        if (!$expiry_index) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD INDEX `idx_kaprodi_expiry` (`status`, `status_kaprodi`, `kaprodi_deadline_at`)");
        }

        $days = $this->get_kaprodi_approval_days();
        // Data lama mendapat masa transisi dari waktu migrasi, bukan dihitung
        // mundur dari created_at, sehingga rollout tidak menolak data massal.
        $this->db->query("UPDATE `{$this->table_peminjaman}`
            SET `kaprodi_approval_limit_days` = ?,
                `kaprodi_deadline_at` = DATE_ADD(NOW(), INTERVAL {$days} DAY)
            WHERE `status` = 'Menunggu ACC Kaprodi'
              AND `status_kaprodi` = 'Pending'
              AND `kaprodi_deadline_at` IS NULL", [$days]);
    }

    public function get_loan_settings() {
        $row = $this->db->where('id_setting', 1)->get($this->table_settings)->row();
        return $row ?: (object) [
            'id_setting' => 1,
            'kaprodi_approval_days' => 4,
            'updated_by' => null,
            'updated_at' => null,
        ];
    }

    public function get_kaprodi_approval_days() {
        $settings = $this->get_loan_settings();
        return min(30, max(1, (int) ($settings->kaprodi_approval_days ?? 4)));
    }

    public function update_kaprodi_approval_days($days, $updated_by = null) {
        $days = (int) $days;
        if ($days < 1 || $days > 30) return false;
        return $this->db->where('id_setting', 1)->update($this->table_settings, [
            'kaprodi_approval_days' => $days,
            'updated_by' => $updated_by ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
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

    public function get_all_peminjam($limit = null) {
        $this->db->order_by('nama_peminjam', 'ASC');
        if ($limit !== null) $this->db->limit(max(1, (int) $limit));
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
     * Membuat pengajuan dan reservasi dalam satu transaksi atomik.
     * Return false berarti stok sudah berubah/tidak cukup atau INSERT gagal.
     */
    public function create_with_stock_reservation(array $data) {
        $id_aset = (int) ($data['id_aset'] ?? 0);
        $jumlah = (int) ($data['jumlah_pinjam'] ?? 0);
        if ($id_aset < 1 || $jumlah < 1) return false;

        $days = $this->get_kaprodi_approval_days();
        $created_at = !empty($data['created_at']) && strtotime((string) $data['created_at']) !== false
            ? (string) $data['created_at']
            : date('Y-m-d H:i:s');
        $db_now_row = $this->db->query('SELECT NOW() AS db_now')->row();
        $deadline_base = (string) ($db_now_row->db_now ?? $created_at);
        $data['created_at'] = $created_at;
        $data['kaprodi_approval_limit_days'] = $days;
        $data['kaprodi_deadline_at'] = date('Y-m-d H:i:s', strtotime('+' . $days . ' days', strtotime($deadline_base)));
        $data['kaprodi_expired_at'] = null;

        $this->db->trans_begin();
        if (!$this->Aset_model->reserve_stock($id_aset, $jumlah)) {
            $this->db->trans_rollback();
            return false;
        }

        $data['jumlah_pinjam'] = $jumlah;
        $data['stock_allocation_status'] = 'reserved';
        $data['stock_allocated_at'] = date('Y-m-d H:i:s');
        $data['stock_released_at'] = null;
        if (!$this->db->insert($this->table_peminjaman, $data)) {
            $this->db->trans_rollback();
            return false;
        }
        $id = (int) $this->db->insert_id();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $id;
    }

    private function allocation_rows_for_update($group_id) {
        if (strpos((string) $group_id, 'single-') === 0) {
            return $this->db->query('SELECT * FROM `' . $this->table_peminjaman . '` WHERE id_peminjaman = ? FOR UPDATE', [(int) str_replace('single-', '', $group_id)])->result();
        }
        return $this->db->query('SELECT * FROM `' . $this->table_peminjaman . '` WHERE group_id = ? ORDER BY id_peminjaman ASC FOR UPDATE', [(string) $group_id])->result();
    }

    /** Pastikan data legacy juga mempunyai reservasi sebelum approval lanjut. */
    public function ensure_group_reserved($group_id) {
        $this->db->trans_begin();
        $rows = $this->allocation_rows_for_update($group_id);
        if (empty($rows)) {
            $this->db->trans_rollback();
            return false;
        }
        foreach ($rows as $row) {
            $state = (string) ($row->stock_allocation_status ?? 'none');
            if (in_array($state, ['reserved', 'borrowed'], true)) continue;
            if (!in_array($state, ['none', 'awaiting_stock'], true)
                || !$this->Aset_model->reserve_stock($row->id_aset, $row->jumlah_pinjam)) {
                $this->db->trans_rollback();
                return false;
            }
            $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                'stock_allocation_status' => 'reserved',
                'stock_allocated_at' => date('Y-m-d H:i:s'),
                'stock_released_at' => null,
            ]);
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /**
     * Mengunci status workflow dan reservasi dalam transaksi yang sama agar
     * aksi setuju/tolak bersamaan tidak menghasilkan status tanpa stok.
     */
    public function approve_group_with_reservation($group_id, array $expected_statuses, array $status_update) {
        if (empty($expected_statuses)) return false;

        $this->db->trans_begin();
        $rows = $this->allocation_rows_for_update($group_id);
        if (empty($rows)) {
            $this->db->trans_rollback();
            return false;
        }

        foreach ($rows as $row) {
            if (!in_array((string) $row->status, $expected_statuses, true)) {
                $this->db->trans_rollback();
                return false;
            }
            $state = (string) ($row->stock_allocation_status ?? 'none');
            if ($state === 'reserved') continue;
            if (!in_array($state, ['none', 'awaiting_stock'], true)
                || !$this->Aset_model->reserve_stock($row->id_aset, $row->jumlah_pinjam)) {
                $this->db->trans_rollback();
                return false;
            }
            $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                'stock_allocation_status' => 'reserved',
                'stock_allocated_at' => date('Y-m-d H:i:s'),
                'stock_released_at' => null,
            ]);
        }

        $status_update['updated_at'] = $status_update['updated_at'] ?? date('Y-m-d H:i:s');
        if (strpos((string) $group_id, 'single-') === 0) {
            $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id));
        } else {
            $this->db->where('group_id', $group_id);
        }
        $this->db->update($this->table_peminjaman, $status_update);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /** Penolakan + pelepasan reservasi dilakukan atomik dan idempotent. */
    public function reject_group_and_release($group_id, array $status_update, array $expected_statuses = []) {
        $this->db->trans_begin();
        $rows = $this->allocation_rows_for_update($group_id);
        if (empty($rows)) {
            $this->db->trans_rollback();
            return false;
        }
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $row) {
            if (!empty($expected_statuses) && !in_array((string) $row->status, $expected_statuses, true)) {
                $this->db->trans_rollback();
                return false;
            }
            $state = (string) ($row->stock_allocation_status ?? 'none');
            if ($state === 'borrowed') {
                $this->db->trans_rollback();
                return false;
            }
            if ($state === 'reserved' && !$this->Aset_model->release_reserved_stock($row->id_aset, $row->jumlah_pinjam)) {
                $this->db->trans_rollback();
                return false;
            }
            if (in_array($state, ['reserved', 'none', 'awaiting_stock'], true)) {
                $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                    'stock_allocation_status' => 'released',
                    'stock_released_at' => $now,
                ]);
            }
        }
        $status_update['updated_at'] = $now;
        if (strpos((string) $group_id, 'single-') === 0) {
            $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id));
        } else {
            $this->db->where('group_id', $group_id);
        }
        $this->db->update($this->table_peminjaman, $status_update);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /**
     * Menolak seluruh group jika tenggat Kaprodi benar-benar sudah lewat.
     * Lock baris membuatnya aman terhadap klik Setujui pada waktu bersamaan.
     */
    private function expire_kaprodi_group_if_due($group_id, $now) {
        $this->db->trans_begin();
        $rows = $this->allocation_rows_for_update($group_id);
        if (empty($rows)) {
            $this->db->trans_rollback();
            return false;
        }

        foreach ($rows as $row) {
            if ((string) $row->status !== 'Menunggu ACC Kaprodi'
                || (string) ($row->status_kaprodi ?? 'Pending') !== 'Pending'
                || empty($row->kaprodi_deadline_at)
                || strtotime((string) $row->kaprodi_deadline_at) > strtotime($now)) {
                $this->db->trans_rollback();
                return false;
            }
        }

        foreach ($rows as $row) {
            $state = (string) ($row->stock_allocation_status ?? 'none');
            if ($state === 'reserved'
                && !$this->Aset_model->release_reserved_stock($row->id_aset, $row->jumlah_pinjam)) {
                $this->db->trans_rollback();
                return false;
            }
            if (in_array($state, ['reserved', 'none', 'awaiting_stock'], true)) {
                $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                    'stock_allocation_status' => 'released',
                    'stock_released_at' => $now,
                ]);
            } elseif ($state !== 'released') {
                $this->db->trans_rollback();
                return false;
            }
        }

        $status_update = [
            'status' => 'Kedaluwarsa / Ditolak Otomatis',
            'status_kaprodi' => 'Ditolak',
            'catatan_kaprodi' => 'Batas waktu persetujuan Kaprodi telah kedaluwarsa.',
            'tgl_approve_kaprodi' => $now,
            'id_approver_kaprodi' => null,
            'kaprodi_expired_at' => $now,
            'updated_at' => $now,
        ];
        if (strpos((string) $group_id, 'single-') === 0) {
            $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id));
        } else {
            $this->db->where('group_id', $group_id);
        }
        $this->db->update($this->table_peminjaman, $status_update);

        $recipient_user_id = (int) ($rows[0]->id_user ?? 0);
        if ($recipient_user_id > 0) {
            $message = 'Batas waktu persetujuan Kaprodi telah kedaluwarsa. Pengajuan peminjaman dibatalkan secara otomatis. Silakan lakukan peminjaman kembali jika barang masih dibutuhkan.';
            if (!$this->create_notifikasi(
                null,
                $recipient_user_id,
                'Pengajuan peminjaman kedaluwarsa',
                $message,
                site_url('peminjaman/riwayat'),
                'peminjaman_expired',
                (int) $rows[0]->id_peminjaman
            )) {
                $this->db->trans_rollback();
                return false;
            }
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /** Dipanggil pada bootstrap aplikasi dan dapat dipanggil dari task CLI. */
    public function expire_overdue_kaprodi_approvals($limit = 200) {
        $limit = min(1000, max(1, (int) $limit));
        $db_now_row = $this->db->query('SELECT NOW() AS db_now')->row();
        $now = (string) ($db_now_row->db_now ?? date('Y-m-d H:i:s'));
        $candidates = $this->db->select("COALESCE(NULLIF(group_id, ''), CONCAT('single-', id_peminjaman)) AS group_ref", false)
            ->from($this->table_peminjaman)
            ->where('status', 'Menunggu ACC Kaprodi')
            ->where('status_kaprodi', 'Pending')
            ->where('kaprodi_deadline_at IS NOT NULL', null, false)
            ->where('kaprodi_deadline_at <=', $now)
            ->group_by("COALESCE(NULLIF(group_id, ''), CONCAT('single-', id_peminjaman))", false)
            ->order_by('MIN(kaprodi_deadline_at)', 'ASC', false)
            ->limit($limit)
            ->get()->result();

        $expired = 0;
        foreach ($candidates as $candidate) {
            if ($this->expire_kaprodi_group_if_due($candidate->group_ref, $now)) {
                $expired++;
            }
        }
        return $expired;
    }

    public function get_last_expired_count() {
        return (int) $this->last_expired_count;
    }

    public function convert_reservation_to_borrowed($id_peminjaman, $jumlah_aktual) {
        $jumlah_aktual = max(0, (int) $jumlah_aktual);
        $this->db->trans_begin();
        $row = $this->db->query('SELECT * FROM `' . $this->table_peminjaman . '` WHERE id_peminjaman = ? LIMIT 1 FOR UPDATE', [(int) $id_peminjaman])->row();
        if (!$row || $jumlah_aktual > (int) $row->jumlah_pinjam) {
            $this->db->trans_rollback();
            return false;
        }

        $reserved = (int) $row->jumlah_pinjam;
        $state = (string) ($row->stock_allocation_status ?? 'none');
        if (in_array($state, ['none', 'awaiting_stock'], true)) {
            if (!$this->Aset_model->reserve_stock($row->id_aset, $reserved)) {
                $this->db->trans_rollback();
                return false;
            }
            $state = 'reserved';
        }
        if ($state !== 'reserved') {
            $this->db->trans_rollback();
            return false;
        }
        $ok = $jumlah_aktual === 0
            ? $this->Aset_model->release_reserved_stock($row->id_aset, $reserved)
            : $this->Aset_model->reserved_to_borrowed($row->id_aset, $reserved, $jumlah_aktual);
        if (!$ok) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
            'jumlah_pinjam' => $jumlah_aktual,
            'stock_allocation_status' => $jumlah_aktual > 0 ? 'borrowed' : 'released',
            'stock_released_at' => $jumlah_aktual > 0 ? null : date('Y-m-d H:i:s'),
        ]);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    public function return_stock_allocation($id_peminjaman, $jumlah_kembali, $make_available = true) {
        $jumlah_kembali = max(0, (int) $jumlah_kembali);
        $this->db->trans_begin();
        $row = $this->db->query('SELECT * FROM `' . $this->table_peminjaman . '` WHERE id_peminjaman = ? LIMIT 1 FOR UPDATE', [(int) $id_peminjaman])->row();
        if (!$row) {
            $this->db->trans_rollback();
            return false;
        }
        $state = (string) ($row->stock_allocation_status ?? 'none');
        if (in_array($state, ['returned', 'unavailable', 'partial_unavailable'], true)) {
            $this->db->trans_commit();
            return true;
        }
        $borrowed_qty = max(0, (int) $row->jumlah_pinjam);
        if ($state === 'released' && $borrowed_qty === 0) {
            $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                'jumlah_kembali' => 0,
                'stock_allocation_status' => 'returned',
                'stock_released_at' => date('Y-m-d H:i:s'),
            ]);
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                return false;
            }
            $this->db->trans_commit();
            return true;
        }
        if ($state !== 'borrowed' || $jumlah_kembali > $borrowed_qty) {
            $this->db->trans_rollback();
            return false;
        }

        if ($make_available && $jumlah_kembali > 0
            && !$this->Aset_model->return_borrowed_stock($row->id_aset, $jumlah_kembali, true)) {
            $this->db->trans_rollback();
            return false;
        }
        $allocation_status = !$make_available
            ? 'unavailable'
            : ($jumlah_kembali === $borrowed_qty ? 'returned' : 'partial_unavailable');
        $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
            'jumlah_kembali' => $jumlah_kembali,
            'stock_allocation_status' => $allocation_status,
            'stock_released_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /**
     * SEARCH PEMINJAMAN - DENGAN URUTAN TERBARU DI ATAS
     * FIX: Menambahkan ORDER BY yang benar untuk menampilkan data terbaru di paling atas
     */
    public function search_peminjaman($filters = [], $limit = null, $offset = 0) {
        // Select dengan GROUP BY group_id
        $this->db->select('
            COALESCE(p.group_id, CONCAT("single-", p.id_peminjaman)) as group_id,
            MIN(p.id_peminjaman) as id_peminjaman,
            MAX(p.id_user) as id_user,
            MAX(p.tanggal_pinjam) as tanggal_pinjam,
            MAX(p.tanggal_kembali_rencana) as tanggal_kembali_rencana,
            MAX(p.tanggal_kembali_actual) as tanggal_kembali_actual,
            MAX(p.status) as status,
            MAX(p.status_kaprodi) as status_kaprodi,
            MAX(p.kaprodi_approval_limit_days) as kaprodi_approval_limit_days,
            MAX(p.kaprodi_deadline_at) as kaprodi_deadline_at,
            MAX(p.kaprodi_expired_at) as kaprodi_expired_at,
            MAX(p.status_laboran) as status_laboran,
            MAX(p.status_kaur) as status_kaur,
            MAX(p.keperluan) as keperluan,
            MAX(p.foto_pengembalian) as foto_pengembalian,
            MAX(p.foto_bukti) as foto_bukti,
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
        $this->db->join('aset sort_aset', 'sort_aset.id_aset = p.id_aset', 'left');
        $this->db->join('ruangan sort_ruangan', 'sort_ruangan.id_ruangan = sort_aset.id_ruangan', 'left');
        
        $this->apply_peminjaman_search_filters($filters);
        
        $this->db->group_by('COALESCE(p.group_id, CONCAT("single-", p.id_peminjaman))', false);
        
        // ========== SORTING ==========
        // Default: terbaru ke terlama. Bisa dioverride lewat filters['sort_by'] / filters['sort_dir'].
        $sort_map = [
            'number' => 'id_peminjaman',
            'peminjam' => 'nama_peminjam',
            'nama_peminjam' => 'nama_peminjam',
            'masa' => 'tanggal_pinjam',
            'tanggal_pinjam' => 'tanggal_pinjam',
            'tanggal_kembali' => 'tanggal_kembali_rencana',
            'status' => 'status',
        ];
        $sort_key = $filters['sort_by'] ?? '';
        $sort_dir = strtoupper((string) ($filters['sort_dir'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';
        $has_explicit_sort = $sort_key === 'barang' || $sort_key === 'lab' || isset($sort_map[$sort_key]);

        $action_role = strtolower((string) ($filters['action_role'] ?? ''));
        if (!$has_explicit_sort && $action_role === 'kaprodi') {
            $this->db->order_by("CASE WHEN MAX(p.status) = 'Menunggu ACC Kaprodi' AND MAX(p.status_kaprodi) = 'Pending' THEN 0 ELSE 1 END", 'ASC', false);
        } elseif (!$has_explicit_sort && $action_role === 'laboran') {
            $this->db->order_by("CASE WHEN MAX(p.status) IN ('Menunggu Verifikasi Laboran','Menunggu Pengecekan Laboran','Menunggu Persetujuan') AND MAX(p.status_kaprodi) = 'Disetujui' AND MAX(p.status_laboran) = 'Pending' THEN 0 ELSE 1 END", 'ASC', false);
        } elseif (!$has_explicit_sort && $action_role === 'kaur') {
            $this->db->order_by("CASE WHEN MAX(p.status) = 'Menunggu ACC Kaur' AND MAX(p.status_kaprodi) = 'Disetujui' AND MAX(p.status_laboran) = 'Disetujui' AND MAX(p.status_kaur) = 'Pending' THEN 0 ELSE 1 END", 'ASC', false);
        }

        if ($sort_key === 'barang') {
            $this->db->order_by('MIN(sort_aset.nama_aset)', $sort_dir, false);
            $this->db->order_by('id_peminjaman', 'DESC');
        } elseif ($sort_key === 'lab') {
            $this->db->order_by('MIN(sort_ruangan.nama_ruangan)', $sort_dir, false);
            $this->db->order_by('id_peminjaman', 'DESC');
        } elseif (isset($sort_map[$sort_key])) {
            $this->db->order_by($sort_map[$sort_key], $sort_dir);
            $this->db->order_by('id_peminjaman', 'DESC');
        } else {
            $this->db->order_by('tanggal_pinjam', 'DESC');
            $this->db->order_by('id_peminjaman', 'DESC');
        }
        // ============================================
        
        if ($limit !== null) {
            $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));
        }
        $query = $this->db->get();
        $results = $query->result();
        
        if (empty($results)) return [];

        $group_ids = [];
        $single_ids = [];
        foreach ($results as $result) {
            if (strpos((string) $result->group_id, 'single-') === 0) {
                $single_ids[] = (int) str_replace('single-', '', $result->group_id);
            } else {
                $group_ids[] = (string) $result->group_id;
            }
        }

        $detail_map = [];
        $this->db->select('COALESCE(p.group_id, CONCAT("single-", p.id_peminjaman)) AS group_key, p.id_peminjaman, p.id_aset, p.jumlah_pinjam, a.nama_aset, a.kode_aset, r.nama_ruangan', false);
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('aset a', 'a.id_aset = p.id_aset', 'left');
        $this->db->join('ruangan r', 'r.id_ruangan = a.id_ruangan', 'left');
        $this->db->group_start();
        if (!empty($group_ids)) $this->db->where_in('p.group_id', $group_ids);
        if (!empty($single_ids)) {
            if (!empty($group_ids)) $this->db->or_where_in('p.id_peminjaman', $single_ids);
            else $this->db->where_in('p.id_peminjaman', $single_ids);
        }
        $this->db->group_end();
        foreach ($this->db->get()->result() as $detail) {
            $detail_map[(string) $detail->group_key][] = $detail;
        }

        $evidence_map = [];
        if ($this->db->table_exists('peminjaman_evidence')) {
            $this->db->select('peminjaman_evidence.*, COALESCE(group_id, CONCAT("single-", id_peminjaman)) AS group_key', false);
            $this->db->from('peminjaman_evidence')->where('jenis', 'serah_terima');
            $this->db->group_start();
            if (!empty($group_ids)) $this->db->where_in('group_id', $group_ids);
            if (!empty($single_ids)) {
                if (!empty($group_ids)) $this->db->or_where_in('id_peminjaman', $single_ids);
                else $this->db->where_in('id_peminjaman', $single_ids);
            }
            $this->db->group_end()->order_by('created_at', 'DESC');
            foreach ($this->db->get()->result() as $evidence) {
                $evidence_map[(string) $evidence->group_key][] = $evidence;
            }
        }

        foreach ($results as $result) {
            $key = (string) $result->group_id;
            $result->detail_barang = $detail_map[$key] ?? [];
            $result->kegiatan = $result->keperluan;
            $result->evidence_serah = $evidence_map[$key] ?? [];
        }
        
        return $results;
    }

    public function count_visible_peminjaman($filters = []) {
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        $this->db->select('COUNT(DISTINCT COALESCE(p.group_id, CONCAT("single-", p.id_peminjaman))) AS total', false);
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('peminjam', 'peminjam.id_peminjam = p.id_peminjam', 'left');
        $this->apply_peminjaman_search_filters($filters);
        $row = $this->db->get()->row();
        return (int) ($row->total ?? 0);
    }

    public function count_actionable_peminjaman($role, $filters = []) {
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        $this->db->select('COUNT(DISTINCT COALESCE(p.group_id, CONCAT("single-", p.id_peminjaman))) AS total', false);
        $this->db->from($this->table_peminjaman . ' as p');
        $this->db->join('peminjam', 'peminjam.id_peminjam = p.id_peminjam', 'left');
        $this->apply_peminjaman_search_filters($filters);
        if ($role === 'kaprodi') {
            $this->db->where('p.status', 'Menunggu ACC Kaprodi')->where('p.status_kaprodi', 'Pending');
        } elseif ($role === 'laboran') {
            $this->db->where_in('p.status', ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan']);
            $this->db->where('p.status_kaprodi', 'Disetujui')->where('p.status_laboran', 'Pending');
        } elseif ($role === 'kaur') {
            $this->db->where('p.status', 'Menunggu ACC Kaur');
            $this->db->where('p.status_kaprodi', 'Disetujui')->where('p.status_laboran', 'Disetujui')->where('p.status_kaur', 'Pending');
        } else {
            return 0;
        }
        $row = $this->db->get()->row();
        return (int) ($row->total ?? 0);
    }

    private function apply_peminjaman_search_filters(array $filters) {
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'Terlambat') {
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

        foreach ((array) ($filters['multi_filters'] ?? []) as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'peminjam') {
                $this->db->group_start()->like('peminjam.nama_peminjam', $value)->or_like('peminjam.nim_nip', $value)->group_end();
            } elseif ($field === 'all') {
                $search = '%' . $value . '%';
                $this->db->group_start()
                    ->like('peminjam.nama_peminjam', $value)
                    ->or_like('peminjam.nim_nip', $value)
                    ->or_like('p.status', $value)
                    ->or_like('p.keperluan', $value)
                    ->or_where("p.id_aset IN (SELECT a.id_aset FROM `aset` a WHERE a.nama_aset LIKE " . $this->db->escape($search) . " OR a.kode_aset LIKE " . $this->db->escape($search) . ")", null, false)
                    ->group_end();
            } elseif ($field === 'barang') {
                $search = '%' . $value . '%';
                $this->db->where("p.id_aset IN (SELECT a.id_aset FROM `aset` a WHERE a.nama_aset LIKE " . $this->db->escape($search) . " OR a.kode_aset LIKE " . $this->db->escape($search) . ")", null, false);
            } elseif ($field === 'lab') {
                $search = '%' . $value . '%';
                $this->db->where("p.id_aset IN (SELECT a.id_aset FROM `aset` a LEFT JOIN `ruangan` r ON r.id_ruangan = a.id_ruangan WHERE r.nama_ruangan LIKE " . $this->db->escape($search) . ")", null, false);
            } elseif (in_array($field, ['status', 'status_approval'], true)) {
                if ($value === 'Terlambat') {
                    $this->db->where_in('p.status', ['Sedang Dipinjam', 'Dipinjam'])->where('p.tanggal_kembali_rencana <', date('Y-m-d'));
                } else {
                    $this->db->like('p.status', $value);
                }
            } elseif (in_array($field, ['tanggal', 'masa'], true)) {
                $date_range = scm_parse_date_range($value);
                if ($date_range) {
                    $this->db->where('DATE(p.tanggal_pinjam) >=', $date_range['start'])->where('DATE(p.tanggal_pinjam) <=', $date_range['end']);
                }
            } elseif ($field === 'keperluan') {
                $this->db->like('p.keperluan', $value);
            } elseif ($field === 'jumlah' && ctype_digit($value)) {
                $this->db->where('p.jumlah_pinjam', (int) $value);
            } elseif ($field === 'number') {
                $this->db->group_start()->like('p.group_id', $value)->or_where('p.id_peminjaman', (int) $value)->group_end();
            }
        }

        if (!empty($filters['pencarian']) && trim((string) $filters['pencarian']) !== '') {
            $search = '%' . trim((string) $filters['pencarian']) . '%';
            $this->db->group_start();
            $this->db->like('peminjam.nama_peminjam', $search, 'both')->or_like('peminjam.nim_nip', $search, 'both')->or_like('p.keperluan', $search, 'both');
            $this->db->or_where("p.id_aset IN (SELECT a.id_aset FROM `aset` a WHERE a.nama_aset LIKE " . $this->db->escape($search) . " OR a.kode_aset LIKE " . $this->db->escape($search) . ")", null, false);
            $this->db->group_end();
        }
        if (!empty($filters['tanggal'])) {
            $date_range = scm_parse_date_range($filters['tanggal']);
            if ($date_range) $this->db->where('DATE(p.tanggal_pinjam) >=', $date_range['start'])->where('DATE(p.tanggal_pinjam) <=', $date_range['end']);
        }
        if (!empty($filters['tanggal_dari'])) $this->db->where('DATE(p.tanggal_pinjam) >=', $filters['tanggal_dari']);
        if (!empty($filters['tanggal_sampai'])) $this->db->where('DATE(p.tanggal_pinjam) <=', $filters['tanggal_sampai']);
    }

    /**
     * GET ALL PEMINJAMAN - Menggunakan search dengan filter kosong
     * Sekarang otomatis terurut dari yang TERBARU
     */
    public function get_all_peminjaman() {
        return $this->search_peminjaman([]);
    }

    /**
     * Menambahkan ledger stok tanpa merusak data lama. Rekonsiliasi dijalankan
     * saat kolom dibuat atau saat dump lama masih mempunyai alokasi aktif 'none'.
     */
    private function ensure_stock_schema() {
        if (!$this->db->table_exists('aset') || !$this->db->table_exists($this->table_peminjaman)) return;

        $needs_backfill = false;
        if (!$this->db->field_exists('jumlah_reserved', 'aset')) {
            $this->db->query("ALTER TABLE `aset` ADD `jumlah_reserved` int(11) NOT NULL DEFAULT 0 AFTER `jumlah_total`");
            $needs_backfill = true;
        }
        if (!$this->db->field_exists('jumlah_dipinjam', 'aset')) {
            $this->db->query("ALTER TABLE `aset` ADD `jumlah_dipinjam` int(11) NOT NULL DEFAULT 0 AFTER `jumlah_reserved`");
            $needs_backfill = true;
        }
        if (!$this->db->field_exists('stock_allocation_status', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `stock_allocation_status` varchar(20) NOT NULL DEFAULT 'none' AFTER `jumlah_pinjam`");
            $needs_backfill = true;
        }
        if (!$this->db->field_exists('stock_allocated_at', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `stock_allocated_at` datetime DEFAULT NULL AFTER `stock_allocation_status`");
            $needs_backfill = true;
        }
        if (!$this->db->field_exists('stock_released_at', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `stock_released_at` datetime DEFAULT NULL AFTER `stock_allocated_at`");
            $needs_backfill = true;
        }
        if (!$this->db->field_exists('jumlah_kembali', $this->table_peminjaman)) {
            $this->db->query("ALTER TABLE `{$this->table_peminjaman}` ADD `jumlah_kembali` int(11) DEFAULT NULL AFTER `stock_released_at`");
            $needs_backfill = true;
        }

        if (!$needs_backfill) {
            $managed_statuses = [
                'Menunggu Persetujuan', 'Menunggu ACC Kaprodi', 'Menunggu Verifikasi Laboran',
                'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur',
                'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)',
                'Sedang Dipinjam', 'Dipinjam'
            ];
            $needs_backfill = $this->db->where('stock_allocation_status', 'none')
                ->where_in('status', $managed_statuses)
                ->count_all_results($this->table_peminjaman) > 0;
        }

        if (!$needs_backfill) return;

        $this->db->trans_start();
        $this->db->query("UPDATE `aset` SET `jumlah_reserved` = 0, `jumlah_dipinjam` = 0, `jumlah_tersedia` = `jumlah_total`");
        $this->db->query("UPDATE `{$this->table_peminjaman}` SET `stock_allocation_status` = CASE
            WHEN `status` IN ('Sedang Dipinjam', 'Dipinjam') THEN 'borrowed'
            WHEN `status` IN ('Dikembalikan', 'Selesai') THEN 'returned'
            WHEN `status` IN ('Ditolak', 'Kedaluwarsa / Ditolak Otomatis') THEN 'released'
            ELSE 'none' END,
            `jumlah_kembali` = CASE WHEN `status` IN ('Dikembalikan', 'Selesai') THEN `jumlah_pinjam` ELSE NULL END");

        $borrowed = $this->db->query("SELECT id_aset, SUM(jumlah_pinjam) AS qty
            FROM `{$this->table_peminjaman}` WHERE status IN ('Sedang Dipinjam', 'Dipinjam') GROUP BY id_aset")->result();
        foreach ($borrowed as $row) {
            $qty = max(0, (int) $row->qty);
            $this->db->query("UPDATE `aset` SET `jumlah_total` = GREATEST(`jumlah_total`, ?),
                `jumlah_dipinjam` = ?, `jumlah_tersedia` = GREATEST(`jumlah_total`, ?) - ? WHERE `id_aset` = ?",
                [$qty, $qty, $qty, $qty, (int) $row->id_aset]);
        }

        $pending_statuses = [
            'Menunggu Persetujuan', 'Menunggu ACC Kaprodi', 'Menunggu Verifikasi Laboran',
            'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur',
            'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)'
        ];
        $pending = $this->db->where_in('status', $pending_statuses)
            ->order_by('created_at', 'ASC')->order_by('id_peminjaman', 'ASC')
            ->get($this->table_peminjaman)->result();
        foreach ($pending as $row) {
            if ($this->Aset_model->reserve_stock($row->id_aset, $row->jumlah_pinjam)) {
                $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                    'stock_allocation_status' => 'reserved',
                    'stock_allocated_at' => $row->created_at ?: date('Y-m-d H:i:s'),
                ]);
            } else {
                // Data legacy yang melebihi stok tidak memicu rekonsiliasi
                // berulang; sistem akan mencoba reservasi lagi saat aksi berikutnya.
                $this->db->where('id_peminjaman', $row->id_peminjaman)->update($this->table_peminjaman, [
                    'stock_allocation_status' => 'awaiting_stock',
                    'stock_released_at' => null,
                ]);
            }
        }
        $this->db->trans_complete();
    }

    /**
     * Data peminjaman yang boleh dilihat lintas role. Tidak membatasi hasil
     * berdasarkan giliran approval; hak aksi tetap divalidasi terpisah.
     */
    public function get_visible_peminjaman($filters = [], $limit = null, $offset = 0) {
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        return $this->search_peminjaman($filters, $limit, $offset);
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
            if (!empty($result->group_id)) {
                $this->db->where('p.group_id', $result->group_id);
            } else {
                $this->db->where('p.id_peminjaman', $result->id_peminjaman);
            }
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

    private function apply_peminjam_history_filters(array $filters) {
        foreach ((array) ($filters['criteria'] ?? []) as $criterion) {
            $field = (string) ($criterion['field'] ?? 'all');
            $value = trim((string) ($criterion['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'tanggal') {
                $range = explode('..', $value, 2);
                if (count($range) === 2) {
                    $this->db->where('DATE(peminjaman.tanggal_pinjam) >=', $range[0]);
                    $this->db->where('DATE(peminjaman.tanggal_kembali_rencana) <=', $range[1]);
                } else {
                    $this->db->group_start()->where('DATE(peminjaman.created_at)', $value)->or_where('DATE(peminjaman.tanggal_pinjam)', $value)->or_where('DATE(peminjaman.tanggal_kembali_rencana)', $value)->group_end();
                }
                continue;
            }
            if ($field === 'barang') { $this->db->like('aset.nama_aset', $value); continue; }
            if ($field === 'kode') { $this->db->like('aset.kode_aset', $value); continue; }
            if ($field === 'status') { $this->db->like('peminjaman.status', $value); continue; }
            $this->db->group_start()->like('aset.nama_aset', $value)->or_like('aset.kode_aset', $value)->or_like('peminjaman.status', $value)->or_like('peminjaman.created_at', $value)->or_like('peminjaman.tanggal_pinjam', $value)->or_like('peminjaman.tanggal_kembali_rencana', $value)->group_end();
        }
    }

    private function build_peminjam_history_query($id_peminjam, array $filters, $select = true) {
        if ($select) {
            $this->db->select('peminjaman.*, aset.nama_aset, aset.kode_aset, ruangan.nama_ruangan');
        }
        $this->db->from($this->table_peminjaman . ' as peminjaman');
        $this->db->join('aset', 'aset.id_aset = peminjaman.id_aset', 'left');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        $this->db->where('peminjaman.id_peminjam', (int) $id_peminjam);
        $this->apply_peminjam_history_filters($filters);
    }

    public function get_peminjaman_by_peminjam($id_peminjam, $filters = [], $limit = 10, $offset = 0) {
        $this->build_peminjam_history_query($id_peminjam, (array) $filters);
        $sort_map = [
            'tanggal' => 'peminjaman.created_at',
            'barang' => 'aset.nama_aset',
            'masa' => 'peminjaman.tanggal_pinjam',
            'status' => 'peminjaman.status',
            'qr' => 'peminjaman.qr_locked',
        ];
        $sort_key = (string) ($filters['sort_by'] ?? '');
        $sort_dir = strtoupper((string) ($filters['sort_dir'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by($sort_map[$sort_key] ?? 'peminjaman.tanggal_pinjam', isset($sort_map[$sort_key]) ? $sort_dir : 'DESC');
        $this->db->order_by('peminjaman.id_peminjaman', 'DESC');
        $this->db->limit(max(1, (int) $limit), max(0, (int) $offset));
        return $this->db->get()->result();
    }

    public function count_peminjaman_by_peminjam($id_peminjam, $filters = []) {
        $this->build_peminjam_history_query($id_peminjam, (array) $filters, false);
        return (int) $this->db->count_all_results();
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
        if (strpos((string) $group_id, 'single-') === 0) {
            $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id));
        } else {
            $this->db->where('group_id', $group_id);
        }
        return $this->db->update($this->table_peminjaman, $data);
    }

    public function get_peminjaman_by_group_id($group_id) {
        $this->db->select('MIN(id_peminjaman) as id_peminjaman');
        if (strpos((string) $group_id, 'single-') === 0) {
            $row = $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id))->get($this->table_peminjaman)->row();
        } else {
            $row = $this->db->where('group_id', $group_id)->get($this->table_peminjaman)->row();
        }
        return $row && $row->id_peminjaman ? $this->get_peminjaman_by_id($row->id_peminjaman) : null;
    }

    /**
     * Reads one transaction while holding an InnoDB row lock. This prevents
     * two concurrent QR scans from both passing the same status check.
     */
    public function get_peminjaman_by_group_id_for_update($group_id) {
        if (strpos((string) $group_id, 'single-') === 0) {
            $sql = 'SELECT id_peminjaman FROM `' . $this->table_peminjaman . '` WHERE id_peminjaman = ? LIMIT 1 FOR UPDATE';
            $row = $this->db->query($sql, [(int) str_replace('single-', '', $group_id)])->row();
        } else {
            $sql = 'SELECT id_peminjaman FROM `' . $this->table_peminjaman . '` WHERE group_id = ? ORDER BY id_peminjaman ASC LIMIT 1 FOR UPDATE';
            $row = $this->db->query($sql, [$group_id])->row();
        }

        return $row && $row->id_peminjaman ? $this->get_peminjaman_by_id($row->id_peminjaman) : null;
    }

    public function get_evidence_by_group_id($group_id, $jenis = null) {
        if (!$this->db->table_exists('peminjaman_evidence')) {
            return [];
        }
        $this->db->from('peminjaman_evidence');
        if (strpos((string) $group_id, 'single-') === 0) {
            $this->db->where('id_peminjaman', (int) str_replace('single-', '', $group_id));
        } elseif ($group_id === null || $group_id === '') {
            $this->db->where('id_peminjaman IS NULL', null, false);
        } else {
            $this->db->where('group_id', $group_id);
        }
        if ($jenis !== null) {
            $this->db->where('jenis', $jenis);
        }
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function insert_evidence($data) {
        if (!$this->db->table_exists('peminjaman_evidence')) {
            return false;
        }
        return $this->db->insert('peminjaman_evidence', $data);
    }

    public function get_pending_laboran() {
        $rows = array_merge(
            $this->search_peminjaman(['status' => 'Menunggu Verifikasi Laboran']),
            $this->search_peminjaman(['status' => 'Menunggu Pengecekan Laboran']),
            $this->search_peminjaman(['status' => 'Menunggu Persetujuan'])
        );
        return array_values(array_filter($rows, static function ($row) {
            return ($row->status_kaprodi ?? 'Pending') === 'Disetujui'
                && ($row->status_laboran ?? 'Pending') === 'Pending';
        }));
    }

    public function get_pending_kaprodi($filters = []) {
        $filters['status'] = 'Menunggu ACC Kaprodi';
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        return $this->search_peminjaman($filters);
    }

    public function get_pending_kaur($filters = []) {
        $filters['status'] = 'Menunggu ACC Kaur';
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        return $this->search_peminjaman($filters);
    }

    public function get_pengembalian_readonly($filters = [], $limit = null, $offset = 0) {
        $filters['status_in'] = ['Sedang Dipinjam', 'Dipinjam', 'Dikembalikan'];
        if (!empty($filters['q']) && empty($filters['pencarian'])) {
            $filters['pencarian'] = $filters['q'];
        }
        return $this->search_peminjaman($filters, $limit, $offset);
    }

    public function count_pengembalian_readonly($filters = []) {
        $filters['status_in'] = ['Sedang Dipinjam', 'Dipinjam', 'Dikembalikan'];
        return $this->count_visible_peminjaman($filters);
    }

    public function get_qr_payload($group_id) {
        return site_url('admin/peminjaman/serah_terima/' . rawurlencode($group_id));
    }

    public function qr_is_visible($status, $qr_locked = 0) {
        return (int) $qr_locked === 1 && in_array((string) $status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam'], true);
    }

    public function finalize_qr($group_id, $id_user = null) {
        return $this->approve_group_with_reservation($group_id, ['Disetujui (Menunggu Finalisasi QR)'], [
            'status' => 'Disetujui (Menunggu Pengambilan)',
            'qr_locked' => 1,
            'qr_finalized_at' => date('Y-m-d H:i:s'),
            'qr_finalized_by' => $id_user,
        ]);
    }

    public function create_notifikasi($recipient_role, $recipient_user_id, $judul, $pesan, $link = null, $reference_type = null, $reference_id = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return false;
        }

        $inserted = $this->db->insert($this->table_notifikasi, [
            'recipient_role' => $recipient_role ?: null,
            'recipient_user_id' => $recipient_user_id ?: null,
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id ?: null,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$inserted) {
            return false;
        }

        $id_notifikasi = (int) $this->db->insert_id();
        if ($id_notifikasi > 0 && $reference_type === 'kaprodi_pengajuan' && $recipient_user_id) {
            $this->db->where('id_notifikasi', $id_notifikasi)->update($this->table_notifikasi, [
                'link' => site_url('kaprodi/dashboard/notifikasi/' . $id_notifikasi),
            ]);
        }
        return $id_notifikasi;
    }

    public function get_notifikasi_by_id($id_notifikasi, $recipient_role = null, $recipient_user_id = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return null;
        }

        $this->db->from($this->table_notifikasi);
        $this->db->where('id_notifikasi', (int) $id_notifikasi);
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
        return $this->db->get()->row();
    }

    public function mark_notifikasi_read($id_notifikasi, $recipient_role = null, $recipient_user_id = null) {
        if (!$this->db->table_exists($this->table_notifikasi)) {
            return false;
        }

        $notification = $this->get_notifikasi_by_id($id_notifikasi, $recipient_role, $recipient_user_id);
        if (!$notification) {
            return false;
        }
        return $this->db->where('id_notifikasi', (int) $id_notifikasi)->update($this->table_notifikasi, ['is_read' => 1]);
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

    private function build_blokir_query($filters = []) {
        $this->db->from($this->table_blokir);

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') continue;

            if ($field === 'pengguna') {
                $this->db->group_start()->like('nama_peminjam', $value)->or_like('nim_nip', $value)->group_end();
            } elseif ($field === 'periode') {
                $this->db->group_start()->where('tanggal_blokir', $value)->or_where('batas_blokir', $value)->group_end();
            } elseif ($field === 'alasan') {
                $this->db->group_start()->like('alasan', $value)->or_like('catatan_buka', $value)->group_end();
            } elseif ($field === 'status') {
                $this->db->like('status', $value);
            }
        }
    }

    public function get_blokir_pengguna($filters = [], $limit = 10, $offset = 0) {
        if (!$this->db->table_exists($this->table_blokir)) {
            return [];
        }

        $this->build_blokir_query($filters);
        $this->db->order_by("FIELD(status, 'Aktif', 'Dibuka')", '', false);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }

    public function count_blokir_pengguna($filters = []) {
        if (!$this->db->table_exists($this->table_blokir)) return 0;
        $this->build_blokir_query($filters);
        return (int) $this->db->count_all_results();
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
        $this->db->trans_begin();
        $peminjaman = $this->db->query(
            'SELECT * FROM `' . $this->table_peminjaman . '` WHERE id_peminjaman = ? LIMIT 1 FOR UPDATE',
            [(int) $id]
        )->row();
        if (!$peminjaman) {
            $this->db->trans_rollback();
            return false;
        }

        $allocation = (string) ($peminjaman->stock_allocation_status ?? 'none');
        if ($allocation === 'reserved'
            && !$this->Aset_model->release_reserved_stock($peminjaman->id_aset, $peminjaman->jumlah_pinjam)) {
            $this->db->trans_rollback();
            return false;
        }
        if ($allocation === 'borrowed'
            && !$this->Aset_model->return_borrowed_stock($peminjaman->id_aset, $peminjaman->jumlah_pinjam, true)) {
            $this->db->trans_rollback();
            return false;
        }

        if ($this->db->table_exists($this->table_peminjaman_detail)) {
            $this->db->where('id_peminjaman', $id);
            $this->db->delete($this->table_peminjaman_detail);
        }

        $this->db->where('id_peminjaman', $id);
        $this->db->delete($this->table_peminjaman);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
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
            $date_range = scm_parse_date_range($filters['tanggal']);
            if ($date_range) {
                $this->db->where('DATE(p.tanggal_pinjam) >=', $date_range['start']);
                $this->db->where('DATE(p.tanggal_pinjam) <=', $date_range['end']);
            }
        }
        foreach ((array) ($filters['multi_filters'] ?? []) as $filter) {
            if (($filter['field'] ?? '') !== 'tanggal') {
                continue;
            }
            $date_range = scm_parse_date_range($filter['value'] ?? '');
            if ($date_range) {
                $this->db->where('DATE(p.tanggal_pinjam) >=', $date_range['start']);
                $this->db->where('DATE(p.tanggal_pinjam) <=', $date_range['end']);
            }
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
            $this->db->where('peminjaman.status_kaprodi', 'Disetujui');
            $this->db->where('peminjaman.status_laboran', 'Pending');
        } elseif ($role === 'kaprodi') {
            $this->db->where('peminjaman.status', 'Menunggu ACC Kaprodi');
            $this->db->where('peminjaman.status_kaprodi', 'Pending');
        } elseif ($role === 'kaur') {
            $this->db->where('peminjaman.status', 'Menunggu ACC Kaur');
            $this->db->where('peminjaman.status_kaprodi', 'Disetujui');
            $this->db->where('peminjaman.status_laboran', 'Disetujui');
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
        } elseif ($role === 'kaprodi') {
            $this->db->where('peminjaman.status_kaprodi', $approval_status);
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
    private function build_katalog_query($filters = [], $id_ruangan = null) {
        $this->db->select('aset.*, ruangan.nama_ruangan');
        $this->db->from('aset');
        $this->db->join('ruangan', 'ruangan.id_ruangan = aset.id_ruangan', 'left');
        if ($id_ruangan !== null && $id_ruangan !== '') $this->db->where('aset.id_ruangan', (int) $id_ruangan);
        foreach ((array) $filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $value = trim((string) ($filter['value'] ?? ''));
            if ($value === '') continue;
            if ($field === 'all') {
                $this->db->group_start()->like('aset.nama_aset', $value)->or_like('aset.kode_aset', $value)->or_like('ruangan.nama_ruangan', $value)->or_like('aset.kondisi', $value);
                if (is_numeric($value)) $this->db->or_where('aset.jumlah_tersedia', (int) $value);
                $this->db->group_end();
            }
            elseif ($field === 'nama') $this->db->like('aset.nama_aset', $value);
            elseif ($field === 'kode') $this->db->like('aset.kode_aset', $value);
            elseif ($field === 'ruangan') $this->db->like('ruangan.nama_ruangan', $value);
            elseif ($field === 'kondisi') $this->db->like('aset.kondisi', $value);
            elseif ($field === 'stok' && is_numeric($value)) $this->db->where('aset.jumlah_tersedia', (int) $value);
        }
    }

    public function get_katalog_barang($filters = [], $limit = 10, $offset = 0, $id_ruangan = null) {
        $this->build_katalog_query($filters, $id_ruangan);
        $this->db->order_by('nama_aset', 'ASC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }

    public function count_katalog_barang($filters = [], $id_ruangan = null) {
        $this->build_katalog_query($filters, $id_ruangan);
        return (int) $this->db->count_all_results();
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
