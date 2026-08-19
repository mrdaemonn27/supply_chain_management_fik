<?php
$master_filters = isset($filters) && is_array($filters) ? $filters : ['criteria' => []];
$master_pagination = isset($pagination) && is_array($pagination) ? $pagination : ['page' => 1, 'per_page' => 10, 'total' => count($barang ?? []), 'total_pages' => 1];
$master_page = (int) ($master_pagination['page'] ?? 1);
$master_total_pages = (int) ($master_pagination['total_pages'] ?? 1);
$master_base_query = ['filter_field' => array_column($master_filters['criteria'] ?? [], 'field'), 'filter_value' => array_column($master_filters['criteria'] ?? [], 'value'), 'per_page' => $master_pagination['per_page'] ?? 10];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Aset - Panel Laboran FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .admin-navbar { background-color: #1a1a1a; border-bottom: 3px solid #ea5b1a; }
        .text-fik-orange { color: #ea5b1a; }
        .btn-fik-orange { background-color: #ea5b1a; color: white; border: none; }
        .btn-fik-orange:hover { background-color: #c24a13; color: white; }
        .master-pagination-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            background: #f9fafb;
        }
        .master-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; }
        .master-pagination-summary, .master-pagination-status { font-size: .72rem; white-space: nowrap; }
        .master-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .master-pagination-status { text-align: center; }
        .master-pagination { margin: 0; }
        .master-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            min-height: 34px;
            padding: .35rem .58rem;
            border-color: #e5e7eb;
            color: #1f2937;
            background: #fff;
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .master-pagination .page-link:hover { color: #ea5b1a; background: #f9fafb; }
        .master-pagination .page-item.active .page-link { color: #fff; background: #ea5b1a; border-color: #ea5b1a; }
        .master-pagination .page-item.disabled .page-link { color: #6b7280; background: #f9fafb; opacity: .62; }
        @media (max-width: 767.98px) {
            .master-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .master-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
        }
        /* Style untuk thumbnail gambar di tabel */
        .img-thumbnail-table { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .img-placeholder { width: 60px; height: 60px; background-color: #eee; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 1.5rem; }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>

    <!-- Navbar Khusus Laboran -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar shadow-sm p-3 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-server me-2 text-fik-orange"></i> LABORAN MASTER DATA</a>
            <div class="ms-auto d-flex align-items-center">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-sm btn-outline-light me-2"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-danger"><i class="bi bi-power"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">Manajemen Master Data Aset</h4>
            </div>
            <!-- Perhatikan penambahan 'admin/' pada URL di bawah ini -->
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('index.php/admin/barang/import') ?>" class="btn btn-outline-success fw-bold px-4 rounded-pill shadow-sm">
                    <i class="bi bi-upload me-1"></i> Import Inventory
                </a>
                <a href="<?= base_url('index.php/admin/barang/tambah') ?>" class="btn btn-fik-orange fw-bold px-4 rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Barang Baru
                </a>
            </div>
        </div>

        <?php
        $multi_filter_id = 'masterMultiFilter';
        $multi_filter_mode = 'server';
        $multi_filter_action = base_url('index.php/admin/barang');
        $multi_filter_rows = $master_filters['criteria'] ?? [];
        $multi_filter_hidden = ['per_page' => (int) ($master_pagination['per_page'] ?? 10), 'page' => 1];
        $multi_filter_fields = [
            'kode' => ['label' => 'Kode aset', 'placeholder' => 'Cari kode aset'],
            'nama' => ['label' => 'Nama barang', 'placeholder' => 'Cari nama barang'],
            'ruangan' => ['label' => 'Lokasi / Lab', 'placeholder' => 'Cari ruangan atau laboratorium'],
            'total' => ['label' => 'Total fisik', 'placeholder' => 'Cari jumlah unit', 'type' => 'number'],
            'kondisi' => ['label' => 'Kondisi', 'placeholder' => 'Cari kondisi barang'],
        ];
        include APPPATH . 'views/admin/_multi_filter.php';
        ?>

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
                                <th class="p-3 text-center">No</th>
                                <th class="p-3 text-center">Gambar</th>
                                <th>Kode Aset</th>
                                <th>Nama Barang</th>
                                <th>Lokasi / Lab</th>
                                <th>Total Fisik</th>
                                <th>Kondisi</th>
                                <th class="text-center">Aksi Laboran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($barang)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada data barang di Master Data.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($barang as $loop_index => $b): ?>
                                <tr>
                                    <td class="p-3 text-center fw-semibold text-muted"><?= (($master_page - 1) * max(1, (int) ($master_pagination['per_page'] ?? 10))) + $loop_index + 1 ?></td>
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
                                        <span class="badge <?= ($b->kondisi == 'Baik') ? 'bg-success' : (($b->kondisi == 'Rusak') ? 'bg-warning text-dark' : 'bg-danger') ?>">
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
            <div class="master-pagination-footer">
                <div class="master-pagination-summary">
                    <label for="masterPerPage">Tampilkan:</label>
                    <select id="masterPerPage" class="form-select form-select-sm" aria-label="Jumlah data aset per halaman">
                        <?php foreach (($per_page_options ?? [10, 25, 50, 100]) as $option): ?><option value="<?= (int) $option ?>" <?= (int) $option === (int) ($master_pagination['per_page'] ?? 10) ? 'selected' : '' ?>><?= (int) $option ?></option><?php endforeach; ?>
                    </select>
                    <span>Total item: <?= (int) ($master_pagination['total'] ?? 0) ?></span>
                </div>
                <div class="master-pagination-status">Halaman: <?= $master_page ?> dari <?= $master_total_pages ?></div>
                <nav aria-label="Pagination master data">
                    <ul class="pagination pagination-sm master-pagination">
                        <?php $master_prev = http_build_query(array_merge($master_base_query, ['page' => max(1, $master_page - 1)])); ?>
                        <li class="page-item <?= $master_page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/barang?' . $master_prev) ?>">Previous</a></li>
                        <?php for ($page_index = 1; $page_index <= $master_total_pages; $page_index++): $master_query = http_build_query(array_merge($master_base_query, ['page' => $page_index])); ?>
                            <li class="page-item <?= $master_page === $page_index ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/barang?' . $master_query) ?>"><?= $page_index ?></a></li>
                        <?php endfor; ?>
                        <?php $master_next = http_build_query(array_merge($master_base_query, ['page' => min($master_total_pages, $master_page + 1)])); ?>
                        <li class="page-item <?= $master_page >= $master_total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/admin/barang?' . $master_next) ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const masterPerPage = document.getElementById('masterPerPage');
        masterPerPage?.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', masterPerPage.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    </script>
</body>
</html>
