<?php
/** @var object $aset */
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$program_studi = isset($program_studi) && is_array($program_studi) ? $program_studi : [];
$jenis_peminjam_options = isset($jenis_peminjam_options) && is_array($jenis_peminjam_options) ? $jenis_peminjam_options : [];
$user_prodi = $user_prodi ?? null;
$user_jenis = $user_jenis ?? null;
$asset_media = [];
$gallery_source = json_decode((string) ($aset->foto ?? ''), true);
$gallery_filenames = is_array($gallery_source) ? $gallery_source : [($aset->foto ?? '')];
$media_filenames = array_merge([($aset->gambar ?? '')], $gallery_filenames);
foreach ($media_filenames as $media_filename) {
    $media_filename = basename(trim((string) $media_filename));
    $media_extension = strtolower(pathinfo($media_filename, PATHINFO_EXTENSION));
    if (
        $media_filename !== ''
        && in_array($media_extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'glb', 'gltf'], true)
        && is_file(FCPATH . 'assets/uploads/barang/' . $media_filename)
        && !in_array($media_filename, $asset_media, true)
    ) {
        $asset_media[] = $media_filename;
    }
}
$has_uploaded_visual = !empty($asset_media);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Peminjaman - SCM FIK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }

        /* Palette FIK */
        .text-fik-orange { color: #ea5b1a !important; }
        .bg-fik-orange { background-color: #ea5b1a !important; }
        .bg-fik-brown { background-color: #5d3315 !important; }

        /* Navbar Dinamis */
        .navbar-custom { background-color: #ffffff; padding: 12px 0; border-bottom: 2px solid #ea5b1a; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .navbar-dark .navbar-nav .nav-link { color: #333333; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }
        .notif-bell { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }

        /* Form Card Styling */
        .form-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .info-card { background: linear-gradient(135deg, #5d3315, #3a1e0a); color: white; border-radius: 15px; border: none; }
        
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; border: 1px solid #dee2e6; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 0.25rem rgba(234, 91, 26, 0.25); }
        .btn-submit { background-color: #ea5b1a; color: white; font-weight: 600; padding: 12px; border-radius: 8px; border: none; transition: 0.3s; }
        .btn-submit:hover { background-color: #c24a13; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(234, 91, 26, 0.3); }
        
        /* Galeri detail aset: foto utama lalu media tambahan 3D. */
        .asset-showcase {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            width: 100%;
            aspect-ratio: 4 / 3;
            min-height: 210px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 16px;
            background:
                radial-gradient(circle at 50% 22%, rgba(255,255,255,.22), transparent 45%),
                linear-gradient(145deg, rgba(255,255,255,.14), rgba(255,255,255,.05));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 18px 34px rgba(31,14,3,.25);
            touch-action: pan-y;
            user-select: none;
        }
        .asset-showcase__slide {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 12px;
            opacity: 0;
            visibility: hidden;
            transform: scale(.985);
            transition: opacity .35s ease, transform .4s ease, visibility 0s linear .4s;
        }
        .asset-showcase__slide.is-active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
            transition-delay: 0s;
        }
        .aset-thumbnail { width: 100%; height: 100%; min-height: 0; object-fit: cover; border-radius: 11px; }
        .aset-thumbnail--product {
            object-fit: contain;
            padding: 8px;
            background:
                radial-gradient(circle at 50% 25%, rgba(255,255,255,.42), transparent 50%),
                rgba(255,255,255,.1);
        }
        .aset-thumbnail--model {
            display: block;
            background: rgba(255,255,255,.08);
            --poster-color: transparent;
            --progress-bar-color: #ea5b1a;
        }
        .asset-showcase__badge {
            position: absolute;
            z-index: 3;
            top: 12px;
            left: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px;
            color: #fff;
            background: rgba(36,17,5,.76);
            box-shadow: 0 6px 16px rgba(0,0,0,.18);
            backdrop-filter: blur(8px);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .03em;
        }
        .asset-showcase__counter {
            position: absolute;
            z-index: 3;
            top: 12px;
            right: 12px;
            padding: 5px 9px;
            border-radius: 999px;
            color: rgba(255,255,255,.9);
            background: rgba(36,17,5,.62);
            backdrop-filter: blur(8px);
            font-size: .68rem;
            font-weight: 600;
        }
        .asset-showcase__nav {
            position: absolute;
            z-index: 4;
            top: 50%;
            display: inline-grid;
            place-items: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 1px solid rgba(255,255,255,.24);
            border-radius: 50%;
            color: #fff;
            background: rgba(45,21,7,.72);
            box-shadow: 0 6px 18px rgba(0,0,0,.2);
            backdrop-filter: blur(8px);
            transform: translateY(-50%);
            transition: background-color .2s ease, transform .2s ease;
        }
        .asset-showcase__nav:hover,
        .asset-showcase__nav:focus-visible { background: #ea5b1a; transform: translateY(-50%) scale(1.06); }
        .asset-showcase__nav--previous { left: 12px; }
        .asset-showcase__nav--next { right: 12px; }
        .asset-showcase__footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 28px;
            margin-top: 12px;
        }
        .asset-showcase__dot {
            width: 9px;
            height: 7px;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: rgba(255,255,255,.35);
            box-shadow: 0 0 0 1px rgba(255,255,255,.08);
            transition: width .3s ease, background-color .3s ease, transform .2s ease;
        }
        .asset-showcase__dot:hover { transform: scale(1.14); }
        .asset-showcase__dot.is-active { width: 34px; background: #ea5b1a; }
        .asset-showcase__hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 18px;
            margin-top: 2px;
            color: rgba(255,255,255,.64);
            font-size: .66rem;
        }
        @media (max-width: 575.98px) {
            .asset-showcase { min-height: 230px; }
            .asset-showcase__nav { width: 34px; height: 34px; }
            .info-card { padding: 1.15rem !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            .asset-showcase__slide, .asset-showcase__nav, .asset-showcase__dot { transition: none; }
        }

        /* Custom Style untuk Drag & Drop Zone */
        .drop-zone {
            border: 2px dashed #adb5bd;
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            background-color: #f4f6f9;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .drop-zone:hover, .drop-zone.dragover {
            background-color: #e2e8f0;
            border-color: #ea5b1a;
            transform: scale(1.01);
        }
        .drop-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .preview-container {
            position: relative;
            z-index: 3;
            pointer-events: none; 
        }
        .preview-wrapper {
            position: relative;
            display: inline-block;
        }
        .btn-remove-preview {
            position: absolute;
            top: -12px;
            right: -12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            pointer-events: auto;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .btn-remove-preview:hover {
            background-color: #bb2d3b;
            transform: scale(1.1);
        }
        .capture-buttons .btn {
            min-height: 44px;
            border-radius: 10px;
            font-weight: 600;
        }
        .camera-overlay {
            position: fixed;
            inset: 0;
            z-index: 1090;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0, 0, 0, .86);
            backdrop-filter: blur(4px);
        }
        .camera-box {
            overflow: hidden;
            width: min(100%, 520px);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 16px;
            background: #050505;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .45);
        }
        .camera-box video {
            display: block;
            width: 100%;
            max-height: 68vh;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            background: #000;
        }
        .camera-controls {
            display: flex;
            justify-content: center;
            gap: .65rem;
            padding: .9rem;
            background: #111;
        }
        @media (max-width: 575.98px) {
            .capture-buttons .btn { font-size: .82rem; }
            .camera-controls { flex-direction: column; }
        }
    </style>
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="<?= base_url('assets/logo/logo.webp'); ?>" alt="Logo" width="300" class="me-2">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/dashboard') ?>">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/peminjaman') ?>">Total Barang</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Ajukan Peminjaman</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a></li>
                </ul>
            </div>
            <div class="d-none d-lg-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                        <div class="fw-bold px-2 py-1">Notifikasi</div>
                        <?php if (empty($notif_items)): ?>
                            <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                            <a class="dropdown-item rounded-3 py-2" href="<?= html_escape($n->link ?: '#') ?>">
                                <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <button class="btn btn-user"><i class="bi bi-person-circle me-1"></i> <?= $display_nama; ?></button>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="mb-4 text-center" data-aos="fade-down">
            <h2 class="fw-bold text-dark mb-0">FORM PENGAJUAN <span class="text-fik-orange">PEMINJAMAN</span></h2>
        </div>

        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" data-aos="shake">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4" data-aos="fade-right">
                <div class="card info-card h-100 p-4">
                    <h5 class="fw-bold text-fik-orange mb-4"><i class="bi bi-box-seam me-2"></i>Detail Aset</h5>
                    
                    <div class="mb-4 text-center">
                        <?php if($has_uploaded_visual): ?>
                            <div class="asset-showcase" data-asset-showcase tabindex="0" aria-roledescription="carousel" aria-label="Galeri media <?= html_escape($aset->nama_aset) ?>">
                                <?php foreach ($asset_media as $media_index => $media_filename): ?>
                                    <?php $media_is_3d = in_array(strtolower(pathinfo($media_filename, PATHINFO_EXTENSION)), ['glb', 'gltf'], true); ?>
                                    <div class="asset-showcase__slide<?= $media_index === 0 ? ' is-active' : '' ?>" data-asset-showcase-slide data-media-type="<?= $media_is_3d ? '3D interaktif' : 'Foto produk' ?>" aria-hidden="<?= $media_index === 0 ? 'false' : 'true' ?>">
                                        <?php if ($media_is_3d): ?>
                                            <model-viewer src="<?= base_url('assets/uploads/barang/'.rawurlencode($media_filename)) ?>" alt="Model 3D <?= html_escape($aset->nama_aset) ?>" class="aset-thumbnail aset-thumbnail--product aset-thumbnail--model" camera-controls disable-pan disable-zoom interaction-prompt="none" touch-action="pan-y" shadow-intensity="0.65" exposure="1.05" auto-rotate auto-rotate-delay="1800" rotation-per-second="18deg" loading="eager" reveal="auto"></model-viewer>
                                        <?php else: ?>
                                            <img src="<?= base_url('assets/uploads/barang/'.rawurlencode($media_filename)) ?>" alt="<?= html_escape($aset->nama_aset) ?><?= count($asset_media) > 1 ? ' - media ' . ($media_index + 1) : '' ?>" class="aset-thumbnail aset-thumbnail--product" decoding="async"<?= $media_index === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <span class="asset-showcase__badge" data-asset-showcase-type><i class="bi bi-image"></i> Foto produk</span>
                                <?php if (count($asset_media) > 1): ?>
                                    <span class="asset-showcase__counter" data-asset-showcase-counter>1 / <?= count($asset_media) ?></span>
                                    <button type="button" class="asset-showcase__nav asset-showcase__nav--previous" data-asset-showcase-previous aria-label="Media sebelumnya"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                                    <button type="button" class="asset-showcase__nav asset-showcase__nav--next" data-asset-showcase-next aria-label="Media berikutnya"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                                <?php endif; ?>
                            </div>
                            <?php if (count($asset_media) > 1): ?>
                                <div class="asset-showcase__footer" aria-label="Pilih media aset">
                                    <?php foreach ($asset_media as $media_index => $media_filename): ?>
                                        <?php $media_is_3d = in_array(strtolower(pathinfo($media_filename, PATHINFO_EXTENSION)), ['glb', 'gltf'], true); ?>
                                        <button type="button" class="asset-showcase__dot<?= $media_index === 0 ? ' is-active' : '' ?>" data-asset-showcase-dot="<?= $media_index ?>" aria-label="Tampilkan <?= $media_is_3d ? 'model 3D' : 'foto' ?> <?= $media_index + 1 ?>" aria-current="<?= $media_index === 0 ? 'true' : 'false' ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="asset-showcase__hint" data-asset-showcase-hint><i class="bi bi-arrow-left-right"></i> Geser atau gunakan tombol untuk melihat media lain</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="asset-showcase"><i class="bi bi-camera" style="font-size: 4rem; color: #f8f9fa; opacity: 0.8;"></i></div>
                        <?php endif; ?>
                    </div>
                    <h5 class="fw-bold text-white mb-1"><?= $aset->nama_aset ?></h5>
                    <p class="text-white opacity-75 small mb-4 font-monospace"><?= $aset->kode_aset ?></p>

                    <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-3">
                        <span class="opacity-75 small">Lokasi Tumpukan</span>
                        <span class="fw-bold small"><?= $aset->nama_ruangan ?: 'Gudang' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-3">
                        <span class="opacity-75 small">Kondisi Sistem</span>
                        <span class="badge bg-success">Baik</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75 small">Stok Maksimal Tersedia</span>
                        <span class="fw-bold fs-5 text-fik-orange"><?= $aset->jumlah_tersedia ?> Unit</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8" data-aos="fade-left">
                <div class="card form-card p-4 p-md-5">
                    <form id="borrowingRequestForm" action="<?= base_url('index.php/peminjaman/proses_pengajuan') ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_aset" value="<?= $aset->id_aset ?>">

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Peminjam</label>
                                <input type="text" class="form-control bg-light" value="<?= $display_nama; ?>" readonly>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="form-label fw-semibold text-muted small">Jumlah Pinjam <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah_pinjam" class="form-control" min="1" max="<?= $aset->jumlah_tersedia ?>" value="1" required>
                                <small class="text-danger" style="font-size: 0.7rem;">Maksimal <?= $aset->jumlah_tersedia ?> unit</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-7">
                                <label for="prodi" class="form-label fw-semibold text-muted small">Program Studi <span class="text-danger">*</span></label>
                                <?php if ($user_prodi): ?>
                                    <input type="text" id="prodi" class="form-control bg-light" value="<?= html_escape($user_prodi) ?>" readonly>
                                <?php else: ?>
                                    <select name="prodi" id="prodi" class="form-select" required>
                                        <option value="" selected disabled>Pilih program studi</option>
                                        <?php foreach ($program_studi as $program): ?>
                                            <option value="<?= html_escape($program) ?>"><?= html_escape($program) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label for="jenis_pengguna" class="form-label fw-semibold text-muted small">Status Peminjam <span class="text-danger">*</span></label>
                                <?php if ($user_jenis): ?>
                                    <input type="text" id="jenis_pengguna" class="form-control bg-light" value="<?= html_escape($user_jenis) ?>" readonly>
                                <?php else: ?>
                                    <select name="jenis_pengguna" id="jenis_pengguna" class="form-select" required>
                                        <option value="" selected disabled>Pilih status</option>
                                        <?php foreach ($jenis_peminjam_options as $jenis): ?>
                                            <option value="<?= html_escape($jenis) ?>"><?= html_escape($jenis) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <?php if (!$user_prodi || !$user_jenis): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning py-2 px-3 mb-0 small"><i class="bi bi-info-circle me-1"></i>Data ini disimpan ke akun Anda untuk menentukan Kaprodi yang berwenang.</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4 borrowing-date-range" data-borrowing-date-range data-min-date="<?= date('Y-m-d') ?>">
                            <div class="borrowing-date-range__enhanced">
                                <label class="form-label fw-semibold text-muted small mb-2"><i class="bi bi-calendar-range me-1"></i> Jadwal Peminjaman <span class="text-danger">*</span></label>
                                <button type="button" class="borrowing-date-range__trigger" data-date-range-trigger aria-expanded="false" aria-controls="borrowDateRangeCalendar">
                                    <span class="borrowing-date-range__date">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><small>Mulai</small><strong data-date-range-start>Pilih tanggal</strong></span>
                                    </span>
                                    <span class="borrowing-date-range__arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                                    <span class="borrowing-date-range__date">
                                        <i class="bi bi-calendar-check"></i>
                                        <span><small>Selesai</small><strong data-date-range-end>Pilih tanggal</strong></span>
                                    </span>
                                    <i class="bi bi-chevron-down borrowing-date-range__chevron" aria-hidden="true"></i>
                                </button>

                                <section id="borrowDateRangeCalendar" class="borrowing-date-range__panel" data-date-range-panel hidden aria-label="Pilih rentang tanggal peminjaman">
                                    <div class="borrowing-date-range__panel-header">
                                        <div>
                                            <span class="borrowing-date-range__eyebrow">Pilih jadwal</span>
                                            <p data-date-range-helper>Pilih tanggal pengambilan terlebih dahulu.</p>
                                        </div>
                                        <div class="borrowing-date-range__navigation">
                                            <button type="button" class="borrowing-date-range__nav" data-date-range-prev aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button>
                                            <button type="button" class="borrowing-date-range__nav" data-date-range-next aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                    <div class="borrowing-date-range__months" data-date-range-months></div>
                                    <div class="borrowing-date-range__panel-footer"><i class="bi bi-info-circle"></i> Klik tanggal satu per satu, atau tahan lalu drag untuk memilih rentang. Tanggal yang sudah lewat tidak dapat dipilih.</div>
                                </section>
                                <div class="borrowing-date-range__feedback" data-date-range-feedback aria-live="polite"></div>
                            </div>

                            <div class="row g-3 borrowing-date-range__native-fallback">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small"><i class="bi bi-calendar-event me-1"></i> Tanggal Pengambilan <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_pinjam" class="form-control" min="<?= date('Y-m-d') ?>" required data-date-range-start-input>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small"><i class="bi bi-calendar-check me-1"></i> Rencana Kembali <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_kembali_rencana" class="form-control" min="<?= date('Y-m-d') ?>" required data-date-range-end-input>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 bg-fik-orange-light p-3 rounded-3 border border-warning border-opacity-25">
                            <label class="form-label fw-bold text-fik-brown"><i class="bi bi-camera-fill me-1"></i> Foto Kondisi Awal Alat <span class="text-danger">*</span></label>

                            <div class="d-flex flex-column flex-sm-row gap-2 mt-2 capture-buttons">
                                <button type="button" class="btn btn-outline-secondary flex-fill" id="btnGaleriKondisi">
                                    <i class="bi bi-images me-1"></i> Pilih dari Galeri
                                </button>
                                <button type="button" class="btn btn-outline-secondary flex-fill" id="btnKameraKondisi">
                                    <i class="bi bi-camera me-1"></i> Buka Kamera
                                </button>
                            </div>
                            <div class="small text-muted mt-2">Pilih satu foto dari galeri, drag ke area di bawah, atau ambil langsung menggunakan kamera.</div>

                            <div class="drop-zone shadow-sm bg-white mt-2 mb-2" id="dropZone">
                                <input type="file" name="foto_kondisi" id="fileInput" accept="image/jpeg,image/png,image/jpg" required>

                                <div id="previewContainer" class="preview-container d-none">
                                    <div class="preview-wrapper">
                                        <img id="imagePreview" src="#" data-default-src="#" alt="Preview" class="img-thumbnail shadow-sm mb-2" style="max-height: 160px; border-radius: 8px;">
                                        <button type="button" id="btnRemovePreview" class="btn-remove-preview" title="Batal Pilih Foto">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <p class="small text-muted mb-0 fw-medium" id="fileName" data-default-text=""></p>
                                </div>

                                <div id="placeholderContainer" class="preview-container d-block">
                                    <i class="bi bi-camera display-4 text-secondary mb-3 d-block"></i>
                                    <h6 class="mb-1 text-dark fs-6"><span class="fw-bold">Pilih file</span> atau drag ke sini.</h6>
                                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Format: JPG/PNG. Maksimal ukuran file: 2MB. Pastikan foto memperlihatkan kelengkapan alat.</small>
                                </div>
                            </div>

                            <div class="camera-overlay d-none" id="cameraOverlayKondisi" aria-hidden="true">
                                <div class="camera-box" role="dialog" aria-modal="true" aria-label="Ambil foto kondisi awal alat">
                                    <video id="cameraVideoKondisi" autoplay playsinline muted></video>
                                    <div class="camera-controls">
                                        <button type="button" class="btn btn-submit px-4" id="btnJepretKondisi"><i class="bi bi-camera-fill me-1"></i> Jepret Foto</button>
                                        <button type="button" class="btn btn-outline-light px-4" id="btnTutupKameraKondisi">Tutup Kamera</button>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label fw-semibold text-muted small mt-3">Sesuai pengamatan fisik, kondisi saat ini:</label>
                            <select name="kondisi_saat_pinjam" class="form-select bg-white" required>
                                <option value="Baik">Baik & Lengkap</option>
                                <option value="Rusak Ringan">Ada Cacat/Goresan (Berfungsi Normal)</option>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold text-muted small">Keperluan / Keterangan Proyek <span class="text-danger">*</span></label>
                            <textarea name="keperluan" class="form-control" rows="3" placeholder="Contoh: Digunakan untuk shooting Tugas Akhir film pendek di luar kampus..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-4">
                            <a href="<?= base_url('index.php/peminjaman') ?>" class="text-decoration-none text-muted fw-semibold hover-orange">
                                <i class="bi bi-arrow-left me-1"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-submit px-4 px-md-5">
                                <i class="bi bi-send-check me-2"></i>Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?= base_url('assets/js/borrowing-date-range.js'); ?>?v=<?= @filemtime(FCPATH . 'assets/js/borrowing-date-range.js'); ?>"></script>
    <script>AOS.init({ once: true, offset: 20 });</script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('fileInput');
            const previewContainer = document.getElementById('previewContainer');
            const placeholderContainer = document.getElementById('placeholderContainer');
            const imagePreview = document.getElementById('imagePreview');
            const fileNameDisplay = document.getElementById('fileName');
            const btnRemovePreview = document.getElementById('btnRemovePreview');
            const btnGaleri = document.getElementById('btnGaleriKondisi');
            const btnKamera = document.getElementById('btnKameraKondisi');
            const cameraOverlay = document.getElementById('cameraOverlayKondisi');
            const cameraVideo = document.getElementById('cameraVideoKondisi');
            const btnJepret = document.getElementById('btnJepretKondisi');
            const btnTutupKamera = document.getElementById('btnTutupKameraKondisi');
            let cameraStream = null;

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('model-viewer[auto-rotate]').forEach(function (model) {
                    model.removeAttribute('auto-rotate');
                });
            }

            document.querySelectorAll('[data-asset-showcase]').forEach(function (showcase) {
                const slides = Array.from(showcase.querySelectorAll('[data-asset-showcase-slide]'));
                const dots = Array.from(showcase.parentElement.querySelectorAll('[data-asset-showcase-dot]'));
                const previous = showcase.querySelector('[data-asset-showcase-previous]');
                const next = showcase.querySelector('[data-asset-showcase-next]');
                const badge = showcase.querySelector('[data-asset-showcase-type]');
                const counter = showcase.querySelector('[data-asset-showcase-counter]');
                const hint = showcase.parentElement.querySelector('[data-asset-showcase-hint]');
                if (!slides.length) return;

                let activeIndex = 0;
                let swipeStart = null;

                function showMedia(index) {
                    activeIndex = (index + slides.length) % slides.length;
                    slides.forEach(function (slide, slideIndex) {
                        const active = slideIndex === activeIndex;
                        slide.classList.toggle('is-active', active);
                        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                    });
                    dots.forEach(function (dot, dotIndex) {
                        const active = dotIndex === activeIndex;
                        dot.classList.toggle('is-active', active);
                        dot.setAttribute('aria-current', active ? 'true' : 'false');
                    });

                    const mediaType = slides[activeIndex].dataset.mediaType || 'Media aset';
                    if (badge) {
                        badge.innerHTML = '<i class="bi ' + (mediaType.indexOf('3D') === 0 ? 'bi-badge-3d' : 'bi-image') + '"></i> ' + mediaType;
                    }
                    if (counter) counter.textContent = (activeIndex + 1) + ' / ' + slides.length;
                    if (hint) {
                        hint.innerHTML = mediaType.indexOf('3D') === 0
                            ? '<i class="bi bi-mouse"></i> Drag model untuk melihat dari berbagai sudut'
                            : '<i class="bi bi-arrow-left-right"></i> Geser atau gunakan tombol untuk melihat media lain';
                    }
                }

                if (previous) previous.addEventListener('click', function () { showMedia(activeIndex - 1); });
                if (next) next.addEventListener('click', function () { showMedia(activeIndex + 1); });
                dots.forEach(function (dot) {
                    dot.addEventListener('click', function () { showMedia(Number(dot.dataset.assetShowcaseDot)); });
                });
                showcase.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowLeft') { event.preventDefault(); showMedia(activeIndex - 1); }
                    if (event.key === 'ArrowRight') { event.preventDefault(); showMedia(activeIndex + 1); }
                });
                showcase.addEventListener('pointerdown', function (event) {
                    if (event.target.closest('button, model-viewer')) return;
                    swipeStart = { id: event.pointerId, x: event.clientX, y: event.clientY };
                });
                showcase.addEventListener('pointerup', function (event) {
                    if (!swipeStart || swipeStart.id !== event.pointerId) return;
                    const deltaX = event.clientX - swipeStart.x;
                    const deltaY = event.clientY - swipeStart.y;
                    swipeStart = null;
                    if (Math.abs(deltaX) > 42 && Math.abs(deltaX) > Math.abs(deltaY)) {
                        showMedia(activeIndex + (deltaX < 0 ? 1 : -1));
                    }
                });
                showcase.addEventListener('pointercancel', function () { swipeStart = null; });
                showMedia(0);
            });

            // Ambil data default foto lama (jika sedang di form edit)
            const defaultSrc = imagePreview ? imagePreview.getAttribute('data-default-src') : '#';
            const defaultText = fileNameDisplay ? fileNameDisplay.getAttribute('data-default-text') : '';

            if (dropZone && fileInput) {
                if (btnGaleri) {
                    btnGaleri.addEventListener('click', function() {
                        fileInput.removeAttribute('capture');
                        fileInput.click();
                    });
                }

                // Mencegah browser membuka file gambar di tab baru saat di-drag
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Memberikan efek animasi transisi saat file disorot/drag di atas kotak
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
                });

                // Menangkap event DROP dan mengisi input file secara otomatis
                dropZone.addEventListener('drop', function(e) {
                    let dt = e.dataTransfer;
                    let files = dt.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        updatePreview(files[0]);
                    }
                }, false);

                // Menangkap event CLICK 
                fileInput.addEventListener('change', function() {
                    this.removeAttribute('capture');
                    if (this.files && this.files[0]) {
                        updatePreview(this.files[0]);
                    }
                });

                function updatePreview(file) {
                    // Validasi bahwa file adalah gambar
                    if (!file.type.match('image.*')) {
                        alert('Format file ditolak! Gunakan gambar JPG atau PNG.');
                        fileInput.value = ''; // Reset input
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran foto melebihi 2MB. Silakan pilih atau ambil ulang foto dengan ukuran lebih kecil.');
                        fileInput.value = '';
                        return;
                    }

                    let reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        fileNameDisplay.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> File dipilih: <b class="text-dark">${file.name}</b>`;
                        
                        previewContainer.classList.remove('d-none');
                        previewContainer.classList.add('d-block');
                        placeholderContainer.classList.remove('d-block');
                        placeholderContainer.classList.add('d-none');
                    }
                }

                // Logika Dinamis saat tombol X diklik
                if(btnRemovePreview) {
                    btnRemovePreview.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); // Mencegah form upload terbuka

                        // Selalu kosongkan input file
                        fileInput.value = '';
                        
                        // Kembalikan ke placeholder awan
                        imagePreview.src = '#';
                        fileNameDisplay.innerHTML = '';
                        previewContainer.classList.remove('d-block');
                        previewContainer.classList.add('d-none');
                        placeholderContainer.classList.remove('d-none');
                        placeholderContainer.classList.add('d-block');
                    });
                }

                async function openCamera() {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        fileInput.setAttribute('capture', 'environment');
                        fileInput.click();
                        return;
                    }

                    try {
                        cameraStream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'environment' } },
                            audio: false
                        });
                        cameraVideo.srcObject = cameraStream;
                        cameraOverlay.classList.remove('d-none');
                        cameraOverlay.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                        btnJepret.focus();
                    } catch (error) {
                        alert('Kamera tidak dapat diakses. Pastikan izin kamera aktif dan halaman dibuka melalui HTTPS atau localhost. Anda tetap dapat memilih foto dari galeri.');
                    }
                }

                function closeCamera() {
                    if (cameraStream) {
                        cameraStream.getTracks().forEach(function(track) { track.stop(); });
                        cameraStream = null;
                    }
                    cameraVideo.srcObject = null;
                    cameraOverlay.classList.add('d-none');
                    cameraOverlay.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    if (btnKamera) btnKamera.focus();
                }

                function takeCameraPhoto() {
                    if (!cameraStream || !cameraVideo.videoWidth || !cameraVideo.videoHeight) {
                        alert('Kamera belum siap. Tunggu sebentar lalu coba jepret kembali.');
                        return;
                    }

                    const maxDimension = 1280;
                    const scale = Math.min(1, maxDimension / Math.max(cameraVideo.videoWidth, cameraVideo.videoHeight));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(cameraVideo.videoWidth * scale));
                    canvas.height = Math.max(1, Math.round(cameraVideo.videoHeight * scale));
                    canvas.getContext('2d').drawImage(cameraVideo, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            alert('Foto gagal diproses. Silakan coba kembali.');
                            return;
                        }
                        if (blob.size > 2 * 1024 * 1024) {
                            alert('Hasil foto masih melebihi 2MB. Silakan coba kembali.');
                            return;
                        }

                        const cameraFile = new File([blob], 'kondisi-awal-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        const transfer = new DataTransfer();
                        transfer.items.add(cameraFile);
                        fileInput.files = transfer.files;
                        updatePreview(cameraFile);
                        closeCamera();
                    }, 'image/jpeg', 0.78);
                }

                if (btnKamera) btnKamera.addEventListener('click', openCamera);
                if (btnJepret) btnJepret.addEventListener('click', takeCameraPhoto);
                if (btnTutupKamera) btnTutupKamera.addEventListener('click', closeCamera);
                if (cameraOverlay) {
                    cameraOverlay.addEventListener('click', function(event) {
                        if (event.target === cameraOverlay) closeCamera();
                    });
                }
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && cameraOverlay && !cameraOverlay.classList.contains('d-none')) closeCamera();
                });
                window.addEventListener('pagehide', closeCamera);
            }
        });
    </script>
</body>
</html>
