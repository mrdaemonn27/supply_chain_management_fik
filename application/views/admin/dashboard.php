<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dashboard Administrator' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Poppins', sans-serif; 
        }
        
        /* Topbar Header Admin */
        .admin-header {
            background-color: #1a1a1a;
            color: white;
            padding: 15px 30px;
            border-bottom: 4px solid #ea5b1a; /* Orange strip FIK */
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-header .title { font-weight: 700; font-size: 1.1rem; letter-spacing: 1px; }
        .admin-header .user-info { font-size: 0.9rem; }
        
        /* Menu Card Styles */
        .menu-card {
            border: none;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            cursor: pointer;
            height: 100%;
            text-decoration: none;
            display: block;
            color: #2c3e50;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(234, 91, 26, 0.12);
        }
        
        /* Efek garis bawah saat hover */
        .menu-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background-color: #ea5b1a;
            transition: width 0.3s ease;
        }
        .menu-card:hover::after { width: 100%; }

        .menu-card .card-body { padding: 35px 25px; text-align: center; }
        
        /* Ikon Modul */
        .icon-box {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .menu-card:hover .icon-box { transform: scale(1.1); }
        
        /* Custom Colors */
        .color-aset { background: rgba(234, 91, 26, 0.1); color: #ea5b1a; }
        .color-ruangan { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .color-peminjaman { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .color-user { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        
        .menu-card h5 { font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
        .menu-card p { font-size: 0.85rem; color: #6c757d; margin-bottom: 15px; line-height: 1.5; }
        
        /* Stat Badge */
        .stat-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        /* Floating Notification Badge */
        .notify-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 12px;
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <!-- Header / Topbar Administrator -->
    <header class="admin-header sticky-top">
        <div class="title d-flex align-items-center">
            <i class="bi bi-shield-lock-fill me-2" style="color: #ea5b1a; font-size: 1.3rem;"></i> 
            <span>ADMINISTRATOR <span class="d-none d-md-inline text-muted fw-normal ms-1">| Master Data</span></span>
        </div>
        <div class="user-info d-flex align-items-center">
            <span class="me-4 d-none d-md-block text-light opacity-75">
                Role: <strong class="text-white"><?= isset($user_role) ? $user_role : 'Admin' ?></strong>
            </span>
            <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-outline-light btn-sm me-2 rounded-pill px-3">
                <i class="bi bi-globe me-1"></i> <span class="d-none d-md-inline">Lihat Web User</span>
            </a>
            <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-danger btn-sm rounded-pill px-3" style="background-color: #ea5b1a; border-color: #ea5b1a;">
                <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Logout</span>
            </a>
        </div>
    </header>

    <!-- Konten Dashboard -->
    <div class="container py-5">
        <div class="row mb-5 align-items-center">
            <div class="col-md-8">
                <h3 class="fw-bold text-dark mb-1">Selamat Datang, Administrator! </h3>
                <p class="text-muted mb-0">Pilih modul di bawah ini untuk mengelola master data pada sistem Supply Chain Management.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0 text-muted small">
                <i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            
            <!-- Modul 1: Manajemen Aset -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= base_url('index.php/admin/barang') ?>" class="card menu-card">
                    <div class="card-body">
                        <div class="icon-box color-aset">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h5>Master Data Aset</h5>
                        <p>Tambah, Edit, dan Hapus data barang secara global.</p>
                    </div>
                </a>
            </div>

            <!-- Modul 2: Manajemen Ruangan / Kategori -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= base_url('index.php/admin/ruangan') ?>" class="card menu-card">
                    <div class="card-body">
                        <div class="icon-box color-ruangan">
                            <i class="bi bi-door-open"></i>
                        </div>
                        <h5>Manajemen Ruangan</h5>
                        <p>Kelola data nama ruangan lab, kode, dan informasi kuota.</p>
                    </div>
                </a>
            </div>

            <!-- Modul 3: Manajemen Peminjaman -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= base_url('index.php/admin/peminjaman') ?>" class="card menu-card">
                    <div class="card-body">
                        <div class="icon-box color-peminjaman">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h5>Data Peminjaman</h5>
                        <p>Persetujuan (Approve/Reject) dan monitoring barang dipinjam.</p>
                    </div>
                </a>
            </div>

            <!-- Modul 4: Manajemen Pengguna -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= base_url('index.php/admin/user') ?>" class="card menu-card">
                    <div class="card-body">
                        <div class="icon-box color-user">
                            <i class="bi bi-people"></i>
                        </div>
                        <h5>Data Pengguna</h5>
                        <p>Kelola data akun mahasiswa, laboran, dan kepala urusan.</p>
                    </div>
                </a>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>