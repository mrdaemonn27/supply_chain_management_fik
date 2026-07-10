<?php
$logged_in = (bool) $this->session->userdata('logged_in');
$session_role = strtolower((string) $this->session->userdata('role'));

if ($logged_in) {
    if ($session_role === 'admin' || $session_role === 'laboran') {
        $dashboard_url = base_url('index.php/admin/dashboard');
    } elseif ($session_role === 'kaur') {
        $dashboard_url = base_url('index.php/kaur/dashboard');
    } elseif ($session_role === 'kaprodi') {
        $dashboard_url = base_url('index.php/kaprodi/dashboard');
    } else {
        $dashboard_url = base_url('index.php/dashboard');
    }
} else {
    $dashboard_url = base_url('index.php/auth');
}

$login_url = base_url('index.php/auth');
$signup_url = base_url('index.php/auth/signup');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCM FIK - Sistem Peminjaman Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap');

        :root {
            --fik-orange: #ea5b1a;
            --fik-orange-dark: #c24a13;
            --fik-brown: #5d3315;
            --fik-ink: #111111;
            --fik-muted: #6f6f6f;
            --fik-line: #e5e2dd;
            --fik-paper: #faf9f6;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--fik-paper);
            color: var(--fik-ink);
            -webkit-font-smoothing: antialiased;
        }

        .serif { font-family: 'Fraunces', serif; }

        a { text-decoration: none; }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--fik-orange);
        }

        /* ---------- Navbar ---------- */
        .navbar-custom {
            background: var(--fik-paper);
            border-bottom: 3px solid var(--fik-orange);
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .navbar-custom .navbar-brand img { height: 34px; }

        .navbar-custom .nav-link {
            color: var(--fik-ink);
            font-weight: 500;
            font-size: 0.92rem;
            margin: 0 14px;
            position: relative;
            padding-bottom: 2px;
        }

        .navbar-custom .nav-link::after {
            content: '';
            position: absolute;
            left: 0; bottom: -2px;
            width: 0;
            height: 1px;
            background: var(--fik-orange);
            transition: width 0.25s ease;
        }

        .navbar-custom .nav-link:hover::after,
        .navbar-custom .nav-link.active::after {
            width: 100%;
        }

        .btn-primary-fik {
            background: var(--fik-ink);
            border: 1px solid var(--fik-ink);
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 2px;
            padding: 10px 22px;
            transition: 0.25s ease;
        }

        .btn-primary-fik:hover {
            background: var(--fik-orange);
            border-color: var(--fik-orange);
            color: white;
        }

        .btn-outline-fik {
            border: 1px solid var(--fik-ink);
            color: var(--fik-ink);
            background: transparent;
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 2px;
            padding: 10px 22px;
            transition: 0.25s ease;
        }

        .btn-outline-fik:hover {
            background: var(--fik-ink);
            color: white;
        }

        /* ---------- Hero ---------- */
        .hero-section {
            position: relative;
            background:
                linear-gradient(100deg, rgba(10,10,10,0.94) 0%, rgba(10,10,10,0.82) 38%, rgba(30,15,8,0.5) 60%, rgba(10,10,10,0.35) 100%),
                url('<?= base_url('assets/logo/FIK GEDUNG.webp'); ?>') center 70%/cover no-repeat;
            color: white;
            padding: 170px 0 140px;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, rgba(10,10,10,0.7) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 15% 20%, rgba(234,91,26,0.2), transparent 45%);
            mix-blend-mode: screen;
            pointer-events: none;
        }

        .hero-section .eyebrow { color: #ffcda1; }

        .hero-title {
            font-family: 'Fraunces', serif;
            font-weight: 500;
            font-size: clamp(2.6rem, 6vw, 5rem);
            line-height: 1.05;
            letter-spacing: -0.01em;
            margin: 18px 0 26px;
            text-shadow: 0 2px 24px rgba(0,0,0,0.45);
        }

        .hero-title em { font-style: italic; color: var(--fik-orange); }

        .hero-lede {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.9);
            max-width: 620px;
            font-weight: 300;
            text-shadow: 0 2px 16px rgba(0,0,0,0.4);
        }

        .hero-meta {
            display: flex;
            gap: 34px;
            margin-top: 56px;
            padding-top: 26px;
            border-top: 1px solid rgba(234,91,26,0.4);
            flex-wrap: wrap;
        }

        .hero-meta div small {
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.68rem;
            color: rgba(255,255,255,0.75);
            margin-bottom: 4px;
        }

        .hero-meta div span {
            font-family: 'Fraunces', serif;
            font-size: 1.05rem;
        }

        /* ---------- Section framing ---------- */
        .section-pad { padding: 110px 0; }
        .section-pad-sm { padding: 80px 0; }

        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-label .dot {
            width: 10px; height: 10px;
            background: var(--fik-orange);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 4px rgba(234,91,26,0.15);
        }

        .section-title {
            font-family: 'Fraunces', serif;
            font-weight: 500;
            color: var(--fik-ink);
            font-size: clamp(1.8rem, 3.2vw, 2.6rem);
            letter-spacing: -0.01em;
        }

        .section-subtitle {
            color: var(--fik-muted);
            max-width: 620px;
            font-weight: 300;
            font-size: 1rem;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--fik-line);
            margin: 0;
        }

        /* ---------- Feature list (no heavy cards) ---------- */
        .feature-row {
            padding: 34px 20px;
            border-top: 1px solid var(--fik-line);
            border-left: 3px solid transparent;
            display: flex;
            gap: 26px;
            align-items: flex-start;
            transition: 0.25s ease;
        }

        .feature-row:hover {
            border-left: 3px solid var(--fik-orange);
            background: rgba(234,91,26,0.04);
        }

        .feature-row:last-child { border-bottom: 1px solid var(--fik-line); }

        .feature-icon {
            background: rgba(234,91,26,0.1);
            color: var(--fik-orange) !important;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem !important;
        }

        .feature-index {
            font-family: 'Fraunces', serif;
            font-size: 1rem;
            color: var(--fik-orange);
            min-width: 34px;
            padding-top: 3px;
        }

        .feature-row h5 {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 1.05rem;
        }

        .feature-row p {
            color: var(--fik-muted);
            font-weight: 300;
            margin: 0;
            font-size: 0.94rem;
        }

        /* ---------- Steps ---------- */
        .step-block {
            padding: 28px 26px;
            border: 1px solid var(--fik-line);
            border-bottom: 3px solid var(--fik-line);
            background: white;
            height: 100%;
            transition: 0.25s ease;
        }

        .step-block:hover {
            border-color: var(--fik-orange);
            border-bottom-color: var(--fik-orange);
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(234,91,26,0.12);
        }

        .step-num {
            font-family: 'Fraunces', serif;
            font-size: 2rem;
            color: var(--fik-orange);
            font-weight: 500;
            line-height: 1;
            margin-bottom: 14px;
            opacity: 0.85;
        }

        .step-block h6 {
            font-weight: 600;
            font-size: 0.98rem;
            margin-bottom: 6px;
        }

        .step-block p {
            color: var(--fik-muted);
            font-size: 0.88rem;
            font-weight: 300;
            margin: 0;
        }

        /* ---------- CTA band ---------- */
        .cta-band {
            background: linear-gradient(135deg, var(--fik-ink) 0%, var(--fik-brown) 55%, var(--fik-orange-dark) 130%);
            color: white;
            padding: 90px 0;
        }

        .cta-band .section-title { color: white; }
        .cta-band p { color: rgba(255,255,255,0.65); font-weight: 300; }

        /* ---------- Footer ---------- */
        .footer-fik {
            background: var(--fik-paper);
            color: var(--fik-ink);
            padding: 70px 0 30px;
            border-top: 3px solid var(--fik-orange);
        }

        .footer-fik h5 {
            font-family: 'Fraunces', serif;
            font-weight: 500;
        }

        .footer-fik a {
            color: var(--fik-ink);
        }

        .footer-fik a:hover { color: var(--fik-orange); }

        .footer-bottom {
            margin-top: 50px;
            padding-top: 22px;
            border-top: 1px solid var(--fik-line);
            font-size: 0.82rem;
            color: var(--fik-muted);
        }

        @media (max-width: 767.98px) {
            .hero-section { padding: 120px 0 90px; }
            .navbar-custom .navbar-nav { padding-top: 14px; }
            .feature-row { flex-direction: column; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo FIK">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="landingNav">
                <ul class="navbar-nav align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="<?= $logged_in ? $dashboard_url : $login_url ?>">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $logged_in ? $dashboard_url : $login_url ?>">Fitur</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $logged_in ? $dashboard_url : $login_url ?>">Alur</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $logged_in ? $dashboard_url : $login_url ?>">Kontak</a></li>
                    <li class="nav-item ms-lg-4 mt-2 mt-lg-0">
                        <a class="btn btn-outline-fik me-2" href="<?= $login_url ?>">Masuk</a>
                        <a class="btn btn-primary-fik" href="<?= $signup_url ?>">Daftar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-9" data-aos="fade-up">
                    <span class="eyebrow">Sistem Peminjaman Aset FIK</span>
                    <h1 class="hero-title">Selamat datang di<br>layanan peminjaman <em>aset</em><br>Fakultas Industri Kreatif.</h1>
                    <p class="hero-lede">Kami hadir untuk memudahkan proses peminjaman studio, peralatan, dan fasilitas laboratorium bagi seluruh sivitas akademika FIK. Mulai dari pengajuan permohonan, persetujuan berjenjang oleh Kaprodi dan Kaur, hingga pemantauan status pengembalian — semua dapat dilakukan secara digital, cepat, transparan, dan tanpa perlu bolak-balik mengurus berkas secara manual.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="<?= $login_url ?>" class="btn btn-primary-fik">Login ke Dashboard</a>
                        <a href="<?= $signup_url ?>" class="btn btn-outline-fik" style="color:white;border-color:rgba(255,255,255,0.5);">Buat Akun Baru</a>
                    </div>

                    <div class="hero-meta">
                        <div>
                            <small>Fakultas</small>
                            <span>Industri Kreatif</span>
                        </div>
                        <div>
                            <small>Layanan</small>
                            <span>Peminjaman Aset &amp; Studio</span>
                        </div>
                        <div>
                            <small>Status</small>
                            <span>Real-time Tracking</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="section-pad bg-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-6" data-aos="fade-up">
                    <h2 class="section-title mb-3">Peminjaman aset, tanpa ribet</h2>
                    <p class="section-subtitle">Semua kebutuhan peminjaman studio dan alat laboratorium kini bisa diurus dari satu tempat — praktis, cepat, dan tetap tertib dari awal sampai akhir.</p>
                </div>
            </div>

            <div class="row mt-4" data-aos="fade-up">
                <div class="col-12">
                    <div class="feature-row">
                        <div class="feature-index">01</div>
                        <div class="feature-icon"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <h5>Akses Cepat</h5>
                            <p>Pengguna dapat masuk ke dashboard dalam beberapa langkah tanpa hambatan.</p>
                        </div>
                    </div>
                    <div class="feature-row">
                        <div class="feature-index">02</div>
                        <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h5>SOP Terintegrasi</h5>
                            <p>Informasi aturan peminjaman dan pengembalian tersedia secara jelas sebelum proses dimulai.</p>
                        </div>
                    </div>
                    <div class="feature-row">
                        <div class="feature-index">03</div>
                        <div class="feature-icon"><i class="bi bi-phone"></i></div>
                        <div>
                            <h5>Responsif</h5>
                            <p>Tampilan menyesuaikan dengan layar desktop, tablet, maupun ponsel.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-pad-sm" style="background: var(--fik-paper);">
        <div class="container">
            <div class="section-label" data-aos="fade-up"><span class="dot"></span><span class="eyebrow">Cara kerja</span></div>
            <h2 class="section-title mb-3" data-aos="fade-up">Alur penggunaan</h2>
            <p class="section-subtitle mb-5" data-aos="fade-up">Cukup masuk ke akun Anda, dan seluruh proses peminjaman bisa langsung berjalan hingga aset sampai di tangan Anda.</p>

            <div class="row g-3">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-block">
                        <div class="step-num">01</div>
                        <h6>Masuk atau daftar</h6>
                        <p>Buka halaman login atau signup untuk memulai.</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="150">
                    <div class="step-block">
                        <div class="step-num">02</div>
                        <h6>Pilih aset</h6>
                        <p>Lihat katalog dan pilih alat yang tersedia.</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-block">
                        <div class="step-num">03</div>
                        <h6>Ikuti SOP</h6>
                        <p>Pahami ketentuan sebelum melanjutkan peminjaman.</p>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="250">
                    <div class="step-block">
                        <div class="step-num">04</div>
                        <h6>Pantau status</h6>
                        <p>Lacak peminjaman sampai pengembalian selesai.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7" data-aos="fade-right">
                    <span class="eyebrow" style="color:#f2c9ab;">Siap memulai?</span>
                    <h2 class="section-title mt-2">Masuk ke sistem sekarang</h2>
                    <p class="mb-0 mt-2">Lihat dashboard dan mulai proses peminjaman dengan lebih praktis.</p>
                </div>
                <div class="col-lg-5 text-lg-end" data-aos="fade-left">
                    <a href="<?= $login_url ?>" class="btn btn-primary-fik me-2 mb-2" style="background:white;color:var(--fik-ink);border-color:white;">Masuk</a>
                    <a href="<?= $signup_url ?>" class="btn btn-outline-fik mb-2" style="color:white;border-color:rgba(255,255,255,0.5);">Daftar</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-fik">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="mb-3">SCM FIK</h5>
                    <p class="text-muted mb-0" style="max-width:420px; font-weight:300;">Sistem peminjaman aset Fakultas Industri Kreatif yang modern, aman, dan mudah digunakan.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-2"><i class="bi bi-envelope me-2 text-fik-orange"></i>studio.fik@telkomuniversity.ac.id</p>
                    <p class="mb-0"><i class="bi bi-telephone me-2 text-fik-orange"></i>+62 811 2233 4455</p>
                </div>
            </div>
            <div class="footer-bottom d-flex justify-content-between flex-wrap gap-2">
                <span>&copy; <?= date('Y'); ?> SCM FIK. All rights reserved.</span>
                <span>Fakultas Industri Kreatif</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
    </script>
</body>
</html>