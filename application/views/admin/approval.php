<?php
$status_class = function ($status) {
    return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Menunggu Verifikasi Laboran'));
};
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pengajuan = isset($pengajuan) && is_array($pengajuan) ? $pengajuan : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Approval Peminjaman' ?></title>
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
        .table-wrap { overflow-x: auto; }
        .approval-table { min-width: 1120px; }
        .approval-table thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .approval-table td { vertical-align: middle; }
        .approval-table tbody tr:hover td { background: #fffaf7; }
        .soft-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-width: 250px;
            max-width: 250px;
            height: 42px;
            min-height: 38px;
            padding: 7px 12px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-weight: 700;
            font-size: .72rem;
            line-height: 1.2;
            white-space: normal;
            text-align: center;
        }
        .status-Menunggu-Persetujuan,
        .status-Menunggu-ACC-Kaprodi,
        .status-Menunggu-Verifikasi-Laboran,
        .status-Menunggu-Pengecekan-Laboran { background: rgba(245,158,11,.14); color: #a16207; }
        .status-Menunggu-ACC-Kaur { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Disetujui-Menunggu-Pengambilan- { background: rgba(25,135,84,.12); color: #198754; }
        .status-Ditolak { background: rgba(220,53,69,.12); color: #dc3545; }
        .asset-list { max-width: 320px; }
        .asset-item { display: flex; justify-content: space-between; gap: 12px; padding: 3px 0; border-bottom: 1px solid #f0f1f3; font-size: .86rem; }
        .asset-item:last-child { border-bottom: 0; }
        .action-cell { min-width: 260px; }
        .action-grid { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .empty-state { min-height: 280px; display: grid; place-items: center; text-align: center; color: #6c757d; }
        .approval-stepper { display:grid; grid-template-columns:repeat(6,minmax(88px,1fr)); gap:6px; min-width:590px; }
        .approval-step { padding:7px 8px; border:1px solid #dfe3e7; border-radius:8px; background:#f7f8f9; color:#7b848d; font-size:.65rem; font-weight:700; text-align:center; }
        .approval-step.is-done { color:#146c43; border-color:#a3cfbb; background:#eaf7f0; }
        .approval-step.is-active { color:#9a450e; border-color:#ea5b1a; background:#fff2e9; box-shadow:inset 0 0 0 1px #ea5b1a; }
        .approval-evidence-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-width:132px; min-height:36px; padding:7px 12px; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn:not(.notif-bell) { flex: 1 1 calc(50% - 8px); }
            .approval-table { min-width: 980px; }
            .soft-badge { min-width: 220px; max-width: 220px; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-patch-check me-2 text-warning"></i>Pengecekan Laboran</div>
                    <div class="small text-white-50">Tahap setelah ACC Kaprodi: cek stok fisik lalu teruskan ke Kaur</div>
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
                    <a href="<?= base_url('index.php/admin/peminjaman/export_pengajuan_acc') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview ACC</a>
                    <a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a>
                    <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-list-check me-1"></i> Peminjaman</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h1 class="h5 fw-bold mb-1">Daftar Pengajuan Menunggu Pengecekan</h1>
                    <div class="text-muted small">Kaprodi sudah menyetujui. Laboran mengecek ketersediaan fisik sebelum meneruskan ke Kaur.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-warning text-dark px-3 py-2"><?= count($pengajuan) ?> menunggu</span>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
                </div>
            </div>
        </section>

        <section class="panel-card p-3 mb-3">
            <form method="get" action="<?= base_url('index.php/admin/approval') ?>" class="row g-2 align-items-end">
                <div class="col-md-9"><label for="approvalSearch" class="form-label small fw-semibold text-muted">Cari approval</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input id="approvalSearch" type="search" name="q" class="form-control" value="<?= html_escape($q ?? '') ?>" placeholder="Nama peminjam, nama barang, atau status"></div></div>
                <div class="col-md-3 d-grid"><button class="btn btn-fik"><i class="bi bi-search me-1"></i> Cari</button></div>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <?php if(empty($pengajuan)): ?>
                <div class="empty-state p-5">
                    <div>
                        <i class="bi bi-check-circle display-5 d-block mb-3 text-success"></i>
                        <h2 class="h5 fw-bold mb-1">Tidak ada pengajuan menunggu</h2>
                        <p class="mb-0">Semua pengajuan peminjaman sudah diproses.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table table-hover approval-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">No</th>
                                <th>Peminjam</th>
                                <th>Barang Diajukan</th>
                                <th>Masa Pinjam</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pengajuan as $index => $p): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-muted"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="fw-bold"><?= html_escape($p->nama_peminjam ?? '-') ?></div>
                                        <div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <div class="asset-list">
                                            <?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?>
                                                <div class="asset-item">
                                                    <span><?= html_escape($d->nama_aset ?? '-') ?></span>
                                                    <strong>Jumlah: <?= (int)($d->jumlah_pinjam ?? 0) ?> unit</strong>
                                                </div>
                                            <?php endforeach; else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span tabindex="0" data-bs-toggle="tooltip" title="<?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>"><div class="small"><i class="bi bi-box-arrow-in-right text-success me-1"></i><?= tanggal_indonesia($p->tanggal_pinjam ?? null) ?></div><div class="small text-muted"><i class="bi bi-box-arrow-left text-danger me-1"></i><?= tanggal_indonesia($p->tanggal_kembali_rencana ?? null) ?></div></span>
                                    </td>
                                    <td>
                                        <div class="small" style="max-width: 260px; white-space: normal;"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div>
                                    </td>
                                    <td><div class="overflow-auto pb-1"><div class="approval-stepper"><span class="approval-step is-done"><i class="bi bi-check2 me-1"></i>Diajukan</span><span class="approval-step is-done"><i class="bi bi-check2 me-1"></i>Kaprodi</span><span class="approval-step is-active">Laboran</span><span class="approval-step">Kaur</span><span class="approval-step">Final QR</span><span class="approval-step">Selesai</span></div></div><span class="soft-badge <?= $status_class($p->status ?? '') ?> mt-2 d-inline-flex"><?= html_escape($p->status ?? '-') ?></span></td>
                                    <td class="text-end pe-3 action-cell">
                                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2"><?php if(!empty($p->foto_bukti)): ?><button type="button" class="btn btn-sm btn-outline-secondary approval-evidence-btn" data-bs-toggle="modal" data-bs-target="#evidenceModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-image" aria-hidden="true"></i><span>Bukti kondisi</span></button><?php endif; ?><button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#processModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-sliders me-1"></i> Proses</button></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php foreach($pengajuan as $p): ?>
        <div class="modal fade" id="processModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Aksi Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="small text-muted">Peminjam</div>
                            <div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?> - <?= html_escape($p->nim_nip ?? '-') ?></div>
                        </div>
                        <label class="form-label small fw-semibold">Catatan Laboran</label>
                        <textarea name="catatan_laboran" class="form-control" rows="3" placeholder="Catatan pengecekan stok atau alasan penolakan."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button formaction="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-4" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                        <button formaction="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-4" onclick="return confirm('Teruskan pengajuan ini ke Kaur?')"><i class="bi bi-send-check me-1"></i> Teruskan ke Kaur</button>
                    </div>
                </form>
            </div>
        </div>
        <?php if(!empty($p->foto_bukti)): ?><?php $approval_evidence_url = scm_upload_url($p->foto_bukti, 'assets/uploads/bukti_peminjaman'); $approval_evidence_exists = scm_upload_exists($p->foto_bukti, 'assets/uploads/bukti_peminjaman'); ?><div class="modal fade" id="evidenceModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold">Bukti Kondisi Awal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body text-center bg-light"><?php if($approval_evidence_exists): ?><img class="img-fluid rounded-3" style="max-height:70vh;object-fit:contain" src="<?= html_escape($approval_evidence_url) ?>" alt="Bukti kondisi awal"><?php else: ?><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File bukti kondisi tidak ditemukan di penyimpanan.</div><?php endif; ?></div></div></div></div><?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));</script>
</body>
</html>
