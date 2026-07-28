<?php
function rp_kaur($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
function num_kaur($value) {
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
}
function status_class_kaur($status) {
    $map = [
        'Pengajuan' => 'status-pengajuan',
        'Revisi' => 'status-revisi',
        'Negosiasi' => 'status-negosiasi',
        'Sedang Negosiasi' => 'status-negosiasi',
        'Deal' => 'status-deal',
        'Disetujui' => 'status-approval',
        'Disetujui (Menunggu Finalisasi QR)' => 'status-Disetujui-Menunggu-Finalisasi-QR-',
        'Approval' => 'status-approval',
        'BAST' => 'status-bast',
        'Inventarisasi' => 'status-inventory',
        'Selesai' => 'status-selesai',
        'Ditolak' => 'status-ditolak',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function query_kaur($filters, $page) {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    return http_build_query($params);
}
$filters = $filters ?? [];
$stats = $stats ?? ['pengajuan' => 0, 'total_pengajuan' => 0, 'negosiasi' => 0, 'sedang_negosiasi' => 0, 'deal' => 0, 'deal_approval' => 0, 'menunggu_approval' => 0, 'bast' => 0, 'total_bast' => 0, 'laporan_deal' => 0];
$anggaran = $anggaran ?? ['tahun' => date('Y'), 'total_anggaran' => 0, 'total_pengeluaran' => 0, 'sisa_anggaran' => 0, 'persentase_penggunaan' => 0, 'catatan' => null];
$dashboard_year = (int) ($dashboard_year ?? $anggaran['tahun'] ?? date('Y'));
$dashboard_years = $dashboard_years ?? [$dashboard_year];
$dashboard_monthly_submissions = $dashboard_monthly_submissions ?? array_fill(0, 12, 0);
$dashboard_status_breakdown = $dashboard_status_breakdown ?? ['Pengajuan' => 0, 'Sedang Negosiasi' => 0, 'Deal' => 0, 'Ditolak' => 0, 'Revisi' => 0];
$dashboard_negotiation = $dashboard_negotiation ?? ['harga_awal' => 0, 'harga_negosiasi' => 0, 'penghematan' => 0];
$dashboard_activity = $dashboard_activity ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan_kaprodi ?? []);
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$active_module = $active_module ?? 'overview';
$module_meta = [
    'overview' => ['title' => 'Dashboard Kaur Laboratorium', 'desc' => 'Pilih fitur operasional Kaur Laboratorium dari panel berikut.'],
    'pengajuan' => ['title' => 'Pengajuan Kaprodi', 'desc' => 'Pantau seluruh kebutuhan barang dan jasa dari prodi.'],
    'negosiasi' => ['title' => 'Negosiasi Pengadaan', 'desc' => 'Pilih vendor dan simpan riwayat harga negosiasi.'],
    'approval' => ['title' => 'Approval Pengadaan', 'desc' => 'Setujui, revisi, atau tolak pengajuan setelah negosiasi selesai.'],
    'peminjaman' => ['title' => 'ACC Peminjaman', 'desc' => 'Setujui peminjaman yang sudah diverifikasi Laboran agar QR aktif.'],
    'anggaran' => ['title' => 'Alokasi Anggaran', 'desc' => 'Kelola total anggaran, pengeluaran, sisa, dan persentase penggunaan.'],
    'bast' => ['title' => 'BAST', 'desc' => 'Input dokumen BAST dari Logistik dan proses barang ke inventory.'],
    'laporan' => ['title' => 'Laporan', 'desc' => 'Lihat hasil akhir negosiasi yang sudah Deal.'],
];
$module = $module_meta[$active_module] ?? $module_meta['overview'];
$is_overview = $active_module === 'overview';
function kaur_module_url($module) {
    return base_url('index.php/kaur/dashboard' . ($module === 'overview' ? '' : '/' . $module));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Kaur Laboratorium') ?></title>
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
        .quick-link { border: 1px solid #e8eaed; border-radius: 8px; color: #202124; text-decoration: none; background: #fff; transition: .18s ease; min-height: 74px; }
        .quick-link:hover { transform: translateY(-2px); border-color: rgba(234, 91, 26, .35); color: #202124; }
        .quick-icon { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234, 91, 26, .12); color: #c24a13; }
        .module-strip { position: sticky; top: 78px; z-index: 15; background: rgba(245, 246, 248, .96); backdrop-filter: blur(8px); border-bottom: 1px solid #e8eaed; }
        .module-strip .nav { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 2px; }
        .module-strip .nav-link { color: #495057; white-space: nowrap; border-radius: 999px; font-size: .86rem; font-weight: 600; }
        .module-strip .nav-link.active { background: #ea5b1a; color: #fff; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi, .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-Disetujui-Menunggu-Finalisasi-QR- { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .section-anchor { scroll-margin-top: 92px; }
        .item-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; }
        .mini-label { font-size: .74rem; color: #6c757d; font-weight: 600; }
        .progress { height: 10px; border-radius: 999px; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; justify-content: flex-end; }
            .topbar-actions .btn { flex: 0 0 auto; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
            .module-strip { top: 126px; }
        }

        .scm-dashboard-kaur .dashboard-content { background: var(--scm-bg); }
        .kaur-overview-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 22px; }
        .kaur-overview-eyebrow { color: var(--scm-orange); font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .kaur-overview-heading h2 { color: var(--scm-text); font-size: clamp(1.45rem, 2vw, 2rem); margin: 5px 0 7px; }
        .kaur-overview-heading p { color: var(--scm-muted); margin: 0; font-size: .9rem; }
        .kaur-year-filter { background: var(--scm-surface); border: 1px solid var(--scm-border); border-radius: 10px; padding: 10px 12px; min-width: 180px; }
        .kaur-year-filter label { color: var(--scm-muted); display: block; font-size: .7rem; font-weight: 700; letter-spacing: .05em; margin-bottom: 5px; text-transform: uppercase; }
        .kaur-year-filter .form-select { background-color: #17191a; border-color: var(--scm-border); color: var(--scm-text); }
        .kaur-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .kaur-stat-card { background: linear-gradient(145deg, #151819 0%, #101213 100%); border: 1px solid var(--scm-border); border-radius: 14px; min-height: 148px; overflow: hidden; padding: 18px; position: relative; transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease; }
        .kaur-stat-card:hover { border-color: rgba(255, 121, 0, .55); box-shadow: 0 14px 28px rgba(0, 0, 0, .2); transform: translateY(-3px); }
        .kaur-stat-card::after { background: currentColor; content: ''; height: 3px; left: 0; opacity: .65; position: absolute; right: 0; top: 0; }
        .kaur-stat-top { align-items: flex-start; display: flex; justify-content: space-between; gap: 10px; }
        .kaur-stat-icon { align-items: center; border: 1px solid currentColor; border-radius: 10px; display: inline-flex; flex: 0 0 38px; height: 38px; justify-content: center; opacity: .95; width: 38px; }
        .kaur-stat-label { color: var(--scm-muted); font-size: .78rem; line-height: 1.35; margin-top: 15px; }
        .kaur-stat-value { color: var(--scm-text); font-size: clamp(1.1rem, 1.75vw, 1.65rem); font-weight: 700; letter-spacing: .01em; line-height: 1.2; margin-top: 6px; overflow-wrap: anywhere; }
        .kaur-stat-blue, .kaur-stat-amber, .kaur-stat-green, .kaur-stat-red, .kaur-stat-orange, .kaur-stat-cyan, .kaur-stat-purple { color: var(--scm-orange); }
        .kaur-stat-slate { color: #aeb6ba; }
        .kaur-stat-blue .kaur-stat-value, .kaur-stat-amber .kaur-stat-value, .kaur-stat-green .kaur-stat-value, .kaur-stat-red .kaur-stat-value, .kaur-stat-orange .kaur-stat-value, .kaur-stat-cyan .kaur-stat-value, .kaur-stat-purple .kaur-stat-value, .kaur-stat-slate .kaur-stat-value { color: #f5f7f8; }
        .kaur-chart-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .85fr); gap: 14px; margin-bottom: 14px; }
        .kaur-chart-panel, .kaur-activity-panel, .kaur-quick-panel { background: #111314; border: 1px solid var(--scm-border); border-radius: 14px; min-width: 0; padding: 18px; }
        .kaur-chart-header, .kaur-panel-header { align-items: flex-start; display: flex; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .kaur-chart-header h3, .kaur-panel-header h3 { color: var(--scm-text); font-size: .95rem; font-weight: 600; margin: 0; }
        .kaur-chart-header p, .kaur-panel-header p { color: var(--scm-muted); font-size: .76rem; margin: 4px 0 0; }
        .kaur-chart-note { color: var(--scm-muted); font-size: .73rem; white-space: nowrap; }
        .kaur-chart-wrap { height: 260px; position: relative; }
        .kaur-chart-wrap canvas { height: 100% !important; max-width: 100%; width: 100% !important; }
        .kaur-chart-fallback { align-items: center; color: var(--scm-muted); display: none; height: 100%; justify-content: center; text-align: center; }
        .kaur-budget-list { display: grid; gap: 12px; margin-top: 8px; }
        .kaur-budget-row { align-items: center; display: flex; justify-content: space-between; gap: 12px; }
        .kaur-budget-row span { color: var(--scm-muted); font-size: .78rem; }
        .kaur-budget-row strong { color: var(--scm-text); font-size: .8rem; }
        .kaur-savings { color: var(--scm-orange); font-size: .78rem; font-weight: 600; }
        .kaur-bottom-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 14px; }
        .kaur-activity-list { max-height: 368px; overflow-y: auto; padding-right: 3px; }
        .kaur-activity-item { align-items: flex-start; border-bottom: 1px solid rgba(255, 255, 255, .07); display: flex; gap: 12px; padding: 11px 0; }
        .kaur-activity-item:first-child { padding-top: 2px; }
        .kaur-activity-item:last-child { border-bottom: 0; }
        .kaur-activity-icon { align-items: center; background: rgba(255, 121, 0, .13); border: 1px solid rgba(255, 121, 0, .2); border-radius: 9px; color: var(--scm-orange); display: inline-flex; flex: 0 0 34px; height: 34px; justify-content: center; width: 34px; }
        .kaur-activity-copy { min-width: 0; }
        .kaur-activity-title { color: var(--scm-text); font-size: .8rem; font-weight: 600; }
        .kaur-activity-description { color: var(--scm-muted); font-size: .74rem; margin-top: 3px; overflow-wrap: anywhere; }
        .kaur-activity-meta { align-items: center; color: var(--scm-muted); display: flex; flex-wrap: wrap; font-size: .68rem; gap: 7px; margin-top: 6px; }
        .kaur-activity-meta .status-pill { font-size: .62rem; padding: 3px 7px; }
        .kaur-quick-grid { display: grid; gap: 9px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kaur-quick-action { align-items: center; background: #17191a; border: 1px solid var(--scm-border); border-radius: 10px; color: var(--scm-text); display: flex; gap: 9px; min-height: 54px; padding: 10px; text-decoration: none; transition: .2s ease; }
        .kaur-quick-action:hover { background: rgba(255, 121, 0, .11); border-color: rgba(255, 121, 0, .5); color: #fff; transform: translateY(-2px); }
        .kaur-quick-action i { color: var(--scm-orange); font-size: 1rem; }
        .kaur-quick-action span { font-size: .75rem; font-weight: 600; line-height: 1.25; }
        .topbar-actions { margin-left: auto; }
        .theme-toggle { flex: 0 0 38px !important; height: 38px; padding: 0 !important; width: 38px; }
        .scm-dashboard-kaur .text-warning { color: var(--scm-orange) !important; }
        .scm-dashboard-kaur .kaur-activity-meta .status-pengajuan,
        .scm-dashboard-kaur .kaur-activity-meta .status-revisi,
        .scm-dashboard-kaur .kaur-activity-meta .status-negosiasi,
        .scm-dashboard-kaur .kaur-activity-meta .status-deal,
        .scm-dashboard-kaur .kaur-activity-meta .status-approval,
        .scm-dashboard-kaur .kaur-activity-meta .status-bast,
        .scm-dashboard-kaur .kaur-activity-meta .status-inventory,
        .scm-dashboard-kaur .kaur-activity-meta .status-selesai { background: rgba(255, 121, 0, .14); color: #ffb477; }
        .scm-dashboard-kaur .kaur-activity-meta .status-ditolak { background: rgba(255, 255, 255, .09); color: #c6cdd1; }
        @media (max-width: 1199.98px) { .kaur-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 991.98px) { .kaur-overview-heading { align-items: stretch; flex-direction: column; gap: 14px; } .kaur-year-filter { width: 100%; } .kaur-chart-grid, .kaur-bottom-grid { grid-template-columns: 1fr; } }
        @media (max-width: 575.98px) { .kaur-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } .kaur-stat-card { min-height: 128px; padding: 14px; } .kaur-stat-icon { flex-basis: 32px; height: 32px; width: 32px; } .kaur-stat-label { font-size: .7rem; margin-top: 11px; } .kaur-stat-value { font-size: 1.08rem; } .kaur-chart-panel, .kaur-activity-panel, .kaur-quick-panel { padding: 14px; } .kaur-chart-wrap { height: 230px; } .kaur-chart-note { white-space: normal; text-align: right; } }

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
        html.scm-theme-light .scm-dashboard-kaur .kaur-year-filter, html.scm-theme-light .scm-dashboard-kaur .kaur-chart-panel, html.scm-theme-light .scm-dashboard-kaur .kaur-activity-panel, html.scm-theme-light .scm-dashboard-kaur .kaur-quick-panel { background: #ffffff; border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-year-filter .form-select { background-color: #ffffff; color: #283138; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-stat-card { background: linear-gradient(145deg, #ffffff 0%, #f7f8f9 100%); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-stat-value, html.scm-theme-light .scm-dashboard-kaur .kaur-stat-slate .kaur-stat-value { color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-quick-action { background: #f7f8f9; border-color: var(--scm-border); color: #273138; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-quick-action:hover { background: rgba(234, 91, 26, .08); color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-activity-item { border-color: rgba(35, 42, 47, .1); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-chart-wrap canvas { color: #263138; }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body class="scm-dashboard scm-dashboard-kaur">
    <aside class="dashboard-sidebar" aria-label="Navigasi Panel Kaur">
        <a class="sidebar-brand" href="<?= kaur_module_url('overview') ?>">
            <span class="sidebar-brand-mark"><i class="bi bi-diagram-3"></i></span>
            <span><strong>SCM FIK</strong><small>Panel Kaur Laboratorium</small></span>
        </a>
        <div class="sidebar-caption">Pengadaan &amp; aset</div>
        <nav class="sidebar-nav">
            <?php foreach (['overview' => ['Panel', 'bi-grid-1x2-fill'], 'pengajuan' => ['Pengajuan', 'bi-inboxes'], 'negosiasi' => ['Negosiasi', 'bi-chat-square-text'], 'approval' => ['Approval', 'bi-patch-check'], 'peminjaman' => ['ACC Peminjaman', 'bi-qr-code-scan'], 'anggaran' => ['Alokasi Anggaran', 'bi-cash-coin'], 'bast' => ['BAST', 'bi-file-earmark-pdf'], 'laporan' => ['Laporan', 'bi-file-earmark-spreadsheet']] as $key => $item): ?>
                <a class="sidebar-link <?= $active_module === $key ? 'active' : '' ?>" href="<?= kaur_module_url($key) ?>"><i class="bi <?= $item[1] ?>"></i><span><?= html_escape($item[0]) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>System operational</span></div>
    </aside>
    <div class="dashboard-content">
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="dashboard-topbar-brand d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-diagram-3"></i></span>
                    <div>
                        <div class="fw-bold">Panel Kaur Laboratorium</div>
                        <div class="small text-white-50">Pengajuan, negosiasi, approval, BAST, dan laporan</div>
                    </div>
                </div>
                <div class="topbar-actions d-flex align-items-center gap-2">
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
                                <a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $n->id_notifikasi) ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
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

    <div class="module-strip">
        <div class="container-fluid px-3 px-lg-4 py-2">
            <nav class="nav gap-2" aria-label="Navigasi fitur Kaur">
                <?php foreach (['overview' => 'Panel', 'pengajuan' => 'Pengajuan', 'negosiasi' => 'Negosiasi', 'approval' => 'Approval', 'peminjaman' => 'ACC Peminjaman', 'anggaran' => 'Alokasi Anggaran', 'bast' => 'BAST', 'laporan' => 'Laporan'] as $key => $label): ?>
                    <a class="nav-link <?= $active_module === $key ? 'active' : '' ?>" href="<?= kaur_module_url($key) ?>"><?= html_escape($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= html_escape($module['title']) ?></h1>
                <p class="text-muted mb-0"><?= html_escape($module['desc']) ?></p>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <?php if (!$is_overview): ?>
                    <a href="<?= kaur_module_url('overview') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-grid me-1"></i> Panel Kaur</a>
                <?php endif; ?>
                <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
            </div>
        </div>

        <?php if ($is_overview): ?>
        <?php
            $stat_cards = [
                ['label' => 'Total Pengajuan', 'value' => (int) ($stats['total_pengajuan'] ?? $stats['pengajuan']), 'icon' => 'bi-inboxes', 'class' => 'kaur-stat-blue', 'format' => 'number'],
                ['label' => 'Sedang Negosiasi', 'value' => (int) ($stats['sedang_negosiasi'] ?? $stats['negosiasi']), 'icon' => 'bi-chat-square-text', 'class' => 'kaur-stat-amber', 'format' => 'number'],
                ['label' => 'Deal / Approval', 'value' => (int) ($stats['deal_approval'] ?? $stats['deal']), 'icon' => 'bi-patch-check', 'class' => 'kaur-stat-green', 'format' => 'number'],
                ['label' => 'Menunggu Approval', 'value' => (int) ($stats['menunggu_approval'] ?? 0), 'icon' => 'bi-hourglass-split', 'class' => 'kaur-stat-red', 'format' => 'number'],
                ['label' => 'Total Anggaran Tahunan', 'value' => (float) ($anggaran['total_anggaran'] ?? 0), 'icon' => 'bi-wallet2', 'class' => 'kaur-stat-orange', 'format' => 'currency'],
                ['label' => 'Anggaran Terpakai', 'value' => (float) ($anggaran['total_pengeluaran'] ?? 0), 'icon' => 'bi-graph-up-arrow', 'class' => 'kaur-stat-cyan', 'format' => 'currency'],
                ['label' => 'Sisa Anggaran', 'value' => (float) ($anggaran['sisa_anggaran'] ?? 0), 'icon' => 'bi-piggy-bank', 'class' => 'kaur-stat-purple', 'format' => 'currency'],
                ['label' => 'Total Dokumen BAST', 'value' => (int) ($stats['total_bast'] ?? $stats['bast']), 'icon' => 'bi-file-earmark-pdf', 'class' => 'kaur-stat-slate', 'format' => 'number'],
            ];
            $quick_actions = [
                ['id' => 'pengajuan', 'icon' => 'bi-inboxes', 'label' => 'Pengajuan'],
                ['id' => 'negosiasi', 'icon' => 'bi-chat-square-text', 'label' => 'Negosiasi'],
                ['id' => 'approval', 'icon' => 'bi-patch-check', 'label' => 'Approval'],
                ['id' => 'peminjaman', 'icon' => 'bi-qr-code-scan', 'label' => 'ACC Peminjaman'],
                ['id' => 'anggaran', 'icon' => 'bi-cash-coin', 'label' => 'Alokasi Anggaran'],
                ['id' => 'bast', 'icon' => 'bi-file-earmark-pdf', 'label' => 'Input BAST'],
                ['id' => 'laporan', 'icon' => 'bi-file-earmark-spreadsheet', 'label' => 'Laporan'],
            ];
        ?>
        <section class="kaur-overview">
            <div class="kaur-stat-grid">
                <?php foreach ($stat_cards as $card): ?>
                    <article class="kaur-stat-card <?= $card['class'] ?>">
                        <div class="kaur-stat-top">
                            <span class="kaur-stat-icon"><i class="bi <?= $card['icon'] ?>"></i></span>
                            <i class="bi bi-three-dots text-white-50" aria-hidden="true"></i>
                        </div>
                        <div class="kaur-stat-label"><?= html_escape($card['label']) ?></div>
                        <div class="kaur-stat-value" data-counter="<?= (float) $card['value'] ?>" data-counter-format="<?= html_escape($card['format']) ?>">
                            <?= $card['format'] === 'currency' ? rp_kaur($card['value']) : number_format((int) $card['value'], 0, ',', '.') ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="kaur-chart-grid">
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Pengajuan per Bulan</h3><p>Jumlah pengajuan yang masuk sepanjang <?= $dashboard_year ?>.</p></div>
                        <span class="kaur-chart-note">Jan - Des</span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="submissionChart" aria-label="Grafik pengajuan per bulan"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Status Pengajuan</h3><p>Distribusi status tahun <?= $dashboard_year ?>.</p></div>
                        <span class="kaur-chart-note">Overview</span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="statusChart" aria-label="Grafik status pengajuan"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
            </div>

            <div class="kaur-chart-grid">
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Penggunaan Anggaran</h3><p>Pagu dan realisasi berdasarkan hasil Deal.</p></div>
                        <span class="kaur-chart-note"><?= html_escape((string) $dashboard_year) ?></span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="budgetChart" aria-label="Grafik penggunaan anggaran"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Perbandingan Negosiasi</h3><p>Harga awal dibandingkan harga setelah negosiasi.</p></div>
                        <span class="kaur-savings">Hemat <?= rp_kaur($dashboard_negotiation['penghematan'] ?? 0) ?></span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="negotiationChart" aria-label="Grafik perbandingan harga negosiasi"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
            </div>

            <div class="kaur-bottom-grid">
                <section class="kaur-activity-panel">
                    <div class="kaur-panel-header">
                        <div><h3>Recent Activity</h3><p>Aktivitas terbaru yang berkaitan dengan panel Kaur.</p></div>
                        <i class="bi bi-activity text-warning" aria-hidden="true"></i>
                    </div>
                    <div class="kaur-activity-list">
                        <?php if (empty($dashboard_activity)): ?>
                            <div class="text-muted small py-4 text-center">Belum ada aktivitas terbaru.</div>
                        <?php else: foreach ($dashboard_activity as $activity): ?>
                            <?php $activity_time = !empty($activity['time']) ? date('d M Y, H:i', strtotime($activity['time'])) : '-'; ?>
                            <div class="kaur-activity-item">
                                <span class="kaur-activity-icon"><i class="bi <?= html_escape($activity['icon'] ?? 'bi-activity') ?>"></i></span>
                                <div class="kaur-activity-copy">
                                    <div class="kaur-activity-title"><?= html_escape($activity['title'] ?? 'Aktivitas') ?></div>
                                    <div class="kaur-activity-description"><?= html_escape($activity['description'] ?? '-') ?></div>
                                    <div class="kaur-activity-meta"><span><i class="bi bi-clock me-1"></i><?= html_escape($activity_time) ?></span><span class="status-pill <?= status_class_kaur($activity['status'] ?? '') ?>"><?= html_escape($activity['status'] ?? '-') ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </section>

                <section class="kaur-quick-panel">
                    <div class="kaur-panel-header">
                        <div><h3>Quick Action</h3><p>Akses cepat tanpa menggantikan menu sidebar.</p></div>
                        <i class="bi bi-lightning-charge text-warning" aria-hidden="true"></i>
                    </div>
                    <div class="kaur-quick-grid">
                        <?php foreach ($quick_actions as $quick): ?>
                            <a class="kaur-quick-action" href="<?= kaur_module_url($quick['id']) ?>"><i class="bi <?= $quick['icon'] ?>"></i><span><?= html_escape($quick['label']) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'peminjaman'): ?>
        <section id="approval-peminjaman" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Approval Peminjaman oleh Kaur</h2>
                    <div class="text-muted small">Pengajuan yang sudah dicek Laboran akan muncul di sini. Setelah disetujui, QR Code tampil di akun peminjam.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <a href="<?= base_url('index.php/kaur/peminjaman/export_pengajuan_acc') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel ACC</a>
                    <span class="badge text-bg-warning align-self-start"><?= count($peminjaman_pending_kaur ?? []) ?> menunggu ACC</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>No. Peminjaman</th><th>Nama Peminjam</th><th>Barang</th><th>Laboratorium</th><th>Tanggal Pinjam</th><th>Tanggal Kembali</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman_pending_kaur)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Tidak ada peminjaman yang menunggu ACC Kaur.</td></tr>
                    <?php else: foreach($peminjaman_pending_kaur as $p): ?>
                        <?php
                            $barang_names = [];
                            $labs = [];
                            foreach (($p->detail_barang ?? []) as $d) {
                                $barang_names[] = ($d->nama_aset ?? '-') . ' (' . (int)($d->jumlah_pinjam ?? 0) . ')';
                                if (!empty($d->nama_ruangan)) { $labs[] = $d->nama_ruangan; }
                            }
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= html_escape($p->group_id ?: $p->id_peminjaman) ?></td>
                            <td><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><?= html_escape(implode(', ', $barang_names) ?: '-') ?></td>
                            <td><?= html_escape(implode(', ', array_unique($labs)) ?: '-') ?></td>
                            <td><?= html_escape($p->tanggal_pinjam ?? '-') ?></td>
                            <td><?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></td>
                            <td><span class="status-pill status-negosiasi"><?= html_escape($p->status ?? '-') ?></span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#loanApprovalModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-eye me-1"></i> Detail</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php foreach(($peminjaman_pending_kaur ?? []) as $p): ?>
            <div class="modal fade" id="loanApprovalModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->group_id ?: $p->id_peminjaman) ?> - <?= html_escape($p->nama_peminjam ?? '-') ?></h5>
                                <div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?> · <?= html_escape($p->tanggal_pinjam ?? '-') ?> s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><div class="mini-label">Keperluan</div><div><?= html_escape($p->keperluan ?? '-') ?></div></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light"><tr><th>Barang</th><th>Kode</th><th>Laboratorium</th><th class="text-end">Jumlah</th></tr></thead>
                                    <tbody>
                                    <?php foreach(($p->detail_barang ?? []) as $d): ?>
                                        <tr>
                                            <td><?= html_escape($d->nama_aset ?? '-') ?></td>
                                            <td><?= html_escape($d->kode_aset ?? '-') ?></td>
                                            <td><?= html_escape($d->nama_ruangan ?? '-') ?></td>
                                            <td class="text-end"><?= (int)($d->jumlah_pinjam ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label small fw-semibold">Catatan ACC Kaur</label>
                            <textarea name="catatan_kaur" class="form-control" rows="3" placeholder="Catatan persetujuan atau alasan penolakan."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak peminjaman ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-3" onclick="return confirm('Setujui peminjaman ini? QR akan menunggu finalisasi Laboran.')"><i class="bi bi-check2-circle me-1"></i> Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_module === 'pengajuan'): ?>
        <section id="pengajuan" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pengajuan Kaprodi</h2>
                    <div class="text-muted small">Data dapat dicari berdasarkan tanggal, jenis, status, dan kata kunci.</div>
                </div>
                <a href="<?= base_url('index.php/kaur/pengajuan/export_pengajuan_acc?' . query_kaur($filters, 1)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Export Pengajuan ACC</a>
            </div>
            <form method="get" action="<?= kaur_module_url('pengajuan') ?>" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kata Kunci</label>
                    <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, barang">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="jenis_pengajuan" class="form-select">
                        <option value="">Semua</option>
                        <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                                <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                                <option value="Barang dan Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang dan Jasa') ? 'selected' : '' ?>>Barang dan Jasa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach (['Pengajuan','Revisi','Sedang Negosiasi','Deal','Disetujui','Approval','BAST','Selesai','Ditolak'] as $status): ?>
                            <option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>"></div>
                <div class="col-md-1 d-grid"><button class="btn btn-fik"><i class="bi bi-search"></i></button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>Kode</th><th>Prodi</th><th>Jenis</th><th>Kebutuhan</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($pengajuan_kaprodi)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">Belum ada pengajuan sesuai filter.</td></tr>
                        <?php else: foreach ($pengajuan_kaprodi as $p): ?>
                            <tr>
                                <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                <td><div class="fw-semibold"><?= html_escape($p->nama_prodi) ?></div><div class="small text-muted"><?= html_escape($p->nama_pengajuan) ?></div></td>
                                <td><span class="badge text-bg-light border"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                <td style="min-width: 280px;">
                                    <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                    <?php foreach (($p->items ?? []) as $item): ?>
                                        <div class="small"><i class="bi bi-dot"></i><span class="badge text-bg-light border me-1"><?= html_escape($item->jenis_item ?? 'Barang') ?></span><?= html_escape($item->uraian_barang) ?> - <?= num_kaur($item->vol) ?> <?= html_escape($item->satuan) ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td><span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                <td class="text-end"><a href="<?= base_url('index.php/kaur/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-2 border-top">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'negosiasi'): ?>
        <section id="negosiasi" class="section-anchor mb-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Negosiasi oleh Kaur</h2>
                    <div class="text-muted small">Setiap simpan akan menjadi riwayat baru. Kaprodi hanya melihat status sampai hasil Deal.</div>
                </div>
            </div>
            <div class="panel-card p-3 p-lg-4 mb-3">
                <form method="get" action="<?= kaur_module_url('negosiasi') ?>" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Kata Kunci</label>
                        <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, item">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status Negosiasi</label>
                        <select name="status_negosiasi" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach (['Sedang Negosiasi','Deal','Ditolak'] as $s): ?>
                                <option value="<?= $s ?>" <?= (($filters['status_negosiasi'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Vendor</label>
                        <input type="text" name="vendor" class="form-control" value="<?= html_escape($filters['vendor'] ?? '') ?>" placeholder="Nama vendor">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Jenis</label>
                        <select name="jenis_pengajuan" class="form-select">
                            <option value="">Semua</option>
                            <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                            <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                            <option value="Barang dan Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang dan Jasa') ? 'selected' : '' ?>>Barang dan Jasa</option>
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label small fw-semibold">Dari</label><input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>"></div>
                    <div class="col-md-1"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-fik"><i class="bi bi-search"></i></button></div>
                </form>
            </div>
            <div class="vstack gap-3">
                <?php if (empty($pengajuan_kaprodi)): ?>
                    <div class="panel-card p-4 text-center text-muted">Belum ada data untuk dinegosiasikan.</div>
                <?php else: foreach ($pengajuan_kaprodi as $p): ?>
                    <div class="panel-card p-3 p-lg-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                            <div>
                                <div class="fw-bold"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?></div>
                                <div class="small text-muted"><?= html_escape($p->nama_prodi) ?> - <?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></div>
                            </div>
                            <span class="status-pill <?= status_class_kaur($p->status) ?> align-self-start"><?= html_escape($p->status) ?></span>
                        </div>
                        <div class="row g-3">
                            <?php foreach (($p->items ?? []) as $item):
                                $latest = $item->latest_negosiasi ?? null;
                                $harga_awal_referensi = (float) ($item->harga_awal_referensi ?? $item->harga_penawaran_sat ?? 0);
                                $volume_awal_referensi = (float) ($item->volume_awal_referensi ?? $item->vol ?? 0);
                                $harga_akhir = $latest ? (float) $latest->harga_negosiasi : 0;
                                $volume_akhir = $latest ? (float) $latest->volume_negosiasi : $volume_awal_referensi;
                                $total_negosiasi_item = $harga_akhir * $volume_akhir;
                                $status_negosiasi = $latest && in_array($latest->status, ['Sedang Negosiasi', 'Deal', 'Ditolak'], true) ? $latest->status : 'Sedang Negosiasi';
                            ?>
                                <div class="col-12">
                                    <form class="item-card p-3 negotiation-form" method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_negosiasi/'.$p->id_pengajuan.'/'.$item->id_item) ?>">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-lg-3">
                                                <div class="mini-label">Item</div>
                                                <div class="fw-semibold"><span class="badge text-bg-light border me-1"><?= html_escape($item->jenis_item ?? 'Barang') ?></span><?= html_escape($item->uraian_barang) ?></div>
                                                <div class="small text-muted">Referensi dari pengajuan Kaprodi</div>
                                            </div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Vendor</label><input type="text" name="vendor" class="form-control" value="<?= html_escape($latest->vendor ?? '') ?>" required></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Harga Awal</label><input type="text" name="harga_awal" class="form-control" value="<?= rp_kaur($harga_awal_referensi) ?>" disabled readonly aria-readonly="true"><div class="form-text">Dari Kaprodi</div></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Harga Setelah Negosiasi</label><input type="text" name="harga_negosiasi" class="form-control money-input negotiation-price" value="<?= $latest && $harga_akhir > 0 ? rp_kaur($harga_akhir) : '' ?>" placeholder="Rp 0" required></div>
                                            <div class="col-md-6 col-lg-1"><label class="form-label small fw-semibold">Volume Awal</label><input type="number" name="volume_awal" class="form-control" value="<?= html_escape($volume_awal_referensi) ?>" disabled readonly aria-readonly="true"><div class="form-text"><?= html_escape($item->satuan) ?></div></div>
                                            <div class="col-md-6 col-lg-1"><label class="form-label small fw-semibold">Volume Setelah Negosiasi</label><input type="number" name="volume_negosiasi" class="form-control negotiation-volume" min="0.01" step="0.01" value="<?= html_escape($volume_akhir) ?>" required><div class="form-text"><?= html_escape($item->satuan) ?></div></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Total Hasil</label><input type="text" class="form-control negotiation-total fw-semibold" value="<?= rp_kaur($total_negosiasi_item) ?>" readonly aria-readonly="true"><div class="form-text">Volume akhir x harga akhir</div></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Garansi</label><input type="text" name="garansi" class="form-control" value="<?= html_escape($latest->garansi ?? '') ?>" placeholder="Contoh: 1 tahun"></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><?php foreach (['Sedang Negosiasi','Deal','Ditolak'] as $s): ?><option value="<?= $s ?>" <?= ($status_negosiasi === $s) ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
                                            <div class="col-lg-8"><label class="form-label small fw-semibold">Catatan</label><input type="text" name="catatan" class="form-control" value="<?= html_escape($latest->catatan ?? '') ?>" placeholder="Catatan hasil negosiasi"></div>
                                            <div class="col-lg-2 d-grid"><button class="btn btn-fik"><i class="bi bi-save me-1"></i> Simpan</button></div>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-3">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'approval'): ?>
        <section id="approval" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <h2 class="h5 fw-bold mb-1">Approval Kaur</h2>
            <div class="text-muted small mb-3">Kaur dapat menyetujui, meminta revisi, atau menolak pengajuan sesuai kebutuhan proses bisnis.</div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>No. Pengajuan</th><th>Tanggal</th><th>Program Studi</th><th>Jenis</th><th>Vendor</th><th>Total Harga</th><th>Status Negosiasi</th><th>Status Approval</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (empty($pengajuan_kaprodi)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada pengajuan untuk approval.</td></tr>
                    <?php else: foreach (($pengajuan_kaprodi ?? []) as $p): ?>
                        <?php
                            $vendors = [];
                            $nego_statuses = [];
                            $can_approve = !empty($p->items);
                            foreach (($p->items ?? []) as $approval_item) {
                                $latest = $approval_item->latest_negosiasi ?? null;
                                if ($latest) {
                                    if (!empty($latest->vendor)) { $vendors[] = $latest->vendor; }
                                    $nego_statuses[] = $latest->status;
                                }
                                if (!$latest || $latest->status !== 'Deal') { $can_approve = false; }
                            }
                            $vendor_label = $vendors ? implode(', ', array_unique($vendors)) : '-';
                            $nego_label = $nego_statuses ? implode(', ', array_unique($nego_statuses)) : 'Belum Negosiasi';
                            $total_harga = ($p->summary['total_negosiasi'] ?? 0) > 0 ? $p->summary['total_negosiasi'] : ($p->summary['total_setelah_pajak'] ?? $p->summary['total_penawaran'] ?? 0);
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                            <td><?= html_escape($p->nama_prodi) ?></td>
                            <td><span class="badge text-bg-light border"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                            <td><?= html_escape($vendor_label) ?></td>
                            <td><?= rp_kaur($total_harga) ?></td>
                            <td><span class="status-pill <?= status_class_kaur($nego_label) ?>"><?= html_escape($nego_label) ?></span></td>
                            <td><span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#approvalModal<?= (int) $p->id_pengajuan ?>"><i class="bi bi-eye me-1"></i> Detail</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-2 border-top">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php foreach (($pengajuan_kaprodi ?? []) as $p): ?>
            <?php
                $can_approve_modal = !empty($p->items);
                foreach (($p->items ?? []) as $approval_item) {
                    if (empty($approval_item->latest_negosiasi) || $approval_item->latest_negosiasi->status !== 'Deal') {
                        $can_approve_modal = false;
                        break;
                    }
                }
                $auto_approved = (($p->status ?? '') === 'Approval');
            ?>
            <div class="modal fade" id="approvalModal<?= (int) $p->id_pengajuan ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?></h5>
                                <div class="small text-muted"><?= html_escape($p->nama_prodi) ?> · <?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><div class="mini-label">Kebutuhan</div><div><?= html_escape($p->kebutuhan_lab ?: '-') ?></div></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light"><tr><th>Item</th><th>Volume</th><th>Harga Awal</th><th>Vendor</th><th>Harga Negosiasi</th><th>Status</th><th>Garansi</th><th>Catatan</th></tr></thead>
                                    <tbody>
                                        <?php foreach (($p->items ?? []) as $item): $latest = $item->latest_negosiasi ?? null; ?>
                                            <tr>
                                                <td><span class="badge text-bg-light border me-1"><?= html_escape($item->jenis_item ?? 'Barang') ?></span><?= html_escape($item->uraian_barang) ?></td>
                                                <td><?= num_kaur($item->volume_awal_referensi ?? $item->vol) ?> <?= html_escape($item->satuan) ?></td>
                                                <td><?= rp_kaur($item->harga_awal_referensi ?? $item->harga_penawaran_sat ?? 0) ?></td>
                                                <td><?= html_escape($latest->vendor ?? '-') ?></td>
                                                <td><?= $latest ? rp_kaur($latest->harga_negosiasi) : '-' ?></td>
                                                <td><?= html_escape($latest->status ?? 'Belum Negosiasi') ?></td>
                                                <td><?= html_escape($latest->garansi ?? '-') ?></td>
                                                <td><?= html_escape($latest->catatan ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label small fw-semibold">Catatan Approval / Revisi</label>
                            <textarea name="catatan_approval" class="form-control" rows="3" placeholder="Catatan approval, revisi, atau alasan penolakan."><?= html_escape($p->catatan_approval ?? '') ?></textarea>
                            <?php if ($auto_approved): ?><div class="alert alert-success py-2 small mt-2 mb-0"><i class="bi bi-check-circle me-1"></i> Semua item sudah Deal. Pengajuan otomatis berstatus Approval dan dapat dilanjutkan ke Alokasi Anggaran/BAST.</div><?php elseif (!$can_approve_modal): ?><div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i> Pengajuan aktif setelah semua item negosiasi berstatus Deal.</div><?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/revisi') ?>" class="btn btn-warning rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i> Revisi</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/tolak') ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <?php if (!$auto_approved): ?><button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>" class="btn btn-success rounded-pill px-3" <?= $can_approve_modal ? '' : 'disabled' ?>><i class="bi bi-check2 me-1"></i> Setujui</button><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_module === 'anggaran'): ?>
        <section id="anggaran" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold mb-2">Pagu Anggaran Tahunan</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-label">Tahun Anggaran</div><div class="fw-bold"><?= (int) $anggaran['tahun'] ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pagu Anggaran</div><div class="fw-bold"><?= rp_kaur($anggaran['total_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengadaan Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengadaan_deal'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengeluaran Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengeluaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Sisa Anggaran</div><div class="fw-bold text-success"><?= rp_kaur($anggaran['sisa_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Penggunaan</div><div class="fw-bold"><?= number_format((float) $anggaran['persentase_penggunaan'], 1, ',', '.') ?>%</div></div>
                        <div class="col-6"><div class="mini-label">Penghematan CAPEX</div><div class="fw-bold text-primary"><?= rp_kaur($anggaran['penghematan_capex'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Belum Terealisasi</div><div class="fw-bold"><?= (int) ($anggaran['belum_terealisasi'] ?? 0) ?> pengajuan</div></div>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: <?= min(100, (float) $anggaran['persentase_penggunaan']) ?>%"></div></div>
                </div>
                <div class="col-lg-6">
                    <form method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_anggaran') ?>" class="row g-2">
                        <div class="col-md-4"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= (int) $anggaran['tahun'] ?>" required></div>
                        <div class="col-md-8"><label class="form-label small fw-semibold">Total Anggaran</label><input type="text" name="total_anggaran" class="form-control money-input" value="<?= $anggaran['total_anggaran'] ? rp_kaur($anggaran['total_anggaran']) : '' ?>" required></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Catatan</label><textarea name="catatan" class="form-control" rows="2"><?= html_escape($anggaran['catatan'] ?? '') ?></textarea></div>
                        <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-fik rounded-pill px-4"><i class="bi bi-save me-1"></i> Simpan Anggaran</button></div>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'bast'): ?>
        <section id="bast" class="section-anchor mb-4">
            <h2 class="h5 fw-bold mb-3">Input BAST dari Logistik</h2>
            <div class="alert alert-warning border-0 rounded-3 small mb-3"><i class="bi bi-hourglass-split me-1"></i> Template resmi BAST berstatus <strong>Hold</strong>. Struktur modul sudah siap, sementara Laboran/Kaur tetap dapat mengunggah PDF atau hasil scan dari Logistik.</div>
            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="panel-card p-3 p-lg-4 h-100">
                        <div class="accordion" id="bastAccordion">
                            <?php $bast_rows = $bast_ready ?? []; ?>
                            <?php if (empty($bast_rows)): ?>
                                <div class="text-muted small p-3">Belum ada pengajuan yang siap BAST. BAST baru tersedia setelah pengajuan disetujui Kaur.</div>
                            <?php endif; ?>
                            <?php foreach ($bast_rows as $index => $p): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#bastForm<?= (int) $p->id_pengajuan ?>">
                                            <?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?> <span class="badge text-bg-success ms-2"><?= html_escape($p->status) ?></span>
                                        </button>
                                    </h2>
                                    <div id="bastForm<?= (int) $p->id_pengajuan ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#bastAccordion">
                                        <form class="accordion-body row g-2" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/kaur/pengajuan/simpan_bast/'.$p->id_pengajuan) ?>">
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Nomor BAST</label><input type="text" name="nomor_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="tanggal_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Jenis</label><select name="jenis_bast" class="form-select"><option value="Barang" <?= (($p->jenis_pengajuan ?? 'Barang') === 'Barang') ? 'selected' : '' ?>>Barang</option><option value="Jasa" <?= (($p->jenis_pengajuan ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option><option value="Barang dan Jasa" <?= (($p->jenis_pengajuan ?? '') === 'Barang dan Jasa') ? 'selected' : '' ?>>Barang dan Jasa</option></select></div>
                                            <div class="col-md-7"><label class="form-label small fw-semibold">File PDF/Scan</label><input type="file" name="file_bast" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
                                            <div class="col-md-5"><label class="form-label small fw-semibold">Catatan</label><input type="text" name="catatan" class="form-control"></div>
                                            <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-fik rounded-pill px-4"><i class="bi bi-upload me-1"></i> Simpan BAST</button></div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="panel-card p-3 p-lg-4 h-100">
                        <h3 class="h6 fw-bold mb-3">BAST Terakhir</h3>
                        <div class="vstack gap-2">
                            <?php if (empty($bast_list)): ?>
                                <div class="text-muted small">Belum ada dokumen BAST.</div>
                            <?php else: foreach ($bast_list as $b): ?>
                                <div class="border rounded-3 p-2">
                                    <div class="fw-semibold"><?= html_escape($b->nomor_bast) ?></div>
                                    <div class="small text-muted"><?= html_escape($b->nama_pengajuan ?? '-') ?> - <?= date('d/m/Y', strtotime($b->tanggal_bast)) ?></div>
                                    <?php if (!empty($b->file_bast)): ?><a class="small" href="<?= base_url($b->file_bast) ?>" target="_blank">Lihat file</a><?php endif; ?>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'laporan'): ?>
        <section id="laporan" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <h2 class="h5 fw-bold mb-1">Laporan Hasil Negosiasi</h2>
            <div class="text-muted small mb-3">Hanya data dengan status Deal yang tampil sebagai dokumentasi resmi hasil akhir.</div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>Pengajuan</th><th>Item</th><th>Vendor</th><th>Harga Awal</th><th>Harga Akhir</th><th>Selisih</th><th>Volume</th><th>Garansi</th><th>Catatan</th></tr></thead>
                    <tbody>
                        <?php if (empty($laporan_negosiasi)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-5">Belum ada negosiasi yang Deal.</td></tr>
                        <?php else: foreach ($laporan_negosiasi as $lap): ?>
                            <tr>
                                <td><div class="fw-semibold"><?= html_escape($lap->kode_pengajuan) ?></div><div class="small text-muted"><?= html_escape($lap->nama_pengajuan) ?></div></td>
                                <td><?= html_escape($lap->uraian_barang) ?></td>
                                <td><?= html_escape($lap->vendor ?: '-') ?></td>
                                <td><?= rp_kaur($lap->harga_awal) ?></td>
                                <td class="fw-semibold text-success"><?= rp_kaur($lap->harga_negosiasi) ?></td>
                                <td><?= rp_kaur($lap->selisih_harga) ?></td>
                                <td><?= num_kaur($lap->volume_negosiasi) ?> <?= html_escape($lap->satuan) ?></td>
                                <td><?= html_escape($lap->garansi ?: '-') ?></td>
                                <td><?= html_escape($lap->catatan ?: '-') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const dashboardData = <?= json_encode([
                'monthly' => array_values($dashboard_monthly_submissions),
                'statuses' => $dashboard_status_breakdown,
                'budget' => [
                    'pagu' => (float) ($anggaran['total_anggaran'] ?? 0),
                    'terpakai' => (float) ($anggaran['total_pengeluaran'] ?? 0),
                    'sisa' => (float) ($anggaran['sisa_anggaran'] ?? 0),
                ],
                'negotiation' => [
                    'harga_awal' => (float) ($dashboard_negotiation['harga_awal'] ?? 0),
                    'harga_negosiasi' => (float) ($dashboard_negotiation['harga_negosiasi'] ?? 0),
                ],
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

            const formatRupiah = (value) => 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
            const formatCounter = (value, format) => format === 'currency' ? formatRupiah(value) : Math.round(Number(value) || 0).toLocaleString('id-ID');

            document.querySelectorAll('[data-counter]').forEach((element) => {
                const target = Number(element.dataset.counter || 0);
                const format = element.dataset.counterFormat || 'number';
                const startedAt = performance.now();
                const duration = 760;

                const tick = (now) => {
                    const progress = Math.min(1, (now - startedAt) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    element.textContent = formatCounter(target * eased, format);
                    if (progress < 1) {
                        window.requestAnimationFrame(tick);
                    }
                };
                window.requestAnimationFrame(tick);
            });

            const chartInstances = [];
            const isLightTheme = document.documentElement.classList.contains('scm-theme-light');
            const chartText = isLightTheme ? '#273138' : '#f3f5f6';
            const chartMuted = isLightTheme ? '#68727b' : '#8f989f';
            const chartGrid = isLightTheme ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
            const orange = '#ff7900';
            const orangeSoft = '#f39a52';
            const orangeDeep = '#c65b18';
            const warmGray = '#aeb6ba';
            const darkGray = '#4b5257';
            const chartFont = { family: 'Poppins, sans-serif' };
            const scaleOptions = {
                x: { ticks: { color: chartMuted, font: chartFont }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: chartMuted, precision: 0, font: chartFont }, grid: { color: chartGrid } },
            };
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                plugins: { legend: { labels: { color: chartText, usePointStyle: true, padding: 16, font: chartFont } }, tooltip: { backgroundColor: isLightTheme ? '#ffffff' : '#181a1b', titleColor: chartText, bodyColor: chartText, borderColor: chartGrid, borderWidth: 1 } },
            };

            const showFallback = (canvas) => {
                if (!canvas) return;
                const fallback = canvas.parentElement.querySelector('.kaur-chart-fallback');
                if (fallback) fallback.style.display = 'flex';
                canvas.style.display = 'none';
            };

            if (typeof window.Chart === 'undefined') {
                document.querySelectorAll('.kaur-chart-wrap canvas').forEach(showFallback);
            } else {
                const submissionCanvas = document.getElementById('submissionChart');
                if (submissionCanvas) {
                    const chart = new window.Chart(submissionCanvas, {
                        type: 'bar',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                            datasets: [{ label: 'Pengajuan', data: dashboardData.monthly || [], backgroundColor: orange, borderRadius: 6, maxBarThickness: 24 }],
                        },
                        options: { ...baseOptions, scales: scaleOptions, plugins: { ...baseOptions.plugins, legend: { display: false } } },
                    });
                    chartInstances.push(chart);
                }

                const statusCanvas = document.getElementById('statusChart');
                if (statusCanvas) {
                    const statusLabels = ['Pengajuan', 'Sedang Negosiasi', 'Deal', 'Ditolak', 'Revisi'];
                    const chart = new window.Chart(statusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{ data: statusLabels.map((label) => Number((dashboardData.statuses || {})[label] || 0)), backgroundColor: [orange, orangeSoft, orangeDeep, warmGray, darkGray], borderColor: isLightTheme ? '#ffffff' : '#111314', borderWidth: 4, hoverOffset: 5 }],
                        },
                        options: { ...baseOptions, cutout: '68%', plugins: { ...baseOptions.plugins, legend: { position: 'bottom' } } },
                    });
                    chartInstances.push(chart);
                }

                const budgetCanvas = document.getElementById('budgetChart');
                if (budgetCanvas) {
                    const budget = dashboardData.budget || {};
                    const chart = new window.Chart(budgetCanvas, {
                        type: 'bar',
                        data: { labels: ['Total Pagu', 'Terpakai', 'Sisa'], datasets: [{ label: 'Anggaran', data: [budget.pagu || 0, budget.terpakai || 0, budget.sisa || 0], backgroundColor: [warmGray, orange, orangeDeep], borderRadius: 7, maxBarThickness: 52 }] },
                        options: { ...baseOptions, scales: { ...scaleOptions, y: { ...scaleOptions.y, ticks: { ...scaleOptions.y.ticks, callback: (value) => formatRupiah(value) } } }, plugins: { ...baseOptions.plugins, legend: { display: false }, tooltip: { callbacks: { label: (context) => formatRupiah(context.raw) } } } },
                    });
                    chartInstances.push(chart);
                }

                const negotiationCanvas = document.getElementById('negotiationChart');
                if (negotiationCanvas) {
                    const negotiation = dashboardData.negotiation || {};
                    const chart = new window.Chart(negotiationCanvas, {
                        type: 'bar',
                        data: { labels: ['Harga Awal', 'Harga Setelah Negosiasi'], datasets: [{ label: 'Nilai Pengadaan', data: [negotiation.harga_awal || 0, negotiation.harga_negosiasi || 0], backgroundColor: [warmGray, orange], borderRadius: 7, maxBarThickness: 70 }] },
                        options: { ...baseOptions, scales: { ...scaleOptions, y: { ...scaleOptions.y, ticks: { ...scaleOptions.y.ticks, callback: (value) => formatRupiah(value) } } }, plugins: { ...baseOptions.plugins, legend: { display: false }, tooltip: { callbacks: { label: (context) => formatRupiah(context.raw) } } } },
                    });
                    chartInstances.push(chart);
                }
            }

            window.kaurSyncChartTheme = () => {
                const light = document.documentElement.classList.contains('scm-theme-light');
                const text = light ? '#273138' : '#f3f5f6';
                const muted = light ? '#68727b' : '#8f989f';
                const grid = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                chartInstances.forEach((chart) => {
                    if (chart.options.scales) {
                        Object.values(chart.options.scales).forEach((scale) => {
                            if (scale.ticks) scale.ticks.color = muted;
                            if (scale.grid) scale.grid.color = grid;
                        });
                    }
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = text;
                    if (chart.options.plugins?.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = light ? '#ffffff' : '#181a1b';
                        chart.options.plugins.tooltip.titleColor = text;
                        chart.options.plugins.tooltip.bodyColor = text;
                        chart.options.plugins.tooltip.borderColor = grid;
                    }
                    chart.data.datasets.forEach((dataset) => {
                        if (chart.config.type === 'doughnut') dataset.borderColor = light ? '#ffffff' : '#111314';
                    });
                    chart.update('none');
                });
            };

            window.addEventListener('scm:themechange', window.kaurSyncChartTheme);
            window.kaurSyncChartTheme();
        })();
    </script>
    <script>
        const parseKaurMoney = (value) => Number(String(value || '').replace(/[^0-9]/g, '')) || 0;

        const updateNegotiationTotal = (form) => {
            const price = parseKaurMoney(form.querySelector('.negotiation-price')?.value);
            const volume = Number(form.querySelector('.negotiation-volume')?.value || 0);
            const total = form.querySelector('.negotiation-total');
            if (total) {
                total.value = 'Rp ' + Math.round(price * volume).toLocaleString('id-ID');
            }
        };

        document.querySelectorAll('.money-input').forEach((input) => {
            input.addEventListener('blur', () => {
                const digits = input.value.replace(/[^0-9]/g, '');
                if (!digits) return;
                input.value = 'Rp ' + Number(digits).toLocaleString('id-ID');
                updateNegotiationTotal(input.closest('.negotiation-form'));
            });
        });

        document.querySelectorAll('.negotiation-form').forEach((form) => {
            form.querySelectorAll('.negotiation-price, .negotiation-volume').forEach((input) => {
                input.addEventListener('input', () => updateNegotiationTotal(form));
            });
            updateNegotiationTotal(form);
        });
    </script>
</body>
</html>
