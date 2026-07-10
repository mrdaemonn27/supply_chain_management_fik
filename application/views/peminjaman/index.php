<?php
/**
 * @var array $barang
 */
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Alat Studio - SCM FIK</title>
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        /* CUSTOM COLOR PALETTE FIK (Konsisten dengan Dashboard) */
        .text-fik-orange { color: #ea5b1a !important; }
        .bg-fik-orange { background-color: #ea5b1a !important; }
        .bg-fik-orange-light { background-color: rgba(234, 91, 26, 0.1) !important; }
        .text-fik-brown { color: #5d3315 !important; }
        
        /* Navbar Dinamis (Sama persis dengan Dashboard) */
        .navbar-custom { background-color: #ffffff; padding: 12px 0; border-bottom: 2px solid #ea5b1a; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .navbar-dark .navbar-nav .nav-link { color: #333333; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }
        .internal-doc-frame { width: 100%; height: min(78vh, 760px); border: 0; border-radius: 0 0 8px 8px; background: #f7f8fa; }
        .btn-doc-mini {
            border-radius: 999px;
            font-weight: 700;
            padding: 7px 12px;
            background-color: #ea5b1a;
            border: 1px solid #ea5b1a;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-doc-mini:hover,
        .btn-doc-mini:focus,
        .btn-doc-mini:active,
        .btn-doc-mini.active,
        .btn-doc-mini.show {
            background-color: #c24a13;
            border-color: #c24a13;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(234, 91, 26, 0.25);
        }

        /* Header Katalog (Slim & Elegan, bukan hero besar) */
        .catalog-header {
            background: linear-gradient(rgba(26, 26, 26, 0.9), rgba(26, 26, 26, 0.95)), url('https://images.unsplash.com/photo-1601506521937-0121a7fc2a6b?auto=format&fit=crop&q=80') center/cover;
            padding: 60px 0 40px 0;
            color: white;
            border-bottom: 5px solid #ea5b1a;
        }

        /* Styling Kartu Barang (Etalase) */
        .item-card {
            border: none;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(234, 91, 26, 0.15);
            border-bottom: 3px solid #ea5b1a;
        }
        
        /* Placeholder/Wadah Gambar Dinamis */
        .item-img-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #f1f2f6 0%, #dfe4ea 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a4b0be;
            position: relative;
            overflow: hidden;
        }
        .item-img-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        /* Efek Zoom saat dihover */
        .item-card:hover .item-img-placeholder i {
            transform: scale(1.1);
            color: #ea5b1a;
            transition: 0.3s;
        }
        .item-card:hover .item-img-placeholder img {
            transform: scale(1.05);
        }

        /* Tombol Pinjam */
        .btn-pinjam {
            background-color: transparent;
            color: #ea5b1a;
            border: 1px solid #ea5b1a;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s;
            display: block;
            text-align: center;
            text-decoration: none;
        }
        .btn-pinjam:hover {
            background-color: #ea5b1a;
            color: white;
        }
        .btn-pinjam.disabled {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #6c757d;
            cursor: not-allowed;
        }

        .sop-modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }
        .sop-modal-header {
            background: linear-gradient(135deg, #5d3315, #2c1607);
            color: #ffffff;
            border-bottom: 4px solid #ea5b1a;
        }
        .sop-asset-summary {
            background: #fff7f2;
            border: 1px solid rgba(234, 91, 26, 0.2);
            border-radius: 10px;
            padding: 14px 16px;
        }
        .sop-scroll-box {
            height: min(42vh, 330px);
            min-height: 220px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 18px;
            background: #ffffff;
            scroll-behavior: smooth;
        }
        .sop-scroll-box:focus {
            outline: 3px solid rgba(234, 91, 26, 0.18);
            border-color: #ea5b1a;
        }
        .sop-scroll-box li {
            margin-bottom: 12px;
            line-height: 1.55;
        }
        .sop-check-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 14px 16px;
            background: #f8f9fa;
        }
        .sop-check-card.is-ready {
            border-color: rgba(25, 135, 84, 0.45);
            background: rgba(25, 135, 84, 0.06);
        }
        .btn-sop-continue {
            background-color: #ea5b1a;
            border-color: #ea5b1a;
            color: #ffffff;
            font-weight: 700;
            border-radius: 8px;
            padding: 10px 18px;
        }
        .btn-sop-continue:hover,
        .btn-sop-continue:focus {
            background-color: #c24a13;
            border-color: #c24a13;
            color: #ffffff;
        }
        .btn-sop-continue.disabled {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #6c757d;
            pointer-events: none;
        }
        @media (max-width: 575.98px) {
            .sop-modal-content {
                border-radius: 0;
            }
            .sop-scroll-box {
                height: 45vh;
                min-height: 240px;
                padding: 14px;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR (Sama dengan Dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= base_url('index.php/dashboard') ?>">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo FIK" height="40" class="me-2">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php/dashboard') ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= base_url('index.php/peminjaman') ?>">Total Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="alert('Silakan pilih alat studio yang ingin dipinjam terlebih dahulu di menu Total Barang.'); return false;">Ajukan Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a>
                    </li>
                </ul>
            </div>
            
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?= $display_nama; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="border-radius: 12px; mt-2">
                        <li>
                            <div class="px-3 py-2">
                                <span class="d-block text-muted small">ID/NIM:</span>
                                <span class="fw-bold"><?= $this->session->userdata('username'); ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="<?= base_url('index.php/auth/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- HEADER KATALOG -->
    <div class="catalog-header">
        <div class="container text-center" data-aos="fade-down" data-aos-duration="800">
            <h2 class="fw-bolder mb-2" style="letter-spacing: 1px;">KATALOG <span class="text-fik-orange">ALAT STUDIO</span></h2>
            <p class="text-light opacity-75 mb-0">Daftar inventaris aset Fakultas Industri Kreatif yang tersedia untuk dipinjam saat ini.</p>
        </div>
    </div>

    <!-- KONTEN UTAMA (ETALASE BARANG) -->
    <div class="container py-5">
        
        <!-- Info & Filter Sederhana -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-grid-fill me-2 text-fik-orange"></i>Daftar Barang Tersedia</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-doc-mini" data-bs-toggle="modal" data-bs-target="#internalDocsModal">
                    <i class="bi bi-file-earmark-pdf me-1"></i> SOP & Instruksi Kerja
                </button>
                <span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-box-seam me-1"></i> Total: <?= count($barang) ?> Aset</span>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Jika tidak ada barang di database -->
            <?php if(empty($barang)): ?>
                <div class="col-12 text-center py-5" data-aos="fade-up">
                    <div class="bg-light rounded-4 p-5">
                        <i class="bi bi-inboxes text-muted mb-3" style="font-size: 5rem;"></i>
                        <h4 class="fw-bold text-dark">Oops, Etalase Kosong!</h4>
                        <p class="text-muted">Saat ini belum ada alat studio yang tersedia atau stok sedang habis dipinjam.</p>
                        <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-outline-secondary mt-3 px-4 rounded-pill">Kembali ke Beranda</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Looping Kartu Barang dari Database -->
            <?php foreach($barang as $index => $b): ?>
            <div class="col-sm-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                <div class="card item-card">
                    <!-- LOGIKA GAMBAR DINAMIS (DIPERBAIKI: Hapus file_exists) -->
                    <div class="item-img-placeholder">
                        <?php if(!empty($b->gambar)): ?>
                            <!-- Tampilkan Foto Asli jika ada nama filenya -->
                            <img src="<?= base_url('assets/uploads/barang/'.$b->gambar) ?>" alt="<?= $b->nama_aset ?>" onerror="this.onerror=null; this.src='https://placehold.co/400x300?text=No+Image';">
                        <?php else: ?>
                            <!-- Tampilkan Ikon Pintar jika belum ada foto -->
                            <?php 
                                $nama_lower = strtolower($b->nama_aset);
                                if(strpos($nama_lower, 'kamera') !== false || strpos($nama_lower, 'dslr') !== false) {
                                    echo '<i class="bi bi-camera" style="font-size: 4.5rem; transition: 0.3s;"></i>';
                                } elseif(strpos($nama_lower, 'komputer') !== false || strpos($nama_lower, 'pc') !== false || strpos($nama_lower, 'mac') !== false) {
                                    echo '<i class="bi bi-pc-display" style="font-size: 4.5rem; transition: 0.3s;"></i>';
                                } elseif(strpos($nama_lower, 'tablet') !== false || strpos($nama_lower, 'wacom') !== false) {
                                    echo '<i class="bi bi-tablet-landscape" style="font-size: 4.5rem; transition: 0.3s;"></i>';
                                } else {
                                    echo '<i class="bi bi-box-seam" style="font-size: 4.5rem; transition: 0.3s;"></i>';
                                }
                            ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-light text-secondary border" style="font-family: monospace; font-size:0.75rem;"><?= $b->kode_aset ?></span>
                            <!-- Label Kondisi Sinkron Database -->
                            <span class="badge <?= ($b->kondisi == 'Baik') ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-warning bg-opacity-10 text-dark border border-warning' ?>">
                                <?= $b->kondisi ?>
                            </span>
                        </div>
                        
                        <h6 class="card-title fw-bold mb-2 text-dark" style="line-height: 1.4;"><?= $b->nama_aset ?></h6>
                        
                        <!-- Info Lokasi Ruangan & Stok -->
                        <div class="mt-auto pt-3">
                            <div class="d-flex align-items-center text-muted small mb-2">
                                <i class="bi bi-geo-alt-fill text-fik-orange me-2"></i> 
                                <span class="text-truncate"><?= isset($b->nama_ruangan) ? $b->nama_ruangan : 'Laboratorium Pusat' ?></span>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-boxes text-fik-orange me-2"></i> 
                                Stok Tersedia: <strong class="ms-1 text-dark fs-6"><?= $b->jumlah_tersedia ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Aksi di bagian bawah kartu -->
                    <div class="card-footer bg-white border-top-0 p-3 pt-0 mt-auto">
                        <?php if($b->jumlah_tersedia > 0): ?>
                            <button type="button"
                                class="btn btn-pinjam js-open-sop w-100 py-2"
                                data-sop-url="<?= base_url('index.php/peminjaman/ajukan/'.$b->id_aset) ?>"
                                data-sop-name="<?= html_escape($b->nama_aset) ?>"
                                data-sop-code="<?= html_escape($b->kode_aset) ?>"
                                data-sop-room="<?= html_escape(isset($b->nama_ruangan) ? $b->nama_ruangan : 'Laboratorium Pusat') ?>"
                                data-sop-stock="<?= (int) $b->jumlah_tersedia ?>">
                                <i class="bi bi-cart-plus me-1"></i> Ajukan Pinjam
                            </button>
                        <?php else: ?>
                            <!-- Tombol mati jika stok 0 -->
                            <button class="btn btn-pinjam disabled w-100 py-2" disabled>
                                <i class="bi bi-x-circle me-1"></i> Stok Habis
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <div class="modal fade" id="internalDocsModal" tabindex="-1" aria-labelledby="internalDocsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <div>
                        <p class="small text-uppercase text-warning fw-bold mb-1">Dokumen Internal</p>
                        <h5 class="modal-title fw-bold mb-0" id="internalDocsModalLabel">SOP & Instruksi Kerja</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <iframe class="internal-doc-frame js-internal-doc-frame" data-src="<?= base_url('index.php/dokumen_internal/popup') ?>" title="Dokumen Internal SOP dan Instruksi Kerja"></iframe>
            </div>
        </div>
    </div>
    <!-- MODAL SOP PEMINJAMAN -->
    <div class="modal fade" id="sopModal" tabindex="-1" aria-labelledby="sopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <div class="modal-content sop-modal-content shadow-lg">
                <div class="modal-header sop-modal-header">
                    <div>
                        <p class="text-fik-orange fw-bold small mb-1 text-uppercase">SOP Peminjaman Barang</p>
                        <h5 class="modal-title fw-bold mb-0" id="sopModalLabel">Baca Ketentuan Sebelum Melanjutkan</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <div class="sop-asset-summary mb-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <div>
                                <span class="small text-muted d-block">Barang yang akan dipinjam</span>
                                <strong class="text-dark" id="sopAssetName">-</strong>
                                <span class="small text-muted d-block font-monospace" id="sopAssetCode">-</span>
                            </div>
                            <div class="text-md-end">
                                <span class="small text-muted d-block">Lokasi dan stok</span>
                                <strong class="text-dark" id="sopAssetRoom">-</strong>
                                <span class="small text-muted d-block" id="sopAssetStock">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="sop-scroll-box" id="sopScrollBox" tabindex="0" role="region" aria-label="Isi SOP peminjaman barang">
                        <ol class="mb-0 ps-3 ps-md-4">
                            <li>Peminjam wajib menggunakan akun pribadi dan mengisi data pengajuan sesuai identitas asli.</li>
                            <li>Barang hanya dapat dipinjam untuk kegiatan akademik, praktikum, produksi, kepanitiaan, atau aktivitas resmi yang relevan dengan Fakultas Industri Kreatif.</li>
                            <li>Peminjam wajib memastikan jumlah barang, kode aset, kondisi fisik, dan kelengkapan aksesori sebelum pengajuan dikirim.</li>
                            <li>Peminjam wajib mengunggah foto kondisi awal barang yang jelas, tidak buram, dan memperlihatkan kelengkapan utama barang.</li>
                            <li>Setelah pengajuan dikirim, peminjaman masih berstatus menunggu persetujuan. Barang belum boleh diambil sebelum pengajuan disetujui oleh petugas terkait.</li>
                            <li>Barang wajib diambil dan dikembalikan sesuai tanggal yang diajukan. Perubahan jadwal harus dikonfirmasi kepada laboran atau petugas aset.</li>
                            <li>Peminjam bertanggung jawab menjaga barang dari kehilangan, kerusakan, kelalaian penggunaan, dan penggunaan di luar keperluan yang diajukan.</li>
                            <li>Barang tidak boleh dipindahtangankan kepada pihak lain tanpa izin petugas aset.</li>
                            <li>Jika terjadi kerusakan, kehilangan, atau kendala saat penggunaan, peminjam wajib segera melapor kepada laboran atau petugas aset.</li>
                            <li>Saat pengembalian, barang harus dalam kondisi lengkap dan sesuai kondisi awal. Petugas berhak melakukan pemeriksaan sebelum transaksi dinyatakan selesai.</li>
                            <li>Pelanggaran SOP dapat menyebabkan pengajuan ditolak, pembatasan peminjaman berikutnya, atau tindak lanjut sesuai ketentuan fakultas.</li>
                            <li>Dengan melanjutkan, peminjam menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan peminjaman barang.</li>
                        </ol>
                    </div>

                    <div class="d-flex align-items-start gap-2 mt-2" id="sopScrollHint">
                        <i class="bi bi-arrow-down-circle text-fik-orange mt-1"></i>
                        <small class="text-muted">Scroll SOP sampai bagian akhir untuk membuka checkbox persetujuan.</small>
                    </div>

                    <div class="sop-check-card mt-3" id="sopCheckCard">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="sopAgreeCheck" disabled>
                            <label class="form-check-label fw-semibold" for="sopAgreeCheck">
                                Saya sudah membaca SOP sampai selesai dan menyetujui ketentuan peminjaman barang ini.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-column flex-sm-row gap-2 p-3 p-md-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <a href="#" class="btn btn-sop-continue disabled w-100 w-sm-auto" id="sopContinueBtn" aria-disabled="true">
                        Lanjut ke Form Peminjaman <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- FOOTER SEDERHANA KHUSUS HALAMAN DALAM -->
    <footer class="bg-dark text-center py-4 mt-5">
        <div class="container">
            <p class="small text-white opacity-50 m-0">
                &copy; <?= date('Y') ?> SCM Fakultas Industri Kreatif - Telkom University. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 20 });

        document.addEventListener('DOMContentLoaded', function () {
            const internalDocsModal = document.getElementById('internalDocsModal');
            const internalDocsFrame = document.querySelector('.js-internal-doc-frame');

            if (internalDocsModal && internalDocsFrame) {
                internalDocsModal.addEventListener('show.bs.modal', function () {
                    if (!internalDocsFrame.getAttribute('src')) {
                        internalDocsFrame.setAttribute('src', internalDocsFrame.dataset.src);
                    }
                });

                internalDocsModal.addEventListener('hidden.bs.modal', function () {
                    internalDocsFrame.removeAttribute('src');
                });
            }
            const sopModalElement = document.getElementById('sopModal');
            const sopModal = new bootstrap.Modal(sopModalElement);
            const sopScrollBox = document.getElementById('sopScrollBox');
            const sopAgreeCheck = document.getElementById('sopAgreeCheck');
            const sopContinueBtn = document.getElementById('sopContinueBtn');
            const sopCheckCard = document.getElementById('sopCheckCard');
            const sopScrollHint = document.getElementById('sopScrollHint');
            const sopAssetName = document.getElementById('sopAssetName');
            const sopAssetCode = document.getElementById('sopAssetCode');
            const sopAssetRoom = document.getElementById('sopAssetRoom');
            const sopAssetStock = document.getElementById('sopAssetStock');

            let targetUrl = '#';
            let hasReadSop = false;

            function setContinueState() {
                const canContinue = hasReadSop && sopAgreeCheck.checked;

                if (canContinue) {
                    sopContinueBtn.classList.remove('disabled');
                    sopContinueBtn.setAttribute('href', targetUrl);
                    sopContinueBtn.setAttribute('aria-disabled', 'false');
                } else {
                    sopContinueBtn.classList.add('disabled');
                    sopContinueBtn.setAttribute('href', '#');
                    sopContinueBtn.setAttribute('aria-disabled', 'true');
                }
            }

            function resetSopState(button) {
                targetUrl = button.dataset.sopUrl || '#';
                hasReadSop = false;
                sopAgreeCheck.checked = false;
                sopAgreeCheck.disabled = true;
                sopCheckCard.classList.remove('is-ready');
                sopScrollBox.scrollTop = 0;
                sopAssetName.textContent = button.dataset.sopName || '-';
                sopAssetCode.textContent = button.dataset.sopCode || '-';
                sopAssetRoom.textContent = button.dataset.sopRoom || '-';
                sopAssetStock.textContent = 'Stok tersedia: ' + (button.dataset.sopStock || '0') + ' unit';
                sopScrollHint.innerHTML = '<i class="bi bi-arrow-down-circle text-fik-orange mt-1"></i><small class="text-muted">Scroll SOP sampai bagian akhir untuk membuka checkbox persetujuan.</small>';
                setContinueState();
            }

            function markSopAsReadIfNeeded() {
                const reachedBottom = sopScrollBox.scrollTop + sopScrollBox.clientHeight >= sopScrollBox.scrollHeight - 8;

                if (reachedBottom && !hasReadSop) {
                    hasReadSop = true;
                    sopAgreeCheck.disabled = false;
                    sopCheckCard.classList.add('is-ready');
                    sopScrollHint.innerHTML = '<i class="bi bi-check-circle-fill text-success mt-1"></i><small class="text-success fw-semibold">SOP sudah dibaca sampai akhir. Silakan centang persetujuan untuk lanjut.</small>';
                }

                setContinueState();
            }

            document.querySelectorAll('.js-open-sop').forEach(function (button) {
                button.addEventListener('click', function () {
                    resetSopState(button);
                    sopModal.show();

                    setTimeout(function () {
                        sopScrollBox.focus({ preventScroll: true });
                        markSopAsReadIfNeeded();
                    }, 250);
                });
            });

            sopScrollBox.addEventListener('scroll', markSopAsReadIfNeeded);
            sopAgreeCheck.addEventListener('change', setContinueState);

            sopContinueBtn.addEventListener('click', function (event) {
                if (!hasReadSop || !sopAgreeCheck.checked) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>