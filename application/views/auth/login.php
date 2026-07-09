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
            --fik-orange: #e04700;
            --fik-brown: #b6471e;
            --fik-dark: #6c6868;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, rgba(234, 91, 26, 0.15), rgba(93, 51, 21, 0.12));
            min-height: 100vh;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 1050px;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.16);
        }

        .auth-sidebar {
            background: linear-gradient(135deg, var(--fik-dark), var(--fik-brown));
            color: #fff;
            padding: 40px;
        }

        .auth-form {
            padding: 40px;
        }

        .btn-fik {
            background: linear-gradient(45deg, #c24a13, var(--fik-orange));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-fik:hover {
            color: #fff;
            box-shadow: 0 8px 20px rgba(234, 91, 26, 0.25);
        }

        .form-control:focus {
            border-color: var(--fik-orange);
            box-shadow: 0 0 0 0.2rem rgba(234, 91, 26, 0.2);
        }

        .text-fik-orange { color: var(--fik-orange) !important; }
        .text-fik-brown { color: var(--fik-brown) !important; }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card row g-0">
            <div class="auth-sidebar col-lg-5 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="300" class="me-2">
                        <div>
                            <div class="fw-bold" style="font-size: 1.1rem; letter-spacing: 1px;"></div>
                            <div class="small text-white-50"></div>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-3">Selamat datang di sistem peminjaman aset.</h2>
                    <p class="text-white-50 mb-4">Masuk untuk melihat katalog alat, mengajukan peminjaman, dan memantau status pengembalian.</p>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-box-seam me-2 text-fik-orange"></i>
                        <span>Kelola aset dengan lebih terstruktur</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clock-history me-2 text-fik-orange"></i>
                        <span>Pantau riwayat peminjaman secara real-time</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check me-2 text-fik-orange"></i>
                        <span>Akses sesuai role Anda</span>
                    </div>
                </div>
                <div class="mt-5">
                    <a href="<?= site_url('auth/signup'); ?>" class="btn btn-outline-light rounded-pill px-4">Buat akun baru</a>
                </div>
            </div>

            <div class="auth-form col-lg-7">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-fik-brown">Masuk ke Akun</h3>
                    <p class="text-muted mb-0">Silakan masukkan NIM/NIP dan password Anda.</p>
                </div>

                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger rounded-3 border-0 text-center">
                        <?= $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success rounded-3 border-0 text-center">
                        <?= $this->session->flashdata('success'); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('auth/process_login'); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">NIM / NIP</label>
                        <input type="text" name="username" id="username" class="form-control py-2" placeholder="Masukkan NIM/NIP Anda" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control py-2" placeholder="Masukkan password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-fik py-2">Masuk</button>
                    </div>
                </form>

                <div class="text-center mt-4 text-muted small">
                    Belum punya akun? <a href="<?= site_url('auth/signup'); ?>" class="text-fik-orange fw-semibold text-decoration-none">Daftar sekarang</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>