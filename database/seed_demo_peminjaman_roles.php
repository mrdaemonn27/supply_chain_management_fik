<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli('localhost', 'root', '', 'peminjaman_aset');
$db->set_charset('utf8mb4');

$db->query("ALTER TABLE `peminjaman` MODIFY `status` varchar(80) NOT NULL DEFAULT 'Menunggu Pengecekan Laboran'");
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

function one(mysqli $db, string $sql): array
{
    $row = $db->query($sql)->fetch_assoc();
    if (!$row) {
        throw new RuntimeException('Data pendukung tidak ditemukan: ' . $sql);
    }
    return $row;
}

$user = one($db, "SELECT id_user, nim_nip, nama_lengkap FROM users WHERE role = 'user' ORDER BY id_user DESC LIMIT 1");
$laboran = one($db, "SELECT id_user FROM users WHERE role IN ('laboran','admin') ORDER BY FIELD(role, 'laboran', 'admin'), id_user LIMIT 1");
$kaur = one($db, "SELECT id_user FROM users WHERE role = 'kaur' ORDER BY id_user DESC LIMIT 1");

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
$result = $db->query("SELECT id_aset, nama_aset FROM aset WHERE jumlah_tersedia > 0 ORDER BY id_aset LIMIT 3");
while ($row = $result->fetch_assoc()) {
    $assets[] = $row;
}
if (count($assets) < 3) {
    throw new RuntimeException('Minimal butuh 3 aset tersedia untuk demo.');
}

$today = '2026-07-15';
$returnDate = '2026-07-18';
$now = date('Y-m-d H:i:s');
$suffix = date('His');
$rows = [
    [
        'group_id' => "DEMO-ROLE-{$suffix}-01",
        'asset' => $assets[0],
        'keperluan' => 'Demo 1 - User mengajukan peminjaman, menunggu pengecekan Laboran.',
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
        'group_id' => "DEMO-ROLE-{$suffix}-02",
        'asset' => $assets[1],
        'keperluan' => 'Demo 2 - Laboran sudah cek stok dan meneruskan ke Kaur.',
        'status' => 'Menunggu ACC Kaur',
        'status_laboran' => 'Disetujui',
        'catatan_laboran' => 'Stok fisik tersedia, diteruskan ke Kaur.',
        'tgl_laboran' => $now,
        'id_laboran' => (int) $laboran['id_user'],
        'status_kaur' => 'Pending',
        'catatan_kaur' => null,
        'tgl_kaur' => null,
        'id_kaur' => null,
    ],
    [
        'group_id' => "DEMO-ROLE-{$suffix}-03",
        'asset' => $assets[2],
        'keperluan' => 'Demo 3 - Kaur sudah ACC, QR aktif untuk pengambilan.',
        'status' => 'Disetujui (Menunggu Pengambilan)',
        'status_laboran' => 'Disetujui',
        'catatan_laboran' => 'Stok fisik tersedia, diteruskan ke Kaur.',
        'tgl_laboran' => $now,
        'id_laboran' => (int) $laboran['id_user'],
        'status_kaur' => 'Disetujui',
        'catatan_kaur' => 'Disetujui untuk demo QR aktif.',
        'tgl_kaur' => $now,
        'id_kaur' => (int) $kaur['id_user'],
    ],
];

$db->begin_transaction();
try {
    $stmt = $db->prepare("INSERT INTO peminjaman (
        group_id, id_aset, id_peminjam, id_user, jumlah_pinjam, tanggal_pinjam, tanggal_kembali_rencana,
        keperluan, status, status_laboran, catatan_laboran, tgl_approve_laboran, id_approver_laboran,
        status_kaur, catatan_kaur, tgl_approve_kaur, id_approver_kaur, kondisi_saat_pinjam,
        foto_bukti, created_at, updated_at
    ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Baik', 'demo_bukti.jpg', ?, ?)");

    foreach ($rows as $row) {
        $id_aset = (int) $row['asset']['id_aset'];
        $id_user = (int) $user['id_user'];
        $id_lab = $row['id_laboran'];
        $id_kaur = $row['id_kaur'];
        $stmt->bind_param(
            'siiisssssssisssiss',
            $row['group_id'],
            $id_aset,
            $id_peminjam,
            $id_user,
            $today,
            $returnDate,
            $row['keperluan'],
            $row['status'],
            $row['status_laboran'],
            $row['catatan_laboran'],
            $row['tgl_laboran'],
            $id_lab,
            $row['status_kaur'],
            $row['catatan_kaur'],
            $row['tgl_kaur'],
            $id_kaur,
            $now,
            $now
        );
        $stmt->execute();
    }

    $notif = $db->prepare("INSERT INTO notifikasi_progress (recipient_role, recipient_user_id, judul, pesan, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)");
    $notifications = [
        ['laboran', null, 'Demo: pengajuan baru', 'Demo 1 menunggu pengecekan Laboran.', 'http://localhost/supply_chain_management_fik/index.php/admin/approval'],
        ['kaur', null, 'Demo: menunggu ACC Kaur', 'Demo 2 sudah diteruskan Laboran dan menunggu ACC Kaur.', 'http://localhost/supply_chain_management_fik/index.php/kaur/dashboard#approval-peminjaman'],
        [null, (int) $user['id_user'], 'Demo: QR sudah aktif', 'Demo 3 sudah disetujui Kaur. QR muncul di riwayat peminjam.', 'http://localhost/supply_chain_management_fik/index.php/peminjaman/riwayat'],
    ];
    foreach ($notifications as $n) {
        $role = $n[0];
        $recipientUser = $n[1];
        $notif->bind_param('sissss', $role, $recipientUser, $n[2], $n[3], $n[4], $now);
        $notif->execute();
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

echo "Demo peminjaman berhasil dibuat untuk user {$user['nama_lengkap']} ({$user['nim_nip']}):" . PHP_EOL;
foreach ($rows as $row) {
    echo "- {$row['group_id']} | {$row['asset']['nama_aset']} | {$row['status']}" . PHP_EOL;
}
