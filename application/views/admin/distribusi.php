<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$distribution_pagination = $pagination ?? ['page' => 1, 'per_page' => 10, 'total' => count($distribusi ?? []), 'total_pages' => 1];
$distribution_page = (int) $distribution_pagination['page'];
$distribution_per_page = (int) $distribution_pagination['per_page'];
$distribution_total = (int) $distribution_pagination['total'];
$distribution_total_pages = (int) $distribution_pagination['total_pages'];
$distribution_query = $_GET;
$distribution_query['per_page'] = $distribution_per_page;

$formatDistributionDate = static function ($value) {
    return tanggal_indonesia($value);
};

$formatDistributionDateTime = static function ($value) {
    return waktu_indonesia($value);
};
$trackingDetailUrl = base_url('index.php/admin/distribusi/detail/');
$assetSearchUrl = base_url('index.php/admin/distribusi/cari_aset');

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
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
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
        .distribution-table { width: 100%; min-width: 100%; margin: 0; }
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
        .distribution-route { display: grid; grid-template-columns: minmax(0, 1fr) 1.75rem minmax(0, 1fr); align-items: center; gap: .4rem; min-width: 0; color: #6b7280; }
        .distribution-route__point { min-width: 0; padding: .5rem .6rem; border: 1px solid #e6e9ee; border-radius: 8px; background: #fafbfc; }
        .distribution-route__label { display: block; margin-bottom: .12rem; color: #8a94a3; font-size: .61rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .distribution-route__value { display: block; color: #374151; font-size: .75rem; font-weight: 600; line-height: 1.4; overflow-wrap: break-word; word-break: normal; }
        .distribution-route__point--destination { border-color: #f3cfbd; background: #fff8f4; }
        .distribution-route__point--destination .distribution-route__value { color: #b84410; }
        .distribution-route__arrow { display: inline-flex; width: 1.75rem; height: 1.75rem; align-items: center; justify-content: center; border-radius: 50%; color: #ea5b1a; background: #fff1e9; font-size: .85rem; }
        .distribution-quantity { color: #111827; font-size: .82rem; font-weight: 600; text-align: center; }
        .distribution-date { white-space: nowrap; }
        .distribution-staff { color: #4b5563; font-weight: 500; overflow-wrap: normal !important; word-break: normal !important; hyphens: none; }
        .distribution-condition { display: inline-flex; align-items: center; padding: .22rem .5rem; border: 1px solid #b8d7c3; border-radius: 999px; color: #167749; background: #f1fcf5; font-size: .68rem; font-weight: 700; white-space: nowrap; }
        .distribution-location { color: #374151; font-weight: 600; white-space: nowrap; }
        .distribution-location i { color: #ea5b1a; margin-right: .22rem; }
        .distribution-action { white-space: nowrap; }
        .distribution-action .btn { font-size: .72rem; }
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
        .distribution-asset-summary { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid #f0d4c4; border-radius: 8px; background: #fffaf7; }
        .distribution-asset-summary__name { color: #111827; font-size: .94rem; font-weight: 700; }
        .distribution-asset-summary__meta { margin-top: .18rem; color: #6b7280; font-size: .75rem; }
        .distribution-stock-info { padding: .7rem .8rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafbfc; color: #4b5563; font-size: .76rem; }
        .asset-combobox { position: relative; }
        .asset-combobox__input-wrap { position: relative; }
        .asset-combobox__input { min-height: 46px !important; padding-right: 2.6rem; }
        .asset-combobox__icon { position: absolute; top: 50%; right: .9rem; color: #8a94a3; pointer-events: none; transform: translateY(-50%); }
        .asset-combobox__list { position: absolute; z-index: 1085; top: calc(100% + .4rem); right: 0; left: 0; max-height: 280px; overflow-y: auto; padding: .35rem; border: 1px solid #dbe1e8; border-radius: 10px; background: #fff; box-shadow: 0 14px 34px rgba(15,23,42,.16); }
        .asset-combobox__list[hidden] { display: none; }
        .asset-combobox__option { display: grid; width: 100%; grid-template-columns: minmax(0, 1fr) auto; gap: .3rem .8rem; padding: .7rem .75rem; border: 0; border-radius: 7px; color: #273142; background: transparent; text-align: left; }
        .asset-combobox__option:hover, .asset-combobox__option.is-active { color: #b84410; background: #fff1e9; }
        .asset-combobox__option-name { overflow: hidden; font-size: .8rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .asset-combobox__option-stock { color: #667085; font-size: .68rem; white-space: nowrap; }
        .asset-combobox__option-meta { grid-column: 1 / -1; color: #8a94a3; font-size: .67rem; }
        .asset-combobox__empty { padding: 1rem .75rem; color: #6b7280; font-size: .75rem; text-align: center; }
        .tracking-modal .modal-content { border: 0; border-radius: 10px; overflow: hidden; }
        .tracking-modal .modal-header { border-bottom: 3px solid #ea5b1a; background: #202124; color: #fff; }
        .tracking-modal .modal-title { font-size: 1rem; font-weight: 700; }
        .tracking-modal .btn-close { filter: invert(1) grayscale(1) brightness(3); }
        .tracking-current-location { display: flex; align-items: center; gap: .65rem; padding: .82rem 1rem; border: 1px solid #f0d4c4; border-radius: 8px; background: #fffaf7; color: #374151; font-size: .8rem; }
        .tracking-current-location i { color: #ea5b1a; font-size: 1rem; }
        .tracking-timeline { position: relative; margin: 1.25rem 0 0 .3rem; padding-left: 1.55rem; }
        .tracking-timeline::before { position: absolute; top: .45rem; bottom: .45rem; left: .25rem; width: 2px; background: #f1c7b1; content: ''; }
        .tracking-event { position: relative; padding: 0 0 1.15rem; }
        .tracking-event:last-child { padding-bottom: 0; }
        .tracking-event::before { position: absolute; z-index: 1; top: .2rem; left: -1.55rem; width: .74rem; height: .74rem; border: 2px solid #ea5b1a; border-radius: 50%; background: #fff; content: ''; }
        .tracking-event__title { color: #111827; font-size: .82rem; font-weight: 700; }
        .tracking-event__route { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .3rem; color: #374151; font-size: .8rem; }
        .tracking-event__route i { color: #ea5b1a; }
        .tracking-event__meta { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin-top: .35rem; color: #6b7280; font-size: .72rem; }
        .tracking-event__note { margin-top: .35rem; color: #6b7280; font-size: .73rem; line-height: 1.5; }
        .tracking-loading { padding: 2rem 0; color: #6b7280; font-size: .82rem; text-align: center; }
        @media (max-width: 1100px) {
            .distribution-toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .distribution-toolbar .toolbar-search { grid-column: 1 / -1; }
        }
        @media (min-width: 1200px) {
            .distribution-table-wrap { overflow-x: auto !important; scrollbar-gutter: stable; }
            .distribution-table { width: max-content !important; min-width: 100% !important; table-layout: auto !important; }
            .distribution-table .distribution-asset-column { width: 16% !important; }
            .distribution-table .distribution-movement-column { width: 26% !important; }
            .distribution-table .distribution-location-column { width: 13% !important; }
            .distribution-table .distribution-staff-column,
            .distribution-table td.distribution-staff { width: 132px !important; min-width: 132px !important; white-space: normal !important; }
            .distribution-table th.distribution-action-column,
            .distribution-table td.distribution-action { position: sticky; right: 0; z-index: 2; width: 142px !important; min-width: 142px !important; padding-left: .75rem !important; padding-right: 1.25rem !important; text-align: center !important; white-space: nowrap !important; background: #fff; box-shadow: -8px 0 14px rgba(15, 23, 42, .05); }
            .distribution-table th.distribution-action-column { z-index: 3; background: #f8f9fa !important; }
            .distribution-table tbody tr:hover td.distribution-action { background: #fffaf7; }
            .distribution-table td.distribution-action .btn { display: inline-flex; width: auto !important; min-width: 110px !important; align-items: center; justify-content: center; white-space: nowrap !important; }
        }
        @media (max-width: 1199.98px) {
            .distribution-table-wrap { overflow: visible; padding: .85rem; background: #f7f8fa; }
            .distribution-route { grid-template-columns: 1fr; }
            .distribution-route__arrow { width: 1.5rem; height: 1.5rem; margin-left: .55rem; transform: rotate(90deg); }
            .distribution-route__point { width: 100%; }
        }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; }
            .topbar-actions .btn { flex: 1; }
            .distribution-page-heading { align-items: stretch; flex-direction: column; }
            .distribution-page-heading .btn { align-self: flex-start; }
            .distribution-toolbar { grid-template-columns: 1fr; }
            .distribution-toolbar .toolbar-search { grid-column: auto; }
            .distribution-table { min-width: 0; }
            .distribution-drawer { width: 100% !important; }
            .distribution-asset-summary { grid-template-columns: 1fr; }
            .distribution-pagination { align-items: stretch; flex-direction: column; }
            .distribution-pagination-left { justify-content: center; }
            .distribution-page-info { align-self: center; }
            .distribution-pagination nav { align-self: center; }
        }
        @media (max-width: 575.98px) {
            .distribution-table-wrap { padding: .65rem; }
            .distribution-route__point { padding: .45rem .5rem; }
            .asset-combobox__option { grid-template-columns: 1fr; }
            .asset-combobox__option-stock, .asset-combobox__option-meta { grid-column: 1; }
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
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3 admin-logout-button"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</a>
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
    $multi_filter_mode = 'server';
    $multi_filter_rows = $filter_rows ?? [['field' => 'aset', 'value' => '']];
    $multi_filter_action = current_url();
    $multi_filter_hidden = ['per_page' => $distribution_per_page, 'page' => 1];
    $multi_filter_fields = [
        'aset' => ['label' => 'Aset / kode', 'placeholder' => 'Cari nama aset atau kode'],
        'asal' => ['label' => 'Ruangan asal', 'placeholder' => 'Cari ruangan asal'],
        'tujuan' => ['label' => 'Ruangan tujuan', 'placeholder' => 'Cari ruangan tujuan'],
        'lokasi_terakhir' => ['label' => 'Lokasi terakhir', 'placeholder' => 'Cari lokasi aset saat ini'],
        'jumlah' => ['label' => 'Jumlah', 'placeholder' => 'Cari jumlah barang', 'type' => 'number'],
        'tanggal' => ['label' => 'Rentang tanggal', 'placeholder' => 'YYYY-MM-DD..YYYY-MM-DD'],
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
                        <th class="ps-3 distribution-asset-column">Aset</th>
                        <th>Kondisi</th>
                        <th class="distribution-movement-column">Perpindahan</th>
                        <th class="distribution-location-column">Lokasi Terakhir</th>
                        <th class="text-center">Jumlah</th>
                        <th>Waktu</th>
                        <th class="distribution-staff-column">Petugas</th>
                        <th class="distribution-action-column pe-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="distributionTableBody">
                <?php if (empty($distribusi)): ?>
                    <tr id="distributionEmptyRow"><td colspan="9" class="distribution-empty text-center"><i class="bi bi-arrow-left-right"></i><div class="fw-semibold text-dark">Belum ada riwayat distribusi</div><div class="small mt-1">Catat perpindahan barang pertama untuk mulai melihat riwayat distribusi.</div></td></tr>
                <?php else: foreach ($distribusi as $distribution_index => $d): ?>
                    <?php
                    $assetName = (string) ($d->nama_aset ?? '-');
                    $assetCode = (string) ($d->kode_aset ?? '-');
                    $origin = trim((string) ($d->ruangan_asal ?? '')) ?: 'Belum ditempatkan';
                    $destination = (string) ($d->ruangan_tujuan ?? '-');
                    $dateValue = (string) ($d->tanggal_distribusi ?? '');
                    $lastLocation = (string) ($d->lokasi_terakhir ?? '-');
                    $condition = (string) ($d->kondisi_aset ?: $d->kondisi_terkini ?: '-');
                    $dateTimeValue = (string) ($d->waktu_distribusi ?: $d->created_at ?: $dateValue);
                    ?>
                    <tr class="distribution-data-row" data-filter-aset="<?= html_escape($assetName . ' ' . $assetCode) ?>" data-filter-asal="<?= html_escape($origin) ?>" data-filter-tujuan="<?= html_escape($destination) ?>" data-filter-lokasi_terakhir="<?= html_escape($lastLocation) ?>" data-filter-jumlah="<?= (int) $d->jumlah ?>" data-filter-tanggal="<?= html_escape($dateValue) ?>" data-filter-petugas="<?= html_escape($d->nama_petugas ?? '-') ?>">
                        <td class="distribution-index-column"><span class="distribution-index"><?= (($distribution_page - 1) * $distribution_per_page) + $distribution_index + 1 ?></span></td>
                        <td class="ps-3 distribution-asset-column"><div class="asset-name"><?= html_escape($assetName) ?></div><div class="asset-meta"><?= html_escape($assetCode) ?></div></td>
                        <td><span class="distribution-condition"><?= html_escape($condition) ?></span></td>
                        <td class="distribution-movement-column"><div class="distribution-route"><span class="distribution-route__point"><small class="distribution-route__label">Dari</small><strong class="distribution-route__value"><?= html_escape($origin) ?></strong></span><i class="bi bi-arrow-right distribution-route__arrow" aria-hidden="true"></i><span class="distribution-route__point distribution-route__point--destination"><small class="distribution-route__label">Ke</small><strong class="distribution-route__value"><?= html_escape($destination) ?></strong></span></div><?php if (trim((string) ($d->keterangan ?? '')) !== ''): ?><div class="distribution-note" title="<?= html_escape($d->keterangan) ?>"><?= html_escape($d->keterangan) ?></div><?php endif; ?></td>
                        <td class="distribution-location-column"><span class="distribution-location"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><?= html_escape($lastLocation) ?></span></td>
                        <td class="distribution-quantity"><?= (int) $d->jumlah ?></td>
                        <td class="distribution-date"><?= html_escape($formatDistributionDateTime($dateTimeValue)) ?></td>
                        <td class="distribution-staff"><?= html_escape($d->nama_petugas ?: 'Laboran') ?></td>
                        <td class="distribution-action pe-3 text-end"><button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 js-view-tracking" data-asset-id="<?= (int) $d->id_aset ?>"><i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>Tracking</button></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="distribution-pagination">
            <div class="distribution-pagination-left">
                <span>Tampilkan:</span>
                <select id="distributionPageSize" class="form-select form-select-sm" aria-label="Jumlah distribusi per halaman" onchange="var u=new URL(window.location.href);u.searchParams.set('per_page',this.value);u.searchParams.set('page','1');window.location.assign(u.toString());">
                    <?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?= $size ?>" <?= $distribution_per_page === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
                </select>
                <span id="distributionTotal" class="distribution-pagination-total">Total item: <?= number_format($distribution_total, 0, ',', '.') ?></span>
            </div>
            <div id="distributionPageInfo" class="distribution-page-info">Halaman: <?= $distribution_page ?> dari <?= $distribution_total_pages ?></div>
            <nav aria-label="Pagination distribusi">
                <ul id="distributionPagination" class="pagination pagination-sm">
                    <?php $distribution_query['page'] = max(1, $distribution_page - 1); ?><li class="page-item <?= $distribution_page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($distribution_query) ?>">Previous</a></li>
                    <?php foreach (scm_pagination_tokens($distribution_page, $distribution_total_pages) as $token): ?><?php if (is_string($token)): ?><li class="page-item disabled" aria-hidden="true"><span class="page-link">...</span></li><?php else: $distribution_query['page'] = $token; ?><li class="page-item <?= $token === $distribution_page ? 'active' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($distribution_query) ?>"><?= $token ?></a></li><?php endif; ?><?php endforeach; ?>
                    <?php $distribution_query['page'] = min($distribution_total_pages, $distribution_page + 1); ?><li class="page-item <?= $distribution_page >= $distribution_total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= current_url() . '?' . http_build_query($distribution_query) ?>">Next</a></li>
                </ul>
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
        <form id="distributionForm" action="<?= base_url('index.php/admin/distribusi/simpan') ?>" method="post" class="vstack gap-3">
            <div>
                <label class="form-label" for="distributionAssetSearch">Cari Aset</label>
                <div id="distributionAssetCombobox" class="asset-combobox">
                    <div class="asset-combobox__input-wrap">
                        <input id="distributionAssetSearch" type="text" class="form-control asset-combobox__input" placeholder="Ketik nama, kode, atau lokasi aset" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="distributionAssetList" required>
                        <i class="bi bi-search asset-combobox__icon" aria-hidden="true"></i>
                    </div>
                    <input id="distributionAsset" type="hidden" name="id_aset" value="">
                    <div id="distributionAssetList" class="asset-combobox__list" role="listbox" aria-label="Hasil pencarian aset" hidden>
                        <div class="asset-combobox__empty">Ketik untuk mencari aset.</div>
                    </div>
                </div>
                <div class="form-hint">Pilih satu hasil pencarian agar detail aset terisi otomatis.</div>
            </div>
            <div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="distributionAssetCode">Kode Aset</label><input id="distributionAssetCode" type="text" class="form-control" readonly placeholder="Terisi otomatis"></div>
                <div class="col-sm-6"><label class="form-label" for="distributionCondition">Kondisi Aset</label><input id="distributionCondition" type="text" class="form-control" readonly placeholder="Terisi otomatis"></div>
            </div>
            <div id="distributionStockInfo" class="distribution-stock-info"><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Pilih aset untuk melihat lokasi dan stok tersedia.</div>
            <div>
                <label class="form-label" for="distributionOrigin">Lokasi / Ruangan Asal</label>
                <select id="distributionOrigin" name="id_ruangan_asal" class="form-select">
                    <option value="">Belum ditempatkan (distribusi pertama)</option>
                    <?php foreach ($ruangan as $r): ?><option value="<?= (int) $r['id_ruangan'] ?>"><?= html_escape($r['nama_ruangan']) ?></option><?php endforeach; ?>
                </select>
                <div class="form-hint">Boleh kosong untuk barang baru yang belum pernah ditempatkan di ruangan.</div>
            </div>
            <div><label class="form-label" for="distributionRoom">Lokasi / Ruangan Tujuan</label><select id="distributionRoom" name="id_ruangan_tujuan" class="form-select" required><option value="">Pilih ruangan tujuan</option><?php foreach ($ruangan as $r): ?><option value="<?= (int) $r['id_ruangan'] ?>"><?= html_escape($r['nama_ruangan']) ?></option><?php endforeach; ?></select></div>
            <div class="row g-3">
                <div class="col-sm-4"><label class="form-label" for="distributionAmount">Jumlah</label><input id="distributionAmount" type="number" name="jumlah" min="1" value="1" class="form-control" required></div>
                <div class="col-sm-4"><label class="form-label" for="distributionTransferDate">Tanggal</label><input id="distributionTransferDate" type="date" name="tanggal_distribusi" value="<?= date('Y-m-d') ?>" class="form-control" required></div>
                <div class="col-sm-4"><label class="form-label" for="distributionTransferTime">Jam</label><input id="distributionTransferTime" type="time" name="jam_distribusi" value="<?= date('H:i') ?>" class="form-control" required></div>
            </div>
            <div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="distributionOfficer">Petugas</label><input id="distributionOfficer" type="text" class="form-control" value="<?= html_escape($operator_name ?? 'Petugas Laboratorium') ?>" readonly></div>
                <div class="col-sm-6"><label class="form-label" for="distributionRecipient">Penanggung Jawab Penerima</label><input id="distributionRecipient" type="text" name="penerima" maxlength="150" class="form-control" placeholder="Opsional"></div>
            </div>
            <div><label class="form-label" for="distributionNotes">Keterangan / Catatan</label><textarea id="distributionNotes" name="keterangan" class="form-control" rows="4" maxlength="1000"></textarea><div class="form-hint">Tambahkan catatan untuk menjelaskan alasan atau kondisi perpindahan bila diperlukan.</div></div>
            <button type="submit" class="btn btn-fik rounded-pill mt-2"><i class="bi bi-arrow-left-right me-1"></i> Simpan Distribusi</button>
        </form>
    </div>
</div>

<div class="modal fade tracking-modal" id="trackingModal" tabindex="-1" aria-labelledby="trackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="trackingModalLabel"><i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>Riwayat Perpindahan Aset</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div id="trackingModalContent" class="tracking-loading"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat riwayat perpindahan...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const assetIdInput = document.getElementById('distributionAsset');
    const assetSearch = document.getElementById('distributionAssetSearch');
    const assetCombobox = document.getElementById('distributionAssetCombobox');
    const assetList = document.getElementById('distributionAssetList');
    let assetOptions = [];
    const originSelect = document.getElementById('distributionOrigin');
    const targetSelect = document.getElementById('distributionRoom');
    const amountInput = document.getElementById('distributionAmount');
    const assetCode = document.getElementById('distributionAssetCode');
    const conditionInput = document.getElementById('distributionCondition');
    const stockInfo = document.getElementById('distributionStockInfo');
    const distributionForm = document.getElementById('distributionForm');
    const trackingModalElement = document.getElementById('trackingModal');
    const trackingContent = document.getElementById('trackingModalContent');
    const trackingBaseUrl = <?= json_encode($trackingDetailUrl) ?>;
    const assetSearchUrl = <?= json_encode($assetSearchUrl) ?>;
    const trackingModal = trackingModalElement && window.bootstrap ? new bootstrap.Modal(trackingModalElement) : null;
    let selectedAsset = null;
    let activeAssetIndex = -1;
    let assetSearchTimer = null;
    let assetRequestNumber = 0;

    const escapeHtml = function (value) {
        return String(value == null ? '' : value).replace(/[&<>'"]/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[character];
        });
    };

    const formatDateTime = function (value) {
        if (!value) return 'Tidak tercatat';
        const normalized = String(value).trim();
        const hasZone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized);
        const date = new Date(normalized.replace(' ', 'T') + (hasZone ? '' : '+07:00'));
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat('id-ID', {
            timeZone: 'Asia/Jakarta', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
        }).format(date).replace('.', ':') + ' WIB';
    };

    const refreshTargetOptions = function () {
        if (!originSelect || !targetSelect) return;
        Array.from(targetSelect.options).forEach(function (option) {
            option.disabled = option.value !== '' && option.value === originSelect.value;
        });
        if (targetSelect.value && targetSelect.value === originSelect.value) targetSelect.value = '';
    };

    const populateAssetDetails = function (option) {
        const hasAsset = Boolean(option && option.dataset.id);
        const stock = hasAsset ? Math.max(parseInt(option.dataset.stock || '0', 10), 0) : 0;

        selectedAsset = hasAsset ? option : null;
        assetIdInput.value = hasAsset ? option.dataset.id : '';
        assetCode.value = hasAsset ? option.dataset.code || '' : '';
        conditionInput.value = hasAsset ? option.dataset.condition || '-' : '';
        amountInput.max = stock > 0 ? String(stock) : '';
        if (stock > 0 && parseInt(amountInput.value || '1', 10) > stock) amountInput.value = String(stock);
        if (!amountInput.value || parseInt(amountInput.value, 10) < 1) amountInput.value = '1';

        if (hasAsset) {
            originSelect.value = option.dataset.roomId && option.dataset.roomId !== '0' ? option.dataset.roomId : '';
            stockInfo.innerHTML = '<i class="bi bi-box-seam me-1" aria-hidden="true"></i>Lokasi terakhir: <strong>' + escapeHtml(option.dataset.room || 'Belum ditempatkan') + '</strong> &middot; Stok tersedia: <strong>' + stock + '</strong>';
        } else {
            originSelect.value = '';
            stockInfo.innerHTML = '<i class="bi bi-box-seam me-1" aria-hidden="true"></i>Pilih aset untuk melihat lokasi dan stok tersedia.';
        }
        refreshTargetOptions();
    };

    const visibleAssetOptions = function () {
        return assetOptions;
    };

    const markActiveAsset = function (index) {
        const visible = visibleAssetOptions();
        if (!visible.length) return;
        activeAssetIndex = (index + visible.length) % visible.length;
        assetOptions.forEach(function (option) {
            const active = option === visible[activeAssetIndex];
            option.classList.toggle('is-active', active);
            option.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        visible[activeAssetIndex].scrollIntoView({block: 'nearest'});
    };

    const createAssetOption = function (asset) {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'asset-combobox__option';
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', 'false');
        option.dataset.id = String(asset.id || '');
        option.dataset.name = String(asset.name || '');
        option.dataset.code = String(asset.code || '');
        option.dataset.roomId = asset.room_id == null ? '' : String(asset.room_id);
        option.dataset.room = String(asset.room || 'Belum ditempatkan');
        option.dataset.stock = String(asset.stock || 0);
        option.dataset.condition = String(asset.condition || '-');

        const name = document.createElement('span');
        name.className = 'asset-combobox__option-name';
        name.textContent = option.dataset.name;
        const stock = document.createElement('span');
        stock.className = 'asset-combobox__option-stock';
        stock.textContent = option.dataset.stock + ' tersedia';
        const meta = document.createElement('span');
        meta.className = 'asset-combobox__option-meta';
        meta.textContent = option.dataset.code + ' · ' + option.dataset.room;
        option.append(name, stock, meta);
        return option;
    };

    const requestAssetOptions = function () {
        if (!assetSearch || !assetList) return;
        const requestNumber = ++assetRequestNumber;
        const url = assetSearchUrl + (assetSearchUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(assetSearch.value.trim());
        assetList.replaceChildren();
        const loading = document.createElement('div');
        loading.className = 'asset-combobox__empty';
        loading.textContent = 'Mencari aset...';
        assetList.appendChild(loading);
        assetList.hidden = false;
        assetSearch.setAttribute('aria-expanded', 'true');

        fetch(url, {headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (requestNumber !== assetRequestNumber) return;
                const results = payload.success && Array.isArray(payload.results) ? payload.results : [];
                assetOptions = results.map(createAssetOption);
                assetList.replaceChildren();
                if (assetOptions.length) {
                    assetOptions.forEach(function (option) { assetList.appendChild(option); });
                } else {
                    const empty = document.createElement('div');
                    empty.className = 'asset-combobox__empty';
                    empty.textContent = 'Aset tidak ditemukan.';
                    assetList.appendChild(empty);
                }
            })
            .catch(function () {
                if (requestNumber !== assetRequestNumber) return;
                assetOptions = [];
                assetList.innerHTML = '<div class="asset-combobox__empty text-danger">Pencarian aset gagal. Coba lagi.</div>';
            });
    };

    const filterAssetOptions = function (immediate) {
        if (!assetSearch || !assetList) return;
        activeAssetIndex = -1;
        assetList.hidden = false;
        assetSearch.setAttribute('aria-expanded', 'true');
        window.clearTimeout(assetSearchTimer);
        assetSearchTimer = window.setTimeout(requestAssetOptions, immediate ? 0 : 180);
    };

    const closeAssetList = function () {
        if (!assetList || !assetSearch) return;
        assetList.hidden = true;
        assetSearch.setAttribute('aria-expanded', 'false');
        activeAssetIndex = -1;
    };

    const chooseAsset = function (option) {
        if (!option) return;
        assetSearch.value = (option.dataset.name || '') + ' — ' + (option.dataset.code || '');
        assetSearch.setCustomValidity('');
        populateAssetDetails(option);
        closeAssetList();
    };

    if (assetSearch) {
        assetSearch.addEventListener('focus', function () { filterAssetOptions(true); });
        assetSearch.addEventListener('input', function () {
            if (selectedAsset && assetSearch.value !== (selectedAsset.dataset.name || '') + ' — ' + (selectedAsset.dataset.code || '')) {
                populateAssetDetails(null);
            }
            filterAssetOptions(false);
        });
        assetSearch.addEventListener('keydown', function (event) {
            const visible = visibleAssetOptions();
            if (event.key === 'ArrowDown') { event.preventDefault(); markActiveAsset(activeAssetIndex + 1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); markActiveAsset(activeAssetIndex - 1); }
            else if (event.key === 'Enter' && activeAssetIndex >= 0) { event.preventDefault(); chooseAsset(visible[activeAssetIndex]); }
            else if (event.key === 'Escape') closeAssetList();
        });
    }
    if (assetList) {
        assetList.addEventListener('click', function (event) {
            const option = event.target.closest('.asset-combobox__option');
            if (option) chooseAsset(option);
        });
    }
    document.addEventListener('mousedown', function (event) {
        if (assetCombobox && !assetCombobox.contains(event.target)) closeAssetList();
    });
    if (originSelect) originSelect.addEventListener('change', refreshTargetOptions);
    if (distributionForm) {
        distributionForm.addEventListener('submit', function (event) {
            const stock = selectedAsset ? parseInt(selectedAsset.dataset.stock || '0', 10) : 0;
            const amount = parseInt(amountInput.value || '0', 10);
            let message = '';
            if (!assetIdInput.value || !selectedAsset) {
                message = 'Pilih aset dari hasil pencarian terlebih dahulu.';
                assetSearch.setCustomValidity(message);
                assetSearch.reportValidity();
                assetSearch.setCustomValidity('');
            } else if (originSelect.value && targetSelect.value && originSelect.value === targetSelect.value) {
                message = 'Ruangan tujuan harus berbeda dari ruangan asal.';
            } else if (!Number.isInteger(amount) || amount < 1 || amount > stock) {
                message = 'Jumlah distribusi harus berada dalam batas stok tersedia.';
            }

            if (message) {
                event.preventDefault();
                if (assetIdInput.value && selectedAsset) {
                    amountInput.setCustomValidity(message);
                    amountInput.reportValidity();
                    amountInput.setCustomValidity('');
                }
            }
        });
    }

    const renderTracking = function (payload) {
        const asset = payload.asset || {};
        const history = Array.isArray(payload.history) ? payload.history : [];
        const summary = '<div class="distribution-asset-summary">'
            + '<div><div class="distribution-asset-summary__name">' + escapeHtml(asset.nama_aset || '-') + '</div>'
            + '<div class="distribution-asset-summary__meta">' + escapeHtml(asset.kode_aset || '-') + ' &middot; Kondisi: ' + escapeHtml(asset.kondisi || '-') + '</div></div>'
            + '<span class="distribution-condition">Stok tersedia: ' + escapeHtml(asset.jumlah_tersedia || 0) + '</span></div>'
            + '<div class="tracking-current-location mt-3"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><div><strong>Lokasi terakhir</strong><br>' + escapeHtml(asset.lokasi_terakhir || '-') + '</div></div>';

        if (!history.length) {
            trackingContent.innerHTML = summary + '<div class="tracking-loading">Belum ada riwayat perpindahan untuk aset ini.</div>';
            return;
        }

        const events = history.map(function (movement, index) {
            const condition = movement.kondisi_aset || asset.kondisi || '-';
            const note = movement.keterangan ? '<div class="tracking-event__note">' + escapeHtml(movement.keterangan) + '</div>' : '';
            const receiver = movement.penerima ? '<span><i class="bi bi-person-check me-1" aria-hidden="true"></i>Penerima: ' + escapeHtml(movement.penerima) + '</span>' : '';
            return '<article class="tracking-event">'
                + '<div class="tracking-event__title">Perpindahan ' + (index + 1) + '</div>'
                + '<div class="tracking-event__route"><strong>' + escapeHtml(movement.ruangan_asal || 'Belum ditempatkan') + '</strong><i class="bi bi-arrow-right" aria-hidden="true"></i><strong>' + escapeHtml(movement.ruangan_tujuan || '-') + '</strong></div>'
                + '<div class="tracking-event__meta"><span><i class="bi bi-person me-1" aria-hidden="true"></i>Petugas: ' + escapeHtml(movement.nama_petugas || 'Laboran') + '</span>'
                + '<span><i class="bi bi-clock me-1" aria-hidden="true"></i>' + escapeHtml(formatDateTime(movement.waktu_distribusi || movement.created_at || movement.tanggal_distribusi)) + '</span>'
                + '<span><i class="bi bi-box-seam me-1" aria-hidden="true"></i>Jumlah: ' + escapeHtml(movement.jumlah || 0) + '</span>'
                + '<span><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Kondisi: ' + escapeHtml(condition) + '</span>' + receiver + '</div>' + note + '</article>';
        }).join('');
        trackingContent.innerHTML = summary + '<div class="tracking-timeline">' + events + '</div>';
    };

    document.querySelectorAll('.js-view-tracking').forEach(function (button) {
        button.addEventListener('click', function () {
            const assetId = button.dataset.assetId;
            if (!assetId || !trackingModal) return;
            trackingContent.innerHTML = '<div class="tracking-loading"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat riwayat perpindahan...</div>';
            trackingModal.show();
            fetch(trackingBaseUrl + encodeURIComponent(assetId), {headers: {'Accept': 'application/json'}})
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload.success) throw new Error(payload.message || 'Riwayat perpindahan tidak dapat dimuat.');
                    renderTracking(payload);
                })
                .catch(function (error) {
                    trackingContent.innerHTML = '<div class="tracking-loading text-danger"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>' + escapeHtml(error.message || 'Riwayat perpindahan tidak dapat dimuat.') + '</div>';
                });
        });
    });
});
</script>
</body>
</html>
