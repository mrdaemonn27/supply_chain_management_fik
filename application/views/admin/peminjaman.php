<?php
$status_options = isset($status_options) && is_array($status_options) ? $status_options : ['', 'Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu ACC Kaur', 'Disetujui (Menunggu Finalisasi QR)', 'Disetujui (Menunggu Pengambilan)', 'Ditolak'];
$status_class = function ($status) { return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Menunggu Verifikasi Laboran')); };
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($peminjaman ?? []), 'per_page' => 10];
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
        @media (max-width: 767.98px) { .topbar-actions { width: 100%; } .topbar-actions .btn { flex: 1; } .topbar-actions .notif-bell { flex: 0 0 38px; } }
    </style>
</head>
<body>
    <header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><div class="fw-bold"><i class="bi bi-clipboard-data me-2 text-warning"></i>Data Peminjaman</div><div class="small text-white-50">Monitoring pengajuan, finalisasi QR, dan serah barang</div></div><div class="topbar-actions d-flex gap-2"><div class="dropdown"><button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi"><i class="bi bi-bell"></i><?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu"><div class="fw-bold px-2 py-1">Notifikasi</div><?php if (empty($notif_items)): ?><div class="small text-muted px-2 py-3">Belum ada notifikasi.</div><?php else: foreach ($notif_items as $n): ?><a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>"><div class="fw-semibold small"><?= html_escape($n->judul) ?></div><div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div></a><?php endforeach; endif; ?></div></div><a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Pengembalian</a><a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></div></div></div></header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-4">
            <form class="row g-3 align-items-end" method="get" action="<?= base_url('index.php/admin/peminjaman') ?>">
                <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Pencarian</label><input type="text" name="q" class="form-control" value="<?= html_escape($filters['pencarian'] ?? '') ?>" placeholder="Nama, NIM/NIP, atau keperluan"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Status</label><select name="status" class="form-select"><?php foreach($status_options as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?: 'Semua Status' ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Tanggal Pinjam</label><input type="date" name="tanggal" class="form-control" value="<?= html_escape($filters['tanggal'] ?? '') ?>"></div>
                <div class="col-md-2 d-grid gap-2"><button class="btn btn-fik"><i class="bi bi-search me-1"></i> Filter</button><a href="<?= $export_url ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel</a></div>
            </form>
        </section>

        <section class="panel-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th class="ps-3">Peminjam</th><th>Barang</th><th>Jadwal</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada data peminjaman.</td></tr>
                    <?php else: foreach($peminjaman as $p): ?>
                        <tr>
                            <td class="ps-3"><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="fw-semibold"><?= (int)($p->total_jenis ?? 1) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="small text-muted"><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><?= html_escape($d->nama_aset) ?> (<?= (int)$d->jumlah_pinjam ?>), <?php endforeach; else: ?>- <?php endif; ?></div></td>
                            <td><div><?= html_escape($p->tanggal_pinjam ?? '-') ?></div><div class="small text-muted">s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></div></td>
                            <td><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span><?php if(!empty($p->foto_pengembalian)): ?><div class="small mt-1"><a href="<?= base_url($p->foto_pengembalian) ?>" target="_blank">Evidence kembali</a></div><?php endif; ?></td>
                            <td class="text-end pe-3">
                                <?php if(($p->status ?? '') === 'Disetujui (Menunggu Finalisasi QR)'): ?>
                                    <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= base_url('index.php/admin/peminjaman/finalkan_qr/'.$p->id_peminjaman) ?>" onclick="return confirm('Finalkan QR dan kunci data transaksi ini?')"><i class="bi bi-qr-code me-1"></i> Finalkan QR</a>
                                <?php elseif(($p->status ?? '') === 'Disetujui (Menunggu Pengambilan)'): ?>
                                    <a class="btn btn-sm btn-outline-success rounded-pill" href="<?= base_url('index.php/admin/peminjaman/serah_terima/'.rawurlencode($p->group_id)) ?>"><i class="bi bi-box-arrow-up-right me-1"></i> Serah Barang</a>
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
                ];
                $page = (int) ($pagination['page'] ?? 1);
                $total_pages = (int) ($pagination['total_pages'] ?? 1);
            ?>
            <?php if($total_pages > 1): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 px-3 py-3 border-top">
                    <div class="small text-muted">Menampilkan <?= count($peminjaman) ?> dari <?= (int)($pagination['total'] ?? 0) ?> data</div>
                    <nav aria-label="Pagination peminjaman">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $prev_query = http_build_query(array_merge($base_query, ['page' => max(1, $page - 1)])); ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($prev_query ? '?'.$prev_query : '')) ?>">Prev</a></li>
                            <?php for($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php $next_query = http_build_query(array_merge($base_query, ['page' => min($total_pages, $page + 1)])); ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/peminjaman'.($next_query ? '?'.$next_query : '')) ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
