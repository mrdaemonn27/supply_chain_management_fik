<?php
$stats = isset($stats) && is_array($stats) ? $stats : [];
$get_stat = function ($key) use ($stats) {
    return isset($stats[$key]) ? (int) $stats[$key] : 0;
};
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);

$menus = [
    [
        'title' => 'Master Data',
        'desc' => 'Kelola barang/aset, stok, kondisi, dan foto inventaris.',
        'url' => base_url('index.php/admin/barang'),
        'icon' => 'bi-boxes',
        'class' => 'tone-orange',
        'metric' => $get_stat('total_aset') . ' jenis aset',
    ],
    [
        'title' => 'Peminjaman',
        'desc' => 'Pantau transaksi peminjaman aktif, riwayat, dan pengembalian.',
        'url' => base_url('index.php/admin/peminjaman'),
        'icon' => 'bi-clipboard-data',
        'class' => 'tone-green',
        'metric' => $get_stat('peminjaman_aktif') . ' aktif',
    ],
    [
        'title' => 'Approval',
        'desc' => 'Cek stok fisik lalu teruskan pengajuan ke Kaur.',
        'url' => base_url('index.php/admin/approval'),
        'icon' => 'bi-patch-check',
        'class' => 'tone-red',
        'metric' => $get_stat('menunggu_persetujuan') . ' menunggu',
        'badge' => $get_stat('menunggu_persetujuan'),
    ],
    [
        'title' => 'Dokumen',
        'desc' => 'Unggah dokumen SOP, berita acara, bukti, atau arsip peminjaman.',
        'url' => base_url('index.php/admin/dokumen'),
        'icon' => 'bi-file-earmark-arrow-up',
        'class' => 'tone-purple',
        'metric' => $get_stat('total_dokumen') . ' dokumen',
    ],
    [
        'title' => 'Ruangan',
        'desc' => 'Atur data ruangan/lab dan foto ruangan yang tampil di dashboard.',
        'url' => base_url('index.php/admin/ruangan'),
        'icon' => 'bi-door-open',
        'class' => 'tone-blue',
        'metric' => $get_stat('total_ruangan') . ' ruangan',
    ],
    [
        'title' => 'Maintenance Barang',
        'desc' => 'Catat perawatan, kondisi setelah maintenance, dan riwayat aset.',
        'url' => base_url('index.php/admin/maintenance'),
        'icon' => 'bi-tools',
        'class' => 'tone-yellow',
        'metric' => $get_stat('total_maintenance') . ' catatan',
    ],
    [
        'title' => 'Distribusi Barang',
        'desc' => 'Pindahkan lokasi aset antar ruangan dan simpan catatan distribusi.',
        'url' => base_url('index.php/admin/distribusi'),
        'icon' => 'bi-truck',
        'class' => 'tone-cyan',
        'metric' => $get_stat('total_distribusi') . ' distribusi',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dashboard Laboran' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; font-family: 'Poppins', sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; border-bottom: 4px solid #ea5b1a; color: #fff; }
        .brand-mark { width: 42px; height: 42px; border-radius: 10px; background: rgba(234, 91, 26, 0.15); color: #ea5b1a; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .summary-card, .menu-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(32, 33, 36, 0.05); }
        .summary-card { padding: 18px; min-height: 106px; }
        .summary-value { font-size: 1.65rem; font-weight: 700; line-height: 1; }
        .summary-label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .menu-card { position: relative; height: 100%; text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; overflow: hidden; }
        .menu-card:hover { transform: translateY(-4px); border-color: rgba(234, 91, 26, .38); box-shadow: 0 14px 34px rgba(32, 33, 36, 0.09); color: inherit; }
        .menu-card .body { padding: 22px; flex: 1; }
        .module-icon { width: 54px; height: 54px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.55rem; margin-bottom: 18px; }
        .menu-card h5 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
        .menu-card p { color: #6c757d; font-size: .86rem; line-height: 1.55; margin-bottom: 18px; }
        .metric-row { border-top: 1px solid #eef0f2; padding: 12px 22px; font-size: .8rem; color: #5f6368; display: flex; justify-content: space-between; align-items: center; }
        .tone-orange { background: rgba(234, 91, 26, .12); color: #c24a13; }
        .tone-green { background: rgba(25, 135, 84, .12); color: #198754; }
        .tone-red { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .tone-purple { background: rgba(111, 66, 193, .12); color: #6f42c1; }
        .tone-blue { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .tone-yellow { background: rgba(245, 158, 11, .16); color: #a16207; }
        .tone-cyan { background: rgba(13, 202, 240, .14); color: #087990; }
        .notify-badge { position: absolute; top: 16px; right: 16px; border-radius: 999px; padding: 5px 10px; background: #dc3545; color: #fff; font-size: .75rem; font-weight: 700; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        @media (max-width: 767.98px) {
            .topbar .actions { width: 100%; margin-top: 12px; }
            .topbar .actions .btn { flex: 1; }
            .topbar .actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
        }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-person-workspace"></i></span>
                    <div>
                        <div class="fw-bold">Panel Laboran</div>
                        <div class="small text-white-50">Supply Chain Management FIK</div>
                    </div>
                </div>
                <div class="actions d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                            <div class="fw-bold px-2 py-1">Notifikasi</div>
                            <?php if (empty($notif_items)): ?>
                                <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                                <a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <span class="d-none d-lg-inline small text-white-50 me-2">Role: <strong class="text-white"><?= isset($user_role) ? $user_role : 'Laboran' ?></strong></span>
                    <a href="<?= base_url('index.php/admin/peminjaman/export_pengajuan_acc') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview ACC</a>
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm rounded-pill px-3 text-white" style="background:#ea5b1a;"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4 py-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-2">Selamat Datang, Laboran</h1>
                <p class="text-muted mb-0">Kelola operasional aset, peminjaman, dokumen, ruangan, maintenance, dan distribusi barang dari satu dashboard.</p>
            </div>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
        </div>

        <section class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="summary-card"><div class="summary-value"><?= $get_stat('total_aset') ?></div><div class="summary-label">Jenis aset</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-card"><div class="summary-value"><?= $get_stat('total_aset_fisik') ?></div><div class="summary-label">Total fisik barang</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-card"><div class="summary-value"><?= $get_stat('menunggu_persetujuan') ?></div><div class="summary-label">Menunggu pengecekan</div></div></div>
            <div class="col-6 col-lg-3"><div class="summary-card"><div class="summary-value"><?= $get_stat('peminjaman_aktif') ?></div><div class="summary-label">Sedang dipinjam</div></div></div>
        </section>

        <section class="row g-3 g-lg-4">
            <?php foreach ($menus as $menu): ?>
                <div class="col-sm-6 col-xl-3">
                    <a href="<?= $menu['url'] ?>" class="menu-card">
                        <?php if (!empty($menu['badge'])): ?>
                            <span class="notify-badge"><?= (int) $menu['badge'] ?></span>
                        <?php endif; ?>
                        <div class="body">
                            <div class="module-icon <?= $menu['class'] ?>"><i class="bi <?= $menu['icon'] ?>"></i></div>
                            <h5><?= $menu['title'] ?></h5>
                            <p><?= $menu['desc'] ?></p>
                        </div>
                        <div class="metric-row">
                            <span><?= $menu['metric'] ?></span>
                            <i class="bi bi-arrow-right"></i>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
