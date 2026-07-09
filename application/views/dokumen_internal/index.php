<?php
$role = strtolower((string) $this->session->userdata('role'));
$back_url = ($role === 'kaur') ? base_url('index.php/kaur/dashboard') : (($role === 'kaprodi') ? base_url('index.php/kaprodi/dashboard') : base_url('index.php/dashboard'));
function internal_size($bytes) {
    $bytes = (int) $bytes;
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    return number_format(max(1, $bytes) / 1024, 1, ',', '.') . ' KB';
}
function internal_icon($file) {
    $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
    return $ext === 'pdf' ? 'file-earmark-pdf' : 'file-earmark-word';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'File Manager SOP' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .panel-card { background: #fff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 8px 22px rgba(32,33,36,.05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234,91,26,.16); }
        .table thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; }
        .doc-icon { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234,91,26,.12); color: #c24a13; font-size: 1.3rem; }
        .soft-badge { border-radius: 999px; padding: 6px 10px; font-weight: 600; font-size: .74rem; background: rgba(234,91,26,.12); color: #c24a13; }
        .inactive-row { opacity: .58; }
        @media (max-width: 767.98px) { .topbar-actions { width: 100%; } .topbar-actions .btn { flex: 1 1 auto; } }
    </style>
</head>
<body>
<header class="topbar sticky-top">
    <div class="container-fluid px-3 px-lg-4 py-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-folder2-open me-2 text-warning"></i>File Manager SOP & Instruksi Kerja</div>
                <div class="small text-white-50">Dokumen internal hanya untuk user yang sudah login</div>
            </div>
            <div class="topbar-actions d-flex flex-wrap gap-2">
                <a href="<?= $back_url ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                <a href="<?= base_url('index.php/dokumen_internal/popup') ?>" target="_blank" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-eye me-1"></i> Preview Popup</a>
            </div>
        </div>
    </div>
</header>

<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-card p-3 p-lg-4 h-100">
                <?php if($can_manage): ?>
                    <h5 class="fw-bold mb-3">Upload Dokumen</h5>
                    <form action="<?= base_url('index.php/dokumen_internal/simpan') ?>" method="post" enctype="multipart/form-data" class="vstack gap-3">
                        <div>
                            <label class="form-label small fw-semibold text-muted">Judul</label>
                            <input type="text" name="judul" class="form-control" placeholder="Contoh: SOP Peminjaman Studio" required>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option>SOP</option>
                                <option>Instruksi Kerja</option>
                                <option>Tata Tertib</option>
                                <option>Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">File</label>
                            <input type="file" name="dokumen" class="form-control" accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">PDF, DOC, DOCX. Maksimal 10MB.</small>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-muted">Deskripsi</label>
                            <textarea name="deskripsi" rows="3" class="form-control" placeholder="Ringkasan isi dokumen"></textarea>
                        </div>
                        <button class="btn btn-fik rounded-pill"><i class="bi bi-upload me-1"></i> Upload</button>
                    </form>
                <?php else: ?>
                    <h5 class="fw-bold mb-2">Mode Baca</h5>
                    <p class="text-muted mb-0">Akun Anda bisa membaca dokumen internal. Upload dan hapus dokumen hanya tersedia untuk pengelola internal.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Dokumen</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Uploader</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($dokumen)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada dokumen internal.</td></tr>
                            <?php else: foreach($dokumen as $d): ?>
                                <tr class="<?= $d->is_active ? '' : 'inactive-row' ?>">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="doc-icon"><i class="bi bi-<?= internal_icon($d->nama_file) ?>"></i></span>
                                            <div>
                                                <div class="fw-semibold"><?= html_escape($d->judul) ?></div>
                                                <div class="small text-muted"><?= html_escape($d->original_name ?: $d->nama_file) ?> - <?= internal_size($d->file_size) ?></div>
                                                <?php if(!empty($d->deskripsi)): ?><div class="small text-muted"><?= html_escape($d->deskripsi) ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="soft-badge"><?= html_escape($d->kategori) ?></span></td>
                                    <td><?= $d->is_active ? '<span class="text-success fw-semibold">Aktif</span>' : '<span class="text-muted">Nonaktif</span>' ?></td>
                                    <td><div><?= html_escape($d->uploader ?: '-') ?></div><div class="small text-muted"><?= html_escape($d->created_at) ?></div></td>
                                    <td class="text-end pe-3">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                            <?php if($d->is_active): ?>
                                                <a href="<?= base_url('index.php/dokumen_internal/lihat/'.$d->id_dokumen) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-eye me-1"></i> Buka</a>
                                                <a href="<?= base_url('index.php/dokumen_internal/unduh/'.$d->id_dokumen) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-download"></i></a>
                                            <?php endif; ?>
                                            <?php if($can_manage): ?>
                                                <a href="<?= base_url('index.php/dokumen_internal/toggle/'.$d->id_dokumen) ?>" class="btn btn-sm btn-outline-warning rounded-pill"><?= $d->is_active ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                                                <a href="<?= base_url('index.php/dokumen_internal/hapus/'.$d->id_dokumen) ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus dokumen ini?')"><i class="bi bi-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
