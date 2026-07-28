<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$previewable = static function ($filename) {
    return in_array(strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true);
};
$previewType = static function ($filename) {
    return in_array(strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : 'document';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dokumen Laboran' ?></title>
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
        .table thead th { font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table td { vertical-align: middle; }
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 600; font-size: .75rem; background: rgba(234,91,26,.12); color: #c24a13; }
        .document-preview-frame { width: 100%; height: min(78vh, 760px); border: 0; background: #f7f8fa; }
        .document-preview-image { max-width: 100%; max-height: min(72vh, 680px); object-fit: contain; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .document-preview-frame { height: 64vh; }
        }
    </style>
</head>
<body class="scm-admin-shell">
<?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>

<header class="topbar sticky-top">
    <div class="container-fluid px-3 px-lg-4 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-file-earmark-arrow-up me-2 text-warning"></i>Dokumen Laboran</div>
                <div class="small text-white-50">Upload dan arsipkan dokumen peminjaman</div>
            </div>
            <div class="topbar-actions d-flex gap-2">
                <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            </div>
        </div>
    </div>
</header>

<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-card p-3 p-lg-4">
                <h5 class="fw-bold mb-3">Masukin Dokumen</h5>
                <form action="<?= base_url('index.php/admin/dokumen/simpan') ?>" method="post" enctype="multipart/form-data" class="vstack gap-3">
                    <div><label class="form-label small fw-semibold text-muted">Judul Dokumen</label><input type="text" name="judul" class="form-control" required></div>
                    <div><label class="form-label small fw-semibold text-muted">Jenis</label><select name="jenis" class="form-select"><option>SOP</option><option>Bukti</option><option>Berita Acara</option><option selected>Lainnya</option></select></div>
                    <div><label class="form-label small fw-semibold text-muted">Relasi Peminjaman</label><select name="id_peminjaman" class="form-select"><option value="">Tidak dikaitkan</option><?php foreach($peminjaman as $p): ?><option value="<?= $p->id_peminjaman ?>"><?= html_escape(($p->nama_peminjam ?? '-') . ' - ' . ($p->nama_aset ?? '-') . ' (' . ($p->tanggal_pinjam ?? '-') . ')') ?></option><?php endforeach; ?></select></div>
                    <div><label class="form-label small fw-semibold text-muted">File</label><input type="file" name="dokumen" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required><small class="text-muted">PDF, Word, Excel, JPG/PNG. Maks 5MB.</small></div>
                    <div><label class="form-label small fw-semibold text-muted">Keterangan</label><textarea name="keterangan" rows="3" class="form-control"></textarea></div>
                    <button class="btn btn-fik rounded-pill"><i class="bi bi-upload me-1"></i> Upload Dokumen</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th class="ps-3">Dokumen</th><th>Relasi</th><th>Tanggal</th><th class="text-end pe-3">Aksi</th></tr></thead>
                        <tbody>
                        <?php if(empty($dokumen)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-5">Belum ada dokumen.</td></tr>
                        <?php else: foreach($dokumen as $d): ?>
                            <tr>
                                <td class="ps-3"><div class="fw-semibold"><?= html_escape($d->judul) ?></div><div class="small text-muted"><?= html_escape($d->original_name ?: $d->nama_file) ?> &middot; <span class="soft-badge"><?= html_escape($d->jenis) ?></span></div></td>
                                <td><div><?= html_escape($d->nama_peminjam ?? '-') ?></div><div class="small text-muted"><?= html_escape($d->nim_nip ?? '') ?></div></td>
                                <td><?= html_escape($d->created_at) ?></td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill js-document-preview" data-bs-toggle="modal" data-bs-target="#documentPreviewModal" data-document-title="<?= html_escape($d->judul) ?>" data-document-preview="<?= base_url('index.php/admin/dokumen/lihat/'.$d->id_dokumen) ?>" data-document-download="<?= base_url('index.php/admin/dokumen/download/'.$d->id_dokumen) ?>" data-document-previewable="<?= $previewable($d->nama_file) ? '1' : '0' ?>" data-document-type="<?= $previewType($d->nama_file) ?>"><i class="bi bi-eye me-1"></i> Preview</button>
                                    <a class="btn btn-sm btn-outline-danger rounded-pill" href="<?= base_url('index.php/admin/dokumen/hapus/'.$d->id_dokumen) ?>" onclick="return confirm('Hapus dokumen ini?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <div><div class="small text-uppercase text-warning fw-bold mb-1">Preview Dokumen</div><h5 class="modal-title fw-bold mb-0" id="documentPreviewModalLabel">Dokumen Laboran</h5></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <iframe id="documentPreviewFrame" class="document-preview-frame d-none" title="Preview dokumen"></iframe>
                <div id="documentPreviewImageWrap" class="document-preview-frame d-none align-items-center justify-content-center p-3"><img id="documentPreviewImage" class="document-preview-image" alt="Preview dokumen"></div>
                <div id="documentPreviewFallback" class="document-preview-frame d-none align-items-center justify-content-center text-center p-4"><div><i class="bi bi-file-earmark-text display-4 text-secondary d-block mb-3"></i><h5 class="fw-bold">Preview tidak tersedia</h5><p class="text-muted mb-0">Format ini tidak dapat dibaca langsung di browser. Gunakan tombol Unduh untuk membukanya.</p></div></div>
            </div>
            <div class="modal-footer"><a id="documentPreviewDownload" class="btn btn-fik rounded-pill px-3" href="#"><i class="bi bi-download me-1"></i> Unduh</a><button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('documentPreviewModal');
    const title = document.getElementById('documentPreviewModalLabel');
    const frame = document.getElementById('documentPreviewFrame');
    const imageWrap = document.getElementById('documentPreviewImageWrap');
    const image = document.getElementById('documentPreviewImage');
    const fallback = document.getElementById('documentPreviewFallback');
    const download = document.getElementById('documentPreviewDownload');

    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        const previewUrl = trigger.dataset.documentPreview || '';
        const isPreviewable = trigger.dataset.documentPreviewable === '1';
        const isImage = trigger.dataset.documentType === 'image';
        title.textContent = trigger.dataset.documentTitle || 'Dokumen Laboran';
        download.href = trigger.dataset.documentDownload || '#';
        frame.classList.add('d-none');
        imageWrap.classList.add('d-none');
        imageWrap.classList.remove('d-flex');
        fallback.classList.add('d-none');
        fallback.classList.remove('d-flex');
        frame.removeAttribute('src');
        image.removeAttribute('src');
        if (!isPreviewable) {
            fallback.classList.remove('d-none');
            fallback.classList.add('d-flex');
        } else if (isImage) {
            image.src = previewUrl;
            imageWrap.classList.remove('d-none');
            imageWrap.classList.add('d-flex');
        } else {
            frame.src = previewUrl;
            frame.classList.remove('d-none');
        }
    });
    modal.addEventListener('hidden.bs.modal', function () {
        frame.classList.add('d-none');
        imageWrap.classList.add('d-none');
        imageWrap.classList.remove('d-flex');
        fallback.classList.add('d-none');
        fallback.classList.remove('d-flex');
        frame.removeAttribute('src');
        image.removeAttribute('src');
    });
});
</script>
</body>
</html>
