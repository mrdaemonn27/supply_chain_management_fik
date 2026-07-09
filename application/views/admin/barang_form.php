<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($aset) ? 'Edit' : 'Tambah' ?> Master Data - Laboran SCM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
    </style>
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-dark text-white p-4 rounded-top-4">
                        <h5 class="m-0 fw-bold text-warning"><i class="bi bi-database-add me-2"></i> Form <?= isset($aset) ? 'Edit' : 'Input' ?> Master Data Aset</h5>
                        <small class="opacity-75">Pastikan data yang diinput sesuai dengan kode inventaris fisik laboratorium.</small>
                    </div>
                    <div class="card-body p-4 p-md-5">
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
                                <label class="form-label fw-semibold">Foto / Gambar Aset</label>
                                <div class="drop-zone shadow-sm" id="dropZone">
                                    <input type="file" name="gambar" id="fileInput" accept="image/jpeg, image/png, image/jpg, image/webp">

                                    <?php
                                    $ada_gambar = (isset($aset) && !empty($aset->gambar));

                                    // GANTI BARIS INI: Tambahkan folder direktori tempat gambar barang disimpan.
                                    // Asumsi foldernya adalah 'assets/uploads/barang/' (Sesuaikan jika nama foldermu berbeda)
                                    $gambar_url = $ada_gambar ? base_url('assets/uploads/barang/' . $aset->gambar) : '#';

                                    $gambar_text = $ada_gambar ? '<i class="bi bi-info-circle me-1"></i>Gambar saat ini (Abaikan jika tidak diubah)' : '';
                                    ?>

                                    <div id="previewContainer" class="preview-container <?= $ada_gambar ? 'd-block' : 'd-none' ?>">
                                        <div class="preview-wrapper">
                                            <img id="imagePreview" src="<?= $gambar_url ?>" data-default-src="<?= $gambar_url ?>" alt="Preview" class="img-thumbnail shadow-sm mb-2" style="max-height: 160px; border-radius: 8px;">
                                            <button type="button" id="btnRemovePreview" class="btn-remove-preview" title="Batal Pilih Foto">
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
                                        <p class="text-muted small mb-0 mt-2">Format yang didukung: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</p>
                                    </div>
                                </div>
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
                                        <option value="Rusak Ringan" <?= (isset($aset) && $aset->kondisi == 'Rusak Ringan') ? 'selected' : '' ?>>Rusak Ringan (Butuh Servis)</option>
                                        <option value="Rusak Berat" <?= (isset($aset) && $aset->kondisi == 'Rusak Berat') ? 'selected' : '' ?>>Rusak Berat / Afkir</option>
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
            const fileNameDisplay = document.getElementById('fileName');
            const btnRemovePreview = document.getElementById('btnRemovePreview');

            // Ambil data default foto lama (jika sedang di form edit)
            const defaultSrc = imagePreview ? imagePreview.getAttribute('data-default-src') : '#';
            const defaultText = fileNameDisplay ? fileNameDisplay.getAttribute('data-default-text') : '';

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
                    // Validasi bahwa file adalah gambar (Frontend validation)
                    if (!file.type.match('image.*')) {
                        alert('Format file ditolak! Pastikan Anda mengunggah format gambar yang didukung (JPG, PNG, WEBP).');
                        fileInput.value = ''; // Reset input agar tidak error di PHP
                        return;
                    }

                    // Proses baca file lokal ke browser tanpa perlu upload ke server dulu
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
                if (btnRemovePreview) {
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
    </script>
</body>

</html>