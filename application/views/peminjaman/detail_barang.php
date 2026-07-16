<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6f8; font-family: Arial, sans-serif; }
        .card-detail { max-width: 720px; margin: 32px auto; border-radius: 10px; border: 1px solid #e8eaed; box-shadow: 0 10px 26px rgba(0,0,0,.06); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
    </style>
</head>
<body>
    <main class="container px-3">
        <div class="card card-detail">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <div class="text-muted small mb-1">Detail Barang Inventaris</div>
                        <h1 class="h4 fw-bold mb-1"><?= html_escape($aset->nama_aset) ?></h1>
                        <div class="text-muted"><?= html_escape($aset->kode_aset) ?></div>
                    </div>
                    <span class="badge text-bg-success align-self-start"><?= html_escape($aset->kondisi ?? 'Baik') ?></span>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-sm-6"><div class="small text-muted">Ruangan</div><div class="fw-semibold"><?= html_escape($aset->nama_ruangan ?? '-') ?></div></div>
                    <div class="col-sm-6"><div class="small text-muted">Stok Tersedia</div><div class="fw-semibold"><?= (int) ($aset->jumlah_tersedia ?? 0) ?> / <?= (int) ($aset->jumlah_total ?? 0) ?></div></div>
                    <div class="col-12"><div class="small text-muted">Deskripsi</div><div><?= nl2br(html_escape($aset->deskripsi ?? '-')) ?></div></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="<?= base_url('index.php/peminjaman/ajukan/'.$aset->id_aset) ?>" class="btn btn-fik rounded-pill px-4"><i class="bi bi-send me-1"></i> Ajukan Peminjaman</a>
                    <a href="<?= base_url('index.php/peminjaman') ?>" class="btn btn-outline-secondary rounded-pill px-4">Katalog</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
