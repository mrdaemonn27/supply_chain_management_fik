-- Setting batas approval Kaprodi dan snapshot tenggat per transaksi.

CREATE TABLE IF NOT EXISTS `peminjaman_settings` (
  `id_setting` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `kaprodi_approval_days` int(11) NOT NULL DEFAULT 4,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `peminjaman_settings`
(`id_setting`, `kaprodi_approval_days`, `updated_at`) VALUES (1, 4, NOW());

ALTER TABLE `peminjaman`
  ADD COLUMN IF NOT EXISTS `kaprodi_approval_limit_days` int(11) DEFAULT NULL AFTER `status_kaprodi`,
  ADD COLUMN IF NOT EXISTS `kaprodi_deadline_at` datetime DEFAULT NULL AFTER `kaprodi_approval_limit_days`,
  ADD COLUMN IF NOT EXISTS `kaprodi_expired_at` datetime DEFAULT NULL AFTER `kaprodi_deadline_at`;

ALTER TABLE `peminjaman`
  ADD INDEX IF NOT EXISTS `idx_kaprodi_expiry` (`status`, `status_kaprodi`, `kaprodi_deadline_at`);

-- Pengisian tenggat data lama dan auto-reject dijalankan secara idempotent
-- oleh Peminjaman_model agar data existing memperoleh masa transisi.
