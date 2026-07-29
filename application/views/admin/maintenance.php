<?php
$pagination = isset($pagination) && is_array($pagination)
    ? $pagination
    : ['page' => 1, 'per_page' => '10', 'total' => count($maintenance ?? []), 'total_pages' => 1];
$page = (int) ($pagination['page'] ?? 1);
$total_pages = (int) ($pagination['total_pages'] ?? 1);
$current_per_page = (string) ($pagination['per_page'] ?? '10');
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
            color: var(--maintenance-muted);
            background: var(--maintenance-soft);
            border-color: var(--maintenance-border);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .035em;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .maintenance-table tbody td {
            padding: 15px 16px;
            color: var(--maintenance-text);
            background: var(--maintenance-bg);
            border-color: var(--maintenance-border);
            line-height: 1.5;
            vertical-align: middle;
        }
        .maintenance-table th:nth-child(1), .maintenance-table td:nth-child(1) { min-width: 235px; }
        .maintenance-table th:nth-child(2), .maintenance-table td:nth-child(2) { min-width: 125px; white-space: nowrap; }
        .maintenance-table th:nth-child(3), .maintenance-table td:nth-child(3) { min-width: 170px; text-align: center; }
        .maintenance-table th:nth-child(4), .maintenance-table td:nth-child(4) { min-width: 300px; }
        .maintenance-table th:nth-child(5), .maintenance-table td:nth-child(5) { min-width: 90px; }
        .maintenance-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 142px;
            min-height: 34px;
            padding: 7px 12px;
            border: 1px solid var(--maintenance-border);
            border-radius: 8px;
            color: var(--maintenance-text);
            background: var(--maintenance-bg);
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
        }
        .maintenance-delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            transition: transform .16s ease, color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .maintenance-delete-btn:hover { transform: translateY(-1px); }
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
                    <div class="small text-white-50">Catat perawatan dan kondisi aset</div>
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

        <div class="row g-4 maintenance-layout">
            <div class="col-lg-4">
                <div class="panel-card p-3 p-lg-4">
                    <h5 class="fw-bold mb-3">Tambah Maintenance</h5>
                    <form action="<?= base_url('index.php/admin/maintenance/simpan') ?>" method="post" class="vstack gap-3">
                        <div>
                            <label class="form-label small fw-semibold text-muted">Aset</label>
                            <select name="id_aset" class="form-select" required>
                                <option value="">Pilih aset</option>
                                <?php foreach ($aset as $a): ?>
                                    <option value="<?= $a->id_aset ?>"><?= html_escape($a->nama_aset . ' - ' . $a->kode_aset) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Tanggal Maintenance</label>
                            <input type="date" name="tanggal_maintenance" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Kondisi Setelah</label>
                            <select name="kondisi_setelah" class="form-select">
                                <option>Baik</option>
                                <option>Rusak</option>
                                <option>Perlu Perbaikan</option>
                                <option>Sudah Diperbaiki</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3" required></textarea>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                        <button class="btn btn-fik rounded-pill"><i class="bi bi-save me-1"></i> Simpan Maintenance</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="panel-card p-0 maintenance-table-card">
                    <div class="table-responsive">
                        <table class="table table-hover maintenance-table">
                            <thead>
                                <tr>
                                    <th class="ps-3">Aset</th>
                                    <th>Tanggal</th>
                                    <th>Kondisi</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($maintenance)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-5">Belum ada catatan maintenance.</td></tr>
                                <?php else: foreach ($maintenance as $m): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold"><?= html_escape($m->nama_aset) ?></div>
                                            <div class="small text-muted"><?= html_escape($m->kode_aset . ' - ' . $m->nama_ruangan) ?></div>
                                        </td>
                                        <td><?= html_escape($m->tanggal_maintenance) ?></td>
                                        <td><span class="maintenance-badge"><?= html_escape($m->kondisi_setelah) ?></span></td>
                                        <td><div class="small"><?= html_escape($m->deskripsi) ?></div></td>
                                        <td class="text-end pe-3">
                                            <a class="btn btn-sm btn-outline-danger rounded-circle maintenance-delete-btn" href="<?= base_url('index.php/admin/maintenance/hapus/' . $m->id_maintenance) ?>" onclick="return confirm('Hapus catatan maintenance ini?')" aria-label="Hapus maintenance" title="Hapus"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
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
                                <?php $prev_query = http_build_query(['page' => max(1, $page - 1), 'per_page' => $current_per_page]); ?>
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $prev_query) ?>">Previous</a></li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(['page' => $i, 'per_page' => $current_per_page]); ?>
                                    <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $page_query) ?>"><?= $i ?></a></li>
                                <?php endfor; ?>
                                <?php $next_query = http_build_query(['page' => min($total_pages, $page + 1), 'per_page' => $current_per_page]); ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/maintenance?' . $next_query) ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
