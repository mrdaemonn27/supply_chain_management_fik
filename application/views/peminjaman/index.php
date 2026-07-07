<?php
/**
 * @var array $barang
 */
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
        .navbar-custom { background-color: #1a1a1a; padding: 12px 0; }
        .navbar-dark .navbar-nav .nav-link { color: #e0e0e0; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }

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
        
        /* Placeholder Gambar (Kalau belum ada gambar asli di DB) */
        .item-img-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #f1f2f6 0%, #dfe4ea 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a4b0be;
        }
        
        .item-card:hover .item-img-placeholder i {
            transform: scale(1.1);
            color: #ea5b1a;
            transition: 0.3s;
        }

        /* Tombol Pinjam */
        .btn-pinjam {
            background-color: transparent;
            color: #ea5b1a;
            border: 1px solid #ea5b1a;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s;
        }
        .btn-pinjam:hover {
            background-color: #ea5b1a;
            color: white;
        }
    </style>
</head>
<body>

    <!-- NAVBAR (Sama dengan Dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <i class="bi bi-palette-fill fs-2 me-2 text-fik-orange"></i>
                <div>
                    <div style="font-size: 1.1rem; line-height: 1.2; letter-spacing: 1px;">SCM FIK</div>
                    <div style="font-size: 0.6rem; color: #aaa; letter-spacing: 2px;">INDUSTRI KREATIF</div>
                </div>
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
                        <a class="nav-link" href="<?= base_url('index.php/peminjaman') ?>">Ajukan Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a>
                    </li>
                </ul>
            </div>
            
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?= $this->session->userdata('nama'); ?>
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

    <!-- HEADER KATALOG (Ramping, fokus pada judul pencarian) -->
    <div class="catalog-header">
        <div class="container text-center" data-aos="fade-down" data-aos-duration="800">
            <h2 class="fw-bolder mb-2" style="letter-spacing: 1px;">KATALOG <span class="text-fik-orange">ALAT STUDIO</span></h2>
            <p class="text-light opacity-75 mb-0">Daftar inventaris aset Fakultas Industri Kreatif yang tersedia untuk dipinjam saat ini.</p>
        </div>
    </div>

    <!-- KONTEN UTAMA (ETALASE BARANG) -->
    <div class="container py-5">
        
        <!-- Info & Filter Sederhana -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-grid-fill me-2 text-fik-orange"></i>Daftar Barang Tersedia</h5>
            <span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-box-seam me-1"></i> Total: <?= count($barang) ?> Aset</span>
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
                    <!-- Placeholder Gambar (Ikon Otomatis menyesuaikan) -->
                    <div class="item-img-placeholder">
                        <?php 
                            // Logika sederhana: ganti ikon berdasarkan nama aset (opsional)
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
                    </div>
                    
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-light text-secondary border" style="font-family: monospace; font-size:0.75rem;"><?= $b->kode_aset ?></span>
                            <!-- Label Kondisi (Anggap saja semua yg tampil kondisinya Baik) -->
                            <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i>Baik</span>
                        </div>
                        
                        <h6 class="card-title fw-bold mb-2 text-dark" style="line-height: 1.4;"><?= $b->nama_aset ?></h6>
                        
                        <!-- Info Lokasi Ruangan -->
                        <div class="mt-auto pt-3">
                            <div class="d-flex align-items-center text-muted small mb-2">
                                <i class="bi bi-geo-alt-fill text-fik-orange me-2"></i> 
                                <span class="text-truncate"><?= $b->nama_ruangan ?: 'Gudang Lab' ?></span>
                            </div>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="bi bi-boxes text-fik-orange me-2"></i> 
                                Stok Tersedia: <strong class="ms-1 text-dark fs-6"><?= $b->jumlah_tersedia ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tombol Aksi di bagian bawah kartu -->
                    <div class="card-footer bg-white border-top-0 p-3 pt-0 mt-auto">
                        <!-- Nanti tombol ini akan kita arahkan ke form pengajuan -->
                        <a href="<?= base_url('index.php/peminjaman/ajukan/'.$b->id_aset) ?>" class="btn btn-pinjam w-100 py-2">
                            <i class="bi bi-cart-plus me-1"></i> Ajukan Pinjam
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
    </script>
</body>
</html>