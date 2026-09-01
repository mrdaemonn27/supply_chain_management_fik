<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($aset) ? 'Edit' : 'Tambah' ?> Master Data - Laboran SCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        /* Custom Style untuk Drag & Drop Zone */
        .drop-zone {
            border: 2px dashed #adb5bd;
            /* Warna border sesuai referensi */
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            background-color: #f4f6f9;
            /* Warna background abu-abu muda elegan */
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            background-color: #e2e8f0;
            border-color: #ea5b1a;
            /* Berubah oranye saat di-hover/drag */
            transform: scale(1.01);
        }

        /* Hidden input memenuhi seluruh div agar bisa diklik di mana saja */
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
            z-index: 3;
            /* Dinaikkan agar di atas input file */
            pointer-events: none;
            /* Membuat klik/drag tembus melewati gambar langsung ke input file */
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
            pointer-events: auto;
            /* Tombol X wajib bisa diklik (tidak tembus) */
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-remove-preview:hover {
            background-color: #bb2d3b;
            transform: scale(1.1);
        }

        .text-fik-orange {
            color: #ea5b1a;
        }

        .asset-model-preview {
            width: min(240px, 100%);
            height: 160px;
            display: inline-block;
            border-radius: 8px;
            background: linear-gradient(145deg, #f7f9fc 0%, #e4eaf1 100%);
            --poster-color: transparent;
            --progress-bar-color: #ea5b1a;
        }
    </style>
</head>

<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-dark text-white p-4 rounded-top-4">
                        <h5 class="m-0 fw-bold text-warning"><i class="bi bi-database-add me-2"></i> Form <?= isset($aset) ? 'Edit' : 'Input' ?> Master Data Aset</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if ($form_error = $this->session->flashdata('error')): ?>
                            <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                <span><?= html_escape($form_error) ?></span>
                            </div>
                        <?php endif; ?>
                        <form action="<?= base_url('index.php/admin/barang/simpan') ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_aset" value="<?= isset($aset) ? $aset->id_aset : '' ?>">

                            <div class="row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Kode Barang / Aset <span class="text-danger">*</span></label>
                                    <input type="text" name="kode_aset" class="form-control bg-light font-monospace" value="<?= isset($aset) ? $aset->kode_aset : '' ?>" placeholder="Contoh: MTL-001" required>
                                </div>
                                <div class="col-md-7 mt-3 mt-md-0">
                                    <label class="form-label fw-semibold">Nama Lengkap Barang <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_aset" class="form-control" value="<?= isset($aset) ? $aset->nama_aset : '' ?>" placeholder="Air Compressor Orange..." required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi / Spesifikasi Aset</label>
                                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Masukkan spesifikasi atau keterangan lengkap barang..."><?= isset($aset) ? $aset->deskripsi : '' ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Lokasi Ruangan / Laboratorium <span class="text-danger">*</span></label>
                                <select name="id_ruangan" class="form-select" required>
                                    <option value="">-- Pilih Penempatan Laboratorium --</option>
                                    <?php foreach ($ruangan as $r): ?>
                                        <?php $selected = (isset($aset) && $aset->id_ruangan == $r->id_ruangan) ? 'selected' : ''; ?>
                                        <option value="<?= $r->id_ruangan ?>" <?= $selected ?>><?= $r->nama_ruangan ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Media Utama Aset</label>
                                <div class="drop-zone shadow-sm" id="dropZone">
                                    <input type="file" name="gambar" id="fileInput" accept="image/jpeg, image/png, image/jpg, image/webp,.glb,.gltf,model/gltf-binary,model/gltf+json">

                                    <?php
                                    $ada_gambar = (isset($aset) && !empty($aset->gambar));
                                    $primary_is_3d = $ada_gambar && in_array(strtolower(pathinfo($aset->gambar, PATHINFO_EXTENSION)), ['glb', 'gltf'], true);

                                    // GANTI BARIS INI: Tambahkan folder direktori tempat gambar barang disimpan.
                                    // Asumsi foldernya adalah 'assets/uploads/barang/' (Sesuaikan jika nama foldermu berbeda)
                                    $gambar_url = $ada_gambar ? base_url('assets/uploads/barang/' . rawurlencode($aset->gambar)) : '#';

                                    $gambar_text = $ada_gambar ? '<i class="bi bi-info-circle me-1"></i>Media saat ini (Abaikan jika tidak diubah)' : '';
                                    ?>

                                    <div id="previewContainer" class="preview-container <?= $ada_gambar ? 'd-block' : 'd-none' ?>">
                                        <div class="preview-wrapper">
                                            <img id="imagePreview" src="<?= $gambar_url ?>" data-default-src="<?= $gambar_url ?>" alt="Preview media aset" class="img-thumbnail shadow-sm mb-2 <?= $primary_is_3d ? 'd-none' : '' ?>" style="max-height: 160px; border-radius: 8px;">
                                            <model-viewer id="modelPreview" src="<?= $primary_is_3d ? $gambar_url : '' ?>" alt="Preview model 3D aset" class="asset-model-preview shadow-sm mb-2 <?= $primary_is_3d ? '' : 'd-none' ?>" camera-controls disable-pan disable-zoom interaction-prompt="none" touch-action="pan-y" shadow-intensity="0.55"></model-viewer>
                                            <button type="button" id="btnRemovePreview" class="btn-remove-preview" title="Batal Pilih Media">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <p class="small text-muted mb-0 fw-medium" id="fileName" data-default-text="<?= htmlspecialchars($gambar_text) ?>">
                                            <?= $gambar_text ?>
                                        </p>
                                    </div>

                                    <div id="placeholderContainer" class="preview-container <?= $ada_gambar ? 'd-none' : 'd-block' ?>">
                                        <i class="bi bi-download display-4 text-secondary mb-3 d-block"></i>
                                        <h6 class="mb-1 text-dark fs-5"><span class="fw-bold">Pilih file</span> atau drag ke sini.</h6>
                                        <p class="text-muted small mb-0 mt-2">Gambar: JPG, JPEG, PNG, WEBP maksimal 2MB. Model: GLB atau GLTF maksimal 15MB.</p>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $gallery_filenames = [];
                            if (isset($aset) && !empty($aset->foto)) {
                                $gallery_source = json_decode((string) $aset->foto, true);
                                $gallery_filenames = is_array($gallery_source) ? $gallery_source : [$aset->foto];
                                $gallery_filenames = array_values(array_unique(array_filter(array_map('basename', $gallery_filenames))));
                            }
                            ?>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Media Galeri Tambahan <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="file" name="galeri_tambahan[]" class="form-control" accept="image/jpeg, image/png, image/jpg, image/webp,.glb,.gltf,model/gltf-binary,model/gltf+json" multiple>
                                <div class="form-text">Pilih hingga 5 media tambahan. Gambar JPG, JPEG, PNG, WEBP maksimal 2MB; GLB/GLTF maksimal 15MB per file. GLTF harus memakai resource yang tertanam; GLB lebih disarankan.</div>
                                <?php if (!empty($gallery_filenames)): ?>
                                    <div class="d-flex flex-wrap gap-3 mt-3">
                                        <?php foreach ($gallery_filenames as $gallery_filename): ?>
                                            <?php $gallery_is_3d = in_array(strtolower(pathinfo($gallery_filename, PATHINFO_EXTENSION)), ['glb', 'gltf'], true); ?>
                                            <label class="border rounded-3 p-2 bg-light text-center" style="width: 132px; cursor: pointer;">
                                                <?php if ($gallery_is_3d): ?>
                                                    <model-viewer src="<?= base_url('assets/uploads/barang/' . rawurlencode($gallery_filename)) ?>" alt="Model 3D galeri aset" class="rounded-2 mb-2" camera-controls disable-pan disable-zoom interaction-prompt="none" touch-action="pan-y" style="width: 112px; height: 82px; background: #eef1f5;"></model-viewer>
                                                <?php else: ?>
                                                    <img src="<?= base_url('assets/uploads/barang/' . rawurlencode($gallery_filename)) ?>" alt="Gambar galeri aset" class="img-fluid rounded-2 mb-2" style="width: 112px; height: 82px; object-fit: cover;">
                                                <?php endif; ?>
                                                <span class="d-flex align-items-center justify-content-center gap-1 small text-danger">
                                                    <input class="form-check-input m-0" type="checkbox" name="hapus_galeri[]" value="<?= html_escape($gallery_filename) ?>"> Hapus
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="row mb-4 p-3 bg-light rounded-3 border">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Total Unit Tersedia <span class="text-danger">*</span></label>
                                    <input type="number" name="jumlah_total" class="form-control" min="1" value="<?= isset($aset) ? $aset->jumlah_total : '1' ?>" <?= isset($aset) ? 'readonly title="Tidak bisa mengubah stok total melalui form ini. Gunakan fitur Maintenance/Pengadaan."' : 'required' ?>>
                                    <?php if (isset($aset)): ?>
                                        <small class="text-danger d-block mt-1" style="font-size: 0.7rem;">Stok awal fisik tidak bisa diubah via form edit dasar.</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mt-3 mt-md-0">
                                    <label class="form-label fw-semibold">Kondisi Fisik <span class="text-danger">*</span></label>
                                    <select name="kondisi" class="form-select border-warning" required>
                                        <option value="Baik" <?= (isset($aset) && $aset->kondisi == 'Baik') ? 'selected' : '' ?>>Baik & Berfungsi</option>
                                        <option value="Rusak" <?= (isset($aset) && in_array($aset->kondisi, ['Rusak','Rusak Ringan','Rusak Berat'], true)) ? 'selected' : '' ?>>Rusak (Butuh tindak lanjut)</option>
                                        <option value="Hilang" <?= (isset($aset) && $aset->kondisi == 'Hilang') ? 'selected' : '' ?>>Hilang</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between pt-4 border-top">
                                <a href="<?= base_url('index.php/admin/barang') ?>" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
                                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-5"><i class="bi bi-save me-1"></i> Simpan ke Database</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const previewContainer = document.getElementById('previewContainer');
             const placeholderContainer = document.getElementById('placeholderContainer');
             const imagePreview = document.getElementById('imagePreview');
             const modelPreview = document.getElementById('modelPreview');
             const fileNameDisplay = document.getElementById('fileName');
             const btnRemovePreview = document.getElementById('btnRemovePreview');

            // Ambil data default foto lama (jika sedang di form edit)
             const defaultSrc = imagePreview ? imagePreview.getAttribute('data-default-src') : '#';
             const defaultText = fileNameDisplay ? fileNameDisplay.getAttribute('data-default-text') : '';
             const defaultIs3d = <?= $primary_is_3d ? 'true' : 'false' ?>;
             let previewObjectUrl = null;

            if (dropZone && fileInput) {
                // Mencegah browser membuka file gambar di tab baru saat di-drag
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Memberikan efek animasi transisi saat file disorot/drag di atas kotak
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
                });

                // Menangkap event DROP dan mengisi input file secara otomatis
                dropZone.addEventListener('drop', function(e) {
                    let dt = e.dataTransfer;
                    let files = dt.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        updatePreview(files[0]);
                    }
                }, false);

                // Menangkap event CLICK (ketika diklik biasa lewat dialog box Windows)
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        updatePreview(this.files[0]);
                    }
                });

                 function updatePreview(file) {
                     const is3d = /\.(glb|gltf)$/i.test(file.name);
                     const isImage = /\.(gif|jpe?g|png|webp)$/i.test(file.name) || String(file.type || '').toLowerCase().indexOf('image/') === 0;
                     const maxBytes = is3d ? 15 * 1024 * 1024 : 2 * 1024 * 1024;

                     if (!is3d && !isImage) {
                         alert('Format file ditolak! Gunakan JPG, JPEG, PNG, WEBP, GLB, atau GLTF.');
                         fileInput.value = ''; // Reset input agar tidak error di PHP
                         return;
                     }
                     if (file.size > maxBytes) {
                         alert(is3d ? 'Ukuran model terlalu besar. Maksimal 15MB per file.' : 'Ukuran gambar terlalu besar. Maksimal 2MB per file.');
                         fileInput.value = '';
                         return;
                     }

                     if (previewObjectUrl) {
                         URL.revokeObjectURL(previewObjectUrl);
                         previewObjectUrl = null;
                     }
                     if (is3d) {
                         previewObjectUrl = URL.createObjectURL(file);
                         modelPreview.src = previewObjectUrl;
                         modelPreview.classList.remove('d-none');
                         imagePreview.classList.add('d-none');
                         setFileName(file.name);
                         previewContainer.classList.remove('d-none');
                         previewContainer.classList.add('d-block');
                         placeholderContainer.classList.remove('d-block');
                         placeholderContainer.classList.add('d-none');
                         return;
                     }

                     // Baca gambar lokal ke browser tanpa perlu upload ke server dulu.
                     let reader = new FileReader();
                     reader.readAsDataURL(file);
                     reader.onload = function(e) {
                         imagePreview.src = e.target.result;
                         imagePreview.classList.remove('d-none');
                         modelPreview.classList.add('d-none');
                         setFileName(file.name);

                         previewContainer.classList.remove('d-none');
                         previewContainer.classList.add('d-block');
                         placeholderContainer.classList.remove('d-block');
                         placeholderContainer.classList.add('d-none');
                     }
                 }

                 function setFileName(name) {
                     fileNameDisplay.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> File dipilih: <b class="text-dark"></b>';
                     fileNameDisplay.querySelector('b').textContent = name;
                 }

                // Logika Dinamis saat tombol X diklik
                if (btnRemovePreview) {
                    btnRemovePreview.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); // Mencegah form upload terbuka

                        // 1. Selalu kosongkan input file yang baru dipilih
                        fileInput.value = '';

                         // 2. Cek apakah ada foto aslinya di DB (Mode Edit)
                         if (defaultSrc !== '' && defaultSrc !== '#') {
                             // Jika ada, kembalikan ke media lama
                             if (defaultIs3d) {
                                 modelPreview.src = defaultSrc;
                                 modelPreview.classList.remove('d-none');
                                 imagePreview.classList.add('d-none');
                             } else {
                                 imagePreview.src = defaultSrc;
                                 imagePreview.classList.remove('d-none');
                                 modelPreview.classList.add('d-none');
                             }
                             fileNameDisplay.innerHTML = defaultText;
                         } else {
                             // Jika tidak ada media (Mode Tambah), sembunyikan preview & munculkan ikon awan
                             imagePreview.src = '#';
                             modelPreview.removeAttribute('src');
                             imagePreview.classList.add('d-none');
                             modelPreview.classList.add('d-none');
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
    </script>
</body>

</html>
