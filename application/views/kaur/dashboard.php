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
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi, .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .section-anchor { scroll-margin-top: 92px; }
        .item-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; }
        .mini-label { font-size: .74rem; color: #6c757d; font-weight: 600; }
        .progress { height: 10px; border-radius: 999px; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn { flex: 1 1 auto; }
            .summary-card { min-height: auto; }
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
                    <a href="#pengajuan" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-inboxes me-1"></i> Pengajuan</a>
                    <a href="#negosiasi" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-chat-square-text me-1"></i> Negosiasi</a>
                    <a href="#laporan" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan</a>
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Dashboard Kaur Laboratorium</h1>
                <p class="text-muted mb-0">Kelola alur dari pengajuan Kaprodi sampai negosiasi, approval, BAST, dan inventarisasi.</p>
            </div>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
        </div>

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
                ['id' => 'approval', 'icon' => 'bi-check2-circle', 'title' => 'Approval', 'desc' => 'Setujui, revisi, atau tolak.'],
                ['id' => 'anggaran', 'icon' => 'bi-cash-coin', 'title' => 'Alokasi Anggaran', 'desc' => 'Kelola total, pengeluaran, dan sisa.'],
                ['id' => 'bast', 'icon' => 'bi-file-earmark-pdf', 'title' => 'BAST', 'desc' => 'Input dokumen dari Logistik.'],
                ['id' => 'laporan', 'icon' => 'bi-file-earmark-spreadsheet', 'title' => 'Laporan', 'desc' => 'Hasil akhir negosiasi Deal.'],
            ];
            foreach ($menus as $menu): ?>
                <div class="col-md-6 col-xl-2">
                    <a class="quick-link d-flex align-items-center gap-3 p-3 h-100" href="#<?= $menu['id'] ?>">
                        <span class="quick-icon"><i class="bi <?= $menu['icon'] ?>"></i></span>
                        <span><span class="fw-bold d-block"><?= $menu['title'] ?></span><span class="small text-muted"><?= $menu['desc'] ?></span></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <section id="pengajuan" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pengajuan Kaprodi</h2>
                    <div class="text-muted small">Data dapat dicari berdasarkan tanggal, jenis, status, dan kata kunci.</div>
                </div>
            </div>
            <form method="get" action="<?= base_url('index.php/kaur/dashboard') ?>" class="row g-2 align-items-end mb-3">
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
                        <?php foreach (['Pengajuan','Revisi','Sedang Negosiasi','Deal','Approval','BAST','Selesai','Ditolak'] as $status): ?>
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
                    <thead><tr><th>Kode</th><th>Prodi</th><th>Jenis</th><th>Kebutuhan</th><th>Status</th><th>Tanggal</th></tr></thead>
                    <tbody>
                        <?php if (empty($pengajuan_kaprodi)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada pengajuan sesuai filter.</td></tr>
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
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pt-2 border-top">
                <div class="small text-muted">Menampilkan <?= count($pengajuan_kaprodi ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaur/dashboard?' . query_kaur($filters, max(1, $page - 1))) ?>#pengajuan">Prev</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaur/dashboard?' . query_kaur($filters, $i)) ?>#pengajuan"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaur/dashboard?' . query_kaur($filters, min($total_pages, $page + 1))) ?>#pengajuan">Next</a></li>
                    </ul>
                </nav>
            </div>
        </section>

        <section id="negosiasi" class="section-anchor mb-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Negosiasi oleh Kaur</h2>
                    <div class="text-muted small">Setiap simpan akan menjadi riwayat baru. Kaprodi hanya melihat status sampai hasil Deal.</div>
                </div>
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
        </section>

        <section id="approval" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <h2 class="h5 fw-bold mb-1">Approval Kaur</h2>
            <div class="text-muted small mb-3">Kaur dapat menyetujui, meminta revisi, atau menolak pengajuan sesuai kebutuhan proses bisnis.</div>
            <div class="row g-3">
                <?php foreach (($pengajuan_kaprodi ?? []) as $p): ?>
                    <div class="col-lg-6">
                        <form class="item-card p-3 h-100" method="post" action="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <div><div class="fw-bold"><?= html_escape($p->kode_pengajuan) ?></div><div class="small text-muted"><?= html_escape($p->nama_pengajuan) ?></div></div>
                                <span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span>
                            </div>
                            <textarea name="catatan_approval" class="form-control mb-2" rows="2" placeholder="Catatan approval atau revisi"><?= html_escape($p->catatan_approval ?? '') ?></textarea>
                            <div class="d-flex flex-wrap gap-2">
                                <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>" class="btn btn-success btn-sm rounded-pill px-3"><i class="bi bi-check2 me-1"></i> Setujui</button>
                                <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/revisi') ?>" class="btn btn-warning btn-sm rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i> Revisi</button>
                                <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/tolak') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="anggaran" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold mb-2">Alokasi Anggaran</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-label">Total Anggaran</div><div class="fw-bold"><?= rp_kaur($anggaran['total_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengeluaran Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengeluaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Sisa Anggaran</div><div class="fw-bold text-success"><?= rp_kaur($anggaran['sisa_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Penggunaan</div><div class="fw-bold"><?= number_format((float) $anggaran['persentase_penggunaan'], 1, ',', '.') ?>%</div></div>
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

        <section id="bast" class="section-anchor mb-4">
            <h2 class="h5 fw-bold mb-3">Input BAST dari Logistik</h2>
            <div class="row g-3">
                <div class="col-xl-7">
                    <div class="panel-card p-3 p-lg-4 h-100">
                        <div class="accordion" id="bastAccordion">
                            <?php foreach (($pengajuan_kaprodi ?? []) as $index => $p): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#bastForm<?= (int) $p->id_pengajuan ?>">
                                            <?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?>
                                        </button>
                                    </h2>
                                    <div id="bastForm<?= (int) $p->id_pengajuan ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#bastAccordion">
                                        <form class="accordion-body row g-2" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/kaur/pengajuan/simpan_bast/'.$p->id_pengajuan) ?>">
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Nomor BAST</label><input type="text" name="nomor_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="tanggal_bast" class="form-control" required></div>
                                            <div class="col-md-4"><label class="form-label small fw-semibold">Jenis</label><select name="jenis_bast" class="form-select"><option value="Barang" <?= (($p->jenis_pengajuan ?? 'Barang') === 'Barang') ? 'selected' : '' ?>>Barang</option><option value="Jasa" <?= (($p->jenis_pengajuan ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option></select></div>
                                            <div class="col-md-7"><label class="form-label small fw-semibold">File PDF/Scan</label><input type="file" name="file_bast" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
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
