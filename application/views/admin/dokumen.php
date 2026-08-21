<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$previewable = static function ($filename) {
    return in_array(strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true);
};
$previewType = static function ($filename) {
    return in_array(strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : 'document';
};
$formatDocumentDate = static function ($value) {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y · H:i', $timestamp) : (string) $value;
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
        .table thead th { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; }
        .table td { vertical-align: middle; }
        .document-page-heading { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .document-page-heading h1 { margin: 0; color: #1f2937; font-size: clamp(1.4rem, 2vw, 1.85rem); font-weight: 700; letter-spacing: -.02em; }
        .document-page-heading p { margin: .3rem 0 0; color: #6b7280; font-size: .84rem; }
        .document-toolbar { display: grid; grid-template-columns: minmax(220px, 1.5fr) minmax(150px, .8fr) minmax(180px, .9fr) auto; align-items: end; gap: .75rem; margin-bottom: 1rem; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; box-shadow: 0 5px 18px rgba(32,33,36,.035); }
        .document-toolbar .form-label { margin-bottom: .35rem; color: #6b7280; font-size: .72rem; font-weight: 600; }
        .document-toolbar .form-control, .document-toolbar .form-select { min-height: 38px; font-size: .8rem; }
        .document-toolbar-reset { min-height: 38px; }
        .document-table-card { overflow: hidden; border-color: #e5e7eb; }
        .document-table { min-width: 900px; margin: 0; --bs-table-bg: #fff; --bs-table-border-color: #e5e7eb; }
        .document-table.table-hover > tbody > tr:hover > * { --bs-table-bg-state: rgba(234,91,26,.045); }
        .document-table thead th { padding: 13px 16px; color: #111827 !important; background: #f8f9fa; border-color: #e5e7eb; font-size: .7rem; font-family: inherit; font-weight: 700 !important; letter-spacing: .05em; text-transform: uppercase; vertical-align: middle; }
        .document-table thead th, .document-table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .document-table tbody td { padding: 15px 16px; color: #1f2937; border-color: #e5e7eb; font-size: .8rem; line-height: 1.45; }
        .document-table th.document-index-column, .document-table td.document-index-column { min-width: 58px; width: 58px; padding-right: 10px; padding-left: 10px; text-align: center; white-space: nowrap; }
        .document-index { color: #64748b; font-size: .8rem; font-weight: 500; }
        .document-table th:nth-child(2), .document-table td:nth-child(2) { min-width: 290px; }
        .document-table th:nth-child(3), .document-table td:nth-child(3) { min-width: 125px; }
        .document-table th:nth-child(4), .document-table td:nth-child(4) { min-width: 250px; }
        .document-table th:nth-child(5), .document-table td:nth-child(5) { min-width: 165px; white-space: nowrap; }
        .document-table th:nth-child(6), .document-table td:nth-child(6) { min-width: 150px; }
        .document-file-name { display: block; max-width: 320px; overflow: hidden; color: #6b7280; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }
        .document-type { display: inline-flex; min-width: 74px; align-items: center; justify-content: center; padding: 5px 10px; border: 1px solid #e5e7eb; border-radius: 7px; color: #4b5563; background: #fff; font-size: .7rem; font-weight: 600; line-height: 1.2; text-align: center; }
        .document-relation-secondary { color: #6b7280; font-size: .72rem; }
        .document-action-preview { min-width: 91px; }
        .document-more-btn { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; padding: 0; border: 1px solid #d1d5db; border-radius: 8px; color: #6b7280; background: #fff; }
        .document-more-btn:hover, .document-more-btn:focus { border-color: rgba(234,91,26,.45); color: #ea5b1a; background: rgba(234,91,26,.05); }
        .document-drawer { width: min(520px, 100vw) !important; border-left: 1px solid #e5e7eb; }
        .document-drawer .offcanvas-header { padding: 1.25rem 1.35rem; border-bottom: 1px solid #e5e7eb; }
        .document-drawer .offcanvas-title { color: #1f2937; font-size: 1rem; font-weight: 700; }
        .document-drawer .offcanvas-body { padding: 1.35rem; background: #fff; }
        .document-drawer .form-label { margin-bottom: .4rem; color: #6b7280; font-size: .75rem; font-weight: 600; }
        .document-drawer .form-control, .document-drawer .form-select { min-height: 40px; font-size: .8rem; }
        .document-drawer textarea.form-control { min-height: auto; }
        .document-upload-note { display: flex; align-items: flex-start; gap: .5rem; margin-top: .45rem; color: #6b7280; font-size: .7rem; line-height: 1.5; }
        .document-pagination { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 58px; padding: .75rem 1rem; border-top: 1px solid #e5e7eb; color: #6b7280; background: #f8f9fa; font-size: .72rem; }
        .document-pagination .pagination { margin: 0; }
        .document-pagination .page-link { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; min-height: 34px; padding: .35rem .58rem; color: #374151; background: #fff; border-color: #dfe3e8; font-size: .72rem; line-height: 1; }
        .document-pagination .page-item.active .page-link { color: #fff; background: #ea5b1a; border-color: #ea5b1a; }
        .document-pagination .page-item.disabled .page-link { color: #adb5bd; background: #f8f9fa; }
        .document-pagination .page-link:hover { color: #c24a13; background: #fff8f3; border-color: #f0b99e; }
        .document-pagination .page-item.active .page-link:hover { color: #fff; background: #c94d14; border-color: #c94d14; }
        .document-preview-frame { width: 100%; height: min(78vh, 760px); border: 0; background: #f7f8fa; }
        #documentPreviewDocumentWrap { position: relative; overflow: auto; }
        .document-preview-pdf-pages { display: flex; flex-direction: column; align-items: center; gap: 1rem; min-height: 100%; padding: 1rem; }
        .document-preview-pdf-page { display: block; max-width: 100%; height: auto; background: #fff; box-shadow: 0 2px 12px rgba(15, 23, 42, .12); }
        .document-preview-loading { position: absolute; inset: 0; z-index: 2; display: flex; align-items: center; justify-content: center; gap: .6rem; color: #6b7280; background: #f7f8fa; font-size: .8rem; }
        .document-preview-image { max-width: 100%; max-height: min(72vh, 680px); object-fit: contain; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .document-page-heading { align-items: stretch; flex-direction: column; }
            .document-page-heading .btn { align-self: flex-start; }
            .document-toolbar { grid-template-columns: 1fr; align-items: stretch; }
            .document-pagination { align-items: stretch; flex-direction: column; }
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
            </div>
            <div class="topbar-actions d-flex gap-2">
                <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

    <div class="document-page-heading">
        <div>
            <h1>Dokumen Laboran</h1>
        </div>
        <button type="button" class="btn btn-fik rounded-pill px-3" data-bs-toggle="offcanvas" data-bs-target="#documentUploadDrawer" aria-controls="documentUploadDrawer">
            <i class="bi bi-plus-lg me-1"></i> Upload Dokumen
        </button>
    </div>

    <?php
    $multi_filter_id = 'documentMultiFilter';
    $multi_filter_mode = 'client';
    $multi_filter_fields = [
        'dokumen' => ['label' => 'Dokumen', 'placeholder' => 'Cari judul, nama file, atau keterangan'],
        'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari jenis dokumen'],
        'relasi' => ['label' => 'Relasi peminjaman', 'placeholder' => 'Cari peminjam, NIM/NIP, atau status relasi'],
        'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal dokumen', 'type' => 'date'],
    ];
    include APPPATH . 'views/admin/_multi_filter.php';
    ?>

    <div class="panel-card p-0 document-table-card">
        <div class="table-responsive">
            <table class="table table-hover document-table">
                <thead>
                    <tr>
                        <th class="document-index-column">No</th>
                        <th class="ps-3">Dokumen</th>
                        <th>Jenis</th>
                        <th>Relasi</th>
                        <th>Tanggal</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody id="documentTableBody">
                <?php if(empty($dokumen)): ?>
                    <tr><td colspan="6" class="text-center py-5"><div class="fw-semibold text-dark">Belum ada dokumen</div><div class="small text-muted mt-1">Upload dokumen pertama untuk mulai mengarsipkan file.</div></td></tr>
                <?php else: foreach($dokumen as $document_index => $d): ?>
                    <?php
                    $has_relation = !empty($d->id_peminjaman);
                    $document_search = strtolower(implode(' ', [
                        (string) ($d->judul ?? ''),
                        (string) ($d->original_name ?: $d->nama_file),
                        (string) ($d->jenis ?? ''),
                        (string) ($d->nama_peminjam ?? ''),
                        (string) ($d->nim_nip ?? ''),
                        (string) ($d->keterangan ?? ''),
                    ]));
                    ?>
                    <tr class="document-data-row" data-filter-dokumen="<?= html_escape($document_search) ?>" data-filter-jenis="<?= html_escape($d->jenis) ?>" data-filter-relasi="<?= html_escape($has_relation ? ('dikaitkan ' . ($d->nama_peminjam ?? '') . ' ' . ($d->nim_nip ?? '')) : 'tidak dikaitkan') ?>" data-filter-tanggal="<?= html_escape(substr((string) ($d->created_at ?? ''), 0, 10)) ?>">
                        <td class="document-index-column"><span class="document-index"><?= $document_index + 1 ?></span></td>
                        <td class="ps-3">
                            <div class="fw-semibold"><?= html_escape($d->judul) ?></div>
                            <span class="document-file-name" title="<?= html_escape($d->original_name ?: $d->nama_file) ?>"><?= html_escape($d->original_name ?: $d->nama_file) ?></span>
                        </td>
                        <td><span class="document-type"><?= html_escape($d->jenis) ?></span></td>
                        <td>
                            <?php if ($has_relation): ?>
                                <div><?= html_escape($d->nama_peminjam ?? '-') ?></div>
                                <div class="document-relation-secondary"><?= html_escape($d->nim_nip ?? '') ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= html_escape($formatDocumentDate($d->created_at)) ?></td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill document-action-preview js-document-preview" data-bs-toggle="modal" data-bs-target="#documentPreviewModal" data-document-title="<?= html_escape($d->judul) ?>" data-document-preview="<?= base_url('index.php/admin/dokumen/lihat/'.$d->id_dokumen) ?>" data-document-download="<?= base_url('index.php/admin/dokumen/download/'.$d->id_dokumen) ?>" data-document-previewable="<?= $previewable($d->nama_file) ? '1' : '0' ?>" data-document-type="<?= $previewType($d->nama_file) ?>"><i class="bi bi-eye me-1"></i> Preview</button>
                                <div class="dropdown">
                                    <button type="button" class="btn document-more-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Aksi dokumen">
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item text-danger" href="<?= base_url('index.php/admin/dokumen/hapus/'.$d->id_dokumen) ?>" onclick="return confirm('Hapus dokumen ini?')"><i class="bi bi-trash me-2"></i>Hapus</a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr id="documentFilteredEmpty" hidden><td colspan="6" class="text-center text-muted py-5">Tidak ada dokumen yang sesuai.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="document-pagination" id="documentPaginationFooter">
            <div class="d-flex align-items-center gap-2">
                <span>Tampilkan:</span>
                <select id="documentPageSize" class="form-select form-select-sm" aria-label="Jumlah dokumen per halaman">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">Semua</option>
                </select>
                <span id="documentTotalItems">Total item: 0</span>
            </div>
            <div id="documentPageInfo">Halaman: 1 dari 1</div>
            <nav aria-label="Pagination dokumen"><ul id="documentPaginationNav" class="pagination pagination-sm"></ul></nav>
        </div>
    </div>
</main>

<div class="offcanvas offcanvas-end document-drawer" tabindex="-1" id="documentUploadDrawer" aria-labelledby="documentUploadDrawerLabel">
    <div class="offcanvas-header">
        <div>
            <h2 class="offcanvas-title" id="documentUploadDrawerLabel">Upload Dokumen</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <form action="<?= base_url('index.php/admin/dokumen/simpan') ?>" method="post" enctype="multipart/form-data" class="vstack gap-3">
            <div><label class="form-label" for="documentTitle">Judul Dokumen</label><input id="documentTitle" type="text" name="judul" class="form-control" required></div>
            <div><label class="form-label" for="documentType">Jenis</label><select id="documentType" name="jenis" class="form-select"><option>SOP</option><option>Bukti</option><option>Berita Acara</option><option selected>Lainnya</option></select></div>
            <div><label class="form-label" for="documentRelation">Relasi Peminjaman</label><select id="documentRelation" name="id_peminjaman" class="form-select"><option value="">Tidak dikaitkan</option><?php foreach($peminjaman as $p): ?><option value="<?= $p->id_peminjaman ?>"><?= html_escape(($p->nama_peminjam ?? '-') . ' - ' . ($p->nama_aset ?? '-') . ' (' . ($p->tanggal_pinjam ?? '-') . ')') ?></option><?php endforeach; ?></select></div>
            <div>
                <label class="form-label" for="documentFile">File</label>
                <input id="documentFile" type="file" name="dokumen" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                <div class="document-upload-note"><i class="bi bi-info-circle"></i><span>PDF, Word, Excel, JPG/PNG. Maks 5MB.</span></div>
            </div>
            <div><label class="form-label" for="documentNotes">Keterangan</label><textarea id="documentNotes" name="keterangan" rows="4" class="form-control"></textarea></div>
            <button class="btn btn-fik rounded-pill mt-2"><i class="bi bi-upload me-1"></i> Upload Dokumen</button>
        </form>
    </div>
</div>

<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <div><div class="small text-uppercase text-warning fw-bold mb-1">Preview Dokumen</div><h5 class="modal-title fw-bold mb-0" id="documentPreviewModalLabel">Dokumen Laboran</h5></div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0 bg-light">
                <div id="documentPreviewDocumentWrap" class="document-preview-frame d-none">
                    <div id="documentPreviewLoading" class="document-preview-loading"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span><span>Memuat preview...</span></div>
                    <div id="documentPreviewPdfPages" class="document-preview-pdf-pages" aria-live="polite"></div>
                </div>
                <div id="documentPreviewImageWrap" class="document-preview-frame d-none align-items-center justify-content-center p-3"><img id="documentPreviewImage" class="document-preview-image" alt="Preview dokumen"></div>
                <div id="documentPreviewFallback" class="document-preview-frame d-none align-items-center justify-content-center text-center p-4"><div><i class="bi bi-file-earmark-text display-4 text-secondary d-block mb-3"></i><h5 class="fw-bold">Preview tidak tersedia</h5><p class="text-muted mb-0">Format ini tidak dapat dibaca langsung di browser. Gunakan tombol Unduh untuk membukanya.</p></div></div>
            </div>
            <div class="modal-footer"><a id="documentPreviewOpen" class="btn btn-outline-primary rounded-pill px-3" href="#" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i> Buka Tab Baru</a><a id="documentPreviewDownload" class="btn btn-fik rounded-pill px-3" href="#"><i class="bi bi-download me-1"></i> Unduh</a><button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('documentPreviewModal');
    const title = document.getElementById('documentPreviewModalLabel');
    const documentWrap = document.getElementById('documentPreviewDocumentWrap');
    const documentLoading = document.getElementById('documentPreviewLoading');
    const pdfPages = document.getElementById('documentPreviewPdfPages');
    const openPreview = document.getElementById('documentPreviewOpen');
    const imageWrap = document.getElementById('documentPreviewImageWrap');
    const image = document.getElementById('documentPreviewImage');
    const fallback = document.getElementById('documentPreviewFallback');
    const download = document.getElementById('documentPreviewDownload');
    let activePreviewUrl = '';
    let activeIsImage = false;
    let renderToken = 0;

    const resetPreview = function () {
        renderToken += 1;
        activePreviewUrl = '';
        activeIsImage = false;
        documentWrap.classList.add('d-none');
        imageWrap.classList.add('d-none');
        imageWrap.classList.remove('d-flex');
        fallback.classList.add('d-none');
        fallback.classList.remove('d-flex');
        documentLoading.classList.remove('d-none');
        if (pdfPages) pdfPages.replaceChildren();
        openPreview.href = '#';
        image.removeAttribute('src');
        fallback.querySelector('h5').textContent = 'Preview tidak tersedia';
        fallback.querySelector('p').textContent = 'Format ini tidak dapat dibaca langsung di browser. Gunakan tombol Unduh untuk membukanya.';
    };

    const showPreviewError = function () {
        documentLoading.classList.add('d-none');
        documentWrap.classList.add('d-none');
        fallback.classList.remove('d-none');
        fallback.classList.add('d-flex');
        fallback.querySelector('h5').textContent = 'Preview dokumen gagal dimuat';
        fallback.querySelector('p').textContent = 'Dokumen tetap tersedia. Gunakan tombol Buka Tab Baru atau Unduh untuk membukanya.';
    };

    const renderPdf = async function (url, token) {
        if (!window.pdfjsLib || !pdfPages) {
            showPreviewError();
            return;
        }

        try {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/pdf' }
            });
            if (!response.ok) throw new Error('Dokumen tidak dapat diakses (' + response.status + ')');
            const fileData = new Uint8Array(await response.arrayBuffer());
            const loadingTask = window.pdfjsLib.getDocument({ data: fileData });
            const pdf = await loadingTask.promise;
            if (token !== renderToken) return;

            const containerWidth = Math.max(pdfPages.clientWidth - 32, 320);
            const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                if (token !== renderToken) return;
                const page = await pdf.getPage(pageNumber);
                const baseViewport = page.getViewport({ scale: 1 });
                const scale = Math.min(1.5, Math.max(.75, containerWidth / baseViewport.width));
                const viewport = page.getViewport({ scale: scale });
                const renderViewport = page.getViewport({ scale: scale * dpr });
                const canvas = document.createElement('canvas');
                canvas.className = 'document-preview-pdf-page';
                canvas.setAttribute('aria-label', 'Halaman ' + pageNumber + ' dari ' + pdf.numPages);
                canvas.width = Math.ceil(renderViewport.width);
                canvas.height = Math.ceil(renderViewport.height);
                canvas.style.width = Math.ceil(viewport.width) + 'px';
                canvas.style.height = Math.ceil(viewport.height) + 'px';
                pdfPages.appendChild(canvas);
                await page.render({ canvasContext: canvas.getContext('2d', { alpha: false }), viewport: renderViewport }).promise;
            }
            if (token === renderToken) documentLoading.classList.add('d-none');
        } catch (error) {
            if (token === renderToken) {
                console.error('Preview PDF gagal dimuat:', error);
                showPreviewError();
            }
        }
    };

    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        const previewUrl = trigger.dataset.documentPreview || '';
        const isPreviewable = trigger.dataset.documentPreviewable === '1';
        const isImage = trigger.dataset.documentType === 'image';
        resetPreview();
        activePreviewUrl = previewUrl;
        activeIsImage = isImage;
        title.textContent = trigger.dataset.documentTitle || 'Dokumen Laboran';
        download.href = trigger.dataset.documentDownload || '#';
        openPreview.href = previewUrl || '#';
        if (!isPreviewable) {
            fallback.classList.remove('d-none');
            fallback.classList.add('d-flex');
        } else if (isImage) {
            image.src = previewUrl;
            imageWrap.classList.remove('d-none');
            imageWrap.classList.add('d-flex');
        } else {
            documentWrap.classList.remove('d-none');
        }
    });
    modal.addEventListener('shown.bs.modal', function () {
        if (activePreviewUrl && !activeIsImage) renderPdf(activePreviewUrl, renderToken);
    });
    modal.addEventListener('hidden.bs.modal', function () {
        resetPreview();
    });
});

(function () {
    const filterRoot = document.getElementById('documentMultiFilter');
    const rows = Array.from(document.querySelectorAll('.document-data-row'));
    const filteredEmpty = document.getElementById('documentFilteredEmpty');
    const footer = document.getElementById('documentPaginationFooter');
    const pageSizeSelect = document.getElementById('documentPageSize');
    const totalItems = document.getElementById('documentTotalItems');
    const pageInfo = document.getElementById('documentPageInfo');
    const pagination = document.getElementById('documentPaginationNav');
    let currentPage = 1;

    if (!filterRoot || !footer || !pageSizeSelect || !pageInfo || !pagination) return;

    const compactPageTokens = function (pageCount, page) {
        if (pageCount <= 7) return Array.from({ length: pageCount }, function (_, index) { return index + 1; });
        if (page <= 3) return [1, 2, 3, 4, 5, 'ellipsis', pageCount];
        if (page >= pageCount - 2) return [pageCount - 4, pageCount - 3, pageCount - 2, pageCount - 1, pageCount];
        return [1, 'ellipsis', page - 2, page - 1, page, page + 1, page + 2, 'ellipsis', pageCount];
    };

    const pageItem = function (label, target, disabled, active, ellipsis) {
        const item = document.createElement('li');
        item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
        if (ellipsis) {
            item.setAttribute('aria-hidden', 'true');
            const separator = document.createElement('span');
            separator.className = 'page-link';
            separator.textContent = '...';
            item.appendChild(separator);
            return item;
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.disabled = disabled;
        if (active) button.setAttribute('aria-current', 'page');
        if (!disabled && !active) button.addEventListener('click', function () {
            currentPage = target;
            applyFilters();
        });
        item.appendChild(button);
        return item;
    };

    const applyFilters = function () {
        const criteria = AdminMultiFilter.getCriteria(filterRoot);
        const filteredRows = rows.filter(function (row) {
            return AdminMultiFilter.matches(row, criteria);
        });
        const pageSize = pageSizeSelect.value === 'all' ? Math.max(filteredRows.length, 1) : Math.max(Number(pageSizeSelect.value) || 10, 1);
        const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
        currentPage = Math.min(Math.max(currentPage, 1), pageCount);
        const start = (currentPage - 1) * pageSize;
        const visibleRows = new Set(filteredRows.slice(start, start + pageSize));

        rows.forEach(function (row) { row.hidden = !visibleRows.has(row); });
        if (filteredEmpty) filteredEmpty.hidden = filteredRows.length !== 0;
        footer.hidden = rows.length === 0;
        if (totalItems) totalItems.textContent = 'Total item: ' + filteredRows.length;
        pageInfo.textContent = 'Halaman: ' + currentPage + ' dari ' + pageCount;
        pagination.replaceChildren();
        pagination.appendChild(pageItem('Previous', currentPage - 1, currentPage === 1, false, false));
        compactPageTokens(pageCount, currentPage).forEach(function (token) {
            pagination.appendChild(typeof token === 'string'
                ? pageItem('...', currentPage, true, false, true)
                : pageItem(String(token), token, false, token === currentPage, false));
        });
        pagination.appendChild(pageItem('Next', currentPage + 1, currentPage === pageCount, false, false));
    };

    filterRoot.addEventListener('admin-multi-filter-change', function () { currentPage = 1; applyFilters(); });
    pageSizeSelect.addEventListener('change', function () { currentPage = 1; applyFilters(); });
    applyFilters();
})();
</script>
</body>
</html>
