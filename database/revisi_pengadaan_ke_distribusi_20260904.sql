-- Menyiapkan aset hasil persetujuan Kaur agar dapat langsung didistribusikan.
-- Aman dijalankan pada database lama; data dan foreign key ruangan dipertahankan.

ALTER TABLE `aset`
    ADD COLUMN IF NOT EXISTS `sumber_bast_id` int(11) DEFAULT NULL AFTER `qr_url`;

ALTER TABLE `aset`
    ADD COLUMN IF NOT EXISTS `sumber_pengajuan_id` int(11) DEFAULT NULL AFTER `sumber_bast_id`;

ALTER TABLE `aset`
    ADD COLUMN IF NOT EXISTS `sumber_pengajuan_item_id` int(11) DEFAULT NULL AFTER `sumber_pengajuan_id`;

ALTER TABLE `aset`
    MODIFY `id_ruangan` int(11) DEFAULT NULL;

ALTER TABLE `pengadaan_inventory_link`
    MODIFY `id_bast` int(11) DEFAULT NULL;

-- Jalankan penambahan index ini hanya bila database lama tidak mempunyai
-- pasangan id_pengajuan/id_item ganda.
SET @has_duplicate_inventory_link := (
    SELECT COUNT(*)
    FROM (
        SELECT `id_pengajuan`, `id_item`
        FROM `pengadaan_inventory_link`
        GROUP BY `id_pengajuan`, `id_item`
        HAVING COUNT(*) > 1
    ) duplicate_link
);
SET @has_inventory_index := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'pengadaan_inventory_link'
      AND index_name = 'uniq_pengajuan_item'
);
SET @inventory_index_sql := IF(
    @has_duplicate_inventory_link = 0 AND @has_inventory_index = 0,
    'ALTER TABLE `pengadaan_inventory_link` ADD UNIQUE KEY `uniq_pengajuan_item` (`id_pengajuan`, `id_item`)',
    'SELECT 1'
);
PREPARE inventory_index_statement FROM @inventory_index_sql;
EXECUTE inventory_index_statement;
DEALLOCATE PREPARE inventory_index_statement;
