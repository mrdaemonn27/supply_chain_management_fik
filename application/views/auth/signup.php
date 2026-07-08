<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SCM FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --fik-orange: #ea5b1a;
            --fik-brown: #5d3315;
            --fik-dark: #1a1a1a;
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
                        <i class="bi bi-palette-fill fs-2 me-2 text-fik-orange"></i>
                        <div>
                            <div class="fw-bold" style="font-size: 1.1rem; letter-spacing: 1px;">SCM FIK</div>
                            <div class="small text-white-50">INDUSTRI KREATIF</div>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-3">Buat akun untuk akses sistem.</h2>
                    <p class="text-white-50 mb-4">Daftar sebagai pengguna untuk mengajukan peminjaman alat studio dan melihat statusnya.</p>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-camera me-2 text-fik-orange"></i>
                        <span>Kelola peminjaman alat studio</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-clipboard-check me-2 text-fik-orange"></i>
                        <span>Lacak status pengajuan</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-2 text-fik-orange"></i>
                        <span>Data akun tersimpan sesuai tabel users</span>
                    </div>
                </div>
                <div class="mt-5">
                    <a href="<?= site_url('auth'); ?>" class="btn btn-outline-light rounded-pill px-4">Sudah punya akun?</a>
                </div>
            </div>

            <div class="auth-form col-lg-7">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-fik-brown">Daftar Akun</h3>
                    <p class="text-muted mb-0">Isi data di bawah untuk membuat akun baru.</p>
                </div>

                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger rounded-3 border-0 text-center">
                        <?= $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('auth/process_signup'); ?>" method="post">
                    <div class="mb-3">
                        <label for="nim_nip" class="form-label fw-semibold">NIM / NIP</label>
                        <input type="text" name="nim_nip" id="nim_nip" class="form-control py-2" placeholder="Contoh: 2023001" required>
                    </div>
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control py-2" placeholder="Nama lengkap Anda" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="email" class="form-control py-2" placeholder="email@domain.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control py-2" placeholder="Buat password" required>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label fw-semibold">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control py-2" placeholder="Ulangi password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-fik py-2">Daftar</button>
                    </div>
                </form>

                <div class="text-center mt-4 text-muted small">
                    Sudah punya akun? <a href="<?= site_url('auth'); ?>" class="text-fik-orange fw-semibold text-decoration-none">Masuk di sini</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
