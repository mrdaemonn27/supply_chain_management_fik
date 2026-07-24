<?php
$status_options = isset($status_options) && is_array($status_options) ? $status_options : ['', 'Sedang Dipinjam', 'Dipinjam', 'Terlambat'];
$status_class = function ($status) { return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Sedang Dipinjam')); };
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($peminjaman ?? []), 'per_page' => 10];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Data Pengembalian') ?></title>
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
        .status-Sedang-Dipinjam, .status-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Terlambat { background: rgba(220,53,69,.12); color: #dc3545; }
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
                    <div class="fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Data Pengembalian</div>
                    <div class="small text-white-50">Transaksi aktif yang sedang dipinjam dan siap divalidasi saat kembali</div>
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
                    <a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-clipboard-data me-1"></i> Peminjaman</a>
                    <a href="<?= base_url('index.php/admin/pengembalian/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-qr-code-scan me-1"></i> Scanner</a>
                    <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

        <section class="panel-card p-3 p-lg-4 mb-4">
            <form class="row g-3 align-items-end" method="get" action="<?= base_url('index.php/admin/pengembalian') ?>">
                <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Pencarian</label><input type="text" name="q" class="form-control" value="<?= html_escape($filters['pencarian'] ?? '') ?>" placeholder="Nama, NIM/NIP, atau keperluan"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold text-muted">Status</label><select name="status" class="form-select"><?php foreach($status_options as $status): ?><option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?: 'Semua Aktif' ?></option><?php endforeach; ?></select></div>
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
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada transaksi aktif untuk pengembalian.</td></tr>
                    <?php else: foreach($peminjaman as $p): ?>
                        <?php $is_late = !empty($p->tanggal_kembali_rencana) && strtotime($p->tanggal_kembali_rencana) < strtotime(date('Y-m-d')); ?>
                        <tr>
                            <td class="ps-3"><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="fw-semibold"><?= (int)($p->total_jenis ?? 1) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="small text-muted"><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><?= html_escape($d->nama_aset) ?> (<?= (int)$d->jumlah_pinjam ?>), <?php endforeach; else: ?>- <?php endif; ?></div></td>
                            <td><div><?= html_escape($p->tanggal_pinjam ?? '-') ?></div><div class="small <?= $is_late ? 'text-danger fw-semibold' : 'text-muted' ?>">s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?><?= $is_late ? ' - Terlambat' : '' ?></div></td>
                            <td><span class="soft-badge <?= $is_late ? 'status-Terlambat' : $status_class($p->status ?? '') ?>"><?= $is_late ? 'Terlambat' : html_escape($p->status ?? '-') ?></span></td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-success rounded-pill" data-bs-toggle="modal" data-bs-target="#returnModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-arrow-counterclockwise me-1"></i> Terima</button>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#blockModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-shield-lock me-1"></i> Blokir</button>
                                </div>
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
                    <nav aria-label="Pagination pengembalian">
                        <ul class="pagination pagination-sm mb-0">
                            <?php $prev_query = http_build_query(array_merge($base_query, ['page' => max(1, $page - 1)])); ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($prev_query ? '?'.$prev_query : '')) ?>">Prev</a></li>
                            <?php for($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php $next_query = http_build_query(array_merge($base_query, ['page' => min($total_pages, $page + 1)])); ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($next_query ? '?'.$next_query : '')) ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php if(!empty($peminjaman)): foreach($peminjaman as $p): ?>
        <div class="modal fade" id="returnModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/kembalikan/'.$p->id_peminjaman) ?>">
                    <input type="hidden" name="return_to" value="admin/pengembalian">
                    <div class="modal-header"><h5 class="modal-title fw-bold">Terima Pengembalian</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><div class="small text-muted">Peminjam</div><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div></div>
                        <label class="form-label small fw-semibold">Kondisi Saat Kembali</label>
                        <select name="kondisi_saat_kembali" class="form-select mb-3 return-condition" required>
                            <option value="Baik">Baik</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Hilang">Hilang</option>
                        </select>
                        <label class="form-label small fw-semibold">Catatan Pengembalian</label>
                        <textarea name="catatan_pengembalian" class="form-control return-note" rows="3" placeholder="Catatan kondisi barang atau kelengkapan. Wajib untuk Rusak/Hilang."></textarea>
                        <label class="form-label small fw-semibold mt-3">Evidence Pengembalian</label>
                        <input type="file" name="foto_pengembalian" class="form-control return-file" accept=".jpg,.jpeg,.png,.pdf,image/*">
                        <label class="form-label small fw-semibold mt-2">Ambil Foto Kamera HP</label>
                        <input type="file" name="foto_pengembalian_camera" class="form-control return-file" accept="image/*" capture="environment">
                        <div class="small text-muted mt-1">Evidence wajib untuk Rusak/Hilang. Maksimal 5MB.</div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button class="btn btn-success rounded-pill px-4" onclick="return confirm('Konfirmasi barang sudah diterima kembali?')">Terima Pengembalian</button></div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="blockModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= base_url('index.php/admin/blokir/simpan') ?>">
                    <input type="hidden" name="return_to" value="admin/pengembalian">
                    <div class="modal-header"><h5 class="modal-title fw-bold">Blokir Pengguna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">NIM/NIP</label>
                                <input type="text" name="nim_nip" class="form-control" value="<?= html_escape($p->nim_nip ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nama</label>
                                <input type="text" name="nama_peminjam" class="form-control" value="<?= html_escape($p->nama_peminjam ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tanggal Blokir</label>
                                <input type="date" name="tanggal_blokir" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Batas Blokir</label>
                                <input type="date" name="batas_blokir" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Alasan</label>
                                <textarea name="alasan" class="form-control" rows="3" placeholder="Contoh: Terlambat mengembalikan barang melewati jadwal." required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger rounded-pill px-4" onclick="return confirm('Blokir pengguna ini?')">Simpan Blokir</button></div>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.modal form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const condition = form.querySelector('.return-condition')?.value;
                if (!condition) return;
                const note = form.querySelector('.return-note')?.value.trim();
                const hasFile = Array.from(form.querySelectorAll('.return-file')).some((input) => input.files && input.files.length);
                if ((condition === 'Rusak' || condition === 'Hilang') && (!note || !hasFile)) {
                    event.preventDefault();
                    alert('Untuk kondisi Rusak atau Hilang, catatan dan evidence wajib diisi.');
                }
            });
        });
    </script>
</body>
</html>
