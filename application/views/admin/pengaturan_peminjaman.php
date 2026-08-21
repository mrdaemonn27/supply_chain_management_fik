<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Pengaturan Peminjaman') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f5f6f8; color:#202124; font-family:'Poppins',sans-serif; }
        .settings-shell { max-width:960px; margin:0 auto; }
        .settings-card { border:1px solid #e4e7ec; border-radius:18px; background:#fff; box-shadow:0 8px 24px rgba(16,24,40,.05); }
        .settings-icon { display:inline-flex; width:46px; height:46px; align-items:center; justify-content:center; border-radius:13px; color:#ff7900; background:#fff1e7; font-size:1.25rem; }
        .settings-input { max-width:220px; }
        .settings-input .form-control { min-height:52px; border-color:#d0d5dd; border-radius:12px 0 0 12px; font-size:1.05rem; font-weight:700; }
        .settings-input .input-group-text { border-color:#d0d5dd; border-radius:0 12px 12px 0; background:#f8fafc; font-weight:600; }
        .quick-days { display:flex; flex-wrap:wrap; gap:8px; }
        .quick-days button { border:1px solid #d0d5dd; border-radius:999px; background:#fff; color:#475467; padding:7px 14px; font-size:.82rem; font-weight:600; }
        .quick-days button:hover { border-color:#ff7900; color:#ff7900; background:#fff8f3; }
        .btn-save { border:0; border-radius:999px; background:#ff7900; color:#fff; padding:11px 24px; font-weight:700; }
        .btn-save:hover { background:#e96d00; color:#fff; }
        .info-panel { border-radius:14px; background:#f8fafc; color:#475467; }
    </style>
</head>
<body class="scm-admin-shell">
    <?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>
    <nav class="topbar navbar bg-white border-bottom px-3 px-lg-4 py-3">
        <div><div class="fw-bold">Pengaturan Peminjaman</div><div class="small text-muted">Konfigurasi workflow SCM FIK</div></div>
        <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary rounded-pill ms-auto"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
    </nav>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <div class="settings-shell">
            <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div><?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>

            <section class="settings-card p-4 p-lg-5">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <span class="settings-icon"><i class="bi bi-hourglass-split"></i></span>
                    <div><h1 class="h4 fw-bold mb-1">Batas Persetujuan Kaprodi</h1><p class="text-muted mb-0">Tentukan berapa lama Kaprodi dapat memberikan keputusan pada pengajuan baru.</p></div>
                </div>

                <form method="post" action="<?= base_url('index.php/admin/pengaturan/simpan') ?>">
                    <label for="kaprodiApprovalDays" class="form-label fw-semibold">Batas waktu</label>
                    <div class="input-group settings-input mb-3">
                        <input id="kaprodiApprovalDays" type="number" name="kaprodi_approval_days" class="form-control" min="1" max="30" value="<?= (int) ($settings->kaprodi_approval_days ?? 4) ?>" required>
                        <span class="input-group-text">Hari</span>
                    </div>
                    <div class="quick-days mb-4" aria-label="Pilihan cepat batas hari">
                        <?php foreach ([1, 2, 3, 4, 7] as $day): ?><button type="button" data-days="<?= $day ?>"><?= $day ?> hari</button><?php endforeach; ?>
                    </div>

                    <div class="info-panel p-3 mb-4 small">
                        <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Berlaku untuk pengajuan baru</div>
                        Pengajuan yang sudah tersimpan tetap memakai tenggatnya sendiri. Jika tenggat terlewati, pengajuan ditolak otomatis, reservasi stok dilepas, dan user menerima notifikasi.
                    </div>

                    <button class="btn btn-save" type="submit"><i class="bi bi-check2-circle me-1"></i> Simpan Pengaturan</button>
                </form>
            </section>
        </div>
    </main>
    <script>
        const approvalDaysInput = document.getElementById('kaprodiApprovalDays');
        document.querySelectorAll('[data-days]').forEach((button) => {
            button.addEventListener('click', () => { approvalDaysInput.value = button.dataset.days; approvalDaysInput.focus(); });
        });
    </script>
</body>
</html>
