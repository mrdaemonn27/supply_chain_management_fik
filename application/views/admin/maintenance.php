<?php
$pagination = isset($pagination) && is_array($pagination)
    ? $pagination
    : ['page' => 1, 'per_page' => '10', 'total' => count($maintenance ?? []), 'total_pages' => 1];
$page = (int) ($pagination['page'] ?? 1);
$total_pages = (int) ($pagination['total_pages'] ?? 1);
$current_per_page = (string) ($pagination['per_page'] ?? '10');
$maintenance_condition_class = static function ($condition) {
    $normalized = strtolower(trim((string) $condition));
    $normalized = preg_replace('/[^a-z0-9]+/i', '-', $normalized);
    return trim('maintenance-condition--' . $normalized, '-');
};
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
                                <td><?= html_escape($m->tanggal_maintenance) ?></td>
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
                        <option value="all" <?= $current_per_page === 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                    <span>Total item: <?= (int) ($pagination['total'] ?? 0) ?></span>
                </div>
                <div class="maintenance-pagination-status">Halaman: <?= $page ?> dari <?= $total_pages ?></div>
                <nav aria-label="Pagination maintenance">
                    <ul class="pagination pagination-sm maintenance-pagination">
                        <?php $maintenance_filter_query = ['filter_field' => array_column($filter_criteria ?? [], 'field'), 'filter_value' => array_column($filter_criteria ?? [], 'value')]; $prev_query = http_build_query(array_merge($maintenance_filter_query, ['page' => max(1, $page - 1), 'per_page' => $current_per_page])); ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $prev_query) ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(array_merge($maintenance_filter_query, ['page' => $i, 'per_page' => $current_per_page])); ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $page_query) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
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
            <form action="<?= base_url('index.php/admin/maintenance/simpan') ?>" method="post" class="vstack gap-3">
                <div>
                    <label class="form-label" for="maintenanceAsset">Aset</label>
                    <select id="maintenanceAsset" name="id_aset" class="form-select" required>
                        <option value="">Pilih aset</option>
                        <?php foreach ($aset as $a): ?>
                            <option value="<?= $a->id_aset ?>"><?= html_escape($a->nama_aset . ' - ' . $a->kode_aset) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="maintenanceDate">Tanggal Maintenance</label>
                    <input id="maintenanceDate" type="date" name="tanggal_maintenance" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
