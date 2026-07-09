<?php
/** @var object $aset */
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Peminjaman - SCM FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }

        /* Palette FIK */
        .text-fik-orange { color: #ea5b1a !important; }
        .bg-fik-orange { background-color: #ea5b1a !important; }
        .bg-fik-brown { background-color: #5d3315 !important; }

        /* Navbar Dinamis */
        .navbar-custom { background-color: #ffffff; padding: 12px 0; border-bottom: 2px solid #ea5b1a; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .navbar-dark .navbar-nav .nav-link { color: #333333; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }

        /* Form Card Styling */
        .form-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .info-card { background: linear-gradient(135deg, #5d3315, #3a1e0a); color: white; border-radius: 15px; border: none; }
        
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; border: 1px solid #dee2e6; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 0.25rem rgba(234, 91, 26, 0.25); }
        .btn-submit { background-color: #ea5b1a; color: white; font-weight: 600; padding: 12px; border-radius: 8px; border: none; transition: 0.3s; }
        .btn-submit:hover { background-color: #c24a13; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(234, 91, 26, 0.3); }
        
        /* Tambahan untuk menjaga ukuran gambar agar proporsional */
        .aset-thumbnail { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

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
            z-index: 3;
            pointer-events: none; 
        }
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
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .btn-remove-preview:hover {
            background-color: #bb2d3b;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="300" class="me-2">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/dashboard') ?>">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/peminjaman') ?>">Total Barang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Ajukan Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a></li>
                </ul>
            </div>
            <div class="d-none d-lg-block">
                <button class="btn btn-user"><i class="bi bi-person-circle me-1"></i> <?= $display_nama; ?></button>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="mb-4 text-center" data-aos="fade-down">
            <h2 class="fw-bold text-dark">FORM PENGAJUAN <span class="text-fik-orange">PEMINJAMAN</span></h2>
            <p class="text-muted">Lengkapi data di bawah ini dengan valid sesuai SOP Laboratorium.</p>
        </div>

        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" data-aos="shake">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4" data-aos="fade-right">
                <div class="card info-card h-100 p-4">
                    <h5 class="fw-bold text-fik-orange mb-4"><i class="bi bi-box-seam me-2"></i>Detail Aset</h5>
                    
                    <div class="bg-white bg-opacity-10 rounded-3 p-3 mb-4 text-center">
                        <?php if(!empty($aset->gambar)): ?>
                            <img src="<?= base_url('assets/uploads/barang/'.$aset->gambar) ?>" alt="<?= $aset->nama_aset ?>" class="aset-thumbnail">
                        <?php else: ?>
                            <i class="bi bi-camera" style="font-size: 4rem; color: #f8f9fa; opacity: 0.8;"></i>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold text-white mb-1"><?= $aset->nama_aset ?></h5>
                    <p class="text-white opacity-75 small mb-4 font-monospace"><?= $aset->kode_aset ?></p>

                    <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-3">
                        <span class="opacity-75 small">Lokasi Tumpukan</span>
                        <span class="fw-bold small"><?= $aset->nama_ruangan ?: 'Gudang' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-3">
                        <span class="opacity-75 small">Kondisi Sistem</span>
                        <span class="badge bg-success">Baik</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75 small">Stok Maksimal Tersedia</span>
                        <span class="fw-bold fs-5 text-fik-orange"><?= $aset->jumlah_tersedia ?> Unit</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8" data-aos="fade-left">
                <div class="card form-card p-4 p-md-5">
                    <form action="<?= base_url('index.php/peminjaman/proses_pengajuan') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_aset" value="<?= $aset->id_aset ?>">

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Peminjam</label>
                                <input type="text" class="form-control bg-light" value="<?= $display_nama; ?>" readonly>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="form-label fw-semibold text-muted small">Jumlah Pinjam <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah_pinjam" class="form-control" min="1" max="<?= $aset->jumlah_tersedia ?>" value="1" required>
                                <small class="text-danger" style="font-size: 0.7rem;">Maksimal <?= $aset->jumlah_tersedia ?> unit</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small"><i class="bi bi-calendar-event me-1"></i> Tanggal Pengambilan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_pinjam" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="form-label fw-semibold text-muted small"><i class="bi bi-calendar-check me-1"></i> Rencana Kembali <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_kembali_rencana" class="form-control" min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4 bg-fik-orange-light p-3 rounded-3 border border-warning border-opacity-25">
                            <label class="form-label fw-bold text-fik-brown"><i class="bi bi-camera-fill me-1"></i> Foto Kondisi Awal Alat <span class="text-danger">*</span></label>
                            
                            <div class="drop-zone shadow-sm bg-white mt-2 mb-2" id="dropZone">
                                <input type="file" name="foto_kondisi" id="fileInput" accept="image/jpeg, image/png, image/jpg, image/webp" required>
                                
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
                                    <i class="bi bi-camera display-4 text-secondary mb-3 d-block"></i>
                                    <h6 class="mb-1 text-dark fs-6"><span class="fw-bold">Pilih file</span> atau drag ke sini.</h6>
                                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Format: JPG/PNG. Maksimal ukuran file: 2MB. Pastikan foto memperlihatkan kelengkapan alat.</small>
                                </div>
                            </div>
                            <label class="form-label fw-semibold text-muted small mt-3">Sesuai pengamatan fisik, kondisi saat ini:</label>
                            <select name="kondisi_saat_pinjam" class="form-select bg-white" required>
                                <option value="Baik">Baik & Lengkap</option>
                                <option value="Rusak Ringan">Ada Cacat/Goresan (Berfungsi Normal)</option>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold text-muted small">Keperluan / Keterangan Proyek <span class="text-danger">*</span></label>
                            <textarea name="keperluan" class="form-control" rows="3" placeholder="Contoh: Digunakan untuk shooting Tugas Akhir film pendek di luar kampus..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-4">
                            <a href="<?= base_url('index.php/peminjaman') ?>" class="text-decoration-none text-muted fw-semibold hover-orange">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-submit px-4 px-md-5">
                                <i class="bi bi-send-check me-2"></i>Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ once: true, offset: 20 });</script>

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

                // Menangkap event CLICK 
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        updatePreview(this.files[0]);
                    }
                });

                function updatePreview(file) {
                    // Validasi bahwa file adalah gambar
                    if (!file.type.match('image.*')) {
                        alert('Format file ditolak! Pastikan Anda mengunggah format gambar yang didukung (JPG, PNG, WEBP).');
                        fileInput.value = ''; // Reset input
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

                        // Selalu kosongkan input file
                        fileInput.value = '';
                        
                        // Kembalikan ke placeholder awan
                        imagePreview.src = '#';
                        fileNameDisplay.innerHTML = '';
                        previewContainer.classList.remove('d-block');
                        previewContainer.classList.add('d-none');
                        placeholderContainer.classList.remove('d-none');
                        placeholderContainer.classList.add('d-block');
                    });
                }
            }
        });
    </script>
</body>
</html>