-- Revisi alur peminjaman, QR serah terima, dan notifikasi progress.
-- Aman dijalankan setelah database utama di-import.

ALTER TABLE `peminjaman`
  MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu Pengecekan Laboran';

ALTER TABLE `peminjaman`
  ADD COLUMN IF NOT EXISTS `id_user` int(11) DEFAULT NULL AFTER `id_peminjam`;

CREATE TABLE IF NOT EXISTS `notifikasi_progress` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
