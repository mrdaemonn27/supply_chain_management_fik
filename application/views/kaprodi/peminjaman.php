<?php
$pengajuan = is_array($pengajuan ?? null) ? $pengajuan : [];
$pengembalian = is_array($pengembalian ?? null) ? $pengembalian : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title ?? 'Approval Peminjaman Kaprodi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f5f6f8; color:#202124; font-family:Poppins,sans-serif; }
        .topbar { background:#1f1f1f; border-bottom:4px solid #ea5b1a; color:#fff; }
        .panel-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 8px 24px rgba(32,33,36,.05); }
        .loan-stepper { display:grid; grid-template-columns:repeat(6,minmax(100px,1fr)); gap:8px; min-width:680px; }
        .loan-step { padding:9px 10px; border:1px solid #dfe3e7; border-radius:10px; color:#77808a; background:#f7f8f9; font-size:.72rem; font-weight:600; text-align:center; }
        .loan-step.is-done { color:#146c43; border-color:#a3cfbb; background:#eaf7f0; }
        .loan-step.is-active { color:#9a450e; border-color:#ea5b1a; background:#fff2e9; box-shadow:inset 0 0 0 1px #ea5b1a; }
        .table > :not(caption) > * > * { vertical-align:middle; }
        .status-pill { display:inline-flex; align-items:center; min-height:28px; padding:5px 10px; border-radius:999px; color:#9a450e; background:#fff2e9; font-size:.72rem; font-weight:700; }
        .empty-filter { display:none; }
    </style>
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body>
<header class="topbar sticky-top"><div class="container-fluid px-3 px-lg-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><div class="fw-bold"><i class="bi bi-patch-check me-2 text-warning"></i>Approval Peminjaman Kaprodi</div><div class="small text-white-50">Tahap pertama sebelum pengecekan Laboran</div></div><div class="d-flex gap-2"><a href="<?= base_url('index.php/kaprodi/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-grid me-1"></i> Dashboard</a><a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-warning rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></div></div></header>
<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')) ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
    <section class="panel-card p-3 p-lg-4 mb-4">
        <div class="row g-3 align-items-end"><div class="col-lg-8"><label for="kaprodiLoanSearch" class="form-label small fw-semibold">Cari peminjam, barang, status, atau tanggal</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="kaprodiLoanSearch" type="search" class="form-control" placeholder="Ketik untuk filter langsung..." autocomplete="off"></div></div><div class="col-lg-4 text-lg-end"><span class="status-pill"><?= count($pengajuan) ?> menunggu keputusan</span></div></div>
    </section>
    <section class="panel-card overflow-hidden mb-4">
        <div class="p-3 border-bottom"><h1 class="h5 fw-bold mb-1">Pengajuan Menunggu ACC</h1><div class="small text-muted">Urutan: Kaprodi → Laboran → Kaur → finalisasi QR Laboran.</div></div>
        <div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Peminjam</th><th>Nama Barang</th><th>Masa Pinjam</th><th>Alur Approval</th><th class="text-end">Aksi</th></tr></thead><tbody data-filter-body>
        <?php if(empty($pengajuan)): ?><tr><td colspan="5" class="text-center text-muted py-5">Tidak ada pengajuan menunggu ACC Kaprodi.</td></tr><?php else: foreach($pengajuan as $p): ?>
            <?php $names=[]; foreach(($p->detail_barang ?? []) as $d){$names[] = ($d->nama_aset ?? '-') . ' — ' . (int)($d->jumlah_pinjam ?? 0) . ' unit';} $search_text = implode(' ', [$p->nama_peminjam ?? '', $p->nim_nip ?? '', implode(' ', $names), $p->status ?? '', tanggal_indonesia($p->tanggal_pinjam ?? null), tanggal_indonesia($p->tanggal_kembali_rencana ?? null)]); ?>
            <tr data-filter-row data-search="<?= html_escape(strtolower($search_text)) ?>"><td><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td><td><?= html_escape(implode(', ', $names) ?: '-') ?></td><td><span data-bs-toggle="tooltip" title="<?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>"><?= tanggal_indonesia($p->tanggal_pinjam ?? null) ?></span></td><td><div class="overflow-auto"><div class="loan-stepper"><span class="loan-step is-done">Diajukan</span><span class="loan-step is-active">Kaprodi</span><span class="loan-step">Laboran</span><span class="loan-step">Kaur</span><span class="loan-step">Final QR</span><span class="loan-step">Selesai</span></div></div></td><td class="text-end"><button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#kaprodiApproval<?= (int)$p->id_peminjaman ?>"><i class="bi bi-eye me-1"></i> Tinjau</button></td></tr>
        <?php endforeach; endif; ?><tr class="empty-filter"><td colspan="5" class="text-center text-muted py-4">Tidak ada hasil yang cocok.</td></tr></tbody></table></div>
    </section>
    <section class="panel-card overflow-hidden">
        <div class="p-3 border-bottom"><h2 class="h5 fw-bold mb-1">Status Pengembalian (Read-only)</h2><div class="small text-muted">Pengembalian hanya dikonfirmasi Laboran; Kaprodi tidak memiliki tombol approve/tolak.</div></div>
        <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Peminjam</th><th>Nama Barang</th><th>Masa Pinjam</th><th>Status Pengembalian</th></tr></thead><tbody data-filter-body><?php if(empty($pengembalian)): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada data pengembalian.</td></tr><?php else: foreach($pengembalian as $p): ?><?php $names=[]; foreach(($p->detail_barang ?? []) as $d){$names[]=$d->nama_aset ?? '-';} $search_text=implode(' ',[$p->nama_peminjam ?? '',implode(' ',$names),$p->status ?? '',$p->tanggal_pinjam ?? '']); ?><tr data-filter-row data-search="<?= html_escape(strtolower($search_text)) ?>"><td><?= html_escape($p->nama_peminjam ?? '-') ?></td><td><?= html_escape(implode(', ',$names) ?: '-') ?></td><td><?= masa_pinjam_indonesia($p->tanggal_pinjam ?? null,$p->tanggal_kembali_rencana ?? null) ?></td><td><span class="status-pill"><?= html_escape($p->status ?? '-') ?></span></td></tr><?php endforeach; endif; ?><tr class="empty-filter"><td colspan="4" class="text-center text-muted py-4">Tidak ada hasil yang cocok.</td></tr></tbody></table></div>
    </section>
</main>
<?php foreach($pengajuan as $p): ?><div class="modal fade" id="kaprodiApproval<?= (int)$p->id_peminjaman ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content" action="<?= base_url('index.php/kaprodi/peminjaman/setujui/'.$p->id_peminjaman) ?>"><div class="modal-header"><h2 class="modal-title h5 fw-bold">Keputusan Kaprodi</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><div class="small text-muted">Peminjam</div><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?> — <?= html_escape($p->nim_nip ?? '-') ?></div></div><label class="form-label small fw-semibold">Catatan</label><textarea name="catatan_kaprodi" class="form-control" rows="3" placeholder="Catatan persetujuan atau alasan penolakan"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button formaction="<?= base_url('index.php/kaprodi/peminjaman/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Tolak pengajuan ini? Pastikan alasan sudah diisi.')"><i class="bi bi-x-lg me-1"></i> Tolak</button><button class="btn btn-success rounded-pill" onclick="return confirm('Setujui dan teruskan ke Laboran?')"><i class="bi bi-check2 me-1"></i> Setujui</button></div></form></div></div><?php endforeach; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));const search=document.getElementById('kaprodiLoanSearch');if(search){search.addEventListener('input',()=>{const q=search.value.trim().toLocaleLowerCase('id');document.querySelectorAll('[data-filter-body]').forEach(body=>{let visible=0;body.querySelectorAll('[data-filter-row]').forEach(row=>{const show=!q||(row.dataset.search||'').includes(q);row.classList.toggle('d-none',!show);if(show)visible++;});const empty=body.querySelector('.empty-filter');if(empty)empty.style.display=visible?'none':'table-row';});});}</script>
</body></html>
