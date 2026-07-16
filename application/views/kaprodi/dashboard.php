<?php
function status_class_kaprodi($status) {
    $map = [
        'Pengajuan' => 'status-pengajuan',
        'Revisi' => 'status-revisi',
        'Negosiasi' => 'status-negosiasi',
        'Sedang Negosiasi' => 'status-negosiasi',
        'Deal' => 'status-deal',
        'Disetujui' => 'status-approval',
        'Approval' => 'status-approval',
        'BAST' => 'status-bast',
        'Inventarisasi' => 'status-inventory',
        'Selesai' => 'status-selesai',
        'Ditolak' => 'status-ditolak',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function query_kaprodi($filters, $page) {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    return http_build_query($params);
}
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'pengajuan' => 0, 'negosiasi' => 0, 'deal' => 0, 'selesai' => 0];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan ?? []);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Kaprodi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; color: #202124; font-family: 'Poppins', sans-serif; }
        .topbar { background: #1f1f1f; color: #fff; border-bottom: 4px solid #ea5b1a; }
        .brand-mark { width: 42px; height: 42px; border-radius: 8px; background: rgba(234, 91, 26, .16); color: #ea5b1a; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; }
        .panel-card { background: #fff; border: 1px solid #e8eaed; border-radius: 8px; box-shadow: 0 8px 22px rgba(32, 33, 36, .05); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover { background: #c24a13; color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234, 91, 26, .16); }
        .summary-card { min-height: 96px; padding: 18px; }
        .summary-card .value { font-weight: 700; font-size: 1.5rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .need-row { border: 1px solid #e8eaed; border-radius: 8px; padding: 12px; background: #fff; }
        .nav-tabs .nav-link { color: #495057; font-weight: 600; }
        .nav-tabs .nav-link.active { color: #ea5b1a; border-bottom-color: #ea5b1a; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1 1 auto; }
            .summary-card { min-height: auto; }
        }
    </style>
</head>
<body>
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-building-check"></i></span>
                    <div>
                        <div class="fw-bold">Panel Kaprodi</div>
                        <div class="small text-white-50">Pengajuan kebutuhan prodi ke laboratorium</div>
                    </div>
                </div>
                <div class="topbar-actions d-flex align-items-center gap-2">
                    <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-globe me-1"></i> Web User</a>
                    <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Pengajuan Barang dan Jasa</h1>
                <p class="text-muted mb-0">Kaprodi mengajukan kebutuhan. Vendor, harga, negosiasi, dan BAST diproses oleh Kaur Laboratorium.</p>
            </div>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['total'] ?></div><div class="label">Total pengajuan</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['pengajuan'] ?></div><div class="label">Menunggu proses</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card"><div class="value"><?= (int) $stats['negosiasi'] ?></div><div class="label">Dalam negosiasi</div></div></div>
            <div class="col-6 col-lg-3"><div class="panel-card summary-card"><div class="value"><?= (int) ($stats['deal'] + $stats['selesai']) ?></div><div class="label">Deal / selesai</div></div></div>
        </div>

        <ul class="nav nav-tabs mb-3" id="kaprodiTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ajukan" type="button"><i class="bi bi-plus-circle me-1"></i> Ajukan</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button"><i class="bi bi-clock-history me-1"></i> Riwayat</button></li>
        </ul>

        <div class="tab-content">
            <section class="tab-pane fade show active" id="tab-ajukan">
                <form action="<?= base_url('index.php/kaprodi/pengajuan/simpan') ?>" method="post" class="panel-card p-3 p-lg-4 mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis Pengajuan</label>
                            <select name="jenis_pengajuan" class="form-select" required>
                                <option value="Barang">Barang</option>
                                <option value="Jasa">Jasa</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Program Studi</label>
                            <input type="text" name="nama_prodi" class="form-control" placeholder="Contoh: S1 Desain Komunikasi Visual" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama Pengajuan</label>
                            <input type="text" name="nama_pengajuan" class="form-control" placeholder="Contoh: Kebutuhan studio fotografi" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keterangan Kebutuhan</label>
                            <textarea name="kebutuhan_lab" class="form-control" rows="3" placeholder="Jelaskan alasan kebutuhan, prioritas, atau ruangan terkait."></textarea>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-4 mb-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1">Daftar Kebutuhan</h2>
                            <div class="small text-muted">Tidak ada input vendor atau harga negosiasi di tahap Kaprodi.</div>
                        </div>
                        <button type="button" class="btn btn-outline-dark rounded-pill px-3" id="addNeed"><i class="bi bi-plus-lg me-1"></i> Tambah Baris</button>
                    </div>

                    <div id="needList" class="vstack gap-2">
                        <div class="need-row">
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-5">
                                    <label class="form-label small fw-semibold">Uraian Barang/Jasa</label>
                                    <input type="text" name="uraian_barang[]" class="form-control" placeholder="Contoh: Kamera mirrorless / jasa instalasi" required>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-semibold">Volume</label>
                                    <input type="number" name="vol[]" class="form-control" min="1" step="1" value="1" required>
                                </div>
                                <div class="col-6 col-lg-2">
                                    <label class="form-label small fw-semibold">Satuan</label>
                                    <input type="text" name="satuan[]" class="form-control" value="unit" required>
                                </div>
                                <div class="col-lg-2">
                                    <label class="form-label small fw-semibold">Link Referensi</label>
                                    <input type="url" name="link_penawaran[]" class="form-control" placeholder="https://...">
                                </div>
                                <div class="col-lg-1 d-grid">
                                    <button type="button" class="btn btn-outline-danger remove-need" disabled><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-fik rounded-pill px-4 fw-semibold"><i class="bi bi-send me-1"></i> Kirim Pengajuan</button>
                    </div>
                </form>
            </section>

            <section class="tab-pane fade" id="tab-riwayat">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Riwayat Pengajuan</h2>
                        <div class="small text-muted">Export mengikuti filter tanggal, jenis, status, dan kata kunci.</div>
                    </div>
                    <a href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</a>
                </div>
                <div class="panel-card p-3 p-lg-4 mb-3">
                    <form method="get" action="<?= base_url('index.php/kaprodi/dashboard') ?>" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kata Kunci</label>
                            <input type="text" name="q" class="form-control" value="<?= html_escape($filters['q'] ?? '') ?>" placeholder="Kode, prodi, kebutuhan">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jenis</label>
                            <select name="jenis_pengajuan" class="form-select">
                                <option value="">Semua</option>
                                <option value="Barang" <?= (($filters['jenis_pengajuan'] ?? '') === 'Barang') ? 'selected' : '' ?>>Barang</option>
                                <option value="Jasa" <?= (($filters['jenis_pengajuan'] ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua</option>
                                <?php foreach (($status_options ?? []) as $option): ?>
                                    <option value="<?= html_escape($option) ?>" <?= (($filters['status'] ?? '') === $option) ? 'selected' : '' ?>><?= html_escape($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Dari</label>
                            <input type="date" name="tanggal_dari" class="form-control" value="<?= html_escape($filters['tanggal_dari'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Sampai</label>
                            <input type="date" name="tanggal_sampai" class="form-control" value="<?= html_escape($filters['tanggal_sampai'] ?? '') ?>">
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-fik"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="panel-card overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-clean align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pengajuan</th>
                                    <th>Jenis</th>
                                    <th>Kebutuhan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pengajuan)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-5">Belum ada data pengajuan sesuai filter.</td></tr>
                                <?php else: foreach ($pengajuan as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= html_escape($p->nama_pengajuan) ?></div>
                                            <div class="small text-muted"><?= html_escape($p->nama_prodi) ?></div>
                                        </td>
                                        <td><span class="badge text-bg-light border"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                        <td style="min-width: 300px;">
                                            <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                            <?php foreach (($p->items ?? []) as $item): ?>
                                                <div class="small"><i class="bi bi-dot"></i><?= html_escape($item->uraian_barang) ?> - <?= rtrim(rtrim(number_format((float) $item->vol, 2, ',', '.'), '0'), ',') ?> <?= html_escape($item->satuan) ?></div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><span class="status-pill <?= status_class_kaprodi($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                        <td class="text-end"><a href="<?= base_url('index.php/kaprodi/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-top">
                        <div class="small text-muted">Menampilkan <?= count($pengajuan ?? []) ?> dari <?= (int) $total_rows ?> data</div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, max(1, $page - 1))) ?>">Prev</a></li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, $i)) ?>"><?= $i ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, min($total_pages, $page + 1))) ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <template id="needTemplate">
        <div class="need-row">
            <div class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label small fw-semibold">Uraian Barang/Jasa</label>
                    <input type="text" name="uraian_barang[]" class="form-control" placeholder="Contoh: Kamera mirrorless / jasa instalasi" required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold">Volume</label>
                    <input type="number" name="vol[]" class="form-control" min="1" step="1" value="1" required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold">Satuan</label>
                    <input type="text" name="satuan[]" class="form-control" value="unit" required>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold">Link Referensi</label>
                    <input type="url" name="link_penawaran[]" class="form-control" placeholder="https://...">
                </div>
                <div class="col-lg-1 d-grid">
                    <button type="button" class="btn btn-outline-danger remove-need"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const needList = document.getElementById('needList');
        const template = document.getElementById('needTemplate');
        document.getElementById('addNeed').addEventListener('click', () => {
            needList.appendChild(template.content.cloneNode(true));
            refreshRemoveButtons();
        });
        needList.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-need');
            if (!button || button.disabled) return;
            button.closest('.need-row').remove();
            refreshRemoveButtons();
        });
        function refreshRemoveButtons() {
            const buttons = needList.querySelectorAll('.remove-need');
            buttons.forEach((button) => button.disabled = buttons.length <= 1);
        }
        refreshRemoveButtons();
    </script>
</body>
</html>
