-- Cakupan approval Kaprodi berdasarkan program studi FIK.
-- Aman dijalankan ulang: akun tidak akan dihapus dan password yang sudah diganti tidak direset.

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `prodi` varchar(120) DEFAULT NULL AFTER `role`,
    ADD COLUMN IF NOT EXISTS `jenis_pengguna` varchar(20) DEFAULT NULL AFTER `prodi`;

CREATE INDEX IF NOT EXISTS `idx_users_role_prodi` ON `users` (`role`, `prodi`);

ALTER TABLE `peminjam`
    ADD COLUMN IF NOT EXISTS `prodi` varchar(120) DEFAULT NULL AFTER `jenis`;

CREATE INDEX IF NOT EXISTS `idx_peminjam_prodi` ON `peminjam` (`prodi`);

ALTER TABLE `peminjaman`
    ADD COLUMN IF NOT EXISTS `prodi` varchar(120) DEFAULT NULL AFTER `id_user`;

CREATE INDEX IF NOT EXISTS `idx_peminjaman_prodi_status` ON `peminjaman` (`prodi`, `status`);

UPDATE `peminjam` p
JOIN `users` u ON u.`nim_nip` = p.`nim_nip`
SET p.`prodi` = u.`prodi`,
    p.`jenis` = COALESCE(u.`jenis_pengguna`, p.`jenis`)
WHERE p.`prodi` IS NULL AND u.`prodi` IS NOT NULL;

UPDATE `peminjaman` pm
LEFT JOIN `users` u ON u.`id_user` = pm.`id_user`
LEFT JOIN `peminjam` p ON p.`id_peminjam` = pm.`id_peminjam`
SET pm.`prodi` = COALESCE(u.`prodi`, p.`prodi`)
WHERE pm.`prodi` IS NULL AND COALESCE(u.`prodi`, p.`prodi`) IS NOT NULL;

INSERT IGNORE INTO `users`
    (`nim_nip`, `nama_lengkap`, `email`, `password`, `role`, `prodi`, `jenis_pengguna`, `created_at`, `updated_at`)
VALUES
    ('KAPRODI.DKV', 'Kaprodi S1 Desain Komunikasi Visual', 'kaprodi.dkv@fik.telkomuniversity.ac.id', '$2y$10$UxnJn3eqoo5ImFma1UzrcO5xrh49pp7dOsokT1PYMxtA6koZ4Tiz.', 'kaprodi', 'S1 Desain Komunikasi Visual (DKV)', 'Staff', NOW(), NOW()),
    ('KAPRODI.DI', 'Kaprodi S1 Desain Interior', 'kaprodi.di@fik.telkomuniversity.ac.id', '$2y$10$zSHiHYDUwyflowB7ICkrLe0eTPMHmfDSyn8zUL.W3bYlsL4foa47K', 'kaprodi', 'S1 Desain Interior', 'Staff', NOW(), NOW()),
    ('KAPRODI.DP', 'Kaprodi S1 Desain Produk', 'kaprodi.dp@fik.telkomuniversity.ac.id', '$2y$10$gJLwCcDOfV6v4p9h5mRSUeFR25v5AC1x7GhpJV5BMZqErKV9bPQIu', 'kaprodi', 'S1 Desain Produk', 'Staff', NOW(), NOW()),
    ('KAPRODI.KTM', 'Kaprodi S1 Kriya Tekstil dan Mode', 'kaprodi.ktm@fik.telkomuniversity.ac.id', '$2y$10$.ZCWWpi3lUVk43FzjYOYzu8QaLWLp5EObg3jr3Eqe28lFxjwFUrf6', 'kaprodi', 'S1 Kriya Tekstil dan Mode', 'Staff', NOW(), NOW()),
    ('KAPRODI.SR', 'Kaprodi S1 Seni Rupa', 'kaprodi.sr@fik.telkomuniversity.ac.id', '$2y$10$AmPXk8wJyekTb3dh1KniqO0fEvd36DoUFEBdQ8R5Jl1IuNrTg5VxS', 'kaprodi', 'S1 Seni Rupa', 'Staff', NOW(), NOW()),
    ('KAPRODI.FA', 'Kaprodi S1 Film & Animasi', 'kaprodi.fa@fik.telkomuniversity.ac.id', '$2y$10$zGycpvhveiE7xOt59PtyM.CX555hRZGg3wyDkadY1pbaJLgpuYNk2', 'kaprodi', 'S1 Film & Animasi', 'Staff', NOW(), NOW()),
    ('KAPRODI.S2MD', 'Kaprodi S2 Magister Desain', 'kaprodi.s2md@fik.telkomuniversity.ac.id', '$2y$10$7thgzqwsVPAFRqaX0n0dn.rKntgq1J/2uCiDXivpxT//e1idFwbp.', 'kaprodi', 'S2 Magister Desain', 'Staff', NOW(), NOW());

UPDATE `users`
SET `nama_lengkap` = CASE `nim_nip`
        WHEN 'KAPRODI.DKV' THEN 'Kaprodi S1 Desain Komunikasi Visual'
        WHEN 'KAPRODI.DI' THEN 'Kaprodi S1 Desain Interior'
        WHEN 'KAPRODI.DP' THEN 'Kaprodi S1 Desain Produk'
        WHEN 'KAPRODI.KTM' THEN 'Kaprodi S1 Kriya Tekstil dan Mode'
        WHEN 'KAPRODI.SR' THEN 'Kaprodi S1 Seni Rupa'
        WHEN 'KAPRODI.FA' THEN 'Kaprodi S1 Film & Animasi'
        WHEN 'KAPRODI.S2MD' THEN 'Kaprodi S2 Magister Desain'
    END,
    `role` = 'kaprodi',
    `prodi` = CASE `nim_nip`
        WHEN 'KAPRODI.DKV' THEN 'S1 Desain Komunikasi Visual (DKV)'
        WHEN 'KAPRODI.DI' THEN 'S1 Desain Interior'
        WHEN 'KAPRODI.DP' THEN 'S1 Desain Produk'
        WHEN 'KAPRODI.KTM' THEN 'S1 Kriya Tekstil dan Mode'
        WHEN 'KAPRODI.SR' THEN 'S1 Seni Rupa'
        WHEN 'KAPRODI.FA' THEN 'S1 Film & Animasi'
        WHEN 'KAPRODI.S2MD' THEN 'S2 Magister Desain'
    END,
    `jenis_pengguna` = 'Staff',
    `updated_at` = NOW()
WHERE `nim_nip` IN ('KAPRODI.DKV', 'KAPRODI.DI', 'KAPRODI.DP', 'KAPRODI.KTM', 'KAPRODI.SR', 'KAPRODI.FA', 'KAPRODI.S2MD');
