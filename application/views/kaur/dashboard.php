<?php
function rp_kaur($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
function num_kaur($value) {
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
}
function status_class_kaur($status) {
    $map = [
        'Pengajuan' => 'status-pengajuan',
        'Revisi' => 'status-revisi',
        'Negosiasi' => 'status-negosiasi',
        'Sedang Negosiasi' => 'status-negosiasi',
        'Deal' => 'status-deal',
        'Disetujui' => 'status-approval',
        'Disetujui (Menunggu Finalisasi QR)' => 'status-Disetujui-Menunggu-Finalisasi-QR-',
        'Approval' => 'status-approval',
        'BAST' => 'status-bast',
        'Inventarisasi' => 'status-inventory',
        'Selesai' => 'status-selesai',
        'Ditolak' => 'status-ditolak',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function query_kaur($filters, $page) {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    return http_build_query($params);
}
$filters = $filters ?? [];
$stats = $stats ?? ['pengajuan' => 0, 'negosiasi' => 0, 'deal' => 0, 'bast' => 0, 'laporan_deal' => 0];
$anggaran = $anggaran ?? ['tahun' => date('Y'), 'total_anggaran' => 0, 'total_pengeluaran' => 0, 'sisa_anggaran' => 0, 'persentase_penggunaan' => 0, 'catatan' => null];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan_kaprodi ?? []);
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$active_module = $active_module ?? 'overview';
$module_meta = [
    'overview' => ['title' => 'Dashboard Kaur Laboratorium', 'desc' => 'Pilih fitur operasional Kaur Laboratorium dari panel berikut.'],
    'pengajuan' => ['title' => 'Pengajuan Kaprodi', 'desc' => 'Pantau seluruh kebutuhan barang dan jasa dari prodi.'],
    'negosiasi' => ['title' => 'Negosiasi Pengadaan', 'desc' => 'Pilih vendor dan simpan riwayat harga negosiasi.'],
    'approval' => ['title' => 'Approval Pengadaan', 'desc' => 'Setujui, revisi, atau tolak pengajuan setelah negosiasi selesai.'],
    'peminjaman' => ['title' => 'ACC Peminjaman', 'desc' => 'Setujui peminjaman yang sudah diverifikasi Laboran agar QR aktif.'],
    'anggaran' => ['title' => 'Alokasi Anggaran', 'desc' => 'Kelola total anggaran, pengeluaran, sisa, dan persentase penggunaan.'],
    'bast' => ['title' => 'BAST', 'desc' => 'Input dokumen BAST dari Logistik dan proses barang ke inventory.'],
    'laporan' => ['title' => 'Laporan', 'desc' => 'Lihat hasil akhir negosiasi yang sudah Deal.'],
];
$module = $module_meta[$active_module] ?? $module_meta['overview'];
$is_overview = $active_module === 'overview';
function kaur_module_url($module) {
    return base_url('index.php/kaur/dashboard' . ($module === 'overview' ? '' : '/' . $module));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Kaur Laboratorium') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .brand-mark { width: 42px; height: 42px; border-radius: 8px; background: rgba(234, 91, 26, .16); color: #ea5b1a; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .panel-card { background: #fff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 8px 22px rgba(32, 33, 36, .05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234, 91, 26, .16); }
        .summary-card { min-height: 96px; padding: 18px; }
        .summary-card .value { font-weight: 700; font-size: 1.5rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .quick-link { border: 1px solid #e8eaed; border-radius: 8px; color: #202124; text-decoration: none; background: #fff; transition: .18s ease; min-height: 74px; }
        .quick-link:hover { transform: translateY(-2px); border-color: rgba(234, 91, 26, .35); color: #202124; }
        .quick-icon { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234, 91, 26, .12); color: #c24a13; }
        .module-strip { position: sticky; top: 78px; z-index: 15; background: rgba(245, 246, 248, .96); backdrop-filter: blur(8px); border-bottom: 1px solid #e8eaed; }
        .module-strip .nav { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 2px; }
        .module-strip .nav-link { color: #495057; white-space: nowrap; border-radius: 999px; font-size: .86rem; font-weight: 600; }
        .module-strip .nav-link.active { background: #ea5b1a; color: #fff; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi, .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-Disetujui-Menunggu-Finalisasi-QR- { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .section-anchor { scroll-margin-top: 92px; }
        .item-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; }
        .mini-label { font-size: .74rem; color: #6c757d; font-weight: 600; }
        .progress { height: 10px; border-radius: 999px; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn { flex: 1 1 auto; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
            .module-strip { top: 126px; }
        }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-diagram-3"></i></span>
                    <div>
                        <div class="fw-bold">Panel Kaur Laboratorium</div>
                        <div class="small text-white-50">Pengajuan, negosiasi, approval, BAST, dan laporan</div>
                    </div>
                </div>
                <div class="topbar-actions d-flex align-items-center gap-2">
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
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="module-strip">
        <div class="container-fluid px-3 px-lg-4 py-2">
            <nav class="nav gap-2" aria-label="Navigasi fitur Kaur">
                <?php foreach (['overview' => 'Panel', 'pengajuan' => 'Pengajuan', 'negosiasi' => 'Negosiasi', 'approval' => 'Approval', 'peminjaman' => 'ACC Peminjaman', 'anggaran' => 'Alokasi Anggaran', 'bast' => 'BAST', 'laporan' => 'Laporan'] as $key => $label): ?>
                    <a class="nav-link <?= $active_module === $key ? 'active' : '' ?>" href="<?= kaur_module_url($key) ?>"><?= html_escape($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= html_escape($module['title']) ?></h1>
                <p class="text-muted mb-0"><?= html_escape($module['desc']) ?></p>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <?php if (!$is_overview): ?>
                    <a href="<?= kaur_module_url('overview') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-grid me-1"></i> Panel Kaur</a>
                <?php endif; ?>
                <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
            </div>
        </div>

        <?php if ($is_overview): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-2"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['pengajuan'] ?></div><div class="label">Pengajuan</div></div></div>
            <div class="col-6 col-xl-2"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['negosiasi'] ?></div><div class="label">Negosiasi</div></div></div>
            <div class="col-6 col-xl-2"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['deal'] ?></div><div class="label">Deal</div></div></div>
            <div class="col-6 col-xl-2"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['bast'] ?></div><div class="label">BAST</div></div></div>
            <div class="col-12 col-xl-4"><div class="panel-card summary-card"><div class="value"><?= rp_kaur($anggaran['sisa_anggaran']) ?></div><div class="label">Sisa anggaran <?= (int) $anggaran['tahun'] ?></div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <?php
            $menus = [
                ['id' => 'pengajuan', 'icon' => 'bi-inboxes', 'title' => 'Pengajuan', 'desc' => 'Pantau semua kebutuhan prodi.'],
                ['id' => 'negosiasi', 'icon' => 'bi-chat-square-text', 'title' => 'Negosiasi', 'desc' => 'Pilih vendor dan catat histori harga.'],
                ['id' => 'approval', 'icon' => 'bi-patch-check', 'title' => 'Approval', 'desc' => 'Setujui pengadaan yang sudah Deal.'],
                ['id' => 'peminjaman', 'icon' => 'bi-qr-code-scan', 'title' => 'ACC Peminjaman', 'desc' => 'Aktifkan QR peminjam.'],
                ['id' => 'anggaran', 'icon' => 'bi-cash-coin', 'title' => 'Alokasi Anggaran', 'desc' => 'Kelola total, pengeluaran, dan sisa.'],
                ['id' => 'bast', 'icon' => 'bi-file-earmark-pdf', 'title' => 'BAST', 'desc' => 'Input dokumen dari Logistik.'],
                ['id' => 'laporan', 'icon' => 'bi-file-earmark-spreadsheet', 'title' => 'Laporan', 'desc' => 'Hasil akhir negosiasi Deal.'],
            ];
            foreach ($menus as $menu): ?>
                <div class="col-md-6 col-xl-2">
                    <a class="quick-link d-flex align-items-center gap-3 p-3 h-100" href="<?= kaur_module_url($menu['id']) ?>">
                        <span class="quick-icon"><i class="bi <?= $menu['icon'] ?>"></i></span>
                        <span><span class="fw-bold d-block"><?= $menu['title'] ?></span><span class="small text-muted"><?= $menu['desc'] ?></span></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($active_module === 'peminjaman'): ?>
        <section id="approval-peminjaman" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Approval Peminjaman oleh Kaur</h2>
                    <div class="text-muted small">Pengajuan yang sudah dicek Laboran akan muncul di sini. Setelah disetujui, QR Code tampil di akun peminjam.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-start">
                    <a href="<?= base_url('index.php/kaur/peminjaman/export_pengajuan_acc') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel ACC</a>
                    <span class="badge text-bg-warning align-self-start"><?= count($peminjaman_pending_kaur ?? []) ?> menunggu ACC</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>No. Peminjaman</th><th>Nama Peminjam</th><th>Barang</th><th>Laboratorium</th><th>Tanggal Pinjam</th><th>Tanggal Kembali</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman_pending_kaur)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Tidak ada peminjaman yang menunggu ACC Kaur.</td></tr>
                    <?php else: foreach($peminjaman_pending_kaur as $p): ?>
                        <?php
                            $barang_names = [];
                            $labs = [];
                            foreach (($p->detail_barang ?? []) as $d) {
                                $barang_names[] = ($d->nama_aset ?? '-') . ' (' . (int)($d->jumlah_pinjam ?? 0) . ')';
                                if (!empty($d->nama_ruangan)) { $labs[] = $d->nama_ruangan; }
                            }
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= html_escape($p->group_id ?: $p->id_peminjaman) ?></td>
                            <td><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><?= html_escape(implode(', ', $barang_names) ?: '-') ?></td>
                            <td><?= html_escape(implode(', ', array_unique($labs)) ?: '-') ?></td>
                            <td><?= html_escape($p->tanggal_pinjam ?? '-') ?></td>
                            <td><?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></td>
                            <td><span class="status-pill status-negosiasi"><?= html_escape($p->status ?? '-') ?></span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#loanApprovalModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-eye me-1"></i> Detail</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php foreach(($peminjaman_pending_kaur ?? []) as $p): ?>
            <div class="modal fade" id="loanApprovalModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->group_id ?: $p->id_peminjaman) ?> - <?= html_escape($p->nama_peminjam ?? '-') ?></h5>
                                <div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?> · <?= html_escape($p->tanggal_pinjam ?? '-') ?> s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><div class="mini-label">Keperluan</div><div><?= html_escape($p->keperluan ?? '-') ?></div></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light"><tr><th>Barang</th><th>Kode</th><th>Laboratorium</th><th class="text-end">Jumlah</th></tr></thead>
                                    <tbody>
                                    <?php foreach(($p->detail_barang ?? []) as $d): ?>
                                        <tr>
                                            <td><?= html_escape($d->nama_aset ?? '-') ?></td>
                                            <td><?= html_escape($d->kode_aset ?? '-') ?></td>
                                            <td><?= html_escape($d->nama_ruangan ?? '-') ?></td>
                                            <td class="text-end"><?= (int)($d->jumlah_pinjam ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label small fw-semibold">Catatan ACC Kaur</label>
                            <textarea name="catatan_kaur" class="form-control" rows="3" placeholder="Catatan persetujuan atau alasan penolakan."></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak peminjaman ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-3" onclick="return confirm('Setujui peminjaman ini? QR akan menunggu finalisasi Laboran.')"><i class="bi bi-check2-circle me-1"></i> Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_module === 'pengajuan'): ?>
        <section id="pengajuan" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pengajuan Kaprodi</h2>
                    <div class="text-muted small">Data dapat dicari berdasarkan tanggal, jenis, status, dan kata kunci.</div>
                </div>
                <a href="<?= base_url('index.php/kaur/pengajuan/export_pengajuan_acc?' . query_kaur($filters, 1)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Export Pengajuan ACC</a>
            </div>
            <form method="get" action="<?= kaur_module_url('pengajuan') ?>" class="row g-2 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kata Kunci</label>
                    <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, barang">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="jenis_pengajuan" class="form-select">
                        <option value="">Semua</option>
                        <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                        <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach (['Pengajuan','Revisi','Sedang Negosiasi','Deal','Disetujui','Approval','BAST','Selesai','Ditolak'] as $status): ?>
                            <option value="<?= $status ?>" <?= (($filters['status'] ?? '') === $status) ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>"></div>
                <div class="col-md-1 d-grid"><button class="btn btn-fik"><i class="bi bi-search"></i></button></div>
            </form>

            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>Kode</th><th>Prodi</th><th>Jenis</th><th>Kebutuhan</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($pengajuan_kaprodi)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">Belum ada pengajuan sesuai filter.</td></tr>
                        <?php else: foreach ($pengajuan_kaprodi as $p): ?>
                            <tr>
                                <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                <td><div class="fw-semibold"><?= html_escape($p->nama_prodi) ?></div><div class="small text-muted"><?= html_escape($p->nama_pengajuan) ?></div></td>
                                <td><span class="badge text-bg-light border"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                <td style="min-width: 280px;">
                                    <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                    <?php foreach (($p->items ?? []) as $item): ?>
                                        <div class="small"><i class="bi bi-dot"></i><?= html_escape($item->uraian_barang) ?> - <?= num_kaur($item->vol) ?> <?= html_escape($item->satuan) ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td><span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                <td class="text-end"><a href="<?= base_url('index.php/kaur/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-2 border-top">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'negosiasi'): ?>
        <section id="negosiasi" class="section-anchor mb-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Negosiasi oleh Kaur</h2>
                    <div class="text-muted small">Setiap simpan akan menjadi riwayat baru. Kaprodi hanya melihat status sampai hasil Deal.</div>
                </div>
            </div>
            <div class="panel-card p-3 p-lg-4 mb-3">
                <form method="get" action="<?= kaur_module_url('negosiasi') ?>" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Kata Kunci</label>
                        <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, item">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status Negosiasi</label>
                        <select name="status_negosiasi" class="form-select">
                            <option value="">Semua</option>
                            <?php foreach (['Belum Negosiasi','Sedang Negosiasi','Deal','Ditolak'] as $s): ?>
                                <option value="<?= $s ?>" <?= (($filters['status_negosiasi'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Vendor</label>
                        <input type="text" name="vendor" class="form-control" value="<?= html_escape($filters['vendor'] ?? '') ?>" placeholder="Nama vendor">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Jenis</label>
                        <select name="jenis_pengajuan" class="form-select">
                            <option value="">Semua</option>
                            <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                            <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label small fw-semibold">Dari</label><input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>"></div>
                    <div class="col-md-1"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-fik"><i class="bi bi-search"></i></button></div>
                </form>
            </div>
            <div class="vstack gap-3">
                <?php if (empty($pengajuan_kaprodi)): ?>
                    <div class="panel-card p-4 text-center text-muted">Belum ada data untuk dinegosiasikan.</div>
                <?php else: foreach ($pengajuan_kaprodi as $p): ?>
                    <div class="panel-card p-3 p-lg-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                            <div>
                                <div class="fw-bold"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?></div>
                                <div class="small text-muted"><?= html_escape($p->nama_prodi) ?> - <?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></div>
                            </div>
                            <span class="status-pill <?= status_class_kaur($p->status) ?> align-self-start"><?= html_escape($p->status) ?></span>
                        </div>
                        <div class="row g-3">
                            <?php foreach (($p->items ?? []) as $item): $latest = $item->latest_negosiasi ?? null; ?>
                                <div class="col-12">
                                    <form class="item-card p-3" method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_negosiasi/'.$p->id_pengajuan.'/'.$item->id_item) ?>">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-lg-3">
                                                <div class="mini-label">Item</div>
                                                <div class="fw-semibold"><?= html_escape($item->uraian_barang) ?></div>
                                                <div class="small text-muted">Vol awal <?= num_kaur($item->vol) ?> <?= html_escape($item->satuan) ?></div>
                                            </div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Vendor</label><input type="text" name="vendor" class="form-control" value="<?= html_escape($latest->vendor ?? '') ?>" required></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Harga Awal</label><input type="text" name="harga_awal" class="form-control money-input" value="<?= $latest ? rp_kaur($latest->harga_awal) : '' ?>" required></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Harga Akhir</label><input type="text" name="harga_negosiasi" class="form-control money-input" value="<?= $latest ? rp_kaur($latest->harga_negosiasi) : '' ?>" required></div>
                                            <div class="col-md-6 col-lg-1"><label class="form-label small fw-semibold">Vol</label><input type="number" name="volume_negosiasi" class="form-control" min="1" step="1" value="<?= html_escape($latest->volume_negosiasi ?? $item->vol) ?>" required></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Garansi</label><input type="text" name="garansi" class="form-control" value="<?= html_escape($latest->garansi ?? '') ?>" placeholder="Contoh: 1 tahun"></div>
                                            <div class="col-md-6 col-lg-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><?php foreach (['Belum Negosiasi','Sedang Negosiasi','Deal','Ditolak'] as $s): ?><option value="<?= $s ?>" <?= (($latest->status ?? 'Belum Negosiasi') === $s) ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
                                            <div class="col-lg-8"><label class="form-label small fw-semibold">Catatan</label><input type="text" name="catatan" class="form-control" value="<?= html_escape($latest->catatan ?? '') ?>" placeholder="Catatan hasil negosiasi"></div>
                                            <div class="col-lg-2 d-grid"><button class="btn btn-fik"><i class="bi bi-save me-1"></i> Simpan</button></div>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-3">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'approval'): ?>
        <section id="approval" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <h2 class="h5 fw-bold mb-1">Approval Kaur</h2>
            <div class="text-muted small mb-3">Kaur dapat menyetujui, meminta revisi, atau menolak pengajuan sesuai kebutuhan proses bisnis.</div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>No. Pengajuan</th><th>Tanggal</th><th>Program Studi</th><th>Jenis</th><th>Vendor</th><th>Total Harga</th><th>Status Negosiasi</th><th>Status Approval</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                    <?php if (empty($pengajuan_kaprodi)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">Belum ada pengajuan untuk approval.</td></tr>
                    <?php else: foreach (($pengajuan_kaprodi ?? []) as $p): ?>
                        <?php
                            $vendors = [];
                            $nego_statuses = [];
                            $can_approve = !empty($p->items);
                            foreach (($p->items ?? []) as $approval_item) {
                                $latest = $approval_item->latest_negosiasi ?? null;
                                if ($latest) {
                                    if (!empty($latest->vendor)) { $vendors[] = $latest->vendor; }
                                    $nego_statuses[] = $latest->status;
                                }
                                if (!$latest || $latest->status !== 'Deal') { $can_approve = false; }
                            }
                            $vendor_label = $vendors ? implode(', ', array_unique($vendors)) : '-';
                            $nego_label = $nego_statuses ? implode(', ', array_unique($nego_statuses)) : 'Belum Negosiasi';
                            $total_harga = ($p->summary['total_negosiasi'] ?? 0) > 0 ? $p->summary['total_negosiasi'] : ($p->summary['total_setelah_pajak'] ?? $p->summary['total_penawaran'] ?? 0);
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                            <td><?= html_escape($p->nama_prodi) ?></td>
                            <td><span class="badge text-bg-light border"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                            <td><?= html_escape($vendor_label) ?></td>
                            <td><?= rp_kaur($total_harga) ?></td>
                            <td><span class="status-pill <?= status_class_kaur($nego_label) ?>"><?= html_escape($nego_label) ?></span></td>
                            <td><span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#approvalModal<?= (int) $p->id_pengajuan ?>"><i class="bi bi-eye me-1"></i> Detail</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-2 border-top">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, max(1, $page - 1)) ?>">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, $i) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, min($total_pages, $page + 1)) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>
        <?php foreach (($pengajuan_kaprodi ?? []) as $p): ?>
            <?php
                $can_approve_modal = !empty($p->items);
                foreach (($p->items ?? []) as $approval_item) {
                    if (empty($approval_item->latest_negosiasi) || $approval_item->latest_negosiasi->status !== 'Deal') {
                        $can_approve_modal = false;
                        break;
                    }
                }
            ?>
            <div class="modal fade" id="approvalModal<?= (int) $p->id_pengajuan ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?></h5>
                                <div class="small text-muted"><?= html_escape($p->nama_prodi) ?> · <?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><div class="mini-label">Kebutuhan</div><div><?= html_escape($p->kebutuhan_lab ?: '-') ?></div></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light"><tr><th>Item</th><th>Volume</th><th>Harga Awal</th><th>Vendor</th><th>Harga Negosiasi</th><th>Status</th><th>Garansi</th><th>Catatan</th></tr></thead>
                                    <tbody>
                                        <?php foreach (($p->items ?? []) as $item): $latest = $item->latest_negosiasi ?? null; ?>
                                            <tr>
                                                <td><?= html_escape($item->uraian_barang) ?></td>
                                                <td><?= num_kaur($item->vol) ?> <?= html_escape($item->satuan) ?></td>
                                                <td><?= rp_kaur($latest->harga_awal ?? $item->harga_penawaran_sat ?? 0) ?></td>
                                                <td><?= html_escape($latest->vendor ?? '-') ?></td>
                                                <td><?= $latest ? rp_kaur($latest->harga_negosiasi) : '-' ?></td>
                                                <td><?= html_escape($latest->status ?? 'Belum Negosiasi') ?></td>
                                                <td><?= html_escape($latest->garansi ?? '-') ?></td>
                                                <td><?= html_escape($latest->catatan ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label small fw-semibold">Catatan Approval / Revisi</label>
                            <textarea name="catatan_approval" class="form-control" rows="3" placeholder="Catatan approval, revisi, atau alasan penolakan."><?= html_escape($p->catatan_approval ?? '') ?></textarea>
                            <?php if (!$can_approve_modal): ?><div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i> Setujui aktif setelah semua item negosiasi berstatus Deal.</div><?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/revisi') ?>" class="btn btn-warning rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i> Revisi</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/tolak') ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>" class="btn btn-success rounded-pill px-3" <?= $can_approve_modal ? '' : 'disabled' ?>><i class="bi bi-check2 me-1"></i> Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_module === 'anggaran'): ?>
        <section id="anggaran" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold mb-2">Pagu Anggaran Tahunan</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-label">Tahun Anggaran</div><div class="fw-bold"><?= (int) $anggaran['tahun'] ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pagu Anggaran</div><div class="fw-bold"><?= rp_kaur($anggaran['total_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengadaan Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengadaan_deal'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengeluaran Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengeluaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Sisa Anggaran</div><div class="fw-bold text-success"><?= rp_kaur($anggaran['sisa_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Penggunaan</div><div class="fw-bold"><?= number_format((float) $anggaran['persentase_penggunaan'], 1, ',', '.') ?>%</div></div>
                        <div class="col-6"><div class="mini-label">Penghematan CAPEX</div><div class="fw-bold text-primary"><?= rp_kaur($anggaran['penghematan_capex'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Belum Terealisasi</div><div class="fw-bold"><?= (int) ($anggaran['belum_terealisasi'] ?? 0) ?> pengajuan</div></div>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: <?= min(100, (float) $anggaran['persentase_penggunaan']) ?>%"></div></div>
                </div>
                <div class="col-lg-6">
                    <form method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_anggaran') ?>" class="row g-2">
                        <div class="col-md-4"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= (int) $anggaran['tahun'] ?>" required></div>
                        <div class="col-md-8"><label class="form-label small fw-semibold">Total Anggaran</label><input type="text" name="total_anggaran" class="form-control money-input" value="<?= $anggaran['total_anggaran'] ? rp_kaur($anggaran['total_anggaran']) : '' ?>" required></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Catatan</label><textarea name="catatan" class="form-control" rows="2"><?= html_escape($anggaran['catatan'] ?? '') ?></textarea></div>
                        <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-fik rounded-pill px-4"><i class="bi bi-save me-1"></i> Simpan Anggaran</button></div>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'bast'): ?>
        <section id="bast" class="section-anchor mb-4">
            <h2 class="h5 fw-bold mb-3">Input BAST dari Logistik</h2>
            <div class="alert alert-warning border-0 rounded-3 small mb-3"><i class="bi bi-hourglass-split me-1"></i> Template resmi BAST berstatus <strong>Hold</strong>. Struktur modul sudah siap, sementara Laboran/Kaur tetap dapat mengunggah PDF atau hasil scan dari Logistik.</div>
            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="panel-card p-3 p-lg-4 h-100">
                        <div class="accordion" id="bastAccordion">
                            <?php $bast_rows = $bast_ready ?? []; ?>
                            <?php if (empty($bast_rows)): ?>
                                <div class="text-muted small p-3">Belum ada pengajuan yang siap BAST. BAST baru tersedia setelah pengajuan disetujui Kaur.</div>
                            <?php endif; ?>
                            <?php foreach ($bast_rows as $index => $p): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#bastForm<?= (int) $p->id_pengajuan ?>">
                                            <?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?> <span class="badge text-bg-success ms-2"><?= html_escape($p->status) ?></span>
                                        </button>
                                    </h2>
                                    <div id="bastForm<?= (int) $p->id_pengajuan ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#bastAccordion">
                                        <form class="accordion-body row g-2" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/kaur/pengajuan/simpan_bast/'.$p->id_pengajuan) ?>">
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Nomor BAST</label><input type="text" name="nomor_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="tanggal_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Jenis</label><select name="jenis_bast" class="form-select"><option value="Barang" <?= (($p->jenis_pengajuan ?? 'Barang') === 'Barang') ? 'selected' : '' ?>>Barang</option><option value="Jasa" <?= (($p->jenis_pengajuan ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option></select></div>
                                            <div class="col-md-7"><label class="form-label small fw-semibold">File PDF/Scan</label><input type="file" name="file_bast" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div>
                                            <div class="col-md-5"><label class="form-label small fw-semibold">Catatan</label><input type="text" name="catatan" class="form-control"></div>
                                            <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-fik rounded-pill px-4"><i class="bi bi-upload me-1"></i> Simpan BAST</button></div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="panel-card p-3 p-lg-4 h-100">
                        <h3 class="h6 fw-bold mb-3">BAST Terakhir</h3>
                        <div class="vstack gap-2">
                            <?php if (empty($bast_list)): ?>
                                <div class="text-muted small">Belum ada dokumen BAST.</div>
                            <?php else: foreach ($bast_list as $b): ?>
                                <div class="border rounded-3 p-2">
                                    <div class="fw-semibold"><?= html_escape($b->nomor_bast) ?></div>
                                    <div class="small text-muted"><?= html_escape($b->nama_pengajuan ?? '-') ?> - <?= date('d/m/Y', strtotime($b->tanggal_bast)) ?></div>
                                    <?php if (!empty($b->file_bast)): ?><a class="small" href="<?= base_url($b->file_bast) ?>" target="_blank">Lihat file</a><?php endif; ?>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'laporan'): ?>
        <section id="laporan" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <h2 class="h5 fw-bold mb-1">Laporan Hasil Negosiasi</h2>
            <div class="text-muted small mb-3">Hanya data dengan status Deal yang tampil sebagai dokumentasi resmi hasil akhir.</div>
            <div class="table-responsive">
                <table class="table table-clean align-middle">
                    <thead><tr><th>Pengajuan</th><th>Item</th><th>Vendor</th><th>Harga Awal</th><th>Harga Akhir</th><th>Selisih</th><th>Volume</th><th>Garansi</th><th>Catatan</th></tr></thead>
                    <tbody>
                        <?php if (empty($laporan_negosiasi)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-5">Belum ada negosiasi yang Deal.</td></tr>
                        <?php else: foreach ($laporan_negosiasi as $lap): ?>
                            <tr>
                                <td><div class="fw-semibold"><?= html_escape($lap->kode_pengajuan) ?></div><div class="small text-muted"><?= html_escape($lap->nama_pengajuan) ?></div></td>
                                <td><?= html_escape($lap->uraian_barang) ?></td>
                                <td><?= html_escape($lap->vendor ?: '-') ?></td>
                                <td><?= rp_kaur($lap->harga_awal) ?></td>
                                <td class="fw-semibold text-success"><?= rp_kaur($lap->harga_negosiasi) ?></td>
                                <td><?= rp_kaur($lap->selisih_harga) ?></td>
                                <td><?= num_kaur($lap->volume_negosiasi) ?> <?= html_escape($lap->satuan) ?></td>
                                <td><?= html_escape($lap->garansi ?: '-') ?></td>
                                <td><?= html_escape($lap->catatan ?: '-') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.money-input').forEach((input) => {
            input.addEventListener('blur', () => {
                const digits = input.value.replace(/[^0-9]/g, '');
                if (!digits) return;
                input.value = 'Rp ' + Number(digits).toLocaleString('id-ID');
            });
        });
    </script>
</body>
</html>
