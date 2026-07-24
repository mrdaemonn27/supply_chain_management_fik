<?php
$boleh_kembali = in_array(($peminjaman->status ?? ''), ['Sedang Dipinjam', 'Dipinjam'], true);
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
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i>Validasi Pengembalian</div>
                <div class="small text-white-50">Scan QR pengembalian dari akun peminjam</div>
            </div>
            <a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">Data Peminjaman</a>
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

            <?php if($boleh_kembali): ?>
                <form method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/kembalikan/'.$peminjaman->id_peminjaman) ?>">
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
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Upload Galeri/Dokumen</label>
                            <input type="file" name="foto_pengembalian" class="form-control return-file" accept=".jpg,.jpeg,.png,.pdf,image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Ambil Foto Kamera HP</label>
                            <input type="file" name="foto_pengembalian_camera" class="form-control return-file" accept="image/*" capture="environment">
                        </div>
                    </div>
                    <button class="btn btn-fik rounded-pill px-4 mt-3" onclick="return confirm('Konfirmasi barang sudah diterima kembali?')"><i class="bi bi-check2-circle me-1"></i> Terima Pengembalian</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0">QR terbaca, tetapi transaksi belum berstatus sedang dipinjam atau sudah selesai dikembalikan.</div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const condition = form.querySelector('.return-condition')?.value;
                const note = form.querySelector('.return-note')?.value.trim();
                const hasFile = Array.from(form.querySelectorAll('.return-file')).some((input) => input.files && input.files.length);
                if ((condition === 'Rusak' || condition === 'Hilang') && (!note || !hasFile)) {
                    event.preventDefault();
                    alert('Untuk kondisi Rusak atau Hilang, catatan dan evidence wajib diisi.');
                }
            });
        });
    </script>
</body>
</html>
