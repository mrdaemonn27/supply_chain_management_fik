<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli('localhost', 'root', '', 'peminjaman_aset');
$db->set_charset('utf8mb4');

function table_exists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'] > 0;
}

function column_exists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'] > 0;
}

function one(mysqli $db, string $sql): array
{
    $row = $db->query($sql)->fetch_assoc();
    if (!$row) {
        throw new RuntimeException('Data pendukung tidak ditemukan: ' . $sql);
    }
    return $row;
}

$db->query("ALTER TABLE `peminjaman` MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu Pengecekan Laboran'");
if (!column_exists($db, 'peminjaman', 'id_user')) {
    $db->query("ALTER TABLE `peminjaman` ADD `id_user` int(11) DEFAULT NULL AFTER `id_peminjam`");
}

$db->query("CREATE TABLE IF NOT EXISTS `notifikasi_progress` (
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

if (!column_exists($db, 'kaprodi_pengajuan', 'jenis_pengajuan')) {
    $db->query("ALTER TABLE `kaprodi_pengajuan` ADD `jenis_pengajuan` enum('Barang','Jasa') NOT NULL DEFAULT 'Barang' AFTER `id_user`");
}
if (!column_exists($db, 'kaprodi_pengajuan', 'catatan_approval')) {
    $db->query("ALTER TABLE `kaprodi_pengajuan` ADD `catatan_approval` text DEFAULT NULL AFTER `catatan_alokasi`");
}
$db->query("ALTER TABLE `kaprodi_pengajuan` MODIFY `status` varchar(60) NOT NULL DEFAULT 'Pengajuan'");

$user = one($db, "SELECT id_user, nim_nip, nama_lengkap FROM users WHERE role = 'user' ORDER BY id_user DESC LIMIT 1");
$laboran = one($db, "SELECT id_user FROM users WHERE role IN ('laboran','admin') ORDER BY FIELD(role, 'laboran', 'admin'), id_user LIMIT 1");
$kaur = one($db, "SELECT id_user FROM users WHERE role = 'kaur' ORDER BY id_user DESC LIMIT 1");
$kaprodi = one($db, "SELECT id_user, nama_lengkap FROM users WHERE role = 'kaprodi' ORDER BY id_user DESC LIMIT 1");

$peminjam = $db->query("SELECT id_peminjam FROM peminjam WHERE nim_nip = '" . $db->real_escape_string($user['nim_nip']) . "' LIMIT 1")->fetch_assoc();
if (!$peminjam) {
    $stmt = $db->prepare("INSERT INTO peminjam (nama_peminjam, nim_nip, jenis) VALUES (?, ?, 'Mahasiswa')");
    $stmt->bind_param('ss', $user['nama_lengkap'], $user['nim_nip']);
    $stmt->execute();
    $id_peminjam = $stmt->insert_id;
} else {
    $id_peminjam = (int) $peminjam['id_peminjam'];
}

$assets = [];
$assetResult = $db->query("SELECT id_aset, nama_aset, kode_aset FROM aset WHERE jumlah_tersedia > 0 ORDER BY id_aset LIMIT 6");
while ($row = $assetResult->fetch_assoc()) {
    $assets[] = $row;
}
if (count($assets) < 3) {
    throw new RuntimeException('Minimal butuh 3 aset tersedia untuk demo.');
}

$now = date('Y-m-d H:i:s');
$today = '2026-07-15';
$returnDate = '2026-07-18';
$suffix = date('His');
$idUser = (int) $user['id_user'];
$idLaboran = (int) $laboran['id_user'];
$idKaur = (int) $kaur['id_user'];
$idKaprodi = (int) $kaprodi['id_user'];

$loans = [
    [
        'group_id' => "DEMO-FULL-{$suffix}-01",
        'asset' => $assets[3],
        'keperluan' => 'Demo penuh 1 - User mengajukan peminjaman, menunggu pengecekan Laboran.',
        'status' => 'Menunggu Pengecekan Laboran',
        'status_laboran' => 'Pending',
        'catatan_laboran' => null,
        'tgl_laboran' => null,
        'id_laboran' => null,
        'status_kaur' => 'Pending',
        'catatan_kaur' => null,
        'tgl_kaur' => null,
        'id_kaur' => null,
    ],
    [
        'group_id' => "DEMO-FULL-{$suffix}-02",
        'asset' => $assets[4],
        'keperluan' => 'Demo penuh 2 - Laboran sudah cek stok, Kaur sudah ACC, QR aktif untuk scanner.',
        'status' => 'Disetujui (Menunggu Pengambilan)',
        'status_laboran' => 'Disetujui',
        'catatan_laboran' => 'Stok fisik tersedia, diteruskan ke Kaur.',
        'tgl_laboran' => $now,
        'id_laboran' => $idLaboran,
        'status_kaur' => 'Disetujui',
        'catatan_kaur' => 'Disetujui untuk demo QR aktif.',
        'tgl_kaur' => $now,
        'id_kaur' => $idKaur,
    ],
];

$db->begin_transaction();
try {
    $loanStmt = $db->prepare("INSERT INTO peminjaman (
        group_id, id_aset, id_peminjam, id_user, jumlah_pinjam, tanggal_pinjam, tanggal_kembali_rencana,
        keperluan, status, status_laboran, catatan_laboran, tgl_approve_laboran, id_approver_laboran,
        status_kaur, catatan_kaur, tgl_approve_kaur, id_approver_kaur, kondisi_saat_pinjam,
        foto_bukti, created_at, updated_at
    ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Baik', 'demo_full_bukti.jpg', ?, ?)");

    foreach ($loans as $loan) {
        $idAset = (int) $loan['asset']['id_aset'];
        $loanStmt->bind_param(
            'siiisssssssisssiss',
            $loan['group_id'],
            $idAset,
            $id_peminjam,
            $idUser,
            $today,
            $returnDate,
            $loan['keperluan'],
            $loan['status'],
            $loan['status_laboran'],
            $loan['catatan_laboran'],
            $loan['tgl_laboran'],
            $loan['id_laboran'],
            $loan['status_kaur'],
            $loan['catatan_kaur'],
            $loan['tgl_kaur'],
            $loan['id_kaur'],
            $now,
            $now
        );
        $loanStmt->execute();
    }

    $kodePengajuan = "DEMO-PBJ-{$suffix}-03";
    $pengajuanStmt = $db->prepare("INSERT INTO kaprodi_pengajuan (
        kode_pengajuan, id_user, jenis_pengajuan, nama_prodi, nama_pengajuan, kebutuhan_lab,
        anak_perusahaan, status, catatan_negosiasi, catatan_alokasi, catatan_approval,
        created_at, updated_at
    ) VALUES (?, ?, 'Barang', 'S1 Desain Komunikasi Visual', 'Demo Pengajuan Kamera Studio', 'Kebutuhan kamera untuk praktikum studio dan dokumentasi karya mahasiswa.', NULL, 'BAST', 'Negosiasi sudah Deal oleh Kaur.', 'Sisa anggaran dialokasikan ke aksesoris studio.', 'Disetujui Kaur untuk proses BAST.', ?, ?)");
    $pengajuanStmt->bind_param('siss', $kodePengajuan, $idKaprodi, $now, $now);
    $pengajuanStmt->execute();
    $idPengajuan = $pengajuanStmt->insert_id;

    $itemName = 'Demo Kamera Mirrorless Studio';
    $itemStmt = $db->prepare("INSERT INTO kaprodi_pengajuan_item (
        id_pengajuan, no_urut, uraian_barang, vol, satuan, harga_penawaran_sat, link_penawaran,
        hasil_negosiasi_vol, hasil_negosiasi_sat, garansi, created_at
    ) VALUES (?, 1, ?, 2, 'unit', 15000000, 'https://vendor.example/demo-kamera', 2, 13500000, '1 tahun', ?)");
    $itemStmt->bind_param('iss', $idPengajuan, $itemName, $now);
    $itemStmt->execute();
    $idItem = $itemStmt->insert_id;

    $vendor = 'PT Demo Visual Kreatif';
    $catatanNego = 'Harga turun dari Rp 15.000.000 menjadi Rp 13.500.000 per unit.';
    $negoStmt = $db->prepare("INSERT INTO pengadaan_negosiasi (
        sumber, id_pengajuan, id_item, vendor, harga_awal, harga_negosiasi, volume_negosiasi,
        garansi, catatan, status, created_by, created_at
    ) VALUES ('kaprodi', ?, ?, ?, 15000000, 13500000, 2, '1 tahun', ?, 'Deal', ?, ?)");
    $negoStmt->bind_param('iissis', $idPengajuan, $idItem, $vendor, $catatanNego, $idKaur, $now);
    $negoStmt->execute();

    $nomorBast = "BAST-DEMO-{$suffix}";
    $bastStmt = $db->prepare("INSERT INTO pengadaan_bast (
        id_pengajuan, nomor_bast, tanggal_bast, jenis_bast, file_bast, catatan, input_by, inventory_processed_at, created_at
    ) VALUES (?, ?, ?, 'Barang', NULL, 'Demo BAST dari Logistik, diinput untuk pengujian dashboard.', ?, ?, ?)");
    $bastStmt->bind_param('ississ', $idPengajuan, $nomorBast, $today, $idLaboran, $now, $now);
    $bastStmt->execute();

    $notifStmt = $db->prepare("INSERT INTO notifikasi_progress (recipient_role, recipient_user_id, judul, pesan, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $notifications = [
        ['laboran', null, 'Demo penuh: pengajuan peminjaman baru', 'Data 1 menunggu pengecekan Laboran.', 'http://localhost/supply_chain_management_fik/index.php/admin/approval'],
        [null, $idUser, 'Demo penuh: QR aktif', 'Data 2 sudah di-ACC Kaur. QR muncul di riwayat peminjam.', 'http://localhost/supply_chain_management_fik/index.php/peminjaman/riwayat'],
        ['kaur', null, 'Demo penuh: pengajuan barang Kaprodi', 'Data 3 adalah pengajuan barang Kaprodi dengan negosiasi Deal dan BAST.', 'http://localhost/supply_chain_management_fik/index.php/kaur/dashboard#laporan'],
    ];
    foreach ($notifications as $notif) {
        $role = $notif[0];
        $recipientUser = $notif[1];
        $notifStmt->bind_param('sissss', $role, $recipientUser, $notif[2], $notif[3], $notif[4], $now);
        $notifStmt->execute();
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "Demo 3 data berhasil dibuat:" . PHP_EOL;
foreach ($loans as $loan) {
    echo "- {$loan['group_id']} | Peminjaman | {$loan['asset']['nama_aset']} | {$loan['status']}" . PHP_EOL;
}
echo "- {$kodePengajuan} | Pengajuan Barang Kaprodi | {$itemName} | BAST + Negosiasi Deal" . PHP_EOL;
