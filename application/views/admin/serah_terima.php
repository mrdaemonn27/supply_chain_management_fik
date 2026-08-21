<?php
$boleh_serah = !empty($qr_valid) && ($peminjaman->status ?? '') === 'Disetujui (Menunggu Pengambilan)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Serah Terima') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6f8; font-family: Arial, sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .panel-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 10px 24px rgba(0,0,0,.06); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .jumlah-input { max-width: 90px; margin-left: auto; }
        .preview-thumb { position: relative; }
        .preview-thumb img { height: 90px; width: 100%; object-fit: cover; }
        .preview-remove {
            position: absolute; top: 4px; right: 4px; width: 24px; height: 24px;
            border-radius: 50%; background: rgba(0,0,0,.6); color: #fff; border: 0;
            display: flex; align-items: center; justify-content: center; padding: 0; line-height: 1;
        }
        .preview-remove:hover { background: rgba(0,0,0,.85); }
        @media (max-width: 576px) {
            .capture-buttons .btn { font-size: .9rem; }
        }
        .camera-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 1080;
            display: flex; align-items: center; justify-content: center; padding: 1rem;
        }
        .camera-box { background: #000; border-radius: 12px; overflow: hidden; width: 100%; max-width: 480px; }
        .camera-box video { width: 100%; max-height: 60vh; background: #000; display: block; }
        .camera-controls { background: #111; padding: .75rem; display: flex; gap: .5rem; justify-content: center; }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center gap-2">
            <div class="fw-bold"><i class="bi bi-box-seam me-2 text-warning"></i>Serah Terima Barang</div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">Scanner</a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
        <section class="panel-card p-3 p-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <div>
                    <div class="small text-muted">ID Transaksi</div>
                    <h1 class="h4 fw-bold mb-1"><?= html_escape($peminjaman->group_id) ?></h1>
                    <div><?= html_escape($peminjaman->nama_peminjam ?? '-') ?> - <?= html_escape($peminjaman->nim_nip ?? '-') ?></div>
                </div>
                <span class="badge <?= $boleh_serah ? 'text-bg-success' : 'text-bg-warning' ?> align-self-start"><?= html_escape($peminjaman->status ?? '-') ?></span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="small text-muted">Tanggal Pinjam</div><div class="fw-semibold"><?= html_escape($peminjaman->tanggal_pinjam ?? '-') ?></div></div>
                <div class="col-md-4"><div class="small text-muted">Rencana Kembali</div><div class="fw-semibold"><?= html_escape($peminjaman->tanggal_kembali_rencana ?? '-') ?></div></div>
                <div class="col-md-4"><div class="small text-muted">Keperluan</div><div class="fw-semibold"><?= html_escape($peminjaman->keperluan ?? '-') ?></div></div>
            </div>

            <?php if($boleh_serah): ?>
                <form method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/proses_serah/'.rawurlencode($peminjaman->group_id)) ?>">

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light"><tr><th>Barang</th><th>Kode</th><th>Ruangan</th><th class="text-end" style="width:140px;">Jumlah Diserahkan</th></tr></thead>
                            <tbody>
                                <?php foreach(($peminjaman->detail_barang ?? []) as $item): ?>
                                    <?php $jumlah_pinjam = (int)($item->jumlah_pinjam ?? 0); ?>
                                    <tr>
                                        <td><?= html_escape($item->nama_aset ?? '-') ?></td>
                                        <td><?= html_escape($item->kode_aset ?? '-') ?></td>
                                        <td><?= html_escape($item->nama_ruangan ?? '-') ?></td>
                                        <td class="text-end">
                                            <input type="number"
                                                   name="jumlah_barang[<?= html_escape($item->kode_aset ?? '') ?>]"
                                                   value="<?= $jumlah_pinjam ?>"
                                                   min="0"
                                                   max="<?= $jumlah_pinjam ?>"
                                                   class="form-control form-control-sm text-end jumlah-input"
                                                   data-max="<?= $jumlah_pinjam ?>"
                                                   required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="small text-muted">Jumlah bisa dikurangi jika barang yang diserahkan tidak sesuai jumlah pengajuan awal.</div>
                    </div>

                    <label class="form-label small fw-semibold">Catatan Serah Terima</label>
                    <textarea name="catatan_serah" class="form-control mb-3" rows="2" placeholder="Contoh: Barang lengkap dan diterima peminjam."></textarea>

                    <label class="form-label small fw-semibold">Dokumentasi Kondisi Saat Serah Terima</label>
                    <div class="d-flex flex-column flex-sm-row gap-2 mb-2 capture-buttons">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="btnGaleriSerah"><i class="bi bi-images me-1"></i> Pilih dari Galeri</button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="btnKameraSerah"><i class="bi bi-camera me-1"></i> Buka Kamera</button>
                    </div>
                    <input type="file" id="fotoSerahInput" class="d-none" accept="image/*" multiple>
                    <div class="small text-muted mb-2">Bisa memilih beberapa foto sekaligus dari galeri, atau ambil langsung lewat kamera. Maksimal 5MB per foto.</div>
                    <div class="row g-2 serah-preview mb-3" id="serahPreview"></div>

                    <div class="camera-overlay d-none" id="cameraOverlaySerah">
                        <div class="camera-box">
                            <video id="cameraVideoSerah" autoplay playsinline></video>
                            <div class="camera-controls">
                                <button type="button" class="btn btn-fik rounded-pill px-4" id="btnJepretSerah"><i class="bi bi-camera-fill me-1"></i> Jepret</button>
                                <button type="button" class="btn btn-outline-light rounded-pill px-4" id="btnTutupKameraSerah">Tutup</button>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-fik rounded-pill px-4" onclick="return confirm('Serahkan barang ke peminjam dan kurangi stok?')"><i class="bi bi-check2-circle me-1"></i> Serahkan Barang ke Peminjam</button>
                </form>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light"><tr><th>Barang</th><th>Kode</th><th>Ruangan</th><th class="text-end">Jumlah</th></tr></thead>
                        <tbody>
                            <?php foreach(($peminjaman->detail_barang ?? []) as $item): ?>
                                <tr>
                                    <td><?= html_escape($item->nama_aset ?? '-') ?></td>
                                    <td><?= html_escape($item->kode_aset ?? '-') ?></td>
                                    <td><?= html_escape($item->nama_ruangan ?? '-') ?></td>
                                    <td class="text-end"><?= (int)($item->jumlah_pinjam ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning mb-0"><?= html_escape($qr_message ?? 'QR terbaca, tetapi transaksi belum berada pada status siap serah.') ?></div>
            <?php endif; ?>
        </section>
    </main>

    <script>
    (function () {
        // Batasi jumlah input agar tidak melebihi jumlah pinjam awal
        document.querySelectorAll('.jumlah-input').forEach((input) => {
            input.addEventListener('input', () => {
                const max = parseInt(input.dataset.max || '0', 10);
                let val = parseInt(input.value || '0', 10);
                if (isNaN(val) || val < 0) val = 0;
                if (val > max) val = max;
                input.value = val;
            });
        });

        // Upload bukti: gabungan tombol galeri + kamera ke satu kumpulan file
        const fileInput = document.getElementById('fotoSerahInput');
        const btnGaleri = document.getElementById('btnGaleriSerah');
        const btnKamera = document.getElementById('btnKameraSerah');
        const preview = document.getElementById('serahPreview');
        const form = fileInput ? fileInput.closest('section').querySelector('form') : null;
        let selectedFiles = [];

        if (!fileInput) return;

        function syncFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach((f) => dt.items.add(f));
            fileInput.files = dt.files;
        }

        function renderPreview() {
            preview.innerHTML = '';
            selectedFiles.forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';
                    col.innerHTML = `
                        <div class="preview-thumb rounded-3 border overflow-hidden">
                            <img src="${event.target.result}" alt="Preview dokumentasi">
                            <button type="button" class="preview-remove" data-idx="${idx}" aria-label="Hapus foto">&times;</button>
                        </div>`;
                    preview.appendChild(col);
                    col.querySelector('.preview-remove').addEventListener('click', () => {
                        selectedFiles.splice(idx, 1);
                        syncFileInput();
                        renderPreview();
                    });
                };
                reader.readAsDataURL(file);
            });
        }

        btnGaleri.addEventListener('click', () => {
            fileInput.removeAttribute('capture');
            fileInput.setAttribute('multiple', 'multiple');
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            Array.from(fileInput.files || []).forEach((file) => {
                if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024) {
                    selectedFiles.push(file);
                }
            });
            syncFileInput();
            renderPreview();
        });

        // Kamera langsung di halaman (video live) - lebih andal daripada atribut capture,
        // karena capture pada input file hanya jalan di sebagian browser mobile dan
        // diabaikan total di desktop.
        const overlay = document.getElementById('cameraOverlaySerah');
        const video = document.getElementById('cameraVideoSerah');
        const btnJepret = document.getElementById('btnJepretSerah');
        const btnTutupKamera = document.getElementById('btnTutupKameraSerah');
        let cameraStream = null;

        async function openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Kamera tidak didukung di browser ini. Gunakan tombol "Pilih dari Galeri" sebagai gantinya.');
                return;
            }
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                video.srcObject = cameraStream;
                overlay.classList.remove('d-none');
            } catch (err) {
                alert('Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan dan halaman diakses lewat HTTPS (atau localhost). Detail: ' + err.message);
            }
        }

        function closeCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach((track) => track.stop());
                cameraStream = null;
            }
            overlay.classList.add('d-none');
        }

        function ambilFoto() {
            if (!cameraStream) return;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) return;
                const file = new File([blob], `bukti-serah-${Date.now()}.jpg`, { type: 'image/jpeg' });
                selectedFiles.push(file);
                syncFileInput();
                renderPreview();
            }, 'image/jpeg', 0.9);
        }

        btnKamera.addEventListener('click', openCamera);
        btnJepret.addEventListener('click', ambilFoto);
        btnTutupKamera.addEventListener('click', closeCamera);

        // Pastikan nama field terkirim sebagai array foto_serah[]
        if (form) {
            form.addEventListener('submit', () => {
                fileInput.name = 'foto_serah[]';
            });
        }
    })();
    </script>
</body>
</html>
