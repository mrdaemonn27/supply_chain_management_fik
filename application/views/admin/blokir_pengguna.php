<?php
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$blokir = isset($blokir) && is_array($blokir) ? $blokir : [];
$peminjam_options = isset($peminjam_options) && is_array($peminjam_options) ? $peminjam_options : [];
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
        body { background: #f5f6f8; font-family: 'Poppins', sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; border-bottom: 4px solid #ea5b1a; color: #fff; }
        .panel-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(32,33,36,.05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234,91,26,.16); }
        .table thead th { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table td { vertical-align: middle; }
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 700; font-size: .75rem; }
        .status-aktif { background: rgba(220,53,69,.12); color: #dc3545; }
        .status-dibuka { background: rgba(25,135,84,.12); color: #198754; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        @media (max-width: 767.98px) { .topbar-actions { width: 100%; flex-wrap: wrap; } .topbar-actions .btn:not(.notif-bell) { flex: 1; } }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Blokir Pengguna</div>
                    <div class="small text-white-50">Kelola pembatasan pengajuan peminjaman dan histori pembukaan blokir</div>
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
                    <a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i> Pengembalian</a>
                    <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <section class="panel-card p-3 p-lg-4 h-100">
                    <h1 class="h5 fw-bold mb-3">Tambah Blokir</h1>
                    <form method="post" action="<?= base_url('index.php/admin/blokir/simpan') ?>">
                        <label class="form-label small fw-semibold">NIM/NIP</label>
                        <input type="text" name="nim_nip" class="form-control mb-3" list="peminjamList" placeholder="Masukkan NIM/NIP peminjam" required>
                        <datalist id="peminjamList">
                            <?php foreach($peminjam_options as $p): ?>
                                <option value="<?= html_escape($p->nim_nip ?? '') ?>"><?= html_escape($p->nama_peminjam ?? '') ?></option>
                            <?php endforeach; ?>
                        </datalist>
                        <label class="form-label small fw-semibold">Nama Peminjam</label>
                        <input type="text" name="nama_peminjam" class="form-control mb-3" placeholder="Opsional, akan diisi otomatis bila user ditemukan">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-12 col-xl-6">
                                <label class="form-label small fw-semibold">Tanggal Blokir</label>
                                <input type="date" name="tanggal_blokir" class="form-control mb-3" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 col-lg-12 col-xl-6">
                                <label class="form-label small fw-semibold">Batas Blokir</label>
                                <input type="date" name="batas_blokir" class="form-control mb-3">
                            </div>
                        </div>
                        <label class="form-label small fw-semibold">Alasan</label>
                        <textarea name="alasan" class="form-control mb-3" rows="4" placeholder="Contoh: Barang dikembalikan dalam kondisi rusak." required></textarea>
                        <button class="btn btn-danger w-100 rounded-pill" onclick="return confirm('Simpan blokir pengguna ini?')"><i class="bi bi-shield-lock me-1"></i> Simpan Blokir</button>
                    </form>
                </section>
            </div>

            <div class="col-lg-8">
                <section class="panel-card p-3 p-lg-4 mb-4">
                    <form class="row g-3 align-items-end" method="get" action="<?= base_url('index.php/admin/blokir') ?>">
                        <div class="col-md-5"><label class="form-label small fw-semibold text-muted">Pencarian</label><input type="text" name="q" class="form-control" value="<?= html_escape($filters['pencarian'] ?? '') ?>" placeholder="Nama, NIM/NIP, atau alasan"></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Status</label><select name="status" class="form-select"><option value="">Semua</option><option value="Aktif" <?= (($filters['status'] ?? '') === 'Aktif') ? 'selected' : '' ?>>Aktif</option><option value="Dibuka" <?= (($filters['status'] ?? '') === 'Dibuka') ? 'selected' : '' ?>>Dibuka</option></select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?= html_escape($filters['tanggal'] ?? '') ?>"></div>
                        <div class="col-md-1 d-grid"><button class="btn btn-fik"><i class="bi bi-search"></i></button></div>
                    </form>
                </section>

                <section class="panel-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th class="ps-3">Pengguna</th><th>Periode</th><th>Alasan</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead>
                            <tbody>
                            <?php if(empty($blokir)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada data blokir pengguna.</td></tr>
                            <?php else: foreach($blokir as $b): ?>
                                <tr>
                                    <td class="ps-3"><div class="fw-semibold"><?= html_escape($b->nama_peminjam ?: '-') ?></div><div class="small text-muted"><?= html_escape($b->nim_nip ?? '-') ?></div></td>
                                    <td><div><?= html_escape($b->tanggal_blokir ?? '-') ?></div><div class="small text-muted">s.d. <?= html_escape($b->batas_blokir ?: 'Tanpa batas') ?></div></td>
                                    <td class="small"><?= html_escape($b->alasan ?? '-') ?><?php if(!empty($b->catatan_buka)): ?><div class="text-muted mt-1">Buka blokir: <?= html_escape($b->catatan_buka) ?></div><?php endif; ?></td>
                                    <td><span class="soft-badge <?= strtolower((string)$b->status) === 'aktif' ? 'status-aktif' : 'status-dibuka' ?>"><?= html_escape($b->status ?? '-') ?></span></td>
                                    <td class="text-end pe-3">
                                        <?php if(($b->status ?? '') === 'Aktif'): ?>
                                            <button class="btn btn-sm btn-outline-success rounded-pill" data-bs-toggle="modal" data-bs-target="#openBlockModal<?= (int)$b->id_blokir ?>"><i class="bi bi-unlock me-1"></i> Buka</button>
                                        <?php else: ?>
                                            <span class="small text-muted"><?= html_escape($b->dibuka_pada ?: '-') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

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
</body>
</html>
