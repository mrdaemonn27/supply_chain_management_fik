<?php
function status_class_kaprodi($status) {
    $map = [
        'Pengajuan' => 'status-pengajuan',
        'Revisi' => 'status-revisi',
        'Negosiasi' => 'status-negosiasi',
        'Sedang Negosiasi' => 'status-negosiasi',
        'Deal' => 'status-deal',
        'Disetujui' => 'status-approval',
        'Approval' => 'status-approval',
        'BAST' => 'status-bast',
        'Inventarisasi' => 'status-inventory',
        'Selesai' => 'status-selesai',
        'Ditolak' => 'status-ditolak',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function query_kaprodi($filters, $page, $tab = 'riwayat', $kategori = '') {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    if ($tab !== '') {
        $params['tab'] = $tab;
    }
    if ($kategori !== '') {
        $params['kategori'] = $kategori;
        if ($kategori === 'barang') {
            $params['jenis_pengajuan'] = 'Barang';
        } elseif ($kategori === 'jasa') {
            $params['jenis_pengajuan'] = 'Jasa';
        } elseif ($kategori === 'gabungan') {
            unset($params['jenis_pengajuan']);
        }
    }
    return http_build_query($params);
}
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'pengajuan' => 0, 'negosiasi' => 0, 'deal' => 0, 'selesai' => 0];
$dashboard_stats = $dashboard_stats ?? [
    'total_pengajuan' => 0, 'menunggu_proses' => 0, 'sedang_negosiasi' => 0,
    'disetujui_deal' => 0, 'direvisi' => 0, 'ditolak' => 0,
    'total_nilai_pengajuan' => 0, 'pengajuan_bulan_ini' => 0,
];
$dashboard_year = (int) ($dashboard_year ?? date('Y'));
$dashboard_years = $dashboard_years ?? [$dashboard_year];
$dashboard_monthly_submissions = $dashboard_monthly_submissions ?? array_fill(0, 12, 0);
$dashboard_monthly_values = $dashboard_monthly_values ?? array_fill(0, 12, 0);
$dashboard_status_breakdown = $dashboard_status_breakdown ?? ['Pengajuan' => 0, 'Sedang Negosiasi' => 0, 'Deal' => 0, 'Revisi' => 0, 'Ditolak' => 0];
$dashboard_type_breakdown = $dashboard_type_breakdown ?? ['Barang' => 0, 'Jasa' => 0, 'Barang dan Jasa' => 0];
$dashboard_activity = $dashboard_activity ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan ?? []);
$active_tab = $active_tab ?? 'panel';
$active_tab = in_array($active_tab, ['panel', 'ajukan', 'riwayat'], true) ? $active_tab : 'panel';
$active_category = $active_category ?? 'gabungan';
$focus_id = (int) ($filters['id_pengajuan'] ?? 0);
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Kaprodi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .brand-mark { width: 42px; height: 42px; border-radius: 8px; background: rgba(234, 91, 26, .16); color: #ea5b1a; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .panel-card { background: #fff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 8px 22px rgba(32, 33, 36, .05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234, 91, 26, .16); }
        .summary-card { min-height: 96px; padding: 18px; }
        .summary-card .value { font-weight: 700; font-size: 1.5rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .jenis-badge { width: 78px; min-height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; line-height: 1; text-align: center; }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 152px; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .need-row { border: 1px solid #e8eaed; border-radius: 8px; padding: 12px; background: #fff; }
        .need-row .row-number { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(234, 91, 26, .12); color: #c24a13; font-weight: 700; }
        .need-row.is-empty { border-color: #e8eaed; }
        .item-type-wrap { display: none; }
        .fill-summary { position: sticky; top: 86px; z-index: 8; border: 1px solid rgba(234, 91, 26, .24); background: rgba(255, 255, 255, .96); backdrop-filter: blur(8px); }
        .fill-summary .metric { border-right: 1px solid #eceff1; }
        .fill-summary .metric:last-child { border-right: 0; }
        .subtotal-preview { background: #f8f9fa; }
        .nav-tabs .nav-link { color: #495057; font-weight: 600; }
        .nav-tabs .nav-link.active { color: #ea5b1a; border-bottom-color: #ea5b1a; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .scm-dashboard-kaprodi .topbar-actions { margin-left: auto; }
        .kaprodi-overview { color: var(--scm-text, #f7f7f7); }
        .kaprodi-overview-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1.35rem; }
        .kaprodi-overview-head h1 { margin: 0; font-size: clamp(1.45rem, 2vw, 2rem); letter-spacing: -.02em; }
        .kaprodi-overview-head p { margin: .35rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .9rem; }
        .kaprodi-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; margin-bottom: .9rem; }
        .kaprodi-stat-card { position: relative; min-height: 132px; padding: 1rem; overflow: hidden; border: 1px solid var(--scm-border, #2b2f33); border-top: 2px solid var(--scm-orange, #ff7900); border-radius: 12px; background: var(--scm-surface, #111416); box-shadow: 0 8px 22px rgba(0,0,0,.12); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .kaprodi-stat-card:hover { transform: translateY(-3px); border-color: rgba(255, 121, 0, .65); box-shadow: 0 12px 28px rgba(0,0,0,.2); }
        .kaprodi-stat-card.is-muted { border-top-color: #8b949e; }
        .kaprodi-stat-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,121,0,.6); border-radius: 9px; color: var(--scm-orange, #ff7900); margin-bottom: .75rem; }
        .kaprodi-stat-card.is-muted .kaprodi-stat-icon { border-color: #68717a; color: #aeb6bd; }
        .kaprodi-stat-label { color: var(--scm-muted, #a8adb5); font-size: .72rem; }
        .kaprodi-stat-value { margin-top: .18rem; color: var(--scm-text, #f7f7f7); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 700; line-height: 1.15; }
        .kaprodi-stat-value.is-currency { font-size: clamp(1rem, 1.6vw, 1.35rem); }
        .kaprodi-stat-note { margin-top: .45rem; color: var(--scm-muted, #a8adb5); font-size: .68rem; }
        .kaprodi-chart-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .9fr); gap: .9rem; margin-bottom: .9rem; }
        .kaprodi-chart-panel, .kaprodi-activity-panel, .kaprodi-quick-panel { min-width: 0; border: 1px solid var(--scm-border, #2b2f33); border-radius: 12px; background: var(--scm-surface, #111416); box-shadow: 0 8px 22px rgba(0,0,0,.12); }
        .kaprodi-chart-panel { padding: 1rem; min-height: 310px; }
        .kaprodi-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .7rem; }
        .kaprodi-panel-heading h2 { margin: 0; color: var(--scm-text, #f7f7f7); font-size: .92rem; font-weight: 700; }
        .kaprodi-panel-heading p { margin: .22rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .7rem; }
        .kaprodi-chart-wrap { position: relative; height: 245px; }
        .kaprodi-bottom-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .9fr); gap: .9rem; }
        .kaprodi-activity-panel, .kaprodi-quick-panel { padding: 1rem; }
        .kaprodi-activity-list { max-height: 320px; overflow-y: auto; padding-right: .2rem; }
        .kaprodi-activity-item { display: flex; align-items: flex-start; gap: .7rem; padding: .75rem 0; border-bottom: 1px solid var(--scm-border, #2b2f33); color: inherit; text-decoration: none; }
        .kaprodi-activity-item:last-child { border-bottom: 0; }
        .kaprodi-activity-item:hover .kaprodi-activity-title { color: var(--scm-orange, #ff7900); }
        .kaprodi-activity-icon { width: 31px; height: 31px; flex: 0 0 31px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,121,0,.48); border-radius: 8px; color: var(--scm-orange, #ff7900); background: rgba(255,121,0,.09); }
        .kaprodi-activity-title { color: var(--scm-text, #f7f7f7); font-size: .75rem; font-weight: 700; transition: color .18s ease; }
        .kaprodi-activity-description, .kaprodi-activity-time { color: var(--scm-muted, #a8adb5); font-size: .68rem; }
        .kaprodi-activity-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }
        .kaprodi-activity-status { display: inline-flex; padding: .15rem .4rem; border-radius: 999px; background: rgba(255,121,0,.12); color: var(--scm-orange, #ff7900); font-size: .62rem; font-weight: 700; }
        .kaprodi-quick-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .kaprodi-quick-link { display: flex; align-items: center; gap: .5rem; min-height: 48px; padding: .65rem .7rem; border: 1px solid var(--scm-border, #2b2f33); border-radius: 9px; color: var(--scm-text, #f7f7f7); background: rgba(255,255,255,.015); font-size: .72rem; font-weight: 600; text-decoration: none; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
        .kaprodi-quick-link:hover { color: var(--scm-text, #f7f7f7); border-color: rgba(255,121,0,.7); background: rgba(255,121,0,.08); transform: translateY(-1px); }
        .kaprodi-quick-link i { color: var(--scm-orange, #ff7900); font-size: 1rem; }
        .theme-toggle { flex: 0 0 38px !important; height: 38px; padding: 0 !important; width: 38px; }
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
        html.scm-theme-light .scm-dashboard .summary-card, html.scm-theme-light .scm-dashboard .menu-card, html.scm-theme-light .scm-dashboard .panel-card, html.scm-theme-light .scm-dashboard .quick-link, html.scm-theme-light .scm-dashboard .need-row, html.scm-theme-light .scm-dashboard .item-card, html.scm-theme-light .scm-dashboard .fill-summary, html.scm-theme-light .scm-dashboard .subtotal-preview { background: #ffffff !important; border-color: var(--scm-border) !important; box-shadow: 0 8px 22px rgba(36, 43, 48, .06) !important; }
        html.scm-theme-light .scm-dashboard .summary-value, html.scm-theme-light .scm-dashboard .summary-card .value, html.scm-theme-light .scm-dashboard .summary-card .summary-value { color: #1c2024; }
        html.scm-theme-light .scm-dashboard .form-control, html.scm-theme-light .scm-dashboard .form-select, html.scm-theme-light .scm-dashboard .input-group-text, html.scm-theme-light .scm-dashboard .form-control:disabled, html.scm-theme-light .scm-dashboard .form-select:disabled { color: #283138 !important; background-color: #ffffff !important; border-color: #cfd6da !important; }
        html.scm-theme-light .scm-dashboard .form-control::placeholder { color: #89939a !important; }
        html.scm-theme-light .scm-dashboard .table { --bs-table-color: #293238; }
        html.scm-theme-light .scm-dashboard .table-clean thead th, html.scm-theme-light .scm-dashboard .table-light, html.scm-theme-light .scm-dashboard .table-light th { color: #5b666e !important; background: #f0f2f3 !important; border-color: var(--scm-border) !important; }
        html.scm-theme-light .scm-dashboard .table-hover > tbody > tr:hover > *, html.scm-theme-light .scm-dashboard .table-striped > tbody > tr:nth-of-type(odd) > * { color: #1c2024; --bs-table-accent-bg: rgba(234, 91, 26, .04); }
        html.scm-theme-light .scm-dashboard .btn-outline-dark, html.scm-theme-light .scm-dashboard .btn-outline-secondary { color: #56616a !important; border-color: #b9c2c8 !important; }
        html.scm-theme-light .scm-dashboard .btn-outline-dark:hover, html.scm-theme-light .scm-dashboard .btn-outline-secondary:hover { color: #1c2024 !important; background: #e9edef !important; }
        html.scm-theme-light .scm-dashboard .alert { color: #334047; background: #ffffff; border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard .modal-content, html.scm-theme-light .scm-dashboard .dropdown-menu { color: var(--scm-text); background: #ffffff; border-color: var(--scm-border) !important; }
        html.scm-theme-light .scm-dashboard .modal-header, html.scm-theme-light .scm-dashboard .modal-footer { border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard .modal .btn-close { filter: none; }
        html.scm-theme-light .scm-dashboard .dropdown-item { color: #39444b; }
        html.scm-theme-light .scm-dashboard .dropdown-item:hover, html.scm-theme-light .scm-dashboard .dropdown-item:focus { color: #1c2024; background: #f0f2f3; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-stat-card, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-chart-panel, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-panel, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-panel { background: #ffffff; border-color: var(--scm-border); box-shadow: 0 8px 22px rgba(36, 43, 48, .06); }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-stat-value, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-panel-heading h2, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-title, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link { color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link { background: #f7f8f9; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link:hover { color: #1c2024; background: rgba(234, 91, 26, .08); }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-item { border-color: rgba(35, 42, 47, .1); }
        @media (max-width: 1199.98px) { .kaprodi-stat-grid { grid-template-columns: repeat(4, minmax(145px, 1fr)); overflow-x: auto; padding-bottom: .3rem; } .kaprodi-stat-card { min-width: 145px; } }
        @media (max-width: 991.98px) { .kaprodi-chart-grid, .kaprodi-bottom-grid { grid-template-columns: 1fr; } }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions { justify-content: flex-end; }
            .topbar-actions .btn { flex: 0 0 auto; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
            .fill-summary { top: 126px; }
            .fill-summary .metric { border-right: 0; border-bottom: 1px solid #eceff1; }
        }
        @media (max-width: 575.98px) { .kaprodi-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); overflow-x: visible; } .kaprodi-stat-card { min-width: 0; min-height: 118px; padding: .8rem; } .kaprodi-stat-icon { margin-bottom: .55rem; } .kaprodi-quick-grid { grid-template-columns: 1fr; } .kaprodi-chart-panel, .kaprodi-activity-panel, .kaprodi-quick-panel { padding: .8rem; } }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body class="scm-dashboard scm-dashboard-kaprodi">
    <aside class="dashboard-sidebar" aria-label="Navigasi Panel Kaprodi">
        <a class="sidebar-brand" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>">
            <span class="sidebar-brand-mark"><i class="bi bi-building-check"></i></span>
            <span><strong>SCM FIK</strong><small>Panel Kaprodi</small></span>
        </a>
        <div class="sidebar-caption">Pengajuan prodi</div>
        <nav class="sidebar-nav">
            <a class="sidebar-link <?= $active_tab === 'panel' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>"><i class="bi bi-grid-1x2"></i><span>Panel</span></a>
            <a class="sidebar-link <?= $active_tab === 'ajukan' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=ajukan') ?>"><i class="bi bi-plus-square"></i><span>Ajukan Kebutuhan</span></a>
            <a class="sidebar-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>"><i class="bi bi-clock-history"></i><span>Riwayat Pengajuan</span></a>
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>System operational</span></div>
    </aside>
    <div class="dashboard-content">
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="dashboard-topbar-brand d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-building-check"></i></span>
                    <div>
                        <div class="fw-bold">Panel Kaprodi</div>
                        <div class="small text-white-50">Pengajuan kebutuhan prodi ke laboratorium</div>
                    </div>
                </div>
                <div class="topbar-actions d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button id="kaprodiNotificationButton" class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                            <div class="fw-bold px-2 py-1">Notifikasi</div>
                            <?php if (empty($notif_items)): ?>
                                <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                                <?php $notif_link = base_url('index.php/kaprodi/dashboard/notifikasi/' . (int) $n->id_notifikasi); ?>
                                <a class="dropdown-item rounded-3 py-2 <?= empty($n->is_read) ? 'bg-light' : '' ?>" href="<?= html_escape($notif_link) ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
                                    <div class="small text-muted mt-1"><?= date('d/m/Y H:i', strtotime($n->created_at)) ?></div>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-circle theme-toggle" data-theme-toggle aria-label="Aktifkan mode terang" title="Aktifkan mode terang">
                        <i class="bi bi-sun" aria-hidden="true"></i>
                    </button>
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>
        <?php if ($focus_id > 0): ?>
            <div class="alert alert-info rounded-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <span><i class="bi bi-funnel me-1"></i> Menampilkan pengajuan terkait notifikasi saja.</span>
                <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>">Kembali ke Semua Data</a>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'panel'): ?>
            <section class="kaprodi-overview" aria-labelledby="kaprodiOverviewTitle">
                <div class="kaprodi-overview-head">
                    <div>
                        <h1 id="kaprodiOverviewTitle">Dashboard Kaprodi</h1>
                        <p>Ringkasan aktivitas pengajuan kebutuhan program studi.</p>
                    </div>
                </div>

                <?php
                $kaprodi_stat_cards = [
                    ['label' => 'Total Pengajuan', 'value' => (int) $dashboard_stats['total_pengajuan'], 'icon' => 'bi-inboxes', 'note' => 'Semua pengajuan Anda', 'muted' => false, 'currency' => false],
                    ['label' => 'Menunggu Proses', 'value' => (int) $dashboard_stats['menunggu_proses'], 'icon' => 'bi-hourglass-split', 'note' => 'Belum diproses Kaur', 'muted' => false, 'currency' => false],
                    ['label' => 'Sedang Negosiasi', 'value' => (int) $dashboard_stats['sedang_negosiasi'], 'icon' => 'bi-chat-square-text', 'note' => 'Sedang ditindaklanjuti', 'muted' => false, 'currency' => false],
                    ['label' => 'Disetujui / Deal', 'value' => (int) $dashboard_stats['disetujui_deal'], 'icon' => 'bi-check2-circle', 'note' => 'Siap ke proses berikutnya', 'muted' => false, 'currency' => false],
                    ['label' => 'Direvisi', 'value' => (int) $dashboard_stats['direvisi'], 'icon' => 'bi-pencil-square', 'note' => 'Perlu diperbaiki', 'muted' => true, 'currency' => false],
                    ['label' => 'Ditolak', 'value' => (int) $dashboard_stats['ditolak'], 'icon' => 'bi-x-circle', 'note' => 'Proses berhenti', 'muted' => true, 'currency' => false],
                    ['label' => 'Total Nilai Pengajuan', 'value' => (float) $dashboard_stats['total_nilai_pengajuan'], 'icon' => 'bi-wallet2', 'note' => 'Estimasi sebelum negosiasi', 'muted' => false, 'currency' => true],
                    ['label' => 'Pengajuan Bulan Ini', 'value' => (int) $dashboard_stats['pengajuan_bulan_ini'], 'icon' => 'bi-calendar-plus', 'note' => 'Periode ' . date('F Y'), 'muted' => false, 'currency' => false],
                ];
                ?>
                <div class="kaprodi-stat-grid">
                    <?php foreach ($kaprodi_stat_cards as $card): ?>
                        <article class="kaprodi-stat-card <?= $card['muted'] ? 'is-muted' : '' ?>">
                            <span class="kaprodi-stat-icon"><i class="bi <?= html_escape($card['icon']) ?>"></i></span>
                            <div class="kaprodi-stat-label"><?= html_escape($card['label']) ?></div>
                            <div class="kaprodi-stat-value <?= $card['currency'] ? 'is-currency' : '' ?>" data-counter="<?= html_escape((string) $card['value']) ?>" data-counter-currency="<?= $card['currency'] ? '1' : '0' ?>"><?= $card['currency'] ? 'Rp 0' : '0' ?></div>
                            <div class="kaprodi-stat-note"><?= $card['note'] ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="kaprodi-chart-grid">
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Pengajuan per Bulan</h2><p>Jumlah pengajuan pada tahun <?= $dashboard_year ?>.</p></div><i class="bi bi-bar-chart-line text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiMonthlySubmissionChart" aria-label="Grafik pengajuan per bulan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Status Pengajuan</h2><p>Distribusi status pada tahun <?= $dashboard_year ?>.</p></div><i class="bi bi-pie-chart text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiStatusChart" aria-label="Grafik status pengajuan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Nilai Pengajuan</h2><p>Total estimasi sebelum negosiasi per bulan.</p></div><i class="bi bi-graph-up-arrow text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiValueChart" aria-label="Grafik nilai pengajuan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Jenis Pengajuan</h2><p>Barang, jasa, dan gabungan pada tahun <?= $dashboard_year ?>.</p></div><i class="bi bi-ui-checks-grid text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiTypeChart" aria-label="Grafik jenis pengajuan"></canvas></div>
                    </article>
                </div>

                <div class="kaprodi-bottom-grid">
                    <article class="kaprodi-activity-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Recent Activity</h2><p>Aktivitas terbaru pengajuan Kaprodi.</p></div><i class="bi bi-activity text-secondary"></i></div>
                        <div class="kaprodi-activity-list">
                            <?php if (empty($dashboard_activity)): ?>
                                <div class="small text-secondary py-4 text-center">Belum ada aktivitas pengajuan.</div>
                            <?php else: foreach ($dashboard_activity as $activity): ?>
                                <a class="kaprodi-activity-item" href="<?= html_escape($activity['link'] ?? '#') ?>">
                                    <span class="kaprodi-activity-icon"><i class="bi <?= html_escape($activity['icon'] ?? 'bi-bell') ?>"></i></span>
                                    <span class="flex-grow-1"><span class="kaprodi-activity-title d-block"><?= html_escape($activity['title'] ?? 'Aktivitas') ?></span><span class="kaprodi-activity-description d-block"><?= html_escape($activity['description'] ?? '') ?></span><span class="kaprodi-activity-meta"><span class="kaprodi-activity-time"><i class="bi bi-clock me-1"></i><?= !empty($activity['time']) ? date('d/m/Y H:i', strtotime($activity['time'])) : '-' ?></span><span class="kaprodi-activity-status"><?= html_escape($activity['status'] ?? '') ?></span></span></span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </article>
                    <article class="kaprodi-quick-panel">
                        <div class="kaprodi-panel-heading"><div><h2>Quick Action</h2><p>Akses cepat ke fitur utama Kaprodi.</p></div><i class="bi bi-lightning-charge text-secondary"></i></div>
                        <div class="kaprodi-quick-grid">
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=ajukan') ?>"><i class="bi bi-plus-square"></i><span>Ajukan Kebutuhan</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>"><i class="bi bi-clock-history"></i><span>Riwayat Pengajuan</span></a>
                            <a class="kaprodi-quick-link" href="#kaprodiNotificationButton" data-kaprodi-notifications><i class="bi bi-bell"></i><span>Lihat Notifikasi</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category)) ?>"><i class="bi bi-file-earmark-spreadsheet"></i><span>Preview Laporan</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category) . '&download=1') ?>"><i class="bi bi-download"></i><span>Export Excel</span></a>
                        </div>
                    </article>
                </div>
            </section>
        <?php else: ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Pengajuan Barang dan Jasa</h1>
                <p class="text-muted mb-0">Kaprodi mengajukan kebutuhan. Vendor, harga, negosiasi, dan BAST diproses oleh Kaur Laboratorium.</p>
            </div>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
        </div>

        <ul class="nav nav-tabs mb-3" id="kaprodiTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link <?= $active_tab === 'ajukan' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-ajukan" type="button" aria-selected="<?= $active_tab === 'ajukan' ? 'true' : 'false' ?>"><i class="bi bi-plus-circle me-1"></i> Ajukan</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button" aria-selected="<?= $active_tab === 'riwayat' ? 'true' : 'false' ?>"><i class="bi bi-clock-history me-1"></i> Riwayat</button></li>
        </ul>

        <div class="tab-content">
            <section class="tab-pane fade <?= $active_tab === 'ajukan' ? 'show active' : '' ?>" id="tab-ajukan">
                <form action="<?= base_url('index.php/kaprodi/pengajuan/simpan') ?>" method="post" class="panel-card p-3 p-lg-4 mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis Pengajuan</label>
                            <select name="jenis_pengajuan" id="jenisPengajuan" class="form-select" required>
                                <option value="Barang">Barang</option>
                                <option value="Jasa">Jasa</option>
                                <option value="Barang dan Jasa">Barang dan Jasa</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Program Studi</label>
                            <input type="text" name="nama_prodi" class="form-control" placeholder="Contoh: S1 Desain Komunikasi Visual" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Pengajuan</label>
                            <input type="text" name="nama_pengajuan" class="form-control" placeholder="Contoh: Kebutuhan studio fotografi" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keterangan Kebutuhan</label>
                            <textarea name="kebutuhan_lab" class="form-control" rows="3" placeholder="Jelaskan alasan kebutuhan, prioritas, atau ruangan terkait."></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-4 mb-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">Daftar Kebutuhan</h2>
                            <div class="small text-muted">Harga satuan adalah estimasi awal. Vendor dan negosiasi tetap diproses oleh Kaur Laboratorium.</div>
                        </div>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <div class="input-group input-group-sm" style="width: min(100%, 240px);">
                                <span class="input-group-text">Jumlah baris</span>
                                <input type="number" id="bulkRows" class="form-control" min="1" max="100" value="1">
                                <button type="button" class="btn btn-outline-dark" id="generateRows">Buat</button>
                            </div>
                            <button type="button" class="btn btn-outline-dark rounded-pill px-3" id="addNeed"><i class="bi bi-plus-lg me-1"></i> Tambah Baris</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" id="removeEmptyRows"><i class="bi bi-filter-circle me-1"></i> Hapus Baris Kosong</button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" id="removeAllRows"><i class="bi bi-trash3 me-1"></i> Hapus Semua</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="resetForm"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form</button>
                    </div>

                    <div class="fill-summary rounded-3 p-2 p-lg-3 mb-3">
                        <div class="row g-0 text-center text-md-start">
                            <div class="col-6 col-lg-2 metric p-2"><div class="small text-muted">Total Baris</div><div class="fw-bold" id="totalRows">0</div></div>
                            <div class="col-6 col-lg-2 metric p-2"><div class="small text-muted">Sudah Terisi</div><div class="fw-bold text-success" id="filledRows">0</div></div>
                            <div class="col-6 col-lg-2 metric p-2"><div class="small text-muted">Masih Kosong</div><div class="fw-bold text-warning" id="emptyRows">0</div></div>
                            <div class="col-6 col-lg-2 metric p-2"><div class="small text-muted">Sebelum Pajak</div><div class="fw-bold" id="totalBeforeTax">Rp 0</div></div>
                            <div class="col-6 col-lg-2 metric p-2"><div class="small text-muted">Pajak 20%</div><div class="fw-bold" id="taxValue">Rp 0</div></div>
                            <div class="col-6 col-lg-2 p-2"><div class="small text-muted">Setelah Pajak</div><div class="fw-bold text-primary" id="totalAfterTax">Rp 0</div></div>
                        </div>
                    </div>

                    <div id="needList" class="vstack gap-2">
                        <div class="need-row">
                        <div class="row g-2 align-items-end">
                                <div class="col-2 col-lg-1 text-center">
                                    <label class="form-label small fw-semibold d-block">No.</label>
                                    <span class="row-number">1</span>
                                </div>
                                <div class="col-10 col-lg-2 item-type-wrap">
                                    <label class="form-label small fw-semibold">Jenis Item</label>
                                    <select name="jenis_item[]" class="form-select need-type">
                                        <option value="Barang">Barang</option>
                                        <option value="Jasa">Jasa</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-3 item-name-col">
                                    <label class="form-label small fw-semibold need-name-label">Nama Barang</label>
                                    <input type="text" name="uraian_barang[]" class="form-control need-name" placeholder="Contoh: Kamera mirrorless">
                                </div>
                                <div class="col-6 col-lg-1">
                                    <label class="form-label small fw-semibold">Volume</label>
                                    <input type="number" name="vol[]" class="form-control need-volume" min="1" step="1" value="1">
                                </div>
                                <div class="col-6 col-lg-1">
                                    <label class="form-label small fw-semibold">Satuan</label>
                                    <input type="text" name="satuan[]" class="form-control" value="unit">
                                </div>
                                <div class="col-md-6 col-lg-2">
                                    <label class="form-label small fw-semibold">Harga Satuan</label>
                                    <input type="text" name="harga_penawaran_sat[]" class="form-control money-input need-price" placeholder="Rp 0">
                                </div>
                                <div class="col-md-6 col-lg-2">
                                    <label class="form-label small fw-semibold">Subtotal</label>
                                    <input type="text" class="form-control subtotal-preview" value="Rp 0" readonly>
                                </div>
                                <div class="col-lg-2">
                                    <label class="form-label small fw-semibold">Link Referensi</label>
                                    <input type="url" name="link_penawaran[]" class="form-control" placeholder="https://...">
                                </div>
                                <div class="col-lg-1 d-grid">
                                    <button type="button" class="btn btn-outline-danger remove-need" disabled><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-fik rounded-pill px-4 fw-semibold"><i class="bi bi-send me-1"></i> Kirim Pengajuan</button>
                    </div>
                </form>
            </section>

            <section class="tab-pane fade <?= $active_tab === 'riwayat' ? 'show active' : '' ?>" id="tab-riwayat">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Riwayat Pengajuan</h2>
                        <div class="small text-muted">Export mengikuti filter tanggal, jenis, status, dan kata kunci.</div>
                    </div>
                    <a href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel</a>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php
                    $category_tabs = [
                        'barang' => 'Pengajuan Barang',
                        'jasa' => 'Pengajuan Jasa',
                        'gabungan' => 'Barang dan Jasa',
                    ];
                    foreach ($category_tabs as $category_key => $category_label):
                    ?>
                        <a class="btn btn-sm rounded-pill <?= $active_category === $category_key ? 'btn-fik' : 'btn-outline-secondary' ?>" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, 1, 'riwayat', $category_key)) ?>">
                            <?= html_escape($category_label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="panel-card p-3 p-lg-4 mb-3">
                    <form method="get" action="<?= base_url('index.php/kaprodi/dashboard') ?>" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="riwayat">
                        <input type="hidden" name="kategori" value="<?= html_escape($active_category) ?>">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kata Kunci</label>
                            <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, kebutuhan">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jenis</label>
                            <select name="jenis_pengajuan" class="form-select">
                                <option value="">Semua</option>
                                <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                                <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach (($status_options ?? []) as $option): ?>
                                    <option value="<?= html_escape($option) ?>" <?= (($filters['status'] ?? '') === $option) ? 'selected' : '' ?>><?= html_escape($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Dari</label>
                            <input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Sampai</label>
                            <input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>">
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-fik"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="panel-card overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-clean align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pengajuan</th>
                                    <th>Jenis</th>
                                    <th>Kebutuhan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pengajuan)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data pengajuan sesuai filter.</td></tr>
                                <?php else: foreach ($pengajuan as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= html_escape($p->nama_pengajuan) ?></div>
                                            <div class="small text-muted"><?= html_escape($p->nama_prodi) ?></div>
                                        </td>
                                        <td><span class="badge text-bg-light border jenis-badge"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                        <td style="min-width: 300px;">
                                            <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                            <?php foreach (($p->items ?? []) as $item): ?>
                                                <?php $item_subtotal = (float) $item->vol * (float) $item->harga_penawaran_sat; ?>
                                                <div class="small"><i class="bi bi-dot"></i><?= html_escape($item->uraian_barang) ?> - <?= rtrim(rtrim(number_format((float) $item->vol, 2, ',', '.'), '0'), ',') ?> <?= html_escape($item->satuan) ?> <?php if ($item_subtotal > 0): ?> · Rp <?= number_format($item_subtotal, 0, ',', '.') ?><?php endif; ?></div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><span class="status-pill <?= status_class_kaprodi($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                        <td class="text-end"><a href="<?= base_url('index.php/kaprodi/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview</a></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-top">
                        <div class="small text-muted">Menampilkan <?= count($pengajuan ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, max(1, $page - 1), 'riwayat', $active_category)) ?>">Prev</a></li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, $i, 'riwayat', $active_category)) ?>"><?= $i ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, min($total_pages, $page + 1), 'riwayat', $active_category)) ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </section>
        </div>
        <?php endif; ?>
    </main>
    </div>

    <template id="needTemplate">
        <div class="need-row">
            <div class="row g-2 align-items-end">
                <div class="col-2 col-lg-1 text-center">
                    <label class="form-label small fw-semibold d-block">No.</label>
                    <span class="row-number">1</span>
                </div>
                <div class="col-10 col-lg-2 item-type-wrap">
                    <label class="form-label small fw-semibold">Jenis Item</label>
                    <select name="jenis_item[]" class="form-select need-type">
                        <option value="Barang">Barang</option>
                        <option value="Jasa">Jasa</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 item-name-col">
                    <label class="form-label small fw-semibold need-name-label">Nama Barang</label>
                    <input type="text" name="uraian_barang[]" class="form-control need-name" placeholder="Contoh: Kamera mirrorless">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-semibold">Volume</label>
                    <input type="number" name="vol[]" class="form-control need-volume" min="1" step="1" value="1">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-semibold">Satuan</label>
                    <input type="text" name="satuan[]" class="form-control" value="unit">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold">Harga Satuan</label>
                    <input type="text" name="harga_penawaran_sat[]" class="form-control money-input need-price" placeholder="Rp 0">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold">Subtotal</label>
                    <input type="text" class="form-control subtotal-preview" value="Rp 0" readonly>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold">Link Referensi</label>
                    <input type="url" name="link_penawaran[]" class="form-control" placeholder="https://...">
                </div>
                <div class="col-lg-1 d-grid">
                    <button type="button" class="btn btn-outline-danger remove-need"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const panel = document.querySelector('.kaprodi-overview');
            const chartInstances = [];
            window.kaprodiSyncChartTheme = () => {
                const light = document.documentElement.classList.contains('scm-theme-light');
                const textColor = light ? '#68727b' : '#a8adb5';
                const gridColor = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                chartInstances.forEach((chart) => {
                    if (chart.options.scales) {
                        Object.values(chart.options.scales).forEach((scale) => {
                            if (scale.ticks) scale.ticks.color = textColor;
                            if (scale.grid) scale.grid.color = gridColor;
                        });
                    }
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = textColor;
                    if (chart.options.plugins?.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = light ? '#ffffff' : '#181a1b';
                        chart.options.plugins.tooltip.titleColor = light ? '#273138' : '#f3f5f6';
                        chart.options.plugins.tooltip.bodyColor = light ? '#273138' : '#f3f5f6';
                        chart.options.plugins.tooltip.borderColor = gridColor;
                    }
                    chart.data.datasets.forEach((dataset) => {
                        if (chart.config.type === 'doughnut') dataset.borderColor = light ? '#ffffff' : '#111416';
                    });
                    chart.update('none');
                });
            };
            if (!panel) return;

            const formatNumber = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
            const formatCurrency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            panel.querySelectorAll('[data-counter]').forEach((element) => {
                const target = Number(element.dataset.counter || 0);
                const isCurrency = element.dataset.counterCurrency === '1';
                const duration = 720;
                const started = performance.now();
                const tick = (now) => {
                    const progress = Math.min(1, (now - started) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = target * eased;
                    element.textContent = isCurrency ? formatCurrency.format(value).replace(',00', '') : formatNumber.format(value);
                    if (progress < 1) window.requestAnimationFrame(tick);
                };
                window.requestAnimationFrame(tick);
            });

            if (typeof Chart === 'undefined') return;
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const orange = '#ff7900';
            const orangeSoft = '#ffad66';
            const gray = '#9ca3aa';
            const isLightTheme = document.documentElement.classList.contains('scm-theme-light');
            const grid = isLightTheme ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
            const text = isLightTheme ? '#68727b' : '#a8adb5';
            const common = { responsive: true, maintainAspectRatio: false, animation: { duration: 650 }, plugins: { legend: { labels: { color: text, usePointStyle: true, boxWidth: 8, font: { size: 10 } } }, tooltip: { backgroundColor: isLightTheme ? '#ffffff' : '#181a1b', titleColor: isLightTheme ? '#273138' : '#f3f5f6', bodyColor: isLightTheme ? '#273138' : '#f3f5f6', borderColor: grid, borderWidth: 1 } } };
            const axis = { ticks: { color: text, font: { size: 10 } }, grid: { color: grid } };
            const monthly = <?= json_encode(array_values($dashboard_monthly_submissions), JSON_NUMERIC_CHECK) ?>;
            const values = <?= json_encode(array_values($dashboard_monthly_values), JSON_NUMERIC_CHECK) ?>;
            const statusLabels = <?= json_encode(array_keys($dashboard_status_breakdown), JSON_UNESCAPED_UNICODE) ?>;
            const statusValues = <?= json_encode(array_values($dashboard_status_breakdown), JSON_NUMERIC_CHECK) ?>;
            const typeLabels = <?= json_encode(array_keys($dashboard_type_breakdown), JSON_UNESCAPED_UNICODE) ?>;
            const typeValues = <?= json_encode(array_values($dashboard_type_breakdown), JSON_NUMERIC_CHECK) ?>;

            const monthlyCanvas = document.getElementById('kaprodiMonthlySubmissionChart');
            if (monthlyCanvas) chartInstances.push(new Chart(monthlyCanvas, { type: 'bar', data: { labels, datasets: [{ label: 'Pengajuan', data: monthly, backgroundColor: orange, borderRadius: 5, maxBarThickness: 28 }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, precision: 0 } } } } }));
            const statusCanvas = document.getElementById('kaprodiStatusChart');
            if (statusCanvas) chartInstances.push(new Chart(statusCanvas, { type: 'doughnut', data: { labels: statusLabels, datasets: [{ data: statusValues, backgroundColor: [orange, orangeSoft, '#d66518', gray, '#626a72'], borderColor: isLightTheme ? '#ffffff' : '#111416', borderWidth: 3, hoverOffset: 6 }] }, options: { ...common, cutout: '68%', plugins: { ...common.plugins, legend: { ...common.plugins.legend, position: 'bottom' } } } }));
            const valueCanvas = document.getElementById('kaprodiValueChart');
            if (valueCanvas) chartInstances.push(new Chart(valueCanvas, { type: 'line', data: { labels, datasets: [{ label: 'Nilai estimasi', data: values, borderColor: orange, backgroundColor: 'rgba(255, 121, 0, .13)', fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: orange }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, callback: (value) => 'Rp ' + formatNumber.format(value) } } } } }));
            const typeCanvas = document.getElementById('kaprodiTypeChart');
            if (typeCanvas) chartInstances.push(new Chart(typeCanvas, { type: 'bar', data: { labels: typeLabels, datasets: [{ label: 'Jumlah', data: typeValues, backgroundColor: [orange, orangeSoft, gray], borderRadius: 5, maxBarThickness: 44 }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, precision: 0 } } }, plugins: { ...common.plugins, legend: { display: false } } } }));

            const notificationButton = document.getElementById('kaprodiNotificationButton');
            panel.querySelectorAll('[data-kaprodi-notifications]').forEach((link) => link.addEventListener('click', (event) => {
                event.preventDefault();
                if (!notificationButton || typeof bootstrap === 'undefined') return;
                bootstrap.Dropdown.getOrCreateInstance(notificationButton).show();
                notificationButton.focus();
            }));
        })();
    </script>
    <script>
        window.addEventListener('scm:themechange', () => {
            if (typeof window.kaprodiSyncChartTheme === 'function') window.kaprodiSyncChartTheme();
        });
        if (typeof window.kaprodiSyncChartTheme === 'function') window.kaprodiSyncChartTheme();
    </script>
    <script>
        const needList = document.getElementById('needList');
        const template = document.getElementById('needTemplate');
        const bulkRows = document.getElementById('bulkRows');
        const jenisPengajuan = document.getElementById('jenisPengajuan');
        const requestForm = document.querySelector('form[action*="kaprodi/pengajuan/simpan"]');
        const rupiahFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

        if (!needList || !template || !bulkRows || !jenisPengajuan || !requestForm) {
            // Panel overview tidak memerlukan inisialisasi form pengajuan.
        } else {

        function parseMoney(value) {
            return Number(String(value || '').replace(/[^0-9]/g, '')) || 0;
        }

        function formatMoney(value) {
            return rupiahFormatter.format(value).replace(',00', '');
        }

        function rows() {
            return Array.from(needList.querySelectorAll('.need-row'));
        }

        function rowHasData(row) {
            return Boolean(
                row.querySelector('.need-name')?.value.trim() ||
                row.querySelector('.need-price')?.value.trim() ||
                row.querySelector('input[name="link_penawaran[]"]')?.value.trim()
            );
        }

        function appendEmptyRow() {
            needList.appendChild(template.content.cloneNode(true));
        }

        function reindexRows() {
            rows().forEach((row, index) => {
                const number = row.querySelector('.row-number');
                if (number) number.textContent = index + 1;
                row.dataset.rowNumber = index + 1;
                row.classList.toggle('is-empty', !rowHasData(row));
            });
            bulkRows.value = rows().length;
        }

        function syncTypeFields() {
            const mode = jenisPengajuan.value;
            rows().forEach((row) => {
                const typeWrap = row.querySelector('.item-type-wrap');
                const typeInput = row.querySelector('.need-type');
                const label = row.querySelector('.need-name-label');
                const nameInput = row.querySelector('.need-name');
                if (typeWrap) typeWrap.style.display = mode === 'Barang dan Jasa' ? 'block' : 'none';
                if (typeInput && mode !== 'Barang dan Jasa') typeInput.value = mode;
                if (label) label.textContent = mode === 'Jasa' ? 'Nama Jasa' : (mode === 'Barang dan Jasa' ? 'Uraian Item' : 'Nama Barang');
                if (nameInput) {
                    nameInput.placeholder = mode === 'Jasa'
                        ? 'Contoh: Jasa kalibrasi mesin'
                        : (mode === 'Barang dan Jasa' ? 'Contoh: Kamera / jasa instalasi' : 'Contoh: Kamera mirrorless');
                }
            });
        }

        function refreshRows() {
            reindexRows();
            syncTypeFields();
            updateSummary();
        }

        function applyRowCount(target) {
            const requested = Number(target);
            const count = Number.isFinite(requested) ? Math.max(0, Math.min(100, Math.floor(requested))) : rows().length;
            const currentRows = rows();
            if (count < currentRows.length) {
                const removedRows = currentRows.slice(count);
                if (removedRows.some(rowHasData)) {
                    const confirmed = window.confirm('Baris berisi data akan dihapus dari bagian paling bawah. Lanjutkan?');
                    if (!confirmed) {
                        bulkRows.value = currentRows.length;
                        return;
                    }
                }
                currentRows.slice(count).forEach((row) => row.remove());
            } else {
                for (let i = currentRows.length; i < count; i++) appendEmptyRow();
            }
            refreshRows();
        }

        document.getElementById('addNeed').addEventListener('click', () => {
            appendEmptyRow();
            refreshRows();
        });
        document.getElementById('generateRows').addEventListener('click', () => applyRowCount(bulkRows.value));
        bulkRows.addEventListener('change', () => applyRowCount(bulkRows.value));
        jenisPengajuan.addEventListener('change', () => {
            syncTypeFields();
            updateSummary();
        });

        needList.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-need');
            if (!button) return;
            const row = button.closest('.need-row');
            if (rowHasData(row) && !window.confirm('Hapus baris ini beserta data yang sudah diisi?')) return;
            row.remove();
            refreshRows();
        });
        needList.addEventListener('input', (event) => {
            if (event.target.matches('.need-name, .need-volume, .need-price, input[name="link_penawaran[]"]')) {
                updateSummary();
            }
        });
        needList.addEventListener('blur', (event) => {
            if (event.target.matches('.need-price')) {
                const value = parseMoney(event.target.value);
                event.target.value = value > 0 ? formatMoney(value) : '';
                updateSummary();
            }
        }, true);
        document.getElementById('removeEmptyRows').addEventListener('click', () => {
            const emptyRows = rows().filter((row) => !rowHasData(row));
            if (!emptyRows.length) {
                window.alert('Tidak ada baris kosong untuk dihapus.');
                return;
            }
            emptyRows.forEach((row) => row.remove());
            refreshRows();
        });
        document.getElementById('removeAllRows').addEventListener('click', () => {
            if (!rows().length) return;
            if (!window.confirm('Hapus seluruh baris pengajuan? Data yang sudah diisi akan hilang.')) return;
            needList.innerHTML = '';
            refreshRows();
        });
        document.getElementById('resetForm').addEventListener('click', () => {
            if (rows().some(rowHasData) && !window.confirm('Reset form dan hapus semua data yang sudah diisi?')) return;
            requestForm.reset();
            jenisPengajuan.value = 'Barang';
            needList.innerHTML = '';
            appendEmptyRow();
            refreshRows();
        });
        requestForm.addEventListener('submit', (event) => {
            if (!rows().some((row) => row.querySelector('.need-name')?.value.trim() !== '')) {
                event.preventDefault();
                alert('Minimal satu baris kebutuhan wajib diisi.');
            }
        });
        function updateSummary() {
            const currentRows = rows();
            let total = 0;
            let filled = 0;
            currentRows.forEach((row) => {
                const name = row.querySelector('.need-name')?.value.trim() || '';
                const volume = Math.max(0, Number(row.querySelector('.need-volume')?.value || 0));
                const price = parseMoney(row.querySelector('.need-price')?.value);
                const subtotal = volume * price;
                if (name !== '') {
                    filled++;
                }
                total += subtotal;
                const subtotalInput = row.querySelector('.subtotal-preview');
                if (subtotalInput) {
                    subtotalInput.value = formatMoney(subtotal);
                }
            });
            const tax = total * 0.20;
            document.getElementById('totalRows').textContent = currentRows.length;
            document.getElementById('filledRows').textContent = filled;
            document.getElementById('emptyRows').textContent = Math.max(0, currentRows.length - filled);
            document.getElementById('totalBeforeTax').textContent = formatMoney(total);
            document.getElementById('taxValue').textContent = formatMoney(tax);
            document.getElementById('totalAfterTax').textContent = formatMoney(total + tax);
        }
        refreshRows();
        }
    </script>
</body>
</html>
