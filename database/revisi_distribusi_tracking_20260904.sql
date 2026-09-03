-- Jalankan sekali pada database lama yang sudah memiliki tabel distribusi_barang.
-- Backup database sebelum menjalankan skrip manual ini.
ALTER TABLE `distribusi_barang`
    ADD COLUMN `kondisi_aset` varchar(50) DEFAULT NULL AFTER `jumlah`,
    ADD COLUMN `waktu_distribusi` datetime DEFAULT NULL AFTER `tanggal_distribusi`,
    ADD COLUMN `penerima` varchar(150) DEFAULT NULL AFTER `keterangan`,
    ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`,
    ADD KEY `idx_distribusi_waktu` (`waktu_distribusi`);

-- Riwayat terdahulu tidak menyimpan jam perpindahan; gunakan waktu pembuatan sebagai fallback.
UPDATE `distribusi_barang`
SET `waktu_distribusi` = `created_at`
WHERE `waktu_distribusi` IS NULL;
