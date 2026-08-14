<?php
/**
 * @var string $page
 * @var string $title
 * @var array  $ruangan_list
 * @var array  $ruangan_detail
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Manajemen Ruangan'; ?> - Panel Laboran</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .admin-navbar { background-color: #1a1a1a; border-bottom: 3px solid #ea5b1a; }
        .text-fik-orange { color: #ea5b1a !important; }
        .btn-fik-orange { background-color: #ea5b1a; color: white; border: none; }
        .btn-fik-orange:hover { background-color: #c24a13; color: white; }
        .img-thumbnail-custom { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }

        /* Custom Style untuk Drag & Drop Zone */
        .drop-zone {
            border: 2px dashed #adb5bd;
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            background-color: #f4f6f9;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .drop-zone:hover, .drop-zone.dragover {
            background-color: #e2e8f0;
            border-color: #ea5b1a;
            transform: scale(1.01);
        }
        .drop-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .preview-container {
            position: relative;
            z-index: 3; /* PERBAIKAN 1: Naikkan posisi agar berada di atas input file */
            pointer-events: none; /* PERBAIKAN 2: Membuat klik/drag tembus melewati gambar langsung ke input file */
        }
        
        /* Style untuk tombol Hapus/Batal Preview */
        .preview-wrapper {
            position: relative;
            display: inline-block;
        }
        .btn-remove-preview {
            position: absolute;
            top: -12px;
            right: -12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            pointer-events: auto; /* PERBAIKAN 3: Tombol X wajib bisa diklik (tidak tembus) */
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .btn-remove-preview:hover {
            background-color: #bb2d3b;
            transform: scale(1.1);
        }
        .room-table-toolbar { display:flex; align-items:center; gap:.65rem; padding:1rem; border-bottom:1px solid #e8eaed; background:#fff; }
        .room-table-search { flex:1 1 auto; max-width:760px; }
        .room-table-search .input-group-text, .room-table-search .form-control { min-height:42px; border-color:#d3d9df; background:#fff; font-size:.78rem; }
        .room-table-search .input-group-text { width:44px; justify-content:center; }
        .room-table-search .form-control { border-left:0; box-shadow:none; }
        .room-table-search .form-control:focus { border-color:#ea5b1a; }
        .room-table-reset { min-height:42px; padding-inline:1rem; border-color:#c5cdd5; border-radius:999px; color:#66717d; background:#fff; font-size:.74rem; font-weight:600; white-space:nowrap; }
        .room-table-reset:hover { border-color:#ea5b1a; color:#ea5b1a; background:#fff7f2; }
        .room-table-hint { padding:0 1rem .75rem; border-bottom:1px solid #e8eaed; color:#7a848e; background:#fff; font-size:.68rem; }
        .room-sort-button { display:inline-flex; align-items:center; gap:.38rem; padding:0; border:0; color:inherit; background:transparent; font:inherit; font-weight:inherit; }
        .room-sort-button i { color:#8d98a3; font-size:.72rem; }
        .room-filter-empty td { padding:2.5rem 1rem !important; color:#6b7280 !important; text-align:center; }
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
        @media (max-width:767.98px) { .room-table-toolbar { align-items:stretch; flex-direction:column; } .room-table-search { width:100%; max-width:none; } .room-table-reset { align-self:flex-end; } .loan-pagination-footer { grid-template-columns:1fr; justify-items:center; gap:.65rem; } .loan-pagination-footer nav { max-width:100%; overflow-x:auto; padding-bottom:2px; } }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>

    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm p-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-door-open-fill me-2 text-fik-orange"></i> LABORAN RUANGAN
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-sm btn-outline-light me-2">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?= base_url('auth/logout') ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-power"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show mt-3">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $this->session->flashdata('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 alert-dismissible fade show mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $this->session->flashdata('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- HALAMAN UTAMA / LIST RUANGAN -->
        <?php if($page == 'index'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Master Data Ruangan</h4>
                <p class="text-muted small m-0">Tambah, Edit, dan Hapus data ruangan / laboratorium secara global.</p>
            </div>
            <a href="<?= base_url('admin/ruangan/tambah') ?>" class="btn btn-fik-orange fw-bold px-4 rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Ruangan
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="p-3 pb-0"><?php
                    $multi_filter_id = 'ruanganMultiFilter';
                    $multi_filter_mode = 'client';
                    $multi_filter_fields = [
                        'nama' => ['label' => 'Nama ruangan', 'placeholder' => 'Cari nama ruangan atau laboratorium'],
                        'deskripsi' => ['label' => 'Deskripsi', 'placeholder' => 'Cari deskripsi ruangan'],
                    ];
                    include APPPATH . 'views/admin/_multi_filter.php';
                ?></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3 text-center" width="5%"><button type="button" class="room-sort-button" data-room-sort="index">No <i class="bi bi-arrow-down-up"></i></button></th>
                                <th width="15%" class="text-center">Gambar</th>
                                <th width="25%"><button type="button" class="room-sort-button" data-room-sort="name">Nama Ruangan <i class="bi bi-arrow-down-up"></i></button></th>
                                <th width="40%"><button type="button" class="room-sort-button" data-room-sort="description">Deskripsi <i class="bi bi-arrow-down-up"></i></button></th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($ruangan_list)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data ruangan.</td>
                            </tr>
                            <?php else: ?>
                                <?php $no=1; foreach($ruangan_list as $r): ?>
                                <tr
                                    class="ruangan-data-row"
                                    data-search="<?= html_escape(($r['nama_ruangan'] ?? '') . ' ' . ($r['deskripsi'] ?? '')) ?>"
                                    data-filter-nama="<?= html_escape($r['nama_ruangan'] ?? '') ?>"
                                    data-filter-deskripsi="<?= html_escape($r['deskripsi'] ?? '') ?>"
                                    data-sort-index="<?= $no ?>"
                                    data-sort-name="<?= html_escape($r['nama_ruangan'] ?? '') ?>"
                                    data-sort-description="<?= html_escape($r['deskripsi'] ?? '') ?>"
                                >
                                    <td class="p-3 text-center"><?= $no++; ?></td>
                                    <td class="text-center">
                                        <?php if($r['foto']): ?>
                                            <img src="<?= base_url('assets/uploads/ruangan/'.$r['foto']) ?>" class="img-thumbnail-custom shadow-sm" alt="Foto Ruangan">
                                        <?php else: ?>
                                            <div class="bg-light text-muted d-inline-flex align-items-center justify-content-center img-thumbnail-custom border">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= $r['nama_ruangan'] ?></td>
                                    <td class="text-muted small">
                                        <?= !empty($r['deskripsi']) ? $r['deskripsi'] : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/ruangan/ubah/'.$r['id_ruangan']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1 mb-1">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="<?= base_url('admin/ruangan/hapus/'.$r['id_ruangan']) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 mb-1" onclick="return confirm('Hapus ruangan ini beserta fotonya secara permanen?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr id="ruanganFilterEmpty" class="room-filter-empty" hidden>
                                    <td colspan="5">Tidak ada ruangan yang sesuai dengan pencarian.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="loan-pagination-footer" id="ruanganPaginationFooter" data-total="<?= count($ruangan_list) ?>">
                <div class="loan-pagination-summary">
                    <label for="ruanganPageSize">Tampilkan:</label>
                    <select id="ruanganPageSize" class="form-select form-select-sm" aria-label="Jumlah data ruangan per halaman">
                        <option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="all">Semua</option>
                    </select>
                    <span>Total item: <span id="ruanganTotalItems"><?= count($ruangan_list) ?></span></span>
                </div>
                <div class="loan-pagination-status" id="ruanganPageStatus">Halaman: 1 dari 1</div>
                <nav aria-label="Pagination ruangan"><ul class="pagination pagination-sm loan-pagination" id="ruanganPageNav"></ul></nav>
            </div>
        </div>

        <!-- HALAMAN TAMBAH RUANGAN -->
        <?php elseif($page == 'tambah'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Tambah Ruangan</h4>
            </div>
            <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-light fw-bold px-4 rounded-pill border">Kembali</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('admin/ruangan/tambah') ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_ruangan" class="form-control <?= form_error('nama_ruangan') ? 'is-invalid' : '' ?>" value="<?= set_value('nama_ruangan') ?>" placeholder="Contoh: Lab Jaringan Komputer">
                        <div class="invalid-feedback"><?= form_error('nama_ruangan') ?></div>
                    </div>
                    
                    <!-- UPLOAD FOTO DENGAN DRAG AND DROP -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Foto Ruangan (Opsional)</label>
                        <div class="drop-zone shadow-sm" id="dropZone">
                            <input type="file" name="foto" id="fileInput" accept="image/jpeg, image/png, image/jpg, image/webp">
                            
                            <div id="previewContainer" class="preview-container d-none">
                                <div class="preview-wrapper">
                                    <img id="imagePreview" src="#" data-default-src="#" alt="Preview" class="img-thumbnail shadow-sm mb-2" style="max-height: 160px; border-radius: 8px;">
                                    <button type="button" id="btnRemovePreview" class="btn-remove-preview" title="Batal Pilih Foto">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <p class="small text-muted mb-0 fw-medium" id="fileName" data-default-text=""></p>
                            </div>

                            <div id="placeholderContainer" class="preview-container d-block">
                                <i class="bi bi-download display-4 text-secondary mb-3 d-block"></i>
                                <h6 class="mb-1 text-dark fs-5"><span class="fw-bold">Pilih file</span> atau drag ke sini.</h6>
                                <p class="text-muted small mb-0 mt-2">Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi Ruangan</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= set_value('deskripsi') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-fik-orange rounded-pill px-4 fw-bold">Simpan</button>
                </form>
            </div>
        </div>

        <!-- HALAMAN UBAH / EDIT RUANGAN -->
        <?php elseif($page == 'ubah'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Edit Ruangan</h4>
            </div>
            <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-light fw-bold px-4 rounded-pill border">Batal</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('admin/ruangan/ubah/'.$ruangan_detail['id_ruangan']) ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_ruangan" class="form-control <?= form_error('nama_ruangan') ? 'is-invalid' : '' ?>" value="<?= set_value('nama_ruangan', $ruangan_detail['nama_ruangan']) ?>">
                        <div class="invalid-feedback"><?= form_error('nama_ruangan') ?></div>
                    </div>
                    
                    <!-- UPLOAD FOTO DENGAN DRAG AND DROP -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload Foto Baru (Opsional)</label>
                        <div class="drop-zone shadow-sm" id="dropZone">
                            <input type="file" name="foto" id="fileInput" accept="image/jpeg, image/png, image/jpg, image/webp">
                            
                            <?php 
                                $ada_foto = !empty($ruangan_detail['foto']);
                                $foto_url = $ada_foto ? base_url('assets/uploads/ruangan/'.$ruangan_detail['foto']) : '#';
                                $foto_text = $ada_foto ? '<i class="bi bi-info-circle me-1"></i>Foto saat ini (Abaikan jika tidak diubah)' : '';
                            ?>

                            <!-- Container Preview -->
                            <div id="previewContainer" class="preview-container <?= $ada_foto ? 'd-block' : 'd-none' ?>">
                                <div class="preview-wrapper">
                                    <img id="imagePreview" src="<?= $foto_url ?>" data-default-src="<?= $foto_url ?>" alt="Preview" class="img-thumbnail shadow-sm mb-2" style="max-height: 160px; border-radius: 8px;">
                                    <button type="button" id="btnRemovePreview" class="btn-remove-preview" title="Batal Pilih Foto">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <p class="small text-muted mb-0 fw-medium" id="fileName" data-default-text="<?= htmlspecialchars($foto_text) ?>">
                                    <?= $foto_text ?>
                                </p>
                            </div>

                            <!-- Container Placeholder -->
                            <div id="placeholderContainer" class="preview-container <?= $ada_foto ? 'd-none' : 'd-block' ?>">
                                <i class="bi bi-download display-4 text-secondary mb-3 d-block"></i>
                                <h6 class="mb-1 text-dark fs-5"><span class="fw-bold">Pilih file baru</span> atau drag ke sini.</h6>
                                <p class="text-muted small mb-0 mt-2">Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi Ruangan</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= set_value('deskripsi', $ruangan_detail['deskripsi']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-fik-orange rounded-pill px-4 fw-bold">Update Data</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SCRIPT LOGIKA DRAG AND DROP & HAPUS FOTO -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const previewContainer = document.getElementById('previewContainer');
            const placeholderContainer = document.getElementById('placeholderContainer');
            const imagePreview = document.getElementById('imagePreview');
            const fileNameDisplay = document.getElementById('fileName');
            const btnRemovePreview = document.getElementById('btnRemovePreview');

            // Ambil data default foto lama (jika sedang di form edit)
            const defaultSrc = imagePreview ? imagePreview.getAttribute('data-default-src') : '#';
            const defaultText = fileNameDisplay ? fileNameDisplay.getAttribute('data-default-text') : '';

            if (dropZone && fileInput) {
                // Cegah browser membuka file secara default
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Efek hover saat di-drag
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
                });

                // Tangkap file yang di-drop
                dropZone.addEventListener('drop', function(e) {
                    let dt = e.dataTransfer;
                    let files = dt.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        updatePreview(files[0]);
                    }
                }, false);

                // Tangkap file yang dipilih manual (klik)
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        updatePreview(this.files[0]);
                    }
                });

                function updatePreview(file) {
                    if (!file.type.match('image.*')) {
                        alert('Format file ditolak! Pastikan Anda mengunggah format gambar yang didukung (JPG, PNG, WEBP).');
                        fileInput.value = '';
                        return;
                    }
                    let reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        fileNameDisplay.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> File dipilih: <b class="text-dark">${file.name}</b>`;
                        
                        previewContainer.classList.remove('d-none');
                        previewContainer.classList.add('d-block');
                        placeholderContainer.classList.remove('d-block');
                        placeholderContainer.classList.add('d-none');
                    }
                }

                // Logika Dinamis saat tombol X diklik
                if(btnRemovePreview) {
                    btnRemovePreview.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); // Mencegah form upload terbuka

                        // 1. Selalu kosongkan input file yang baru dipilih
                        fileInput.value = '';
                        
                        // 2. Cek apakah ada foto aslinya di DB (Mode Edit)
                        if (defaultSrc !== '' && defaultSrc !== '#') {
                            // Jika ada, kembalikan ke foto lama
                            imagePreview.src = defaultSrc;
                            fileNameDisplay.innerHTML = defaultText;
                        } else {
                            // Jika tidak ada foto (Mode Tambah), sembunyikan gambar & munculkan ikon awan
                            imagePreview.src = '#';
                            fileNameDisplay.innerHTML = '';
                            previewContainer.classList.remove('d-block');
                            previewContainer.classList.add('d-none');
                            placeholderContainer.classList.remove('d-none');
                            placeholderContainer.classList.add('d-block');
                        }
                    });
                }
            }
        });
        (function () {
            const rows = Array.from(document.querySelectorAll('.ruangan-data-row'));
            const select = document.getElementById('ruanganPageSize');
            const status = document.getElementById('ruanganPageStatus');
            const nav = document.getElementById('ruanganPageNav');
            const filterRoot = document.getElementById('ruanganMultiFilter');
            const total = document.getElementById('ruanganTotalItems');
            const empty = document.getElementById('ruanganFilterEmpty');
            const body = rows.length ? rows[0].parentElement : null;
            const sortButtons = Array.from(document.querySelectorAll('[data-room-sort]'));
            if (!rows.length || !select || !status || !nav) return;

            rows.forEach((row, index) => { row.dataset.originalIndex = String(index); });
            let page = 1;
            let sortKey = '';
            let sortDirection = 'asc';
            const normalize = value => String(value || '').trim().toLocaleLowerCase('id');
            const pageSize = length => select.value === 'all' ? Math.max(length, 1) : Number(select.value) || 10;
            const button = (label, target, disabled, active) => {
                const li = document.createElement('li');
                li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const a = document.createElement('a');
                a.className = 'page-link'; a.href = '#'; a.textContent = label;
                if (!disabled) a.addEventListener('click', function (event) { event.preventDefault(); page = target; render(); });
                li.appendChild(a); nav.appendChild(li);
            };

            function filteredRows() {
                const criteria = filterRoot ? AdminMultiFilter.getCriteria(filterRoot) : [];
                return rows.filter(row => AdminMultiFilter.matches(row, criteria));
            }

            function sortedRows(items) {
                return items.slice().sort((left, right) => {
                    if (!sortKey) return Number(left.dataset.originalIndex) - Number(right.dataset.originalIndex);

                    const datasetKey = 'sort' + sortKey.charAt(0).toUpperCase() + sortKey.slice(1);
                    const leftValue = left.dataset[datasetKey] || '';
                    const rightValue = right.dataset[datasetKey] || '';
                    let comparison = sortKey === 'index'
                        ? Number(leftValue) - Number(rightValue)
                        : normalize(leftValue).localeCompare(normalize(rightValue), 'id', { numeric: true, sensitivity: 'base' });

                    if (comparison === 0) comparison = Number(left.dataset.originalIndex) - Number(right.dataset.originalIndex);
                    return sortDirection === 'asc' ? comparison : -comparison;
                });
            }

            function updateSortButtons() {
                sortButtons.forEach(sortButton => {
                    const key = sortButton.dataset.roomSort;
                    const icon = sortButton.querySelector('i');
                    const heading = sortButton.closest('th');
                    const active = key === sortKey;
                    if (icon) icon.className = active
                        ? (sortDirection === 'asc' ? 'bi bi-sort-up-alt' : 'bi bi-sort-down')
                        : 'bi bi-arrow-down-up';
                    if (heading) heading.setAttribute('aria-sort', active ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
                });
            }

            function render() {
                const currentRows = sortedRows(filteredRows());
                const size = pageSize(currentRows.length);
                const totalPages = Math.max(1, Math.ceil(currentRows.length / size));
                page = Math.max(1, Math.min(page, totalPages));
                const start = (page - 1) * size;
                const end = start + size;

                rows.forEach(row => { row.hidden = true; });
                currentRows.forEach((row, index) => {
                    if (body) body.insertBefore(row, empty || null);
                    row.hidden = index < start || index >= end;
                });

                if (empty) empty.hidden = currentRows.length !== 0;
                if (total) total.textContent = String(currentRows.length);
                status.textContent = 'Halaman: ' + page + ' dari ' + totalPages;
                nav.innerHTML = '';
                button('Previous', Math.max(1, page - 1), page === 1, false);
                for (let index = 1; index <= totalPages; index += 1) button(String(index), index, false, index === page);
                button('Next', Math.min(totalPages, page + 1), page === totalPages, false);
                updateSortButtons();
            }

            filterRoot?.addEventListener('admin-multi-filter-change', function () { page = 1; render(); });
            select.addEventListener('change', function () { page = 1; render(); });

            sortButtons.forEach(sortButton => {
                sortButton.addEventListener('click', function () {
                    const nextKey = sortButton.dataset.roomSort;
                    if (sortKey === nextKey) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortKey = nextKey;
                        sortDirection = 'asc';
                    }
                    page = 1;
                    render();
                });
            });

            render();
        }());
    </script>
</body>
</html>
