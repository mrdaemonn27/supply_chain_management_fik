USE `peminjaman_aset`;

DELETE d
FROM `peminjaman_detail` d
LEFT JOIN `peminjaman` p ON p.`id_peminjaman` = d.`id_peminjaman`
WHERE p.`id_peminjaman` IS NULL;

ALTER TABLE `peminjaman_detail`
  ADD CONSTRAINT `fk_peminjaman_detail_peminjaman`
  FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`)
  ON DELETE CASCADE;

ALTER TABLE `peminjaman_detail`
  ADD CONSTRAINT `fk_peminjaman_detail_aset`
  FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`)
  ON DELETE CASCADE;
