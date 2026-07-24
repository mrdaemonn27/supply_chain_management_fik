<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Import Inventory') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; font-family: Arial, sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .panel-card { background: #fff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 8px 22px rgba(32,33,36,.05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        textarea { font-family: Consolas, monospace; }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-upload me-2 text-warning"></i>Import Data Inventory</div>
                <div class="small text-white-50">Upload CSV/XLSX atau copy-paste tabel dari Excel, lalu preview sebelum import.</div>
            </div>
            <a href="<?= base_url('index.php/admin/barang') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Master Data</a>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('success')) ?></div><?php endif; ?>
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <form class="panel-card p-3 p-lg-4" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/barang/preview_import') ?>">
                    <h1 class="h5 fw-bold mb-3">Sumber Data</h1>
                    <label class="form-label fw-semibold">Import File</label>
                    <input type="file" name="file_import" class="form-control mb-2" accept=".csv,.xlsx">
                    <div class="small text-muted mb-3">Kolom yang didukung: kode_aset, nama_aset, ruangan/lokasi, jumlah_total, jumlah_tersedia, kondisi, deskripsi.</div>

                    <label class="form-label fw-semibold">Copy Paste dari Excel</label>
                    <textarea name="paste_data" class="form-control" rows="10" placeholder="Paste tabel dari Excel di sini..."></textarea>
                    <button class="btn btn-fik rounded-pill px-4 mt-3 w-100"><i class="bi bi-eye me-1"></i> Preview Data</button>
                </form>
            </div>
            <div class="col-lg-8">
                <section class="panel-card p-3 p-lg-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Preview Import</h2>
                            <div class="small text-muted">Data baru masuk inventory setelah tombol Import ditekan.</div>
                        </div>
                        <?php if(!empty($preview_rows)): ?>
                            <form method="post" action="<?= base_url('index.php/admin/barang/proses_import') ?>">
                                <button class="btn btn-success rounded-pill px-4" onclick="return confirm('Import semua data preview ke inventory?')"><i class="bi bi-check2-circle me-1"></i> Import ke Inventory</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Ruangan</th>
                                    <th>Total</th>
                                    <th>Tersedia</th>
                                    <th>Kondisi</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($preview_rows)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-5">Belum ada preview data.</td></tr>
                            <?php else: foreach($preview_rows as $row): ?>
                                <tr>
                                    <td class="font-monospace"><?= html_escape($row['kode_aset'] ?: 'Auto') ?></td>
                                    <td class="fw-semibold"><?= html_escape($row['nama_aset']) ?></td>
                                    <td><?= html_escape($row['ruangan_label'] ?: 'Ruangan default') ?></td>
                                    <td><?= (int) $row['jumlah_total'] ?></td>
                                    <td><?= (int) $row['jumlah_tersedia'] ?></td>
                                    <td><span class="badge <?= $row['kondisi'] === 'Baik' ? 'bg-success' : ($row['kondisi'] === 'Rusak' ? 'bg-warning text-dark' : 'bg-danger') ?>"><?= html_escape($row['kondisi']) ?></span></td>
                                    <td><?= html_escape($row['deskripsi'] ?: '-') ?></td>
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
