<?php
function popup_icon($file) {
    $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
    return $ext === 'pdf' ? 'file-earmark-pdf' : 'file-earmark-word';
}
function popup_previewable($file) {
    return strtolower(pathinfo((string) $file, PATHINFO_EXTENSION)) === 'pdf';
}
$first = !empty($dokumen) ? $dokumen[0] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dokumen Internal' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f7f8fa; color: #202124; font-family: 'Poppins', sans-serif; }
        .popup-shell { min-height: 100vh; }
        .side-list { background: #fff; border-right: 1px solid #e8eaed; }
        .doc-button { width: 100%; border: 1px solid #e8eaed; border-radius: 8px; background: #fff; text-align: left; padding: 12px; transition: .16s ease; }
        .doc-button:hover, .doc-button.active { border-color: #ea5b1a; box-shadow: 0 7px 18px rgba(32,33,36,.08); }
        .doc-icon { width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234,91,26,.12); color: #c24a13; font-size: 1.15rem; }
        .soft-badge { border-radius: 999px; padding: 5px 9px; font-weight: 600; font-size: .7rem; background: rgba(234,91,26,.12); color: #c24a13; }
        .preview-pane { min-height: 70vh; }
        .doc-frame { width: 100%; height: 72vh; border: 1px solid #e8eaed; border-radius: 8px; background: #fff; }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        @media (max-width: 991.98px) {
            .side-list { border-right: 0; border-bottom: 1px solid #e8eaed; }
            .doc-frame { height: 58vh; }
        }
    </style>
</head>
<body>
<div class="container-fluid popup-shell p-0">
    <div class="row g-0">
        <aside class="col-lg-4 col-xl-3 side-list p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <div class="small text-uppercase fw-bold text-muted">Dokumen Internal</div>
                    <h5 class="fw-bold mb-0">SOP & Instruksi Kerja</h5>
                </div>
                <?php if($can_manage): ?>
                    <a href="<?= base_url('index.php/dokumen_internal') ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-folder2-open"></i></a>
                <?php endif; ?>
            </div>

            <?php if(empty($dokumen)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-folder-x display-6 d-block mb-2"></i>
                    Belum ada dokumen aktif.
                </div>
            <?php else: ?>
                <div class="vstack gap-2">
                    <?php foreach($dokumen as $index => $d): ?>
                        <button type="button"
                            class="doc-button js-doc-select <?= $index === 0 ? 'active' : '' ?>"
                            data-title="<?= html_escape($d->judul) ?>"
                            data-category="<?= html_escape($d->kategori) ?>"
                            data-description="<?= html_escape($d->deskripsi ?: 'Tidak ada deskripsi.') ?>"
                            data-preview="<?= base_url('index.php/dokumen_internal/lihat/'.$d->id_dokumen) ?>"
                            data-download="<?= base_url('index.php/dokumen_internal/unduh/'.$d->id_dokumen) ?>"
                            data-previewable="<?= popup_previewable($d->nama_file) ? '1' : '0' ?>">
                            <div class="d-flex align-items-start gap-2">
                                <span class="doc-icon"><i class="bi bi-<?= popup_icon($d->nama_file) ?>"></i></span>
                                <span class="d-block">
                                    <span class="fw-semibold d-block"><?= html_escape($d->judul) ?></span>
                                    <span class="small text-muted d-block"><?= html_escape($d->original_name ?: $d->nama_file) ?></span>
                                    <span class="soft-badge mt-1 d-inline-flex"><?= html_escape($d->kategori) ?></span>
                                </span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <main class="col-lg-8 col-xl-9 p-3 p-lg-4 preview-pane">
            <?php if($first): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <span class="soft-badge mb-2" id="docCategory"><?= html_escape($first->kategori) ?></span>
                        <h4 class="fw-bold mb-1" id="docTitle"><?= html_escape($first->judul) ?></h4>
                        <p class="text-muted mb-0" id="docDescription"><?= html_escape($first->deskripsi ?: 'Tidak ada deskripsi.') ?></p>
                    </div>
                    <div class="d-flex gap-2 align-items-start">
                        <a href="<?= base_url('index.php/dokumen_internal/unduh/'.$first->id_dokumen) ?>" class="btn btn-outline-secondary rounded-pill px-3" id="docDownload"><i class="bi bi-download me-1"></i> Unduh</a>
                        <?php if($can_manage): ?><a href="<?= base_url('index.php/dokumen_internal') ?>" target="_blank" class="btn btn-fik rounded-pill px-3"><i class="bi bi-folder2-open me-1"></i> Kelola</a><?php endif; ?>
                    </div>
                </div>
                <div id="docPreviewWrap">
                    <?php if(popup_previewable($first->nama_file)): ?>
                        <iframe class="doc-frame" id="docFrame" src="<?= base_url('index.php/dokumen_internal/lihat/'.$first->id_dokumen) ?>"></iframe>
                    <?php else: ?>
                        <div class="doc-frame d-flex flex-column align-items-center justify-content-center text-center p-4" id="docNoPreview">
                            <i class="bi bi-file-earmark-word display-4 text-secondary mb-3"></i>
                            <h5 class="fw-bold">Preview tidak tersedia untuk file Word</h5>
                            <p class="text-muted">Silakan unduh dokumen untuk membukanya di aplikasi Office.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center text-center text-muted">
                    <div>
                        <i class="bi bi-folder-x display-4 d-block mb-3"></i>
                        <h5 class="fw-bold">Belum Ada Dokumen</h5>
                        <p>Pengelola internal bisa mengunggah SOP dan Instruksi Kerja dari file manager.</p>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const title = document.getElementById('docTitle');
        const category = document.getElementById('docCategory');
        const description = document.getElementById('docDescription');
        const download = document.getElementById('docDownload');
        const wrap = document.getElementById('docPreviewWrap');

        document.querySelectorAll('.js-doc-select').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.js-doc-select').forEach(function (item) { item.classList.remove('active'); });
                button.classList.add('active');

                title.textContent = button.dataset.title || '-';
                category.textContent = button.dataset.category || '-';
                description.textContent = button.dataset.description || '-';
                download.setAttribute('href', button.dataset.download || '#');

                if (button.dataset.previewable === '1') {
                    wrap.innerHTML = '<iframe class="doc-frame" id="docFrame" src="' + (button.dataset.preview || '#') + '"></iframe>';
                } else {
                    wrap.innerHTML = '<div class="doc-frame d-flex flex-column align-items-center justify-content-center text-center p-4"><i class="bi bi-file-earmark-word display-4 text-secondary mb-3"></i><h5 class="fw-bold">Preview tidak tersedia untuk file Word</h5><p class="text-muted">Silakan unduh dokumen untuk membukanya di aplikasi Office.</p></div>';
                }
            });
        });
    });
</script>
</body>
</html>
