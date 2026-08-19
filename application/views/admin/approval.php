<?php
$status_class = function ($status) {
    return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Menunggu Verifikasi Laboran'));
};
$approval_progress = static function ($p) {
    $status = (string) ($p->status ?? '');
    $state = static function ($complete, $current) {
        return $complete ? 'is-complete' : ($current ? 'is-current' : 'is-pending');
    };
    $steps = [
        ['label' => 'Diajukan', 'state' => $status !== '' ? 'is-complete' : 'is-current'],
        ['label' => 'Kaprodi', 'state' => $state(($p->status_kaprodi ?? '') === 'Disetujui', $status === 'Menunggu ACC Kaprodi')],
        ['label' => 'Laboran', 'state' => $state(($p->status_laboran ?? '') === 'Disetujui', in_array($status, ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran', 'Menunggu Persetujuan'], true))],
        ['label' => 'Kaur', 'state' => $state(($p->status_kaur ?? '') === 'Disetujui', $status === 'Menunggu ACC Kaur')],
        ['label' => 'Final QR', 'state' => $state((int) ($p->qr_locked ?? 0) === 1 || !empty($p->qr_finalized_at), $status === 'Disetujui (Menunggu Finalisasi QR)')],
        ['label' => 'Selesai', 'state' => $state($status === 'Dikembalikan', in_array($status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam'], true))],
    ];
    $current_index = null;
    foreach ($steps as $index => $step) {
        if ($step['state'] === 'is-current') {
            $current_index = $index;
            break;
        }
    }
    if ($current_index === null) {
        foreach ($steps as $index => $step) {
            if ($step['state'] === 'is-pending') {
                $current_index = $index;
                break;
            }
        }
    }
    return [
        'steps' => $steps,
        'current_label' => $current_index !== null ? $steps[$current_index]['label'] : 'Selesai',
        'current_index' => $current_index !== null ? $current_index : count($steps) - 1,
        'status' => $status,
    ];
};
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pengajuan = isset($pengajuan) && is_array($pengajuan) ? $pengajuan : [];
$approval_total = count($pengajuan);
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
        .approval-table thead th { font-size: .76rem; font-family: inherit; font-weight: 700 !important; text-transform: uppercase; letter-spacing: .04em; color: #111827 !important; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .approval-table thead th, .approval-table thead th * { color: #111827 !important; font-weight: 700 !important; }
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
        .asset-list, .asset-quantity-list { max-width: 320px; }
        .asset-item, .asset-quantity-item { min-height: 30px; display: flex; align-items: center; padding: 3px 0; border-bottom: 1px solid #f0f1f3; font-size: .86rem; }
        .asset-quantity-item { white-space: nowrap; }
        .asset-item:last-child, .asset-quantity-item:last-child { border-bottom: 0; }
        .action-cell { min-width: 260px; }
        .action-grid { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .empty-state { min-height: 280px; display: grid; place-items: center; text-align: center; color: #6c757d; }
        .approval-progress { width:min(100%, 320px); min-width:250px; }
        .approval-progress__heading { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:7px; }
        .approval-progress__current { min-width:0; color:#273444; font-size:.72rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .approval-progress__count { flex:0 0 auto; color:#7b848d; font-size:.64rem; font-weight:600; white-space:nowrap; }
        .approval-progress__track { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); align-items:center; min-width:0; }
        .approval-progress__unit { position:relative; display:flex; align-items:center; min-width:0; height:16px; }
        .approval-progress__unit:not(:last-child)::after { content:""; position:absolute; top:50%; left:50%; right:-50%; height:1px; background:#dfe3e7; transform:translateY(-50%); }
        .approval-progress__dot { position:relative; z-index:1; width:12px; height:12px; flex:0 0 12px; border:1px solid #cbd2da; border-radius:50%; background:#fff; }
        .approval-progress__unit.is-complete .approval-progress__dot { display:inline-flex; align-items:center; justify-content:center; border-color:#a3cfbb; background:#eaf7f0; }
        .approval-progress__unit.is-complete .approval-progress__dot::before { content:""; width:4px; height:2px; border-left:1px solid #146c43; border-bottom:1px solid #146c43; transform:rotate(-45deg) translate(1px, -1px); }
        .approval-progress__unit.is-current .approval-progress__dot { border:2px solid #ea5b1a; background:#fff2e9; }
        .approval-progress__unit.is-current .approval-progress__dot::before { content:""; width:4px; height:4px; border-radius:50%; background:#ea5b1a; }
        .approval-progress__unit.is-rejected .approval-progress__dot { border-color:#dc3545; background:#fbeaec; }
        .approval-progress__unit.is-rejected .approval-progress__dot::before { content:""; width:5px; height:1px; background:#dc3545; transform:rotate(-45deg); }
        .approval-progress__status { display:inline-flex; align-items:center; max-width:100%; margin-top:8px; padding:3px 8px; border:1px solid #dfe3e7; border-radius:999px; color:#5f6368; background:#f8f9fa; font-size:.64rem; font-weight:600; line-height:1.2; white-space:normal; }
        .approval-progress__status.status-Menunggu-Persetujuan,
        .approval-progress__status.status-Menunggu-ACC-Kaprodi,
        .approval-progress__status.status-Menunggu-Verifikasi-Laboran,
        .approval-progress__status.status-Menunggu-Pengecekan-Laboran { border-color:#f2cf85; color:#8a5800; background:#fff8e8; }
        .approval-progress__status.status-Menunggu-ACC-Kaur { border-color:#b8d5ff; color:#0759bd; background:#f0f6ff; }
        .approval-progress__status.status-Disetujui-Menunggu-Pengambilan- { border-color:#a3cfbb; color:#146c43; background:#f0faf4; }
        .approval-progress__status.status-Ditolak { border-color:#f1aeb5; color:#b02a37; background:#fff4f5; }
        .approval-evidence-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-width:132px; min-height:36px; padding:7px 12px; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .loan-pagination-footer { display:grid; grid-template-columns:minmax(0, auto) 1fr minmax(0, auto); align-items:center; gap:1rem; min-height:64px; padding:.75rem 1rem; border-top:1px solid #e8eaed; color:#6b7280; background:#f8f9fa; }
        .loan-pagination-summary { display:flex; align-items:center; flex-wrap:wrap; gap:.55rem; }
        .loan-pagination-summary, .loan-pagination-status { font-size:.72rem; white-space:nowrap; }
        .loan-pagination-summary .form-select { width:92px; min-height:34px; padding-top:.3rem; padding-bottom:.3rem; font-size:.72rem; }
        .loan-pagination-status { text-align:center; }
        .loan-pagination { margin:0; }
        .loan-pagination .page-link { display:inline-flex; align-items:center; justify-content:center; min-width:34px; min-height:34px; padding:.35rem .58rem; border-color:#e8eaed; color:#202124; background:#fff; font-size:.72rem; line-height:1; transition:color .16s ease, background-color .16s ease, border-color .16s ease; }
        .loan-pagination .page-link:hover { color:#ea5b1a; background:#fff7f2; }
        .loan-pagination .page-item.active .page-link { color:#fff; background:#ea5b1a; border-color:#ea5b1a; }
        .loan-pagination .page-item.disabled .page-link { color:#9aa0a6; background:#f8f9fa; opacity:.62; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn:not(.notif-bell) { flex: 1 1 calc(50% - 8px); }
            .approval-table { min-width: 980px; }
            .soft-badge { min-width: 220px; max-width: 220px; }
            .approval-progress { width:250px; min-width:0; }
            .loan-pagination-footer { grid-template-columns:1fr; justify-items:center; gap:.65rem; }
            .loan-pagination-footer nav { max-width:100%; overflow-x:auto; padding-bottom:2px; }
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
                    <h1 class="h5 fw-bold mb-0">Daftar Pengajuan Menunggu Pengecekan</h1>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-warning text-dark px-3 py-2"><?= count($pengajuan) ?> menunggu</span>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
                </div>
            </div>
        </section>

        <?php
        $multi_filter_id = 'approvalMultiFilter';
        $multi_filter_mode = 'client';
        $multi_filter_fields = [
            'peminjam' => ['label' => 'Peminjam / NIM', 'placeholder' => 'Cari nama peminjam atau NIM/NIP'],
            'barang' => ['label' => 'Nama barang / kode', 'placeholder' => 'Cari barang yang diajukan'],
            'jumlah' => ['label' => 'Jumlah', 'placeholder' => 'Cari jumlah unit', 'type' => 'number'],
            'masa' => ['label' => 'Tanggal pinjam', 'placeholder' => 'Pilih tanggal pinjam', 'type' => 'date'],
            'keperluan' => ['label' => 'Keperluan', 'placeholder' => 'Cari keperluan peminjaman'],
            'status' => ['label' => 'Status', 'placeholder' => 'Cari status approval'],
        ];
        include APPPATH . 'views/admin/_multi_filter.php';
        ?>

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
                                <th>Jumlah</th>
                                <th>Masa Pinjam</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pengajuan as $index => $p):
                                $approval_items = []; $approval_quantities = [];
                                foreach (($p->detail_barang ?? []) as $filter_item) { $approval_items[] = ($filter_item->nama_aset ?? '') . ' ' . ($filter_item->kode_aset ?? ''); $approval_quantities[] = (string) ($filter_item->jumlah_pinjam ?? 0); }
                            ?>
                                <tr class="approval-data-row" data-filter-peminjam="<?= html_escape(($p->nama_peminjam ?? '') . ' ' . ($p->nim_nip ?? '')) ?>" data-filter-barang="<?= html_escape(implode(' ', $approval_items)) ?>" data-filter-jumlah="<?= html_escape(implode(' ', $approval_quantities)) ?>" data-filter-masa="<?= html_escape(($p->tanggal_pinjam ?? '') . ' ' . ($p->tanggal_kembali_rencana ?? '')) ?>" data-filter-keperluan="<?= html_escape($p->keperluan ?? '') ?>" data-filter-status="<?= html_escape(($p->status ?? '') . ' ' . ($p->status_laboran ?? '')) ?>">
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
                                                </div>
                                            <?php endforeach; else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="asset-quantity-list">
                                            <?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?>
                                                <div class="asset-quantity-item"><?= (int)($d->jumlah_pinjam ?? 0) ?> unit</div>
                                            <?php endforeach; else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span tabindex="0" data-bs-toggle="tooltip" title="<?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>"><div><?= tanggal_indonesia($p->tanggal_pinjam ?? null) ?></div><div class="small text-muted">s.d. <?= tanggal_indonesia($p->tanggal_kembali_rencana ?? null) ?></div></span>
                                    </td>
                                    <td>
                                        <div class="small" style="max-width: 260px; white-space: normal;"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div>
                                    </td>
                                    <?php $progress = $approval_progress($p); ?>
                                    <td>
                                        <div class="approval-progress" aria-label="Progress approval">
                                            <div class="approval-progress__heading">
                                                <span class="approval-progress__current"><?= html_escape($progress['current_label']) ?></span>
                                                <span class="approval-progress__count">Tahap <?= (int) $progress['current_index'] + 1 ?> dari 6</span>
                                            </div>
                                            <div class="approval-progress__track" aria-hidden="true">
                                                <?php foreach ($progress['steps'] as $step): ?>
                                                    <span class="approval-progress__unit <?= html_escape($step['state']) ?>" title="<?= html_escape($step['label']) ?>"><span class="approval-progress__dot"></span></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <span class="approval-progress__status <?= $status_class($progress['status']) ?>"><?= html_escape($progress['status'] ?: '-') ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3 action-cell">
                                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2"><?php if(!empty($p->foto_bukti)): ?><button type="button" class="btn btn-sm btn-outline-secondary approval-evidence-btn" data-bs-toggle="modal" data-bs-target="#evidenceModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-image" aria-hidden="true"></i><span>Bukti kondisi</span></button><?php endif; ?><button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" data-bs-toggle="modal" data-bs-target="#processModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-sliders me-1"></i> Proses</button></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="loan-pagination-footer" id="approvalPaginationFooter" data-total="<?= $approval_total ?>">
                    <div class="loan-pagination-summary">
                        <label for="approvalPageSize">Tampilkan:</label>
                        <select id="approvalPageSize" class="form-select form-select-sm" aria-label="Jumlah data approval per halaman">
                            <option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="all">Semua</option>
                        </select>
                        <span id="approvalTotalItems">Total item: <?= $approval_total ?></span>
                    </div>
                    <div class="loan-pagination-status" id="approvalPageStatus">Halaman: 1 dari 1</div>
                    <nav aria-label="Pagination approval"><ul class="pagination pagination-sm loan-pagination" id="approvalPageNav"></ul></nav>
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
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        (function () {
            const rows = Array.from(document.querySelectorAll('.approval-data-row'));
            const select = document.getElementById('approvalPageSize');
            const filterRoot = document.getElementById('approvalMultiFilter');
            const status = document.getElementById('approvalPageStatus');
            const nav = document.getElementById('approvalPageNav');
            const totalItems = document.getElementById('approvalTotalItems');
            if (!rows.length || !select || !status || !nav) return;
            let page = 1;
            const pageSize = () => select.value === 'all' ? Math.max(rows.length, 1) : Number(select.value) || 10;
            const button = (label, target, disabled, active) => {
                const li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const a = document.createElement('a');
                a.className = 'page-link'; a.href = '#'; a.textContent = label;
                a.setAttribute('aria-label', label);
                if (!disabled) a.addEventListener('click', function (event) { event.preventDefault(); page = target; render(); });
                li.appendChild(a); nav.appendChild(li);
            };
            function render() {
                const filtered = rows.filter(row => !filterRoot || AdminMultiFilter.matches(row, AdminMultiFilter.getCriteria(filterRoot)));
                const size = select.value === 'all' ? Math.max(filtered.length, 1) : Number(select.value) || 10;
                const totalPages = Math.max(1, Math.ceil(filtered.length / size));
                page = Math.min(page, totalPages);
                const visible = new Set(filtered.slice((page - 1) * size, page * size));
                rows.forEach(row => { row.hidden = !visible.has(row); });
                status.textContent = 'Halaman: ' + page + ' dari ' + totalPages;
                if (totalItems) totalItems.textContent = 'Total item: ' + filtered.length;
                nav.innerHTML = '';
                button('Previous', Math.max(1, page - 1), page === 1, false);
                for (let index = 1; index <= totalPages; index += 1) button(String(index), index, false, index === page);
                button('Next', Math.min(totalPages, page + 1), page === totalPages, false);
            }
            select.addEventListener('change', function () { page = 1; render(); });
            filterRoot?.addEventListener('admin-multi-filter-change', function () { page = 1; render(); });
            render();
        }());
    </script>
</body>
</html>
