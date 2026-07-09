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
        'Approval Tahap 1 (BAST)' => 'status-bast-pending',
        'BAST Disetujui' => 'status-bast-ok',
        'Negosiasi' => 'status-negosiasi',
        'ACC Anak Perusahaan' => 'status-acc',
        'Alokasi' => 'status-alokasi',
        'Selesai' => 'status-selesai',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function kondisi_class_kaur($kondisi) {
    if ($kondisi === 'Baik' || $kondisi === 'Sudah Diperbaiki') {
        return 'badge-ok';
    }
    if ($kondisi === 'Perlu Perbaikan') {
        return 'badge-warn';
    }
    return 'badge-danger-soft';
}
$total_bast_selesai = count(array_filter($pengajuan, fn($p) => in_array($p->status, ['BAST Disetujui', 'Selesai'], true)));
$total_negosiasi = count(array_filter($pengajuan, fn($p) => $p->status === 'Negosiasi'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dashboard Kaur Laboratorium' ?></title>
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
        .summary-card { min-height: 104px; padding: 18px; position: relative; overflow: hidden; }
        .summary-card:before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--accent, #ea5b1a); }
        .summary-card .value { font-weight: 700; font-size: 1.55rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .8rem; margin-top: 8px; }
        .quick-link { min-height: 72px; border: 1px solid #e8eaed; border-radius: 8px; color: #202124; text-decoration: none; background: #fff; transition: .18s ease; }
        .quick-link:hover { transform: translateY(-2px); border-color: rgba(234, 91, 26, .35); color: #202124; }
        .quick-icon { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234, 91, 26, .12); color: #c24a13; }
        .procurement-table { min-width: 1320px; border-color: #60656b; font-size: .82rem; }
        .procurement-table th, .procurement-table td { border: 1px solid #60656b; vertical-align: middle; padding: 7px 8px; }
        .procurement-table thead th { background: #e9ecef; text-align: center; font-weight: 700; }
        .procurement-table .total-label { text-align: right; font-weight: 700; background: #f8f9fa; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table-clean td { vertical-align: middle; }
        .status-pill, .soft-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-bast-pending { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-bast-ok { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-acc { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-alokasi { background: rgba(111, 66, 193, .12); color: #6f42c1; }
        .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .badge-ok { background: rgba(25, 135, 84, .12); color: #198754; }
        .badge-warn { background: rgba(245, 158, 11, .16); color: #a16207; }
        .badge-danger-soft { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .lab-strip { border-left: 4px solid var(--accent, #ea5b1a); }
        .item-row .form-control { min-width: 120px; }
        .item-row .uraian-input { min-width: 260px; }
        .sticky-actions { background: #fff; border-top: 1px solid #e8eaed; }
        .section-anchor { scroll-margin-top: 92px; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1 1 auto; }
            .summary-card { min-height: auto; }
            .quick-link { min-height: auto; }
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
                        <div class="small text-white-50"><?= html_escape($this->session->userdata('nama') ?: 'Kaur Lab') ?></div>
                    </div>
                </div>
                <div class="topbar-actions d-flex flex-wrap align-items-center gap-2">
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="#pengajuan" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-file-earmark-plus me-1"></i> Pengajuan</a>
                    <a href="#laporan-maintenance" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-tools me-1"></i> Maintenance</a>
                    <a href="#laporan-lab" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-building me-1"></i> Lab</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4 py-lg-5">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-2">Kepala Urusan Lab</h1>
                <div class="text-muted">Laporan maintenance, laporan laboratorium, approval tahap 1 BAST, dan pengajuan kebutuhan lab.</div>
            </div>
            <button class="btn btn-fik rounded-pill px-4" type="button" data-bs-toggle="collapse" data-bs-target="#formPengajuan"><i class="bi bi-plus-circle me-1"></i> Buat Pengajuan</button>
        </div>

        <section class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="panel-card summary-card" style="--accent:#0d6efd"><div class="value"><?= count($pengajuan) ?></div><div class="label">Total pengajuan</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card" style="--accent:#0dcaf0"><div class="value"><?= count($approval_bast) ?></div><div class="label">Approval BAST</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card" style="--accent:#198754"><div class="value"><?= count($maintenance) ?></div><div class="label">Laporan maintenance</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card" style="--accent:#6f42c1"><div class="value"><?= count($laboratorium) ?></div><div class="label">Ruang laboratorium</div></div></div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><a href="#laporan-maintenance" class="quick-link p-3 d-flex align-items-center gap-3"><span class="quick-icon"><i class="bi bi-tools"></i></span><span class="fw-semibold">Laporan Maintenance</span></a></div>
            <div class="col-md-6 col-xl-3"><a href="#laporan-lab" class="quick-link p-3 d-flex align-items-center gap-3"><span class="quick-icon"><i class="bi bi-building-check"></i></span><span class="fw-semibold">Laporan Laboratorium</span></a></div>
            <div class="col-md-6 col-xl-3"><a href="#approval-bast" class="quick-link p-3 d-flex align-items-center gap-3"><span class="quick-icon"><i class="bi bi-patch-check"></i></span><span class="fw-semibold">Approval Tahap 1 BAST</span></a></div>
            <div class="col-md-6 col-xl-3"><a href="#pengajuan" class="quick-link p-3 d-flex align-items-center gap-3"><span class="quick-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span><span class="fw-semibold">Pengajuan Barang</span></a></div>
        </section>

        <section class="collapse mb-4 section-anchor" id="formPengajuan">
            <div class="panel-card p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Form Pengajuan Kebutuhan Lab</h5>
                        <div class="text-muted small">Harga penawaran otomatis dihitung dengan kenaikan 20% dan PPN 11% pada laporan.</div>
                    </div>
                </div>
                <form action="<?= base_url('index.php/kaur/pengajuan/simpan') ?>" method="post">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Nama Lab</label><input type="text" name="nama_lab" class="form-control" placeholder="Contoh: Lab Multimedia" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Nama Pengajuan</label><input type="text" name="nama_pengajuan" class="form-control" placeholder="Contoh: Pengadaan Inventaris Lab" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold text-muted">Anak Perusahaan / Vendor</label><input type="text" name="anak_perusahaan" class="form-control" placeholder="Contoh: PT Trengginas Jaya"></div>
                        <div class="col-12"><label class="form-label small fw-semibold text-muted">Kebutuhan Lab</label><textarea name="kebutuhan_lab" class="form-control" rows="2" placeholder="Kebutuhan barang/pekerjaan laboratorium"></textarea></div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle mb-0" id="itemTable">
                            <thead class="table-light"><tr><th>Uraian Barang/Pekerjaan</th><th>Vol</th><th>Satuan</th><th>Harga Penawaran Sat</th><th>Link Penawaran</th><th>Vol Nego</th><th>Harga Nego Sat</th><th>Garansi</th><th></th></tr></thead>
                            <tbody>
                                <tr class="item-row">
                                    <td><input type="text" name="uraian_barang[]" class="form-control uraian-input" placeholder="Nama barang/pekerjaan" required></td>
                                    <td><input type="number" step="0.01" min="0" name="vol[]" class="form-control" value="1" required></td>
                                    <td><input type="text" name="satuan[]" class="form-control" value="unit" required></td>
                                    <td><input type="number" step="1" min="0" name="harga_penawaran_sat[]" class="form-control" placeholder="0" required></td>
                                    <td><input type="url" name="link_penawaran[]" class="form-control" placeholder="https://..."></td>
                                    <td><input type="number" step="0.01" min="0" name="hasil_negosiasi_vol[]" class="form-control" value="1"></td>
                                    <td><input type="number" step="1" min="0" name="hasil_negosiasi_sat[]" class="form-control" placeholder="0"></td>
                                    <td><input type="text" name="garansi[]" class="form-control" placeholder="Contoh: 1 Tahun"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-row"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold text-muted">Catatan Negosiasi</label><textarea name="catatan_negosiasi" class="form-control" rows="2"></textarea></div>
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="addRowBtn"><i class="bi bi-plus-lg me-1"></i> Tambah Baris</button>
                        <button class="btn btn-fik rounded-pill px-4"><i class="bi bi-send-check me-1"></i> Simpan Pengajuan</button>
                    </div>
                </form>
            </div>
        </section>

        <section id="approval-bast" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Approval Tahap 1 (BAST)</h5>
                    <div class="text-muted small">Antrian BAST yang menunggu persetujuan Kaur Laboratorium.</div>
                </div>
                <span class="status-pill status-bast-pending align-self-start"><?= count($approval_bast) ?> menunggu</span>
            </div>
            <?php if(empty($approval_bast)): ?>
                <div class="text-muted text-center py-4"><i class="bi bi-check-circle display-6 d-block mb-2 text-success"></i>Tidak ada BAST yang menunggu approval.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach($approval_bast as $b): ?>
                        <div class="col-lg-6 col-xxl-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                    <div>
                                        <div class="fw-bold"><?= html_escape($b->nama_pengajuan) ?></div>
                                        <div class="small text-muted"><?= html_escape($b->kode_pengajuan) ?> - <?= html_escape($b->nama_lab) ?></div>
                                    </div>
                                    <i class="bi bi-patch-check text-info fs-4"></i>
                                </div>
                                <div class="small mb-3">
                                    <div><span class="text-muted">Nomor:</span> <?= html_escape($b->bast_nomor ?: '-') ?></div>
                                    <div><span class="text-muted">Tanggal:</span> <?= html_escape($b->bast_tanggal ?: '-') ?></div>
                                    <div><span class="text-muted">Penerima:</span> <?= html_escape($b->bast_penerima ?: '-') ?></div>
                                </div>
                                <a href="<?= base_url('index.php/kaur/pengajuan/setujui_bast/'.$b->id_pengajuan) ?>" class="btn btn-success w-100 rounded-pill" onclick="return confirm('Setujui Approval Tahap 1 BAST ini?')"><i class="bi bi-check2-circle me-1"></i> Setujui BAST</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section id="pengajuan" class="section-anchor mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Pengajuan Kaur</h5>
                    <div class="text-muted small">Total BAST disetujui: <?= $total_bast_selesai ?> - Dalam negosiasi: <?= $total_negosiasi ?></div>
                </div>
            </div>
            <?php if(empty($pengajuan)): ?>
                <div class="panel-card p-5 text-center text-muted"><i class="bi bi-table display-5 d-block mb-3 text-secondary"></i>Belum ada pengajuan Kaur.</div>
            <?php else: ?>
                <?php foreach($pengajuan as $p): ?>
                    <section class="panel-card mb-4 overflow-hidden">
                        <div class="p-3 p-lg-4 border-bottom">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <h5 class="fw-bold mb-0"><?= html_escape($p->nama_pengajuan) ?></h5>
                                        <span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span>
                                    </div>
                                    <div class="small text-muted"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_lab) ?> - <?= html_escape($p->anak_perusahaan ?: 'Vendor belum diisi') ?></div>
                                    <?php if(!empty($p->kebutuhan_lab)): ?><div class="small mt-2"><?= nl2br(html_escape($p->kebutuhan_lab)) ?></div><?php endif; ?>
                                </div>
                                <div class="text-lg-end">
                                    <div class="small text-muted">Total setelah +20% dan PPN</div>
                                    <div class="h5 fw-bold mb-1"><?= rp_kaur($p->summary['total_penawaran']) ?></div>
                                    <div class="small text-success">Sisa alokasi: <?= rp_kaur($p->summary['sisa_alokasi']) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table procurement-table mb-0">
                                <thead>
                                    <tr>
                                        <th rowspan="2" style="width:44px;">No.</th>
                                        <th rowspan="2">Uraian Barang/Pekerjaan</th>
                                        <th rowspan="2">Vol</th>
                                        <th rowspan="2">Satuan</th>
                                        <th colspan="4">Harga Penawaran Kaur (Rp)</th>
                                        <th rowspan="2">Uraian Barang/Pekerjaan</th>
                                        <th colspan="3">Hasil Negosiasi (Rp)</th>
                                        <th rowspan="2">Garansi</th>
                                        <th rowspan="2">Alokasi Sisa</th>
                                    </tr>
                                    <tr>
                                        <th>Harga Sat</th><th>Jmlh Harga</th><th>Harga +20%</th><th>Link</th>
                                        <th>Vol</th><th>Harga Sat</th><th>Jmlh Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($p->items as $i => $item): ?>
                                        <?php
                                        $jumlah_penawaran = (float)$item->vol * (float)$item->harga_penawaran_sat;
                                        $harga_markup_sat = (float)$item->harga_penawaran_sat * 1.2;
                                        $jumlah_markup = (float)$item->vol * $harga_markup_sat;
                                        $nego_vol = $item->hasil_negosiasi_vol !== null ? (float)$item->hasil_negosiasi_vol : (float)$item->vol;
                                        $nego_sat = $item->hasil_negosiasi_sat !== null ? (float)$item->hasil_negosiasi_sat : 0;
                                        $jumlah_nego = $nego_vol * $nego_sat;
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $i + 1 ?></td>
                                            <td><?= html_escape($item->uraian_barang) ?></td>
                                            <td class="text-center"><?= num_kaur($item->vol) ?></td>
                                            <td class="text-center"><?= html_escape($item->satuan) ?></td>
                                            <td class="text-end"><?= rp_kaur($item->harga_penawaran_sat) ?></td>
                                            <td class="text-end"><?= rp_kaur($jumlah_penawaran) ?></td>
                                            <td class="text-end"><?= rp_kaur($jumlah_markup) ?></td>
                                            <td class="text-center"><?php if($item->link_penawaran): ?><a href="<?= html_escape($item->link_penawaran) ?>" target="_blank" rel="noopener">Link</a><?php else: ?>-<?php endif; ?></td>
                                            <td><?= html_escape($item->uraian_barang) ?></td>
                                            <td class="text-center"><?= num_kaur($nego_vol) ?></td>
                                            <td class="text-end"><?= rp_kaur($nego_sat) ?></td>
                                            <td class="text-end"><?= rp_kaur($jumlah_nego) ?></td>
                                            <td><?= html_escape($item->garansi ?: '-') ?></td>
                                            <td><?= html_escape($item->alokasi_sisa ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr><td colspan="6" class="total-label">Sub Total (+20%)</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['subtotal_markup']) ?></td><td></td><td colspan="3" class="total-label">Sub Total</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['subtotal_negosiasi']) ?></td><td colspan="2"></td></tr>
                                    <tr><td colspan="6" class="total-label">PPN 11%</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['ppn_penawaran']) ?></td><td></td><td colspan="3" class="total-label">PPN 11%</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['ppn_negosiasi']) ?></td><td colspan="2"></td></tr>
                                    <tr><td colspan="6" class="total-label">Total</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['total_penawaran']) ?></td><td></td><td colspan="3" class="total-label">Total</td><td class="text-end fw-bold"><?= rp_kaur($p->summary['total_negosiasi']) ?></td><td colspan="2"></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="sticky-actions p-3 p-lg-4">
                            <div class="row g-3 align-items-start">
                                <div class="col-lg-4">
                                    <div class="small text-muted mb-1">BAST Tahap 1</div>
                                    <div class="fw-semibold"><?= html_escape($p->bast_nomor ?: 'Belum ada BAST') ?></div>
                                    <div class="small text-muted"><?= html_escape($p->bast_tanggal ?: '-') ?> <?= $p->bast_penerima ? '- ' . html_escape($p->bast_penerima) : '' ?></div>
                                </div>
                                <div class="col-lg-8">
                                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                        <button class="btn btn-sm btn-outline-info rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#bastTahap<?= $p->id_pengajuan ?>">Approval BAST</button>
                                        <a href="<?= base_url('index.php/kaur/pengajuan/setujui_bast/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill" onclick="return confirm('Setujui Approval Tahap 1 BAST ini?')">Setujui BAST</a>
                                        <form method="post" action="<?= base_url('index.php/kaur/pengajuan/negosiasi/'.$p->id_pengajuan) ?>" class="d-inline-flex gap-2"><input type="hidden" name="catatan_negosiasi" value="Naik ke tahap negosiasi"><button class="btn btn-sm btn-outline-warning rounded-pill">Naik ke Negosiasi</button></form>
                                        <a href="<?= base_url('index.php/kaur/pengajuan/acc/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill" onclick="return confirm('Tandai hasil negosiasi sudah ACC anak perusahaan?')">ACC Anak Perusahaan</a>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#alokasi<?= $p->id_pengajuan ?>">Alokasi Sisa</button>
                                        <a href="<?= base_url('index.php/kaur/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-fik rounded-pill"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel</a>
                                        <a href="<?= base_url('index.php/kaur/pengajuan/selesai/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="return confirm('Tandai pengajuan selesai?')">Selesai</a>
                                    </div>
                                </div>
                            </div>

                            <div class="collapse mt-3" id="bastTahap<?= $p->id_pengajuan ?>">
                                <form method="post" action="<?= base_url('index.php/kaur/pengajuan/bast_tahap1/'.$p->id_pengajuan) ?>" class="panel-card p-3 shadow-none">
                                    <div class="row g-2">
                                        <div class="col-md-3"><label class="form-label small text-muted">Nomor BAST</label><input type="text" name="bast_nomor" class="form-control" value="<?= html_escape($p->bast_nomor) ?>"></div>
                                        <div class="col-md-3"><label class="form-label small text-muted">Tanggal BAST</label><input type="date" name="bast_tanggal" class="form-control" value="<?= html_escape($p->bast_tanggal ?: date('Y-m-d')) ?>"></div>
                                        <div class="col-md-3"><label class="form-label small text-muted">Penerima</label><input type="text" name="bast_penerima" class="form-control" value="<?= html_escape($p->bast_penerima) ?>"></div>
                                        <div class="col-md-3"><label class="form-label small text-muted">Catatan</label><input type="text" name="bast_catatan" class="form-control" value="<?= html_escape($p->bast_catatan) ?>"></div>
                                        <div class="col-12 text-end"><button class="btn btn-fik rounded-pill px-4">Kirim ke Approval Tahap 1</button></div>
                                    </div>
                                </form>
                            </div>

                            <div class="collapse mt-3" id="alokasi<?= $p->id_pengajuan ?>">
                                <form method="post" action="<?= base_url('index.php/kaur/pengajuan/alokasi/'.$p->id_pengajuan) ?>" class="panel-card p-3 shadow-none">
                                    <div class="fw-semibold mb-2">Alokasi sisa anggaran: <?= rp_kaur($p->summary['sisa_alokasi']) ?></div>
                                    <div class="row g-2">
                                        <?php foreach($p->items as $item): ?>
                                            <div class="col-md-6"><label class="form-label small text-muted"><?= html_escape($item->uraian_barang) ?></label><input type="text" name="alokasi_item[<?= $item->id_item ?>]" class="form-control" value="<?= html_escape($item->alokasi_sisa) ?>" placeholder="Contoh: dialihkan ke sparepart"></div>
                                        <?php endforeach; ?>
                                        <div class="col-12"><label class="form-label small text-muted">Catatan alokasi umum</label><textarea name="catatan_alokasi" class="form-control" rows="2"><?= html_escape($p->catatan_alokasi) ?></textarea></div>
                                        <div class="col-12 text-end"><button class="btn btn-fik rounded-pill px-4">Simpan Alokasi</button></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section id="laporan-maintenance" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Laporan Maintenance</h5>
                    <div class="text-muted small">Data maintenance terbaru dari aset laboratorium.</div>
                </div>
                <span class="soft-badge badge-ok"><?= count($maintenance) ?> data</span>
            </div>
            <div class="table-responsive">
                <table class="table table-clean align-middle mb-0">
                    <thead><tr><th>Tanggal</th><th>Aset</th><th>Ruangan</th><th>Kondisi</th><th>Deskripsi</th><th>Catatan</th></tr></thead>
                    <tbody>
                        <?php if(empty($maintenance)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada laporan maintenance.</td></tr>
                        <?php else: foreach($maintenance as $m): ?>
                            <tr>
                                <td class="text-nowrap"><?= html_escape($m->tanggal_maintenance) ?></td>
                                <td><div class="fw-semibold"><?= html_escape($m->nama_aset ?: '-') ?></div><div class="small text-muted"><?= html_escape($m->kode_aset ?: '-') ?></div></td>
                                <td><?= html_escape($m->nama_ruangan ?: '-') ?></td>
                                <td><span class="soft-badge <?= kondisi_class_kaur($m->kondisi_setelah) ?>"><?= html_escape($m->kondisi_setelah) ?></span></td>
                                <td class="small"><?= nl2br(html_escape($m->deskripsi ?: '-')) ?></td>
                                <td class="small"><?= nl2br(html_escape($m->catatan ?: '-')) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="laporan-lab" class="section-anchor panel-card p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">Laporan Laboratorium</h5>
                    <div class="text-muted small">Rekap ruangan, jenis aset, unit total, dan unit tersedia.</div>
                </div>
                <span class="soft-badge status-alokasi"><?= count($laboratorium) ?> ruangan</span>
            </div>
            <div class="row g-3">
                <?php if(empty($laboratorium)): ?>
                    <div class="col-12"><div class="text-center text-muted py-4">Belum ada data laboratorium.</div></div>
                <?php else: foreach($laboratorium as $lab): ?>
                    <?php $ratio = ((int)$lab->total_unit > 0) ? round(((int)$lab->tersedia_unit / (int)$lab->total_unit) * 100) : 0; ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded-3 p-3 h-100 lab-strip" style="--accent: <?= html_escape($lab->warna ?: '#ea5b1a') ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="fw-bold"><?= html_escape($lab->nama_ruangan) ?></div>
                                    <div class="small text-muted"><?= html_escape($lab->deskripsi ?: 'Tanpa deskripsi') ?></div>
                                </div>
                                <span class="quick-icon"><i class="bi bi-<?= html_escape($lab->icon ?: 'building') ?>"></i></span>
                            </div>
                            <div class="row g-2 text-center mb-3">
                                <div class="col-4"><div class="fw-bold"><?= (int)$lab->total_jenis ?></div><div class="small text-muted">Jenis</div></div>
                                <div class="col-4"><div class="fw-bold"><?= (int)$lab->total_unit ?></div><div class="small text-muted">Unit</div></div>
                                <div class="col-4"><div class="fw-bold"><?= (int)$lab->tersedia_unit ?></div><div class="small text-muted">Tersedia</div></div>
                            </div>
                            <div class="progress" role="progressbar" aria-valuenow="<?= $ratio ?>" aria-valuemin="0" aria-valuemax="100" style="height:8px">
                                <div class="progress-bar" style="width: <?= $ratio ?>%; background: <?= html_escape($lab->warna ?: '#ea5b1a') ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small text-muted"><span>Ketersediaan</span><span><?= $ratio ?>%</span></div>
                            <?php if((int)$lab->perlu_perhatian > 0): ?><div class="mt-2"><span class="soft-badge badge-warn"><?= (int)$lab->perlu_perhatian ?> aset perlu perhatian</span></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addRowBtn = document.getElementById('addRowBtn');
            const tbody = document.querySelector('#itemTable tbody');

            function bindRemoveButtons() {
                document.querySelectorAll('.js-remove-row').forEach(function (button) {
                    button.onclick = function () {
                        if (tbody.querySelectorAll('tr').length > 1) {
                            button.closest('tr').remove();
                        }
                    };
                });
            }

            addRowBtn?.addEventListener('click', function () {
                const firstRow = tbody.querySelector('tr');
                const clone = firstRow.cloneNode(true);
                clone.querySelectorAll('input').forEach(function (input) {
                    if (input.name === 'vol[]' || input.name === 'hasil_negosiasi_vol[]') {
                        input.value = '1';
                    } else if (input.name === 'satuan[]') {
                        input.value = 'unit';
                    } else {
                        input.value = '';
                    }
                });
                tbody.appendChild(clone);
                bindRemoveButtons();
            });

            bindRemoveButtons();
        });
    </script>
</body>
</html>
