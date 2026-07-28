<?php
$overview = isset($dashboard_overview) && is_array($dashboard_overview) ? $dashboard_overview : [];
$overview_stats = isset($overview['stats']) && is_array($overview['stats']) ? $overview['stats'] : [];
$get_stat = static function ($key) use ($overview_stats) {
    return (int) ($overview_stats[$key] ?? 0);
};
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);

$menus = [
    ['title' => 'Master Data', 'desc' => 'Kelola barang, stok, kondisi, dan foto inventaris.', 'url' => base_url('index.php/admin/barang'), 'icon' => 'bi-boxes'],
    ['title' => 'Peminjaman', 'desc' => 'Pantau pengajuan, finalisasi QR, dan serah barang.', 'url' => base_url('index.php/admin/peminjaman'), 'icon' => 'bi-clipboard-data'],
    ['title' => 'Pengembalian', 'desc' => 'Scan QR transaksi dan validasi barang yang kembali.', 'url' => base_url('index.php/admin/pengembalian'), 'icon' => 'bi-arrow-counterclockwise'],
    ['title' => 'Approval', 'desc' => 'Cek stok fisik lalu teruskan pengajuan ke Kaur.', 'url' => base_url('index.php/admin/approval'), 'icon' => 'bi-patch-check'],
    ['title' => 'Dokumen', 'desc' => 'Unggah SOP, berita acara, bukti, atau arsip peminjaman.', 'url' => base_url('index.php/admin/dokumen'), 'icon' => 'bi-file-earmark-arrow-up'],
    ['title' => 'Ruangan', 'desc' => 'Atur data ruangan/lab dan foto ruangan.', 'url' => base_url('index.php/admin/ruangan'), 'icon' => 'bi-door-open'],
    ['title' => 'Maintenance Barang', 'desc' => 'Catat perawatan, kondisi, dan riwayat aset.', 'url' => base_url('index.php/admin/maintenance'), 'icon' => 'bi-tools'],
    ['title' => 'Distribusi Barang', 'desc' => 'Pindahkan lokasi aset dan simpan catatan distribusi.', 'url' => base_url('index.php/admin/distribusi'), 'icon' => 'bi-truck'],
    ['title' => 'Blokir Pengguna', 'desc' => 'Batasi peminjam bermasalah dan simpan histori blokir.', 'url' => base_url('index.php/admin/blokir'), 'icon' => 'bi-shield-lock'],
];
$stat_cards = [
    ['label' => 'Total Aset', 'key' => 'total_aset', 'icon' => 'bi-boxes', 'note' => 'Jenis aset terdaftar', 'muted' => false],
    ['label' => 'Total Unit Barang', 'key' => 'total_unit_barang', 'icon' => 'bi-stack', 'note' => 'Jumlah unit inventaris', 'muted' => false],
    ['label' => 'Barang Dipinjam', 'key' => 'barang_dipinjam', 'icon' => 'bi-box-arrow-up-right', 'note' => 'Unit sedang dipakai', 'muted' => false],
    ['label' => 'Menunggu Approval', 'key' => 'menunggu_approval', 'icon' => 'bi-hourglass-split', 'note' => 'Perlu pengecekan awal', 'muted' => false],
    ['label' => 'Menunggu Pengembalian', 'key' => 'menunggu_pengembalian', 'icon' => 'bi-arrow-return-left', 'note' => 'Transaksi masih aktif', 'muted' => false],
    ['label' => 'Barang Maintenance', 'key' => 'barang_maintenance', 'icon' => 'bi-tools', 'note' => 'Kondisi maintenance', 'muted' => true],
    ['label' => 'Barang Rusak', 'key' => 'barang_rusak', 'icon' => 'bi-exclamation-triangle', 'note' => 'Perlu tindak lanjut', 'muted' => true],
    ['label' => 'Barang Hilang', 'key' => 'barang_hilang', 'icon' => 'bi-question-circle', 'note' => 'Perlu pencatatan', 'muted' => true],
    ['label' => 'Distribusi Barang', 'key' => 'distribusi_barang', 'icon' => 'bi-truck', 'note' => 'Riwayat perpindahan aset', 'muted' => false],
    ['label' => 'Pengguna Diblokir', 'key' => 'pengguna_diblokir', 'icon' => 'bi-shield-lock', 'note' => 'Status blokir aktif', 'muted' => true],
];
$quick_actions = [
    ['label' => 'Master Data', 'url' => base_url('index.php/admin/barang'), 'icon' => 'bi-boxes'],
    ['label' => 'Peminjaman', 'url' => base_url('index.php/admin/peminjaman'), 'icon' => 'bi-clipboard-data'],
    ['label' => 'Pengembalian', 'url' => base_url('index.php/admin/pengembalian'), 'icon' => 'bi-arrow-counterclockwise'],
    ['label' => 'Approval', 'url' => base_url('index.php/admin/approval'), 'icon' => 'bi-patch-check'],
    ['label' => 'Dokumen', 'url' => base_url('index.php/admin/dokumen'), 'icon' => 'bi-file-earmark-arrow-up'],
    ['label' => 'Ruangan', 'url' => base_url('index.php/admin/ruangan'), 'icon' => 'bi-door-open'],
    ['label' => 'Maintenance Barang', 'url' => base_url('index.php/admin/maintenance'), 'icon' => 'bi-tools'],
    ['label' => 'Distribusi Barang', 'url' => base_url('index.php/admin/distribusi'), 'icon' => 'bi-truck'],
    ['label' => 'Blokir Pengguna', 'url' => base_url('index.php/admin/blokir'), 'icon' => 'bi-shield-lock'],
    ['label' => 'Scan QR', 'url' => base_url('index.php/admin/peminjaman/scanner'), 'icon' => 'bi-qr-code-scan'],
];
$recent_activity = isset($overview['recent_activity']) && is_array($overview['recent_activity']) ? $overview['recent_activity'] : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Laboran') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
        .brand-mark { width: 42px; height: 42px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #ea5b1a; background: rgba(234, 91, 26, .15); font-size: 1.35rem; }
        .scm-dashboard-laboran .topbar-actions { margin-left: auto; }
        .theme-toggle { flex: 0 0 38px !important; height: 38px; padding: 0 !important; width: 38px; }
        .laboran-overview-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
        .laboran-overview-heading h1 { margin: 0; font-size: clamp(1.45rem, 2vw, 2rem); letter-spacing: -.02em; }
        .laboran-overview-heading p { margin: .35rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .88rem; }
        .laboran-stat-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .8rem; margin-bottom: .9rem; }
        .laboran-stat-card { min-width: 0; min-height: 132px; padding: 1rem; border: 1px solid var(--scm-border, #292d30); border-top: 2px solid var(--scm-orange, #ff7900); border-radius: 12px; background: var(--scm-surface, #121415); box-shadow: 0 8px 22px rgba(0, 0, 0, .12); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .laboran-stat-card:hover { transform: translateY(-3px); border-color: rgba(255, 121, 0, .65); box-shadow: 0 12px 28px rgba(0, 0, 0, .2); }
        .laboran-stat-card.is-muted { border-top-color: #8b949e; }
        .laboran-stat-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: .75rem; border: 1px solid rgba(255, 121, 0, .6); border-radius: 9px; color: var(--scm-orange, #ff7900); }
        .laboran-stat-card.is-muted .laboran-stat-icon { border-color: #68717a; color: #aeb6bd; }
        .laboran-stat-label { color: var(--scm-muted, #a8adb5); font-size: .72rem; line-height: 1.35; }
        .laboran-stat-value { margin-top: .18rem; color: var(--scm-text, #f7f7f7); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 700; line-height: 1.15; }
        .laboran-stat-note { margin-top: .45rem; color: var(--scm-muted, #a8adb5); font-size: .66rem; }
        .laboran-chart-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr); gap: .9rem; margin-bottom: .9rem; }
        .laboran-chart-panel, .laboran-activity-panel, .laboran-quick-panel { min-width: 0; border: 1px solid var(--scm-border, #292d30); border-radius: 12px; background: var(--scm-surface, #121415); box-shadow: 0 8px 22px rgba(0, 0, 0, .12); }
        .laboran-chart-panel { min-height: 310px; padding: 1rem; }
        .laboran-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .7rem; }
        .laboran-panel-heading h2 { margin: 0; color: var(--scm-text, #f7f7f7); font-size: .92rem; font-weight: 700; }
        .laboran-panel-heading p { margin: .22rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .7rem; }
        .laboran-chart-wrap { position: relative; height: 245px; }
        .laboran-chart-wrap canvas { height: 100% !important; max-width: 100%; width: 100% !important; }
        .laboran-chart-fallback { display: none; height: 100%; align-items: center; justify-content: center; color: var(--scm-muted, #a8adb5); text-align: center; }
        .laboran-bottom-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr); gap: .9rem; }
        .laboran-activity-panel, .laboran-quick-panel { padding: 1rem; }
        .laboran-activity-list { max-height: 350px; overflow-y: auto; padding-right: .2rem; }
        .laboran-activity-item { display: flex; align-items: flex-start; gap: .7rem; padding: .75rem 0; border-bottom: 1px solid var(--scm-border, #292d30); color: inherit; text-decoration: none; }
        .laboran-activity-item:last-child { border-bottom: 0; }
        .laboran-activity-item:hover .laboran-activity-title { color: var(--scm-orange, #ff7900); }
        .laboran-activity-icon { width: 32px; height: 32px; flex: 0 0 32px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 121, 0, .48); border-radius: 8px; color: var(--scm-orange, #ff7900); background: rgba(255, 121, 0, .09); }
        .laboran-activity-title { color: var(--scm-text, #f7f7f7); font-size: .75rem; font-weight: 700; transition: color .18s ease; }
        .laboran-activity-description, .laboran-activity-time { color: var(--scm-muted, #a8adb5); font-size: .68rem; }
        .laboran-activity-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }
        .laboran-activity-status { display: inline-flex; padding: .15rem .4rem; border-radius: 999px; background: rgba(255, 121, 0, .12); color: var(--scm-orange, #ff7900); font-size: .62rem; font-weight: 700; }
        .laboran-quick-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .laboran-quick-link { display: flex; align-items: center; gap: .5rem; min-height: 48px; padding: .65rem .7rem; border: 1px solid var(--scm-border, #292d30); border-radius: 9px; color: var(--scm-text, #f7f7f7); background: rgba(255, 255, 255, .015); font-size: .72rem; font-weight: 600; text-decoration: none; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
        .laboran-quick-link:hover { color: var(--scm-text, #f7f7f7); border-color: rgba(255, 121, 0, .7); background: rgba(255, 121, 0, .08); transform: translateY(-1px); }
        .laboran-quick-link i { color: var(--scm-orange, #ff7900); font-size: 1rem; }
        html.scm-theme-light {
            --scm-bg: #f3f4f6;
            --scm-surface: #ffffff;
            --scm-surface-strong: #eef0f2;
            --scm-border: #dfe3e6;
            --scm-text: #1c2024;
            --scm-muted: #68727b;
            --scm-orange-soft: rgba(234, 91, 26, .1);
        }
        html.scm-theme-light .scm-dashboard { background: var(--scm-bg) !important; color: var(--scm-text) !important; }
        html.scm-theme-light .scm-dashboard .dashboard-sidebar { background: #ffffff; border-color: #e3e6e8; }
        html.scm-theme-light .scm-dashboard .sidebar-link { color: #59636b; }
        html.scm-theme-light .scm-dashboard .sidebar-link i { color: #7e878e; }
        html.scm-theme-light .scm-dashboard .sidebar-link:hover, html.scm-theme-light .scm-dashboard .sidebar-link.active { color: #ffffff; }
        html.scm-theme-light .scm-dashboard .sidebar-footer { border-color: #e3e6e8; }
        html.scm-theme-light .scm-dashboard .topbar { background: #ffffff !important; border-color: #e3e6e8 !important; box-shadow: 0 5px 18px rgba(35, 42, 47, .06); }
        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light { color: #4e5961; border-color: #cbd2d7; }
        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light:hover { color: #1c2024; background: #f1f3f4; border-color: #aeb7bd; }
        html.scm-theme-light .scm-dashboard main, html.scm-theme-light .scm-dashboard .dashboard-content { background: var(--scm-bg); }
        html.scm-theme-light .scm-dashboard .dropdown-menu { color: var(--scm-text); background: #ffffff; border-color: var(--scm-border) !important; }
        html.scm-theme-light .scm-dashboard .dropdown-item { color: #39444b; }
        html.scm-theme-light .scm-dashboard .dropdown-item:hover, html.scm-theme-light .scm-dashboard .dropdown-item:focus { color: #1c2024; background: #f0f2f3; }
        html.scm-theme-light .scm-dashboard-laboran .laboran-stat-card, html.scm-theme-light .scm-dashboard-laboran .laboran-chart-panel, html.scm-theme-light .scm-dashboard-laboran .laboran-activity-panel, html.scm-theme-light .scm-dashboard-laboran .laboran-quick-panel { background: #ffffff; border-color: var(--scm-border); box-shadow: 0 8px 22px rgba(36, 43, 48, .06); }
        html.scm-theme-light .scm-dashboard-laboran .laboran-stat-value, html.scm-theme-light .scm-dashboard-laboran .laboran-panel-heading h2, html.scm-theme-light .scm-dashboard-laboran .laboran-activity-title, html.scm-theme-light .scm-dashboard-laboran .laboran-quick-link { color: #1c2024; }
        html.scm-theme-light .scm-dashboard-laboran .laboran-quick-link { background: #f7f8f9; }
        html.scm-theme-light .scm-dashboard-laboran .laboran-quick-link:hover { color: #1c2024; background: rgba(234, 91, 26, .08); }
        html.scm-theme-light .scm-dashboard-laboran .laboran-activity-item { border-color: rgba(35, 42, 47, .1); }
        @media (max-width: 1399.98px) { .laboran-stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (max-width: 1199.98px) { .laboran-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 991.98px) { .laboran-overview-heading { align-items: stretch; flex-direction: column; } .laboran-chart-grid, .laboran-bottom-grid { grid-template-columns: 1fr; } }
        @media (max-width: 767.98px) { .scm-dashboard-laboran .topbar-actions { width: 100%; flex-wrap: wrap; justify-content: flex-end; } }
        @media (max-width: 575.98px) { .laboran-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; } .laboran-stat-card { min-height: 118px; padding: .8rem; } .laboran-stat-icon { margin-bottom: .55rem; } .laboran-quick-grid { grid-template-columns: 1fr; } .laboran-chart-panel, .laboran-activity-panel, .laboran-quick-panel { padding: .8rem; } }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body class="scm-dashboard scm-dashboard-laboran">
    <aside class="dashboard-sidebar" aria-label="Navigasi Panel Laboran">
        <a class="sidebar-brand" href="<?= base_url('index.php/admin/dashboard') ?>">
            <span class="sidebar-brand-mark"><i class="bi bi-person-workspace"></i></span>
            <span><strong>SCM FIK</strong><small>Panel Laboran</small></span>
        </a>
        <div class="sidebar-caption">Operasional</div>
        <nav class="sidebar-nav">
            <a class="sidebar-link active" href="<?= base_url('index.php/admin/dashboard') ?>"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
            <?php foreach ($menus as $menu): ?>
                <a class="sidebar-link" href="<?= $menu['url'] ?>"><i class="bi <?= html_escape($menu['icon']) ?>"></i><span><?= html_escape($menu['title']) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>System operational</span></div>
    </aside>
    <div class="dashboard-content">
        <header class="topbar sticky-top">
            <div class="container-fluid px-3 px-lg-4 py-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="dashboard-topbar-brand d-flex align-items-center gap-3">
                        <span class="brand-mark"><i class="bi bi-person-workspace"></i></span>
                        <div><div class="fw-bold">Panel Laboran</div><div class="small text-white-50">Monitoring operasional aset laboratorium</div></div>
                    </div>
                    <div class="topbar-actions d-flex align-items-center gap-2">
                        <div class="dropdown">
                            <button id="laboranNotificationButton" class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                                <i class="bi bi-bell"></i>
                                <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                                <div class="fw-bold px-2 py-1">Notifikasi</div>
                                <?php if (empty($notif_items)): ?>
                                    <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                                <?php else: foreach ($notif_items as $notification): ?>
                                    <a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $notification->id_notifikasi) ?>">
                                        <div class="fw-semibold small"><?= html_escape($notification->judul) ?></div>
                                        <div class="small text-muted text-wrap"><?= html_escape($notification->pesan) ?></div>
                                    </a>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-light btn-sm rounded-circle theme-toggle" data-theme-toggle aria-label="Aktifkan mode terang" title="Aktifkan mode terang"><i class="bi bi-sun" aria-hidden="true"></i></button>
                        <a href="<?= base_url('index.php/admin/peminjaman/export_pengajuan_acc') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview ACC</a>
                        <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                        <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-fik btn-sm rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container-fluid px-3 px-lg-4 py-4">
            <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div><?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
            <section class="laboran-overview" aria-labelledby="laboranOverviewTitle">
                <div class="laboran-overview-heading">
                    <div>
                        <div class="text-uppercase text-warning fw-bold small mb-2" style="letter-spacing:.12em; color:var(--scm-orange)!important;">Operational overview</div>
                        <h1 id="laboranOverviewTitle">Ringkasan Laboratorium</h1>
                        <p>Monitor aset, peminjaman, pengembalian, maintenance, dan approval dalam satu tampilan.</p>
                    </div>
                </div>

                <div class="laboran-stat-grid">
                    <?php foreach ($stat_cards as $card): ?>
                        <article class="laboran-stat-card <?= $card['muted'] ? 'is-muted' : '' ?>">
                            <span class="laboran-stat-icon"><i class="bi <?= html_escape($card['icon']) ?>"></i></span>
                            <div class="laboran-stat-label"><?= html_escape($card['label']) ?></div>
                            <div class="laboran-stat-value" data-counter="<?= $get_stat($card['key']) ?>">0</div>
                            <div class="laboran-stat-note"><?= html_escape($card['note']) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="laboran-chart-grid">
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Status Inventory</h2><p>Distribusi kondisi aset terdaftar.</p></div><i class="bi bi-pie-chart text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranInventoryChart" aria-label="Grafik status inventory"></canvas><div class="laboran-chart-fallback">Data inventory belum tersedia.</div></div></article>
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Peminjaman per Bulan</h2><p>Jumlah transaksi peminjaman.</p></div><i class="bi bi-graph-up text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranLoanChart" aria-label="Grafik peminjaman per bulan"></canvas><div class="laboran-chart-fallback">Data peminjaman belum tersedia.</div></div></article>
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Pengembalian</h2><p>Perbandingan status pengembalian.</p></div><i class="bi bi-arrow-return-left text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranReturnChart" aria-label="Grafik pengembalian"></canvas><div class="laboran-chart-fallback">Data pengembalian belum tersedia.</div></div></article>
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Distribusi Barang</h2><p>Jumlah unit berdasarkan ruangan tujuan.</p></div><i class="bi bi-truck text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranDistributionChart" aria-label="Grafik distribusi barang"></canvas><div class="laboran-chart-fallback">Belum ada data distribusi.</div></div></article>
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Maintenance per Bulan</h2><p>Catatan maintenance aset.</p></div><i class="bi bi-tools text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranMaintenanceChart" aria-label="Grafik maintenance per bulan"></canvas><div class="laboran-chart-fallback">Belum ada catatan maintenance.</div></div></article>
                    <article class="laboran-chart-panel"><div class="laboran-panel-heading"><div><h2>Status Approval</h2><p>Progress pemeriksaan Laboran dan Kaur.</p></div><i class="bi bi-patch-check text-secondary"></i></div><div class="laboran-chart-wrap"><canvas id="laboranApprovalChart" aria-label="Grafik status approval"></canvas><div class="laboran-chart-fallback">Belum ada data approval.</div></div></article>
                </div>

                <div class="laboran-bottom-grid">
                    <article class="laboran-activity-panel">
                        <div class="laboran-panel-heading"><div><h2>Recent Activity</h2><p>Aktivitas terbaru operasional Laboratorium.</p></div><i class="bi bi-activity text-secondary"></i></div>
                        <div class="laboran-activity-list">
                            <?php if (empty($recent_activity)): ?>
                                <div class="small text-muted py-4 text-center">Belum ada aktivitas terbaru.</div>
                            <?php else: foreach ($recent_activity as $activity): ?>
                                <a class="laboran-activity-item" href="<?= base_url('index.php/' . ltrim((string) ($activity['link'] ?? 'admin/dashboard'), '/')) ?>">
                                    <span class="laboran-activity-icon"><i class="bi <?= html_escape($activity['icon'] ?? 'bi-activity') ?>"></i></span>
                                    <span class="flex-grow-1"><span class="laboran-activity-title d-block"><?= html_escape($activity['title'] ?? 'Aktivitas') ?></span><span class="laboran-activity-description d-block"><?= html_escape($activity['description'] ?? '') ?></span><span class="laboran-activity-meta"><span class="laboran-activity-time"><i class="bi bi-clock me-1"></i><?= !empty($activity['time']) ? date('d/m/Y H:i', strtotime($activity['time'])) : '-' ?></span><span class="laboran-activity-status"><?= html_escape($activity['status'] ?? '') ?></span></span></span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </article>
                    <article class="laboran-quick-panel">
                        <div class="laboran-panel-heading"><div><h2>Quick Action</h2><p>Akses cepat ke fitur operasional Laboran.</p></div><i class="bi bi-lightning-charge text-secondary"></i></div>
                        <div class="laboran-quick-grid">
                            <?php foreach ($quick_actions as $action): ?><a class="laboran-quick-link" href="<?= $action['url'] ?>"><i class="bi <?= html_escape($action['icon']) ?>"></i><span><?= html_escape($action['label']) ?></span></a><?php endforeach; ?>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const dashboardData = <?= json_encode([
                'inventory' => $overview['inventory_status'] ?? [],
                'loans' => $overview['peminjaman_bulanan'] ?? array_fill(0, 12, 0),
                'returns' => $overview['pengembalian'] ?? [],
                'distribution' => $overview['distribusi_ruangan'] ?? ['labels' => [], 'values' => []],
                'maintenance' => $overview['maintenance_bulanan'] ?? array_fill(0, 12, 0),
                'approval' => $overview['approval'] ?? [],
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_NUMERIC_CHECK) ?>;
            const chartInstances = [];
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const orange = '#ff7900';
            const orangeSoft = '#f39a52';
            const orangeDeep = '#c65b18';
            const warmGray = '#aeb6ba';
            const darkGray = '#4b5257';
            const formatNumber = (value) => Math.round(Number(value) || 0).toLocaleString('id-ID');
            document.querySelectorAll('[data-counter]').forEach((element) => {
                const target = Number(element.dataset.counter || 0);
                const startedAt = performance.now();
                const tick = (now) => {
                    const progress = Math.min(1, (now - startedAt) / 760);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    element.textContent = formatNumber(target * eased);
                    if (progress < 1) window.requestAnimationFrame(tick);
                };
                window.requestAnimationFrame(tick);
            });
            const isLight = () => document.documentElement.classList.contains('scm-theme-light');
            const chartOptions = () => {
                const light = isLight();
                const text = light ? '#68727b' : '#8f989f';
                const title = light ? '#273138' : '#f3f5f6';
                const grid = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                return { responsive: true, maintainAspectRatio: false, animation: { duration: 850, easing: 'easeOutQuart' }, scales: { x: { ticks: { color: text, font: { family: 'Poppins, sans-serif', size: 10 } }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: text, precision: 0, font: { family: 'Poppins, sans-serif', size: 10 } }, grid: { color: grid } } }, plugins: { legend: { labels: { color: title, usePointStyle: true, padding: 14, font: { family: 'Poppins, sans-serif', size: 10 } } }, tooltip: { backgroundColor: light ? '#ffffff' : '#181a1b', titleColor: title, bodyColor: title, borderColor: grid, borderWidth: 1 } } };
            };
            const fallback = (canvas) => { const element = canvas?.parentElement?.querySelector('.laboran-chart-fallback'); if (element) element.style.display = 'flex'; if (canvas) canvas.style.display = 'none'; };
            if (typeof window.Chart === 'undefined') {
                document.querySelectorAll('.laboran-chart-wrap canvas').forEach(fallback);
            } else {
                const base = chartOptions();
                const inventoryCanvas = document.getElementById('laboranInventoryChart');
                if (inventoryCanvas) chartInstances.push(new window.Chart(inventoryCanvas, { type: 'doughnut', data: { labels: Object.keys(dashboardData.inventory || {}), datasets: [{ data: Object.values(dashboardData.inventory || {}), backgroundColor: [orange, orangeSoft, warmGray, darkGray], borderColor: isLight() ? '#ffffff' : '#111314', borderWidth: 4, hoverOffset: 5 }] }, options: { ...base, cutout: '68%', plugins: { ...base.plugins, legend: { ...base.plugins.legend, position: 'bottom' } } } }));
                const loanCanvas = document.getElementById('laboranLoanChart');
                if (loanCanvas) chartInstances.push(new window.Chart(loanCanvas, { type: 'line', data: { labels, datasets: [{ label: 'Peminjaman', data: dashboardData.loans || [], borderColor: orange, backgroundColor: 'rgba(255, 121, 0, .13)', fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: orange }] }, options: base }));
                const returnCanvas = document.getElementById('laboranReturnChart');
                if (returnCanvas) chartInstances.push(new window.Chart(returnCanvas, { type: 'bar', data: { labels: Object.keys(dashboardData.returns || {}), datasets: [{ label: 'Transaksi', data: Object.values(dashboardData.returns || {}), backgroundColor: [orange, orangeSoft, darkGray], borderRadius: 6, maxBarThickness: 42 }] }, options: { ...base, plugins: { ...base.plugins, legend: { display: false } } } }));
                const distributionCanvas = document.getElementById('laboranDistributionChart');
                if (distributionCanvas) chartInstances.push(new window.Chart(distributionCanvas, { type: 'bar', data: { labels: dashboardData.distribution?.labels || [], datasets: [{ label: 'Unit', data: dashboardData.distribution?.values || [], backgroundColor: orange, borderRadius: 6, maxBarThickness: 38 }] }, options: { ...base, indexAxis: 'y', scales: { x: base.scales.y, y: base.scales.x }, plugins: { ...base.plugins, legend: { display: false } } } }));
                const maintenanceCanvas = document.getElementById('laboranMaintenanceChart');
                if (maintenanceCanvas) chartInstances.push(new window.Chart(maintenanceCanvas, { type: 'line', data: { labels, datasets: [{ label: 'Maintenance', data: dashboardData.maintenance || [], borderColor: orangeSoft, backgroundColor: 'rgba(243, 154, 82, .14)', fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: orangeSoft }] }, options: base }));
                const approvalCanvas = document.getElementById('laboranApprovalChart');
                if (approvalCanvas) chartInstances.push(new window.Chart(approvalCanvas, { type: 'bar', data: { labels: Object.keys(dashboardData.approval || {}), datasets: [{ label: 'Jumlah', data: Object.values(dashboardData.approval || {}), backgroundColor: [orange, orangeSoft, orangeDeep, warmGray, darkGray], borderRadius: 6, maxBarThickness: 34 }] }, options: { ...base, indexAxis: 'y', scales: { x: base.scales.y, y: base.scales.x }, plugins: { ...base.plugins, legend: { display: false } } } }));
            }
            window.laboranSyncChartTheme = () => {
                const light = isLight();
                const text = light ? '#68727b' : '#8f989f';
                const title = light ? '#273138' : '#f3f5f6';
                const grid = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                chartInstances.forEach((chart) => {
                    Object.values(chart.options.scales || {}).forEach((scale) => { if (scale.ticks) scale.ticks.color = text; if (scale.grid) scale.grid.color = grid; });
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = title;
                    if (chart.options.plugins?.tooltip) { chart.options.plugins.tooltip.backgroundColor = light ? '#ffffff' : '#181a1b'; chart.options.plugins.tooltip.titleColor = title; chart.options.plugins.tooltip.bodyColor = title; chart.options.plugins.tooltip.borderColor = grid; }
                    chart.data.datasets.forEach((dataset) => { if (chart.config.type === 'doughnut') dataset.borderColor = light ? '#ffffff' : '#111314'; });
                    chart.update('none');
                });
            };
            window.addEventListener('scm:themechange', window.laboranSyncChartTheme);
            window.laboranSyncChartTheme();
            window.setInterval(() => {
                if (!document.hidden && !document.querySelector('form:focus-within')) window.location.reload();
            }, 60000);
        })();
    </script>
</body>
</html>
