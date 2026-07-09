<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Aset - Panel Admin FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .admin-navbar { background-color: #1a1a1a; border-bottom: 3px solid #ea5b1a; }
        .text-fik-orange { color: #ea5b1a; }
        .btn-fik-orange { background-color: #ea5b1a; color: white; border: none; }
        .btn-fik-orange:hover { background-color: #c24a13; color: white; }
        /* Style untuk thumbnail gambar di tabel */
        .img-thumbnail-table { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .img-placeholder { width: 60px; height: 60px; background-color: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 1.5rem; }
    </style>
</head>
<body>

    <!-- Navbar Khusus Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm p-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-server me-2 text-fik-orange"></i> ADMINISTRATOR MASTER DATA</a>
            <div class="ms-auto d-flex align-items-center">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-sm btn-outline-light me-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-danger"><i class="bi bi-power"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Master Data Aset</h4>
                <p class="text-muted small m-0">Tambah, Edit, dan Hapus data barang secara global.</p>
            </div>
            <!-- Perhatikan penambahan 'admin/' pada URL di bawah ini -->
            <a href="<?= base_url('index.php/admin/barang/tambah') ?>" class="btn btn-fik-orange fw-bold px-4 rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Barang Baru
            </a>
        </div>

        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $this->session->flashdata('success'); ?>
            </div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3 text-center">Gambar</th>
                                <th>Kode Aset</th>
                                <th>Nama Barang</th>
                                <th>Lokasi / Lab</th>
                                <th>Total Fisik</th>
                                <th>Kondisi</th>
                                <th class="text-center">Aksi Administrator</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($barang)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada data barang di Master Data.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($barang as $b): ?>
                                <tr>
                                    <td class="p-3 text-center">
                                        <!-- Logika menampilkan gambar atau placeholder -->
                                        <?php if(!empty($b->gambar) && file_exists('./assets/uploads/barang/'.$b->gambar)): ?>
                                            <img src="<?= base_url('assets/uploads/barang/'.$b->gambar) ?>" alt="<?= $b->nama_aset ?>" class="img-thumbnail-table">
                                        <?php else: ?>
                                            <div class="img-placeholder mx-auto" title="Tidak ada gambar">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary font-monospace"><?= $b->kode_aset ?></span></td>
                                    <td class="fw-semibold text-dark"><?= $b->nama_aset ?></td>
                                    <td class="text-muted small"><i class="bi bi-geo-alt-fill text-fik-orange me-1"></i><?= $b->nama_ruangan ?></td>
                                    <td><b class="text-primary"><?= $b->jumlah_total ?></b> Unit</td>
                                    <td>
                                        <span class="badge <?= ($b->kondisi == 'Baik') ? 'bg-success' : (($b->kondisi == 'Rusak Ringan') ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                            <?= $b->kondisi ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <!-- Perhatikan penambahan 'admin/' pada URL di bawah ini -->
                                        <a href="<?= base_url('index.php/admin/barang/edit/'.$b->id_aset) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="<?= base_url('index.php/admin/barang/hapus/'.$b->id_aset) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('PERINGATAN!\n\nMenghapus master data ini akan menghilangkan barang dari halaman peminjaman secara permanen. Lanjutkan?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>