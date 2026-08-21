<?php
$boleh_kembali = !empty($qr_valid) && in_array(($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Validasi Pengembalian') ?></title>
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
            <div>
                <div class="fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Validasi Pengembalian</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('index.php/admin/pengembalian') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">Data Pengembalian</a>
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
                <span class="badge <?= $boleh_kembali ? 'text-bg-primary' : 'text-bg-warning' ?> align-self-start"><?= html_escape($peminjaman->status ?? '-') ?></span>
            </div>

            <?php if($boleh_kembali): ?>
                <form method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/kembalikan/'.$peminjaman->id_peminjaman) ?>">
                    <input type="hidden" name="return_to" value="admin/pengembalian">
                    <input type="hidden" name="from_qr" value="1">

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light"><tr><th>Barang</th><th>Kode</th><th>Ruangan</th><th class="text-end" style="width:140px;">Jumlah Dikembalikan</th></tr></thead>
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
                        <div class="small text-muted">Jumlah bisa dikurangi jika barang yang dikembalikan tidak lengkap (misal ada yang hilang).</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kondisi Akhir</label>
                            <select name="kondisi_saat_kembali" class="form-select return-condition" required>
                                <option value="Baik">Baik</option>
                                <option value="Rusak">Rusak</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Catatan</label>
                            <input type="text" name="catatan_pengembalian" class="form-control return-note" placeholder="Wajib untuk kondisi Rusak/Hilang">
                        </div>
                    </div>

                    <label class="form-label fw-semibold mt-3">Dokumentasi Bukti Pengembalian</label>
                    <div class="d-flex flex-column flex-sm-row gap-2 mb-2 capture-buttons">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="btnGaleriKembali"><i class="bi bi-images me-1"></i> Pilih dari Galeri</button>
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="btnKameraKembali"><i class="bi bi-camera me-1"></i> Buka Kamera</button>
                    </div>
                    <input type="file" id="fotoKembaliInput" class="d-none" accept="image/*,.pdf" multiple>
                    <div class="small text-muted mb-2">Bisa pilih beberapa foto/dokumen dari galeri, atau ambil langsung lewat kamera. Maksimal 5MB per file.</div>
                    <div class="row g-2 return-preview mb-3" id="kembaliPreview"></div>

                    <div class="camera-overlay d-none" id="cameraOverlayKembali">
                        <div class="camera-box">
                            <video id="cameraVideoKembali" autoplay playsinline></video>
                            <div class="camera-controls">
                                <button type="button" class="btn btn-fik rounded-pill px-4" id="btnJepretKembali"><i class="bi bi-camera-fill me-1"></i> Jepret</button>
                                <button type="button" class="btn btn-outline-light rounded-pill px-4" id="btnTutupKameraKembali">Tutup</button>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-fik rounded-pill px-4 mt-1" onclick="return confirm('Konfirmasi barang sudah diterima kembali?')"><i class="bi bi-check2-circle me-1"></i> Terima Pengembalian</button>
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
                <div class="alert alert-warning mb-0"><?= html_escape($qr_message ?? 'QR terbaca, tetapi transaksi belum berstatus sedang dipinjam atau sudah selesai dikembalikan.') ?></div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        const fileInput = document.getElementById('fotoKembaliInput');
        const btnGaleri = document.getElementById('btnGaleriKembali');
        const btnKamera = document.getElementById('btnKameraKembali');
        const preview = document.getElementById('kembaliPreview');
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
                if (file.type === 'application/pdf') {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';
                    col.innerHTML = `
                        <div class="preview-thumb rounded-3 border d-flex align-items-center justify-content-center bg-light" style="height:90px;">
                            <i class="bi bi-file-earmark-pdf text-danger fs-2"></i>
                            <button type="button" class="preview-remove" data-idx="${idx}" aria-label="Hapus file">&times;</button>
                        </div>`;
                    preview.appendChild(col);
                    col.querySelector('.preview-remove').addEventListener('click', () => {
                        selectedFiles.splice(idx, 1);
                        syncFileInput();
                        renderPreview();
                    });
                    return;
                }
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
            fileInput.setAttribute('accept', 'image/*,.pdf');
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            Array.from(fileInput.files || []).forEach((file) => {
                const isValidType = file.type.startsWith('image/') || file.type === 'application/pdf';
                if (isValidType && file.size <= 5 * 1024 * 1024) {
                    selectedFiles.push(file);
                }
            });
            syncFileInput();
            renderPreview();
        });

        // Kamera langsung di halaman (video live) - lebih andal daripada atribut capture,
        // karena capture pada input file hanya jalan di sebagian browser mobile dan
        // diabaikan total di desktop.
        const overlay = document.getElementById('cameraOverlayKembali');
        const video = document.getElementById('cameraVideoKembali');
        const btnJepret = document.getElementById('btnJepretKembali');
        const btnTutupKamera = document.getElementById('btnTutupKameraKembali');
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
                const file = new File([blob], `bukti-kembali-${Date.now()}.jpg`, { type: 'image/jpeg' });
                selectedFiles.push(file);
                syncFileInput();
                renderPreview();
            }, 'image/jpeg', 0.9);
        }

        btnKamera.addEventListener('click', openCamera);
        btnJepret.addEventListener('click', ambilFoto);
        btnTutupKamera.addEventListener('click', closeCamera);

        // Validasi wajib catatan + bukti untuk kondisi Rusak/Hilang, dan kirim field sebagai foto_pengembalian[]
        if (form) {
            form.addEventListener('submit', (event) => {
                fileInput.name = 'foto_pengembalian[]';
                const condition = form.querySelector('.return-condition')?.value;
                const note = form.querySelector('.return-note')?.value.trim();
                if ((condition === 'Rusak' || condition === 'Hilang') && (!note || selectedFiles.length === 0)) {
                    event.preventDefault();
                    alert('Untuk kondisi Rusak atau Hilang, catatan dan evidence wajib diisi.');
                }
            });
        }
    })();
    </script>
</body>
</html>
