<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Preview Export') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: Arial, sans-serif; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .preview-frame { width: 100%; min-height: calc(100vh - 170px); border: 1px solid #dfe3e7; border-radius: 8px; background: #fff; }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-warning"></i><?= html_escape($title ?? 'Preview Export') ?></div>
                <div class="small text-white-50">Periksa isi laporan sebelum file Excel diunduh.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= html_escape($back_url ?? '#') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                <a href="<?= html_escape($download_url ?? '#') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-download me-1"></i> Download Excel</a>
            </div>
        </div>
    </header>
    <main class="container-fluid px-3 px-lg-4 py-3">
        <iframe class="preview-frame" src="<?= html_escape($iframe_url ?? '') ?>" title="Preview laporan"></iframe>
    </main>
</body>
</html>
