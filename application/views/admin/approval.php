<?php
$status_class = function ($status) { return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Menunggu Verifikasi Laboran')); };
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= isset($title) ? $title : 'Approval Peminjaman' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
<header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><div class="fw-bold"><i class="bi bi-patch-check me-2 text-warning"></i>Pengecekan Laboran</div><div class="small text-white-50">Cek stok fisik lalu teruskan pengajuan ke Kaur</div></div><div class="topbar-actions d-flex gap-2"><div class="dropdown"><button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi"><i class="bi bi-bell"></i><?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?></button><div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu"><div class="fw-bold px-2 py-1">Notifikasi</div><?php if (empty($notif_items)): ?><div class="small text-muted px-2 py-3">Belum ada notifikasi.</div><?php else: foreach ($notif_items as $n): ?><a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>"><div class="fw-semibold small"><?= html_escape($n->judul) ?></div><div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div></a><?php endforeach; endif; ?></div></div><a href="<?= base_url('index.php/admin/peminjaman/export_pengajuan_acc') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview ACC</a><a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-list-check me-1"></i> Peminjaman</a></div></div></div></header>
<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>
    <div class="row g-3">
    <?php if(empty($pengajuan)): ?>
        <div class="col-12"><div class="panel-card p-5 text-center text-muted"><i class="bi bi-check-circle display-5 d-block mb-3 text-success"></i>Tidak ada pengajuan yang menunggu approval.</div></div>
    <?php else: foreach($pengajuan as $p): ?>
        <div class="col-lg-6 col-xxl-4"><div class="panel-card h-100 p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h5 class="fw-bold mb-1"><?= html_escape($p->nama_peminjam ?? '-') ?></h5><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?> - <?= html_escape($p->tanggal_pinjam ?? '-') ?></div></div><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span></div>
            <div class="mb-3"><div class="small fw-semibold text-muted mb-1">Barang diajukan</div><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><div class="d-flex justify-content-between border-bottom py-1 small"><span><?= html_escape($d->nama_aset) ?></span><strong><?= (int)$d->jumlah_pinjam ?> unit</strong></div><?php endforeach; endif; ?></div>
            <div class="mb-3"><div class="small fw-semibold text-muted mb-1">Keperluan</div><div class="small"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div></div>
            <div class="d-flex flex-column gap-2 mt-auto">
                <form method="post" action="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>"><input type="hidden" name="catatan_laboran" value="Stok fisik tersedia, diteruskan ke Kaur"><button class="btn btn-success w-100 rounded-pill" onclick="return confirm('Teruskan pengajuan ini ke Kaur? Stok belum dikurangi.')"><i class="bi bi-send-check me-1"></i> Teruskan ke Kaur</button></form>
                <form method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>" class="d-flex gap-2"><input type="text" name="catatan_laboran" class="form-control form-control-sm" placeholder="Catatan penolakan"><button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button></form>
            </div>
        </div></div>
    <?php endforeach; endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
