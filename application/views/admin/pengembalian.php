<?php
$status_options = isset($status_options) && is_array($status_options) ? $status_options : ['', 'Sedang Dipinjam', 'Dipinjam', 'Terlambat'];
$status_class = function ($status) { return 'status-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($status ?: 'Sedang Dipinjam')); };
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($peminjaman ?? []), 'per_page' => 10];
$current_per_page = (string) ($pagination['per_page'] ?? '10');
$filter_rows = isset($filter_rows) && is_array($filter_rows) ? array_values($filter_rows) : [];
if (empty($filter_rows)) $filter_rows = [['field' => 'peminjam', 'value' => '']];
$filter_fields = ['peminjam' => 'Peminjam / NIM', 'barang' => 'Nama barang / kode', 'status' => 'Status', 'tanggal' => 'Tanggal pinjam', 'keperluan' => 'Keperluan'];
$filter_suggestions = isset($filter_suggestions) && is_array($filter_suggestions) ? $filter_suggestions : [];
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
        .table thead th { font-size: .78rem; font-family: inherit; font-weight: 700 !important; text-transform: uppercase; letter-spacing: .04em; color: #111827 !important; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table thead th, .table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .table td { vertical-align: middle; }
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 600; font-size: .75rem; }
        .status-Sedang-Dipinjam, .status-Dipinjam { background: rgba(13,110,253,.12); color: #0d6efd; }
        .status-Terlambat { background: rgba(220,53,69,.12); color: #dc3545; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .admin-filter-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
        .admin-filter-heading h2 { margin:0; font-size:1rem; font-weight:700; }
        .admin-filter-heading p { margin:0; color:#6b7280; font-size:.78rem; }
        .admin-filter-list { display:grid; gap:.65rem; }
        .admin-filter-row { display:grid; grid-template-columns:minmax(185px, .7fr) minmax(0, 1.5fr) auto; gap:.65rem; align-items:center; }
        .admin-filter-row .form-select, .admin-filter-row .form-control { min-height:42px; }
        .admin-filter-tools { display:flex; align-items:center; gap:.45rem; }
        .admin-filter-icon { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; }
        .admin-filter-add { border-color:#ea5b1a; color:#c84f17; }
        .admin-filter-add:hover { background:#ea5b1a; border-color:#ea5b1a; color:#fff; }
        .admin-filter-actions { display:flex; flex-wrap:wrap; gap:.65rem; margin-top:1rem; }
        .admin-filter-actions .btn { min-height:40px; }
        .admin-filter-note { color:#6b7280; font-size:.74rem; }
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
        @media (max-width:767.98px) { .loan-pagination-footer { grid-template-columns:1fr; justify-items:center; gap:.65rem; } .loan-pagination-footer nav { max-width:100%; overflow-x:auto; padding-bottom:2px; } }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; }
            .topbar-actions .btn:not(.notif-bell) { flex: 1; }
            .admin-filter-row { grid-template-columns:1fr; gap:.45rem; padding:.75rem; border:1px solid #edf0f2; border-radius:10px; }
            .admin-filter-tools { justify-content:flex-end; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Data Pengembalian</div>
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
            <form id="adminFilters" method="get" action="<?= base_url('index.php/admin/pengembalian') ?>" data-max-filters="4">
                <input type="hidden" name="per_page" value="<?= html_escape($current_per_page) ?>">
                <div class="admin-filter-heading"><h2><i class="bi bi-funnel me-2 text-warning"></i>Filter pencarian</h2></div>
                <div id="adminFilterRows" class="admin-filter-list">
                    <?php foreach($filter_rows as $index => $filter_row): ?>
                    <div class="admin-filter-row">
                        <select name="filter_field[]" class="form-select admin-filter-field" aria-label="Jenis filter <?= $index + 1 ?>"><?php foreach($filter_fields as $field_key => $field_label): ?><option value="<?= $field_key ?>" <?= (($filter_row['field'] ?? '') === $field_key) ? 'selected' : '' ?>><?= $field_label ?></option><?php endforeach; ?></select>
                        <input type="search" name="filter_value[]" class="form-control admin-filter-value" value="<?= html_escape($filter_row['value'] ?? '') ?>" placeholder="Ketik untuk mencari" autocomplete="off" aria-label="Nilai filter <?= $index + 1 ?>">
                        <div class="admin-filter-tools"><button type="button" class="btn btn-outline-secondary admin-filter-icon admin-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg"></i></button><button type="button" class="btn btn-outline-primary admin-filter-icon admin-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg"></i></button></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="admin-filter-actions"><button class="btn btn-fik px-4"><i class="bi bi-search me-1"></i>Terapkan filter</button><a href="<?= base_url('index.php/admin/pengembalian?per_page='.rawurlencode($current_per_page)) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a></div>
            </form>
        </section>

        <section class="panel-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th class="ps-3">No</th><th>Peminjam</th><th>Barang</th><th>Jadwal</th><th>Status</th><th class="text-end pe-3">Aksi</th></tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada transaksi aktif untuk pengembalian.</td></tr>
                    <?php else: foreach($peminjaman as $index => $p): ?>
                        <?php $is_late = !empty($p->tanggal_kembali_rencana) && strtotime($p->tanggal_kembali_rencana) < strtotime(date('Y-m-d')); ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted"><?= (($pagination['page'] ?? 1) - 1) * max(1, (int) ($pagination['per_page'] ?? count($peminjaman))) + $index + 1 ?></td>
                            <td class="ps-3"><div class="fw-semibold"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="fw-semibold"><?= (int)($p->total_jenis ?? 1) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="small text-muted"><?php if(!empty($p->detail_barang)): foreach($p->detail_barang as $d): ?><?= html_escape($d->nama_aset) ?> (<?= (int)$d->jumlah_pinjam ?>), <?php endforeach; else: ?>- <?php endif; ?></div></td>
                            <td><div><?= html_escape($p->tanggal_pinjam ?? '-') ?></div><div class="small <?= $is_late ? 'text-danger fw-semibold' : 'text-muted' ?>">s.d. <?= html_escape($p->tanggal_kembali_rencana ?? '-') ?><?= $is_late ? ' - Terlambat' : '' ?></div></td>
                            <td><span class="soft-badge <?= $is_late ? 'status-Terlambat' : $status_class($p->status ?? '') ?>"><?= $is_late ? 'Terlambat' : html_escape($p->status ?? '-') ?></span><?php if (!empty($p->evidence_serah)): ?><div class="small mt-1"><?php foreach ($p->evidence_serah as $evidence): ?><a class="d-block" href="<?= base_url($evidence->nama_file) ?>" target="_blank" rel="noopener"><i class="bi bi-image me-1"></i><?= html_escape($evidence->original_name ?: 'Evidence serah terima') ?></a><?php endforeach; ?></div><?php endif; ?></td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#evidenceModal<?= (int)$p->id_peminjaman ?>" title="Tambah dokumentasi kondisi serah terima"><i class="bi bi-camera me-1"></i> Foto</button>
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
                    'per_page' => $current_per_page,
                ];
                foreach ($filter_rows as $filter_row) {
                    $base_query['filter_field'][] = $filter_row['field'] ?? 'peminjam';
                    $base_query['filter_value'][] = $filter_row['value'] ?? '';
                }
                $page = (int) ($pagination['page'] ?? 1);
                $total_pages = (int) ($pagination['total_pages'] ?? 1);
            ?>
                <div class="loan-pagination-footer">
                    <div class="loan-pagination-summary">
                        <label for="returnPageSize">Tampilkan:</label>
                        <select id="returnPageSize" class="form-select form-select-sm" aria-label="Jumlah data pengembalian per halaman">
                            <option value="10" <?= $current_per_page === '10' ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $current_per_page === '25' ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $current_per_page === '50' ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $current_per_page === '100' ? 'selected' : '' ?>>100</option>
                        </select>
                        <span>Total item: <?= (int) ($pagination['total'] ?? 0) ?></span>
                    </div>
                    <div class="loan-pagination-status">Halaman: <?= $page ?> dari <?= $total_pages ?></div>
                    <nav aria-label="Pagination pengembalian">
                        <ul class="pagination pagination-sm loan-pagination">
                            <?php $prev_query = http_build_query(array_merge($base_query, ['page' => max(1, $page - 1)])); ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($prev_query ? '?'.$prev_query : '')) ?>">Previous</a></li>
                            <?php for($i = 1; $i <= $total_pages; $i++): $page_query = http_build_query(array_merge($base_query, ['page' => $i])); ?>
                                <li class="page-item <?= $page === $i ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($page_query ? '?'.$page_query : '')) ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php $next_query = http_build_query(array_merge($base_query, ['page' => min($total_pages, $page + 1)])); ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/pengembalian'.($next_query ? '?'.$next_query : '')) ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
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

        <div class="modal fade" id="evidenceModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content evidence-form" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/upload_evidence_serah/'.$p->id_peminjaman) ?>">
                    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-camera me-2 text-primary"></i>Dokumentasi Serah Terima</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="small text-muted">Tambahkan foto kondisi barang saat sedang dipinjam. Anda dapat memilih beberapa foto sekaligus atau mengambilnya langsung dari kamera HP.</p>
                        <input type="file" name="foto_serah[]" class="form-control evidence-file" accept="image/*,.jpg,.jpeg,.png" capture="environment" multiple required>
                        <div class="row g-2 evidence-preview mt-2"></div>
                        <div class="small text-muted mt-2">Format JPG/PNG, maksimal 5MB per foto.</div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary rounded-pill px-4"><i class="bi bi-upload me-1"></i>Simpan Foto</button></div>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const form = document.getElementById('adminFilters');
            if (!form) return;
            const rows = document.getElementById('adminFilterRows');
            const maxFilters = Number(form.dataset.maxFilters || 4);
            const fields = <?= json_encode($filter_fields, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const suggestions = <?= json_encode($filter_suggestions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            let submitTimer;

            const syncFilterInput = row => {
                const field = row.querySelector('.admin-filter-field').value;
                const input = row.querySelector('.admin-filter-value');
                const listId = `filterSuggestions${Date.now()}${Math.random().toString(16).slice(2)}`;
                row.querySelector('datalist')?.remove();
                input.removeAttribute('list');
                input.type = field === 'tanggal' ? 'date' : 'search';
                input.placeholder = field === 'tanggal' ? 'Pilih tanggal pinjam' : `Cari ${fields[field].toLowerCase()}`;
                if (field === 'tanggal') return;
                const datalist = document.createElement('datalist');
                datalist.id = listId;
                (suggestions[field] || []).slice(0, 80).forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    datalist.appendChild(option);
                });
                row.appendChild(datalist);
                input.setAttribute('list', listId);
            };

            const updateButtons = () => {
                const filterRows = Array.from(rows.querySelectorAll('.admin-filter-row'));
                filterRows.forEach((row, index) => {
                    row.querySelector('.admin-filter-remove').disabled = filterRows.length === 1;
                    row.querySelector('.admin-filter-add').disabled = filterRows.length >= maxFilters || index !== filterRows.length - 1;
                });
            };

            const addRow = (field = 'peminjam', value = '') => {
                if (rows.querySelectorAll('.admin-filter-row').length >= maxFilters) return;
                const row = document.createElement('div');
                row.className = 'admin-filter-row';
                const select = document.createElement('select');
                select.name = 'filter_field[]'; select.className = 'form-select admin-filter-field';
                Object.entries(fields).forEach(([key, label]) => select.add(new Option(label, key, false, key === field)));
                const input = document.createElement('input');
                input.name = 'filter_value[]'; input.className = 'form-control admin-filter-value'; input.value = value; input.autocomplete = 'off';
                const tools = document.createElement('div');
                tools.className = 'admin-filter-tools';
                tools.innerHTML = '<button type="button" class="btn btn-outline-secondary admin-filter-icon admin-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg"></i></button><button type="button" class="btn btn-outline-primary admin-filter-icon admin-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg"></i></button>';
                row.append(select, input, tools); rows.appendChild(row); syncFilterInput(row); updateButtons(); return row;
            };

            rows.querySelectorAll('.admin-filter-row').forEach(syncFilterInput);
            updateButtons();
            rows.addEventListener('click', event => {
                const row = event.target.closest('.admin-filter-row');
                if (!row) return;
                if (event.target.closest('.admin-filter-add')) { const added = addRow(); added?.querySelector('.admin-filter-value').focus(); }
                if (event.target.closest('.admin-filter-remove') && rows.querySelectorAll('.admin-filter-row').length > 1) { row.remove(); updateButtons(); form.requestSubmit(); }
            });
            rows.addEventListener('change', event => {
                if (!event.target.matches('.admin-filter-field')) return;
                const row = event.target.closest('.admin-filter-row');
                row.querySelector('.admin-filter-value').value = '';
                syncFilterInput(row);
                row.querySelector('.admin-filter-value').focus();
            });
            rows.addEventListener('input', event => {
                if (!event.target.matches('.admin-filter-value')) return;
                window.clearTimeout(submitTimer);
                submitTimer = window.setTimeout(() => form.requestSubmit(), 600);
            });
        })();
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
        document.querySelectorAll('.evidence-file').forEach((input) => {
            input.addEventListener('change', () => {
                const preview = input.closest('form')?.querySelector('.evidence-preview');
                if (!preview) return;
                preview.innerHTML = '';
                Array.from(input.files || []).forEach((file) => {
                    if (!file.type.startsWith('image/') || file.size > 5 * 1024 * 1024) return;
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const item = document.createElement('div');
                        item.className = 'col-4';
                        item.innerHTML = '<img src="' + event.target.result + '" alt="Preview foto" class="img-fluid rounded-3 border" style="height:84px;width:100%;object-fit:cover">';
                        preview.appendChild(item);
                    };
                    reader.readAsDataURL(file);
                });
            });
        });
        document.getElementById('returnPageSize')?.addEventListener('change', function () {
            const targetUrl = new URL(window.location.href);
            targetUrl.searchParams.set('per_page', this.value);
            targetUrl.searchParams.set('page', '1');
            window.location.assign(targetUrl.toString());
        });
        window.setInterval(() => {
            const activeElement = document.activeElement;
            const isEditing = activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(activeElement.tagName);
            if (!document.hidden && !document.querySelector('.modal.show') && !isEditing) window.location.reload();
        }, 60000);
    </script>
</body>
</html>
