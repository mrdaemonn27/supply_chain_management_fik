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
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    
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
            border-bottom: 4px solid transparent;
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

        .rule-card__actions {
            display: grid;
            grid-template-columns: minmax(0, 0.86fr) minmax(0, 1.14fr);
            align-items: stretch;
            gap: 8px;
        }

        .rule-card__actions .btn-doc-internal {
            min-height: 44px;
            padding: 9px 8px;
            font-size: 0.72rem;
            white-space: nowrap;
            text-align: center;
        }

        .rule-card__actions .btn-doc-manual {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.42);
        }

        .rule-card__actions .btn-doc-manual:hover,
        .rule-card__actions .btn-doc-manual:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.72);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 1199.98px) {
            .rule-card__actions {
                grid-template-columns: 1fr;
            }

            .rule-card__actions .btn-doc-internal {
                font-size: 0.82rem;
            }
        }

        /* Header Tampilan Awal (Slimmer) */
        .catalog-header {
            background: linear-gradient(rgba(26, 26, 26, 0.85), rgba(26, 26, 26, 0.9)), url('https://images.unsplash.com/photo-1542744094-24638ea0b3b5?auto=format&fit=crop&q=80') center/cover;
            padding: 50px 0;
            color: white;
            border-bottom: 5px solid #ea5b1a;
        }

        .catalog-header__inner {
            text-align: center;
        }

        .catalog-header__title {
            margin: 0;
        }

        .lab-search-panel { max-width: 960px; margin: 0 auto 2rem; }
        .lab-search-panel .admin-multi-filter { margin-bottom: 0 !important; }
        .asset-search-results { max-width: 960px; margin: 0 auto 2rem; }
        .asset-search-results__heading { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
        .asset-search-results__heading h3 { margin:0; color:#1f2937; font-size:1.05rem; font-weight:700; }
        .asset-search-results__heading p { margin:.25rem 0 0; color:#6b7280; font-size:.78rem; }
        .asset-search-results__list { display:grid; gap:.75rem; }
        .asset-search-result { padding:1rem 1.1rem; border:1px solid #e1e5e9; border-left:4px solid #ea5b1a; border-radius:12px; background:#fff; box-shadow:0 4px 14px rgba(15,23,42,.045); }
        .asset-search-result__top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
        .asset-search-result__name { margin:0; color:#20262d; font-size:.95rem; font-weight:700; }
        .asset-search-result__codes { margin:.2rem 0 0; color:#7c8791; font-family:monospace; font-size:.7rem; }
        .asset-search-result__stock { flex:0 0 auto; color:#198754; font-size:.75rem; font-weight:700; }
        .asset-search-result__locations { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.75rem; }
        .asset-location-chip { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .7rem; border:1px solid rgba(234,91,26,.25); border-radius:999px; background:#fff7f2; color:#8c3f18; font-size:.72rem; font-weight:600; text-decoration:none; }
        .asset-location-chip:hover, .asset-location-chip:focus { border-color:#ea5b1a; color:#c24a13; }
        .asset-location-chip small { color:#6b7280; font-weight:500; }
        .asset-search-results__more { margin:.8rem 0 0; color:#6b7280; font-size:.75rem; text-align:center; }

        html.scm-theme-dark .asset-search-results__heading h3,
        html.scm-theme-dark .asset-search-result__name { color:var(--scm-theme-text); }
        html.scm-theme-dark .asset-search-results__heading p,
        html.scm-theme-dark .asset-search-result__codes,
        html.scm-theme-dark .asset-search-results__more { color:var(--scm-theme-muted); }
        html.scm-theme-dark .asset-search-result { border-color:var(--scm-theme-border); background:var(--scm-theme-surface); }
        html.scm-theme-dark .asset-location-chip { border-color:rgba(255,139,36,.35); background:rgba(234,91,26,.12); color:#ffb16b; }
        
        /* Styling Kartu Lab */
        .service-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.04);
            transition: box-shadow 0.4s ease, border-bottom-color 0.4s ease;
            height: 100%;
        }
        .lab-grid {
            --lab-columns: 3;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: stretch;
            margin: -12px;
        }
        .lab-grid__item { display:flex; min-width:0; flex:0 0 calc(100% / var(--lab-columns)); padding:12px; }
        .lab-grid__item .service-card { width:100%; }
        .lab-search-empty { display:none; width:100%; padding:3rem 1rem; text-align:center; }
        .lab-grid__item[hidden] { display:none !important; }
        .service-card__media {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: #eef1f3;
            perspective: 900px;
        }
        .service-card:hover {
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-bottom-color: #ea5b1a;
        }
        .service-card__media img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(0deg) scale(1);
            transition: transform .28s cubic-bezier(.22, 1, .36, 1);
            transform-origin: center center;
        }
        .service-card__model {
            display:block;
            width:100%;
            height:100%;
            background:linear-gradient(145deg, #f7f9fc 0%, #e4eaf1 100%);
            --poster-color:transparent;
            --progress-bar-color:#ea5b1a;
        }
        .service-card__placeholder { position: absolute; inset: 0; display: grid; place-items: center; }
        .service-card__media.is-pointer-active img {
            transform: translate3d(var(--media-shift-x, 0px), var(--media-shift-y, 0px), 0)
                rotateX(var(--media-rotate-x, 0deg))
                rotateY(var(--media-rotate-y, 0deg))
                scale(var(--media-scale, 1.03));
            transition-duration: .12s;
        }
        @media (max-width: 991.98px) {
            .lab-grid__item { flex-basis:50%; }
        }
        @media (max-width: 575.98px) {
            .catalog-header { padding: 36px 0; }
            .catalog-header__title { text-align: center; font-size: 1.25rem; line-height: 1.35; }
            .lab-grid { margin:-9px; }
            .lab-grid__item { flex-basis:100%; padding:9px; }
            .service-card__media { aspect-ratio: 16 / 9; }
            .asset-search-results__heading, .asset-search-result__top { flex-direction:column; }
        }

        /* SOP / Aturan Section */
        .sop-section {
            background: linear-gradient(rgba(107, 49, 22, 0.84), rgba(115, 43, 12, 0.93)), url('<?= base_url('assets/logo/BG-FIK-VSCO.jpg'); ?>') center / cover no-repeat;
            background-attachment: fixed;
            padding: 80px 0;
            color: white;
        }

        @media (max-width: 991.98px) {
            .sop-section {
                background-attachment: scroll;
                background-position: center;
            }
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
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
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
                                <a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $n->id_notifikasi) ?>">
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
        <div class="container catalog-header__inner" data-aos="fade-down" data-aos-duration="800">
            <h2 class="catalog-header__title fw-bolder" style="letter-spacing: 1px;">DAFTAR <span class="text-fik-orange">STUDIO & LABORATORIUM</span></h2>
        </div>
    </div>

    <section class="container py-5 mt-2">
        <?php
            $lab_count = count($ruangan_list ?? []);
            $lab_columns = min(4, max(1, $lab_count));
            while ($lab_columns > 2 && $lab_count > 1 && $lab_count % $lab_columns === 1) {
                $lab_columns--;
            }
        ?>

        <div class="lab-search-panel">
            <?php
                $multi_filter_id = 'labMultiFilter';
                $multi_filter_mode = 'client';
                $multi_filter_fields = [
                    'all' => ['label' => 'Studio, lab, atau barang', 'placeholder' => 'Cari studio, laboratorium, nama barang, atau kode aset...'],
                    'barang' => ['label' => 'Nama barang', 'placeholder' => 'Contoh: HDMI, kamera, tripod...'],
                    'kode_barang' => ['label' => 'Kode aset', 'placeholder' => 'Cari kode aset...'],
                    'ruangan' => ['label' => 'Studio / laboratorium', 'placeholder' => 'Cari nama studio atau laboratorium...'],
                ];
                $multi_filter_rows = [['field' => 'all', 'value' => '']];
                $multi_filter_meta_id = 'labSearchMeta';
                $multi_filter_meta = number_format($lab_count, 0, ',', '.') . ' ruangan/laboratorium tersedia';
                include APPPATH . 'views/admin/_multi_filter.php';
                unset($multi_filter_id, $multi_filter_mode, $multi_filter_fields, $multi_filter_rows, $multi_filter_meta_id, $multi_filter_meta);
            ?>
        </div>

        <section id="assetSearchResults" class="asset-search-results" aria-labelledby="assetSearchResultsTitle" hidden>
            <div class="asset-search-results__heading">
                <div>
                    <h3 id="assetSearchResultsTitle"><i class="bi bi-box-seam me-2 text-fik-orange" aria-hidden="true"></i>Barang ditemukan</h3>
                    <p id="assetSearchResultsSummary">Lokasi barang yang cocok dengan pencarian.</p>
                </div>
                <span class="badge bg-light text-dark border" id="assetSearchResultsCount"></span>
            </div>
            <div class="asset-search-results__list" id="assetSearchResultsList"></div>
            <p class="asset-search-results__more" id="assetSearchResultsMore" hidden></p>
        </section>

        <div class="lab-grid" style="--lab-columns: <?= (int) $lab_columns ?>" data-lab-count="<?= (int) $lab_count ?>">
            
            <?php if(empty($ruangan_list)): ?>
                <div class="text-center py-5 w-100">
                    <i class="bi bi-door-closed fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Belum ada data ruangan yang tersedia.</h5>
                </div>
            <?php else: ?>
                <?php $delay = 100; foreach($ruangan_list as $r): ?>
                <?php $lab_search_text = trim(($r['nama_ruangan'] ?? '') . ' ' . ($r['deskripsi'] ?? '')); ?>
                <div class="lab-grid__item" data-lab-card data-room-id="<?= (int) ($r['id_ruangan'] ?? 0) ?>" data-room-search="<?= html_escape($lab_search_text) ?>" data-search="<?= html_escape($lab_search_text) ?>" data-filter-all="<?= html_escape($lab_search_text) ?>" data-filter-ruangan="<?= html_escape($lab_search_text) ?>">
                    <div class="card service-card">
                        <div class="service-card__media">
                        <?php if(!empty($r['foto_url'])): ?>
                            <?php $room_media_is_3d = in_array(strtolower((string) pathinfo($r['foto'] ?? '', PATHINFO_EXTENSION)), ['glb', 'gltf'], true); ?>
                            <?php if($room_media_is_3d): ?>
                                <model-viewer class="service-card__model" src="<?= html_escape($r['foto_url']) ?>" alt="Model 3D <?= html_escape($r['nama_ruangan']) ?>" camera-controls disable-pan disable-zoom interaction-prompt="none" touch-action="pan-y" shadow-intensity="0.7" loading="lazy" reveal="auto"></model-viewer>
                            <?php else: ?>
                                <img src="<?= html_escape($r['foto_url']) ?>" alt="Foto <?= html_escape($r['nama_ruangan']) ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="service-card__placeholder bg-light">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        </div>
                        
                        <div class="card-body p-4 text-center d-flex flex-column">
                            <h5 class="fw-bold mt-3"><?= html_escape($r['nama_ruangan']) ?></h5>
                            <p class="text-muted small">
                                <?= !empty($r['deskripsi']) ? html_escape($r['deskripsi']) : 'Tersedia perlengkapan dan peralatan penunjang praktikum mahasiswa.' ?>
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

            <div id="labSearchEmpty" class="lab-search-empty" role="status">
                <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
                <h5 class="text-muted">Studio, laboratorium, atau barang tidak ditemukan.</h5>
                <p class="small text-muted mb-0">Coba gunakan nama ruangan, nama barang, atau kode aset yang berbeda.</p>
            </div>
        </div>
    </section>

    <script type="application/json" id="labAssetSearchData"><?= json_encode($asset_search_index ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

    <section class="sop-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="zoom-in">
                <h2 class="fw-bold text-white mb-0" style="letter-spacing: 1px;">SOP & TATA TERTIB STUDIO</h2>
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
                            Akses SOP, tata tertib, instruksi kerja, dan panduan penggunaan sistem peminjaman.
                        </p>
                        <div class="rule-card__actions">
                            <?php if($can_read_internal_docs): ?>
                                <button type="button" class="btn-doc-internal" data-bs-toggle="modal" data-bs-target="#internalDocsModal">
                                    <i class="bi bi-eye"></i> Lihat Dokumen
                                </button>
                            <?php else: ?>
                                <a href="<?= base_url('index.php/auth') ?>" class="btn-doc-internal">
                                    <i class="bi bi-lock"></i> Login untuk Akses
                                </a>
                            <?php endif; ?>
                            <a href="<?= base_url('assets/documents/User_Manual_Peminjaman_Barang_SCM_FIK_v1.0_2026.pdf') ?>"
                               class="btn-doc-internal btn-doc-manual"
                               target="_blank"
                               rel="noopener">
                                <i class="bi bi-book"></i> Panduan Peminjaman
                            </a>
                        </div>
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
        function bindLabSearch() {
            const root = document.getElementById('labMultiFilter');
            const meta = document.getElementById('labSearchMeta');
            const empty = document.getElementById('labSearchEmpty');
            const cards = Array.from(document.querySelectorAll('[data-lab-card]'));
            const resultSection = document.getElementById('assetSearchResults');
            const resultList = document.getElementById('assetSearchResultsList');
            const resultCount = document.getElementById('assetSearchResultsCount');
            const resultSummary = document.getElementById('assetSearchResultsSummary');
            const resultMore = document.getElementById('assetSearchResultsMore');
            const assetDataNode = document.getElementById('labAssetSearchData');
            if (!root) return;

            const normalize = value => String(value || '')
                .trim()
                .toLocaleLowerCase('id')
                .replace(/laboratorium/g, 'lab')
                .replace(/[._\-/]+/g, ' ')
                .replace(/\s+/g, ' ');
            let assets = [];
            try {
                assets = JSON.parse(assetDataNode?.textContent || '[]');
            } catch (error) {
                assets = [];
            }
            const assetsByRoom = new Map();
            assets.forEach(asset => {
                const roomId = String(Number(asset.id_ruangan) || 0);
                if (!assetsByRoom.has(roomId)) assetsByRoom.set(roomId, []);
                assetsByRoom.get(roomId).push(asset);
            });

            const assetMatchesCriterion = (asset, criterion) => {
                const name = `${asset.nama_aset || ''} ${asset.deskripsi || ''}`;
                const code = asset.kode_aset || '';
                const room = asset.nama_ruangan || '';
                const fields = {
                    all: `${name} ${code} ${room}`,
                    barang: name,
                    kode_barang: code,
                    ruangan: room
                };
                return normalize(fields[criterion.field] || '').includes(normalize(criterion.value));
            };

            const cardMatchesCriteria = (card, criteria) => {
                const room = card.dataset.roomSearch || '';
                const roomAssets = assetsByRoom.get(String(Number(card.dataset.roomId) || 0)) || [];
                return criteria.every(criterion => {
                    const query = normalize(criterion.value);
                    if (!query) return true;
                    if (criterion.field === 'ruangan') return normalize(room).includes(query);
                    if (criterion.field === 'all' && normalize(room).includes(query)) return true;
                    return roomAssets.some(asset => assetMatchesCriterion(asset, criterion));
                });
            };

            const hasAssetIdentityMatch = (asset, criteria) => criteria.some(criterion => {
                if (criterion.field === 'ruangan') return false;
                const identity = criterion.field === 'kode_barang'
                    ? asset.kode_aset
                    : `${asset.nama_aset || ''} ${asset.kode_aset || ''} ${asset.deskripsi || ''}`;
                return normalize(identity).includes(normalize(criterion.value));
            });

            const groupAssets = matchedAssets => {
                const groups = new Map();
                matchedAssets.forEach(asset => {
                    const key = normalize(asset.nama_aset) || `aset-${asset.id_aset}`;
                    if (!groups.has(key)) {
                        groups.set(key, {
                            name: asset.nama_aset || 'Barang tanpa nama',
                            codes: new Set(),
                            locations: new Map(),
                            total: 0,
                            available: 0
                        });
                    }
                    const group = groups.get(key);
                    if (asset.kode_aset) group.codes.add(asset.kode_aset);
                    group.total += Number(asset.jumlah_total) || 0;
                    group.available += Number(asset.jumlah_tersedia) || 0;

                    const roomId = Number(asset.id_ruangan) || 0;
                    const roomKey = roomId || normalize(asset.nama_ruangan) || 'tanpa-lokasi';
                    const existing = group.locations.get(roomKey) || {
                        id: roomId,
                        name: asset.nama_ruangan || 'Lokasi belum ditentukan',
                        total: 0,
                        available: 0
                    };
                    existing.total += Number(asset.jumlah_total) || 0;
                    existing.available += Number(asset.jumlah_tersedia) || 0;
                    group.locations.set(roomKey, existing);
                });
                return Array.from(groups.values());
            };

            const renderAssetResults = (criteria, matchedAssets) => {
                if (!resultSection || !resultList || !resultCount || !resultSummary || !resultMore) return;
                const groups = groupAssets(matchedAssets);
                resultList.replaceChildren();
                resultSection.hidden = !criteria.length || !groups.length;
                if (resultSection.hidden) return;

                const shownGroups = groups.slice(0, 20);
                resultCount.textContent = `${new Intl.NumberFormat('id-ID').format(groups.length)} jenis barang`;
                resultSummary.textContent = 'Setiap lokasi di bawah menunjukkan studio atau laboratorium tempat barang tersedia.';

                shownGroups.forEach(group => {
                    const article = document.createElement('article');
                    article.className = 'asset-search-result';

                    const top = document.createElement('div');
                    top.className = 'asset-search-result__top';
                    const identity = document.createElement('div');
                    const name = document.createElement('h4');
                    name.className = 'asset-search-result__name';
                    name.textContent = group.name;
                    identity.appendChild(name);

                    const codes = Array.from(group.codes);
                    if (codes.length) {
                        const code = document.createElement('p');
                        code.className = 'asset-search-result__codes';
                        code.textContent = `Kode: ${codes.slice(0, 5).join(', ')}${codes.length > 5 ? ` +${codes.length - 5} lainnya` : ''}`;
                        identity.appendChild(code);
                    }
                    top.appendChild(identity);

                    const stock = document.createElement('span');
                    stock.className = 'asset-search-result__stock';
                    stock.textContent = `${group.available}/${group.total} tersedia`;
                    top.appendChild(stock);
                    article.appendChild(top);

                    const locations = document.createElement('div');
                    locations.className = 'asset-search-result__locations';
                    group.locations.forEach(location => {
                        const chip = document.createElement(location.id ? 'a' : 'span');
                        chip.className = 'asset-location-chip';
                        if (location.id) chip.href = `<?= base_url('index.php/peminjaman?id_ruangan=') ?>${encodeURIComponent(location.id)}`;
                        const icon = document.createElement('i');
                        icon.className = 'bi bi-geo-alt-fill';
                        icon.setAttribute('aria-hidden', 'true');
                        const label = document.createElement('span');
                        label.textContent = location.name;
                        const quantity = document.createElement('small');
                        quantity.textContent = `${location.available}/${location.total} tersedia`;
                        chip.append(icon, label, quantity);
                        locations.appendChild(chip);
                    });
                    article.appendChild(locations);
                    resultList.appendChild(article);
                });

                resultMore.hidden = groups.length <= shownGroups.length;
                resultMore.textContent = resultMore.hidden ? '' : `${groups.length - shownGroups.length} jenis barang lainnya juga cocok. Persempit kata pencarian untuk melihatnya.`;
            };

            const render = () => {
                const criteria = window.AdminMultiFilter?.getCriteria(root) || [];
                let visible = 0;
                cards.forEach(card => {
                    const matches = cardMatchesCriteria(card, criteria);
                    card.hidden = !matches;
                    if (matches) visible += 1;
                });
                const matchedAssets = criteria.length
                    ? assets.filter(asset => criteria.every(criterion => assetMatchesCriterion(asset, criterion)) && hasAssetIdentityMatch(asset, criteria))
                    : [];
                renderAssetResults(criteria, matchedAssets);
                if (empty) empty.style.display = visible ? 'none' : 'block';
                if (meta) {
                    const formatted = new Intl.NumberFormat('id-ID').format(visible);
                    const assetGroups = groupAssets(matchedAssets).length;
                    meta.textContent = criteria.length
                        ? `${formatted} ruangan/laboratorium dan ${new Intl.NumberFormat('id-ID').format(assetGroups)} jenis barang ditemukan`
                        : `${formatted} ruangan/laboratorium tersedia`;
                }
            };

            root.addEventListener('admin-multi-filter-change', render);
            render();
        }

        function bindLabCard3d() {
            const canHover = window.matchMedia
                && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            const reducedMotion = window.matchMedia
                && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (!canHover || reducedMotion) return;

            document.querySelectorAll('.service-card').forEach((card) => {
                const surface = card.querySelector('.service-card__media');
                const image = surface && surface.querySelector('img');
                if (!surface || !image || card.dataset.pointer3dBound === 'true') return;

                card.dataset.pointer3dBound = 'true';
                const target = { x: 0, y: 0, rotateX: 0, rotateY: 0, scale: 1 };
                const current = { x: 0, y: 0, rotateX: 0, rotateY: 0, scale: 1 };
                let frameId = 0;
                let isActive = false;

                const render = () => {
                    const easing = isActive ? 0.16 : 0.2;
                    current.x += (target.x - current.x) * easing;
                    current.y += (target.y - current.y) * easing;
                    current.rotateX += (target.rotateX - current.rotateX) * easing;
                    current.rotateY += (target.rotateY - current.rotateY) * easing;
                    current.scale += (target.scale - current.scale) * easing;

                    surface.style.setProperty('--media-shift-x', `${current.x.toFixed(2)}px`);
                    surface.style.setProperty('--media-shift-y', `${current.y.toFixed(2)}px`);
                    surface.style.setProperty('--media-rotate-x', `${current.rotateX.toFixed(2)}deg`);
                    surface.style.setProperty('--media-rotate-y', `${current.rotateY.toFixed(2)}deg`);
                    surface.style.setProperty('--media-scale', current.scale.toFixed(3));

                    const settled = Math.abs(current.x - target.x) < 0.01
                        && Math.abs(current.y - target.y) < 0.01
                        && Math.abs(current.rotateX - target.rotateX) < 0.01
                        && Math.abs(current.rotateY - target.rotateY) < 0.01
                        && Math.abs(current.scale - target.scale) < 0.001;

                    if (!isActive && settled) {
                        surface.classList.remove('is-pointer-active');
                        frameId = 0;
                        return;
                    }

                    frameId = window.requestAnimationFrame(render);
                };

                const startRender = () => {
                    if (!frameId) frameId = window.requestAnimationFrame(render);
                };

                card.addEventListener('pointermove', (event) => {
                    if (event.pointerType && !['mouse', 'pen'].includes(event.pointerType)) return;

                    const rect = card.getBoundingClientRect();
                    if (!rect.width || !rect.height) return;

                    const relativeX = ((event.clientX - rect.left) / rect.width) * 2 - 1;
                    const relativeY = ((event.clientY - rect.top) / rect.height) * 2 - 1;
                    const x = Math.max(-1, Math.min(1, relativeX));
                    const y = Math.max(-1, Math.min(1, relativeY));

                    target.x = x * 3;
                    target.y = y * 2;
                    target.rotateX = y * -4;
                    target.rotateY = x * 6;
                    target.scale = 1.03;
                    isActive = true;
                    surface.classList.add('is-pointer-active');
                    startRender();
                });

                card.addEventListener('pointerleave', () => {
                    target.x = 0;
                    target.y = 0;
                    target.rotateX = 0;
                    target.rotateY = 0;
                    target.scale = 1;
                    isActive = false;
                    startRender();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindInternalDocsModal();
            bindLabSearch();
            bindLabCard3d();
        });
    </script>
</body>
</html>
