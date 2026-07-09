<?php
$status_options = ['', 'Menunggu Persetujuan', 'Dipinjam', 'Dikembalikan', 'Ditolak', 'Terlambat'];
$status_class = function ($status) { return 'status-' . str_replace(' ', '-', $status ?: 'Menunggu Persetujuan'); };
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
        .status-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Dikembalikan { background: rgba(25,135,84,.12); color: #198754; }
        .status-Ditolak { background: rgba(220,53,69,.12); color: #dc3545; }
        @media (max-width: 767.98px) { .topbar-actions { width: 100%; } .topbar-actions .btn { flex: 1; } }
    </style>
</head>
<body>
    <header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3"><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2"><div><div class="fw-bold"><i class="bi bi-clipboard-data me-2 text-warning"></i>Data Peminjaman</div><div class="small text-white-50">Monitoring transaksi dan pengembalian barang</div></div><div class="topbar-actions d-flex gap-2"><a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a><a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></div></div></div></header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-4">
            <form class="row g-3 align-items-end" method="get" action="<?= base_url('index.php/admin/peminjaman') ?>">
                <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Pencarian</label><input type="text" name="q" class="form-control" value="<?= html_escape($filters['pencarian'] ?? '') ?>" placeholder="Nama, NIM/NIP, atau keperluan"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Status</label><select name="status" class="form-select"><?php foreach($status_options as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?: 'Semua Status' ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Tanggal Pinjam</label><input type="date" name="tanggal" class="form-control" value="<?= html_escape($filters['tanggal'] ?? '') ?>"></div>
                <div class="col-md-2 d-grid"><button class="btn btn-fik"><i class="bi bi-search me-1"></i> Filter</button></div>
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
                            <td><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span></td>
                            <td class="text-end pe-3"><?php if(($p->status ?? '') === 'Dipinjam'): ?><a href="<?= base_url('index.php/admin/peminjaman/kembalikan/'.$p->id_peminjaman) ?>" class="btn btn-sm btn-outline-success rounded-pill" onclick="return confirm('Tandai barang ini sudah dikembalikan?')"><i class="bi bi-arrow-counterclockwise me-1"></i> Kembali</a><?php else: ?><span class="text-muted small">-</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>