-- Ledger stok peminjaman: total = reserved + borrowed/ditahan + available.
-- MariaDB/MySQL modern mendukung ADD COLUMN IF NOT EXISTS.

ALTER TABLE `aset`
  ADD COLUMN IF NOT EXISTS `jumlah_reserved` int(11) NOT NULL DEFAULT 0 AFTER `jumlah_total`,
  ADD COLUMN IF NOT EXISTS `jumlah_dipinjam` int(11) NOT NULL DEFAULT 0 AFTER `jumlah_reserved`;

ALTER TABLE `peminjaman`
  ADD COLUMN IF NOT EXISTS `stock_allocation_status` varchar(20) NOT NULL DEFAULT 'none' AFTER `jumlah_pinjam`,
  ADD COLUMN IF NOT EXISTS `stock_allocated_at` datetime DEFAULT NULL AFTER `stock_allocation_status`,
  ADD COLUMN IF NOT EXISTS `stock_released_at` datetime DEFAULT NULL AFTER `stock_allocated_at`,
  ADD COLUMN IF NOT EXISTS `jumlah_kembali` int(11) DEFAULT NULL AFTER `stock_released_at`;

-- Rekonsiliasi data lama dijalankan idempotent oleh Peminjaman_model ketika
-- aplikasi pertama kali dibuka. Pengajuan baru memakai conditional UPDATE
-- di dalam transaksi sehingga reservasi bersamaan tidak dapat overbook.
