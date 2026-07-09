<?php
/**
 * @var string $page
 * @var string $title
 * @var array  $ruangan_list
 * @var array  $ruangan_detail
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Manajemen Ruangan'; ?> - Panel Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .admin-navbar { background-color: #1a1a1a; border-bottom: 3px solid #ea5b1a; }
        .text-fik-orange { color: #ea5b1a !important; }
        .btn-fik-orange { background-color: #ea5b1a; color: white; border: none; }
        .btn-fik-orange:hover { background-color: #c24a13; color: white; }
        .img-thumbnail-custom { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm p-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-door-open-fill me-2 text-fik-orange"></i> ADMINISTRATOR RUANGAN
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-sm btn-outline-light me-2">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="<?= base_url('auth/logout') ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-power"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3 alert-dismissible fade show mt-3">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $this->session->flashdata('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3 alert-dismissible fade show mt-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $this->session->flashdata('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if($page == 'index'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Master Data Ruangan</h4>
                <p class="text-muted small m-0">Tambah, Edit, dan Hapus data ruangan / laboratorium secara global.</p>
            </div>
            <a href="<?= base_url('admin/ruangan/tambah') ?>" class="btn btn-fik-orange fw-bold px-4 rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Ruangan
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3 text-center" width="5%">No</th>
                                <th width="15%" class="text-center">Gambar</th>
                                <th width="25%">Nama Ruangan</th>
                                <th width="40%">Deskripsi</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($ruangan_list)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data ruangan.</td>
                            </tr>
                            <?php else: ?>
                                <?php $no=1; foreach($ruangan_list as $r): ?>
                                <tr>
                                    <td class="p-3 text-center"><?= $no++; ?></td>
                                    <td class="text-center">
                                        <?php if($r['foto']): ?>
                                            <img src="<?= base_url('assets/uploads/ruangan/'.$r['foto']) ?>" class="img-thumbnail-custom shadow-sm" alt="Foto Ruangan">
                                        <?php else: ?>
                                            <div class="bg-light text-muted d-inline-flex align-items-center justify-content-center img-thumbnail-custom border">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= $r['nama_ruangan'] ?></td>
                                    <td class="text-muted small">
                                        <?= !empty($r['deskripsi']) ? $r['deskripsi'] : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/ruangan/ubah/'.$r['id_ruangan']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1 mb-1">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="<?= base_url('admin/ruangan/hapus/'.$r['id_ruangan']) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 mb-1" onclick="return confirm('Hapus ruangan ini beserta fotonya secara permanen?');">
                                            <i class="bi bi-trash"></i>
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


        <?php elseif($page == 'tambah'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Tambah Ruangan</h4>
            </div>
            <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-light fw-bold px-4 rounded-pill border">Kembali</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('admin/ruangan/tambah') ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_ruangan" class="form-control <?= form_error('nama_ruangan') ? 'is-invalid' : '' ?>" value="<?= set_value('nama_ruangan') ?>" placeholder="Contoh: Lab Jaringan Komputer">
                        <div class="invalid-feedback"><?= form_error('nama_ruangan') ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto Ruangan (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <small class="text-muted">Maksimal 2MB. Format: JPG, JPEG, PNG, WEBP.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi Ruangan</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= set_value('deskripsi') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-fik-orange rounded-pill px-4 fw-bold">Simpan</button>
                </form>
            </div>
        </div>


        <?php elseif($page == 'ubah'): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 mt-4">
            <div>
                <h4 class="fw-bold mb-1">Edit Ruangan</h4>
            </div>
            <a href="<?= base_url('admin/ruangan') ?>" class="btn btn-light fw-bold px-4 rounded-pill border">Batal</a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('admin/ruangan/ubah/'.$ruangan_detail['id_ruangan']) ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Ruangan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_ruangan" class="form-control <?= form_error('nama_ruangan') ? 'is-invalid' : '' ?>" value="<?= set_value('nama_ruangan', $ruangan_detail['nama_ruangan']) ?>">
                        <div class="invalid-feedback"><?= form_error('nama_ruangan') ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Foto Baru (Opsional)</label>
                        <?php if($ruangan_detail['foto']): ?>
                            <div class="mb-2">
                                <img src="<?= base_url('assets/uploads/ruangan/'.$ruangan_detail['foto']) ?>" style="height:80px; border-radius:5px;" alt="Current Foto">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto, dengan Format: JPG/PNG. Maksimal ukuran file: 2MB.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Deskripsi Ruangan</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= set_value('deskripsi', $ruangan_detail['deskripsi']) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-fik-orange rounded-pill px-4 fw-bold">Update Data</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>