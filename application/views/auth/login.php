<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Peminjaman Barang FIK</title>
    <!-- Menggunakan Bootstrap 5 via CDN untuk mempercepat desain UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box { width: 100%; max-width: 400px; padding: 15px; }
        .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-primary">Peminjaman FIK</h4>
                    <p class="text-muted">Silakan login ke akun Anda</p>
                </div>
                
                <!-- Menampilkan pesan error jika login gagal -->
                <?php if($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger text-center p-2 mb-3">
                        <?= $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form action="<?= base_url('auth/process_login'); ?>" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username..." required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password..." required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold">Login</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center mt-3 text-muted">
            <small>Demo Akses:<br>User: <b>logistic</b> | Pass: <b>admin123</b></small>
        </div>
    </div>
</body>
</html>