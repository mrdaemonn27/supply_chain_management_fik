<?php
// Mencegah akses langsung.
defined('BASEPATH') OR exit('No direct script access allowed');

// Logika routing dan session bawaan — jangan diubah.
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

// Tiga gambar perjalanan: gedung malam → pintu masuk → koridor laboratorium.
$img_night    = base_url('assets/images/hero-campus-night.png');
$img_entrance = base_url('assets/images/hero-campus-entrance.png');
$img_corridor = base_url('assets/images/hero-scm-corridor.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#05070c">
    <title>Sistem Supply Chain Management FIK - Telkom University</title>
    <meta name="description" content="Platform digital terintegrasi untuk pengajuan, persetujuan, peminjaman, dan pengembalian aset Fakultas Industri Kreatif Telkom University.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Instrument+Sans:wght@400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preload" as="image" href="<?= $img_night; ?>" fetchpriority="high">

    <style>
        :root {
            --telkom-red: #c8102e;
            --telkom-red-dark: #9f0d25;
            --telkom-red-light: #ff5c72;
            --ink: #05070c;
            --nav-height: 82px;
            --gutter: clamp(20px, 5vw, 84px);
            --ease-cinematic: cubic-bezier(0.22, 1, 0.36, 1);

            /* Bricolage Grotesque untuk display (punya optical sizing, jadi
               bentuk hurufnya menyesuaikan saat dipakai besar), Instrument
               Sans untuk teks agar tetap bersih dan mudah dibaca. */
            --font-display: "Bricolage Grotesque", "Space Grotesk", sans-serif;
            --font-body: "Instrument Sans", "Manrope", sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { background: var(--ink); }

        body {
            margin: 0;
            overflow-x: hidden;
            background: var(--ink);
            color: #fff;
            font-family: var(--font-body);
            font-optical-sizing: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        body.is-loading { overflow: hidden; }

        img { display: block; max-width: 100%; }
        a, button { color: inherit; text-decoration: none; font-family: inherit; }

        :focus-visible { outline: 3px solid rgba(255, 92, 114, 0.6); outline-offset: 3px; }

        /* ================= PRELOADER ================= */
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            background: var(--ink);
            transition: visibility 0s linear 0.9s;
        }

        .page-loader.is-hidden { visibility: hidden; pointer-events: none; }
        .loader-inner { width: min(560px, calc(100vw - 42px)); }

        .loader-label {
            margin-bottom: 16px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.24em;
            text-transform: uppercase;
        }

        .loader-number {
            margin-bottom: 22px;
            font-family: var(--font-display);
            font-optical-sizing: auto;
            font-size: clamp(4.4rem, 11.5vw, 8rem);
            font-weight: 700;
            letter-spacing: -0.05em;
            line-height: 0.84;
            font-variant-numeric: tabular-nums;
        }

        .loader-track { height: 2px; overflow: hidden; background: rgba(255, 255, 255, 0.12); }
        .loader-fill { width: 0; height: 100%; background: linear-gradient(90deg, var(--telkom-red), var(--telkom-red-light)); }

        /* ================= NAVBAR ================= */
        .site-nav {
            position: fixed;
            inset: 0 0 auto;
            z-index: 500;
            display: flex;
            align-items: center;
            min-height: var(--nav-height);
            padding-inline: var(--gutter);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: transparent;
            transition: min-height 0.4s var(--ease-cinematic), background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        .site-nav.is-scrolled {
            min-height: 66px;
            background: rgba(8, 10, 16, 0.72);
            border-bottom-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.4);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            backdrop-filter: blur(18px) saturate(140%);
        }

        .site-nav__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            max-width: 1520px;
            margin-inline: auto;
        }

        .site-nav__brand { display: inline-flex; align-items: center; flex-shrink: 0; }
        .site-nav__brand img { height: 34px; width: auto; filter: brightness(0) invert(1) drop-shadow(0 1px 3px rgba(0,0,0,.6)); }

        .nav-actions { display: flex; gap: 10px; flex-shrink: 0; }

        .btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 22px;
            border: 1px solid transparent;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: -0.005em;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, color 0.3s ease;
        }

        .btn--primary {
            border-color: var(--telkom-red);
            background: linear-gradient(135deg, var(--telkom-red), var(--telkom-red-dark));
            color: #fff;
            box-shadow: 0 14px 32px rgba(200, 16, 46, 0.32);
        }

        .btn--primary:hover { box-shadow: 0 18px 46px rgba(200, 16, 46, 0.5), 0 0 28px rgba(255, 92, 114, 0.35); }

        .btn--glass {
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(8, 10, 16, 0.68);
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            backdrop-filter: blur(12px) saturate(140%);
        }

        .btn--glass:hover { border-color: #fff; background: #fff; color: var(--ink); text-shadow: none; }

        .btn__arrow { transition: transform 0.3s var(--ease-cinematic); }
        .btn:hover .btn__arrow { transform: translateX(4px); }

        /* ================= HERO ================= */
        .cinematic-hero {
            position: relative;
            height: 100svh;
            min-height: 620px;
            overflow: hidden;
            background: #04050a;
            perspective: 1400px;
        }

        /* Lapisan transform terpisah: scroll / mouse / idle tidak pernah
           menulis transform pada elemen yang sama. */
        .scene-layer {
            position: absolute;
            inset: 0;
            opacity: 0;
            transform-style: preserve-3d;
        }

        .scene-layer--night { opacity: 1; }

        .scroll-camera,
        .mouse-parallax,
        .idle-breathing {
            position: absolute;
            inset: 0;
            transform-style: preserve-3d;
            will-change: transform;
        }

        .scene-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            user-select: none;
            -webkit-user-drag: none;
        }

        .scene-layer--night .scene-image { object-position: 66% 52%; }
        .scene-layer--entrance .scene-image { object-position: 56% 58%; }
        .scene-layer--corridor .scene-image { object-position: 60% 50%; }

        /* Shade berpindah sisi mengikuti posisi teks agar selalu terbaca. */
        .shade {
            position: absolute;
            inset: 0;
            z-index: 6;
            pointer-events: none;
            opacity: 0;
        }

        .shade--left {
            background: linear-gradient(90deg, rgba(3, 4, 9, 0.9) 0%, rgba(3, 4, 9, 0.68) 28%, rgba(3, 4, 9, 0.24) 54%, transparent 80%);
        }

        .shade--right {
            background: linear-gradient(270deg, rgba(3, 4, 9, 0.9) 0%, rgba(3, 4, 9, 0.68) 28%, rgba(3, 4, 9, 0.24) 54%, transparent 80%);
        }

        /* Shade simetris untuk scene penutup yang terpusat. */
        .shade--center {
            background: radial-gradient(ellipse 72% 74% at 50% 50%, rgba(3, 4, 9, 0.5) 0%, rgba(3, 4, 9, 0.78) 58%, rgba(3, 4, 9, 0.92) 100%);
        }

        .shade--base {
            opacity: 1;
            background: linear-gradient(180deg, rgba(3, 4, 9, 0.5) 0%, rgba(3, 4, 9, 0.16) 34%, rgba(3, 4, 9, 0.5) 100%);
        }

        /* ================= KABUT ================= */
        .fog {
            position: absolute;
            top: -20%;
            width: 75%;
            height: 140%;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
            will-change: transform, opacity;
        }

        /* Kabut malam: kebiruan, tiga kedalaman */
        .fog--back {
            left: -25%;
            z-index: 4;
            filter: blur(60px);
            background: radial-gradient(ellipse at center, rgba(150, 176, 205, 0.85), rgba(120, 145, 180, 0) 70%);
        }

        .fog--middle {
            left: -45%;
            z-index: 5;
            width: 85%;
            filter: blur(48px);
            background: radial-gradient(ellipse at center, rgba(205, 220, 236, 0.9), rgba(180, 200, 225, 0) 70%);
        }

        .fog--front {
            left: -60%;
            z-index: 7;
            width: 110%;
            filter: blur(78px);
            background: radial-gradient(ellipse at center, rgba(228, 238, 248, 0.95), rgba(210, 226, 240, 0) 72%);
        }

        /* Kabut kedua: putih hangat seperti cahaya interior & embun kaca */
        .fog--warm-left, .fog--warm-right {
            z-index: 7;
            width: 90%;
            filter: blur(70px);
            background: radial-gradient(ellipse at center, rgba(255, 238, 208, 0.95), rgba(255, 226, 180, 0) 72%);
        }

        .fog--warm-left { left: -55%; }
        .fog--warm-right { right: -55%; left: auto; }

        /* ================= OVERLAY SINEMATIK ================= */
        .light-overlay {
            position: absolute;
            inset: 0;
            z-index: 5;
            opacity: 0;
            pointer-events: none;
            background: radial-gradient(circle at 58% 58%, rgba(255, 232, 190, 0.9), rgba(255, 210, 150, 0) 58%);
        }

        .reflection-overlay {
            position: absolute;
            inset: 0;
            z-index: 5;
            opacity: 0;
            pointer-events: none;
            background: linear-gradient(112deg, transparent 34%, rgba(255, 255, 255, 0.26) 47%, rgba(255, 255, 255, 0.05) 54%, transparent 66%);
        }

        .vignette-overlay {
            position: absolute;
            inset: 0;
            z-index: 8;
            pointer-events: none;
            opacity: 0.58;
            background: radial-gradient(ellipse 76% 72% at 56% 50%, transparent 42%, rgba(2, 3, 6, 0.94) 118%);
        }

        .film-grain {
            position: absolute;
            inset: 0;
            z-index: 9;
            pointer-events: none;
            opacity: 0.04;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.9'/%3E%3C/svg%3E");
        }

        /* Cahaya hangat yang mengembang dari arah pintu saat kamera menembus
           masuk. Menggantikan panel pintu kaca yang tampak seperti bidang
           putih kaku, dan memakai warna yang sama dengan kabut interior. */
        .portal-bloom {
            position: absolute;
            top: 52%;
            left: 56%;
            z-index: 7;
            width: 62vmax;
            height: 62vmax;
            translate: -50% -50%;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
            filter: blur(34px);
            background: radial-gradient(circle,
                rgba(255, 244, 222, 0.92) 0%,
                rgba(255, 232, 194, 0.55) 32%,
                rgba(255, 214, 158, 0.18) 56%,
                rgba(255, 210, 150, 0) 74%);
        }

        /* ================= SCENE TEKS (kiri ⇄ kanan) ================= */
        .story {
            position: absolute;
            inset: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            padding: calc(var(--nav-height) + 24px) var(--gutter) 108px;
            opacity: 0;
            pointer-events: none;
            will-change: transform, opacity;
        }

        .story--left { justify-content: flex-start; }
        .story--right { justify-content: flex-end; }

        .story__inner { width: min(520px, 46%); }

        /* Scene penutup: teks dipusatkan agar terasa menutup rangkaian. */
        .story--center { justify-content: center; }
        .story--center .story__inner { width: min(700px, 80%); text-align: center; }
        .story--center .story__desc { max-width: 560px; margin-inline: auto; }
        .story--center .story__actions { justify-content: center; }

        .story__step {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            color: var(--telkom-red-light);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            font-variant-numeric: tabular-nums;
        }

        .story__step::before { content: ""; width: 26px; height: 2px; background: currentColor; }

        .story__title {
            margin: 0;
            font-family: var(--font-display);
            font-optical-sizing: auto;
            font-weight: 700;
            letter-spacing: -0.035em;
            line-height: 1.06;
            font-size: clamp(2rem, 3.7vw, 3.1rem);
            text-wrap: balance;
            text-shadow: 0 2px 26px rgba(0, 0, 0, 0.75);
            /* Ruang 3D khusus judul, supaya tiap huruf bisa berputar sendiri
               tanpa mengganggu transform kontainer scene. */
            perspective: 760px;
        }

        .story__title .accent { color: var(--telkom-red-light); }

        /* Tiap kata dibungkus dan dikunci agar baris hanya boleh patah di
           spasi — tanpa ini huruf inline-block bisa terpenggal di tengah kata. */
        .story__title .word {
            display: inline-block;
            white-space: nowrap;
        }

        /* Tiap huruf jadi elemennya sendiri agar bisa dianimasikan terpisah. */
        .story__title .char {
            display: inline-block;
            will-change: transform, opacity, filter;
            backface-visibility: hidden;
            transform-origin: 50% 80%;
        }

        .story__desc {
            margin: 20px 0 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: clamp(0.95rem, 1.05vw, 1.06rem);
            font-weight: 400;
            line-height: 1.72;
            letter-spacing: -0.005em;
            text-wrap: pretty;
            text-shadow: 0 1px 14px rgba(0, 0, 0, 0.8);
            will-change: transform, opacity, filter;
        }

        .story__actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; pointer-events: auto; }

        .scroll-cue {
            position: absolute;
            z-index: 12;
            bottom: clamp(22px, 4vh, 34px);
            left: 50%;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            max-width: calc(100vw - 32px);
            padding: 10px 18px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 999px;
            background: rgba(8, 10, 16, 0.5);
            color: rgba(255, 255, 255, 0.9);
            font-size: clamp(0.6rem, 1.6vw, 0.67rem);
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            white-space: nowrap;
            transform: translateX(-50%);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
        }

        .scroll-cue i { animation: cueArrow 1.6s ease-in-out infinite; }

        @keyframes cueArrow {
            0%, 100% { transform: translateY(-2px); }
            50% { transform: translateY(4px); }
        }

        .no-js-note {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 10000;
            max-width: 360px;
            padding: 12px 16px;
            border-radius: 10px;
            background: #0b0e15;
            color: #fff;
            font-size: 0.82rem;
        }

        .no-js-note a { color: var(--telkom-red-light); font-weight: 700; }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1279.98px) {
            .story__inner { width: min(480px, 52%); }
        }

        @media (max-width: 1023.98px) {
            :root { --nav-height: 70px; --gutter: 22px; }
            .story__inner { width: min(440px, 58%); }
            .story__title { font-size: clamp(1.7rem, 3.6vw, 2.3rem); }
        }

        /* Layar sempit: teks jadi satu kolom penuh di bawah agar tetap terbaca. */
        @media (max-width: 767.98px) {
            .scene-layer--night .scene-image { object-position: 70% 52%; }
            .scene-layer--entrance .scene-image { object-position: 56% 55%; }
            .scene-layer--corridor .scene-image { object-position: 68% 50%; }

            .story { align-items: flex-end; padding-bottom: 124px; }
            .story--left, .story--right, .story--center { justify-content: flex-start; }
            .story__inner { width: 100%; }
            /* Penutup tetap terpusat agar kesan mengakhiri tidak hilang. */
            .story--center .story__inner { width: 100%; text-align: center; }
            .story__title { font-size: clamp(1.6rem, 6.6vw, 2.2rem); }
            .story__desc { font-size: 0.94rem; line-height: 1.68; }
            .story__actions { flex-direction: column; align-items: stretch; }

            .shade--left, .shade--right {
                background: linear-gradient(180deg, rgba(3,4,9,.45) 0%, rgba(3,4,9,.35) 38%, rgba(3,4,9,.94) 100%);
            }
        }

        @media (max-width: 575.98px) {
            :root { --gutter: 16px; }
            .site-nav__brand img { height: 22px; }
            .btn { padding: 10px 13px; font-size: 0.75rem; }
            .nav-actions { gap: 7px; }
            .scroll-cue { gap: 8px; padding: 9px 14px; letter-spacing: 0.08em; }
        }

        @media (max-width: 359.98px) {
            .site-nav__brand img { height: 20px; }
            .btn { padding: 9px 11px; font-size: 0.72rem; }
        }

        /* Tanpa animasi: gambar pertama sebagai latar, seluruh teks ditumpuk. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
                scroll-behavior: auto !important;
            }

            .cinematic-hero { height: auto; }
            .scene-layer--entrance, .scene-layer--corridor { opacity: 0; }
            .story {
                position: relative;
                inset: auto;
                opacity: 1 !important;
                pointer-events: auto;
                padding: 52px var(--gutter);
                background: rgba(4, 5, 10, 0.74);
            }
            .story--left, .story--right, .story--center { justify-content: flex-start; }
            .story__inner { width: min(640px, 100%); }
            .story__title { perspective: none; }
            .shade--left, .shade--right, .shade--center, .fog, .portal-bloom, .scroll-cue { display: none; }
        }
    </style>
</head>
<body class="is-loading">
    <noscript>
        <div class="no-js-note">Animasi memerlukan JavaScript. Untuk masuk langsung, gunakan <a href="<?= $login_url; ?>">halaman login</a>.</div>
    </noscript>

    <div class="page-loader" id="pageLoader" aria-hidden="true">
        <div class="loader-inner">
            <div class="loader-label">MEMUAT SISTEM SUPPLY CHAIN MANAGEMENT</div>
            <div class="loader-number" id="loaderNumber">0%</div>
            <div class="loader-track"><div class="loader-fill" id="loaderFill"></div></div>
        </div>
    </div>

    <nav class="site-nav" id="siteNav" aria-label="Navigasi utama">
        <div class="site-nav__inner">
            <a class="site-nav__brand" href="<?= base_url(); ?>" aria-label="SCM FIK - Beranda">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo Fakultas Industri Kreatif">
            </a>
            <div class="nav-actions">
                <a class="btn btn--glass" href="<?= $login_url; ?>">Masuk</a>
                <a class="btn btn--primary" href="<?= $signup_url; ?>">Daftar</a>
            </div>
        </div>
    </nav>

    <main>
        <section class="cinematic-hero" id="cinematicHero" aria-labelledby="heroTitle">

            <!-- ===== Gambar 1: gedung FIK malam hari ===== -->
            <div class="scene-layer scene-layer--night" id="layerNight">
                <div class="scroll-camera" id="camNight">
                    <div class="mouse-parallax" data-parallax="8">
                        <div class="idle-breathing" data-idle>
                            <img class="scene-image" src="<?= $img_night; ?>" alt="Gedung Fakultas Industri Kreatif Telkom University pada malam hari" fetchpriority="high">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Gambar 2: pintu masuk FIK ===== -->
            <div class="scene-layer scene-layer--entrance" id="layerEntrance">
                <div class="scroll-camera" id="camEntrance">
                    <div class="mouse-parallax" data-parallax="12">
                        <div class="idle-breathing" data-idle>
                            <img class="scene-image" src="<?= $img_entrance; ?>" alt="Pintu masuk utama Fakultas Industri Kreatif" decoding="async">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== Gambar 3: koridor laboratorium SCM ===== -->
            <div class="scene-layer scene-layer--corridor" id="layerCorridor">
                <div class="scroll-camera" id="camCorridor">
                    <div class="mouse-parallax" data-parallax="6">
                        <div class="idle-breathing" data-idle>
                            <img class="scene-image" src="<?= $img_corridor; ?>" alt="Koridor Supply Chain Management Lab, Inventory &amp; Asset Lab, dan Equipment Borrowing Service" decoding="async">
                        </div>
                    </div>
                </div>
            </div>

            <div class="shade shade--base" aria-hidden="true"></div>
            <div class="shade shade--left" id="shadeLeft" aria-hidden="true"></div>
            <div class="shade shade--right" id="shadeRight" aria-hidden="true"></div>
            <div class="shade shade--center" id="shadeCenter" aria-hidden="true"></div>

            <span class="fog fog--back" id="fogBack" aria-hidden="true"></span>
            <span class="fog fog--middle" id="fogMiddle" aria-hidden="true"></span>
            <span class="fog fog--front" id="fogFront" aria-hidden="true"></span>
            <span class="fog fog--warm-left" id="fogWarmLeft" aria-hidden="true"></span>
            <span class="fog fog--warm-right" id="fogWarmRight" aria-hidden="true"></span>

            <div class="portal-bloom" id="portalBloom" aria-hidden="true"></div>

            <div class="light-overlay" id="lightOverlay" aria-hidden="true"></div>
            <div class="reflection-overlay" id="reflectionOverlay" aria-hidden="true"></div>
            <div class="vignette-overlay" id="vignette" aria-hidden="true"></div>
            <div class="film-grain" aria-hidden="true"></div>

            <!-- 01 — kiri · gambar 1 -->
            <article class="story story--left" data-side="left">
                <div class="story__inner">
                    <span class="story__step">Supply Chain Management FIK</span>
                    <h1 class="story__title" id="heroTitle">Satu Sistem untuk<br><span class="accent">Seluruh Aset</span> Fakultas</h1>
                    <p class="story__desc">
                        Platform digital Fakultas Industri Kreatif untuk mengelola pengajuan, persetujuan, peminjaman, hingga pengembalian aset — semuanya tercatat rapi dalam satu alur.
                    </p>
                </div>
            </article>

            <!-- 02 — kanan · gambar 1 -->
            <article class="story story--right" data-side="right">
                <div class="story__inner">
                    <span class="story__step">01 — Pengajuan</span>
                    <h2 class="story__title">Ajukan Kebutuhan Secara Digital</h2>
                    <p class="story__desc">
                        Mahasiswa dan dosen mengajukan peminjaman alat atau permintaan barang langsung dari sistem — lengkap dengan tanggal pakai, jumlah, dan keperluan. Tidak ada lagi formulir kertas.
                    </p>
                </div>
            </article>

            <!-- 03 — kiri · gambar 2 -->
            <article class="story story--left" data-side="left">
                <div class="story__inner">
                    <span class="story__step">02 — Persetujuan</span>
                    <h2 class="story__title">Disetujui Secara Berjenjang</h2>
                    <p class="story__desc">
                        Pengajuan diteruskan otomatis kepada Kaprodi dan Kaur untuk ditinjau. Setiap keputusan tercatat lengkap dengan waktu dan penanggung jawabnya.
                    </p>
                </div>
            </article>

            <!-- 04 — kanan · gambar 2 -->
            <article class="story story--right" data-side="right">
                <div class="story__inner">
                    <span class="story__step">03 — Persiapan Aset</span>
                    <h2 class="story__title">Disiapkan dan Diverifikasi Laboran</h2>
                    <p class="story__desc">
                        Setelah disetujui, laboran menyiapkan unit yang diminta, memeriksa kelengkapan dan kondisinya, lalu menandainya siap diambil.
                    </p>
                </div>
            </article>

            <!-- 05 — kiri · gambar 3 -->
            <article class="story story--left" data-side="left">
                <div class="story__inner">
                    <span class="story__step">04 — Peminjaman</span>
                    <h2 class="story__title">Serah Terima dengan QR Code</h2>
                    <p class="story__desc">
                        Aset diserahkan di Equipment Borrowing Service. Proses serah terima diverifikasi melalui QR code dan tercatat dalam berita acara digital.
                    </p>
                </div>
            </article>

            <!-- 06 — tengah · gambar 3 (penutup) -->
            <article class="story story--center" data-side="center">
                <div class="story__inner">
                    <span class="story__step">05 — Pengembalian</span>
                    <h2 class="story__title">Kembali Tepat Waktu,<br><span class="accent">Terpantau</span> Penuh</h2>
                    <p class="story__desc">
                        Status tiap aset terpantau hingga kembali ke Inventory &amp; Asset Lab. Stok, riwayat, dan kondisi barang tersimpan terpusat.
                    </p>
                    <div class="story__actions">
                        <a href="<?= $login_url; ?>" class="btn btn--primary">
                            Ajukan Peminjaman
                            <i class="bi bi-arrow-right btn__arrow" aria-hidden="true"></i>
                        </a>
                        <a href="<?= $dashboard_url; ?>" class="btn btn--glass">Masuk ke Dashboard</a>
                    </div>
                </div>
            </article>

            <div class="scroll-cue" id="scrollCue" aria-hidden="true">
                <span>Scroll untuk Melihat Alur</span>
                <i class="bi bi-chevron-down"></i>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js"></script>

    <script>
        (() => {
            "use strict";

            const doc = document;
            const body = doc.body;
            const $ = (id) => doc.getElementById(id);

            const nav = $("siteNav");
            const hero = $("cinematicHero");

            const layerNight = $("layerNight");
            const layerEntrance = $("layerEntrance");
            const layerCorridor = $("layerCorridor");
            const camNight = $("camNight");
            const camEntrance = $("camEntrance");
            const camCorridor = $("camCorridor");

            const shadeLeft = $("shadeLeft");
            const shadeRight = $("shadeRight");
            const shadeCenter = $("shadeCenter");
            const fogBack = $("fogBack");
            const fogMiddle = $("fogMiddle");
            const fogFront = $("fogFront");
            const fogWarmLeft = $("fogWarmLeft");
            const fogWarmRight = $("fogWarmRight");
            const portalBloom = $("portalBloom");
            const lightOverlay = $("lightOverlay");
            const reflectionOverlay = $("reflectionOverlay");
            const vignette = $("vignette");
            const scrollCue = $("scrollCue");
            const loader = $("pageLoader");
            const loaderNumber = $("loaderNumber");
            const loaderFill = $("loaderFill");

            const stories = Array.from(doc.querySelectorAll(".story"));

            const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
            const coarsePointer = window.matchMedia("(pointer: coarse)");
            const isNarrow = window.matchMedia("(max-width: 767.98px)");

            let lenis = null;
            let experienceReady = false;
            let loaderValue = 0;
            let loaderTimer = null;
            let loaderFinished = false;
            let idleTl = null;
            let idleTimer = null;

            if ("scrollRestoration" in history) history.scrollRestoration = "manual";
            window.scrollTo(0, 0);

            function updateLoader(value) {
                loaderValue = Math.max(loaderValue, Math.min(100, Math.round(value)));
                loaderNumber.textContent = `${loaderValue}%`;
                loaderFill.style.width = `${loaderValue}%`;
            }

            function startLoader() {
                const startedAt = performance.now();
                loaderTimer = window.setInterval(() => {
                    const elapsed = performance.now() - startedAt;
                    const target = Math.min(94, (elapsed / 900) * 94);
                    updateLoader(Math.max(target, loaderValue + (loaderValue < 55 ? 2.5 : 0.9)));
                }, 55);

                const img = layerNight.querySelector("img");
                const done = () => finishLoader(startedAt);

                if (img && !img.complete) {
                    img.addEventListener("load", done, { once: true });
                    img.addEventListener("error", done, { once: true });
                    window.setTimeout(done, 2600);
                } else {
                    window.setTimeout(done, 800);
                }

                // Pengaman: animasi GSAP bergantung pada requestAnimationFrame,
                // yang dihentikan browser saat tab berada di latar belakang.
                // Timer ini memastikan loader tidak pernah tersangkut.
                window.setTimeout(forceHideLoader, 4500);
            }

            function forceHideLoader() {
                if (!body.classList.contains("is-loading")) return;
                window.clearInterval(loaderTimer);
                loaderFinished = true;
                updateLoader(100);
                if (window.gsap) gsap.set(loader, { yPercent: -105 });
                loader.classList.add("is-hidden");
                body.classList.remove("is-loading");
                initExperience();
            }

            function finishLoader(startedAt) {
                if (loaderFinished) return;
                loaderFinished = true;
                window.clearInterval(loaderTimer);

                const wait = Math.max(0, 420 - (performance.now() - startedAt));
                window.setTimeout(() => {
                    if (window.gsap) {
                        gsap.to({ value: loaderValue }, {
                            value: 100, duration: 0.4, ease: "power2.out",
                            onUpdate() { updateLoader(this.targets()[0].value); },
                            onComplete: hideLoader
                        });
                    } else {
                        updateLoader(100);
                        hideLoader();
                    }
                }, wait);
            }

            function hideLoader() {
                if (window.gsap) {
                    gsap.to(loader, {
                        yPercent: -105, duration: 0.8, ease: "power3.inOut",
                        onComplete() {
                            loader.classList.add("is-hidden");
                            body.classList.remove("is-loading");
                            initExperience();
                        }
                    });
                } else {
                    loader.classList.add("is-hidden");
                    body.classList.remove("is-loading");
                    initStaticFallback();
                }
            }

            function setupLenis() {
                if (reducedMotion.matches || typeof window.Lenis === "undefined") return;
                lenis = new Lenis({
                    duration: 1.05, smoothWheel: true, syncTouch: false,
                    wheelMultiplier: 0.95, touchMultiplier: 1.1
                });
                lenis.on("scroll", ScrollTrigger.update);
                gsap.ticker.add((time) => lenis.raf(time * 1000));
                gsap.ticker.lagSmoothing(0);
            }

            // ============ ANIMASI FONT ============
            // Judul dipecah menjadi huruf per huruf supaya bisa diterbangkan
            // satu per satu. Hanya simpul teks yang disentuh, sehingga <br>
            // dan <span class="accent"> tetap utuh.
            function splitChars(root) {
                if (!root || root.dataset.split) return;
                root.dataset.split = "1";

                const walker = doc.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
                const textNodes = [];
                while (walker.nextNode()) textNodes.push(walker.currentNode);

                textNodes.forEach((node) => {
                    const frag = doc.createDocumentFragment();

                    // Dipecah per kata lebih dulu. Tiap kata jadi satu kotak
                    // yang tidak boleh dipenggal, sehingga baris hanya patah
                    // di spasi — bukan di tengah kata.
                    node.textContent.split(/(\s+)/).forEach((part) => {
                        if (part === "") return;

                        if (/^\s+$/.test(part)) {
                            frag.appendChild(doc.createTextNode(" "));
                            return;
                        }

                        const word = doc.createElement("span");
                        word.className = "word";
                        part.split("").forEach((ch) => {
                            const s = doc.createElement("span");
                            s.className = "char";
                            s.textContent = ch;
                            word.appendChild(s);
                        });
                        frag.appendChild(word);
                    });

                    node.parentNode.replaceChild(frag, node);
                });
            }

            // Keadaan awal & akhir huruf, dipakai bersama animasi pembuka dan
            // timeline scroll. Dibuat sebagai fungsi — bukan objek tetap —
            // karena GSAP menambahkan properti internalnya ke objek vars yang
            // diterima, sehingga objek yang sama tidak boleh dipakai dua kali.
            const charFrom = (extra) => Object.assign(
                { yPercent: 118, rotateX: -78, autoAlpha: 0, filter: "blur(6px)", transformPerspective: 760 }, extra);
            const charTo = (extra) => Object.assign(
                { yPercent: 0, rotateX: 0, autoAlpha: 1, filter: "blur(0px)" }, extra);
            const descFrom = (extra) => Object.assign(
                { y: 26, autoAlpha: 0, filter: "blur(9px)" }, extra);
            const descTo = (extra) => Object.assign(
                { y: 0, autoAlpha: 1, filter: "blur(0px)" }, extra);

            // ===== Pembuka: gedung keluar dari gelap, teks pertama masuk =====
            function runOpening() {
                gsap.set(stories, { autoAlpha: 0 });
                gsap.set([layerEntrance, layerCorridor], { autoAlpha: 0 });
                gsap.set([shadeLeft, shadeRight, shadeCenter], { opacity: 0 });
                gsap.set([fogBack, fogMiddle, fogFront, fogWarmLeft, fogWarmRight], { opacity: 0 });
                gsap.set([portalBloom, lightOverlay, reflectionOverlay], { opacity: 0 });
                gsap.set(camNight, { scale: 1.06, transformOrigin: "66% 54%" });
                // Mulai sudah gelap dan hanya naik sedikit, sehingga tampilan
                // temaram itu terlihat sejak halaman dibuka — bukan baru
                // muncul setelah digulir.
                gsap.set(layerNight.querySelector("img"), { filter: "brightness(0.4) saturate(0.8)" });
                gsap.set(vignette, { opacity: 0.82 });

                const first = stories[0];

                gsap.timeline({ defaults: { ease: "power3.out" } })
                    .to(layerNight.querySelector("img"), { filter: "brightness(0.62) saturate(0.9)", duration: 2.1, ease: "power2.out" }, 0.1)
                    .to(vignette, { opacity: 0.72, duration: 2.1 }, 0.1)
                    .to(camNight, { scale: 1, duration: 2.2, ease: "power2.out" }, 0.1)
                    .to(shadeLeft, { opacity: 1, duration: 1.4 }, 0.3)
                    .to(fogBack, { opacity: 0.14, duration: 1.6 }, 0.6)
                    .fromTo(nav, { yPercent: -100, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: 0.8 }, 0.4)
                    .set(first, { autoAlpha: 1 }, 0.7)
                    // Kicker: garis dan huruf melebar dari rapat.
                    .fromTo(first.querySelector(".story__step"),
                        { autoAlpha: 0, letterSpacing: "0.02em", x: -12 },
                        { autoAlpha: 1, letterSpacing: "0.26em", x: 0, duration: 0.9 }, 0.72)
                    // Judul: tiap huruf terbit dari bawah sambil berputar.
                    .fromTo(first.querySelectorAll(".char"), charFrom(),
                        charTo({ duration: 0.95, stagger: 0.026, ease: "back.out(1.7)" }), 0.82)
                    // Deskripsi: buram menjadi tajam sambil naik ringan.
                    .fromTo(first.querySelector(".story__desc"), descFrom(),
                        descTo({ duration: 0.9 }), 1.25)
                    .fromTo(scrollCue, { y: 14, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.6 }, 1.55);
            }

            function setupMouseParallax() {
                if (coarsePointer.matches || reducedMotion.matches) return;

                const movers = gsap.utils.toArray("[data-parallax]").map((el) => ({
                    depth: parseFloat(el.dataset.parallax) || 8,
                    x: gsap.quickTo(el, "x", { duration: 0.9, ease: "power3.out" }),
                    y: gsap.quickTo(el, "y", { duration: 0.9, ease: "power3.out" })
                }));

                hero.addEventListener("pointermove", (event) => {
                    const rect = hero.getBoundingClientRect();
                    const nx = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
                    const ny = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
                    movers.forEach((m) => { m.x(nx * -m.depth); m.y(ny * -m.depth); });
                }, { passive: true });

                hero.addEventListener("pointerleave", () => {
                    movers.forEach((m) => { m.x(0); m.y(0); });
                }, { passive: true });
            }

            function setupIdleBreathing() {
                if (reducedMotion.matches) return;

                idleTl = gsap.timeline({ repeat: -1, yoyo: true, paused: true })
                    .to("[data-idle]", { scale: 1.008, duration: 5.5, ease: "sine.inOut" }, 0);

                const pauseIdle = () => {
                    idleTl.pause();
                    window.clearTimeout(idleTimer);
                    idleTimer = window.setTimeout(() => idleTl.play(), 900);
                };

                window.addEventListener("scroll", pauseIdle, { passive: true });
                idleTimer = window.setTimeout(() => idleTl.play(), 1400);
            }

            // ================= MASTER TIMELINE (0–100) =================
            // Gambar 1: scene 1–2 · Gambar 2: scene 3–4 · Gambar 3: scene 5–6
            // Teks bergantian: kiri → kanan → kiri → kanan → kiri → tengah
            function buildJourney() {
                const SPAN = 100 / stories.length;   // 16.67 per scene
                const HOLD = SPAN * 0.62;

                const nightImg = layerNight.querySelector("img");
                const entranceImg = layerEntrance.querySelector("img");
                const corridorImg = layerCorridor.querySelector("img");

                // Titik perpindahan gambar, tepat di sela antar-scene.
                const SWAP_1 = SPAN * 2;   // 33.3 — gambar 1 → 2
                const SWAP_2 = SPAN * 4;   // 66.7 — gambar 2 → 3

                const tl = gsap.timeline({
                    defaults: { ease: "none" },
                    scrollTrigger: {
                        trigger: hero,
                        start: "top top",
                        end: "+=520%",
                        pin: true,
                        scrub: 1.1,
                        anticipatePin: 1,
                        invalidateOnRefresh: true,
                        onUpdate(self) {
                            nav.classList.toggle("is-scrolled", self.progress > 0.02);
                            const last = stories[stories.length - 1];
                            last.style.pointerEvents = self.progress > 0.88 ? "auto" : "none";
                        }
                    }
                });

                // Scene pertama dimiliki timeline ini juga agar keadaannya pasti
                // benar walau ScrollTrigger di-refresh / di-scrub ulang.
                tl.set(stories[0], { autoAlpha: 1, x: 0, y: 0 }, 0)
                  .set(stories[0].querySelectorAll(".char"), charTo(), 0)
                  .set(stories[0].querySelector(".story__desc"), descTo(), 0);

                /* ---------- GAMBAR 1: gedung malam (scene 1–2) ---------- */
                tl.to(camNight, { scale: 1.28, yPercent: -4, xPercent: -3, duration: SWAP_1, ease: "power1.in" }, 0)
                  .to(scrollCue, { autoAlpha: 0, duration: 5 }, 5)
                  .to(fogBack, { opacity: 0.28, xPercent: 24, duration: SWAP_1 }, 0)
                  .to(lightOverlay, { opacity: 0.32, duration: SPAN }, SPAN)

                /* ---------- WHOOSH 1: kabut malam, gambar 1 → 2 ---------- */
                  .to(camNight, { scale: 1.42, duration: SPAN * 0.7, ease: "power2.in" }, SWAP_1 - SPAN * 0.34)
                  .to(nightImg, { filter: "brightness(0.62) saturate(0.9) blur(9px)", duration: SPAN * 0.5 }, SWAP_1 - SPAN * 0.34)
                  .to(layerNight, { autoAlpha: 0, duration: SPAN * 0.3 }, SWAP_1 - SPAN * 0.12)
                  .to(fogBack, { opacity: 0.55, xPercent: 70, duration: SPAN * 0.6 }, SWAP_1 - SPAN * 0.34)
                  .fromTo(fogMiddle, { opacity: 0, xPercent: -20 },
                      { opacity: 0.7, xPercent: 110, duration: SPAN * 0.55 }, SWAP_1 - SPAN * 0.3)
                  .fromTo(fogFront, { opacity: 0, xPercent: -30, scale: 1 },
                      { opacity: 0.85, xPercent: 95, scale: 1.25, duration: SPAN * 0.5, ease: "power2.inOut" }, SWAP_1 - SPAN * 0.26)
                  .to(lightOverlay, { opacity: 0.6, duration: SPAN * 0.3 }, SWAP_1 - SPAN * 0.26)

                /* ---------- GAMBAR 2: pintu masuk (scene 3–4) ---------- */
                  .set(layerEntrance, { autoAlpha: 1 }, SWAP_1 - SPAN * 0.1)
                  .fromTo(camEntrance,
                      { scale: 1.12, rotateX: 1, transformOrigin: "56% 60%" },
                      { scale: 1, rotateX: 0, duration: SPAN * 0.55, ease: "power2.out" }, SWAP_1 - SPAN * 0.1)
                  .fromTo(entranceImg, { filter: "brightness(1) blur(8px)" },
                      { filter: "brightness(1) blur(0px)", duration: SPAN * 0.5 }, SWAP_1 - SPAN * 0.06)
                  .to([fogFront, fogMiddle], { opacity: 0, duration: SPAN * 0.35 }, SWAP_1 + SPAN * 0.2)
                  .to(fogBack, { opacity: 0.14, duration: SPAN * 0.35 }, SWAP_1 + SPAN * 0.2)
                  .to(lightOverlay, { opacity: 0.16, duration: SPAN * 0.35 }, SWAP_1 + SPAN * 0.2)
                  // lobby menghangat, refleksi lantai naik
                  .to(entranceImg, { filter: "brightness(1.08) saturate(1.06) blur(0px)", duration: SPAN * 0.6 }, SWAP_1 + SPAN * 0.35)
                  .to(reflectionOverlay, { opacity: 0.24, duration: SPAN * 0.6 }, SWAP_1 + SPAN * 0.4)
                  // kamera maju menuju pintu kaca
                  .to(camEntrance, { scale: 1.3, yPercent: -5, rotateX: -0.8, rotateY: 0.4, duration: SPAN * 1.1, ease: "power2.in" }, SWAP_1 + SPAN * 0.9)

                /* ---------- WHOOSH 2: cahaya pintu + kabut hangat, gambar 2 → 3 ---------- */
                  // Cahaya hangat mengembang dari arah pintu, seolah kamera
                  // menembus masuk — selaras dengan kabut hangat di bawah ini.
                  .fromTo(portalBloom, { opacity: 0, scale: 0.28 },
                      { opacity: 0.95, scale: 1.15, duration: SPAN * 0.5, ease: "power2.in" }, SWAP_2 - SPAN * 0.48)
                  .to(camEntrance, { scale: 1.58, duration: SPAN * 0.55, ease: "power2.in" }, SWAP_2 - SPAN * 0.42)
                  .to(entranceImg, { filter: "brightness(1.2) blur(10px)", duration: SPAN * 0.45 }, SWAP_2 - SPAN * 0.42)
                  .fromTo(fogWarmLeft, { opacity: 0, xPercent: -20 },
                      { opacity: 0.9, xPercent: 55, duration: SPAN * 0.4, ease: "power2.out" }, SWAP_2 - SPAN * 0.4)
                  .fromTo(fogWarmRight, { opacity: 0, xPercent: 20 },
                      { opacity: 0.9, xPercent: -55, duration: SPAN * 0.4, ease: "power2.out" }, SWAP_2 - SPAN * 0.4)
                  .to(lightOverlay, { opacity: 0.7, duration: SPAN * 0.3 }, SWAP_2 - SPAN * 0.38)
                  .to(layerEntrance, { autoAlpha: 0, duration: SPAN * 0.25 }, SWAP_2 - SPAN * 0.14)

                /* ---------- GAMBAR 3: koridor SCM (scene 5–6) ---------- */
                  .set(layerCorridor, { autoAlpha: 1 }, SWAP_2 - SPAN * 0.12)
                  .fromTo(camCorridor,
                      { scale: 1.14, z: 100, transformOrigin: "58% 50%" },
                      { scale: 1, z: 0, duration: SPAN * 0.6, ease: "power2.out" }, SWAP_2 - SPAN * 0.12)
                  .fromTo(corridorImg, { filter: "brightness(1) blur(9px)", opacity: 0 },
                      { filter: "brightness(1) blur(0px)", opacity: 1, duration: SPAN * 0.5, ease: "power2.out" }, SWAP_2 - SPAN * 0.12)
                  // kabut & cahaya pintu memudar — tidak menetap di koridor
                  .to(fogWarmLeft, { opacity: 0, xPercent: 120, duration: SPAN * 0.45, ease: "power2.inOut" }, SWAP_2 + SPAN * 0.05)
                  .to(fogWarmRight, { opacity: 0, xPercent: -120, duration: SPAN * 0.45, ease: "power2.inOut" }, SWAP_2 + SPAN * 0.05)
                  .to(portalBloom, { opacity: 0, scale: 1.9, duration: SPAN * 0.5, ease: "power2.out" }, SWAP_2 - SPAN * 0.05)
                  .to(fogBack, { opacity: 0, duration: SPAN * 0.4 }, SWAP_2 + SPAN * 0.05)
                  .to(lightOverlay, { opacity: 0.12, duration: SPAN * 0.4 }, SWAP_2 + SPAN * 0.05)
                  .to(reflectionOverlay, { opacity: 0.1, duration: SPAN * 0.4 }, SWAP_2 + SPAN * 0.05)
                  // kamera menyusuri koridor pelan sampai akhir
                  .to(camCorridor, { scale: 1.07, xPercent: -1.4, yPercent: -0.6, duration: SPAN * 1.7, ease: "power1.out" }, SWAP_2 + SPAN * 0.3)
                  .to(vignette, { opacity: 0.48, duration: SPAN }, SWAP_2 + SPAN * 0.3);

                /* ---------- TEKS: bergantian kiri ⇄ kanan, ditutup di tengah ---------- */
                stories.forEach((el, i) => {
                    const at = i * SPAN;
                    const out = at + HOLD;
                    const side = el.dataset.side;
                    const isCenter = side === "center";
                    const fromLeft = side === "left";

                    // Scene penutup dan layar sempit tidak bergeser mendatar,
                    // melainkan naik dari bawah agar terasa mengakhiri rangkaian.
                    const offX = () => (isCenter || isNarrow.matches) ? 0 : (fromLeft ? -1 : 1);
                    const offY = () => (isCenter || isNarrow.matches) ? 1 : 0;

                    const step = el.querySelector(".story__step");
                    const chars = el.querySelectorAll(".char");
                    const desc = el.querySelector(".story__desc");
                    const actions = el.querySelector(".story__actions");

                    // Scene pertama sudah dimunculkan oleh animasi pembuka.
                    if (i > 0) {
                        tl.set(el, { autoAlpha: 1 }, at)
                          // Kicker melebar dari rapat.
                          .fromTo(step,
                              { autoAlpha: 0, letterSpacing: "0.02em", x: () => 30 * offX(), y: () => 20 * offY() },
                              { autoAlpha: 1, letterSpacing: "0.26em", x: 0, y: 0,
                                duration: SPAN * 0.22, ease: "power3.out" }, at)
                          // Judul: huruf terbit satu per satu, buram → tajam.
                          .fromTo(chars, charFrom(),
                              charTo({ duration: SPAN * 0.26, stagger: SPAN * 0.0075, ease: "back.out(1.6)" }),
                              at + SPAN * 0.05)
                          // Deskripsi menyusul, blur ke tajam.
                          .fromTo(desc, descFrom(),
                              descTo({ duration: SPAN * 0.24, ease: "power3.out" }), at + SPAN * 0.16);

                        // Penutup mengembang halus dari 0.94 → 1 sebagai penekanan akhir.
                        if (isCenter) {
                            tl.fromTo(el, { scale: 0.94 },
                                { scale: 1, duration: SPAN * 0.42, ease: "power3.out" }, at);
                        }

                        if (actions) {
                            tl.fromTo(actions.children, { y: 20, autoAlpha: 0 },
                                { y: 0, autoAlpha: 1, stagger: SPAN * 0.05, duration: SPAN * 0.2, ease: "power3.out" }, at + SPAN * 0.22);
                        }
                    }

                    // Shade mengikuti posisi teks: kiri, kanan, atau terpusat.
                    const shadeAktif = isCenter ? shadeCenter : (fromLeft ? shadeLeft : shadeRight);
                    const shadeLain = [shadeLeft, shadeRight, shadeCenter].filter((s) => s !== shadeAktif);

                    tl.to(shadeAktif, { opacity: 1, duration: SPAN * 0.24 }, at)
                      .to(shadeLain, { opacity: 0, duration: SPAN * 0.24 }, at);

                    // Keluar: huruf berjatuhan dulu, lalu seluruh scene menepi.
                    if (i < stories.length - 1) {
                        tl.to(chars, {
                            yPercent: -95,
                            rotateX: 62,
                            autoAlpha: 0,
                            filter: "blur(5px)",
                            duration: SPAN * 0.2,
                            stagger: SPAN * 0.004,
                            ease: "power2.in"
                        }, out)
                          .to(desc, { y: -18, autoAlpha: 0, filter: "blur(6px)", duration: SPAN * 0.2, ease: "power2.in" }, out)
                          .to(el, {
                              x: () => -60 * offX(),
                              y: () => -30 * offY(),
                              autoAlpha: 0,
                              duration: SPAN * 0.3,
                              ease: "power2.in"
                          }, out + SPAN * 0.04);
                    }
                });

                tl.to({}, { duration: 0 }, 100);
                return tl;
            }

            function setupNavbar() {
                let scheduled = false;
                window.addEventListener("scroll", () => {
                    if (!experienceReady || scheduled) return;
                    scheduled = true;
                    requestAnimationFrame(() => {
                        scheduled = false;
                        if (window.scrollY > 10) nav.classList.add("is-scrolled");
                    });
                }, { passive: true });
            }

            function initStaticFallback() {
                body.classList.remove("is-loading");
                stories.forEach((el) => {
                    el.style.opacity = "1";
                    el.style.pointerEvents = "auto";
                });
                if (scrollCue) scrollCue.style.display = "none";
            }

            function initExperience() {
                if (experienceReady) return;
                experienceReady = true;

                setupNavbar();

                if (reducedMotion.matches || !window.gsap || !window.ScrollTrigger) {
                    initStaticFallback();
                    return;
                }

                gsap.registerPlugin(ScrollTrigger);
                // Address bar HP yang muncul/hilang memicu resize; abaikan agar
                // pin tidak "melompat" saat pengguna menggulir di perangkat sentuh.
                ScrollTrigger.config({ ignoreMobileResize: true });

                // Judul dipecah per huruf sebelum timeline dibangun, supaya
                // GSAP langsung mendapat elemen .char yang benar.
                stories.forEach((el) => splitChars(el.querySelector(".story__title")));

                setupLenis();
                runOpening();
                setupMouseParallax();
                buildJourney();
                setupIdleBreathing();

                const refresh = () => ScrollTrigger.refresh();
                if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(refresh).catch(refresh);
                window.addEventListener("load", refresh, { once: true });
                window.addEventListener("orientationchange", () => window.setTimeout(refresh, 250), { passive: true });
                doc.querySelectorAll(".scene-image").forEach((img) => {
                    if (!img.complete) img.addEventListener("load", refresh, { once: true });
                });
                window.setTimeout(refresh, 400);
            }

            startLoader();
        })();
    </script>
</body>
</html>
