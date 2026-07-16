<?php
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
$display_role = ($session_role === 'admin') ? 'Laboran' : ucfirst((string) $this->session->userdata('role'));
$can_read_internal_docs = (bool) $this->session->userdata('logged_in');
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SCM - Fakultas Industri Kreatif</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* Tipografi Modern */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* CUSTOM COLOR PALETTE FIK */
        .text-fik-orange { color: #ea5b1a !important; }
        .text-fik-brown { color: #5d3315 !important; }
        .bg-fik-orange-light { background-color: rgba(234, 91, 26, 0.1) !important; }
        .bg-fik-brown-light { background-color: rgba(93, 51, 21, 0.1) !important; }

        /* Navbar Dinamis */
        .navbar-custom {
            background-color: #ffffff;
            padding: 12px 0;
            transition: all 0.3s ease-in-out;
            border-bottom: 5px solid #ea5b1a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .navbar-dark .navbar-nav .nav-link {
            color: #333333;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0 12px;
            transition: 0.3s;
            position: relative;
        }
        .navbar-dark .navbar-nav .nav-link:hover, 
        .navbar-dark .navbar-nav .nav-link.active {
            color: #ea5b1a;
        }
        .navbar-dark .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0; height: 2px;
            display: block;
            margin-top: 5px;
            right: 0;
            background: #ea5b1a;
            transition: width 0.3s ease;
        }
        .navbar-dark .navbar-nav .nav-link:hover::after {
            width: 100%; left: 0; background: #ea5b1a;
        }

        .btn-user {
            background: linear-gradient(45deg, #c24a13, #ea5b1a);
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            box-shadow: 0 4px 15px rgba(234, 91, 26, 0.3);
            transition: 0.3s;
        }
        .btn-user:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234, 91, 26, 0.4);
        }
        .notif-bell { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }

        .internal-doc-frame { width: 100%; height: min(78vh, 760px); border: 0; border-radius: 0 0 8px 8px; background: #f7f8fa; }
        .btn-doc-internal {
            border: 1px solid rgba(234, 91, 26, 0.75);
            color: #fff;
            background: rgba(234, 91, 26, 0.18);
            border: 1px solid #ea5b1a;
            background: #ea5b1a;
            color: #fff;
            border-radius: 999px;
            padding: 10px 18px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.25s ease;
        }
        .btn-doc-internal:hover {
            background: rgba(234, 91, 26, 0.32);
            border-color: #ea5b1a;
            color: #fff;
            transform: translateY(-2px);
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-doc-internal:hover,
        .btn-doc-internal:focus,
        .btn-doc-internal:active,
        .btn-doc-internal.active {
            background: #c24a13;
            border-color: #c24a13;
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(234, 91, 26, 0.25);
        }

        /* Header Tampilan Awal (Slimmer) */
        .catalog-header {
            background: linear-gradient(rgba(26, 26, 26, 0.85), rgba(26, 26, 26, 0.9)), url('https://images.unsplash.com/photo-1542744094-24638ea0b3b5?auto=format&fit=crop&q=80') center/cover;
            padding: 60px 0 40px 0;
            color: white;
            border-bottom: 5px solid #ea5b1a;
        }
        
        /* Styling Kartu Lab */
        .service-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-bottom: 4px solid #ea5b1a;
        }
        .service-card img, .service-card .card-img-top {
            height: 180px;
            object-fit: cover;
            transition: 0.5s;
        }
        .service-card:hover img {
            transform: scale(1.05);
        }

        /* SOP / Aturan Section */
          .sop-section {
            /* Menambahkan efek gradasi oranye FIK di atas gambar background Gedung.webp */
            background: linear-gradient(rgba(107, 49, 22, 0.85), rgba(115, 43, 12, 0.95)), url('<?= base_url('assets/logo/Gedung.webp'); ?>') center/ cover no-repeat;
            /* DITAMBAHKAN: Memberikan efek parallax agar gambar tidak ikut terscroll */
            background-attachment: fixed;
            padding: 80px 0;
            color: white;
        }

        .rule-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 35px;
            border-radius: 20px;
            transition: 0.3s;
        }
        .rule-card:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #ea5b1a; 
        }

        /* ========================================= */
        /* TAMPILAN FOOTER FIK                       */
        /* ========================================= */
        .footer-fik {
            background-color: #343434;
            color: #f8f9fa;
            padding: 70px 0 30px 0;
            font-size: 0.95rem;
            position: relative;
        }
        .footer-fik .logo-wrap { display: flex; align-items: flex-start; margin-bottom: 25px; }
        .footer-fik .logo-icon { font-size: 2.8rem; line-height: 1; color: #888; margin-right: 15px; }
        .footer-fik .logo-text { color: #ea5b1a; font-weight: 700; font-size: 1.25rem; line-height: 1.2; }
        .footer-fik .logo-subtext { color: #a0a0a0; font-size: 0.85rem; line-height: 1.3; font-weight: 500; }
        .footer-fik .info-text { color: #dcdcdc; line-height: 1.6; margin-bottom: 5px; }
        .social-circle {
            display: inline-flex; align-items: center; justify-content: center;
            width: 38px; height: 38px; background-color: #ffffff; color: #ba4713;
            border-radius: 50%; margin-right: 12px; font-size: 1.2rem; text-decoration: none; transition: all 0.3s ease;
        }
        .social-circle:hover { transform: translateY(-4px); box-shadow: 0 4px 12px rgba(0,0,0,0.4); color: #ffffff; background-color: #ea5b1a; }
        .footer-fik h5 { font-weight: 700; margin-bottom: 1.8rem; font-size: 1.15rem; color: #ffffff; }
        .footer-fik ul { list-style: none; padding-left: 0; }
        .footer-fik ul li { margin-bottom: 0.8rem; position: relative; padding-left: 18px; }
        .footer-fik ul li::before { content: ''; position: absolute; left: 0; top: .55rem; width: 6px; height: 6px; border-radius: 50%; background: #ea5b1a; }
        .footer-fik ul li a { color: #dcdcdc; text-decoration: none; transition: 0.3s; }
        .footer-fik ul li a:hover { color: #ffffff; text-decoration: underline; }
        .footer-fik ul li::before { content: '\2022'; position: absolute; left: 0; top: -2px; color: #ffffff; font-size: 1.2rem; }
        .footer-fik ul li a { 
            color: #ffffff !important; 
            text-decoration: none !important; 
            transition: 0.3s; 
        }
        .footer-fik ul li a:visited { 
            color: #ffffff !important; 
        }
        .footer-fik ul li a:hover { 
            color: #ea5b1a !important; 
            text-decoration: underline; 
        }
        .map-container iframe { width: 100%; height: 350px; border-radius: 15px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="300" class="me-2">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= base_url('index.php/dashboard') ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php/peminjaman') ?>">Total Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="alert('Silakan pilih alat studio yang ingin dipinjam terlebih dahulu di menu Total Barang.'); return false;">Ajukan Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a>
                    </li>
                    
                    <?php if (strtolower($this->session->userdata('role')) == 'admin'): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link text-fik-orange fw-bold" href="<?= base_url('index.php/admin/dashboard') ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard Laboran
                        </a>
                    </li>
                    <?php elseif (strtolower($this->session->userdata('role')) == 'kaur'): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link text-fik-orange fw-bold" href="<?= base_url('index.php/kaur/dashboard') ?>">
                            <i class="bi bi-diagram-3 me-1"></i> Dashboard Kaur
                        </a>
                    </li>
                    <?php elseif (strtolower($this->session->userdata('role')) == 'kaprodi'): ?>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link text-fik-orange fw-bold" href="<?= base_url('index.php/kaprodi/dashboard') ?>">
                            <i class="bi bi-table me-1"></i> Dashboard Kaprodi
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
           <div class="d-none d-lg-flex align-items-center gap-2">
                <?php if($this->session->userdata('logged_in')): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                            <div class="fw-bold px-2 py-1">Notifikasi</div>
                            <?php if (empty($notif_items)): ?>
                                <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                                <a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <!-- Dropdown Jika SUDAH LOGIN -->
                    <div class="dropdown">
                        <button class="btn btn-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= $display_nama; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="border-radius: 12px; margin-top: 2px;">
                            <li>
                                <div class="px-3 py-2">
                                    <span class="d-block text-muted small">ID/NIM:</span>
                                    <span class="fw-bold"><?= $this->session->userdata('username'); ?></span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-shield-check me-2 text-fik-orange"></i>Role: <?= $display_role; ?></a></li>
                            <li><a class="dropdown-item text-danger fw-bold" href="<?= base_url('index.php/auth/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Dropdown Jika BELUM LOGIN (Mode Guest) -->
                    <div class="dropdown">
                        <button class="btn btn-user dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> Guest
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg" style="border-radius: 12px; margin-top: 2px;">
                            <li>
                                <div class="px-3 py-2 text-center pb-1">
                                    <span class="d-block text-muted small">Selamat Datang, Guest!</span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item fw-semibold py-2" href="<?= base_url('index.php/auth') ?>">
                                    <i class="bi bi-box-arrow-in-right me-2 text-fik-orange"></i> Masuk (Login)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item fw-semibold py-2" href="<?= base_url('index.php/auth/signup') ?>">
                                    <i class="bi bi-person-plus me-2 text-fik-orange"></i> Daftar Akun Baru
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="catalog-header">
        <div class="container text-center" data-aos="fade-down" data-aos-duration="800">
            <h2 class="fw-bolder mb-2" style="letter-spacing: 1px;">DAFTAR <span class="text-fik-orange">STUDIO & LABORATORIUM</span></h2>
            <p class="text-light opacity-75 mb-0">Pilih ruangan laboratorium untuk melihat ketersediaan aset dan mulai peminjaman.</p>
        </div>
    </div>

    <section class="container py-5 mt-2">
        <div class="row g-4 justify-content-center">
            
            <?php if(empty($ruangan_list)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-door-closed fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Belum ada data ruangan yang tersedia.</h5>
                </div>
            <?php else: ?>
                <?php $delay = 100; foreach($ruangan_list as $r): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= $delay; ?>">
                    <div class="card service-card">
                        
                        <?php if(!empty($r['foto'])): ?>
                            <img src="<?= base_url('assets/uploads/ruangan/'.$r['foto']) ?>" class="card-img-top" alt="<?= $r['nama_ruangan'] ?>">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center card-img-top">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body p-4 text-center d-flex flex-column">
                            <h5 class="fw-bold mt-3"><?= $r['nama_ruangan'] ?></h5>
                            <p class="text-muted small">
                                <?= !empty($r['deskripsi']) ? $r['deskripsi'] : 'Tersedia perlengkapan dan peralatan penunjang praktikum mahasiswa.' ?>
                            </p>
                            
                            <div class="mt-auto">
                                <hr class="border-secondary opacity-25">
                                <div class="d-flex justify-content-between align-items-center mb-3 small">
                                    <span class="text-muted"><i class="bi bi-geo-alt-fill text-fik-orange me-1"></i> Gd. Sebatik</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Buka</span>
                                </div>
                                <a href="<?= base_url('index.php/peminjaman?id_ruangan='.$r['id_ruangan']) ?>" class="btn btn-user w-100 rounded-pill mt-1">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Ruangan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    // Menambah delay agar animasi AOS muncul bergiliran
                    $delay += 100; 
                    endforeach; 
                ?>
            <?php endif; ?>

        </div>
    </section>

    <section class="sop-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="zoom-in">
                <h2 class="fw-bold text-white mb-2" style="letter-spacing: 1px;">SOP & TATA TERTIB STUDIO</h2>
                <p class="text-white opacity-75 mb-0">Mohon patuhi regulasi berikut demi kenyamanan bersama</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="rule-card h-100">
                        <i class="bi bi-info-circle fs-1 text-fik-orange mb-3 d-block"></i>
                        <h5 class="fw-bold text-white mb-3">Ketentuan Peminjaman</h5>
                        <p class="text-light opacity-75 small mb-0" style="line-height: 1.8;">
                            1. Peminjaman alat wajib diajukan maksimal <strong>H-1</strong> sebelum pengambilan.<br>
                            2. Peminjam wajib memeriksa kelengkapan (baterai, kabel, memory card) bersama laboran.<br>
                            3. Kerusakan aset saat dipinjam menjadi tanggung jawab penuh pihak peminjam.
                        </p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="rule-card h-100">
                        <i class="bi bi-file-earmark-pdf fs-1 text-fik-orange mb-3 d-block"></i>
                        <h5 class="fw-bold text-white mb-3">Dokumen SOP & Instruksi Kerja</h5>
                        <p class="text-light opacity-75 small mb-4" style="line-height: 1.8;">
                            Buka dokumen internal berisi SOP, tata tertib, dan instruksi kerja studio/laboratorium.
                        </p>
                        <?php if($can_read_internal_docs): ?>
                            <button type="button" class="btn-doc-internal" data-bs-toggle="modal" data-bs-target="#internalDocsModal">
                                <i class="bi bi-eye"></i> Lihat Dokumen
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('index.php/auth') ?>" class="btn-doc-internal">
                                <i class="bi bi-lock"></i> Login untuk Akses
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="rule-card h-100">
                        <i class="bi bi-exclamation-triangle fs-1 text-fik-orange mb-3 d-block"></i>
                        <h5 class="fw-bold text-white mb-3">Ketentuan Pengembalian</h5>
                        <p class="text-light opacity-75 small mb-0" style="line-height: 1.8;">
                            1. Pengembalian wajib menyertakan bukti foto kondisi alat sesudah dipakai.<br>
                            2. Keterlambatan tanpa konfirmasi perpanjangan akan dikenakan sanksi blacklist sistem.<br>
                            3. Wajib memindai QR Code dari alat yang dikembalikan ke sistem.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
                    <h4 class="fw-bolder mb-4" style="color: #1a1a1a;">LAYANAN <span class="text-fik-orange">STUDIO FIK</span></h4>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-light p-3 rounded-circle me-3 text-fik-orange"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Gedung FIK (Sebatik)</h6>
                            <p class="text-muted small mb-0">Jl. Telekomunikasi No. 1, Terusan Buahbatu<br>Bandung, Jawa Barat 40257</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-light p-3 rounded-circle me-3 text-fik-orange"><i class="bi bi-whatsapp"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Kontak Laboran Peminjaman</h6>
                            <p class="text-muted small mb-0">+62 811 2233 4455 (WA Chat Only)<br>Buka di jam kerja (08:30 - 16:30)</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="bg-light p-3 rounded-circle me-3 text-fik-orange"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Email Resmi</h6>
                            <p class="text-muted small mb-0">studio.fik@telkomuniversity.ac.id</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="map-container shadow-sm p-2 bg-white rounded-4 border">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.3074558509425!2d107.62834241477341!3d-6.973007094961817!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e9adf177bf8d%3A0x437398556f9fa03!2sTelkom%20University!5e0!3m2!1sid!2sid!4v1689234567890!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <?php if($can_read_internal_docs): ?>
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
    <?php endif; ?>
    <footer class="footer-fik">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-12 mb-5 mb-lg-0" data-aos="fade-up" data-aos-delay="100">
                    <div class="logo-wrap">
                        <div class="logo-icon">
                            <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="350" class="me-2">
                        </div>
                    </div>
                    
                    <p class="info-text">Gedung Sebatik - Telkom University</p>
                    <p class="info-text">Jl. Telekomunikasi Terusan Buah Batu Bandung</p>
                    <p class="info-text mb-4">40257 Indonesia</p>
                    
                    <p class="info-text mb-1">Telp: (022) 7566456</p>
                    <p class="info-text mb-4">email: info@telkomuniversity.ac.id</p>
                    
                    <div class="d-flex mt-2">
                        <a href="#" class="social-circle"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-circle"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-circle"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-circle"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="social-circle"><i class="bi bi-spotify"></i></a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-5 mb-md-0" data-aos="fade-up" data-aos-delay="200">
                    <h5>Department:</h5>
                    <ul>
                        <li><a href="#">Desain Komunikasi Visual</a></li>
                        <li><a href="#">Desain Interior</a></li>
                        <li><a href="#">Desain Produk</a></li>
                        <li><a href="#">Kriya Tekstil & Fashion</a></li>
                        <li><a href="#">Seni Rupa</a></li>
                        <li><a href="#">Film & Animasi</a></li>
                        <li><a href="#">Magister Desain</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <h5>Related Link:</h5>
                    <ul>
                        <li><a href="#">Telkom University</a></li>
                        <li><a href="#">Admission</a></li>
                        <li><a href="#">iGracias</a></li>
                        <li><a href="#">iFik</a></li>
                        <li><a href="#">Tel-U Career</a></li>
                        <li><a href="#">Language Center</a></li>
                        <li><a href="#">Research & Community Service</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Inisialisasi Efek Animasi
        AOS.init({
            once: true,
            offset: 50 
        });
        function bindInternalDocsModal() {
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
        }
        document.addEventListener('DOMContentLoaded', bindInternalDocsModal);
    </script>
</body>
</html>
