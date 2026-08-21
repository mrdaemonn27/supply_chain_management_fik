<?php
$status_options = isset($status_options) && is_array($status_options) ? $status_options : ['', 'Menunggu ACC Kaprodi', 'Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur', 'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)', 'Ditolak', 'Kedaluwarsa / Ditolak Otomatis'];
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($peminjaman ?? []), 'per_page' => 10];
$current_per_page = (string) ($pagination['per_page'] ?? '10');
$compact_pagination_pages = static function ($current, $last) {
    $current = max(1, min((int) $current, max(1, (int) $last)));
    $last = max(1, (int) $last);
    if ($last <= 7) return range(1, $last);
    if ($current <= 3) return array_merge(range(1, 5), ['ellipsis-after', $last]);
    if ($current >= $last - 2) return array_merge([$last - 4, $last - 3, $last - 2, $last - 1, $last]);
    return array_merge([1, 'ellipsis-before'], range($current - 2, $current + 2), ['ellipsis-after', $last]);
};
$filter_rows = isset($filter_rows) && is_array($filter_rows) ? array_values($filter_rows) : [];
if (empty($filter_rows)) $filter_rows = [['field' => 'peminjam', 'value' => '']];
$filter_fields = ['peminjam' => 'Peminjam / NIM', 'barang' => 'Nama barang / kode', 'status' => 'Status', 'tanggal' => 'Tanggal pinjam', 'keperluan' => 'Keperluan'];
$filter_suggestions = isset($filter_suggestions) && is_array($filter_suggestions) ? $filter_suggestions : [];
$approval_actionable = (int) ($approval_actionable ?? 0);
$export_params = [
    'status' => $filters['status'] ?? '',
    'q' => $filters['pencarian'] ?? '',
    'tanggal' => $filters['tanggal'] ?? '',
];
foreach ($filter_rows as $filter_row) {
    $export_params['filter_field'][] = $filter_row['field'] ?? 'peminjam';
    $export_params['filter_value'][] = $filter_row['value'] ?? '';
}
$export_query = http_build_query($export_params);
$export_url = base_url('index.php/admin/peminjaman/export_pengajuan_acc' . ($export_query ? '?' . $export_query : ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Data Peminjaman' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/loan-progress.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/loan-progress.css'); ?>">
    <style>
        body { background: #f5f6f8; font-family: 'Poppins', sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; border-bottom: 4px solid #ea5b1a; color: #fff; }
        .panel-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(32,33,36,.05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234,91,26,.16); }
        .table thead th { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table td { vertical-align: middle; }
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 600; font-size: .75rem; }
        .status-Menunggu-Persetujuan { background: rgba(245,158,11,.14); color: #a16207; }
        .status-Menunggu-ACC-Kaprodi { background: rgba(245,158,11,.14); color: #a16207; }
        .status-Menunggu-Verifikasi-Laboran { background: rgba(245,158,11,.14); color: #a16207; }
        .status-Menunggu-Pengecekan-Laboran { background: rgba(245,158,11,.14); color: #a16207; }
        .status-Menunggu-ACC-Kaur { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Disetujui-Menunggu-Finalisasi-QR- { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Disetujui-Menunggu-Pengambilan- { background: rgba(25,135,84,.12); color: #198754; }
        .status-Sedang-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Dikembalikan { background: rgba(25,135,84,.12); color: #198754; }
        .status-Ditolak { background: rgba(220,53,69,.12); color: #dc3545; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .loan-table-card {
            --loan-bg: var(--scm-theme-surface, #ffffff);
            --loan-soft: var(--scm-theme-surface-soft, #f9fafb);
            --loan-border: var(--scm-theme-border, #e5e7eb);
            --loan-text: var(--scm-theme-text, #1f2937);
            --loan-muted: var(--scm-theme-muted, #6b7280);
            overflow: hidden;
            border-color: var(--loan-border);
            background: var(--loan-bg);
        }
        .loan-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .loan-table {
            min-width: 1280px;
            margin: 0;
            --bs-table-bg: var(--loan-bg);
            --bs-table-color: var(--loan-text);
            --bs-table-border-color: var(--loan-border);
        }
        .loan-table.table-hover > tbody > tr:hover > * { --bs-table-bg-state: rgba(234, 91, 26, .045); }
        .loan-table thead th {
            padding: 14px 18px;
            color: #111827 !important;
            background: var(--loan-soft);
            border-color: var(--loan-border);
            font-size: .72rem;
            font-family: inherit;
            font-weight: 700 !important;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .loan-table thead th, .loan-table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .loan-table tbody tr { min-height: 82px; }
        .loan-table tbody td {
            padding: 16px 18px;
            color: var(--loan-text);
            background: var(--loan-bg);
            border-color: var(--loan-border);
            line-height: 1.5;
            vertical-align: middle;
        }
        .loan-table th:first-child, .loan-table td:first-child { min-width:70px; width:70px; }
        .loan-table th:nth-child(2), .loan-table td:nth-child(2) { min-width:220px; }
        .loan-table th:nth-child(3), .loan-table td:nth-child(3) { min-width:340px; text-align:left; }
        .loan-table th:nth-child(4), .loan-table td:nth-child(4) { min-width:175px; text-align:left; }
        .loan-table th:nth-child(5), .loan-table td:nth-child(5) { min-width:270px; }
        .loan-table th:nth-child(6), .loan-table td:nth-child(6) { min-width:210px; }
        .loan-list-summary { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; padding:1rem; border-bottom:1px solid var(--loan-border); }
        .loan-list-summary h2 { margin:0; font-size:1rem; font-weight:700; }
        .loan-list-summary p { margin:.2rem 0 0; color:var(--loan-muted); font-size:.76rem; }
        .loan-table .soft-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            height: 42px;
            min-height: 42px;
            padding: 7px 12px;
            border: 1px solid var(--loan-border);
            border-radius: 8px;
            color: var(--loan-text);
            background: var(--loan-bg);
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
        }
        .loan-action-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 126px; min-height: 36px; transition: transform .16s ease, background-color .16s ease, border-color .16s ease, color .16s ease; }
        .loan-action-btn:hover { transform: translateY(-1px); }
        .loan-pagination-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid var(--loan-border);
            color: var(--loan-muted);
            background: var(--loan-soft);
        }
        .loan-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; }
        .loan-pagination-summary, .loan-pagination-status { font-size: .72rem; white-space: nowrap; }
        .loan-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .loan-pagination-status { text-align: center; }
        .loan-pagination { margin: 0; }
        .loan-approval-summary { display:grid; gap:5px; width:100%; min-width:0; text-align:left; }
        .loan-approval-summary__status { display:flex; align-items:center; min-width:0; }
        .loan-approval-summary__status-text { display:inline-flex; align-items:center; min-width:0; max-width:100%; color:var(--loan-text); font-size:.72rem; font-weight:600; line-height:1.25; white-space:normal; }
        .loan-approval-summary__status-dot { width:7px; height:7px; flex:0 0 7px; margin-right:7px; border-radius:50%; background:#9aa3af; }
        .loan-approval-summary__status-dot.status-Ditolak { background:#dc4c5a; }
        .loan-approval-summary__status-dot.status-Sedang-Dipinjam,
        .loan-approval-summary__status-dot.status-Dipinjam { background:#3b82f6; }
        .loan-approval-summary__status-dot.status-Disetujui-Menunggu-Pengambilan-,
        .loan-approval-summary__status-dot.status-Dikembalikan { background:#2f9e68; }
        .loan-approval-summary__status-dot.status-Menunggu-Persetujuan,
        .loan-approval-summary__status-dot.status-Menunggu-ACC-Kaprodi,
        .loan-approval-summary__status-dot.status-Menunggu-Verifikasi-Laboran,
        .loan-approval-summary__status-dot.status-Menunggu-Pengecekan-Laboran,
        .loan-approval-summary__status-dot.status-Menunggu-ACC-Kaur,
        .loan-approval-summary__status-dot.status-Disetujui-Menunggu-Finalisasi-QR- { background:#ea5b1a; }
        .loan-approval-summary__actions { display:flex; align-items:center; justify-content:space-between; gap:8px; min-width:0; }
        .loan-approval-detail-btn { display:inline-flex; align-items:center; flex:0 0 auto; gap:4px; padding:0; border:0; color:#5f6b78; background:transparent; font-size:.68rem; font-weight:600; text-decoration:none; }
        .loan-approval-detail-btn:hover { color:#ea5b1a; text-decoration:underline; }
        .loan-approval-detail-btn .bi { transition:transform .16s ease; }
        .loan-approval-detail-btn:hover .bi { transform:translateX(2px); }
        .loan-approval-timeline { position:relative; display:grid; gap:0; margin:2px 0 18px; }
        .loan-approval-timeline::before { content:""; position:absolute; top:15px; bottom:15px; left:14px; width:1px; background:#dfe4ea; }
        .loan-approval-timeline__item { position:relative; display:grid; grid-template-columns:30px minmax(0, 1fr); align-items:center; min-height:40px; }
        .loan-approval-timeline__marker { position:relative; z-index:1; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #dfe4ea; border-radius:50%; color:#8a94a2; background:#f7f8fa; font-size:.72rem; }
        .loan-approval-timeline__label { padding-left:10px; color:#737d8b; font-size:.78rem; font-weight:600; }
        .loan-approval-timeline__item.is-complete .loan-approval-timeline__marker { color:#177245; border-color:#bfe5cd; background:#ecf8f1; }
        .loan-approval-timeline__item.is-complete .loan-approval-timeline__label { color:#177245; }
        .loan-approval-timeline__item.is-current .loan-approval-timeline__marker { color:#a94714; border-color:#f1b28d; background:#fff4ec; }
        .loan-approval-timeline__item.is-current .loan-approval-timeline__label { color:#a94714; }
        .loan-approval-timeline__item.is-rejected .loan-approval-timeline__marker { color:#b4232f; border-color:#f1c4c8; background:#fff1f2; }
        .loan-approval-timeline__item.is-rejected .loan-approval-timeline__label { color:#b4232f; }
        .loan-approval-modal .modal-dialog { max-width:440px; }
        .loan-approval-modal .modal-content { border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 18px 50px rgba(31,41,55,.14); }
        .loan-approval-modal .modal-header { padding:16px 18px 12px; border-bottom:1px solid #eef0f3; }
        .loan-approval-modal .modal-title { font-size:1rem; }
        .loan-approval-modal .modal-body { padding:16px 18px 18px; }
        .loan-approval-modal__status { border-top:1px solid #eef0f3; padding-top:14px; }
        .loan-approval-modal__status .soft-badge { max-width:100%; padding:6px 10px; font-size:.7rem; line-height:1.25; white-space:normal; }
        .loan-approval-modal__evidence { margin-top:14px; padding-top:14px; border-top:1px solid #eef0f3; }
        .loan-approval-modal__evidence-actions { display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .loan-approval-modal__evidence .loan-evidence-btn { min-width:0; min-height:36px; padding:7px 12px; font-size:.68rem; }
        .loan-approval-modal__evidence-empty { color:#8a94a2; font-size:.72rem; }
        .loan-approval-modal__actions { display:flex; flex-wrap:wrap; gap:6px; margin-top:12px; }
        .loan-evidence-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:5px; }
        .loan-evidence-actions .loan-evidence-btn { min-width:auto; min-height:28px; padding:4px 8px; font-size:.64rem; }
        .loan-evidence-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-width: 126px;
            min-height: 34px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            white-space: normal;
        }
        .loan-evidence-btn .bi { font-size: .88rem; }
        .loan-table .soft-badge.status-Menunggu-Persetujuan,
        .loan-table .soft-badge.status-Menunggu-ACC-Kaprodi,
        .loan-table .soft-badge.status-Menunggu-Verifikasi-Laboran,
        .loan-table .soft-badge.status-Menunggu-Pengecekan-Laboran { color:#8a5800; border-color:#f2cf85; background:#fff4d6; }
        .loan-table .soft-badge.status-Menunggu-ACC-Kaur,
        .loan-table .soft-badge.status-Disetujui-Menunggu-Finalisasi-QR-,
        .loan-table .soft-badge.status-Sedang-Dipinjam,
        .loan-table .soft-badge.status-Dipinjam { color:#0759bd; border-color:#9ec5fe; background:#e9f2ff; }
        .loan-table .soft-badge.status-Disetujui-Menunggu-Pengambilan-,
        .loan-table .soft-badge.status-Dikembalikan { color:#146c43; border-color:#a3cfbb; background:#eaf7f0; }
        .loan-table .soft-badge.status-Ditolak { color:#b02a37; border-color:#f1aeb5; background:#fbeaec; }
        .loan-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            min-height: 34px;
            padding: .35rem .58rem;
            border-color: var(--loan-border);
            color: var(--loan-text);
            background: var(--loan-bg);
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .loan-pagination .page-link:hover { color: #ea5b1a; background: var(--loan-soft); }
        .loan-pagination .page-item.active .page-link { color: #ffffff; background: #ea5b1a; border-color: #ea5b1a; }
        .loan-pagination .page-item.disabled .page-link { color: var(--loan-muted); background: var(--loan-soft); opacity: .62; }
        .admin-filter-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
        .admin-filter-heading h2 { margin:0; font-size:1rem; font-weight:700; }
        .admin-filter-heading p { margin:0; color:#6b7280; font-size:.78rem; }
        .admin-filter-list { display:grid; gap:.65rem; }
        .admin-filter-row { display:grid; grid-template-columns:minmax(185px, .7fr) minmax(0, 1.5fr) auto; gap:.65rem; align-items:center; }
        .admin-filter-row .form-select, .admin-filter-row .form-control { min-height:42px; }
        .admin-filter-tools { display:flex; align-items:center; gap:.45rem; }
        .admin-filter-icon { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; }
        .admin-filter-add { border-color:#ea5b1a; color:#c84f17; }
        .admin-filter-add:hover { background:#ea5b1a; border-color:#ea5b1a; color:#fff; }
        .admin-filter-actions { display:flex; flex-wrap:wrap; gap:.65rem; margin-top:1rem; }
        .admin-filter-actions .btn { min-height:40px; }
        .admin-filter-note { color:#6b7280; font-size:.74rem; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .loan-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .loan-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
            .loan-table .soft-badge { width: 250px; min-width: 250px; max-width: 250px; }
            .loan-approval-modal .modal-dialog { margin: .75rem; }
            .loan-approval-summary__actions { align-items:center; }
            .loan-evidence-actions { justify-content:flex-end; }
            .admin-filter-row { grid-template-columns:1fr; gap:.45rem; padding:.75rem; border:1px solid #edf0f2; border-radius:10px; }
            .admin-filter-tools { justify-content:flex-end; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
<header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div class="fw-bold"><i class="bi bi-clipboard-data me-2 text-warning"></i>Data Peminjaman</div><div class="topbar-actions d-flex gap-2"><div class="dropdown"><button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi"><i class="bi bi-bell"></i><?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu"><div class="fw-bold px-2 py-1">Notifikasi</div><?php if (empty($notif_items)): ?><div class="small text-muted px-2 py-3">Belum ada notifikasi.</div><?php else: foreach ($notif_items as $n): ?><a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $n->id_notifikasi) ?>"><div class="fw-semibold small"><?= html_escape($n->judul) ?></div><div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div></a><?php endforeach; endif; ?></div></div><a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Pengembalian</a><a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a></div></div></div></header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-4">
            <form id="adminFilters" method="get" action="<?= base_url('index.php/admin/peminjaman') ?>" data-max-filters="4">
                <input type="hidden" name="per_page" value="<?= html_escape($current_per_page) ?>">
                <div class="admin-filter-heading"><h2><i class="bi bi-funnel me-2 text-fik-orange"></i>Filter pencarian</h2></div>
                <div id="adminFilterRows" class="admin-filter-list">
                    <?php foreach($filter_rows as $index => $filter_row): ?>
                    <div class="admin-filter-row">
                        <select name="filter_field[]" class="form-select admin-filter-field" aria-label="Jenis filter <?= $index + 1 ?>"><?php foreach($filter_fields as $field_key => $field_label): ?><option value="<?= $field_key ?>" <?= (($filter_row['field'] ?? '') === $field_key) ? 'selected' : '' ?>><?= $field_label ?></option><?php endforeach; ?></select>
                        <input type="search" name="filter_value[]" class="form-control admin-filter-value" value="<?= html_escape($filter_row['value'] ?? '') ?>" placeholder="Ketik untuk mencari" autocomplete="off" aria-label="Nilai filter <?= $index + 1 ?>">
                        <div class="admin-filter-tools"><button type="button" class="btn btn-outline-secondary admin-filter-icon admin-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg"></i></button><button type="button" class="btn btn-outline-primary admin-filter-icon admin-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg"></i></button></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="admin-filter-actions"><button class="btn btn-fik px-4"><i class="bi bi-search me-1"></i>Terapkan filter</button><a href="<?= base_url('index.php/admin/peminjaman?per_page='.rawurlencode($current_per_page)) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a><a href="<?= $export_url ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Preview Excel</a></div>
            </form>
        </section>

        <section class="panel-card p-0 loan-table-card">
            <div class="loan-list-summary">
                <div><h2>Seluruh Peminjaman</h2><p>Data tetap terlihat sampai proses selesai; aksi hanya aktif pada tahap Laboran.</p></div>
                <div class="d-flex flex-wrap gap-2"><span class="badge rounded-pill text-bg-warning px-3 py-2"><?= $approval_actionable ?> perlu tindakan</span><span class="badge rounded-pill text-bg-light border px-3 py-2"><?= (int) ($pagination['total'] ?? 0) ?> total</span></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover loan-table">
                    <thead><tr><th>No</th><th>Peminjam</th><th>Barang</th><th>Masa Pinjam</th><th>Progress Peminjaman</th><th class="text-end pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada data peminjaman.</td></tr>
                    <?php else: foreach($peminjaman as $index => $p): ?>
                        <?php
                            $loan_evidence_url = scm_upload_url($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman');
                            $loan_evidence_exists = scm_upload_exists($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman');
                            $return_evidence_url = scm_upload_url($p->foto_pengembalian ?? '', 'assets/uploads/bukti_pengembalian');
                            $return_evidence_exists = scm_upload_exists($p->foto_pengembalian ?? '', 'assets/uploads/bukti_pengembalian');
                            $can_laboran_act = scm_loan_can_act($p, 'laboran');
                            $status = (string) ($p->status ?? '');
                        ?>
                        <tr>
                            <td class="fw-semibold text-muted"><?= (((int) ($pagination['page'] ?? 1) - 1) * max(1, (int) ($pagination['per_page'] ?? 10))) + $index + 1 ?></td>
                            <td><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="fw-semibold"><?= (int)($p->total_jenis ?? 1) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="small text-muted"><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><?= html_escape($d->nama_aset) ?> (<?= (int)$d->jumlah_pinjam ?>), <?php endforeach; else: ?>- <?php endif; ?></div></td>
                            <td><span tabindex="0" data-bs-toggle="tooltip" title="<?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>"><div><?= tanggal_indonesia($p->tanggal_pinjam ?? null) ?></div><div class="small text-muted">s.d. <?= tanggal_indonesia($p->tanggal_kembali_rencana ?? null) ?></div></span></td>
                            <td><?php $loan_progress_item = $p; $loan_progress_compact = true; include APPPATH . 'views/shared/loan_progress.php'; ?><button type="button" class="loan-approval-detail-btn mt-2" data-bs-toggle="modal" data-bs-target="#approvalDetail<?= (int) $p->id_peminjaman ?>">Lihat Detail <i class="bi bi-arrow-right" aria-hidden="true"></i></button></td>
                            <td class="text-end pe-3">
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <?php if ($can_laboran_act): ?>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#processModal<?= (int) $p->id_peminjaman ?>"><i class="bi bi-sliders me-1"></i>Proses</button>
                                    <?php elseif ($status === 'Disetujui (Menunggu Finalisasi QR)'): ?>
                                        <a class="btn btn-sm btn-fik rounded-pill px-3" href="<?= base_url('index.php/admin/peminjaman/finalkan_qr/'.$p->id_peminjaman) ?>" onclick="return confirm('Finalkan QR dan kunci data transaksi ini?')"><i class="bi bi-qr-code me-1"></i>Finalkan QR</a>
                                    <?php elseif ($status === 'Disetujui (Menunggu Pengambilan)'): ?>
                                        <a class="btn btn-sm btn-outline-success rounded-pill px-3" href="<?= base_url('index.php/admin/peminjaman/serah_terima/'.rawurlencode($p->group_id)) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Serah Barang</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" disabled title="Aksi belum berada pada tahap Laboran"><i class="bi bi-sliders me-1"></i>Proses</button>
                                    <?php endif; ?>
                                    <?php if(!empty($p->foto_bukti)): ?><button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="modal" data-bs-target="#loanEvidence<?= (int)$p->id_peminjaman ?>" aria-label="Lihat bukti kondisi"><i class="bi bi-image"></i></button><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
                $base_query = [
                    'status' => $filters['status'] ?? '',
                    'q' => $filters['pencarian'] ?? '',
                    'tanggal' => $filters['tanggal'] ?? '',
                    'per_page' => $current_per_page,
                ];
                foreach ($filter_rows as $filter_row) {
                    $base_query['filter_field'][] = $filter_row['field'] ?? 'peminjam';
                    $base_query['filter_value'][] = $filter_row['value'] ?? '';
                }
                $page = (int) ($pagination['page'] ?? 1);
                $total_pages = (int) ($pagination['total_pages'] ?? 1);
            ?>
                <div class="loan-pagination-footer">
                    <div class="loan-pagination-summary">
                        <label for="loanPageSize">Tampilkan:</label>
                        <select id="loanPageSize" class="form-select form-select-sm" aria-label="Jumlah data peminjaman per halaman">
                            <option value="10" <?= $current_per_page === '10' ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $current_per_page === '25' ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $current_per_page === '50' ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $current_per_page === '100' ? 'selected' : '' ?>>100</option>
                        </select>
                        <span>Total item: <?= (int) ($pagination['total'] ?? 0) ?></span>
                    </div>
                    <?php $first_item = ((int) ($pagination['total'] ?? 0)) > 0 ? (($page - 1) * (int) $current_per_page) + 1 : 0; $last_item = min((int) ($pagination['total'] ?? 0), $page * (int) $current_per_page); ?>
                    <div class="loan-pagination-status">Menampilkan <?= $first_item ?>–<?= $last_item ?> dari <?= number_format((int) ($pagination['total'] ?? 0), 0, ',', '.') ?> data</div>
                    <nav aria-label="Pagination peminjaman">
                        <ul class="pagination pagination-sm loan-pagination">
                            <?php $prev_query = http_build_query(array_merge($base_query, ['page' => max(1, $page - 1)])); ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($prev_query ? '?'.$prev_query : '')) ?>">Previous</a></li>
<<<<<<< HEAD
                            <?php foreach ($compact_pagination_pages($page, $total_pages) as $i): ?>
                                <?php if (is_string($i)): ?>
                                    <li class="page-item disabled" aria-hidden="true"><span class="page-link">...</span></li>
                                <?php else: $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                    <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
=======
                            <?php $start_page = max(1, $page - 2); $end_page = min($total_pages, $start_page + 4); $start_page = max(1, $end_page - 4); for($i = $start_page; $i <= $end_page; $i++): $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
>>>>>>> origin/main
                            <?php $next_query = http_build_query(array_merge($base_query, ['page' => min($total_pages, $page + 1)])); ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($next_query ? '?'.$next_query : '')) ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
        </section>
        <?php foreach(($peminjaman ?? []) as $p): ?>
            <?php $can_laboran_act = scm_loan_can_act($p, 'laboran'); $loan_evidence_url = scm_upload_url($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman'); $loan_evidence_exists = scm_upload_exists($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman'); $return_evidence_url = scm_upload_url($p->foto_pengembalian ?? '', 'assets/uploads/bukti_pengembalian'); $return_evidence_exists = scm_upload_exists($p->foto_pengembalian ?? '', 'assets/uploads/bukti_pengembalian'); ?>
            <div class="modal fade loan-approval-modal" id="approvalDetail<?= (int) $p->id_peminjaman ?>" tabindex="-1" aria-labelledby="approvalDetailLabel<?= (int) $p->id_peminjaman ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <div class="small text-uppercase text-muted fw-semibold">Detail proses</div>
                                <h2 class="modal-title h5 fw-bold mb-0" id="approvalDetailLabel<?= (int) $p->id_peminjaman ?>">Progress Peminjaman</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <?php $loan_progress_item = $p; $loan_progress_compact = false; include APPPATH . 'views/shared/loan_progress.php'; ?>
                            <div class="loan-approval-modal__evidence">
                                <div class="small text-muted">Bukti pendukung</div>
                                <div class="loan-approval-modal__evidence-actions">
                                    <?php if(!empty($p->foto_bukti)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary loan-evidence-btn" data-bs-toggle="modal" data-bs-target="#loanEvidence<?= (int)$p->id_peminjaman ?>"><i class="bi bi-image" aria-hidden="true"></i><span>Bukti kondisi</span></button>
                                    <?php else: ?>
                                        <span class="loan-approval-modal__evidence-empty">Bukti kondisi belum tersedia</span>
                                    <?php endif; ?>
                                    <?php if(!empty($p->foto_pengembalian)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary loan-evidence-btn" data-bs-toggle="modal" data-bs-target="#returnEvidence<?= (int)$p->id_peminjaman ?>"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span>Bukti kembali</span></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($can_laboran_act): ?>
            <div class="modal fade" id="processModal<?= (int) $p->id_peminjaman ?>" tabindex="-1" aria-labelledby="processModalLabel<?= (int) $p->id_peminjaman ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>">
                        <div class="modal-header"><h2 class="modal-title h5 fw-bold" id="processModalLabel<?= (int) $p->id_peminjaman ?>">Proses Peminjaman</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body"><div class="mb-3"><div class="small text-muted">Peminjam</div><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?> — <?= html_escape($p->nim_nip ?? '-') ?></div></div><label class="form-label small fw-semibold">Catatan Laboran</label><textarea name="catatan_laboran" class="form-control" rows="3" placeholder="Catatan pengecekan atau alasan penolakan."></textarea><div class="form-text">Alasan wajib diisi saat menolak.</div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button formaction="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-4" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i>Tolak</button><button formaction="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-4" onclick="return confirm('Teruskan pengajuan ini ke Kaur?')"><i class="bi bi-send-check me-1"></i>Setujui</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($p->foto_bukti)): ?><div class="modal fade" id="loanEvidence<?= (int)$p->id_peminjaman ?>" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5 fw-bold">Bukti Kondisi Awal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body text-center bg-light"><?php if($loan_evidence_exists): ?><img class="img-fluid rounded-3" style="max-height:70vh;object-fit:contain" src="<?= html_escape($loan_evidence_url) ?>" alt="Bukti kondisi awal"><?php else: ?><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File bukti kondisi tidak ditemukan di penyimpanan.</div><?php endif; ?></div></div></div></div><?php endif; ?>
            <?php if(!empty($p->foto_pengembalian)): ?><div class="modal fade" id="returnEvidence<?= (int)$p->id_peminjaman ?>" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5 fw-bold">Bukti Pengembalian</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body text-center bg-light"><?php if($return_evidence_exists): ?><img class="img-fluid rounded-3" style="max-height:70vh;object-fit:contain" src="<?= html_escape($return_evidence_url) ?>" alt="Bukti pengembalian"><?php else: ?><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File bukti pengembalian tidak ditemukan di penyimpanan.</div><?php endif; ?></div></div></div></div><?php endif; ?>
        <?php endforeach; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const form = document.getElementById('adminFilters');
            if (!form) return;
            const rows = document.getElementById('adminFilterRows');
            const maxFilters = Number(form.dataset.maxFilters || 4);
            const fields = <?= json_encode($filter_fields, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const suggestions = <?= json_encode($filter_suggestions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            let submitTimer;

            const syncFilterInput = row => {
                const field = row.querySelector('.admin-filter-field').value;
                const input = row.querySelector('.admin-filter-value');
                const listId = `filterSuggestions${Date.now()}${Math.random().toString(16).slice(2)}`;
                row.querySelector('datalist')?.remove();
                input.removeAttribute('list');
                input.type = field === 'tanggal' ? 'date' : 'search';
                input.placeholder = field === 'tanggal' ? 'Pilih tanggal pinjam' : `Cari ${fields[field].toLowerCase()}`;
                if (field === 'tanggal') return;
                const datalist = document.createElement('datalist');
                datalist.id = listId;
                (suggestions[field] || []).slice(0, 80).forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    datalist.appendChild(option);
                });
                row.appendChild(datalist);
                input.setAttribute('list', listId);
            };

            const updateButtons = () => {
                const filterRows = Array.from(rows.querySelectorAll('.admin-filter-row'));
                filterRows.forEach((row, index) => {
                    row.querySelector('.admin-filter-remove').disabled = filterRows.length === 1;
                    row.querySelector('.admin-filter-add').disabled = filterRows.length >= maxFilters || index !== filterRows.length - 1;
                });
            };

            const addRow = (field = 'peminjam', value = '') => {
                if (rows.querySelectorAll('.admin-filter-row').length >= maxFilters) return;
                const row = document.createElement('div');
                row.className = 'admin-filter-row';
                const select = document.createElement('select');
                select.name = 'filter_field[]';
                select.className = 'form-select admin-filter-field';
                Object.entries(fields).forEach(([key, label]) => {
                    const option = new Option(label, key, false, key === field);
                    select.add(option);
                });
                const input = document.createElement('input');
                input.name = 'filter_value[]'; input.className = 'form-control admin-filter-value'; input.value = value; input.autocomplete = 'off';
                const tools = document.createElement('div');
                tools.className = 'admin-filter-tools';
                tools.innerHTML = '<button type="button" class="btn btn-outline-secondary admin-filter-icon admin-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg"></i></button><button type="button" class="btn btn-outline-primary admin-filter-icon admin-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg"></i></button>';
                row.append(select, input, tools); rows.appendChild(row); syncFilterInput(row); updateButtons(); return row;
            };

            rows.querySelectorAll('.admin-filter-row').forEach(syncFilterInput);
            updateButtons();
            rows.addEventListener('click', event => {
                const row = event.target.closest('.admin-filter-row');
                if (!row) return;
                if (event.target.closest('.admin-filter-add')) { const added = addRow(); added?.querySelector('.admin-filter-value').focus(); }
                if (event.target.closest('.admin-filter-remove') && rows.querySelectorAll('.admin-filter-row').length > 1) { row.remove(); updateButtons(); form.requestSubmit(); }
            });
            rows.addEventListener('change', event => {
                if (!event.target.matches('.admin-filter-field')) return;
                const row = event.target.closest('.admin-filter-row');
                row.querySelector('.admin-filter-value').value = '';
                syncFilterInput(row);
                row.querySelector('.admin-filter-value').focus();
            });
            rows.addEventListener('input', event => {
                if (!event.target.matches('.admin-filter-value')) return;
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(() => form.requestSubmit(), 600);
            });
        })();
        const loanPageSize = document.getElementById('loanPageSize');
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        if (loanPageSize) {
            loanPageSize.addEventListener('change', () => {
                const targetUrl = new URL(window.location.href);
                targetUrl.searchParams.set('page', '1');
                targetUrl.searchParams.set('per_page', loanPageSize.value);
                window.location.assign(targetUrl.toString());
            });
        }
        window.setInterval(() => {
            const activeElement = document.activeElement;
            const isEditing = activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(activeElement.tagName);
            if (!document.hidden && !document.querySelector('.modal.show') && !isEditing) window.location.reload();
        }, 60000);
    </script>
</body>
</html>
