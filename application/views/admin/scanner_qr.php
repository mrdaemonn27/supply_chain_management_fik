<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Scanner QR') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6f8; font-family: Arial, sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .panel-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; box-shadow: 0 10px 24px rgba(0,0,0,.06); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        #reader { width: min(100%, 520px); margin: 0 auto; overflow: hidden; border-radius: 8px; }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3 d-flex justify-content-between align-items-center gap-2">
            <div><div class="fw-bold"><i class="bi bi-qr-code-scan me-2 text-warning"></i>Scanner QR Peminjaman</div><div class="small text-white-50">Scan QR pengambilan atau pengembalian dari akun peminjam</div></div>
            <a href="<?= base_url('index.php/admin/peminjaman') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">Data Peminjaman</a>
        </div>
    </header>

    <main class="container py-4">
        <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
        <section class="panel-card p-3 p-lg-4 text-center">
            <h1 class="h5 fw-bold mb-2">Arahkan kamera ke QR peminjam</h1>
            <p class="text-muted small mb-3">Setelah QR terbaca, sistem langsung membuka halaman validasi sesuai jenis QR.</p>
            <div id="reader"></div>
            <div id="scanStatus" class="small text-muted mt-3">Menunggu kamera aktif...</div>
        </section>
    </main>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        const statusEl = document.getElementById('scanStatus');
        const scanner = new Html5QrcodeScanner('reader', {
            fps: 10,
            qrbox: { width: 260, height: 260 }
        }, false);

        scanner.render((decodedText) => {
            statusEl.textContent = 'QR terbaca, membuka serah terima...';
            if (/^https?:\/\//i.test(decodedText)) {
                window.location.href = decodedText;
                return;
            }
            window.location.href = '<?= base_url('index.php/admin/peminjaman/serah_terima/') ?>' + encodeURIComponent(decodedText);
        }, () => {});
    </script>
</body>
</html>
