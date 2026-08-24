<?php
/** @var array $riwayat */
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$history_pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'per_page' => 10, 'total' => count($riwayat ?? []), 'total_pages' => 1];
$history_page = (int) $history_pagination['page'];
$history_total_pages = (int) $history_pagination['total_pages'];
$history_total = (int) $history_pagination['total'];
$history_per_page = (int) $history_pagination['per_page'];
$history_query = $_GET;
$history_query['per_page'] = $history_per_page;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - SCM FIK</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/loan-progress.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/loan-progress.css'); ?>">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }

        /* Palette FIK */
        .text-fik-orange { color: #ea5b1a !important; }
        .bg-fik-orange { background-color: #ea5b1a !important; }
        .text-fik-brown { color: #5d3315 !important; }

        /* Navbar */
        .navbar-custom { background-color: #ffffff; padding: 12px 0; border-bottom: 2px solid #ea5b1a; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .navbar-dark .navbar-nav .nav-link { color: #333333; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }
        .notif-bell { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }

        /* Custom Table Styling */
        .table-custom { border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table-custom thead th { background-color: #5d3315; color: white; font-weight: 500; border: none; padding: 15px; letter-spacing: 0.5px;}
        .table-custom tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #eee; background: white; }
        .table-custom tbody tr:hover td { background-color: #fafafa; }
        
        .table-custom th:nth-child(4), .table-custom td:nth-child(4) { width: 320px; min-width: 320px; }
        .badge-status { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; width:300px; min-width:300px; max-width:300px; height:42px; min-height:42px; padding:7px 14px; border-radius:999px; font-weight:600; font-size:.76rem; line-height:1.2; white-space:normal; text-align:center; }
        .history-search { max-width:980px; margin:0 auto 1.25rem; }
        .history-date { display:inline-flex; align-items:center; gap:.45rem; padding:.4rem .55rem; border-radius:8px; cursor:help; transition:background-color .18s ease; }
        .history-date:hover { background:#fff3eb; }
        .history-empty-filter { display:none; }
        .history-pagination { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1.25rem; }
        .history-pagination__info { margin:0; color:#6c757d; font-size:.85rem; }
        .history-pagination .pagination { flex-wrap:wrap; }
        .history-pagination .page-link {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:38px;
            height:38px;
            padding:.4rem .7rem;
            border-color:#e2e5e9;
            color:#5d3315;
            font-weight:600;
            box-shadow:none;
        }
        .history-pagination .page-item:first-child .page-link,
        .history-pagination .page-item:last-child .page-link { border-radius:10px; }
        .history-pagination .page-item.active .page-link { background:#ea5b1a; border-color:#ea5b1a; color:#fff; }
        .history-pagination .page-item:not(.active):not(.disabled) .page-link:hover { background:#fff3eb; border-color:#ea5b1a; color:#c44810; }
        .history-pagination .page-item.disabled .page-link { color:#adb5bd; background:#f3f4f6; }
        .history-list-summary { margin-bottom:0; }
        @media (max-width: 575.98px) {
            .history-pagination { flex-direction:column; justify-content:center; }
            .history-pagination__info { text-align:center; }
            .history-pagination .page-link { min-width:36px; height:36px; padding:.35rem .6rem; }
        }
    </style>
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="300" class="me-2">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/dashboard') ?>">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/peminjaman') ?>">Total Barang</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="alert('Pilih barang dari Katalog terlebih dahulu.'); return false;">Ajukan Peminjaman</a></li>
                    <!-- INI YANG AKTIF -->
                    <li class="nav-item"><a class="nav-link active" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a></li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
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
                <button class="btn btn-user"><i class="bi bi-person-circle me-1"></i> <?= $display_nama; ?></button>
            </div>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container py-5">
        <div class="mb-4 text-center" data-aos="fade-down">
            <h2 class="fw-bold text-dark mb-0">RIWAYAT <span class="text-fik-orange">PEMINJAMAN</span></h2>
        </div>

        <!-- Notifikasi Sukses -->
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center rounded-3 mb-4" data-aos="zoom-in">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div><?= $this->session->flashdata('success'); ?></div>
            </div>
        <?php endif; ?>

        <div class="history-search" data-aos="fade-up">
            <?php
                $multi_filter_id = 'historyMultiFilter';
                $multi_filter_mode = 'server';
                $multi_filter_fields = [
                    'all' => ['label' => 'Semua riwayat', 'placeholder' => 'Cari nama barang, status, kode aset, atau tanggal...'],
                    'barang' => ['label' => 'Nama barang', 'placeholder' => 'Cari nama barang...'],
                    'kode' => ['label' => 'Kode aset', 'placeholder' => 'Cari kode aset...'],
                    'status' => ['label' => 'Status', 'placeholder' => 'Cari status peminjaman...'],
                    'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal atau rentang tanggal', 'type' => 'date'],
                ];
                $multi_filter_rows = $filter_rows ?? [['field' => 'all', 'value' => '']];
                $multi_filter_action = current_url();
                $multi_filter_hidden = ['per_page' => $history_per_page, 'page' => 1, 'sort_by' => $history_sort ?? '', 'sort_dir' => $history_dir ?? 'desc'];
                $multi_filter_meta_id = 'historySearchCount';
                $multi_filter_meta = number_format($history_total, 0, ',', '.') . ' total riwayat';
                include APPPATH . 'views/admin/_multi_filter.php';
                unset($multi_filter_id, $multi_filter_mode, $multi_filter_fields, $multi_filter_rows, $multi_filter_meta_id, $multi_filter_meta);
            ?>
        </div>

        <!-- Tabel Riwayat -->
        <?php if(!empty($riwayat)): ?>
        <div class="scm-pagination-top history-list-summary" aria-label="Pengaturan jumlah riwayat">
            <div class="scm-pagination-top__summary">
                <label for="historyPageSize">Tampilkan:</label>
                <select id="historyPageSize" class="form-select form-select-sm" aria-label="Jumlah riwayat per halaman" onchange="var u=new URL(window.location.href);u.searchParams.set('per_page',this.value);u.searchParams.set('page','1');window.location.assign(u.toString());">
                    <?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $history_per_page === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
                </select>
                <span>Total item: <span id="historyTotalItems"><?= number_format($history_total, 0, ',', '.') ?></span></span>
            </div>
        </div>
        <?php endif; ?>
        <div class="table-responsive" data-aos="fade-up">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <?php foreach (['tanggal' => 'Tgl Pengajuan', 'barang' => 'Nama Barang', 'masa' => 'Masa Pinjam', 'status' => 'Status Approval', 'qr' => 'Status QR'] as $sort_key => $sort_label): ?>
                        <th class="<?= $sort_key === 'qr' ? 'text-center' : '' ?>" aria-sort="<?= scm_sort_aria($sort_key, $history_sort ?? '', $history_dir ?? 'desc') ?>"><a class="scm-sort-control <?= ($history_sort ?? '') === $sort_key ? 'is-active' : '' ?>" href="<?= scm_sort_url($sort_key, $history_sort ?? '', $history_dir ?? 'desc') ?>"><?= html_escape($sort_label) ?><i class="bi <?= scm_sort_icon_class($sort_key, $history_sort ?? '', $history_dir ?? 'desc') ?>" aria-hidden="true"></i></a></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($riwayat)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">Belum ada riwayat peminjaman.</p>
                                <a href="<?= base_url('index.php/peminjaman') ?>" class="btn btn-sm btn-outline-secondary mt-2">Buka Katalog</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($riwayat as $r): ?>
                        <?php $history_dates = implode(' ', [substr((string)($r->created_at ?? ''), 0, 10), substr((string)($r->tanggal_pinjam ?? ''), 0, 10), substr((string)($r->tanggal_kembali_rencana ?? ''), 0, 10), tanggal_indonesia($r->created_at ?? null), tanggal_indonesia($r->tanggal_pinjam ?? null), tanggal_indonesia($r->tanggal_kembali_rencana ?? null)]); $search_label = strtolower(implode(' ', [$r->nama_aset ?? '', $r->kode_aset ?? '', $r->status ?? '', $history_dates])); ?>
                        <tr data-history-row data-search="<?= html_escape($search_label) ?>" data-filter-all="<?= html_escape($search_label) ?>" data-filter-barang="<?= html_escape($r->nama_aset ?? '') ?>" data-filter-kode="<?= html_escape($r->kode_aset ?? '') ?>" data-filter-status="<?= html_escape($r->status ?? '') ?>" data-filter-tanggal="<?= html_escape($history_dates) ?>">
                            <td>
                                <div class="fw-semibold text-dark"><?= tanggal_indonesia($r->created_at) ?></div>
                                <div class="text-muted small"><?= date('H:i', strtotime($r->created_at)) ?> WIB</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $r->nama_aset ?></div>
                                <div class="text-muted small">Kode: <?= $r->kode_aset ?> &bull; Jml: <span class="text-fik-orange fw-bold"><?= $r->jumlah_pinjam ?></span></div>
                            </td>
                            <td>
                                <span class="history-date" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" title="Masa pinjam: <?= html_escape(masa_pinjam_indonesia($r->tanggal_pinjam, $r->tanggal_kembali_rencana)) ?>">
                                    <i class="bi bi-calendar-range text-fik-orange"></i>
                                    <span><span class="d-block small fw-semibold"><?= tanggal_indonesia($r->tanggal_pinjam) ?></span><span class="d-block small text-muted">s.d. <?= tanggal_indonesia($r->tanggal_kembali_rencana) ?></span></span>
                                </span>
                            </td>
                            <td>
                                <?php $loan_progress_item = $r; $loan_progress_compact = true; include APPPATH . 'views/shared/loan_progress.php'; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $qr_locked = (int)($r->qr_locked ?? 0) === 1;
                                    $show_pickup_qr = $qr_locked && $r->status === 'Disetujui (Menunggu Pengambilan)';
                                    $show_return_qr = $qr_locked && in_array($r->status, ['Sedang Dipinjam', 'Dipinjam'], true);
                                ?>
                                <?php if($show_pickup_qr || $show_return_qr): ?>
                                    <button class="btn btn-sm btn-outline-dark fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#qrModal<?= $r->id_peminjaman ?>">
                                        <i class="bi bi-qr-code-scan me-1"></i> QR Transaksi
                                    </button>
                                <?php else: ?>
                                    <span class="small text-muted">QR belum aktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if(!empty($riwayat)): ?>
        <div id="historyPaginationWrap" class="history-pagination" aria-live="polite">
            <?php $history_first = $history_total ? (($history_page - 1) * $history_per_page) + 1 : 0; $history_last = min($history_total, $history_page * $history_per_page); ?>
            <p id="historyPageInfo" class="history-pagination__info">Menampilkan <?= number_format($history_first, 0, ',', '.') ?>–<?= number_format($history_last, 0, ',', '.') ?> dari <?= number_format($history_total, 0, ',', '.') ?> data</p>
            <nav id="historyPaginationNav" aria-label="Navigasi halaman riwayat peminjaman">
                <ul id="historyPagination" class="pagination pagination-sm mb-0">
                    <?php $history_query['page'] = max(1, $history_page - 1); ?><li class="page-item <?= $history_page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($history_query) ?>" aria-label="Halaman sebelumnya">Previous</a></li>
                    <?php foreach (scm_pagination_tokens($history_page, $history_total_pages) as $token): ?>
                        <?php if (is_string($token)): ?><li class="page-item disabled" aria-hidden="true"><span class="page-link">&hellip;</span></li>
                        <?php else: $history_query['page'] = $token; ?><li class="page-item <?= $token === $history_page ? 'active' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($history_query) ?>" <?= $token === $history_page ? 'aria-current="page"' : '' ?>><?= $token ?></a></li><?php endif; ?>
                    <?php endforeach; ?>
                    <?php $history_query['page'] = min($history_total_pages, $history_page + 1); ?><li class="page-item <?= $history_page >= $history_total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($history_query) ?>" aria-label="Halaman berikutnya">Next</a></li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- MODAL TIKET QR CODE (DIPINDAHKAN KELUAR DARI TABLE AGAR TIDAK BUG/KEPOTONG) -->
    <?php if(!empty($riwayat)): ?>
        <?php foreach($riwayat as $r): ?>
        <?php
            $qr_locked = (int)($r->qr_locked ?? 0) === 1;
            $show_pickup_qr = $qr_locked && $r->status === 'Disetujui (Menunggu Pengambilan)';
            $show_return_qr = $qr_locked && in_array($r->status, ['Sedang Dipinjam', 'Dipinjam'], true);
            if(!$show_pickup_qr && !$show_return_qr) { continue; }
            $qr_url = site_url('admin/peminjaman/serah_terima/'.rawurlencode($r->group_id));
        ?>
        <div class="modal fade" id="qrModal<?= $r->id_peminjaman ?>" tabindex="-1" aria-labelledby="qrModalLabel<?= $r->id_peminjaman ?>" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content text-center p-4 border-0 shadow-lg" style="border-radius: 20px;">
                    <h5 class="fw-bold text-fik-orange mb-1" id="qrModalLabel<?= $r->id_peminjaman ?>">QR Transaksi Laboratorium</h5>
                    <p class="small text-muted mb-4"><?= $show_return_qr ? 'Tunjukkan QR yang sama kepada Laboran saat mengembalikan barang.' : 'Tunjukkan QR ini kepada Laboran saat serah terima barang. QR yang sama dipakai kembali saat pengembalian.' ?></p>
                    
                    <!-- QR transaksi tunggal: dipakai untuk serah barang dan pengembalian -->
                    <div class="bg-white p-3 rounded-4 mb-3 mx-auto shadow-sm border" style="display: inline-block;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($qr_url) ?>" alt="QR Code" class="img-fluid">
                    </div>
                    
                    <div class="font-monospace fs-6 fw-bold bg-light border px-3 py-2 rounded-3 text-secondary mb-3">
                        <?= $r->group_id ?>
                    </div>

                    <div class="alert alert-info py-2 small mb-4 text-start">
                        <strong>Barang:</strong> <?= $r->nama_aset ?><br>
                        <strong>Status:</strong> <?= $r->status ?>
                    </div>
                    
                    <button type="button" class="btn btn-secondary w-100 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-center py-4 mt-5">
        <div class="container">
            <p class="small text-white opacity-50 m-0">
                &copy; <?= date('Y') ?> SCM Fakultas Industri Kreatif - Telkom University. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 20 });
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        const historyFilterRoot = document.getElementById('historyMultiFilter');
        if (historyFilterRoot?.dataset.mode === 'client') {
            const rows = Array.from(document.querySelectorAll('[data-history-row]'));
            const emptyRow = document.querySelector('.history-empty-filter');
            const counter = document.getElementById('historySearchCount');
            const paginationWrap = document.getElementById('historyPaginationWrap');
            const paginationNav = document.getElementById('historyPaginationNav');
            const pagination = document.getElementById('historyPagination');
            const pageInfo = document.getElementById('historyPageInfo');
            const pageSizeSelect = document.getElementById('historyPageSize');
            const totalItems = document.getElementById('historyTotalItems');
            let currentPage = 1;

            const paginationPages = totalPages => {
                if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);
                if (currentPage <= 3) return [1, 2, 3, 4, 5, 'ellipsis-end', totalPages];
                if (currentPage >= totalPages - 2) return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
                return [1, 'ellipsis-start', currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, 'ellipsis-end', totalPages];
            };

            const pageButton = (label, page, options = {}) => {
                const item = document.createElement('li');
                item.className = `page-item${options.active ? ' active' : ''}${options.disabled ? ' disabled' : ''}`;

                if (options.ellipsis) {
                    item.classList.add('disabled');
                    item.innerHTML = '<span class="page-link" aria-hidden="true">&hellip;</span>';
                    return item;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.dataset.page = String(page);
                button.innerHTML = label;
                if (options.label) button.setAttribute('aria-label', options.label);
                if (options.active) button.setAttribute('aria-current', 'page');
                if (options.disabled) button.disabled = true;
                item.appendChild(button);
                return item;
            };

            const renderPagination = totalPages => {
                pagination.replaceChildren();
                pagination.appendChild(pageButton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', currentPage - 1, {
                    disabled: currentPage === 1,
                    label: 'Halaman sebelumnya'
                }));

                paginationPages(totalPages).forEach(page => {
                    if (typeof page === 'string') {
                        pagination.appendChild(pageButton('', 0, { ellipsis: true }));
                        return;
                    }
                    pagination.appendChild(pageButton(String(page), page, {
                        active: page === currentPage,
                        label: `Halaman ${page}`
                    }));
                });

                pagination.appendChild(pageButton('<i class="bi bi-chevron-right" aria-hidden="true"></i>', currentPage + 1, {
                    disabled: currentPage === totalPages,
                    label: 'Halaman berikutnya'
                }));
            };

            const renderHistory = (resetPage = false) => {
                const criteria = window.AdminMultiFilter?.getCriteria(historyFilterRoot) || [];
                const filteredRows = rows.filter(row => window.AdminMultiFilter?.matches(row, criteria) ?? true);
                const pageSize = Number(pageSizeSelect?.value || 10);
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                if (resetPage) currentPage = 1;
                currentPage = Math.min(currentPage, totalPages);

                rows.forEach(row => row.classList.add('d-none'));
                const firstIndex = (currentPage - 1) * pageSize;
                filteredRows.slice(firstIndex, firstIndex + pageSize).forEach(row => row.classList.remove('d-none'));

                if (emptyRow) emptyRow.style.display = filteredRows.length ? 'none' : 'table-row';
                if (counter) counter.textContent = criteria.length ? `${filteredRows.length} riwayat ditemukan` : `${rows.length} total riwayat`;
                if (totalItems) totalItems.textContent = new Intl.NumberFormat('id-ID').format(filteredRows.length);

                if (paginationWrap) paginationWrap.classList.toggle('d-none', filteredRows.length === 0);
                if (paginationNav) paginationNav.classList.toggle('d-none', totalPages <= 1);
                if (pageInfo) {
                    const firstShown = filteredRows.length ? firstIndex + 1 : 0;
                    const lastShown = Math.min(firstIndex + pageSize, filteredRows.length);
                    const format = value => new Intl.NumberFormat('id-ID').format(value);
                    pageInfo.textContent = filteredRows.length
                        ? `Menampilkan ${format(firstShown)}–${format(lastShown)} dari ${format(filteredRows.length)} data`
                        : 'Menampilkan 0 dari 0 data';
                }
                if (pagination && filteredRows.length) renderPagination(totalPages);
            };

            historyFilterRoot.addEventListener('admin-multi-filter-change', () => renderHistory(true));
            pageSizeSelect?.addEventListener('change', () => renderHistory(true));
            if (pagination) {
                pagination.addEventListener('click', event => {
                    const button = event.target.closest('button[data-page]');
                    if (!button || button.disabled) return;
                    currentPage = Number(button.dataset.page);
                    renderHistory();
                });
            }
            renderHistory();
        }
    </script>
</body>
</html>
