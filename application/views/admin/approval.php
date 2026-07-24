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
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 700; font-size: .75rem; white-space: nowrap; }
        .status-Menunggu-Persetujuan,
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
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn:not(.notif-bell) { flex: 1 1 calc(50% - 8px); }
            .approval-table { min-width: 980px; }
        }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-patch-check me-2 text-warning"></i>Pengecekan Laboran</div>
                    <div class="small text-white-50">Cek stok fisik lalu teruskan pengajuan ke Kaur</div>
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
                                <a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>">
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
                    <div class="text-muted small">Laboran hanya mengecek ketersediaan fisik. Jika aman, teruskan ke Kaur untuk ACC.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-warning text-dark px-3 py-2"><?= count($pengajuan) ?> menunggu</span>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
                </div>
            </div>
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
                                <th>Jadwal</th>
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
                                                    <strong><?= (int)($d->jumlah_pinjam ?? 0) ?> unit</strong>
                                                </div>
                                            <?php endforeach; else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small"><i class="bi bi-box-arrow-in-right text-success me-1"></i><?= html_escape($p->tanggal_pinjam ?? '-') ?></div>
                                        <div class="small text-muted"><i class="bi bi-box-arrow-left text-danger me-1"></i><?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></div>
                                    </td>
                                    <td>
                                        <div class="small" style="max-width: 260px; white-space: normal;"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div>
                                    </td>
                                    <td><span class="soft-badge <?= $status_class($p->status ?? '') ?>"><?= html_escape($p->status ?? '-') ?></span></td>
                                    <td class="text-end pe-3 action-cell">
                                        <div class="action-grid">
                                            <form method="post" action="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>">
                                                <input type="hidden" name="catatan_laboran" value="Stok fisik tersedia, diteruskan ke Kaur">
                                                <button class="btn btn-success btn-sm rounded-pill w-100" onclick="return confirm('Teruskan pengajuan ini ke Kaur? Stok belum dikurangi.')">
                                                    <i class="bi bi-send-check me-1"></i> Teruskan
                                                </button>
                                            </form>
                                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int)$p->id_peminjaman ?>">
                                                Tolak
                                            </button>
                                        </div>
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
        <div class="modal fade" id="rejectModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Tolak Pengajuan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="small text-muted">Peminjam</div>
                            <div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?> - <?= html_escape($p->nim_nip ?? '-') ?></div>
                        </div>
                        <label class="form-label small fw-semibold">Catatan Penolakan</label>
                        <textarea name="catatan_laboran" class="form-control" rows="3" placeholder="Contoh: stok fisik belum tersedia atau alat sedang maintenance." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-outline-danger rounded-pill px-4" onclick="return confirm('Tolak pengajuan ini?')">Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
