<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$formatDistributionDate = static function ($value) {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : (string) $value;
};

$distributionOrigins = [];
$distributionDestinations = [];
foreach ($distribusi as $item) {
    $origin = trim((string) ($item->ruangan_asal ?? ''));
    $destination = trim((string) ($item->ruangan_tujuan ?? ''));
    if ($origin !== '') {
        $distributionOrigins[$origin] = $origin;
    }
    if ($destination !== '') {
        $distributionDestinations[$destination] = $destination;
    }
}
ksort($distributionOrigins, SORT_NATURAL | SORT_FLAG_CASE);
ksort($distributionDestinations, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Distribusi Barang' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f5f6f8; font-family: 'Poppins', sans-serif; color: #202124; }
        .topbar { background: #1f1f1f; border-bottom: 4px solid #ea5b1a; color: #fff; }
        .distribution-page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
        .distribution-page-heading h1 { margin: 0; color: #111827; font-size: clamp(1.35rem, 2.1vw, 1.9rem); font-weight: 700; letter-spacing: -.02em; }
        .distribution-page-heading p { margin: .25rem 0 0; color: #6b7280; font-size: .82rem; }
        .panel-card { border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; box-shadow: 0 8px 22px rgba(32,33,36,.045); }
        .btn-fik { background: #ea5b1a; color: #fff; border: 0; }
        .btn-fik:hover, .btn-fik:focus { background: #c94d14; color: #fff; }
        .form-control, .form-select { border-color: #d7dce2; color: #374151; font-size: .8rem; }
        .form-control:focus, .form-select:focus { border-color: #ea5b1a; box-shadow: 0 0 0 .2rem rgba(234,91,26,.14); }
        .distribution-toolbar { display: grid; grid-template-columns: minmax(220px, 1.45fr) minmax(170px, 1fr) minmax(170px, 1fr) minmax(150px, .75fr) auto; align-items: end; gap: .75rem; padding: 1rem; }
        .distribution-toolbar label { display: block; margin-bottom: .35rem; color: #6b7280; font-size: .7rem; font-weight: 600; }
        .distribution-toolbar .form-control, .distribution-toolbar .form-select { min-height: 40px; }
        .distribution-toolbar .btn { min-height: 40px; white-space: nowrap; }
        .distribution-table-wrap { overflow-x: auto; }
        .distribution-table { min-width: 760px; margin: 0; }
        .distribution-table thead th { padding: .78rem .75rem; color: #111827 !important; background: #f8f9fa; border-bottom: 1px solid #e5e7eb; font-size: .68rem; font-family: inherit; font-weight: 700 !important; letter-spacing: .06em; text-transform: uppercase; white-space: nowrap; }
        .distribution-table thead th, .distribution-table thead th * { color: #111827 !important; font-weight: 700 !important; }
        .distribution-table tbody td { padding: .9rem .75rem; color: #374151; border-color: #edf0f2; font-size: .78rem; vertical-align: middle; }
        .distribution-table tbody tr { transition: background-color .18s ease; }
        .distribution-table tbody tr:hover { background: #fffaf7; }
        .distribution-table th.distribution-index-column, .distribution-table td.distribution-index-column { min-width: 58px; width: 58px; padding-right: 10px; padding-left: 10px; text-align: center; white-space: nowrap; }
        .distribution-index { color: #64748b; font-size: .8rem; font-weight: 500; }
        .distribution-table .asset-name { color: #111827; font-size: .82rem; font-weight: 600; }
        .distribution-table .asset-meta, .distribution-table .distribution-note { color: #8a94a3; font-size: .7rem; }
        .distribution-table .distribution-note { display: -webkit-box; max-width: 300px; margin-top: .25rem; overflow: hidden; line-height: 1.45; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .distribution-route { display: flex; align-items: center; gap: .45rem; min-width: 250px; color: #6b7280; }
        .distribution-route .route-destination { color: #374151; font-weight: 600; }
        .distribution-route i { color: #9ca3af; font-size: .9rem; }
        .distribution-quantity { color: #111827; font-size: .82rem; font-weight: 600; text-align: center; }
        .distribution-date { white-space: nowrap; }
        .distribution-staff { color: #4b5563; font-weight: 500; }
        .distribution-empty { padding: 4rem 1rem !important; color: #6b7280 !important; }
        .distribution-empty i { display: block; margin-bottom: .75rem; color: #c4cbd4; font-size: 2rem; }
        .distribution-pagination { display: flex; align-items: center; justify-content: space-between; gap: 1rem; min-height: 64px; padding: .75rem 1rem; border-top: 1px solid #e5e7eb; background: #fafbfc; color: #6b7280; font-size: .72rem; }
        .distribution-pagination-left { display: flex; align-items: center; gap: .55rem; white-space: nowrap; }
        .distribution-pagination-left select { width: 74px; min-height: 36px; padding-top: .35rem; padding-bottom: .35rem; font-size: .75rem; }
        .distribution-pagination-total { white-space: nowrap; }
        .distribution-page-info { min-width: 120px; color: #6b7280; text-align: center; white-space: nowrap; }
        .distribution-pagination .pagination { margin: 0; }
        .distribution-pagination .page-link { min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; padding: .35rem .55rem; color: #0d6efd; background: #fff; border-color: #dfe3e8; font-size: .75rem; }
        .distribution-pagination .page-item.active .page-link { color: #fff; background: #ea5b1a; border-color: #ea5b1a; }
        .distribution-pagination .page-item.disabled .page-link { color: #adb5bd; background: #f8f9fa; border-color: #e5e7eb; }
        .distribution-pagination .page-link:hover { color: #c24a13; background: #fff8f3; border-color: #f0b99e; }
        .distribution-pagination .page-item.active .page-link:hover { color: #fff; background: #c94d14; border-color: #c94d14; }
        .distribution-drawer { width: min(520px, 100vw) !important; border-left: 1px solid #e5e7eb; }
        .distribution-drawer .offcanvas-header { padding: 1.25rem 1.35rem; border-bottom: 1px solid #e5e7eb; }
        .distribution-drawer .offcanvas-title { color: #111827; font-size: 1rem; font-weight: 700; }
        .distribution-drawer .offcanvas-body { padding: 1.35rem; background: #fff; }
        .distribution-drawer .form-label { margin-bottom: .4rem; color: #6b7280; font-size: .75rem; font-weight: 600; }
        .distribution-drawer .form-control, .distribution-drawer .form-select { min-height: 42px; }
        .distribution-drawer textarea.form-control { min-height: auto; }
        .distribution-drawer .form-hint { margin-top: .35rem; color: #9ca3af; font-size: .7rem; line-height: 1.45; }
        @media (max-width: 1100px) {
            .distribution-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .distribution-toolbar .toolbar-search { grid-column: 1 / -1; }
        }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .distribution-page-heading { align-items: stretch; flex-direction: column; }
            .distribution-page-heading .btn { align-self: flex-start; }
            .distribution-toolbar { grid-template-columns: 1fr; }
            .distribution-toolbar .toolbar-search { grid-column: auto; }
            .distribution-table { min-width: 680px; }
            .distribution-drawer { width: 100% !important; }
            .distribution-pagination { align-items: stretch; flex-direction: column; }
            .distribution-pagination-left { justify-content: center; }
            .distribution-page-info { align-self: center; }
            .distribution-pagination nav { align-self: center; }
        }
    </style>
</head>
<body class="scm-admin-shell">
<?php include APPPATH . 'views/admin/panel_sidebar.php'; ?>

<header class="topbar sticky-top">
    <div class="container-fluid px-3 px-lg-4 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <div class="fw-bold"><i class="bi bi-truck me-2 text-warning"></i>Distribusi Barang</div>
            </div>
            <div class="topbar-actions d-flex gap-2">
                <a href="<?= base_url('index.php/admin/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            </div>
        </div>
    </div>
</header>

<main class="container-fluid px-3 px-lg-4 py-4">
    <?php if ($this->session->flashdata('success')): ?><div class="alert alert-success border-0 shadow-sm"><?= $this->session->flashdata('success'); ?></div><?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger border-0 shadow-sm"><?= $this->session->flashdata('error'); ?></div><?php endif; ?>

    <div class="distribution-page-heading">
        <div>
            <h1>Distribusi Barang</h1>
        </div>
        <button type="button" class="btn btn-fik rounded-pill px-3" data-bs-toggle="offcanvas" data-bs-target="#distributionDrawer" aria-controls="distributionDrawer">
            <i class="bi bi-plus-lg me-1"></i> Catat Distribusi
        </button>
    </div>

    <?php
    $multi_filter_id = 'distributionMultiFilter';
    $multi_filter_mode = 'client';
    $multi_filter_fields = [
        'aset' => ['label' => 'Aset / kode', 'placeholder' => 'Cari nama aset atau kode'],
        'asal' => ['label' => 'Ruangan asal', 'placeholder' => 'Cari ruangan asal'],
        'tujuan' => ['label' => 'Ruangan tujuan', 'placeholder' => 'Cari ruangan tujuan'],
        'jumlah' => ['label' => 'Jumlah', 'placeholder' => 'Cari jumlah barang', 'type' => 'number'],
        'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal distribusi', 'type' => 'date'],
        'petugas' => ['label' => 'Petugas', 'placeholder' => 'Cari nama petugas'],
    ];
    include APPPATH . 'views/admin/_multi_filter.php';
    ?>

    <section class="panel-card overflow-hidden" aria-labelledby="distributionHistoryTitle">
        <div class="d-flex align-items-center justify-content-between gap-2 px-3 px-lg-4 py-3 border-bottom">
            <div>
                <h2 id="distributionHistoryTitle" class="h6 mb-0 fw-bold">Riwayat Distribusi</h2>
            </div>
        </div>
        <div class="distribution-table-wrap">
            <table class="table distribution-table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="distribution-index-column">No</th>
                        <th class="ps-3">Aset</th>
                        <th>Perpindahan</th>
                        <th class="text-center">Jumlah</th>
                        <th>Tanggal</th>
                        <th class="pe-3">Petugas</th>
                    </tr>
                </thead>
                <tbody id="distributionTableBody">
                <?php if (empty($distribusi)): ?>
                    <tr id="distributionEmptyRow"><td colspan="6" class="distribution-empty text-center"><i class="bi bi-arrow-left-right"></i><div class="fw-semibold text-dark">Belum ada riwayat distribusi</div><div class="small mt-1">Catat perpindahan barang pertama untuk mulai melihat riwayat distribusi.</div></td></tr>
                <?php else: foreach ($distribusi as $distribution_index => $d): ?>
                    <?php
                    $assetName = (string) ($d->nama_aset ?? '-');
                    $assetCode = (string) ($d->kode_aset ?? '-');
                    $origin = (string) ($d->ruangan_asal ?? '-');
                    $destination = (string) ($d->ruangan_tujuan ?? '-');
                    $dateValue = (string) ($d->tanggal_distribusi ?? '');
                    $searchText = strtolower(implode(' ', [$assetName, $assetCode, $origin, $destination, (string) ($d->nama_petugas ?? ''), (string) ($d->keterangan ?? '')]));
                    ?>
                    <tr class="distribution-data-row" data-filter-aset="<?= html_escape($assetName . ' ' . $assetCode) ?>" data-filter-asal="<?= html_escape($origin) ?>" data-filter-tujuan="<?= html_escape($destination) ?>" data-filter-jumlah="<?= (int) $d->jumlah ?>" data-filter-tanggal="<?= html_escape($dateValue) ?>" data-filter-petugas="<?= html_escape($d->nama_petugas ?? '-') ?>">
                        <td class="distribution-index-column"><span class="distribution-index"><?= $distribution_index + 1 ?></span></td>
                        <td class="ps-3"><div class="asset-name"><?= html_escape($assetName) ?></div><div class="asset-meta"><?= html_escape($assetCode) ?></div></td>
                        <td><div class="distribution-route"><span><?= html_escape($origin) ?></span><i class="bi bi-arrow-right" aria-hidden="true"></i><span class="route-destination"><?= html_escape($destination) ?></span></div><?php if (trim((string) ($d->keterangan ?? '')) !== ''): ?><div class="distribution-note" title="<?= html_escape($d->keterangan) ?>"><?= html_escape($d->keterangan) ?></div><?php endif; ?></td>
                        <td class="distribution-quantity"><?= (int) $d->jumlah ?></td>
                        <td class="distribution-date"><?= html_escape($formatDistributionDate($dateValue)) ?></td>
                        <td class="distribution-staff pe-3"><?= html_escape($d->nama_petugas ?: 'Laboran') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                    <tr id="distributionFilteredEmpty" hidden><td colspan="6" class="distribution-empty text-center"><i class="bi bi-search"></i><div class="fw-semibold text-dark">Tidak ada distribusi yang sesuai</div><div class="small mt-1">Coba ubah kata kunci atau filter yang dipilih.</div></td></tr>
                </tbody>
            </table>
        </div>
        <div class="distribution-pagination">
            <div class="distribution-pagination-left">
                <span>Tampilkan:</span>
                <select id="distributionPageSize" class="form-select form-select-sm" aria-label="Jumlah distribusi per halaman">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Semua</option>
                </select>
                <span id="distributionTotal" class="distribution-pagination-total">Total item: 0</span>
            </div>
            <div id="distributionPageInfo" class="distribution-page-info">Halaman: 1 dari 1</div>
            <nav aria-label="Pagination distribusi">
                <ul id="distributionPagination" class="pagination pagination-sm"></ul>
            </nav>
        </div>
    </section>
</main>

<div class="offcanvas offcanvas-end distribution-drawer" tabindex="-1" id="distributionDrawer" aria-labelledby="distributionDrawerLabel">
    <div class="offcanvas-header">
        <h2 id="distributionDrawerLabel" class="offcanvas-title mb-0">Catat Distribusi</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <form action="<?= base_url('index.php/admin/distribusi/simpan') ?>" method="post" class="vstack gap-3">
            <div><label class="form-label" for="distributionAsset">Aset</label><select id="distributionAsset" name="id_aset" class="form-select" required><option value="">Pilih aset</option><?php foreach ($aset as $a): ?><option value="<?= $a->id_aset ?>"><?= html_escape($a->nama_aset . ' - ' . $a->kode_aset . ' (' . $a->nama_ruangan . ')') ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label" for="distributionRoom">Ruangan Tujuan</label><select id="distributionRoom" name="id_ruangan_tujuan" class="form-select" required><option value="">Pilih ruangan</option><?php foreach ($ruangan as $r): ?><option value="<?= $r['id_ruangan'] ?>"><?= html_escape($r['nama_ruangan']) ?></option><?php endforeach; ?></select></div>
            <div class="row g-3"><div class="col-6"><label class="form-label" for="distributionAmount">Jumlah</label><input id="distributionAmount" type="number" name="jumlah" min="1" value="1" class="form-control"></div><div class="col-6"><label class="form-label" for="distributionTransferDate">Tanggal</label><input id="distributionTransferDate" type="date" name="tanggal_distribusi" value="<?= date('Y-m-d') ?>" class="form-control"></div></div>
            <div><label class="form-label" for="distributionNotes">Keterangan</label><textarea id="distributionNotes" name="keterangan" class="form-control" rows="4"></textarea><div class="form-hint">Tambahkan catatan jika diperlukan untuk menjelaskan perpindahan.</div></div>
            <button type="submit" class="btn btn-fik rounded-pill mt-2"><i class="bi bi-arrow-left-right me-1"></i> Simpan Distribusi</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterRoot = document.getElementById('distributionMultiFilter');
    const pageSizeSelect = document.getElementById('distributionPageSize');
    const total = document.getElementById('distributionTotal');
    const pageInfo = document.getElementById('distributionPageInfo');
    const pagination = document.getElementById('distributionPagination');
    const filteredEmpty = document.getElementById('distributionFilteredEmpty');
    const rows = Array.from(document.querySelectorAll('.distribution-data-row'));
    let currentPage = 1;

    if (!filterRoot || !pageSizeSelect || !total || !pageInfo || !pagination) return;

    const getPageSize = function () {
        return pageSizeSelect.value === 'all' ? Math.max(rows.length, 1) : Math.max(parseInt(pageSizeSelect.value, 10) || 10, 1);
    };

    const createPageItem = function (label, page, disabled, active, ariaLabel) {
        const item = document.createElement('li');
        item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.setAttribute('aria-label', ariaLabel || label);
        if (active) button.setAttribute('aria-current', 'page');
        button.disabled = disabled;
        if (!disabled && !active) button.addEventListener('click', function () {
            currentPage = page;
            render();
        });
        item.appendChild(button);
        return item;
    };

    const renderPagination = function (pageCount, filteredRows) {
        pagination.replaceChildren();
        pagination.appendChild(createPageItem('Previous', currentPage - 1, currentPage <= 1, false, 'Halaman sebelumnya'));

        for (let page = 1; page <= pageCount; page += 1) {
            pagination.appendChild(createPageItem(String(page), page, false, page === currentPage, 'Halaman ' + page));
        }

        pagination.appendChild(createPageItem('Next', currentPage + 1, currentPage >= pageCount, false, 'Halaman berikutnya'));
        pageInfo.textContent = 'Halaman: ' + currentPage + ' dari ' + pageCount;
        total.textContent = 'Total item: ' + filteredRows.length;
    };

    const render = function () {
        const criteria = AdminMultiFilter.getCriteria(filterRoot);
        const filteredRows = rows.filter(function (row) { return AdminMultiFilter.matches(row, criteria); });
        const pageSize = getPageSize();
        const pageCount = Math.max(Math.ceil(filteredRows.length / pageSize), 1);
        currentPage = Math.min(currentPage, pageCount);
        const firstIndex = (currentPage - 1) * pageSize;
        const visibleRows = new Set(filteredRows.slice(firstIndex, firstIndex + pageSize));

        rows.forEach(function (row) {
            row.hidden = !visibleRows.has(row);
        });

        if (filteredEmpty) filteredEmpty.hidden = rows.length === 0 || filteredRows.length !== 0;
        renderPagination(pageCount, filteredRows);
    };

    const filterChanged = function () {
        currentPage = 1;
        render();
    };

    filterRoot.addEventListener('admin-multi-filter-change', filterChanged);
    pageSizeSelect.addEventListener('change', filterChanged);
    render();
});
</script>
</body>
</html>
