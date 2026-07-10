<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($dokumen->judul) ?></title>
    <style>
        html, body { height: 100%; margin: 0; background: #f7f8fa; font-family: Arial, sans-serif; color: #202124; }
        .pdf-shell { height: 100%; display: flex; flex-direction: column; }
        .pdf-title { padding: 10px 14px; background: #fff; border-bottom: 1px solid #e8eaed; font-size: 13px; font-weight: 700; }
        .pdf-viewer { flex: 1; width: 100%; border: 0; background: #fff; }
        .empty { height: 100%; display: flex; align-items: center; justify-content: center; text-align: center; padding: 24px; box-sizing: border-box; }
        .empty h3 { margin: 0 0 8px; font-size: 18px; }
        .empty p { margin: 0; color: #666; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="pdf-shell">
        <div class="pdf-title"><?= html_escape($dokumen->judul) ?></div>
        <?php if($is_pdf && !empty($pdf_data_uri)): ?>
            <iframe class="pdf-viewer" src="<?= $pdf_data_uri ?>#toolbar=1&navpanes=0" title="<?= html_escape($dokumen->judul) ?>"></iframe>
        <?php else: ?>
            <div class="empty">
                <div>
                    <h3>Preview tidak tersedia</h3>
                    <p>File Word tidak bisa ditampilkan langsung di browser. Gunakan tombol Unduh untuk membukanya di aplikasi Office.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>