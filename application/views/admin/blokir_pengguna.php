<?php
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$blokir = isset($blokir) && is_array($blokir) ? $blokir : [];
$peminjam_options = isset($peminjam_options) && is_array($peminjam_options) ? $peminjam_options : [];
$filters = isset($filters) && is_array($filters) ? $filters : [];
$selected_status = trim((string) ($filters['status'] ?? ''));

$status_options = [];
foreach ($blokir as $row) {
    $status_value = trim((string) ($row->status ?? ''));
    if ($status_value !== '') {
        $status_options[$status_value] = $status_value;
    }
}
if ($selected_status !== '') {
    $status_options[$selected_status] = $selected_status;
}
ksort($status_options, SORT_NATURAL | SORT_FLAG_CASE);

$format_date = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y', $timestamp) : $value;
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Blokir Pengguna') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --blokir-orange: #ea5b1a;
            --blokir-ink: #1f2937;
            --blokir-muted: #64748b;
            --blokir-border: #e5e7eb;
            --blokir-surface: #ffffff;
            --blokir-page: #f4f6f8;
        }

        body {
            background: var(--blokir-page);
            color: var(--blokir-ink);
            font-family: 'Poppins', sans-serif;
        }

        .topbar { background: #1f1f1f; border-bottom: 4px solid var(--blokir-orange); color: #fff; }
        .btn-fik { background: var(--blokir-orange); border-color: var(--blokir-orange); color: #fff; }
        .btn-fik:hover, .btn-fik:focus { background: #c94d13; border-color: #c94d13; color: #fff; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }

        .blokir-main { max-width: 1600px; }
        .blokir-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        .blokir-heading h1 { color: #111827; font-size: clamp(1.55rem, 2vw, 2rem); font-weight: 700; margin: 0; }
        .blokir-heading p { color: var(--blokir-muted); font-size: .9rem; margin: 4px 0 0; }
        .blokir-heading .btn { min-height: 42px; padding-inline: 18px; }

        .blokir-panel { background: var(--blokir-surface); border: 1px solid var(--blokir-border); border-radius: 12px; box-shadow: 0 8px 24px rgba(15, 23, 42, .045); }
        .blokir-toolbar { padding: 18px; margin-bottom: 18px; }
        .blokir-toolbar .form-label { color: #475569; font-size: .75rem; font-weight: 600; margin-bottom: 7px; }
        .blokir-toolbar .form-control, .blokir-toolbar .form-select { min-height: 42px; border-color: #d7dde5; font-size: .85rem; }
        .blokir-toolbar .btn { min-height: 42px; }
        .form-control:focus, .form-select:focus { border-color: var(--blokir-orange); box-shadow: 0 0 0 .2rem rgba(234, 91, 26, .14); }

        .blokir-table-wrap { overflow-x: auto; }
        .blokir-table { min-width: 940px; margin: 0; }
        .blokir-table th.blokir-index-column, .blokir-table td.blokir-index-column { min-width: 58px; width: 58px; padding-right: 10px; padding-left: 10px; text-align: center; white-space: nowrap; }
        .blokir-index { color: #64748b; font-size: .8rem; font-weight: 500; }
        .blokir-table thead th { background: #f8fafc; border-bottom: 1px solid var(--blokir-border); color: #111827 !important; font-size: .7rem; font-family: inherit; font-weight: 700 !important; letter-spacing: .06em; padding: 14px 16px; text-transform: uppercase; white-space: nowrap; }
        .blokir-table thead th, .blokir-table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .blokir-table tbody td { border-color: var(--blokir-border); color: #334155; font-size: .82rem; padding: 15px 16px; vertical-align: middle; }
        .blokir-table tbody tr:last-child td { border-bottom: 0; }
        .blokir-table tbody tr { transition: background-color .18s ease; }
        .blokir-table tbody tr:hover { background: #fffaf7; }
        .blokir-user-name { color: #1f2937; font-weight: 600; }
        .blokir-meta { color: #7b8798; font-size: .74rem; margin-top: 3px; }
        .blokir-period { color: #334155; white-space: nowrap; }
        .blokir-period .blokir-meta { white-space: nowrap; }
        .blokir-reason { display: -webkit-box; max-width: 390px; overflow: hidden; line-height: 1.55; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .blokir-open-note { color: #7b8798; font-size: .72rem; line-height: 1.45; margin-top: 5px; max-width: 390px; }
        .blokir-status { align-items: center; display: inline-flex; gap: 8px; font-size: .8rem; font-weight: 600; white-space: nowrap; }
        .blokir-status-dot { border-radius: 50%; display: inline-block; height: 8px; width: 8px; }
        .blokir-status-active { color: #b54708; }
        .blokir-status-active .blokir-status-dot { background: #f59e0b; }
        .blokir-status-done { color: #15803d; }
        .blokir-status-done .blokir-status-dot { background: #22c55e; }
        .blokir-status-neutral { color: #475569; }
        .blokir-status-neutral .blokir-status-dot { background: #94a3b8; }
        .blokir-action { border-color: #cbd5e1; color: #475569; font-size: .76rem; min-height: 34px; padding: 5px 12px; }
        .blokir-action:hover { background: #fff7ed; border-color: #fdba74; color: #c2410c; }

        .blokir-empty { color: var(--blokir-muted); padding: 58px 20px !important; text-align: center; }
        .blokir-empty-icon { align-items: center; background: #fff7ed; border-radius: 50%; color: var(--blokir-orange); display: inline-flex; height: 48px; justify-content: center; margin-bottom: 12px; width: 48px; }
        .blokir-empty strong { color: #334155; display: block; font-size: .95rem; }
        .blokir-empty span { display: block; font-size: .78rem; margin: 5px auto 16px; }

        .blokir-footer { align-items: center; border-top: 1px solid var(--blokir-border); display: grid; gap: 12px; grid-template-columns: minmax(260px, 1fr) auto minmax(300px, 1fr); min-height: 68px; padding: 12px 18px; }
        .blokir-footer-meta { color: #64748b; font-size: .78rem; white-space: nowrap; }
        .blokir-page-info { color: #64748b; font-size: .76rem; text-align: center; white-space: nowrap; }
        .blokir-pagination { justify-content: flex-end; margin: 0; }
        .blokir-pagination .page-link { align-items: center; background: #fff; border-color: #dfe4ea; color: #2563eb; display: inline-flex; font-size: .75rem; height: 32px; justify-content: center; min-width: 32px; padding: 0 9px; }
        .blokir-pagination .page-item.active .page-link { background: var(--blokir-orange); border-color: var(--blokir-orange); color: #fff; }
        .blokir-pagination .page-item.disabled .page-link { background: #f8fafc; color: #a5afbd; }
        .blokir-pagination .page-link:hover { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
        .blokir-pagination .page-item.active .page-link:hover { background: #c94d13; color: #fff; }
        .blokir-page-size { align-items: center; color: #64748b; display: inline-flex; font-size: .76rem; gap: 8px; white-space: nowrap; }
        .blokir-page-size select { border: 1px solid #d7dde5; border-radius: 5px; color: #334155; font-size: .76rem; height: 32px; min-width: 64px; padding: 0 24px 0 9px; }

        .blokir-drawer { width: min(470px, 100vw); }
        .blokir-drawer .offcanvas-header { border-bottom: 1px solid var(--blokir-border); padding: 20px 24px; }
        .blokir-drawer .offcanvas-body { padding: 24px; }
        .blokir-drawer .form-label { color: #475569; font-size: .78rem; font-weight: 600; }
        .blokir-drawer .form-control { border-color: #d7dde5; min-height: 42px; }
        .blokir-drawer textarea.form-control { min-height: 112px; }
        .blokir-drawer .btn { min-height: 42px; }

        @media (max-width: 991.98px) {
            .blokir-heading { align-items: flex-start; flex-direction: column; }
            .blokir-heading .btn { width: 100%; }
            .blokir-footer { grid-template-columns: 1fr auto; }
            .blokir-page-info { grid-column: 1 / -1; grid-row: 1; order: -1; text-align: left; }
        }
        @media (max-width: 767.98px) {
            .topbar-actions { flex-wrap: wrap; width: 100%; }
            .topbar-actions .btn:not(.notif-bell) { flex: 1; }
            .blokir-toolbar { padding: 14px; }
            .blokir-footer { align-items: stretch; display: flex; flex-direction: column; padding: 14px; }
            .blokir-page-info { order: -1; text-align: left; }
            .blokir-pagination { justify-content: flex-start; overflow-x: auto; }
            .blokir-page-size { justify-content: space-between; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Blokir Pengguna</div>
                </div>
                <div class="topbar-actions d-flex gap-2">
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
                    <a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Pengembalian</a>
                    <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid blokir-main px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <div class="blokir-heading">
            <div>
                <h1>Blokir Pengguna</h1>
            </div>
            <button type="button" class="btn btn-fik rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#blockUserDrawer" aria-controls="blockUserDrawer">
                <i class="bi bi-plus-lg me-1"></i> Blokir Pengguna
            </button>
        </div>

        <?php
        $multi_filter_id = 'blockMultiFilter';
        $multi_filter_mode = 'client';
        $multi_filter_fields = [
            'pengguna' => ['label' => 'Pengguna / NIM', 'placeholder' => 'Cari nama pengguna atau NIM/NIP'],
            'periode' => ['label' => 'Periode', 'placeholder' => 'Pilih tanggal blokir atau batas blokir', 'type' => 'date'],
            'alasan' => ['label' => 'Alasan', 'placeholder' => 'Cari alasan atau catatan pembukaan'],
            'status' => ['label' => 'Status', 'placeholder' => 'Cari status blokir'],
        ];
        include APPPATH . 'views/admin/_multi_filter.php';
        ?>

        <section class="blokir-panel overflow-hidden">
            <div class="blokir-table-wrap">
                <table class="table blokir-table align-middle">
                    <thead>
                        <tr>
                            <th class="blokir-index-column">No</th>
                            <th>Pengguna</th>
                            <th>Periode</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="blockTableBody">
                        <?php if(empty($blokir)): ?>
                            <tr id="blockEmptyRow"><td colspan="6" class="blokir-empty">
                                <span class="blokir-empty-icon"><i class="bi bi-shield-check fs-5"></i></span>
                                <strong>Belum ada pengguna yang diblokir</strong>
                                <span>Data pembatasan pengguna akan muncul di sini.</span>
                                <button type="button" class="btn btn-sm btn-fik rounded-pill" data-bs-toggle="offcanvas" data-bs-target="#blockUserDrawer"><i class="bi bi-plus-lg me-1"></i> Blokir Pengguna</button>
                            </td></tr>
                        <?php else: foreach($blokir as $block_index => $b):
                            $block_status = trim((string) ($b->status ?? '-'));
                            $block_status_key = strtolower($block_status);
                            $block_status_class = $block_status_key === 'aktif' ? 'blokir-status-active' : ($block_status_key === 'dibuka' ? 'blokir-status-done' : 'blokir-status-neutral');
                            $search_text = strtolower(trim(implode(' ', [
                                (string) ($b->nama_peminjam ?? ''),
                                (string) ($b->nim_nip ?? ''),
                                (string) ($b->alasan ?? ''),
                                (string) ($b->catatan_buka ?? '')
                            ])));
                            $date_value = trim((string) ($b->tanggal_blokir ?? ''));
                        ?>
                            <tr class="blokir-data-row" data-filter-pengguna="<?= html_escape(($b->nama_peminjam ?? '') . ' ' . ($b->nim_nip ?? '')) ?>" data-filter-periode="<?= html_escape(($b->tanggal_blokir ?? '') . ' ' . ($b->batas_blokir ?? '')) ?>" data-filter-alasan="<?= html_escape(($b->alasan ?? '') . ' ' . ($b->catatan_buka ?? '')) ?>" data-filter-status="<?= html_escape($block_status) ?>">
                                <td class="blokir-index-column"><span class="blokir-index"><?= $block_index + 1 ?></span></td>
                                <td>
                                    <div class="blokir-user-name"><?= html_escape($b->nama_peminjam ?: '-') ?></div>
                                    <div class="blokir-meta"><?= html_escape($b->nim_nip ?? '-') ?></div>
                                </td>
                                <td>
                                    <div class="blokir-period"><?= html_escape($format_date($b->tanggal_blokir ?? '')) ?></div>
                                    <div class="blokir-meta">s.d. <?= html_escape($b->batas_blokir ? $format_date($b->batas_blokir) : 'Tanpa batas') ?></div>
                                </td>
                                <td>
                                    <div class="blokir-reason" title="<?= html_escape($b->alasan ?? '-') ?>"><?= html_escape($b->alasan ?? '-') ?></div>
                                    <?php if(!empty($b->catatan_buka)): ?><div class="blokir-open-note">Buka blokir: <?= html_escape($b->catatan_buka) ?></div><?php endif; ?>
                                </td>
                                <td><span class="blokir-status <?= $block_status_class ?>"><span class="blokir-status-dot"></span><?= html_escape($block_status) ?></span></td>
                                <td class="text-end">
                                    <?php if($block_status === 'Aktif'): ?>
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill blokir-action" data-bs-toggle="modal" data-bs-target="#openBlockModal<?= (int)$b->id_blokir ?>"><i class="bi bi-unlock me-1"></i> Buka</button>
                                    <?php else: ?>
                                        <span class="blokir-meta" title="<?= html_escape($b->dibuka_pada ?: '-') ?>"><?= html_escape($b->dibuka_pada ?: '-') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        <tr id="blockFilteredEmpty" hidden><td colspan="6" class="blokir-empty">
                            <span class="blokir-empty-icon"><i class="bi bi-search fs-5"></i></span>
                            <strong>Tidak ada data yang sesuai</strong>
                            <span>Ubah kata kunci atau filter untuk melihat data lainnya.</span>
                        </td></tr>
                    </tbody>
                </table>
            </div>
            <div class="blokir-footer" id="blockPaginationFooter">
                <div class="blokir-footer-meta">
                    <label class="blokir-page-size" for="blockPageSize">Tampilkan:
                        <select id="blockPageSize" aria-label="Jumlah data per halaman">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Semua</option>
                        </select>
                    </label>
                    <span class="ms-2" id="blockTotalItems">Total item: <?= count($blokir) ?></span>
                </div>
                <div class="blokir-page-info" id="blockPageInfo">Halaman: 1 dari 1</div>
                <nav aria-label="Pagination blokir pengguna">
                    <ul class="pagination blokir-pagination" id="blockPagination"></ul>
                </nav>
            </div>
        </section>
    </main>

    <div class="offcanvas offcanvas-end blokir-drawer" tabindex="-1" id="blockUserDrawer" aria-labelledby="blockUserDrawerLabel">
        <div class="offcanvas-header">
            <div>
                <h2 class="offcanvas-title h5 fw-bold mb-0" id="blockUserDrawerLabel">Blokir Pengguna</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <form method="post" action="<?= base_url('index.php/admin/blokir/simpan') ?>">
                <label class="form-label" for="blockNim">NIM/NIP</label>
                <input id="blockNim" type="text" name="nim_nip" class="form-control mb-3" list="peminjamList" placeholder="Masukkan NIM/NIP peminjam" required>
                <datalist id="peminjamList">
                    <?php foreach($peminjam_options as $p): ?>
                        <option value="<?= html_escape($p->nim_nip ?? '') ?>"><?= html_escape($p->nama_peminjam ?? '') ?></option>
                    <?php endforeach; ?>
                </datalist>
                <label class="form-label" for="blockName">Nama Peminjam</label>
                <input id="blockName" type="text" name="nama_peminjam" class="form-control mb-3" placeholder="Opsional, akan diisi otomatis bila user ditemukan">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="blockStart">Tanggal Blokir</label>
                        <input id="blockStart" type="date" name="tanggal_blokir" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="blockEnd">Batas Blokir</label>
                        <input id="blockEnd" type="date" name="batas_blokir" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="blockReason">Alasan</label>
                    <textarea id="blockReason" name="alasan" class="form-control" rows="4" placeholder="Contoh: Barang dikembalikan dalam kondisi rusak." required></textarea>
                </div>
                <div class="d-grid mt-4">
                    <button class="btn btn-danger rounded-pill" onclick="return confirm('Simpan blokir pengguna ini?')"><i class="bi bi-shield-lock me-1"></i> Simpan Blokir</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach($blokir as $b): if(($b->status ?? '') === 'Aktif'): ?>
        <div class="modal fade" id="openBlockModal<?= (int)$b->id_blokir ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= base_url('index.php/admin/blokir/buka/'.(int)$b->id_blokir) ?>">
                    <div class="modal-header"><h5 class="modal-title fw-bold">Buka Blokir</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><div class="small text-muted">Pengguna</div><div class="fw-semibold"><?= html_escape($b->nama_peminjam ?: $b->nim_nip) ?></div></div>
                        <label class="form-label small fw-semibold">Catatan Pembukaan</label>
                        <textarea name="catatan_buka" class="form-control" rows="3" placeholder="Contoh: Pengguna sudah menyelesaikan tanggung jawab."></textarea>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button class="btn btn-success rounded-pill px-4" onclick="return confirm('Buka blokir pengguna ini?')">Buka Blokir</button></div>
                </form>
            </div>
        </div>
    <?php endif; endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict';

            var rows = Array.prototype.slice.call(document.querySelectorAll('.blokir-data-row'));
            var pageSizeSelect = document.getElementById('blockPageSize');
            var pagination = document.getElementById('blockPagination');
            var pageInfo = document.getElementById('blockPageInfo');
            var totalItems = document.getElementById('blockTotalItems');
            var filteredEmpty = document.getElementById('blockFilteredEmpty');
            var filterRoot = document.getElementById('blockMultiFilter');
            var currentPage = 1;

            function getPageSize() {
                if (!pageSizeSelect || pageSizeSelect.value === 'all') {
                    return rows.length || 1;
                }
                return Math.max(1, parseInt(pageSizeSelect.value, 10) || 10);
            }
            function compactPageTokens(pageCount, currentPage) {
                if (pageCount <= 7) return Array.from({ length: pageCount }, function (_, index) { return index + 1; });
                if (currentPage <= 3) return [1, 2, 3, 4, 5, 'ellipsis', pageCount];
                if (currentPage >= pageCount - 2) return [pageCount - 4, pageCount - 3, pageCount - 2, pageCount - 1, pageCount];
                return [1, 'ellipsis', currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, 'ellipsis', pageCount];
            }

            function makePageItem(label, page, disabled, active, ellipsis) {
                var li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                if (ellipsis) {
                    li.setAttribute('aria-hidden', 'true');
                    var separator = document.createElement('span');
                    separator.className = 'page-link';
                    separator.textContent = '...';
                    li.appendChild(separator);
                    return li;
                }
                var link = document.createElement('button');
                link.type = 'button';
                link.className = 'page-link';
                link.textContent = label;
                link.disabled = disabled;
                link.setAttribute('aria-label', typeof label === 'number' ? 'Halaman ' + label : label);
                if (!disabled && !active) {
                    link.addEventListener('click', function () {
                        currentPage = page;
                        render();
                    });
                }
                li.appendChild(link);
                return li;
            }

            function render() {
                var criteria = filterRoot ? AdminMultiFilter.getCriteria(filterRoot) : [];
                var filtered = rows.filter(function (row) {
                    return AdminMultiFilter.matches(row, criteria);
                });
                var pageSize = getPageSize();
                var pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
                currentPage = Math.min(Math.max(currentPage, 1), pageCount);
                var start = (currentPage - 1) * pageSize;
                var visible = filtered.slice(start, start + pageSize);

                rows.forEach(function (row) {
                    row.hidden = visible.indexOf(row) === -1;
                });
                if (filteredEmpty) {
                    filteredEmpty.hidden = rows.length === 0 || filtered.length !== 0;
                }
                if (totalItems) totalItems.textContent = 'Total item: ' + filtered.length;
                if (pageInfo) pageInfo.textContent = 'Halaman: ' + currentPage + ' dari ' + pageCount;
                if (!pagination) return;

                pagination.replaceChildren();
                pagination.appendChild(makePageItem('Previous', currentPage - 1, currentPage === 1, false));
                compactPageTokens(pageCount, currentPage).forEach(function (token) {
                    pagination.appendChild(typeof token === 'string'
                        ? makePageItem('...', currentPage, true, false, true)
                        : makePageItem(token, token, false, token === currentPage));
                });
                pagination.appendChild(makePageItem('Next', currentPage + 1, currentPage === pageCount, false));
            }

            if (pageSizeSelect) pageSizeSelect.addEventListener('change', function () { currentPage = 1; render(); });
            if (filterRoot) filterRoot.addEventListener('admin-multi-filter-change', function () { currentPage = 1; render(); });
            render();
        }());
    </script>
</body>
</html>
