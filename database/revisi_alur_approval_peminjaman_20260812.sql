-- Alur peminjaman: Peminjam -> Kaprodi -> Laboran -> Kaur -> finalisasi QR Laboran.
ALTER TABLE `peminjaman`
  MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu ACC Kaprodi';

ALTER TABLE `peminjaman`
  ADD COLUMN IF NOT EXISTS `status_kaprodi` varchar(20) NOT NULL DEFAULT 'Pending' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `catatan_kaprodi` text DEFAULT NULL AFTER `status_kaprodi`,
  ADD COLUMN IF NOT EXISTS `tgl_approve_kaprodi` datetime DEFAULT NULL AFTER `catatan_kaprodi`,
  ADD COLUMN IF NOT EXISTS `id_approver_kaprodi` int(11) DEFAULT NULL AFTER `tgl_approve_kaprodi`;

UPDATE `peminjaman`
SET `status_kaprodi` = 'Disetujui'
WHERE `status_kaprodi` = 'Pending'
  AND `status` NOT IN ('Menunggu ACC Kaprodi', 'Ditolak');
