<?php
$pagination = isset($pagination) && is_array($pagination)
    ? $pagination
    : ['page' => 1, 'per_page' => '10', 'total' => count($maintenance ?? []), 'total_pages' => 1];
$page = (int) ($pagination['page'] ?? 1);
$total_pages = (int) ($pagination['total_pages'] ?? 1);
$current_per_page = (string) ($pagination['per_page'] ?? '10');
$compact_pagination_pages = static function ($current, $last) {
    $current = max(1, min((int) $current, max(1, (int) $last)));
    $last = max(1, (int) $last);
    if ($last <= 7) return range(1, $last);
    if ($current <= 3) return array_merge(range(1, 5), ['ellipsis-after', $last]);
    if ($current >= $last - 2) return array_merge([1, 'ellipsis-before'], range($last - 4, $last));
    return array_merge([1, 'ellipsis-before'], range($current - 2, $current + 2), ['ellipsis-after', $last]);
};
$maintenance_condition_class = static function ($condition) {
    $normalized = strtolower(trim((string) $condition));
    $normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized);
    return trim('maintenance-condition--' . $normalized, '-');
};
$maintenance_asset_search_url = base_url('index.php/admin/maintenance/cari_aset');
$system_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Maintenance Barang' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
    <style>
        body { background: #f5f6f8; font-family: 'Poppins', sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; border-bottom: 4px solid #ea5b1a; color: #fff; }
        .panel-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(32, 33, 36, .05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234, 91, 26, .16); }
        .maintenance-table-card {
            --maintenance-bg: var(--scm-theme-surface, #ffffff);
            --maintenance-soft: var(--scm-theme-surface-soft, #f9fafb);
            --maintenance-border: var(--scm-theme-border, #e5e7eb);
            --maintenance-text: var(--scm-theme-text, #1f2937);
            --maintenance-muted: var(--scm-theme-muted, #6b7280);
            overflow: hidden;
            border-color: var(--maintenance-border);
            background: var(--maintenance-bg);
        }
        .maintenance-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .maintenance-table {
            min-width: 920px;
            margin: 0;
            --bs-table-bg: var(--maintenance-bg);
            --bs-table-color: var(--maintenance-text);
            --bs-table-border-color: var(--maintenance-border);
        }
        .maintenance-table.table-hover > tbody > tr:hover > * { --bs-table-bg-state: rgba(234, 91, 26, .045); }
        .maintenance-table thead th {
            padding: 14px 16px;
            color: #111827 !important;
            background: var(--maintenance-soft);
            border-color: var(--maintenance-border);
            font-size: .72rem;
            font-family: inherit;
            font-weight: 700 !important;
            letter-spacing: .035em;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .maintenance-table thead th, .maintenance-table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .maintenance-table tbody td {
            padding: 15px 16px;
            color: var(--maintenance-text);
            background: var(--maintenance-bg);
            border-color: var(--maintenance-border);
            line-height: 1.5;
            vertical-align: middle;
        }
        .maintenance-table th.maintenance-index-column, .maintenance-table td.maintenance-index-column { min-width: 58px; width: 58px; padding-right: 10px; padding-left: 10px; text-align: center; white-space: nowrap; }
        .maintenance-index { color: var(--maintenance-muted); font-size: .8rem; font-weight: 500; }
        .maintenance-table th:nth-child(2), .maintenance-table td:nth-child(2) { min-width: 235px; }
        .maintenance-table th:nth-child(3), .maintenance-table td:nth-child(3) { min-width: 125px; white-space: nowrap; }
        .maintenance-table th:nth-child(4), .maintenance-table td:nth-child(4) { min-width: 170px; }
        .maintenance-table th:nth-child(5), .maintenance-table td:nth-child(5) { min-width: 320px; }
        .maintenance-table th:nth-child(6), .maintenance-table td:nth-child(6) { min-width: 90px; }
        .maintenance-condition {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--maintenance-muted);
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .maintenance-condition__dot {
            width: 8px;
            height: 8px;
            flex: 0 0 8px;
            border-radius: 50%;
            background: #9ca3af;
            box-shadow: 0 0 0 3px rgba(156, 163, 175, .12);
        }
        .maintenance-condition--baik { color: #24734d; }
        .maintenance-condition--baik .maintenance-condition__dot { background: #39a36d; box-shadow: 0 0 0 3px rgba(57, 163, 109, .13); }
        .maintenance-condition--rusak { color: #a83d4c; }
        .maintenance-condition--rusak .maintenance-condition__dot { background: #dc6b78; box-shadow: 0 0 0 3px rgba(220, 107, 120, .13); }
        .maintenance-condition--perlu-perbaikan { color: #9a6813; }
        .maintenance-condition--perlu-perbaikan .maintenance-condition__dot { background: #e3a331; box-shadow: 0 0 0 3px rgba(227, 163, 49, .13); }
        .maintenance-condition--sudah-diperbaiki { color: #236c7b; }
        .maintenance-condition--sudah-diperbaiki .maintenance-condition__dot { background: #42a9b8; box-shadow: 0 0 0 3px rgba(66, 169, 184, .13); }
        .maintenance-description {
            display: -webkit-box;
            max-width: 520px;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .maintenance-more-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            color: var(--maintenance-muted);
            border: 1px solid var(--maintenance-border);
            border-radius: 8px;
            background: var(--maintenance-bg);
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .maintenance-more-btn:hover, .maintenance-more-btn:focus { color: #ea5b1a; border-color: rgba(234, 91, 26, .45); background: rgba(234, 91, 26, .05); }
        .maintenance-page-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .maintenance-page-heading h1 { margin: 0; color: #1f2937; font-size: clamp(1.4rem, 2vw, 1.85rem); font-weight: 700; letter-spacing: -.02em; }
        .maintenance-page-heading p { margin: .3rem 0 0; color: #6b7280; font-size: .84rem; }
        .maintenance-toolbar { display: grid; grid-template-columns: minmax(220px, 1.6fr) minmax(180px, .9fr) auto; align-items: end; gap: .75rem; margin-bottom: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; box-shadow: 0 5px 18px rgba(32, 33, 36, .035); }
        .maintenance-toolbar .form-label { margin-bottom: .35rem; color: var(--maintenance-muted); font-size: .72rem; font-weight: 600; }
        .maintenance-toolbar .form-control, .maintenance-toolbar .form-select { min-height: 38px; font-size: .8rem; }
        .maintenance-toolbar-reset { min-height: 38px; }
        .maintenance-drawer { width: min(520px, 100vw) !important; border-left: 1px solid #e5e7eb; }
        .maintenance-drawer .offcanvas-header { padding: 1.25rem 1.35rem; border-bottom: 1px solid #e5e7eb; }
        .maintenance-drawer .offcanvas-title { color: #1f2937; font-size: 1rem; font-weight: 700; }
        .maintenance-drawer .offcanvas-body { padding: 1.35rem; background: #fff; }
        .maintenance-drawer .form-label { margin-bottom: .4rem; color: #6b7280; font-size: .75rem; font-weight: 600; }
        .maintenance-drawer .form-control, .maintenance-drawer .form-select { min-height: 40px; font-size: .8rem; }
        .maintenance-drawer textarea.form-control { min-height: auto; }
        .maintenance-drawer .btn-fik { min-height: 40px; }
        .maintenance-asset-combobox { position: relative; }
        .maintenance-asset-combobox__input-wrap { position: relative; }
        .maintenance-asset-combobox__input { min-height: 46px !important; padding-right: 2.65rem; }
        .maintenance-asset-combobox__icon { position: absolute; top: 50%; right: .95rem; color: #8a94a3; pointer-events: none; transform: translateY(-50%); }
        .maintenance-asset-combobox__list { position: absolute; z-index: 1085; top: calc(100% + .4rem); right: 0; left: 0; max-height: min(320px, 42vh); overflow-y: auto; padding: .35rem; border: 1px solid #dbe1e8; border-radius: 10px; background: #fff; box-shadow: 0 14px 34px rgba(15, 23, 42, .16); }
        .maintenance-asset-combobox__list[hidden] { display: none; }
        .maintenance-asset-combobox__option { display: grid; width: 100%; grid-template-columns: minmax(0, 1fr) auto; gap: .3rem .75rem; padding: .72rem .75rem; border: 0; border-radius: 7px; color: #273142; background: transparent; text-align: left; }
        .maintenance-asset-combobox__option:hover, .maintenance-asset-combobox__option.is-active { color: #b84410; background: #fff1e9; }
        .maintenance-asset-combobox__name { overflow: hidden; font-size: .8rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .maintenance-asset-combobox__condition { color: #667085; font-size: .68rem; white-space: nowrap; }
        .maintenance-asset-combobox__meta { grid-column: 1 / -1; color: #8a94a3; font-size: .68rem; }
        .maintenance-asset-combobox__empty { padding: 1rem .75rem; color: #6b7280; font-size: .75rem; text-align: center; }
        .maintenance-form-hint { margin-top: .35rem; color: #8a94a3; font-size: .7rem; line-height: 1.45; }
        .maintenance-date-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .maintenance-system-date { color: #667085; background: #f4f6f8 !important; cursor: not-allowed; }
        .maintenance-pagination-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid var(--maintenance-border);
            color: var(--maintenance-muted);
            background: var(--maintenance-soft);
        }
        .maintenance-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; }
        .maintenance-pagination-summary, .maintenance-pagination-status { font-size: .72rem; white-space: nowrap; }
        .maintenance-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .maintenance-pagination-status { text-align: center; }
        .maintenance-pagination { margin: 0; }
        .maintenance-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            min-height: 34px;
            padding: .35rem .58rem;
            border-color: var(--maintenance-border);
            color: var(--maintenance-text);
            background: var(--maintenance-bg);
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .maintenance-pagination .page-link:hover { color: #ea5b1a; background: var(--maintenance-soft); }
        .maintenance-pagination .page-item.active .page-link { color: #fff; background: #ea5b1a; border-color: #ea5b1a; }
        .maintenance-pagination .page-item.disabled .page-link { color: var(--maintenance-muted); background: var(--maintenance-soft); opacity: .62; }
        @media (max-width: 991.98px) {
            .maintenance-layout > [class*="col-"] { width: 100%; }
        }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .maintenance-page-heading { align-items: stretch; flex-direction: column; }
            .maintenance-page-heading .btn { align-self: flex-start; }
            .maintenance-toolbar { grid-template-columns: 1fr; align-items: stretch; }
            .maintenance-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .maintenance-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
            .maintenance-date-grid { grid-template-columns: 1fr; }
            .maintenance-asset-combobox__list { position: fixed; top: auto; right: 12px; bottom: 12px; left: 12px; max-height: min(420px, 62vh); }
        }
        @media (max-width: 575.98px) {
            .maintenance-drawer .offcanvas-body { padding: 1rem; }
            .maintenance-asset-combobox__option { grid-template-columns: 1fr; }
            .maintenance-asset-combobox__condition, .maintenance-asset-combobox__meta { grid-column: 1; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>

    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-tools me-2 text-warning"></i>Maintenance Barang</div>
                </div>
                <div class="topbar-actions d-flex gap-2">
                    <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <div class="maintenance-page-heading">
            <div>
                <h1>Maintenance Barang</h1>
            </div>
            <button type="button" class="btn btn-fik rounded-pill px-3" data-bs-toggle="offcanvas" data-bs-target="#maintenanceFormDrawer" aria-controls="maintenanceFormDrawer">
                <i class="bi bi-plus-lg me-1"></i> Tambah Maintenance
            </button>
        </div>

        <?php
        $multi_filter_id = 'maintenanceMultiFilter';
        $multi_filter_mode = 'server';
        $multi_filter_action = base_url('index.php/admin/maintenance');
        $multi_filter_rows = $filter_criteria ?? [];
        $multi_filter_hidden = ['per_page' => $current_per_page, 'page' => 1];
        $multi_filter_fields = [
            'aset' => ['label' => 'Aset / kode', 'placeholder' => 'Cari nama aset atau kode'],
            'ruangan' => ['label' => 'Lokasi / Lab', 'placeholder' => 'Cari lokasi aset'],
            'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal maintenance', 'type' => 'date'],
            'kondisi' => ['label' => 'Kondisi', 'placeholder' => 'Cari kondisi setelah maintenance'],
            'deskripsi' => ['label' => 'Deskripsi / catatan', 'placeholder' => 'Cari deskripsi atau catatan'],
        ];
        include APPPATH . 'views/admin/_multi_filter.php';
        ?>

        <div class="panel-card p-0 maintenance-table-card">
            <div class="table-responsive">
                <table class="table table-hover maintenance-table">
                    <thead>
                        <tr>
                            <th class="maintenance-index-column">No</th>
                            <th class="ps-3">Aset</th>
                            <th>Tanggal</th>
                            <th>Kondisi</th>
                            <th>Deskripsi</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="maintenanceTableBody">
                        <?php if (empty($maintenance)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada catatan maintenance.</td></tr>
                        <?php else: foreach ($maintenance as $maintenance_index => $m): ?>
                            <?php
                            $condition = (string) ($m->kondisi_setelah ?? '');
                            $asset_search = strtolower(implode(' ', [
                                (string) ($m->nama_aset ?? ''),
                                (string) ($m->kode_aset ?? ''),
                                (string) ($m->nama_ruangan ?? ''),
                                (string) ($m->deskripsi ?? ''),
                                (string) ($m->catatan ?? ''),
                            ]));
                            ?>
                            <tr class="maintenance-data-row" data-search="<?= html_escape($asset_search) ?>" data-condition="<?= html_escape($condition) ?>">
                                <td class="maintenance-index-column"><span class="maintenance-index"><?= (($page - 1) * (is_numeric($current_per_page) ? (int) $current_per_page : count($maintenance)) + $maintenance_index + 1) ?></span></td>
                                <td class="ps-3">
                                    <div class="fw-semibold"><?= html_escape($m->nama_aset) ?></div>
                                    <div class="small text-muted"><?= html_escape($m->kode_aset . ' - ' . $m->nama_ruangan) ?></div>
                                </td>
                                <td><?= html_escape(tanggal_indonesia($m->tanggal_maintenance)) ?></td>
                                <td>
                                    <span class="maintenance-condition <?= html_escape($maintenance_condition_class($condition)) ?>">
                                        <span class="maintenance-condition__dot" aria-hidden="true"></span>
                                        <?= html_escape($condition) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small maintenance-description" title="<?= html_escape($m->deskripsi) ?>"><?= html_escape($m->deskripsi) ?></div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <button type="button" class="btn maintenance-more-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi maintenance">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item text-danger" href="<?= base_url('index.php/admin/maintenance/hapus/' . $m->id_maintenance) ?>" onclick="return confirm('Hapus catatan maintenance ini?')">
                                                    <i class="bi bi-trash me-2"></i>Hapus
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <tr id="maintenanceFilteredEmpty" hidden>
                                <td colspan="6" class="text-center text-muted py-5">Tidak ada catatan yang sesuai.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="maintenance-pagination-footer">
                <div class="maintenance-pagination-summary">
                    <label for="maintenancePageSize">Tampilkan:</label>
                    <select id="maintenancePageSize" class="form-select form-select-sm" aria-label="Jumlah data maintenance per halaman">
                        <option value="10" <?= $current_per_page === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $current_per_page === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $current_per_page === '50' ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $current_per_page === '100' ? 'selected' : '' ?>>100</option>
                    </select>
                    <span>Total item: <?= (int) ($pagination['total'] ?? 0) ?></span>
                </div>
                <div class="maintenance-pagination-status">Halaman: <?= $page ?> dari <?= $total_pages ?></div>
                <nav aria-label="Pagination maintenance">
                    <ul class="pagination pagination-sm maintenance-pagination">
                        <?php $maintenance_filter_query = ['filter_field' => array_column($filter_criteria ?? [], 'field'), 'filter_value' => array_column($filter_criteria ?? [], 'value')]; $prev_query = http_build_query(array_merge($maintenance_filter_query, ['page' => max(1, $page - 1), 'per_page' => $current_per_page])); ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $prev_query) ?>">Previous</a></li>
                        <?php foreach ($compact_pagination_pages($page, $total_pages) as $i): ?>
                            <?php if (is_string($i)): ?>
                                <li class="page-item disabled" aria-hidden="true"><span class="page-link">...</span></li>
                            <?php else: $page_query = http_build_query(array_merge($maintenance_filter_query, ['page' => $i, 'per_page' => $current_per_page])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $page_query) ?>"><?= $i ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php $next_query = http_build_query(array_merge($maintenance_filter_query, ['page' => min($total_pages, $page + 1), 'per_page' => $current_per_page])); ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $next_query) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </main>

    <div class="offcanvas offcanvas-end maintenance-drawer" tabindex="-1" id="maintenanceFormDrawer" aria-labelledby="maintenanceFormDrawerLabel">
        <div class="offcanvas-header">
            <div>
                <h2 class="offcanvas-title" id="maintenanceFormDrawerLabel">Tambah Maintenance</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <form id="maintenanceForm" action="<?= base_url('index.php/admin/maintenance/simpan') ?>" method="post" class="vstack gap-3">
                <div>
                    <label class="form-label" for="maintenanceAssetSearch">Cari Aset</label>
                    <div id="maintenanceAssetCombobox" class="maintenance-asset-combobox">
                        <div class="maintenance-asset-combobox__input-wrap">
                            <input id="maintenanceAssetSearch" type="text" class="form-control maintenance-asset-combobox__input" placeholder="Ketik nama, kode, atau lokasi aset" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="maintenanceAssetList" required>
                            <i class="bi bi-search maintenance-asset-combobox__icon" aria-hidden="true"></i>
                        </div>
                        <input id="maintenanceAsset" type="hidden" name="id_aset" value="">
                        <div id="maintenanceAssetList" class="maintenance-asset-combobox__list" role="listbox" aria-label="Hasil pencarian aset" hidden>
                            <div class="maintenance-asset-combobox__empty">Ketik untuk mencari aset.</div>
                        </div>
                    </div>
                    <div class="maintenance-form-hint">Pilih aset dari hasil pencarian agar data yang disimpan tetap tepat.</div>
                </div>
                <div class="maintenance-date-grid">
                    <div>
                        <label class="form-label" for="maintenanceInputDate">Tanggal Input (Sistem)</label>
                        <input id="maintenanceInputDate" type="date" class="form-control maintenance-system-date" value="<?= html_escape($system_date) ?>" readonly aria-readonly="true">
                        <div class="maintenance-form-hint">Diisi otomatis sesuai tanggal sistem saat data disimpan.</div>
                    </div>
                    <div>
                        <label class="form-label" for="maintenanceDate">Tanggal Maintenance</label>
                        <input id="maintenanceDate" type="date" name="tanggal_maintenance" class="form-control" value="<?= html_escape($system_date) ?>" required>
                        <div class="maintenance-form-hint">Pilih tanggal pelaksanaan maintenance.</div>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="maintenanceCondition">Kondisi Setelah</label>
                    <select id="maintenanceCondition" name="kondisi_setelah" class="form-select">
                        <option>Baik</option>
                        <option>Rusak</option>
                        <option>Perlu Perbaikan</option>
                        <option>Sudah Diperbaiki</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="maintenanceDescription">Deskripsi</label>
                    <textarea id="maintenanceDescription" name="deskripsi" class="form-control" rows="4" required></textarea>
                </div>
                <div>
                    <label class="form-label" for="maintenanceNotes">Catatan</label>
                    <textarea id="maintenanceNotes" name="catatan" class="form-control" rows="3"></textarea>
                </div>
                <button class="btn btn-fik rounded-pill mt-2"><i class="bi bi-save me-1"></i> Simpan Maintenance</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const maintenanceForm = document.getElementById('maintenanceForm');
        const maintenanceAssetCombobox = document.getElementById('maintenanceAssetCombobox');
        const maintenanceAssetSearch = document.getElementById('maintenanceAssetSearch');
        const maintenanceAssetInput = document.getElementById('maintenanceAsset');
        const maintenanceAssetList = document.getElementById('maintenanceAssetList');
        const maintenanceAssetSearchUrl = <?= json_encode($maintenance_asset_search_url) ?>;
        let maintenanceAssetOptions = [];
        let maintenanceSelectedAsset = null;
        let maintenanceActiveAssetIndex = -1;
        let maintenanceSearchTimer = null;
        let maintenanceRequestNumber = 0;

        const closeMaintenanceAssetList = function () {
            if (!maintenanceAssetList || !maintenanceAssetSearch) return;
            maintenanceAssetList.hidden = true;
            maintenanceAssetSearch.setAttribute('aria-expanded', 'false');
            maintenanceAssetSearch.removeAttribute('aria-activedescendant');
            maintenanceActiveAssetIndex = -1;
        };

        const setActiveMaintenanceAsset = function (index) {
            if (!maintenanceAssetOptions.length || !maintenanceAssetSearch) return;
            maintenanceActiveAssetIndex = (index + maintenanceAssetOptions.length) % maintenanceAssetOptions.length;
            maintenanceAssetOptions.forEach(function (option, optionIndex) {
                const active = optionIndex === maintenanceActiveAssetIndex;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            const activeOption = maintenanceAssetOptions[maintenanceActiveAssetIndex];
            maintenanceAssetSearch.setAttribute('aria-activedescendant', activeOption.id);
            activeOption.scrollIntoView({block: 'nearest'});
        };

        const chooseMaintenanceAsset = function (option) {
            if (!option || !maintenanceAssetSearch || !maintenanceAssetInput) return;
            maintenanceSelectedAsset = option;
            maintenanceAssetInput.value = option.dataset.id || '';
            maintenanceAssetSearch.value = (option.dataset.name || '') + ' — ' + (option.dataset.code || '');
            maintenanceAssetSearch.setCustomValidity('');
            closeMaintenanceAssetList();
        };

        const createMaintenanceAssetOption = function (asset, index) {
            const option = document.createElement('button');
            option.type = 'button';
            option.id = 'maintenanceAssetOption' + index;
            option.className = 'maintenance-asset-combobox__option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.id = String(asset.id || '');
            option.dataset.name = String(asset.name || '');
            option.dataset.code = String(asset.code || '');

            const name = document.createElement('span');
            name.className = 'maintenance-asset-combobox__name';
            name.textContent = option.dataset.name;
            const condition = document.createElement('span');
            condition.className = 'maintenance-asset-combobox__condition';
            condition.textContent = String(asset.condition || '-');
            const meta = document.createElement('span');
            meta.className = 'maintenance-asset-combobox__meta';
            meta.textContent = option.dataset.code + ' · ' + String(asset.room || 'Belum ditempatkan') + ' · ' + String(asset.total || 0) + ' unit';
            option.append(name, condition, meta);
            return option;
        };

        const loadMaintenanceAssets = function () {
            if (!maintenanceAssetSearch || !maintenanceAssetList) return;
            const requestNumber = ++maintenanceRequestNumber;
            const url = maintenanceAssetSearchUrl + (maintenanceAssetSearchUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(maintenanceAssetSearch.value.trim());
            maintenanceAssetList.innerHTML = '<div class="maintenance-asset-combobox__empty">Mencari aset...</div>';
            maintenanceAssetList.hidden = false;
            maintenanceAssetSearch.setAttribute('aria-expanded', 'true');

            fetch(url, {headers: {'Accept': 'application/json'}})
                .then(function (response) {
                    if (!response.ok) throw new Error('Pencarian aset gagal.');
                    return response.json();
                })
                .then(function (payload) {
                    if (requestNumber !== maintenanceRequestNumber) return;
                    const results = payload.success && Array.isArray(payload.results) ? payload.results : [];
                    maintenanceAssetOptions = results.map(createMaintenanceAssetOption);
                    maintenanceActiveAssetIndex = -1;
                    maintenanceAssetList.replaceChildren();
                    if (maintenanceAssetOptions.length) {
                        maintenanceAssetOptions.forEach(function (option) { maintenanceAssetList.appendChild(option); });
                    } else {
                        const empty = document.createElement('div');
                        empty.className = 'maintenance-asset-combobox__empty';
                        empty.textContent = 'Aset tidak ditemukan.';
                        maintenanceAssetList.appendChild(empty);
                    }
                })
                .catch(function () {
                    if (requestNumber !== maintenanceRequestNumber) return;
                    maintenanceAssetOptions = [];
                    maintenanceAssetList.innerHTML = '<div class="maintenance-asset-combobox__empty text-danger">Pencarian aset gagal. Coba lagi.</div>';
                });
        };

        const scheduleMaintenanceAssetSearch = function (immediate) {
            window.clearTimeout(maintenanceSearchTimer);
            maintenanceSearchTimer = window.setTimeout(loadMaintenanceAssets, immediate ? 0 : 180);
        };

        if (maintenanceAssetSearch) {
            maintenanceAssetSearch.addEventListener('focus', function () { scheduleMaintenanceAssetSearch(true); });
            maintenanceAssetSearch.addEventListener('input', function () {
                const selectedLabel = maintenanceSelectedAsset
                    ? (maintenanceSelectedAsset.dataset.name || '') + ' — ' + (maintenanceSelectedAsset.dataset.code || '')
                    : '';
                if (!maintenanceSelectedAsset || maintenanceAssetSearch.value !== selectedLabel) {
                    maintenanceSelectedAsset = null;
                    maintenanceAssetInput.value = '';
                }
                maintenanceAssetSearch.setCustomValidity('');
                scheduleMaintenanceAssetSearch(false);
            });
            maintenanceAssetSearch.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActiveMaintenanceAsset(maintenanceActiveAssetIndex + 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActiveMaintenanceAsset(maintenanceActiveAssetIndex - 1);
                } else if (event.key === 'Enter' && maintenanceActiveAssetIndex >= 0) {
                    event.preventDefault();
                    chooseMaintenanceAsset(maintenanceAssetOptions[maintenanceActiveAssetIndex]);
                } else if (event.key === 'Escape') {
                    closeMaintenanceAssetList();
                }
            });
        }

        if (maintenanceAssetList) {
            maintenanceAssetList.addEventListener('click', function (event) {
                const option = event.target.closest('.maintenance-asset-combobox__option');
                if (option) chooseMaintenanceAsset(option);
            });
        }

        document.addEventListener('mousedown', function (event) {
            if (maintenanceAssetCombobox && !maintenanceAssetCombobox.contains(event.target)) closeMaintenanceAssetList();
        });

        if (maintenanceForm) {
            maintenanceForm.addEventListener('submit', function (event) {
                if (maintenanceAssetInput.value && maintenanceSelectedAsset) return;
                event.preventDefault();
                maintenanceAssetSearch.setCustomValidity('Pilih aset dari hasil pencarian terlebih dahulu.');
                maintenanceAssetSearch.reportValidity();
                maintenanceAssetSearch.setCustomValidity('');
            });
        }

        const maintenancePageSize = document.getElementById('maintenancePageSize');
        if (maintenancePageSize) {
            maintenancePageSize.addEventListener('change', () => {
                const targetUrl = new URL(window.location.href);
                targetUrl.searchParams.set('page', '1');
                targetUrl.searchParams.set('per_page', maintenancePageSize.value);
                window.location.assign(targetUrl.toString());
            });
        }

    </script>
</body>
</html>
