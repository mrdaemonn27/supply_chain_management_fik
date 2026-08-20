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

$login_url  = base_url('index.php/auth');
$signup_url = base_url('index.php/auth/signup');
$faq_items  = isset($faq_items) && is_array($faq_items) ? array_values($faq_items) : array();

// Satu-satunya aset tiga dimensi: kamera yang bodi dan lensanya terpisah,
// sehingga keduanya bisa dianimasikan sendiri-sendiri.
$model_camera  = base_url('assets/models/camera-parts.glb');
// Versi WebP dari hero-campus-night.png. Foto ini dipaku ke viewport dan
// digambar ulang di tiap frame gulir, jadi berkas 2,2 MB versi PNG-nya terlalu
// berat untuk dipakai langsung. Berkas aslinya tetap ada di folder yang sama.
$img_night     = base_url('assets/images/hero-campus-night.webp');
$img_testimonials = base_url('assets/logo/BG-FIK-VSCO.jpg');
$video_hero    = base_url('assets/uploads/videos/landing-cinematic.mp4');
$video_fallback = base_url('assets/uploads/videos/scm_fik.mp4');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>Sistem Supply Chain Management FIK - Telkom University</title>
    <meta name="description" content="Platform digital terintegrasi untuk pengajuan, persetujuan, peminjaman, dan pengembalian aset Fakultas Industri Kreatif Telkom University.">

    <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.fontshare.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            /* Empat warna saja. Sisanya turunan opasitas dari hitam. */
            --ink: #111111;
            --white: #ffffff;
            --gray: #6b7280;
            --acc: #f97316;
            --acc-dk: #ea580c;

            --surface: #ffffff;
            --surface-2: #fafafa;
            --line: rgba(17, 17, 17, .08);
            --line-2: rgba(17, 17, 17, .15);

            /* Bayangan berlapis tipis — kedalaman yang terasa, bukan yang terlihat. */
            --sh-sm: 0 1px 2px rgba(17, 17, 17, .04), 0 4px 12px rgba(17, 17, 17, .04);
            --sh-md: 0 2px 4px rgba(17, 17, 17, .03), 0 14px 34px rgba(17, 17, 17, .07);
            --sh-lg: 0 4px 8px rgba(17, 17, 17, .03), 0 28px 70px rgba(17, 17, 17, .09);

            /* Skala tipografi: setiap tingkat punya jarak yang jelas dari tetangganya. */
            --fs-eyebrow: .75rem;
            --fs-h1: clamp(2.75rem, 6vw, 6rem);
            --fs-h2: clamp(2.25rem, 4.4vw, 4rem);
            --fs-h3: clamp(1.35rem, 1.9vw, 1.75rem);
            --fs-sub: clamp(1.15rem, 1.45vw, 1.5rem);
            --fs-body: clamp(1rem, 1.05vw, 1.125rem);

            --nav-h: 76px;
            --gutter: clamp(22px, 4.6vw, 88px);
            --sec-y: clamp(112px, 16vh, 224px);
            --ease: cubic-bezier(.22, 1, .36, 1);
            --r: 20px;
            --r-lg: 28px;

            --font: "Satoshi", "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { background: var(--white); }

        body {
            margin: 0;
            overflow-x: hidden;
            background: var(--white);
            color: var(--gray);
            font-family: var(--font);
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body.is-loading { overflow: hidden; }
        img { display: block; max-width: 100%; }
        a, button { color: inherit; text-decoration: none; font-family: inherit; }
        :focus-visible { outline: 2px solid var(--acc); outline-offset: 4px; }

        /* ============ LOADER ============ */
        .ld {
            position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center;
            overflow: hidden; background: radial-gradient(circle at 50% 42%, rgba(249, 115, 22, .08), transparent 34%), #f7f3ed;
        }
        .ld.is-hidden { visibility: hidden; pointer-events: none; }

        /* Cahaya oranye yang bernapas sangat pelan di balik konten. */
        .ld__glow { position: absolute; display: block; border-radius: 50%; pointer-events: none; will-change: transform, opacity; }
        .ld__glow--a { top: -26%; left: 8%; width: min(72vw, 900px); aspect-ratio: 1; background: radial-gradient(circle, rgba(249, 115, 22, .09), transparent 66%); filter: blur(52px); }
        .ld__glow--b { right: -14%; bottom: -30%; width: min(64vw, 780px); aspect-ratio: 1; background: radial-gradient(circle, rgba(249, 115, 22, .06), transparent 68%); filter: blur(62px); }

        /* Butiran debu tipis; elemennya dibuat dari JS agar markup tetap bersih. */
        .ld__dust { display: none; }

        .ld__in { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; width: min(320px, calc(100vw - 48px)); text-align: center; will-change: transform, opacity, filter; }
        .ld__hourglass { display: grid; place-items: center; width: 104px; height: 128px; margin-bottom: 26px; }
        .ld__hourglass svg { display: block; width: 100%; height: 100%; overflow: visible; }
        .ld__glass { fill: rgba(255, 255, 255, .34); stroke: rgba(26, 29, 33, .72); stroke-width: 2.4; stroke-linejoin: round; }
        .ld__frame { fill: none; stroke: #24272b; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }
        .ld__sand { fill: #f97316; filter: drop-shadow(0 0 3px rgba(249, 115, 22, .34)); transform-box: fill-box; transform-origin: center; clip-path: url(#ld-hourglass-clip); }
        .ld__sand--top { animation: ld-sand-top 3.8s ease-in-out infinite; }
        .ld__sand--bottom { animation: ld-sand-bottom 3.8s ease-in-out infinite; }
        .ld__stream { stroke: #f97316; stroke-width: 2.4; stroke-linecap: round; animation: ld-sand-stream 3.8s ease-in-out infinite; }
        .ld__lb { margin: 0; color: #24272b; font-size: .73rem; font-weight: 700; letter-spacing: .16em; line-height: 1.4; text-transform: uppercase; }
        .ld__num { display: flex; align-items: baseline; gap: 4px; margin-bottom: 26px; color: var(--ink); font-size: clamp(3.75rem, 11vw, 6.5rem); font-weight: 800; letter-spacing: -.045em; line-height: .88; font-variant-numeric: tabular-nums; }
        .ld__num i { font-style: normal; font-size: .34em; font-weight: 700; color: var(--acc); letter-spacing: 0; }

        .ld__track { position: relative; height: 2px; border-radius: 2px; background: rgba(17, 17, 17, .07); }
        .ld__fill { position: absolute; inset: 0 auto 0 0; width: 0; border-radius: 2px; background: linear-gradient(90deg, #fdba74, var(--acc)); box-shadow: 0 0 10px rgba(249, 115, 22, .55), 0 0 22px rgba(249, 115, 22, .25); }
        /* Titik terang di ujung isian — memberi kesan garisnya sedang ditarik. */
        .ld__dot { position: absolute; top: 50%; left: 0; width: 7px; height: 7px; margin: -3.5px 0 0 -3.5px; border-radius: 50%; background: var(--acc); box-shadow: 0 0 10px rgba(249, 115, 22, .9), 0 0 20px rgba(249, 115, 22, .5); }
        .ld__st { margin: 10px 0 0; color: #7a7f86; font-size: .82rem; font-weight: 500; }

        @keyframes ld-sand-top {
            0%, 10% { transform: scaleY(1); opacity: 1; }
            72%, 84% { transform: scaleY(.18); opacity: .9; }
            100% { transform: scaleY(1); opacity: 1; }
        }
        @keyframes ld-sand-bottom {
            0%, 10% { transform: scaleY(.28); opacity: .82; }
            72%, 84% { transform: scaleY(1); opacity: 1; }
            100% { transform: scaleY(.28); opacity: .82; }
        }
        @keyframes ld-sand-stream {
            0%, 9%, 86%, 100% { opacity: 0; stroke-dasharray: 1 7; }
            16%, 76% { opacity: 1; stroke-dasharray: 8 2; }
        }

        .prog { position: fixed; inset: 0 0 auto; z-index: 400; height: 2px; transform: scaleX(0); transform-origin: 0 50%; background: var(--acc); }

        /* ============ AKSEN LATAR ============
           Radial oranye tipis (5–8%) sebagai penghangat ruang, bukan sebagai
           elemen yang menarik perhatian. */
        .glow { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .glow span { position: absolute; display: block; border-radius: 50%; will-change: transform; }
        .glow__a { top: -20%; right: -6%; width: min(62vw, 820px); aspect-ratio: 1; background: radial-gradient(circle, rgba(249, 115, 22, .08), transparent 68%); }
        .glow__b { bottom: -24%; left: -10%; width: min(56vw, 720px); aspect-ratio: 1; background: radial-gradient(circle, rgba(249, 115, 22, .055), transparent 70%); }
        .glow__c { top: 42%; left: 44%; width: min(42vw, 520px); aspect-ratio: 1; background: radial-gradient(circle, rgba(249, 115, 22, .05), transparent 72%); }

        /* ============ NAV ============ */
        .nav { position: fixed; inset: 0 0 auto; z-index: 300; display: flex; align-items: center; min-height: var(--nav-h); padding-inline: var(--gutter); transition: background .4s ease, box-shadow .4s ease, backdrop-filter .4s ease; }
        .nav.is-scrolled { background: rgba(255, 255, 255, .8); box-shadow: 0 1px 0 var(--line); -webkit-backdrop-filter: blur(20px); backdrop-filter: blur(20px); }
        .nav__in { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; max-width: 1400px; margin-inline: auto; }
        .nav__brand img { height: 32px; width: auto; }
        .nav__act { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        /* ============ TOMBOL ============ */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            min-height: 48px; padding: 13px 26px; border: 1px solid transparent; border-radius: 999px;
            font-size: .9375rem; font-weight: 600; letter-spacing: -.005em; white-space: nowrap; cursor: pointer;
            transition: background .28s var(--ease), border-color .28s, color .28s, box-shadow .32s var(--ease), transform .32s var(--ease);
        }
        .btn--dark { background: var(--ink); color: var(--white); box-shadow: var(--sh-sm); }
        .btn--dark:hover { transform: translateY(-2px); background: #232323; box-shadow: var(--sh-md); }
        .btn--acc { background: var(--acc); color: var(--white); box-shadow: 0 4px 14px rgba(249, 115, 22, .26); }
        .btn--acc:hover { transform: translateY(-2px); background: var(--acc-dk); box-shadow: 0 10px 28px rgba(249, 115, 22, .34); }
        .btn--ghost { border-color: var(--line-2); color: var(--ink); background: var(--white); }
        .btn--ghost:hover { border-color: var(--ink); transform: translateY(-2px); box-shadow: var(--sh-sm); }
        .btn--sm { min-height: 42px; padding: 10px 20px; font-size: .875rem; }
        .btn__ar { transition: transform .3s var(--ease); }
        .btn:hover .btn__ar { transform: translateX(4px); }

        .ico { display: grid; place-items: center; width: 48px; height: 48px; padding: 0; border: 1px solid var(--line-2); border-radius: 50%; background: var(--white); color: var(--ink); font-size: .95rem; cursor: pointer; transition: border-color .28s, color .28s, transform .32s var(--ease), box-shadow .32s; }
        .ico:hover { border-color: var(--acc); color: var(--acc); transform: translateY(-2px); box-shadow: var(--sh-sm); }

        /* ============ TIPOGRAFI ============
           Satu keluarga huruf untuk semuanya. Kata yang ditonjolkan cukup
           dibedakan lewat warna dan bobot, bukan lewat jenis huruf lain. */
        .eyebrow {
            display: inline-flex; align-items: center; gap: 10px; margin: 0 0 22px;
            color: var(--gray); font-size: var(--fs-eyebrow); font-weight: 600;
            letter-spacing: .14em; text-transform: uppercase;
        }
        .eyebrow::before { content: ""; width: 22px; height: 2px; border-radius: 2px; background: var(--acc); }
        .is-center .eyebrow::before { display: none; }

        .h1, .h2 {
            margin: 0; color: var(--ink); font-weight: 800;
            letter-spacing: -.035em; text-wrap: balance;
        }
        .h1 { font-size: var(--fs-h1); line-height: 1.02; }
        .h2 { font-size: var(--fs-h2); line-height: 1.06; }
        .h3 { margin: 0; color: var(--ink); font-size: var(--fs-h3); font-weight: 700; letter-spacing: -.02em; line-height: 1.3; }
        .hl { color: var(--acc); font-weight: 800; }

        .sub { max-width: 30ch; margin: 26px 0 0; color: var(--gray); font-size: var(--fs-sub); font-weight: 450; line-height: 1.5; letter-spacing: -.012em; }
        .body { max-width: 46ch; margin: 22px 0 0; color: var(--gray); font-size: var(--fs-body); font-weight: 400; line-height: 1.7; }
        .is-center .sub, .is-center .body { margin-inline: auto; }

        .acts { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 44px; }
        .is-center .acts { justify-content: center; }
        .note { margin: 20px 0 0; color: var(--gray); font-size: .8125rem; }

        .line { display: block; overflow: clip; }
        .line > span { display: block; }
        .w { display: inline-block; white-space: nowrap; will-change: transform; }

        /* ============ PANGGUNG 3D ============ */
        .stage { position: fixed; inset: 0; z-index: 2; pointer-events: none; }
        .stage canvas { position: absolute; inset: 0; display: block; width: 100% !important; height: 100% !important; }

        /* Bayangan kontak dibuat di DOM: di atas putih inilah yang membuat kamera
           terbaca melayang, bukan tertempel. */
        .stage__sh {
            position: absolute; top: 0; left: 0; width: 440px; height: 120px; margin: -60px 0 0 -220px;
            border-radius: 50%; opacity: 0; will-change: transform;
            background: radial-gradient(closest-side, rgba(17, 17, 17, .2), rgba(17, 17, 17, .07) 48%, transparent 76%);
            filter: blur(18px);
        }

        /* ============ ADEGAN — ZIG-ZAG ============
           Teks dan model 3D selalu menempati sisi yang berlawanan, dan sisinya
           berganti tiap adegan. Karena keduanya tidak lagi bertumpuk, judul bisa
           memakai hitam pekat tanpa berebut ruang dengan kamera. */
        main { position: relative; }
        .scenes { position: relative; }
        .scene { position: relative; display: flex; align-items: center; min-height: 100svh; padding: calc(var(--nav-h) + 7vh) var(--gutter) 12vh; }
        .scene__in { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: clamp(24px, 3vw, 48px); align-items: center; width: 100%; max-width: 1400px; margin-inline: auto; }
        .scene__copy { position: relative; z-index: 3; grid-column: 1 / span 5; }
        .scene--flip .scene__copy { grid-column: 8 / span 5; }

        /* ============ BAGIAN PADAT ============ */
        .solid { position: relative; z-index: 5; background: var(--white); }
        .sec { position: relative; padding: var(--sec-y) var(--gutter); }
        .sec__in { width: 100%; max-width: 1400px; margin-inline: auto; }
        .is-center { text-align: center; }

        .split { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: clamp(32px, 4vw, 72px); align-items: center; }
        .split__copy { grid-column: 1 / span 5; }
        .split__media { grid-column: 7 / span 6; }
        .split--flip .split__copy { grid-column: 8 / span 5; }
        .split--flip .split__media { grid-column: 1 / span 6; }


        /* ============ LABORATORIUM ============
           Jumlah kartunya mengikuti isi basis data, jadi kolomnya dibiarkan
           menyesuaikan sendiri alih-alih dipatok angka tetap. */
        .labs { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 72px; text-align: left; }
        .lab { display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--line); border-radius: var(--r-lg); background: var(--white); box-shadow: var(--sh-sm); transition: transform .4s var(--ease), box-shadow .4s, border-color .4s; }
        .lab:hover { transform: translateY(-5px); border-color: rgba(249, 115, 22, .3); box-shadow: var(--sh-md); }
        .lab:hover .lab__ph img { transform: scale(1.05); }

        .lab__ph { position: relative; overflow: hidden; aspect-ratio: 4 / 3; background: var(--surface-2); }
        .lab__ph img { width: 100%; height: 100%; object-fit: cover; transition: transform .7s var(--ease); }
        /* Ruangan yang belum punya foto tetap mendapat blok visual seukuran sama,
           jadi tinggi kartu dalam satu baris tidak jadi belang. */
        .lab__ph--none { display: grid; place-items: center; color: var(--acc); font-size: 2rem; background: rgba(249, 115, 22, .07); }

        .lab__b { display: flex; flex-direction: column; flex: 1; padding: 28px; }
        .lab__d { flex: 1; margin: 12px 0 0; color: var(--gray); font-size: .9375rem; line-height: 1.65; }
        .lab__n { margin: 22px 0 0; padding-top: 18px; border-top: 1px solid var(--line); color: var(--ink); font-size: .875rem; font-weight: 600; }

        /* ============ SECTION BERLATAR FOTO ============
           Satu-satunya section yang memakai gambar. Foto kampus diredam gradasi
           oranye pekat: fotonya masih terbaca sebagai tempat, tapi warnanya tidak
           keluar dari palet dan teks putih di atasnya tetap nyaman dibaca. */
        /* Foto dipaku ke viewport, bukan ke section. Saat halaman digulir, bidang
           section-lah yang bergerak melewati foto yang diam, sehingga bagian
           gedung yang terlihat ikut berganti — persis perilaku pada situs rujukan.
           Ini fitur bawaan peramban, bukan animasi JS, jadi arahnya selalu
           mengikuti gulir tanpa perlu disinkronkan. */
        .sec--photo {
            position: relative; overflow: hidden; color: var(--white);
            background-image: url("<?= $img_testimonials; ?>");
            background-attachment: fixed;
            background-position: center center;
            background-size: cover;
            background-repeat: no-repeat;
        }
        .sec--photo > .sec__in { position: relative; z-index: 2; }
        .sec__tint {
            position: absolute; inset: 0; z-index: 1; display: block;
            background:
                radial-gradient(70% 62% at 50% 38%, rgba(234, 88, 12, .26), transparent 74%),
                linear-gradient(180deg, rgba(84, 29, 10, .95) 0%, rgba(148, 50, 17, .91) 46%, rgba(88, 31, 11, .96) 100%);
        }

        .sec--photo .eyebrow { color: rgba(255, 255, 255, .78); }
        .sec--photo .eyebrow::before { background: #fed7aa; }
        .sec--photo .h2 { color: var(--white); }
        /* Oranye di atas oranye tidak terbaca; aksennya digeser ke amber muda. */
        .sec--photo .hl { color: #fed7aa; }
        .sec--photo .sub { color: rgba(255, 255, 255, .82); }

        .sec--photo .rev {
            border-color: rgba(255, 255, 255, .22); background: rgba(255, 255, 255, .11);
            box-shadow: 0 18px 46px rgba(60, 20, 0, .22);
            -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
        }
        .sec--photo .rev:hover { border-color: rgba(255, 255, 255, .38); background: rgba(255, 255, 255, .17); box-shadow: 0 26px 60px rgba(60, 20, 0, .3); }
        .sec--photo .rev__st { color: #fed7aa; }
        .sec--photo .rev__tx, .sec--photo .rev__by { color: var(--white); }
        .sec--photo .rev__ro { color: rgba(255, 255, 255, .68); }

        /* ============ TESTIMONI ============ */
        .revs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 72px; text-align: left; }
        .rev { display: flex; flex-direction: column; padding: 34px; border: 1px solid var(--line); border-radius: var(--r); background: var(--white); box-shadow: var(--sh-sm); transition: transform .4s var(--ease), box-shadow .4s; }
        .rev:hover { transform: translateY(-4px); box-shadow: var(--sh-md); }
        .rev__st { color: var(--acc); font-size: .875rem; letter-spacing: .1em; }
        .rev__tx { flex: 1; margin: 20px 0 26px; color: var(--ink); font-size: 1.0625rem; font-weight: 450; line-height: 1.68; letter-spacing: -.01em; }
        .rev__by { color: var(--ink); font-size: .9375rem; font-weight: 600; }
        .rev__ro { margin-top: 4px; color: var(--gray); font-size: .875rem; }

        /* ============ FOOTER ============ */
        .foot { position: relative; z-index: 5; padding: clamp(80px, 11vh, 140px) var(--gutter) 0; color: #eef1f3; background: #272b30; border-top: 3px solid var(--acc); }
        .foot__in { display: grid; grid-template-columns: minmax(0, 1.45fr) repeat(2, minmax(0, 1fr)); gap: clamp(32px, 4vw, 72px); max-width: 1400px; margin-inline: auto; }
        .foot__logo { width: auto; height: 40px; margin: 0 0 26px; }
        .foot__lede { max-width: 34ch; margin: 0; color: #b8c0c7; font-size: .9375rem; line-height: 1.7; }
        .foot__ttl { margin: 0 0 20px; color: #f5f6f7; font-size: var(--fs-eyebrow); font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .foot__ln { display: grid; gap: 14px; }
        .foot__ln a { color: #c7cdd2; font-size: .9375rem; font-weight: 500; transition: color .28s; }
        .foot__ln a:hover { color: #ff7548; }
        .foot__bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; max-width: 1400px; margin: clamp(56px, 8vh, 96px) auto 0; padding-top: 28px; border-top: 1px solid rgba(255, 255, 255, .11); color: #9fa8b0; font-size: .875rem; }


        /* ============ TAMBAHAN ============ */
        .cur { position: fixed; z-index: 500; top: 0; left: 0; width: 36px; height: 36px; margin: -18px 0 0 -18px; border: 1px solid var(--line-2); border-radius: 50%; pointer-events: none; opacity: 0; will-change: transform; }
        .top { position: fixed; right: var(--gutter); bottom: 28px; z-index: 300; opacity: 0; box-shadow: var(--sh-md); }

        .nojs { position: fixed; right: 18px; bottom: 18px; z-index: 10000; max-width: 340px; padding: 14px 18px; border-radius: 14px; background: var(--ink); color: #fff; font-size: .875rem; }
        .nojs a { color: var(--acc); }

        /* ============ RESPONSIF ============ */
        @media (max-width: 1023.98px) {
            :root { --nav-h: 64px; --r-lg: 22px; }
            .revs { grid-template-columns: 1fr; }
            .foot__in { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            /* Satu kolom: teks di atas, kamera 3D mengisi paruh bawah layar.
               Padding atas dipangkas karena di sini blok teks hampir setinggi
               layar, dan sisa ruangnya harus cukup untuk modelnya. */
            .scene { align-items: flex-start; padding: calc(var(--nav-h) + 3vh) var(--gutter) 48vh; }
            .scene__copy, .scene--flip .scene__copy { grid-column: 1 / -1; }
            .split__copy, .split__media,
            .split--flip .split__copy, .split--flip .split__media { grid-column: 1 / -1; }
            .split__media { margin-top: 8px; }
            .sub, .body { max-width: 52ch; }
        }

        @media (max-width: 767.98px) {
            :root { --sec-y: clamp(84px, 12vh, 132px); }
            .foot__in { grid-template-columns: 1fr; }
            .acts { width: 100%; }
            .acts .btn { flex: 1 1 auto; }
            .scene { padding-bottom: 44vh; }
            .cur { display: none; }
            .nav__brand img { height: 26px; }
            .lab__ph { aspect-ratio: 16 / 10; }
        }

        /* iOS mengabaikan background-attachment: fixed dan menggambarnya dengan
           ukuran yang meleset. Di perangkat sentuh fotonya dikunci ke section
           saja — diam, tapi utuh. */
        @media (hover: none) {
            .sec--photo { background-attachment: scroll; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
            .prog, .cur { display: none; }
            .line { overflow: visible; }
        }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/css/filter-autocomplete.css') ?>?v=<?= @filemtime(FCPATH . 'assets/css/filter-autocomplete.css') ?>">
    <script defer src="<?= base_url('assets/js/filter-autocomplete.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/filter-autocomplete.js') ?>"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/landing-particles.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/landing-particles.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing-video.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/landing-video.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/faq-assistant.css'); ?>?v=<?= @filemtime(FCPATH . 'assets/css/faq-assistant.css'); ?>">
</head>
<body class="is-loading">
    <noscript>
        <div class="nojs">Halaman ini memakai animasi berbasis JavaScript. Untuk masuk langsung, gunakan <a href="<?= $login_url; ?>">halaman login</a>.</div>
    </noscript>

    <div class="ld" id="loader" aria-hidden="true">
        <span class="ld__glow ld__glow--a" data-ldglow></span>
        <span class="ld__glow ld__glow--b" data-ldglow></span>
        <div class="ld__in" id="loaderIn">
            <div class="ld__hourglass" aria-hidden="true">
                <svg viewBox="0 0 100 120" role="presentation">
                    <clipPath id="ld-hourglass-clip">
                        <path d="M29 15h42c0 17-8 27-21 40 13 13 21 23 21 40H29c0-17 8-27 21-40-13-13-21-23-21-40Z"></path>
                    </clipPath>
                    <path class="ld__glass" d="M27 13h46c0 18-8 29-23 42 15 13 23 24 23 42H27c0-18 8-29 23-42C35 42 27 31 27 13Z"></path>
                    <path class="ld__sand ld__sand--top" d="M33 18h34c-2 11-7 19-17 28-10-9-15-17-17-28Z"></path>
                    <path class="ld__sand ld__sand--bottom" d="M33 98h34c-2-11-7-19-17-28-10 9-15 17-17 28Z"></path>
                    <path class="ld__stream" d="M50 48v24"></path>
                    <path class="ld__frame" d="M23 11h54M23 109h54"></path>
                </svg>
            </div>
            <p class="ld__lb">Supply Chain Management FIK</p>
            <p class="ld__st" id="loaderStat">Memuat pengalaman...</p>
        </div>
    </div>

    <div class="prog" id="prog" aria-hidden="true"></div>

    <div class="glow" id="glow" aria-hidden="true">
        <span class="glow__a"></span>
        <span class="glow__b"></span>
        <span class="glow__c"></span>
    </div>

    <nav class="nav" id="nav" aria-label="Navigasi utama">
        <div class="nav__in">
            <a class="nav__brand" href="<?= base_url(); ?>" aria-label="SCM FIK — Beranda">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo Fakultas Industri Kreatif">
            </a>
        </div>
    </nav>

    <!-- Kanvas 3D: dipaku di viewport, melintasi seluruh rangkaian adegan. -->
    <div class="stage" id="stage" data-model="<?= $model_camera; ?>"
         role="img" aria-label="Kamera inventaris Fakultas Industri Kreatif dalam tiga dimensi">
        <span class="stage__sh" id="stageShadow" aria-hidden="true"></span>
    </div>

    <main>
        <div class="scenes" id="scenes">
            <!-- Hero video frontend. Kamera 3D untuk alur 01-03 tetap dimulai setelah banner ini. -->
            <section class="scene video-hero" id="beranda" data-video-hero aria-labelledby="videoHeroTitle">
                <div class="video-hero__media" aria-hidden="true">
                    <div class="video-hero__parallax">
                        <video class="video-hero__video" autoplay muted loop playsinline preload="metadata" poster="<?= $img_night; ?>" data-fallback-src="<?= $video_fallback; ?>">
                            <source src="<?= $video_hero; ?>" type="video/mp4">
                        </video>
                    </div>
                    <span class="video-hero__mood video-hero__mood--base"></span>
                    <span class="video-hero__mood video-hero__mood--next"></span>
                    <span class="video-hero__vignette"></span>
                    <span class="video-hero__pulse"></span>
                    <span class="video-hero__mist"></span>
                </div>

                <div class="video-hero__outline" aria-hidden="true"></div>

                <div class="video-hero__ui">
                    <p class="video-hero__kicker">Selamat Datang di</p>
                    <h1 class="video-hero__title" id="videoHeroTitle">
                        <span class="video-hero__title-line"><span class="video-hero__title-line-inner">SISTEM MANAJEMEN</span></span>
                        <span class="video-hero__title-line"><span class="video-hero__title-line-inner video-hero__title-accent">ASET FIK</span></span>
                    </h1>
                    <p class="video-hero__lead">Semua aset fakultas, pengajuan, persetujuan, peminjaman, dan pengembalian dalam satu alur yang rapi.</p>
                    <div class="video-hero__actions">
                        <a class="btn btn--hero-primary" href="<?= $login_url; ?>">Masuk Sistem <i class="bi bi-arrow-right btn__ar" aria-hidden="true"></i></a>
                        <a class="btn btn--hero-secondary" href="<?= $signup_url; ?>">Buat Akun</a>
                    </div>
                </div>
                <span class="video-hero__sr" data-palette-status aria-live="polite">Mood video biru aktif</span>
            </section>

            <!-- 02 — model kiri, teks kanan -->
            <section class="scene scene--flip process-scene process-scene--first">
                <div class="scene__in">
                    <div class="scene__copy">
                        <p class="eyebrow" data-fade>01 — Pengajuan</p>
                        <h2 class="h2" data-fade>Ajukan dalam <span class="hl">Menit</span>.</h2>
                        <p class="sub" data-fade>Pilih unit, tentukan tanggal, kirim.</p>
                        <p class="body" data-fade>
                            Tidak ada lagi antre tanda tangan di atas kertas. Formulir dan
                            lampiran menyatu dalam satu pengajuan yang bisa dilacak statusnya.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 03 — teks kiri, model kanan -->
            <section class="scene process-scene process-scene--approval">
                <div class="scene__in">
                    <div class="scene__copy">
                        <p class="eyebrow" data-fade>02 — Persetujuan</p>
                        <h2 class="h2" data-fade>Disetujui <span class="hl">Berjenjang</span>.</h2>
                        <p class="sub" data-fade>Kaprodi lalu Kaur, otomatis dan berurutan.</p>
                        <p class="body" data-fade>
                            Pengajuan diteruskan sendiri ke peninjau berikutnya. Setiap keputusan
                            tercatat lengkap dengan waktu dan penanggung jawabnya.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 04 — model kiri, teks kanan -->
            <section class="scene scene--flip process-scene process-scene--return process-scene--last">
                <div class="scene__in">
                    <div class="scene__copy">
                        <p class="eyebrow" data-fade>03 — Pengembalian</p>
                        <h2 class="h2" data-fade>Kembali, Tercatat <span class="hl">Utuh</span>.</h2>
                        <p class="sub" data-fade>Kondisi unit diperiksa dan stok langsung diperbarui.</p>
                        <p class="body" data-fade>
                            Keterlambatan maupun kerusakan terbaca pada status yang sama, jadi
                            tidak ada unit yang hilang dari catatan.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <div class="solid">
            <!-- 05 LABORATORIUM — data diambil dari tabel `ruangan` -->
            <section class="sec is-center labs-section" id="fasilitas">
                <div class="sec__in">
                    <p class="eyebrow" data-fade>Laboratorium Fakultas</p>
                    <h2 class="h2" data-fade>Fasilitas laboratorium di <span class="hl">Industri Kreatif</span>.</h2>
                    <p class="sub" data-fade>
                        Setiap ruangan beserta aset di dalamnya tercatat dan dapat dipinjam
                        melalui alur yang sama.
                    </p>

                    <?php if (!empty($ruangan)): ?>
                    <div class="lab-coverflow" data-lab-coverflow data-fade aria-label="Carousel fasilitas laboratorium" aria-roledescription="carousel">
                        <div class="lab-coverflow__viewport" data-lab-viewport>
                            <div class="labs lab-coverflow__track" data-lab-track>
                        <?php foreach ($ruangan as $r): ?>
                        <?php
                            // Foto diunggah lewat dashboard admin dan hanya nama
                            // filenya yang disimpan di basis data.
                            $foto_ruangan = !empty($r->foto)
                                ? base_url('assets/uploads/ruangan/'.rawurlencode($r->foto))
                                : null;
                        ?>
                        <article class="lab" data-lab-card>
                            <?php if ($foto_ruangan): ?>
                            <div class="lab__ph">
                                <img src="<?= $foto_ruangan; ?>" alt="Foto <?= html_escape($r->nama_ruangan); ?>" loading="lazy" decoding="async">
                            </div>
                            <?php else: ?>
                            <div class="lab__ph lab__ph--none" aria-hidden="true">
                                <i class="bi bi-<?= html_escape($r->icon ? $r->icon : 'door-open-fill'); ?>"></i>
                            </div>
                            <?php endif; ?>
                            <div class="lab__b">
                                <h3 class="h3"><?= html_escape($r->nama_ruangan); ?></h3>
                                <?php if (!empty($r->deskripsi)): ?>
                                <p class="lab__d"><?= html_escape($r->deskripsi); ?></p>
                                <?php endif; ?>
                                <p class="lab__n"><?= (int) $r->jumlah_aset; ?> unit aset terdaftar</p>
                            </div>
                        </article>
                        <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="lab-coverflow__controls">
                            <button class="lab-coverflow__nav" type="button" data-lab-prev aria-label="Laboratorium sebelumnya">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            </button>
                            <div class="lab-coverflow__pagination" data-lab-pagination aria-label="Pilih laboratorium"></div>
                            <button class="lab-coverflow__nav" type="button" data-lab-next aria-label="Laboratorium berikutnya">
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 06 TESTIMONI -->
            <section class="sec is-center sec--photo">
                <span class="sec__tint" aria-hidden="true"></span>
                <div class="sec__in">
                    <p class="eyebrow" data-fade>Suara Pengguna</p>
                    <h2 class="h2" data-fade>Bagaimana sistem ini <span class="hl">membantu</span>?</h2>
                    <p class="sub" data-fade>Dari mahasiswa, dosen, sampai laboran yang mengelola alat setiap hari.</p>

                    <div class="revs" data-stagger>
                        <article class="rev">
                            <div class="rev__st">★★★★★</div>
                            <p class="rev__tx">Dulu saya harus bolak-balik minta tanda tangan sebelum bisa pinjam kamera. Sekarang cukup ajukan dari laptop dan tinggal menunggu notifikasi.</p>
                            <div class="rev__by">Mahasiswa DKV</div>
                            <div class="rev__ro">Peminjam</div>
                        </article>
                        <article class="rev">
                            <div class="rev__st">★★★★★</div>
                            <p class="rev__tx">Sebagai pengampu mata kuliah produksi, saya bisa melihat siapa memakai alat apa dan sampai kapan. Rekapnya jelas tanpa harus bertanya ke laboran.</p>
                            <div class="rev__by">Dosen Produksi</div>
                            <div class="rev__ro">Pengguna</div>
                        </article>
                        <article class="rev">
                            <div class="rev__st">★★★★★</div>
                            <p class="rev__tx">Serah terima lewat QR memangkas banyak salah catat. Kondisi alat saat keluar dan kembali tercatat langsung di sistem.</p>
                            <div class="rev__by">Laboran</div>
                            <div class="rev__ro">Pengelola aset</div>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <div class="solid">
            <!-- 10 FAQ Assistant — data berasal dari tabel faq -->
            <section class="sec">
                <div class="sec__in faq-promo">
                    <div class="faq-promo__copy">
                        <p class="eyebrow" data-fade>FAQ Assistant</p>
                        <h2 class="h2" data-fade>Tanyakan alur SCM, temukan jawaban <span class="hl">seketika</span>.</h2>
                        <p class="sub" data-fade>Pilih pertanyaan atau cari topik tentang pengajuan, persetujuan, QR, dan pengembalian barang.</p>
                        <div class="faq-promo__actions" data-fade>
                            <button class="btn btn--dark" type="button" data-faq-open>
                                Buka FAQ Assistant <i class="bi bi-chat-dots btn__ar" aria-hidden="true"></i>
                            </button>
                            <span class="faq-promo__note"><i class="bi bi-database-check" aria-hidden="true"></i> Jawaban bersumber dari fitur SCM FIK</span>
                        </div>
                    </div>
                    <div class="faq-demo" data-fade>
                        <div class="faq-demo__head">
                            <span class="faq-avatar" aria-hidden="true"><i class="bi bi-stars"></i></span>
                            <div>
                                <p class="faq-demo__title">SCM FIK Assistant</p>
                                <p class="faq-demo__status">Siap membantu</p>
                            </div>
                        </div>
                        <div class="faq-demo__body">
                            <p class="faq-demo__bubble">Halo! Ada yang ingin diketahui tentang peminjaman aset FIK?</p>
                            <div class="faq-demo__questions">
                                <?php foreach (array_slice($faq_items, 0, 3, true) as $faq_index => $faq): ?>
                                    <button class="faq-demo__question" type="button" data-faq-open data-faq-index="<?= (int) $faq_index; ?>">
                                        <?= html_escape($faq['question']); ?>
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <footer class="foot" id="kontak">
        <div class="foot__in">
            <div>
                <img class="foot__logo" src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo Fakultas Industri Kreatif">
                <p class="foot__ttl">Supply Chain Management</p>
                <p class="foot__lede">
                    Sistem pengelolaan aset Fakultas Industri Kreatif,
                    Telkom University.
                </p>
            </div>
            <div>
                <p class="foot__ttl">Halaman</p>
                <div class="foot__ln">
                    <a href="#beranda">Beranda</a>
                    <a href="<?= $login_url; ?>">Masuk</a>
                    <a href="<?= $signup_url; ?>">Daftar</a>
                    <a href="<?= $dashboard_url; ?>">Dashboard</a>
                </div>
            </div>
            <div>
                <p class="foot__ttl">Alur</p>
                <div class="foot__ln">
                    <a href="#beranda">Pengajuan</a>
                    <a href="#beranda">Persetujuan</a>
                    <a href="#beranda">Peminjaman</a>
                    <a href="#beranda">Pengembalian</a>
                </div>
            </div>
        </div>
        <div class="foot__bar">
            <span>&copy; <?= date('Y'); ?> Fakultas Industri Kreatif — Telkom University</span>
            <span>Sistem Supply Chain Management</span>
        </div>
    </footer>

    <button class="faq-launcher" id="faqLauncher" type="button" aria-controls="faqAssistant" aria-expanded="false">
        <span class="faq-launcher__icon" aria-hidden="true"><i class="bi bi-chat-dots-fill"></i></span>
        <span>Tanya SCM FIK</span>
        <span class="faq-launcher__badge" aria-hidden="true"></span>
    </button>

    <section class="faq-assistant" id="faqAssistant" role="dialog" aria-modal="false" aria-labelledby="faqAssistantTitle" aria-hidden="true">
        <header class="faq-assistant__head">
            <span class="faq-avatar" aria-hidden="true"><i class="bi bi-stars"></i></span>
            <div class="faq-assistant__identity">
                <h2 class="faq-assistant__title" id="faqAssistantTitle">SCM FIK Assistant</h2>
                <p class="faq-assistant__status">FAQ layanan aset</p>
            </div>
            <button class="faq-assistant__close" id="faqClose" type="button" aria-label="Tutup FAQ Assistant">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="faq-assistant__chat" id="faqChat" role="log" aria-live="polite" aria-relevant="additions">
            <div class="faq-message faq-message--assistant">
                <span class="faq-message__avatar" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <div class="faq-message__bubble">Halo! Saya membantu menjelaskan fitur dan alur peminjaman SCM FIK. Cari atau pilih salah satu pertanyaan di bawah.</div>
            </div>
        </div>

        <div class="faq-assistant__browser">
            <div class="faq-search">
                <label class="faq-search__sr" for="faqSearch">Cari pertanyaan FAQ</label>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input class="faq-search__input" id="faqSearch" type="search" placeholder="Cari pertanyaan, misalnya QR..." autocomplete="off">
                <button class="faq-search__clear" id="faqSearchClear" type="button" aria-label="Hapus pencarian">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="faq-assistant__label">
                <span>Pertanyaan umum</span>
                <span id="faqResultCount"><?= count($faq_items); ?> pertanyaan</span>
            </div>
            <div class="faq-question-list" id="faqQuestionList">
                <?php foreach ($faq_items as $faq_index => $faq): ?>
                    <button class="faq-question" type="button" data-faq-question data-faq-index="<?= (int) $faq_index; ?>">
                        <span><?= html_escape($faq['question']); ?></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                <?php endforeach; ?>
            </div>
            <p class="faq-empty" id="faqEmpty">Pertanyaan tidak ditemukan. Coba kata kunci lain atau hubungi Laboran melalui dashboard.</p>
            <div class="faq-assistant__label">
                <span class="faq-assistant__data-source">Sumber data: tabel FAQ SCM FIK</span>
            </div>
        </div>
    </section>

    <script type="application/json" id="faqData"><?= json_encode($faq_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

    <button class="ico top" id="top" type="button" aria-label="Kembali ke atas"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
    <div class="cur" id="cur" aria-hidden="true"></div>

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/DRACOLoader.js"></script>

    <script>
        (() => {
            "use strict";

            const doc = document, body = doc.body, $ = (id) => doc.getElementById(id);
            const nav = $("nav"), stage = $("stage"), prog = $("prog");
            const cur = $("cur"), topBtn = $("top"), shadow = $("stageShadow");
            const loader = $("loader"), loaderStat = $("loaderStat");

            const reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
            const coarse = window.matchMedia("(pointer: coarse)");

            // Satu bahasa gerak untuk seluruh halaman: jarak, durasi, dan easing
            // yang sama membuat animasi terasa satu keluarga, bukan tambal sulam.
            const EASE = "power3.out";
            const RISE = 34;
            const DUR = 1.05;
            // Porsi tiap ruas gulir yang dipakai untuk berpindah pose; sisanya diam.
            const SETTLE = .58;

            let lenis = null, ready = false, cameraStageVisible = false;
            let world = null, rig = null, idle = null, parts = null, lastFrame = 0, clock = 0;
            let modelPromise = null;

            if ("scrollRestoration" in history) history.scrollRestoration = "manual";
            window.scrollTo(0, 0);

            /* ================= LOADER =================
               Angkanya mengikuti pemuatan aset yang sebenarnya — font, halaman,
               dan berkas model 3D — bukan penghitung waktu palsu. Model sengaja
               mulai diunduh bersamaan dengan loader, bukan setelahnya, supaya
               halaman terbuka dengan kameranya sudah siap di tempat. */

            const BOOT = { fonts: 0, page: 0, model: 0 };
            const WEIGHT = { fonts: .16, page: .22, model: .62 };
            const MIN_MS = 5500;   // beri waktu 5,5 detik untuk menikmati preloader sebelum masuk landing
            const MAX_MS = 9000;   // jaring pengaman kalau ada aset yang menggantung
            const HOLD_MS = 0;

            let bootStart = 0, lastStep = 0, shown = 0, closing = false;

            const paint = () => {
                if (loaderStat) loaderStat.textContent = "Memuat pengalaman...";
            };

            const realProgress = () =>
                BOOT.fonts * WEIGHT.fonts + BOOT.page * WEIGHT.page + BOOT.model * WEIGHT.model;

            function loaderBreathe() {
                if (reduce.matches || !window.gsap) return;
                gsap.utils.toArray("[data-ldglow]").forEach((el, i) => {
                    gsap.to(el, {
                        xPercent: i ? -10 : 12, yPercent: i ? 8 : -8, scale: 1.12,
                        duration: 9 + i * 4, ease: "sine.inOut", repeat: -1, yoyo: true
                    });
                });
            }

            function startLoader() {
                bootStart = lastStep = performance.now();
                paint(0);
                loaderBreathe();

                if (doc.fonts && doc.fonts.ready) {
                    doc.fonts.ready.then(() => { BOOT.fonts = 1; }).catch(() => { BOOT.fonts = 1; });
                    window.setTimeout(() => { BOOT.fonts = 1; }, 3500);
                } else {
                    BOOT.fonts = 1;
                }

                if (doc.readyState === "complete") BOOT.page = 1;
                else addEventListener("load", () => { BOOT.page = 1; }, { once: true });

                preloadModel();
                requestAnimationFrame(step);
            }

            function step(now) {
                if (closing) return;
                const stamp = now || performance.now();
                const elapsed = stamp - bootStart;
                const dt = Math.min(.1, (stamp - lastStep) / 1000) || .016;
                lastStep = stamp;
                const real = realProgress();

                // Dua pagar sekaligus: lantai berbasis waktu supaya angkanya tidak
                // pernah terlihat macet, dan langit-langit berbasis waktu supaya
                // aset yang sudah tersimpan di cache tidak melompat ke 100 seketika.
                const floor = Math.min(.9, elapsed / (MIN_MS * 1.25));
                const ceil = Math.min(1, .05 + (elapsed / MIN_MS) * .95);
                const target = Math.min(Math.max(real, floor), ceil);

                // Perlambatannya dihitung dari waktu, bukan dari jumlah frame.
                // Kalau memakai faktor tetap per frame, perangkat yang lambat
                // membuat angkanya merangkak jauh di belakang progres sebenarnya.
                shown += (target - shown) * (1 - Math.exp(-dt * 4.5));
                paint(shown);

                const ready = real > .999 && elapsed >= MIN_MS;
                if (ready || elapsed >= MAX_MS) finishLoader();
                else requestAnimationFrame(step);
            }

            function finishLoader() {
                if (closing) return;
                closing = true;

                if (!window.gsap) {
                    paint(1);
                    loader.classList.add("is-hidden");
                    body.classList.remove("is-loading");
                    fallback();
                    window.dispatchEvent(new CustomEvent("scm:landing-ready"));
                    return;
                }

                gsap.timeline()
                    // 1. Rapatkan sisa angka ke 100 dengan perlambatan, bukan lompatan.
                    // 2. Jeda di 100% — inilah yang membuat perpindahannya tidak
                    //    terasa terburu-buru.
                    .to({}, { duration: HOLD_MS / 1000 })
                    // 3. Isi loader terangkat dan mengabur lebih dulu...
                    .to("#loaderIn", { autoAlpha: 0, y: -14, scale: .98, filter: "blur(5px)", duration: .44, ease: "power2.in" })
                    .to("[data-ldglow]", { autoAlpha: 0, duration: .44 }, "<")
                    // 4. ...baru latarnya menyusul.
                    .to(loader, {
                        autoAlpha: 0, duration: .68, ease: "power2.inOut",
                        onComplete() {
                            loader.classList.add("is-hidden");
                            body.classList.remove("is-loading");
                        }
                    }, "-=.18")
                    // 5. Halaman masuk dari skala 1,02 sambil melepas blur.
                    //    Hanya <main> yang diberi transform: nav, panggung 3D, dan
                    //    lapisan cahaya berposisi fixed, dan transform pada leluhurnya
                    //    akan mematahkan penempatan itu.
                    .fromTo("main",
                        { scale: 1.02, filter: "blur(9px)", autoAlpha: .35 },
                        {
                            scale: 1, filter: "blur(0px)", autoAlpha: 1,
                            duration: 1.15, ease: "power3.out",
                            // Transform meninggalkan stacking context baru; kalau
                            // dibiarkan, kanvas 3D akan menutupi section di bawahnya.
                            clearProps: "transform,filter,opacity",
                            onStart() {
                                init();
                                window.dispatchEvent(new CustomEvent("scm:landing-ready"));
                            }
                        }, "-=.85");
            }

            /* ================= PANGGUNG 3D =================
               Kamera hitam mengkilap di atas putih. Yang membuat bentuknya terbaca
               bukan cahaya langsung melainkan pantulan softbox, jadi peta lingkungan
               dibuat lebih dulu dan menjadi sumber cahaya utamanya. */

            const webglOK = () => {
                try { const c = doc.createElement("canvas");
                    return !!(window.WebGLRenderingContext && (c.getContext("webgl") || c.getContext("experimental-webgl"))); }
                catch (e) { return false; }
            };

            function studioEnv(renderer, scene) {
                const W = 1024, H = 512;
                const c = doc.createElement("canvas");
                c.width = W; c.height = H;
                const g = c.getContext("2d");

                const base = g.createLinearGradient(0, 0, 0, H);
                base.addColorStop(0, "#ffffff");
                base.addColorStop(.48, "#ececec");
                base.addColorStop(.62, "#bcbcbc");
                base.addColorStop(1, "#6f6f6f");
                g.fillStyle = base;
                g.fillRect(0, 0, W, H);

                const blob = (x, y, rx, ry, color) => {
                    const r = Math.max(rx, ry);
                    const grad = g.createRadialGradient(x, y, 0, x, y, r);
                    grad.addColorStop(0, color);
                    grad.addColorStop(1, "rgba(0,0,0,0)");
                    g.save();
                    g.translate(x, y); g.scale(rx / r, ry / r); g.translate(-x, -y);
                    g.fillStyle = grad;
                    g.fillRect(-W, -H, W * 3, H * 3);
                    g.restore();
                };

                // Strip tegas inilah yang menjadi garis kilau tajam di tepi bodi.
                // Tanpa ini pantulannya hanya kabut lebar tanpa bentuk.
                const strip = (x, y, w, h, alpha) => {
                    const grad = g.createLinearGradient(0, y - h, 0, y + h);
                    grad.addColorStop(0, "rgba(255,255,255,0)");
                    grad.addColorStop(.5, "rgba(255,255,255," + alpha + ")");
                    grad.addColorStop(1, "rgba(255,255,255,0)");
                    g.fillStyle = grad;
                    g.fillRect(x, y - h, w, h * 2);
                };
                strip(60, 70, 460, 22, 1);
                strip(620, 132, 330, 13, .9);
                strip(180, 250, 400, 8, .5);

                // Satu sapuan oranye — satu-satunya warna di seluruh pantulan.
                blob(700, 330, 250, 115, "rgba(249,115,22,.34)");
                // Bagian gelap: tanpa ini bodi hitam tidak punya tepi untuk dibaca.
                blob(300, 430, 300, 120, "rgba(20,20,20,.72)");
                blob(940, 420, 220, 110, "rgba(20,20,20,.52)");

                const tex = new THREE.CanvasTexture(c);
                tex.mapping = THREE.EquirectangularReflectionMapping;
                const pmrem = new THREE.PMREMGenerator(renderer);
                pmrem.compileEquirectangularShader();
                scene.environment = pmrem.fromEquirectangular(tex).texture;
                pmrem.dispose();
                tex.dispose();
            }

            function prepareModel(obj) {
                obj.traverse((child) => {
                    if (!child.isMesh) return;
                    // Kotak batas bawaan glTF berasal dari min/max accessor dan bisa
                    // meleset; seluruh penskalaan di bawah bergantung padanya.
                    if (child.geometry) {
                        child.geometry.computeBoundingBox();
                        child.geometry.computeBoundingSphere();
                    }
                    const materials = Array.isArray(child.material) ? child.material : [child.material];
                    materials.filter(Boolean).forEach((m) => {
                        if (m.map) m.map.encoding = THREE.sRGBEncoding;
                        if (m.isMeshStandardMaterial) {
                            m.envMapIntensity = 1.25;
                            // Bodi kamera itu plastik dan karet, bukan cermin.
                            m.roughness = Math.min(.6, Math.max(m.roughness, .24));
                        }
                        if (m.color) {
                            const hsl = { h: 0, s: 0, l: 0 };
                            m.color.getHSL(hsl);

                            // Palet halaman ini hitam, putih, abu, dan oranye. Layar LCD
                            // model aslinya kebiruan dan menabrak palet itu, jadi warna
                            // di luar rentang oranye dinetralkan.
                            let s = hsl.s;
                            const orangeish = hsl.h < .1 || hsl.h > .95;
                            if (s > .12 && !orangeish) s = .04;

                            // Model ini mewarisi beberapa material abu terang dan putih
                            // bawaan. Ujung terangnya dikompresi, bukan dipukul rata,
                            // supaya tombol dan cincin lensa tetap lebih terang dari bodi.
                            const l = hsl.l > .3 ? .05 + (hsl.l - .3) * .16 : hsl.l;
                            m.color.setHSL(hsl.h, s, l);
                        }
                        m.needsUpdate = true;
                    });
                });
                return obj;
            }

            function loadGLB(url, onProgress) {
                return new Promise((resolve, reject) => {
                    const draco = new THREE.DRACOLoader();
                    draco.setDecoderPath("https://www.gstatic.com/draco/v1/decoders/");
                    const loader3d = new THREE.GLTFLoader();
                    loader3d.setDRACOLoader(draco);
                    loader3d.load(url,
                        (gltf) => { draco.dispose(); resolve(prepareModel(gltf.scene)); },
                        (e) => {
                            // Server tidak selalu mengirim Content-Length; kalau begitu
                            // porsi ini dibiarkan dan loader memakai laju waktunya sendiri.
                            if (onProgress && e && e.lengthComputable && e.total) {
                                onProgress(Math.min(1, e.loaded / e.total));
                            }
                        },
                        (err) => { draco.dispose(); reject(err); });
                });
            }

            function createWorld(host) {
                const light = coarse.matches || innerWidth < 900;
                const size = () => ({ w: Math.max(1, host.clientWidth), h: Math.max(1, host.clientHeight) });
                const initial = size();

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: "high-performance" });
                renderer.setPixelRatio(Math.min(devicePixelRatio || 1, light ? 1.5 : 1.9));
                renderer.setSize(initial.w, initial.h);
                renderer.outputEncoding = THREE.sRGBEncoding;
                renderer.toneMapping = THREE.ACESFilmicToneMapping;
                renderer.toneMappingExposure = 1.05;
                host.insertBefore(renderer.domElement, host.firstChild);

                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(30, initial.w / initial.h, .1, 100);

                studioEnv(renderer, scene);

                // Peta lingkungan sudah menanggung sebagian besar penerangan; lampu
                // di sini hanya menambah kilau terarah. physicallyCorrectLights mati
                // secara bawaan, jadi intensitasnya berskala kecil.
                scene.add(new THREE.HemisphereLight(0xffffff, 0xd6d6d6, .5));
                const key = new THREE.DirectionalLight(0xffffff, 1.1);
                key.position.set(3.2, 5.4, 5.8); scene.add(key);
                const warm = new THREE.PointLight(0xf97316, 1.2, 22, 2);
                warm.position.set(-4.4, 1.2, -1.8); scene.add(warm);

                const w = {
                    host, renderer, scene, camera, active: true,
                    px: 0, py: 0, radius: 1, dist: 10, warm, onResize: null,
                    look: new THREE.Vector3(), portrait: false, spread: 0
                };

                const resize = () => {
                    const next = size();
                    renderer.setSize(next.w, next.h);
                    camera.aspect = next.w / next.h;
                    camera.updateProjectionMatrix();
                    if (w.onResize) w.onResize();
                };
                if (window.ResizeObserver) new ResizeObserver(resize).observe(host);
                else addEventListener("resize", resize, { passive: true });
                if (window.IntersectionObserver) {
                    new IntersectionObserver((e) => { w.active = e[0].isIntersecting; }, { rootMargin: "120px" }).observe(host);
                }
                return w;
            }

            // Jarak kamera dihitung dari dua batas sekaligus — tinggi DAN lebar —
            // supaya model tidak pernah melebar keluar layar sempit. Di layar lebar
            // model hanya boleh mengisi satu kolom, karena kolom seberangnya milik teks.
            function frameStage() {
                if (!world) return;
                const el = world.host;
                const aspect = Math.max(.3, el.clientWidth / Math.max(1, el.clientHeight));
                const portrait = aspect < 1;
                const tan = Math.tan(world.camera.fov * Math.PI / 360);
                const fitH = portrait ? .44 : .58;
                const fitW = portrait ? .66 : .3;
                world.portrait = portrait;
                world.dist = Math.max(
                    world.radius / (tan * fitH),
                    world.radius / (tan * aspect * fitW)
                );
                // Seberapa jauh model digeser ke samping agar duduk di kolomnya
                // sendiri. Di layar sempit tidak ada kolom, jadi tetap di tengah.
                world.spread = portrait ? 0 : tan * world.dist * aspect * .48;
                // Di layar portrait teks memenuhi paruh atas, jadi titik bidik
                // dinaikkan agar kamera turun ke ruang kosong di bawahnya.
                world.look.set(0, portrait ? tan * world.dist * .54 : 0, 0);
            }

            /* -------- Koreografi: kamera mengorbit, lensa terlepas --------
               Yang berputar adalah kamera pengamat, bukan modelnya. Pantulan
               menyapu bodi dengan benar dan bayangan kontak tetap jatuh lurus.
               `side` menentukan model muncul di kolom kanan (+1) atau kiri (-1),
               berselang-seling mengikuti zig-zag tata letak teksnya. */
            const SHOTS = [
                { az: -.58, el:  .18, zoom: 1,    ex: 0,   roll:  .03, side:  1 },
                { az:  .62, el: -.04, zoom:  .92, ex:  .9, roll: -.04, side: -1 },
                { az: 1.98, el:  .28, zoom: 1.04, ex:  .35, roll:  .05, side:  1 },
                { az: 3.3,  el:  .05, zoom:  .88, ex: 1,   roll: -.03, side: -1 }
            ];

            const shot = {
                az: SHOTS[0].az, el: SHOTS[0].el, zoom: 1,
                ex: 0, roll: SHOTS[0].roll, side: SHOTS[0].side
            };

            function choreograph() {
                const mm = gsap.matchMedia();

                mm.add("(prefers-reduced-motion: no-preference)", () => {
                    const firstProcess = doc.querySelector("#scenes .scene:nth-child(2)");
                    const reveal = firstProcess ? gsap.fromTo(stage,
                        { autoAlpha: 0, y: 72, scale: .94 },
                        {
                            autoAlpha: 1, y: 0, scale: 1,
                            ease: "none",
                            scrollTrigger: {
                                trigger: firstProcess,
                                start: "top 60%",
                                end: "top 18%",
                                scrub: .5,
                                invalidateOnRefresh: true,
                                onEnter() { cameraStageVisible = true; },
                                onEnterBack() { cameraStageVisible = true; },
                                onLeaveBack() { cameraStageVisible = false; }
                            }
                        }) : null;

                    const tl = gsap.timeline({
                        defaults: { ease: "power2.inOut", duration: SETTLE },
                        scrollTrigger: {
                            trigger: "#scenes", start: "top top", end: "bottom bottom",
                            scrub: .55, invalidateOnRefresh: true
                        }
                    });
                    // Satu timeline untuk seluruh rangkaian adegan: kalau tiap adegan
                    // punya tween sendiri, jangkauannya tumpang tindih dan saling
                    // berebut properti sehingga orbitnya sempat mundur.
                    //
                    // Tiap ruas berdurasi 1, tetapi perpindahannya diselesaikan dalam
                    // SETTLE (0,58) lalu diam. Adegan baru pas di tengah layar tepat
                    // di akhir ruas, jadi tanpa jeda diam itu model masih melayang
                    // menyeberang justru ketika teksnya sudah harus dibaca.
                    for (let i = 1; i < SHOTS.length; i += 1) {
                        const s = SHOTS[i], at = i - 1;
                        tl.to(shot, { az: s.az, el: s.el, zoom: s.zoom, roll: s.roll }, at)
                          .to(shot, { ex: s.ex }, at)
                          .to(shot, { side: s.side }, at);
                    }

                    // Keluarnya panggung diselesaikan jauh sebelum bagian padat
                    // masuk. Dengan pemudaran yang lambat, model sempat tertinggal
                    // sebagai bayangan pucat raksasa di atas teks section berikutnya.
                    // Turun sedikit sambil mengecil membuatnya terbaca sebagai
                    // pergi, bukan sekadar menghilang di tempat.
                    const OUT = { trigger: "#scenes", start: "bottom 96%", end: "bottom 72%", scrub: .4 };

                    const fade = gsap.to(stage,
                        {
                            autoAlpha: 0, y: 90, scale: .88, ease: "power2.in",
                            immediateRender: false,
                            scrollTrigger: OUT
                        });

                    // Teks adegan terakhir ikut pergi pada rentang gulir yang sama.
                    // Geraknya sengaja berlawanan — model turun, teks naik — supaya
                    // keduanya terbaca sebagai satu adegan yang ditutup bersama,
                    // bukan dua elemen yang kebetulan menghilang berdekatan.
                    const lastCopy = doc.querySelector("#scenes .scene:last-child .scene__copy");
                    const copyOut = lastCopy ? gsap.fromTo(lastCopy,
                        { autoAlpha: 1, y: 0, scale: 1 },
                        { autoAlpha: 0, y: -64, scale: .97, ease: "power2.in", scrollTrigger: OUT }) : null;

                    return () => {
                        if (tl.scrollTrigger) tl.scrollTrigger.kill();
                        if (reveal && reveal.scrollTrigger) reveal.scrollTrigger.kill();
                        if (fade.scrollTrigger) fade.scrollTrigger.kill();
                        if (copyOut && copyOut.scrollTrigger) copyOut.scrollTrigger.kill();
                        cameraStageVisible = false;
                        gsap.set(stage, { autoAlpha: 0, y: 0, scale: 1 });
                        if (lastCopy) gsap.set(lastCopy, { clearProps: "opacity,visibility,transform" });
                    };
                });
            }

            // Unduhan dimulai dari loader; di sini tinggal memakai hasilnya.
            function preloadModel() {
                if (!stage || !webglOK() || !window.THREE || !THREE.GLTFLoader || !THREE.DRACOLoader) {
                    BOOT.model = 1;
                    return;
                }
                modelPromise = loadGLB(stage.dataset.model, (f) => { BOOT.model = f * .96; })
                    .then((m) => { BOOT.model = 1; return m; })
                    .catch((e) => { BOOT.model = 1; console.warn("Model 3D tidak dapat dimuat:", e); return null; });
            }

            function buildStage() {
                if (!stage || !modelPromise) return Promise.resolve();
                world = createWorld(stage);
                world.onResize = frameStage;

                return modelPromise.then((model) => {
                    if (!model) return;
                    // Normalkan ke ukuran tetap supaya koreografi tidak bergantung
                    // pada satuan asli model.
                    const box = new THREE.Box3().setFromObject(model);
                    const size = box.getSize(new THREE.Vector3());
                    model.scale.setScalar(3.4 / (Math.max(size.x, size.y, size.z) || 1));
                    model.updateMatrixWorld(true);
                    const fitted = new THREE.Box3().setFromObject(model);
                    model.position.sub(fitted.getCenter(new THREE.Vector3()));
                    model.updateMatrixWorld(true);

                    idle = new THREE.Group();
                    idle.add(model);
                    rig = new THREE.Group();
                    rig.add(idle);
                    world.scene.add(rig);

                    const sphere = new THREE.Box3().setFromObject(model).getBoundingSphere(new THREE.Sphere());
                    world.radius = sphere.radius;

                    // Bodi dan lensa dipisahkan saat penyiapan aset supaya keduanya
                    // bisa bergerak sendiri-sendiri di sini.
                    const lens = model.getObjectByName("SCM_LENS");
                    const bodyPart = model.getObjectByName("SCM_BODY");
                    parts = null;
                    if (lens && bodyPart) {
                        const lb = new THREE.Box3().setFromObject(lens);
                        const bb = new THREE.Box3().setFromObject(bodyPart);
                        const bc = bb.getCenter(new THREE.Vector3());
                        parts = {
                            lens,
                            home: lens.position.clone(),
                            // Arah lepasnya mengikuti garis bodi ke lensa, jadi terbaca
                            // sebagai lensa yang dicabut, bukan melayang acak.
                            dir: lb.getCenter(new THREE.Vector3()).sub(bc).normalize(),
                            ground: new THREE.Vector3(0, fitted.min.y - fitted.getCenter(new THREE.Vector3()).y - .45, 0)
                        };
                    }

                    frameStage();
                    choreograph();
                    cameraStageVisible = false;
                    gsap.set(stage, { autoAlpha: 0, y: 0, scale: 1 });

                    if (!reduce.matches) {
                        gsap.fromTo(shot, { zoom: 1.5, el: .46 },
                            { zoom: 1, el: SHOTS[0].el, duration: 1.9, ease: "power3.out" });
                    }
                    ScrollTrigger.refresh();
                }).catch((e) => console.warn("Model 3D tidak dapat dimuat:", e));
            }

            const _p = new THREE.Vector3();

            function placeOverlay() {
                if (!parts || !world) return;
                const rect = world.host.getBoundingClientRect();
                const toScreen = (v, out) => {
                    out.copy(v);
                    rig.localToWorld(out);
                    out.project(world.camera);
                    return {
                        x: (out.x * .5 + .5) * rect.width,
                        y: (-out.y * .5 + .5) * rect.height,
                        z: out.z
                    };
                };

                const g = toScreen(parts.ground, _p);
                if (shadow) {
                    shadow.style.transform = "translate3d(" + g.x + "px," + g.y + "px,0) scale(" +
                        (1 + shot.ex * .3).toFixed(3) + "," + (1 - shot.ex * .1).toFixed(3) + ")";
                    shadow.style.opacity = (.9 - shot.ex * .2).toFixed(3);
                }
            }

            function render(now) {
                requestAnimationFrame(render);
                if (!world || !world.active || !rig || !cameraStageVisible) { lastFrame = now || 0; return; }

                const stamp = now || performance.now();
                const dt = Math.min(.05, (stamp - lastFrame) / 1000) || .016;
                lastFrame = stamp;
                clock += dt;

                if (idle && !reduce.matches) {
                    // Denyut diam ditumpuk di grup terpisah agar tidak berebut
                    // properti dengan tween gulir.
                    idle.position.y = Math.sin(clock * .8) * .06;
                    idle.rotation.z = Math.sin(clock * .62) * .026;
                    idle.rotation.x = Math.sin(clock * .48) * .03;
                }

                // Lensa terlepas dari bodi mengikuti nilai `ex` yang digerakkan gulir.
                if (parts) {
                    const push = shot.ex * world.radius * (world.portrait ? .58 : .78);
                    parts.lens.position.copy(parts.home).addScaledVector(parts.dir, push);
                    parts.lens.rotation.z = shot.ex * .5;
                    parts.lens.rotation.y = shot.ex * -.32;
                }

                const az = shot.az + world.px * .42;
                const el = Math.max(-1.1, Math.min(1.1, shot.el - world.py * .3));
                // Saat lensa terlepas, jangkauan model melebar; kamera ikut mundur
                // supaya bagian yang terlepas tidak keluar dari tepi layar.
                const r = world.dist * shot.zoom * (1 + shot.ex * (world.portrait ? .46 : .2));

                // Menggeser kamera DAN titik bidiknya sejauh jarak yang sama akan
                // memindahkan model ke sisi berlawanan di layar tanpa memiringkan
                // scene sedikit pun. Geserannya harus searah sumbu KANAN KAMERA,
                // bukan sumbu X dunia: begitu orbitnya melewati seperempat putaran,
                // sumbu X dunia sudah menunjuk ke dalam layar, sehingga geseran
                // sepanjang sumbu itu tidak lagi memindahkan model ke samping.
                const offset = -shot.side * world.spread;
                const ox = Math.cos(az) * offset;
                const oz = -Math.sin(az) * offset;
                world.look.set(ox, world.look.y, oz);

                world.camera.position.set(
                    Math.sin(az) * Math.cos(el) * r + ox,
                    Math.sin(el) * r + world.look.y,
                    Math.cos(az) * Math.cos(el) * r + oz
                );
                world.camera.lookAt(world.look);
                world.camera.rotateZ(shot.roll);

                if (world.warm) world.warm.intensity = 1.15 + Math.sin(clock * 1.2) * .35;

                world.renderer.render(world.scene, world.camera);
                placeOverlay();
            }

            function pointer() {
                if (coarse.matches || reduce.matches) return;
                addEventListener("pointermove", (e) => {
                    if (world) {
                        world.px = (e.clientX / innerWidth - .5) * .5;
                        world.py = (e.clientY / innerHeight - .5) * .5;
                    }
                }, { passive: true });
            }

            function cursor() {
                if (!cur || coarse.matches || reduce.matches) return;
                const setX = gsap.quickTo(cur, "x", { duration: .5, ease: "power3" });
                const setY = gsap.quickTo(cur, "y", { duration: .5, ease: "power3" });
                // Cincin baru dimunculkan setelah pointer benar-benar bergerak;
                // kalau tidak, ia tertinggal di pojok kiri atas.
                let seen = false;
                addEventListener("pointermove", (e) => {
                    if (!seen) {
                        seen = true;
                        gsap.set(cur, { x: e.clientX, y: e.clientY });
                        gsap.to(cur, { autoAlpha: 1, duration: .4 });
                    }
                    setX(e.clientX); setY(e.clientY);
                }, { passive: true });
                doc.querySelectorAll("a, button, .rev, .lab").forEach((el) => {
                    el.addEventListener("pointerenter", () => gsap.to(cur, { scale: 1.7, borderColor: "rgba(249,115,22,.7)", duration: .35, ease: EASE }));
                    el.addEventListener("pointerleave", () => gsap.to(cur, { scale: 1, borderColor: "rgba(17,17,17,.15)", duration: .35, ease: EASE }));
                });
            }

            /* -------- Pemecah kata --------
               Kata dibungkus lebih dulu (bukan per huruf) supaya peramban tidak
               pernah memutus satu kata di tengah saat baris menyempit. */
            function splitWords(root) {
                if (!root || root.dataset.split === "1") return Array.from(root ? root.querySelectorAll(".w") : []);
                const walker = doc.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
                const nodes = [];
                let n;
                while ((n = walker.nextNode())) nodes.push(n);

                nodes.forEach((node) => {
                    if (!node.nodeValue.trim()) return;
                    const frag = doc.createDocumentFragment();
                    node.nodeValue.split(/(\s+)/).forEach((part) => {
                        if (!part) return;
                        if (!part.trim()) { frag.appendChild(doc.createTextNode(part)); return; }
                        const s = doc.createElement("span");
                        s.className = "w";
                        s.textContent = part;
                        frag.appendChild(s);
                    });
                    node.parentNode.replaceChild(frag, node);
                });
                root.dataset.split = "1";
                return Array.from(root.querySelectorAll(".w"));
            }

            function headings() {
                const firstProcess = doc.querySelector("#scenes .scene:nth-child(2)");
                gsap.utils.toArray(".h1, .h2").forEach((h) => {
                    if (h.closest("#beranda")) return; // ditangani animasi pembuka
                    if (h.closest(".scene") === firstProcess) return; // ditangani transisi hero
                    const words = splitWords(h);
                    if (!words.length) return;
                    gsap.fromTo(words,
                        { yPercent: 108, autoAlpha: 0 },
                        {
                            yPercent: 0, autoAlpha: 1, duration: 1.1, ease: "expo.out", stagger: .04,
                            scrollTrigger: { trigger: h, start: "top 86%", once: true }
                        });
                });
            }

            function reveals() {
                const firstProcess = doc.querySelector("#scenes .scene:nth-child(2)");
                // Naik-dan-muncul: jarak, durasi, dan easing yang sama di mana pun,
                // sehingga tiap elemen terasa bagian dari satu gerakan.
                gsap.utils.toArray("[data-fade]").forEach((el) => {
                    if (el.closest("#beranda")) return;
                    if (el.closest(".scene") === firstProcess) return; // parent sudah dianimasikan
                    gsap.fromTo(el, { y: RISE, autoAlpha: 0 }, {
                        y: 0, autoAlpha: 1, duration: DUR, ease: EASE,
                        scrollTrigger: { trigger: el, start: "top 90%", once: true }
                    });
                });
                gsap.utils.toArray("[data-stagger]").forEach((wrap) => {
                    gsap.fromTo(wrap.children, { y: RISE + 8, autoAlpha: 0 }, {
                        y: 0, autoAlpha: 1, duration: DUR, ease: EASE, stagger: .085,
                        scrollTrigger: { trigger: wrap, start: "top 88%", once: true }
                    });
                });
            }

            function glows() {
                const spans = doc.querySelectorAll(".glow span");
                if (!spans.length || reduce.matches) return;
                spans.forEach((s, i) => {
                    gsap.to(s, {
                        xPercent: i % 2 ? -12 : 14, yPercent: i % 2 ? 10 : -9,
                        duration: 20 + i * 6, ease: "sine.inOut", repeat: -1, yoyo: true
                    });
                });
            }

            function chrome() {
                let q = false;
                addEventListener("scroll", () => {
                    if (!ready || q) return;
                    q = true;
                    requestAnimationFrame(() => {
                        q = false;
                        nav.classList.toggle("is-scrolled", scrollY > 20);
                        if (topBtn) gsap.to(topBtn, { autoAlpha: scrollY > innerHeight * 2 ? 1 : 0, duration: .3 });
                    });
                }, { passive: true });

                if (topBtn) topBtn.addEventListener("click", () => scrollTo(0, 0));
                doc.querySelectorAll("[data-scroll-next]").forEach((b) => {
                    b.addEventListener("click", () => scrollTo({ top: innerHeight, behavior: "smooth" }));
                });

                if (prog) {
                    gsap.to(prog, {
                        scaleX: 1, ease: "none",
                        scrollTrigger: { trigger: doc.documentElement, start: "top top", end: "bottom bottom", scrub: .3 }
                    });
                }
            }

            function smooth() {
                if (reduce.matches || typeof window.Lenis === "undefined") return;
                lenis = new Lenis({ duration: 1.15, smoothWheel: true, syncTouch: false, wheelMultiplier: .92 });
                lenis.on("scroll", ScrollTrigger.update);
                gsap.ticker.add((t) => lenis.raf(t * 1000));
                gsap.ticker.lagSmoothing(0);
            }

            function opening() {
                const tl = gsap.timeline({ defaults: { ease: EASE } });

                tl.fromTo(nav, { yPercent: -100, autoAlpha: 0 }, { yPercent: 0, autoAlpha: 1, duration: .85 }, 0)
                  .fromTo(".glow span", { autoAlpha: 0, scale: .84 }, { autoAlpha: 1, scale: 1, duration: 1.8, stagger: .12 }, 0);

                 if (doc.querySelector(".particle-hero, .video-hero")) return;

                const title = doc.querySelector("#beranda .h1");
                const words = splitWords(title);

                if (words.length) {
                    tl.fromTo(words,
                        { yPercent: 112, autoAlpha: 0 },
                        { yPercent: 0, autoAlpha: 1, duration: 1.2, stagger: .045, ease: "expo.out" }, .2);
                }

                tl.fromTo("#beranda .sub", { y: RISE, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: DUR }, .72)
                  .fromTo("#beranda .body", { y: RISE, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: DUR }, .84)
                  .fromTo("#beranda .acts", { y: RISE, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: DUR }, .96)
                  .fromTo("#beranda .note", { y: 18, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: .7 }, 1.1);
            }

            function fallback() {
                body.classList.remove("is-loading");
                doc.querySelectorAll("[data-fade], [data-stagger] > *, .w").forEach((e) => { e.style.opacity = "1"; });
                doc.querySelectorAll(".line > span, .w").forEach((e) => { e.style.transform = "none"; });
                if (prog) prog.style.display = "none";
            }

            function init() {
                if (ready) return;
                ready = true;
                chrome();

                if (reduce.matches || !window.gsap || !window.ScrollTrigger) { fallback(); return; }

                gsap.registerPlugin(ScrollTrigger);
                ScrollTrigger.config({ ignoreMobileResize: true });

                smooth(); opening(); headings(); reveals();
                glows(); cursor();

                const refresh = () => ScrollTrigger.refresh();
                if (doc.fonts && doc.fonts.ready) doc.fonts.ready.then(refresh).catch(refresh);
                addEventListener("load", refresh, { once: true });
                addEventListener("orientationchange", () => setTimeout(refresh, 250), { passive: true });
                setTimeout(refresh, 400);

                if (!webglOK() || !window.THREE || !THREE.GLTFLoader || !THREE.DRACOLoader) return;
                pointer();
                render();
                buildStage().then(refresh);
            }

            startLoader();
        })();
    </script>
    <script src="<?= base_url('assets/js/hero-video.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/hero-video.js'); ?>"></script>
    <script src="<?= base_url('assets/js/landing-lab-coverflow.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/landing-lab-coverflow.js'); ?>"></script>
    <script src="<?= base_url('assets/js/faq-assistant.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/faq-assistant.js'); ?>"></script>
</body>
</html>
