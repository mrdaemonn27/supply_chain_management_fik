<?php $status_class = function ($status) { return 'status-' . str_replace(' ', '-', $status ?: 'Menunggu Persetujuan'); }; ?>
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
        .status-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Dikembalikan { background: rgba(25,135,84,.12); color: #198754; }
        .status-Ditolak { background: rgba(220,53,69,.12); color: #dc3545; }
        @media (max-width: 767.98px) { .topbar-actions { width: 100%; } .topbar-actions .btn { flex: 1; } }
    </style>
</head>
<body>
<header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><div class="fw-bold"><i class="bi bi-patch-check me-2 text-warning"></i>Approval Peminjaman</div><div class="small text-white-50">Validasi pengajuan sebelum barang dipinjam</div></div><div class="topbar-actions d-flex gap-2"><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-list-check me-1"></i> Peminjaman</a></div></div></div></header>
<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>
    <div class="row g-3">
    <?php if(empty($pengajuan)): ?>
        <div class="col-12"><div class="panel-card p-5 text-center text-muted"><i class="bi bi-check-circle display-5 d-block mb-3 text-success"></i>Tidak ada pengajuan yang menunggu approval.</div></div>
    <?php else: foreach($pengajuan as $p): ?>
        <div class="col-lg-6 col-xxl-4"><div class="panel-card h-100 p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h5 class="fw-bold mb-1"><?= html_escape($p->nama_peminjam ?? '-') ?></h5><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?> · <?= html_escape($p->tanggal_pinjam ?? '-') ?></div></div><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span></div>
            <div class="mb-3"><div class="small fw-semibold text-muted mb-1">Barang diajukan</div><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><div class="d-flex justify-content-between border-bottom py-1 small"><span><?= html_escape($d->nama_aset) ?></span><strong><?= (int)$d->jumlah_pinjam ?> unit</strong></div><?php endforeach; endif; ?></div>
            <div class="mb-3"><div class="small fw-semibold text-muted mb-1">Keperluan</div><div class="small"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div></div>
            <div class="d-flex flex-column gap-2 mt-auto">
                <form method="post" action="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>"><input type="hidden" name="catatan_laboran" value="Disetujui oleh Laboran"><button class="btn btn-success w-100 rounded-pill" onclick="return confirm('Setujui pengajuan ini? Stok akan dikurangi otomatis.')"><i class="bi bi-check2-circle me-1"></i> Setujui</button></form>
                <form method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>" class="d-flex gap-2"><input type="text" name="catatan_laboran" class="form-control form-control-sm" placeholder="Catatan penolakan"><button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button></form>
            </div>
        </div></div>
    <?php endforeach; endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>