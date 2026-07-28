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
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center gap-2">
            <div><div class="fw-bold"><i class="bi bi-box-seam me-2 text-warning"></i>Serah Terima Barang</div><div class="small text-white-50">Validasi detail sebelum barang diserahkan</div></div>
            <a href="<?= base_url('index.php/admin/peminjaman/scanner') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">Scanner</a>
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

            <?php if($boleh_serah): ?>
                <form method="post" enctype="multipart/form-data" action="<?= base_url('index.php/admin/peminjaman/proses_serah/'.rawurlencode($peminjaman->group_id)) ?>">
                    <label class="form-label small fw-semibold">Catatan Serah Terima</label>
                    <textarea name="catatan_serah" class="form-control mb-3" rows="2" placeholder="Contoh: Barang lengkap dan diterima peminjam."></textarea>
                    <label class="form-label small fw-semibold">Dokumentasi Kondisi Saat Serah Terima</label>
                    <input type="file" name="foto_serah[]" class="form-control serah-file mb-2" accept="image/*,.jpg,.jpeg,.png" capture="environment" multiple>
                    <div class="small text-muted mb-2">Bisa memilih beberapa foto. Maksimal 5MB per foto.</div>
                    <div class="row g-2 serah-preview mb-3"></div>
                    <button class="btn btn-fik rounded-pill px-4" onclick="return confirm('Serahkan barang ke peminjam dan kurangi stok?')"><i class="bi bi-check2-circle me-1"></i> Serahkan Barang ke Peminjam</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0"><?= html_escape($qr_message ?? 'QR terbaca, tetapi transaksi belum berada pada status siap serah.') ?></div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        document.querySelectorAll('.serah-file').forEach((input) => {
            input.addEventListener('change', () => {
                const preview = input.closest('form')?.querySelector('.serah-preview');
                if (!preview) return;
                preview.innerHTML = '';
                Array.from(input.files || []).forEach((file) => {
                    if (!file.type.startsWith('image/') || file.size > 5 * 1024 * 1024) return;
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const item = document.createElement('div');
                        item.className = 'col-6 col-md-3';
                        item.innerHTML = '<img src="' + event.target.result + '" alt="Preview dokumentasi" class="img-fluid rounded-3 border" style="height:90px;width:100%;object-fit:cover">';
                        preview.appendChild(item);
                    };
                    reader.readAsDataURL(file);
                });
            });
        });
    </script>
</body>
</html>
