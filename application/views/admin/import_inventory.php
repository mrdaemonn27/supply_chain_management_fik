<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Import Inventory') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --import-orange: #ea5b1a; --import-border: #e6e8eb; --import-muted: #687078; }
        body { background: #f8f9fa; font-family: Arial, sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .panel-card { background: #fff; border: 1px solid var(--import-border); border-radius: 14px; box-shadow: 0 10px 28px rgba(32,33,36,.06); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        textarea { font-family: Consolas, monospace; }
        .format-notice { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 12px; color: #7c2d12; }
        .template-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; }
        .supported-columns { line-height: 1.7; }
        .preview-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
        .summary-item { border: 1px solid var(--import-border); border-radius: 11px; padding: .8rem 1rem; background: #fff; }
        .summary-item__value { display: block; font-size: 1.25rem; font-weight: 700; line-height: 1.1; }
        .summary-item__label { color: var(--import-muted); font-size: .78rem; }
        .preview-table-wrap { max-height: min(62vh, 650px); border: 1px solid var(--import-border); border-radius: 12px; overflow: auto; }
        .preview-table { min-width: 1120px; margin-bottom: 0; }
        .preview-table thead th { position: sticky; top: 0; z-index: 2; padding: .85rem .75rem; background: #f4f5f6; border-bottom: 1px solid #dfe3e7; color: #4b5563; font-size: .72rem; letter-spacing: .045em; text-transform: uppercase; white-space: nowrap; }
        .preview-table tbody td { padding: .8rem .75rem; border-color: #edf0f2; vertical-align: top; }
        .preview-table tbody tr:hover { background: #fffaf7; }
        .preview-table .col-number { width: 48px; color: #8a9299; text-align: center; }
        .preview-table .col-code { min-width: 135px; white-space: nowrap; }
        .preview-table .col-name { min-width: 190px; }
        .preview-table .col-room { min-width: 155px; }
        .preview-table .col-qty { width: 88px; text-align: center; }
        .preview-table .col-condition { width: 100px; }
        .preview-table .col-description { min-width: 210px; max-width: 300px; white-space: normal; }
        .preview-table .col-status { min-width: 190px; max-width: 260px; white-space: normal; }
        .status-badge { display: inline-flex; max-width: 100%; align-items: flex-start; gap: .35rem; white-space: normal; text-align: left; line-height: 1.35; }
        .import-actions { width: 100%; }
        .import-actions .form-select { min-width: 210px; }
        .import-actions .btn { flex: 0 0 auto; }
        .empty-preview { padding: 4.5rem 1rem !important; }
        @media (max-width: 767.98px) {
            .preview-summary { grid-template-columns: 1fr; }
            .preview-table-wrap { max-height: none; }
        }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-upload me-2 text-warning"></i>Import Data Inventory</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('index.php/admin/barang') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Master Data</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')) ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>

        <?php
            $preview_total = count((array) $preview_rows);
            $duplicate_total = 0;
            foreach ((array) $preview_rows as $preview_row) {
                if (!empty($preview_row['duplicate_id'])) $duplicate_total++;
            }
            $new_total = max(0, $preview_total - $duplicate_total);
        ?>

        <div class="row g-4 align-items-start">
            <div class="col-lg-4">
                <form class="panel-card p-3 p-lg-4" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/barang/preview_import') ?>">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h1 class="h5 fw-bold mb-1">Sumber Data</h1>
                            <p class="small text-muted mb-0">Unggah template yang sudah diisi untuk melihat preview.</p>
                        </div>
                        <i class="bi bi-filetype-csv fs-3 text-success" aria-hidden="true"></i>
                    </div>

                    <div class="format-notice p-3 mb-3" role="note">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle me-1"></i> File wajib berformat CSV</div>
                        <div class="small">File <strong>.xls</strong> atau <strong>.xlsx</strong> tidak dapat diimpor. Template CSV dapat dibuka dan diisi menggunakan Microsoft Excel.</div>
                    </div>

                    <div class="template-box p-3 mb-3">
                        <div class="fw-semibold mb-1">Mulai dari template kosong</div>
                        <div class="small text-muted mb-3">Header kolom sudah disusun sesuai tabel preview import.</div>
                        <a href="<?= base_url('assets/templates/template_import_inventory.csv') ?>" download="template_import_inventory.csv" class="btn btn-outline-success btn-sm rounded-pill px-3">
                            <i class="bi bi-download me-1"></i> Download Template CSV
                        </a>
                    </div>

                    <label class="form-label fw-semibold" for="inventoryCsv">Pilih File CSV</label>
                    <input id="inventoryCsv" type="file" name="file_import" class="form-control mb-2" accept=".csv,text/csv">
                    <div class="small text-muted supported-columns mb-4"><strong>Urutan kolom:</strong> kode_aset, nama_aset, ruangan, jumlah_total, jumlah_tersedia, kondisi, deskripsi.</div>

                    <div class="d-flex align-items-center gap-2 mb-3" aria-hidden="true">
                        <span class="border-top flex-grow-1"></span><span class="small text-muted">atau</span><span class="border-top flex-grow-1"></span>
                    </div>
                    <label class="form-label fw-semibold" for="pasteData">Copy-paste dari Excel</label>
                    <textarea id="pasteData" name="paste_data" class="form-control" rows="7" placeholder="Tempel tabel dari Excel di sini, termasuk baris header..."></textarea>
                    <div class="form-text">Gunakan susunan kolom yang sama dengan template.</div>
                    <button class="btn btn-fik rounded-pill px-4 mt-3 w-100"><i class="bi bi-eye me-1"></i> Preview Data</button>
                </form>
            </div>
            <div class="col-lg-8">
                <section class="panel-card p-3 p-lg-4">
                    <div class="d-flex flex-column gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Preview Import</h2>
                            <p class="small text-muted mb-0">Periksa isi, lokasi, stok, dan status duplikat sebelum diproses.</p>
                        </div>
                        <?php if(!empty($preview_rows)): ?>
                            <form method="post" action="<?= base_url('index.php/admin/barang/proses_import') ?>" class="import-actions d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                                <label class="small fw-semibold text-muted mb-0" for="duplicateAction">Jika duplikat:</label>
                                <select id="duplicateAction" name="duplicate_action" class="form-select form-select-sm flex-grow-1">
                                    <option value="skip">Lewati data duplikat</option>
                                    <option value="update">Perbarui data lama</option>
                                    <option value="cancel">Batalkan import</option>
                                </select>
                                <button class="btn btn-success btn-sm rounded-pill px-4" onclick="return confirm('Proses data preview ke inventory?')"><i class="bi bi-check2-circle me-1"></i> Proses Import</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($preview_rows)): ?>
                        <div class="preview-summary mb-3" aria-label="Ringkasan preview import">
                            <div class="summary-item"><span class="summary-item__value"><?= $preview_total ?></span><span class="summary-item__label">Total baris</span></div>
                            <div class="summary-item"><span class="summary-item__value text-success"><?= $new_total ?></span><span class="summary-item__label">Data baru</span></div>
                            <div class="summary-item"><span class="summary-item__value text-warning"><?= $duplicate_total ?></span><span class="summary-item__label">Data duplikat</span></div>
                        </div>
                    <?php endif; ?>

                    <div class="preview-table-wrap">
                        <table class="table preview-table align-middle" data-scm-table-ignore>
                            <thead>
                                <tr>
                                    <th class="col-number">No.</th>
                                    <th class="col-code">Kode Aset</th>
                                    <th class="col-name">Nama Barang</th>
                                    <th class="col-room">Ruangan</th>
                                    <th class="col-qty">Total</th>
                                    <th class="col-qty">Tersedia</th>
                                    <th class="col-condition">Kondisi</th>
                                    <th class="col-description">Deskripsi</th>
                                    <th class="col-status">Status Data</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($preview_rows)): ?>
                                <tr>
                                    <td colspan="9" class="empty-preview text-center text-muted">
                                        <i class="bi bi-table d-block fs-1 mb-2 text-secondary"></i>
                                        <span class="d-block fw-semibold text-dark">Belum ada data untuk ditampilkan</span>
                                        <span class="small">Unggah file CSV atau tempel tabel, lalu klik Preview Data.</span>
                                    </td>
                                </tr>
                            <?php else: foreach($preview_rows as $index => $row): ?>
                                <tr>
                                    <td class="col-number"><?= $index + 1 ?></td>
                                    <td class="col-code font-monospace"><?= html_escape($row['kode_aset'] ?: 'Auto') ?></td>
                                    <td class="col-name fw-semibold"><?= html_escape($row['nama_aset']) ?></td>
                                    <td class="col-room"><?= html_escape($row['ruangan_label'] ?: 'Ruangan default') ?></td>
                                    <td class="col-qty fw-semibold"><?= (int) $row['jumlah_total'] ?></td>
                                    <td class="col-qty"><?= (int) $row['jumlah_tersedia'] ?></td>
                                    <td class="col-condition"><span class="badge <?= $row['kondisi'] === 'Baik' ? 'bg-success' : ($row['kondisi'] === 'Rusak' ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= html_escape($row['kondisi']) ?></span></td>
                                    <td class="col-description"><?= html_escape($row['deskripsi'] ?: '-') ?></td>
                                    <td class="col-status"><?php if (!empty($row['duplicate_id'])): ?><span class="badge status-badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i><span>Duplikat: <?= html_escape($row['duplicate_label'] ?? 'data lama') ?></span></span><?php else: ?><span class="badge status-badge bg-success"><i class="bi bi-check-circle"></i><span>Data baru</span></span><?php endif; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
