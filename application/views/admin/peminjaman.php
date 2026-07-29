<?php
$status_options = isset($status_options) && is_array($status_options) ? $status_options : ['', 'Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur', 'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)', 'Ditolak'];
$status_class = function ($status) { return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Menunggu Verifikasi Laboran')); };
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($peminjaman ?? []), 'per_page' => 10];
$current_per_page = (string) ($pagination['per_page'] ?? '10');
$export_query = http_build_query([
    'status' => $filters['status'] ?? '',
    'q' => $filters['pencarian'] ?? '',
    'tanggal' => $filters['tanggal'] ?? '',
]);
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
            color: var(--loan-muted);
            background: var(--loan-soft);
            border-color: var(--loan-border);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .loan-table tbody tr { min-height: 82px; }
        .loan-table tbody td {
            padding: 16px 18px;
            color: var(--loan-text);
            background: var(--loan-bg);
            border-color: var(--loan-border);
            line-height: 1.5;
            vertical-align: middle;
        }
        .loan-table th:nth-child(1), .loan-table td:nth-child(1) { min-width: 220px; }
        .loan-table th:nth-child(2), .loan-table td:nth-child(2) { min-width: 430px; }
        .loan-table th:nth-child(3), .loan-table td:nth-child(3) { min-width: 175px; }
        .loan-table th:nth-child(4), .loan-table td:nth-child(4) { min-width: 270px; text-align: center; }
        .loan-table th:nth-child(5), .loan-table td:nth-child(5) { min-width: 160px; }
        .loan-table .soft-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 226px;
            min-height: 34px;
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
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .loan-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .loan-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><div class="fw-bold"><i class="bi bi-clipboard-data me-2 text-warning"></i>Data Peminjaman</div><div class="small text-white-50">Monitoring pengajuan, finalisasi QR, dan serah barang</div></div><div class="topbar-actions d-flex gap-2"><div class="dropdown"><button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi"><i class="bi bi-bell"></i><?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu"><div class="fw-bold px-2 py-1">Notifikasi</div><?php if (empty($notif_items)): ?><div class="small text-muted px-2 py-3">Belum ada notifikasi.</div><?php else: foreach ($notif_items as $n): ?><a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $n->id_notifikasi) ?>"><div class="fw-semibold small"><?= html_escape($n->judul) ?></div><div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div></a><?php endforeach; endif; ?></div></div><a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Pengembalian</a><a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></div></div></div></header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-4">
            <form class="row g-3 align-items-end" method="get" action="<?= base_url('index.php/admin/peminjaman') ?>">
                <input type="hidden" name="per_page" value="<?= html_escape($current_per_page) ?>">
                <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Pencarian</label><input type="text" name="q" class="form-control" value="<?= html_escape($filters['pencarian'] ?? '') ?>" placeholder="Nama, NIM/NIP, atau keperluan"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Status</label><select name="status" class="form-select"><?php foreach($status_options as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?: 'Semua Status' ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Tanggal Pinjam</label><input type="date" name="tanggal" class="form-control" value="<?= html_escape($filters['tanggal'] ?? '') ?>"></div>
                <div class="col-md-2 d-grid gap-2"><button class="btn btn-fik"><i class="bi bi-search me-1"></i> Filter</button><a href="<?= $export_url ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel</a></div>
            </form>
        </section>

        <section class="panel-card p-0 loan-table-card">
            <div class="table-responsive">
                <table class="table table-hover loan-table">
                    <thead><tr><th class="ps-3">Peminjam</th><th>Barang</th><th>Jadwal</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada data peminjaman.</td></tr>
                    <?php else: foreach($peminjaman as $p): ?>
                        <tr>
                            <td class="ps-3"><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="fw-semibold"><?= (int)($p->total_jenis ?? 1) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="small text-muted"><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><?= html_escape($d->nama_aset) ?> (<?= (int)$d->jumlah_pinjam ?>), <?php endforeach; else: ?>- <?php endif; ?></div></td>
                            <td><div><?= html_escape($p->tanggal_pinjam ?? '-') ?></div><div class="small text-muted">s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></div></td>
                            <td><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span><?php if(!empty($p->foto_pengembalian)): ?><div class="small mt-1"><a href="<?= base_url($p->foto_pengembalian) ?>" target="_blank" rel="noopener">Evidence kembali</a></div><?php endif; ?><?php if (!empty($p->evidence_serah)): ?><div class="small mt-1"><?php foreach ($p->evidence_serah as $evidence): ?><a class="d-block" href="<?= base_url($evidence->nama_file) ?>" target="_blank" rel="noopener"><i class="bi bi-image me-1"></i><?= html_escape($evidence->original_name ?: 'Evidence serah terima') ?></a><?php endforeach; ?></div><?php endif; ?></td>
                            <td class="text-end pe-3">
                                <?php if(($p->status ?? '') === 'Disetujui (Menunggu Finalisasi QR)'): ?>
                                    <a class="btn btn-sm btn-outline-primary rounded-pill loan-action-btn" href="<?= base_url('index.php/admin/peminjaman/finalkan_qr/'.$p->id_peminjaman) ?>" onclick="return confirm('Finalkan QR dan kunci data transaksi ini?')"><i class="bi bi-qr-code me-1"></i> Finalkan QR</a>
                                <?php elseif(($p->status ?? '') === 'Disetujui (Menunggu Pengambilan)'): ?>
                                    <a class="btn btn-sm btn-outline-success rounded-pill loan-action-btn" href="<?= base_url('index.php/admin/peminjaman/serah_terima/'.rawurlencode($p->group_id)) ?>"><i class="bi bi-box-arrow-up-right me-1"></i> Serah Barang</a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
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
                            <option value="all" <?= $current_per_page === 'all' ? 'selected' : '' ?>>Semua</option>
                        </select>
                        <span>Total item: <?= (int) ($pagination['total'] ?? 0) ?></span>
                    </div>
                    <div class="loan-pagination-status">Halaman: <?= $page ?> dari <?= $total_pages ?></div>
                    <nav aria-label="Pagination peminjaman">
                        <ul class="pagination pagination-sm loan-pagination">
                            <?php $prev_query = http_build_query(array_merge($base_query, ['page' => max(1, $page - 1)])); ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($prev_query ? '?'.$prev_query : '')) ?>">Previous</a></li>
                            <?php for($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php $next_query = http_build_query(array_merge($base_query, ['page' => min($total_pages, $page + 1)])); ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($next_query ? '?'.$next_query : '')) ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const loanPageSize = document.getElementById('loanPageSize');
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
