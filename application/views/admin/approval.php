<?php
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pengajuan = isset($pengajuan) && is_array($pengajuan) ? $pengajuan : [];
$approval_total = (int) ($approval_total ?? count($pengajuan));
$approval_actionable = (int) ($approval_actionable ?? count(array_filter($pengajuan, static function ($p) { return scm_loan_can_act($p, 'laboran'); })));
$approval_page_actionable = count(array_filter($pengajuan, static function ($p) { return scm_loan_can_act($p, 'laboran'); }));
$page = max(1, (int) ($page ?? 1));
$per_page = (int) ($per_page ?? 10);
$total_pages = max(1, (int) ($total_pages ?? 1));
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
    <link rel="stylesheet" href="<?= base_url('assets/css/loan-progress.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/loan-progress.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/approval-bulk-select.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/approval-bulk-select.css'); ?>">
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
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
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
                    <h1 class="h5 fw-bold mb-0">Progress Seluruh Peminjaman</h1>
                    <p class="small text-muted mb-0 mt-1">Semua pengajuan terlihat; aksi Laboran hanya aktif pada tahap verifikasi.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill text-bg-warning text-dark px-3 py-2"><?= $approval_actionable ?> perlu aksi</span>
                    <span class="badge rounded-pill text-bg-light border px-3 py-2"><?= $approval_total ?> total</span>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
                </div>
            </div>
        </section>

        <?php
        $multi_filter_id = 'approvalMultiFilter';
        $multi_filter_mode = 'server';
        $multi_filter_fields = [
            'peminjam' => ['label' => 'Peminjam / NIM', 'placeholder' => 'Cari nama peminjam atau NIM/NIP'],
            'barang' => ['label' => 'Nama barang / kode', 'placeholder' => 'Cari barang yang diajukan'],
            'jumlah' => ['label' => 'Jumlah', 'placeholder' => 'Cari jumlah unit', 'type' => 'number'],
            'masa' => ['label' => 'Tanggal pinjam', 'placeholder' => 'Pilih tanggal pinjam', 'type' => 'date'],
            'keperluan' => ['label' => 'Keperluan', 'placeholder' => 'Cari keperluan peminjaman'],
            'status' => ['label' => 'Status', 'placeholder' => 'Cari status approval'],
        ];
        $multi_filter_rows = $multi_filter_rows ?? [['field' => 'peminjam', 'value' => '']];
        $multi_filter_action = base_url('index.php/admin/approval');
        $multi_filter_hidden = ['per_page' => $per_page, 'page' => 1];
        include APPPATH . 'views/admin/_multi_filter.php';
        ?>

        <section class="panel-card overflow-hidden" data-bulk-approval>
            <?php if(empty($pengajuan)): ?>
                <div class="empty-state p-5">
                    <div>
                        <i class="bi bi-check-circle display-5 d-block mb-3 text-success"></i>
                        <h2 class="h5 fw-bold mb-1">Belum ada data peminjaman</h2>
                        <p class="mb-0">Pengajuan baru akan tampil di sini sejak tahap Kaprodi.</p>
                    </div>
                </div>
            <?php else: ?>
                <form id="laboranBulkForm" method="post" action="<?= base_url('index.php/admin/approval/bulk') ?>" class="approval-bulk-toolbar m-3" data-bulk-form data-bulk-toolbar hidden>
                    <input type="hidden" name="bulk_note" value="">
                    <span class="approval-bulk-toolbar__count" data-bulk-count>0 data terpilih</span>
                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success rounded-pill px-3" data-bulk-approve-action><i class="bi bi-check2-circle me-1"></i>Setujui Terpilih</button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#laboranBulkRejectModal"><i class="bi bi-x-circle me-1"></i>Tolak Terpilih</button>
                    <button type="submit" name="action" value="reject" data-bulk-reject-action hidden></button>
                </form>
                <div class="table-wrap">
                    <table class="table table-hover approval-table mb-0">
                        <thead>
                            <tr>
                                <th class="approval-bulk-cell"><label class="approval-bulk-select-all" title="Pilih semua data yang dapat diproses pada halaman ini"><input type="checkbox" class="form-check-input approval-bulk-check" data-bulk-select-all <?= $approval_page_actionable > 0 ? '' : 'disabled' ?>> <span>Select All</span></label></th>
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
                                $can_laboran_act = scm_loan_can_act($p, 'laboran');
                            ?>
                                <tr class="approval-data-row" data-filter-peminjam="<?= html_escape(($p->nama_peminjam ?? '') . ' ' . ($p->nim_nip ?? '')) ?>" data-filter-barang="<?= html_escape(implode(' ', $approval_items)) ?>" data-filter-jumlah="<?= html_escape(implode(' ', $approval_quantities)) ?>" data-filter-masa="<?= html_escape(($p->tanggal_pinjam ?? '') . ' ' . ($p->tanggal_kembali_rencana ?? '')) ?>" data-filter-keperluan="<?= html_escape($p->keperluan ?? '') ?>" data-filter-status="<?= html_escape(($p->status ?? '') . ' ' . ($p->status_laboran ?? '')) ?>">
                                    <td class="approval-bulk-cell"><input type="checkbox" class="form-check-input approval-bulk-check" name="loan_ids[]" value="<?= (int) $p->id_peminjaman ?>" form="laboranBulkForm" data-bulk-row aria-label="Pilih pengajuan <?= (int) $p->id_peminjaman ?>" <?= $can_laboran_act ? '' : 'disabled title="Belum berada pada tahap Laboran"' ?>></td>
                                    <td class="ps-3 fw-semibold text-muted"><?= (($page - 1) * $per_page) + $index + 1 ?></td>
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
                                    <td>
                                        <?php $loan_progress_item = $p; $loan_progress_compact = true; include APPPATH . 'views/shared/loan_progress.php'; ?>
                                    </td>
                                    <td class="text-end pe-3 action-cell">
                                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2"><?php if(!empty($p->foto_bukti)): ?><button type="button" class="btn btn-sm btn-outline-secondary approval-evidence-btn" data-bs-toggle="modal" data-bs-target="#evidenceModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-image" aria-hidden="true"></i><span>Bukti kondisi</span></button><?php endif; ?><button class="btn btn-sm btn-outline-primary rounded-pill px-3" type="button" <?= $can_laboran_act ? 'data-bs-toggle="modal" data-bs-target="#processModal'.(int)$p->id_peminjaman.'"' : 'disabled title="Aksi tersedia saat memasuki tahap Laboran"'; ?>><i class="bi bi-sliders me-1"></i> Proses</button></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal fade" id="laboranBulkRejectModal" tabindex="-1" aria-labelledby="laboranBulkRejectTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                        <div class="modal-header"><h2 class="modal-title h5 fw-bold" id="laboranBulkRejectTitle">Tolak Pengajuan Terpilih</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body"><label for="laboranBulkRejectReason" class="form-label fw-semibold">Alasan penolakan</label><textarea id="laboranBulkRejectReason" class="form-control" rows="4" placeholder="Tuliskan alasan yang akan diterapkan pada seluruh pengajuan terpilih." data-bulk-reject-reason></textarea><div class="form-text">Reservasi stok pada pengajuan yang berhasil ditolak akan dilepas.</div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-danger rounded-pill px-4" data-bulk-reject-submit>Tolak Terpilih</button></div>
                    </div></div>
                </div>
                <div class="loan-pagination-footer" id="approvalPaginationFooter" data-total="<?= $approval_total ?>">
                    <div class="loan-pagination-summary">
                        <label for="approvalPageSize">Tampilkan:</label>
                        <select id="approvalPageSize" class="form-select form-select-sm" aria-label="Jumlah data approval per halaman" onchange="var u=new URL(window.location.href);u.searchParams.set('per_page',this.value);u.searchParams.set('page','1');window.location.href=u.toString();">
                            <?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $per_page === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
                        </select>
                        <span id="approvalTotalItems">Total item: <?= $approval_total ?></span>
                    </div>
                    <?php $first_item = $approval_total > 0 ? (($page - 1) * $per_page) + 1 : 0; $last_item = min($approval_total, $page * $per_page); ?>
                    <div class="loan-pagination-status" id="approvalPageStatus">Menampilkan <?= $first_item ?>–<?= $last_item ?> dari <?= number_format($approval_total, 0, ',', '.') ?> data</div>
                    <nav aria-label="Pagination approval"><ul class="pagination pagination-sm loan-pagination" id="approvalPageNav">
                        <?php $approval_query = $_GET; $approval_query['per_page'] = $per_page; ?>
                        <?php $approval_query['page'] = max(1, $page - 1); ?><li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($approval_query) ?>">Previous</a></li>
                        <?php $start_page = max(1, $page - 2); $end_page = min($total_pages, $start_page + 4); $start_page = max(1, $end_page - 4); for ($i = $start_page; $i <= $end_page; $i++): $approval_query['page'] = $i; ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($approval_query) ?>"><?= $i ?></a></li>
                        <?php endfor; $approval_query['page'] = min($total_pages, $page + 1); ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($approval_query) ?>">Next</a></li>
                    </ul></nav>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php foreach($pengajuan as $p): $can_laboran_act = scm_loan_can_act($p, 'laboran'); ?>
        <div class="modal fade" id="processModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Aksi Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!$can_laboran_act): ?><div class="alert alert-info small">Pengajuan ini tetap terlihat untuk pemantauan. Aksi Laboran akan aktif setelah disetujui Kaprodi.</div><?php endif; ?>
                        <div class="mb-3">
                            <div class="small text-muted">Peminjam</div>
                            <div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?> - <?= html_escape($p->nim_nip ?? '-') ?></div>
                        </div>
                        <label class="form-label small fw-semibold">Catatan Laboran</label>
                        <textarea name="catatan_laboran" class="form-control" rows="3" placeholder="Catatan pengecekan stok atau alasan penolakan." <?= $can_laboran_act ? '' : 'disabled'; ?>></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button formaction="<?= base_url('index.php/admin/approval/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-4" onclick="return confirm('Tolak pengajuan ini?')" <?= $can_laboran_act ? '' : 'disabled'; ?>><i class="bi bi-x-lg me-1"></i> Tolak</button>
                        <button formaction="<?= base_url('index.php/admin/approval/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-4" onclick="return confirm('Teruskan pengajuan ini ke Kaur?')" <?= $can_laboran_act ? '' : 'disabled'; ?>><i class="bi bi-send-check me-1"></i> Teruskan ke Kaur</button>
                    </div>
                </form>
            </div>
        </div>
        <?php if(!empty($p->foto_bukti)): ?><?php $approval_evidence_url = scm_upload_url($p->foto_bukti, 'assets/uploads/bukti_peminjaman'); $approval_evidence_exists = scm_upload_exists($p->foto_bukti, 'assets/uploads/bukti_peminjaman'); ?><div class="modal fade" id="evidenceModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold">Bukti Kondisi Awal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body text-center bg-light"><?php if($approval_evidence_exists): ?><img class="img-fluid rounded-3" style="max-height:70vh;object-fit:contain" src="<?= html_escape($approval_evidence_url) ?>" alt="Bukti kondisi awal"><?php else: ?><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File bukti kondisi tidak ditemukan di penyimpanan.</div><?php endif; ?></div></div></div></div><?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/approval-bulk-select.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/approval-bulk-select.js'); ?>"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
<<<<<<< HEAD
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
            const compactPageTokens = (totalPages, currentPage) => {
                if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);
                if (currentPage <= 3) return [1, 2, 3, 4, 5, 'ellipsis', totalPages];
                if (currentPage >= totalPages - 2) return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
                return [1, 'ellipsis', currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, 'ellipsis', totalPages];
            };
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
                compactPageTokens(totalPages, page).forEach((token) => {
                    if (typeof token === 'string') {
                        const item = document.createElement('li');
                        item.className = 'page-item disabled';
                        item.setAttribute('aria-hidden', 'true');
                        const separator = document.createElement('span');
                        separator.className = 'page-link';
                        separator.textContent = '...';
                        item.appendChild(separator);
                        nav.appendChild(item);
                    } else {
                        button(String(token), token, false, token === page);
                    }
                });
                button('Next', Math.min(totalPages, page + 1), page === totalPages, false);
            }
            select.addEventListener('change', function () { page = 1; render(); });
            filterRoot?.addEventListener('admin-multi-filter-change', function () { page = 1; render(); });
            render();
        }());
=======
>>>>>>> origin/main
    </script>
</body>
</html>
