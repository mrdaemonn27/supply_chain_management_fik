<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCM FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --fik-orange: #ea5b1a;
            --fik-red: #d71920;
            --fik-dark: #1f1f1f;
            --fik-muted: #6b7280;
            --fik-line: #e8eaed;
            --fik-soft: #f4f6f8;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #111827;
            background:
                linear-gradient(135deg, rgba(31, 31, 31, .94), rgba(31, 31, 31, .9)),
                url('<?= base_url('assets/logo/Gedung.webp'); ?>') center/cover fixed;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.75), transparent 80%);
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(18px, 4vw, 44px);
        }

        .auth-shell {
            width: min(1080px, 100%);
            min-height: 640px;
            display: grid;
            grid-template-columns: 1.06fr .94fr;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 28px;
            box-shadow: 0 30px 90px rgba(0,0,0,.32);
            animation: shellIn .72s cubic-bezier(.2,.8,.2,1) both;
        }

        .auth-form-panel {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(28px, 5vw, 58px);
            background: #fff;
            animation: formIn .72s cubic-bezier(.2,.8,.2,1) .12s both;
        }

        .auth-form-inner {
            width: min(430px, 100%);
        }

        .brand-lockup {
            display: flex;
            justify-content: center;
            margin-bottom: 26px;
        }

        .brand-lockup img {
            width: min(280px, 82%);
            height: auto;
            object-fit: contain;
        }

        .auth-title {
            font-size: clamp(2rem, 5vw, 2.7rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0;
            text-align: center;
            margin-bottom: 10px;
        }

        .auth-subtitle {
            text-align: center;
            color: var(--fik-muted);
            font-size: .94rem;
            margin-bottom: 26px;
        }

        .auth-social-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .auth-social {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--fik-line);
            background: #fff;
            color: #343a40;
            box-shadow: 0 8px 18px rgba(17,24,39,.06);
            animation: floatIcon 3.2s ease-in-out infinite;
        }

        .auth-social:nth-child(2) { animation-delay: .16s; }
        .auth-social:nth-child(3) { animation-delay: .32s; }
        .auth-social:nth-child(4) { animation-delay: .48s; }

        .auth-hint {
            text-align: center;
            color: #8a9099;
            font-size: .86rem;
            margin-bottom: 20px;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 14px;
        }

        .input-wrap i {
            position: absolute;
            left: 18px;
            top: 50%;
            z-index: 3;
            width: 18px;
            text-align: center;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            line-height: 1;
            pointer-events: none;
            transition: color .2s ease, opacity .2s ease;
        }

        .form-control {
            position: relative;
            z-index: 1;
            height: 52px;
            border: 0;
            border-radius: 12px;
            background: #eef0f3;
            padding: 0 18px 0 48px;
            font-size: .96rem;
            color: #111827;
            transition: box-shadow .2s ease, background .2s ease, transform .2s ease;
        }

        .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(234,91,26,.17), 0 10px 26px rgba(17,24,39,.08);
            transform: translateY(-1px);
        }

        .input-wrap:focus-within i {
            color: var(--fik-orange);
            opacity: 1;
        }

        .btn-fik {
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0;
            background: linear-gradient(135deg, var(--fik-red), var(--fik-orange));
            box-shadow: 0 16px 30px rgba(234,91,26,.28);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn-fik:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 20px 38px rgba(234,91,26,.38);
        }

        .auth-side-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(34px, 5vw, 56px);
            color: #fff;
            background: linear-gradient(135deg, #d71920 0%, #f04f19 100%);
            animation: panelSlideIn .82s cubic-bezier(.2,.8,.2,1) both;
        }

        .auth-side-panel::before {
            content: '';
            position: absolute;
            inset: -35% -20%;
            z-index: 0;
            background:
                linear-gradient(115deg, transparent 0 35%, rgba(255,255,255,.18) 45%, transparent 56%),
                repeating-linear-gradient(135deg, rgba(255,255,255,.08) 0 1px, transparent 1px 18px);
            animation: panelSweep 5.8s linear infinite;
        }

        .auth-side-content {
            position: relative;
            z-index: 2;
            width: min(390px, 100%);
            text-align: center;
            animation: sideTextIn .78s cubic-bezier(.2,.8,.2,1) .2s both;
        }

        .auth-corner-logo {
            position: absolute;
            top: clamp(18px, 3vw, 30px);
            left: clamp(18px, 3vw, 34px);
            z-index: 4;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 13px;
            border: 1px solid rgba(255,255,255,.34);
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(255,255,255,.72), rgba(255,255,255,.42));
            box-shadow: 0 14px 32px rgba(0,0,0,.12), inset 0 1px 0 rgba(255,255,255,.42);
            backdrop-filter: blur(10px) saturate(1.15);
            -webkit-backdrop-filter: blur(10px) saturate(1.15);
        }

        .auth-corner-logo img {
            width: min(200px, 34vw);
            height: auto;
            display: block;
            object-fit: contain;
            filter: saturate(1.08) contrast(1.04) drop-shadow(0 4px 10px rgba(0,0,0,.1));
        }

        .auth-side-content h2 {
            font-size: clamp(2.1rem, 5vw, 3rem);
            line-height: 1.08;
            font-weight: 800;
            margin-bottom: 14px;
            letter-spacing: 0;
        }

        .auth-side-content p {
            color: rgba(255,255,255,.82);
            margin-bottom: 30px;
        }

        .btn-ghost {
            min-width: 170px;
            border: 2px solid rgba(255,255,255,.82);
            color: #fff;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .btn-ghost:hover {
            background: #fff;
            color: var(--fik-red);
            transform: translateY(-2px);
        }

        .auth-mobile-link {
            display: none;
            text-align: center;
            margin-top: 22px;
            color: var(--fik-muted);
            font-size: .9rem;
        }

        .auth-mobile-link a {
            color: var(--fik-orange);
            font-weight: 700;
            text-decoration: none;
        }

        .alert {
            font-size: .9rem;
            border-radius: 12px;
        }

        @keyframes shellIn {
            from { transform: translateY(22px) scale(.985); }
            to { transform: translateY(0) scale(1); }
        }

        @keyframes formIn {
            from { transform: translateX(-26px); }
            to { transform: translateX(0); }
        }

        @keyframes panelSlideIn {
            from { transform: translateX(16%); }
            to { transform: translateX(0); }
        }

        @keyframes sideTextIn {
            from { transform: translateY(18px); }
            to { transform: translateY(0); }
        }

        @keyframes panelSweep {
            from { transform: translateX(-12%) rotate(0deg); }
            to { transform: translateX(12%) rotate(0deg); }
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @media (max-width: 991.98px) {
            body { background-attachment: scroll; }
            .auth-page { align-items: flex-start; }
            .auth-shell {
                display: flex;
                flex-direction: column;
                min-height: auto;
                grid-template-columns: 1fr;
                max-width: 560px;
                background: linear-gradient(135deg, #d71920 0%, #f04f19 100%);
                border-radius: 22px;
            }

            .auth-side-panel {
                order: -1;
                width: 100%;
                min-width: 0;
                min-height: 260px;
                padding: 34px 26px;
            }

            .auth-form-panel {
                width: 100%;
                min-width: 0;
                padding: 92px 24px 34px;
            }

            .auth-side-panel,
            .auth-form-panel,
            .auth-side-content {
                animation: none;
                transform: none;
            }

            .auth-side-content h2 { font-size: 2rem; }
            .auth-side-content p { margin-bottom: 18px; }
            .btn-ghost { display: none; }
            .auth-mobile-link { display: block; }
        }

        @media (max-width: 420px) {
            .auth-page { padding: 12px; }
            .auth-shell { border-radius: 18px; }
            .auth-side-panel {
                min-height: auto;
                padding: 28px 20px;
            }
            .auth-corner-logo {
                top: 16px;
                left: 16px;
                padding: 7px 12px;
            }
            .auth-corner-logo img {
                width: min(170px, 54vw);
            }
            .auth-side-content h2 {
                font-size: 1.9rem;
                line-height: 1.08;
                margin-bottom: 10px;
            }
            .auth-side-content p {
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
                font-size: .9rem;
                margin-bottom: 0;
            }
            .auth-form-panel {
                padding: 84px 18px 28px;
            }
            .brand-lockup {
                margin-bottom: 14px;
            }
            .brand-lockup img {
                width: min(230px, 82%);
            }
            .auth-title {
                font-size: 2rem;
            }
            .auth-social {
                width: 40px;
                height: 40px;
                border-radius: 11px;
            }
            .auth-hint {
                margin-bottom: 14px;
            }
            .input-wrap {
                margin-bottom: 10px;
            }
            .form-control, .btn-fik { height: 50px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <section class="auth-shell" aria-label="Halaman masuk SCM FIK">
            <div class="auth-form-panel">
                <div class="auth-corner-logo">
                    <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Fakultas Industri Kreatif">
                </div>
                <div class="auth-form-inner">
                    <h1 class="auth-title">Sign In</h1>
                    <div class="auth-social-row" aria-hidden="true">
                        <span class="auth-social"><i class="bi bi-person-badge"></i></span>
                        <span class="auth-social"><i class="bi bi-box-seam"></i></span>
                        <span class="auth-social"><i class="bi bi-shield-check"></i></span>
                        <span class="auth-social"><i class="bi bi-building"></i></span>
                    </div>
                    <p class="auth-hint">Masuk menggunakan NIM/NIP dan password</p>

                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger border-0 text-center">
                            <?= $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success border-0 text-center">
                            <?= $this->session->flashdata('success'); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= site_url('auth/process_login'); ?>" method="post">
                        <div class="input-wrap">
                            <label for="username" class="visually-hidden">NIM / NIP</label>
                            <i class="bi bi-person"></i>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan NIM/NIP Anda" required autofocus>
                        </div>
                        <div class="input-wrap">
                            <label for="password" class="visually-hidden">Password</label>
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-fik">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                            </button>
                        </div>
                    </form>

                    <div class="auth-mobile-link">
                        Belum punya akun? <a href="<?= site_url('auth/signup'); ?>">Daftar sekarang</a>
                    </div>
                </div>
            </div>

            <aside class="auth-side-panel">
                <div class="auth-side-content">
                    <h2>Selamat Datang</h2>
                    <p>Lihat katalog aset, ajukan peminjaman, dan pantau status pengembalian dari satu sistem.</p>
                    <a href="<?= site_url('auth/signup'); ?>" class="btn-ghost">
                        Buat Akun
                    </a>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
