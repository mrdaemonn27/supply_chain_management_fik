<?php
/** @var array $riwayat */
$session_role = strtolower((string) $this->session->userdata('role'));
$display_nama = ($session_role === 'admin') ? 'Laboran' : $this->session->userdata('nama');
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - SCM FIK</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }

        /* Palette FIK */
        .text-fik-orange { color: #ea5b1a !important; }
        .bg-fik-orange { background-color: #ea5b1a !important; }
        .text-fik-brown { color: #5d3315 !important; }

        /* Navbar */
        .navbar-custom { background-color: #ffffff; padding: 12px 0; border-bottom: 2px solid #ea5b1a; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .navbar-dark .navbar-nav .nav-link { color: #333333; font-weight: 500; font-size: 0.95rem; margin: 0 12px; transition: 0.3s; position: relative; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .nav-link.active { color: #ea5b1a; }
        .navbar-dark .navbar-nav .nav-link::after { content: ''; position: absolute; width: 0; height: 2px; display: block; margin-top: 5px; right: 0; background: #ea5b1a; transition: width 0.3s ease; }
        .navbar-dark .navbar-nav .nav-link:hover::after { width: 100%; left: 0; background: #ea5b1a; }
        .btn-user { background: linear-gradient(45deg, #c24a13, #ea5b1a); color: white; font-weight: 600; border: none; border-radius: 8px; padding: 8px 20px; }
        .notif-bell { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }

        /* Custom Table Styling */
        .table-custom { border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table-custom thead th { background-color: #5d3315; color: white; font-weight: 500; border: none; padding: 15px; letter-spacing: 0.5px;}
        .table-custom tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #eee; background: white; }
        .table-custom tbody tr:hover td { background-color: #fafafa; }
        
        .badge-status { padding: 8px 12px; border-radius: 6px; font-weight: 500; font-size: 0.8rem;}
    </style>
</head>
<body>

    <!-- NAVBAR -->
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
                    <li class="nav-item"><a class="nav-link" href="#" onclick="alert('Pilih barang dari Katalog terlebih dahulu.'); return false;">Ajukan Peminjaman</a></li>
                    <!-- INI YANG AKTIF -->
                    <li class="nav-item"><a class="nav-link active" href="<?= base_url('index.php/peminjaman/riwayat') ?>">Riwayat</a></li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
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

    <!-- CONTENT -->
    <div class="container py-5">
        <div class="mb-4 text-center" data-aos="fade-down">
            <h2 class="fw-bold text-dark">RIWAYAT <span class="text-fik-orange">PEMINJAMAN</span></h2>
            <p class="text-muted">Pantau status pengajuan dan akses QR Code bukti persetujuan Anda.</p>
        </div>

        <!-- Notifikasi Sukses -->
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center rounded-3 mb-4" data-aos="zoom-in">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div><?= $this->session->flashdata('success'); ?></div>
            </div>
        <?php endif; ?>

        <!-- Tabel Riwayat -->
        <div class="table-responsive" data-aos="fade-up">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th>Tgl Pengajuan</th>
                        <th>Aset Studio</th>
                        <th>Masa Pinjam</th>
                        <th>Status Approval</th>
                        <th class="text-center">Aksi / Tiket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($riwayat)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-folder2-open text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">Belum ada riwayat peminjaman.</p>
                                <a href="<?= base_url('index.php/peminjaman') ?>" class="btn btn-sm btn-outline-secondary mt-2">Buka Katalog</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($r->created_at)) ?></div>
                                <div class="text-muted small"><?= date('H:i', strtotime($r->created_at)) ?> WIB</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $r->nama_aset ?></div>
                                <div class="text-muted small">Kode: <?= $r->kode_aset ?> &bull; Jml: <span class="text-fik-orange fw-bold"><?= $r->jumlah_pinjam ?></span></div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-box-arrow-in-right text-success me-1"></i> <?= date('d/m/Y', strtotime($r->tanggal_pinjam)) ?></div>
                                <div class="small"><i class="bi bi-box-arrow-left text-danger me-1"></i> <?= date('d/m/Y', strtotime($r->tanggal_kembali_rencana)) ?></div>
                            </td>
                            <td>
                                <?php 
                                    // Logika Pewarnaan Badge Status
                                    if(strpos($r->status, 'Menunggu') !== false || strpos($r->status, 'Pending') !== false) {
                                        echo '<span class="badge bg-warning text-dark badge-status"><i class="bi bi-hourglass-split me-1"></i> '.$r->status.'</span>';
                                    } elseif($r->status == 'Disetujui (Menunggu Pengambilan)') {
                                        echo '<span class="badge bg-success badge-status"><i class="bi bi-qr-code-scan me-1"></i> QR Aktif</span>';
                                    } elseif($r->status == 'Sedang Dipinjam' || $r->status == 'Dipinjam') {
                                        echo '<span class="badge bg-primary badge-status"><i class="bi bi-play-circle-fill me-1"></i> Sedang Dipinjam</span>';
                                    } elseif($r->status == 'Dikembalikan') {
                                        echo '<span class="badge bg-success badge-status"><i class="bi bi-check2-circle me-1"></i> Dikembalikan</span>';
                                    } elseif($r->status == 'Ditolak') {
                                        echo '<span class="badge bg-danger badge-status"><i class="bi bi-x-circle-fill me-1"></i> Ditolak</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary badge-status">'.$r->status.'</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if(in_array($r->status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam'], true)): ?>
                                    <button class="btn btn-sm btn-outline-dark fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#qrModal<?= $r->id_peminjaman ?>">
                                        <i class="bi bi-qr-code-scan me-1"></i> Tampilkan QR
                                    </button>
                                <?php else: ?>
                                    <span class="small text-muted">QR belum aktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TIKET QR CODE (DIPINDAHKAN KELUAR DARI TABLE AGAR TIDAK BUG/KEPOTONG) -->
    <?php if(!empty($riwayat)): ?>
        <?php foreach($riwayat as $r): ?>
        <?php if(!in_array($r->status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam'], true)) { continue; } ?>
        <div class="modal fade" id="qrModal<?= $r->id_peminjaman ?>" tabindex="-1" aria-labelledby="qrModalLabel<?= $r->id_peminjaman ?>" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content text-center p-4 border-0 shadow-lg" style="border-radius: 20px;">
                    <h5 class="fw-bold text-fik-orange mb-1" id="qrModalLabel<?= $r->id_peminjaman ?>">E-Ticket Laboratorium</h5>
                    <p class="small text-muted mb-4">Tunjukkan kode ini kepada Laboran saat serah terima barang.</p>
                    
                    <!-- QR berisi link serah terima untuk scanner Laboran -->
                    <div class="bg-white p-3 rounded-4 mb-3 mx-auto shadow-sm border" style="display: inline-block;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode(site_url('admin/peminjaman/serah_terima/'.rawurlencode($r->group_id))) ?>" alt="QR Code" class="img-fluid">
                    </div>
                    
                    <div class="font-monospace fs-6 fw-bold bg-light border px-3 py-2 rounded-3 text-secondary mb-3">
                        <?= $r->group_id ?>
                    </div>

                    <div class="alert alert-info py-2 small mb-4 text-start">
                        <strong>Barang:</strong> <?= $r->nama_aset ?><br>
                        <strong>Status:</strong> <?= $r->status ?>
                    </div>
                    
                    <button type="button" class="btn btn-secondary w-100 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-center py-4 mt-5">
        <div class="container">
            <p class="small text-white opacity-50 m-0">
                &copy; <?= date('Y') ?> SCM Fakultas Industri Kreatif - Telkom University. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init({ once: true, offset: 20 });</script>
</body>
</html>
