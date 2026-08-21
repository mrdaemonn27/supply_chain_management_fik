<?php
$pengajuan = is_array($pengajuan ?? null) ? $pengajuan : [];
$pengembalian = is_array($pengembalian ?? null) ? $pengembalian : [];
$filter_keyword = trim((string) ($filters['q'] ?? ''));
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);

if (!function_exists('kaprodi_loan_status_tone')) {
    function kaprodi_loan_status_tone($status)
    {
        $normalized = strtolower(trim((string) $status));

        if (strpos($normalized, 'tolak') !== false || strpos($normalized, 'reject') !== false) {
            return 'is-rejected';
        }

        if (
            strpos($normalized, 'dikembalikan') !== false ||
            strpos($normalized, 'selesai') !== false ||
            strpos($normalized, 'disetujui') !== false
        ) {
            return 'is-completed';
        }

        if (
            strpos($normalized, 'menunggu') !== false ||
            strpos($normalized, 'dipinjam') !== false ||
            strpos($normalized, 'kaprodi') !== false
        ) {
            return 'is-current';
        }

        return 'is-pending';
    }
}
if (!function_exists('kaprodi_client_filter_fields')) {
    function kaprodi_client_filter_fields($scope)
    {
        if ($scope === 'return') {
            return [
                'peminjam' => ['label' => 'Peminjam / NIM', 'placeholder' => 'Cari peminjam / NIM'],
                'barang' => ['label' => 'Nama barang', 'placeholder' => 'Cari nama barang'],
                'masa' => ['label' => 'Masa pinjam', 'placeholder' => 'Pilih tanggal pinjam', 'type' => 'date'],
                'status' => ['label' => 'Status pengembalian', 'placeholder' => 'Cari status pengembalian'],
            ];
        }
        return [
            'number' => ['label' => 'No. peminjaman', 'placeholder' => 'Cari nomor peminjaman'],
            'peminjam' => ['label' => 'Peminjam / NIM', 'placeholder' => 'Cari peminjam / NIM'],
            'barang' => ['label' => 'Nama barang / kode', 'placeholder' => 'Cari nama barang / kode'],
            'lab' => ['label' => 'Laboratorium', 'placeholder' => 'Cari laboratorium'],
            'masa' => ['label' => 'Masa pinjam', 'placeholder' => 'Pilih tanggal pinjam', 'type' => 'date'],
            'status' => ['label' => 'Status', 'placeholder' => 'Cari status approval'],
        ];
    }
}
if (!function_exists('render_kaprodi_client_filter')) {
    function render_kaprodi_client_filter($scope, $id)
    {
        $fields = kaprodi_client_filter_fields($scope);
        $default_field = (string) array_key_first($fields);
        $default_meta = $fields[$default_field];
        ?>
        <div id="<?= html_escape($id) ?>" class="kp-multi-filter" data-kp-multi-filter data-max-filters="4">
            <div class="kp-multi-filter-heading">
                <h3><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter pencarian</h3>
            </div>
            <div class="kp-multi-filter-list" data-filter-list>
                <div class="kp-multi-filter-row" data-filter-row>
                    <select class="form-select kp-multi-filter-field" aria-label="Jenis filter 1">
                        <?php foreach ($fields as $key => $meta): ?>
                            <option value="<?= html_escape($key) ?>" data-input-type="<?= html_escape($meta['type'] ?? 'search') ?>" data-placeholder="<?= html_escape($meta['placeholder']) ?>"><?= html_escape($meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="<?= html_escape($default_meta['type'] ?? 'search') ?>" class="form-control kp-multi-filter-value" placeholder="<?= html_escape($default_meta['placeholder']) ?>" autocomplete="off" aria-label="Nilai filter 1">
                    <div class="kp-multi-filter-tools">
                        <button type="button" class="btn btn-outline-secondary kp-multi-filter-icon kp-multi-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-primary kp-multi-filter-icon kp-multi-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title ?? 'Approval Peminjaman Kaprodi') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
    <style>
        .kaprodi-loan-page {
            --kp-bg: var(--scm-theme-bg, #f4f5f7);
            --kp-surface: var(--scm-theme-surface, #ffffff);
            --kp-surface-soft: var(--scm-theme-surface-soft, #f7f8fa);
            --kp-border: var(--scm-theme-border, #dfe3e8);
            --kp-text: var(--scm-theme-text, #18202b);
            --kp-muted: var(--scm-theme-muted, #6c7784);
            --kp-orange: #ff6b00;
            --kp-orange-soft: #fff5e9;
            min-height: 100vh;
            background: var(--kp-bg);
            color: var(--kp-text);
            font-family: Poppins, sans-serif;
            font-size: 14px;
        }

        .kp-topbar {
            background: #202020;
            border-bottom: 3px solid var(--kp-orange);
            color: #fff;
        }

        .kp-topbar-inner {
            min-height: 66px;
        }

        .kp-topbar-title {
            font-size: .96rem;
            font-weight: 700;
        }

        .kp-topbar-subtitle {
            color: rgba(255, 255, 255, .62);
            font-size: .72rem;
        }

        .kp-topbar .btn {
            min-height: 30px;
            padding: .3rem .8rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .kp-topbar .kp-logout-button {
            border-color: var(--kp-orange);
            background: var(--kp-orange);
            color: #fff;
        }

        .kp-topbar .kp-logout-button:hover,
        .kp-topbar .kp-logout-button:focus {
            border-color: #e96000;
            background: #e96000;
            color: #fff;
        }

        .kp-page-content {
            padding-top: 22px;
            padding-bottom: 32px;
        }

        .scm-dashboard-kaprodi .topbar-actions {
            margin-left: auto;
        }

        .brand-mark {
            display: inline-flex;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(234, 91, 26, .16);
            color: #ea5b1a;
            font-size: 1.35rem;
        }

        .notif-bell {
            display: inline-flex;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            align-items: center;
            justify-content: center;
        }

        .notif-menu {
            width: min(380px, calc(100vw - 32px));
            max-height: min(420px, calc(100vh - 110px));
            overflow-y: auto;
        }

        .theme-toggle {
            width: 38px;
            height: 38px;
            flex: 0 0 38px !important;
            padding: 0 !important;
        }

        html.scm-theme-light {
            --scm-bg: #f3f4f6;
            --scm-surface: #ffffff;
            --scm-surface-strong: #eef0f2;
            --scm-border: #dfe3e6;
            --scm-text: #1c2024;
            --scm-muted: #68727b;
            --scm-orange-soft: rgba(234, 91, 26, .1);
        }

        html.scm-theme-light .scm-dashboard {
            background: var(--scm-bg) !important;
            color: var(--scm-text) !important;
        }

        html.scm-theme-light .scm-dashboard .dashboard-sidebar {
            border-color: #e3e6e8;
            background: #ffffff;
        }

        html.scm-theme-light .scm-dashboard .sidebar-link {
            color: #59636b;
        }

        html.scm-theme-light .scm-dashboard .sidebar-link i {
            color: #7e878e;
        }

        html.scm-theme-light .scm-dashboard .sidebar-link:hover,
        html.scm-theme-light .scm-dashboard .sidebar-link.active {
            color: #ffffff;
        }

        html.scm-theme-light .scm-dashboard .sidebar-link:hover i,
        html.scm-theme-light .scm-dashboard .sidebar-link.active i {
            color: #ffffff;
        }

        html.scm-theme-light .scm-dashboard .sidebar-footer {
            border-color: #e3e6e8;
        }

        html.scm-theme-light .scm-dashboard .topbar {
            border-color: #e3e6e8 !important;
            background: #ffffff !important;
            box-shadow: 0 5px 18px rgba(35, 42, 47, .06);
        }

        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light {
            border-color: #cbd2d7;
            color: #4e5961;
        }

        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light:hover {
            border-color: #aeb7bd;
            background: #f1f3f4;
            color: #1c2024;
        }

        html.scm-theme-light .scm-dashboard .form-control,
        html.scm-theme-light .scm-dashboard .form-select,
        html.scm-theme-light .scm-dashboard .input-group-text,
        html.scm-theme-light .scm-dashboard .kp-control,
        html.scm-theme-light .scm-dashboard .kp-page-size select {
            border-color: #cfd6da !important;
            background-color: #ffffff !important;
            color: #283138 !important;
        }

        html.scm-theme-light .scm-dashboard .form-control::placeholder {
            color: #89939a !important;
            opacity: 1;
        }

        html.scm-theme-light .scm-dashboard input[type="date"] {
            color-scheme: light;
        }

        html.scm-theme-light .scm-dashboard main,
        html.scm-theme-light .scm-dashboard .dashboard-content {
            background: var(--scm-bg);
        }

        .kp-page-heading {
            margin-bottom: 18px;
        }

        .kp-page-heading h1 {
            margin: 0 0 4px;
            color: var(--kp-text);
            font-size: clamp(1.4rem, 2vw, 1.85rem);
            font-weight: 700;
        }

        .kp-page-heading p {
            margin: 0;
            color: var(--kp-muted);
            font-size: .88rem;
        }

        .kp-context {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            color: var(--kp-muted);
            font-size: .76rem;
        }

        .kp-context-badge {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 13px;
            border: 1px solid #bdc6d0;
            border-radius: 999px;
            background: var(--kp-surface);
            color: var(--kp-text);
            font-weight: 500;
        }

        .kp-card {
            overflow: hidden;
            border: 1px solid var(--kp-border);
            border-radius: 9px;
            background: var(--kp-surface);
            box-shadow: 0 5px 18px rgba(25, 36, 50, .045);
        }

        .kp-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 17px 20px;
        }

        .kp-section-title {
            margin: 0 0 3px;
            color: var(--kp-text);
            font-size: 1rem;
            font-weight: 700;
        }

        .kp-section-copy {
            margin: 0;
            color: var(--kp-muted);
            font-size: .76rem;
        }

        .kp-count-badge,
        .kp-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            width: max-content;
            min-height: 29px;
            padding: 5px 11px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        .kp-count-badge,
        .kp-status-badge.is-current {
            border-color: #f4bd70;
            background: var(--kp-orange-soft);
            color: #965600;
        }

        .kp-status-badge.is-completed {
            border-color: #9ed7bd;
            background: #eaf8f1;
            color: #13734d;
        }

        .kp-status-badge.is-pending {
            border-color: #d6dbe1;
            background: #f2f4f6;
            color: #66717d;
        }

        .kp-status-badge.is-rejected {
            border-color: #efb2b2;
            background: #fff0f0;
            color: #b4232d;
        }

        .kp-status-dot {
            width: 7px;
            height: 7px;
            flex: 0 0 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .kp-filter-card {
            padding: 14px 18px 13px;
        }

        .kp-multi-filter {
            padding: 17px 18px;
            border-bottom: 1px solid var(--kp-border);
            background: var(--kp-surface);
        }

        .kp-filter-card .kp-multi-filter {
            padding: 0;
            border: 0;
        }

        .kp-multi-filter-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .kp-multi-filter-heading h3 {
            margin: 0;
            color: var(--kp-text);
            font-size: .92rem;
            font-weight: 700;
        }

        .kp-multi-filter-heading h3 i { color: var(--kp-orange); }
        .kp-multi-filter-heading p { margin: 3px 0 0; color: var(--kp-muted); font-size: .69rem; }
        .kp-multi-filter-note { color: var(--kp-muted); font-size: .68rem; white-space: nowrap; }
        .kp-multi-filter-list { display: grid; gap: 9px; }
        .kp-multi-filter-row { display: grid; grid-template-columns: minmax(205px, .72fr) minmax(270px, 1.55fr) auto; align-items: center; gap: 9px; }
        .kp-multi-filter-row .form-select, .kp-multi-filter-row .form-control { min-height: 42px; border-color: #cbd3dc; background-color: var(--kp-surface); color: var(--kp-text); font-size: .74rem; box-shadow: none; }
        .kp-multi-filter-row .form-select:focus, .kp-multi-filter-row .form-control:focus { border-color: var(--kp-orange); box-shadow: 0 0 0 .2rem rgba(255, 107, 0, .12); }
        .kp-multi-filter-tools { display: flex; align-items: center; gap: 8px; }
        .kp-multi-filter-icon { width: 42px; height: 42px; display: inline-flex; flex: 0 0 42px; align-items: center; justify-content: center; padding: 0; border-radius: 50%; }
        .kp-multi-filter-add { border-color: var(--kp-orange); color: var(--kp-orange); }
        .kp-multi-filter-add:hover { border-color: var(--kp-orange); background: var(--kp-orange); color: #fff; }
        .kp-multi-filter-icon:disabled { opacity: .38; }

        .kp-filter-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1.7fr) minmax(170px, .72fr) minmax(170px, .72fr) auto;
            gap: 10px;
            align-items: end;
        }

        .kp-field-label {
            display: block;
            margin-bottom: 6px;
            color: var(--kp-text);
            font-size: .66rem;
            font-weight: 700;
        }

        .kp-control {
            height: 40px;
            border-color: #cbd3dc;
            background-color: var(--kp-surface);
            color: var(--kp-text);
            font-size: .75rem;
            box-shadow: none !important;
        }

        .kp-control:focus {
            border-color: var(--kp-orange);
        }

        .kp-search-group .input-group-text {
            width: 42px;
            justify-content: center;
            border-color: #cbd3dc;
            background: var(--kp-surface);
            color: var(--kp-text);
        }

        .kp-search-group .form-control {
            border-left: 0;
        }

        .kp-filter-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kp-filter-actions .btn {
            height: 40px;
            padding-inline: 14px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .kp-btn-primary {
            border-color: var(--kp-orange);
            background: var(--kp-orange);
            color: #fff;
        }

        .kp-btn-primary:hover,
        .kp-btn-primary:focus {
            border-color: #e96000;
            background: #e96000;
            color: #fff;
        }

        .kp-btn-reset {
            border-color: #c6ced7;
            background: var(--kp-surface);
            color: var(--kp-muted);
        }

        .kp-filter-hint {
            margin-top: 7px;
            color: var(--kp-muted);
            font-size: .62rem;
        }

        .kp-table-card {
            margin-top: 12px;
        }

        .kp-table {
            min-width: 1120px;
            margin: 0;
            color: var(--kp-text);
        }

        .kp-table > :not(caption) > * > * {
            padding: 11px 14px;
            border-bottom-color: var(--kp-border);
            vertical-align: middle;
        }

        .kp-table thead th {
            border-bottom-width: 1px;
            background: var(--kp-surface-soft);
            color: #5f6974;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .025em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .kp-table tbody td {
            background: var(--kp-surface);
            color: var(--kp-text);
            font-size: .72rem;
        }

        .kp-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .kp-table tbody tr:hover td {
            background: var(--kp-surface-soft);
        }

        .kp-return-table {
            min-width: 1180px;
        }

        .kp-return-table th:nth-child(1),
        .kp-return-table td:nth-child(1) {
            width: 64px;
            min-width: 64px;
            text-align: center;
        }

        .kp-return-table th:nth-child(2),
        .kp-return-table td:nth-child(2) { min-width: 260px; }
        .kp-return-table th:nth-child(3),
        .kp-return-table td:nth-child(3) { min-width: 300px; }
        .kp-return-table th:nth-child(4),
        .kp-return-table td:nth-child(4) { min-width: 300px; }
        .kp-return-table th:nth-child(5),
        .kp-return-table td:nth-child(5) { min-width: 260px; }

        .kp-number-cell {
            color: var(--kp-muted) !important;
            font-weight: 600;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .kp-sort-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-weight: inherit;
            letter-spacing: inherit;
            text-transform: inherit;
        }

        .kp-sort-button i {
            color: #8c96a1;
            font-size: .68rem;
        }

        .kp-primary-text {
            color: var(--kp-text);
            font-size: .72rem;
            font-weight: 700;
        }

        .kp-secondary-text {
            margin-top: 2px;
            color: var(--kp-muted);
            font-size: .61rem;
        }

        .kp-item-list {
            display: grid;
            gap: 3px;
        }

        .kp-item-meta {
            margin-left: 8px;
            color: var(--kp-muted);
            font-size: .61rem;
            white-space: nowrap;
        }

        .kp-detail-button {
            min-height: 30px;
            padding: 5px 13px;
            border-color: #0d6efd;
            border-radius: 999px;
            font-size: .67rem;
            font-weight: 600;
        }

        .kp-empty-row td {
            padding: 38px 18px !important;
            color: var(--kp-muted) !important;
            text-align: center;
        }

        .kp-section-header {
            padding: 16px 19px;
            border-bottom: 1px solid var(--kp-border);
        }

        .kp-toolbar {
            display: flex;
            gap: 9px;
            align-items: center;
            padding: 10px 18px 9px;
            border-bottom: 1px solid var(--kp-border);
            background: var(--kp-surface);
        }

        .kp-toolbar .kp-search-group {
            flex: 1 1 auto;
        }

        .kp-toolbar .btn {
            height: 40px;
            padding-inline: 16px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
        }

        .kp-toolbar-hint {
            padding: 0 19px 9px;
            border-bottom: 1px solid var(--kp-border);
            background: var(--kp-surface);
            color: var(--kp-muted);
            font-size: .61rem;
        }

        .kp-pagination-footer {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 14px;
            min-height: 52px;
            padding: 8px 14px;
            border-top: 1px solid var(--kp-border);
            background: var(--kp-surface-soft);
            color: var(--kp-muted);
            font-size: .64rem;
        }

        .kp-page-size {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kp-page-size select {
            width: 62px;
            height: 31px;
            border-color: #cbd3dc;
            background-color: var(--kp-surface);
            color: var(--kp-text);
            font-size: .65rem;
        }

        .kp-page-status {
            text-align: center;
        }

        .kp-pagination-nav {
            display: flex;
            justify-content: flex-end;
        }

        .kp-pagination {
            display: inline-flex;
            overflow: hidden;
            margin: 0;
            padding: 0;
            border: 1px solid var(--kp-border);
            border-radius: 4px;
            background: var(--kp-surface);
            list-style: none;
        }

        .kp-page-button {
            min-width: 36px;
            height: 32px;
            padding: 0 10px;
            border: 0;
            border-right: 1px solid var(--kp-border);
            background: var(--kp-surface);
            color: var(--kp-text);
            font-size: .67rem;
        }

        .kp-pagination li:last-child .kp-page-button {
            border-right: 0;
        }

        .kp-page-button.is-active {
            background: var(--kp-orange);
            color: #fff;
        }

        .kp-page-button:disabled {
            background: var(--kp-surface);
            color: #a7afb9;
            cursor: default;
        }

        .kp-loan-modal .modal-dialog {
            max-width: 760px;
        }

        .kp-loan-modal .modal-content {
            overflow: hidden;
            border: 1px solid var(--kp-border);
            border-radius: 13px;
            background: var(--kp-surface);
            color: var(--kp-text);
            box-shadow: 0 22px 60px rgba(15, 23, 42, .2);
        }

        .kp-loan-modal .modal-header {
            align-items: flex-start;
            padding: 19px 21px 17px;
            border-bottom-color: var(--kp-border);
        }

        .kp-modal-kicker {
            margin-bottom: 4px;
            color: var(--kp-muted);
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .kp-loan-modal .modal-title {
            color: var(--kp-text);
            font-size: 1rem;
            font-weight: 700;
        }

        .kp-modal-meta {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 14px;
            margin-top: 13px;
        }

        .kp-meta-box {
            padding: 11px 13px;
            border: 1px solid var(--kp-border);
            border-radius: 8px;
            background: var(--kp-surface-soft);
        }

        .kp-meta-label,
        .kp-modal-section-title {
            color: var(--kp-muted);
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .kp-meta-value {
            margin-top: 3px;
            color: var(--kp-text);
            font-size: .75rem;
            font-weight: 600;
        }

        .kp-loan-modal .modal-body {
            padding: 19px 21px;
        }

        .kp-modal-section + .kp-modal-section {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--kp-border);
        }

        .kp-purpose {
            margin: 7px 0 0;
            color: var(--kp-text);
            font-size: .76rem;
            line-height: 1.65;
        }

        .kp-detail-table {
            overflow: hidden;
            margin-top: 9px;
            border: 1px solid var(--kp-border);
            border-radius: 8px;
        }

        .kp-detail-table table {
            margin: 0;
            color: var(--kp-text);
        }

        .kp-detail-table th,
        .kp-detail-table td {
            padding: 9px 10px;
            border-bottom-color: var(--kp-border);
            font-size: .67rem;
            vertical-align: middle;
        }

        .kp-detail-table th {
            background: var(--kp-surface-soft);
            color: var(--kp-muted);
            font-size: .59rem;
            letter-spacing: .035em;
            text-transform: uppercase;
        }

        .kp-detail-table td {
            background: var(--kp-surface);
            color: var(--kp-text);
        }

        .kp-loan-modal textarea {
            margin-top: 8px;
            min-height: 92px;
            resize: vertical;
            border-color: #cbd3dc;
            background: var(--kp-surface);
            color: var(--kp-text);
            font-size: .73rem;
        }

        .kp-loan-modal textarea:focus {
            border-color: var(--kp-orange);
            box-shadow: 0 0 0 .2rem rgba(255, 107, 0, .12);
        }

        .kp-loan-modal .modal-footer {
            gap: 8px;
            padding: 14px 21px;
            border-top-color: var(--kp-border);
            background: var(--kp-surface-soft);
        }

        .kp-loan-modal .modal-footer .btn {
            min-width: 92px;
            min-height: 36px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 600;
        }

        html.scm-theme-dark .kp-topbar {
            background: #15181d;
        }

        html.scm-theme-dark .kp-search-group .input-group-text,
        html.scm-theme-dark .kp-control,
        html.scm-theme-dark .kp-page-size select,
        html.scm-theme-dark .kp-loan-modal textarea {
            border-color: var(--kp-border);
            background-color: var(--kp-surface);
            color: var(--kp-text);
        }

        html.scm-theme-dark .kp-loan-modal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        @media (max-width: 991.98px) {
            .kp-filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .kp-keyword-field,
            .kp-filter-actions {
                grid-column: 1 / -1;
            }

            .kp-filter-actions {
                justify-content: flex-end;
            }
        }

        @media (max-width: 767.98px) {
            .kp-multi-filter { padding: 14px; }
            .kp-multi-filter-heading { flex-direction: column; gap: 7px; }
            .kp-multi-filter-note { white-space: normal; }
            .kp-multi-filter-row { grid-template-columns: 1fr; gap: 8px; padding: 11px; border: 1px solid var(--kp-border); border-radius: 9px; }
            .kp-multi-filter-tools { justify-content: flex-end; }
            .topbar-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .topbar-actions .btn,
            .topbar-actions .notif-bell {
                flex: 0 0 auto;
            }

            .kp-page-content {
                padding-top: 16px;
            }

            .kp-page-heading,
            .kp-summary {
                align-items: flex-start;
            }

            .kp-page-heading > .row {
                gap: 12px;
            }

            .kp-context {
                justify-content: flex-start;
            }

            .kp-summary {
                flex-direction: column;
                padding: 15px;
            }

            .kp-filter-card,
            .kp-section-header {
                padding-inline: 14px;
            }

            .kp-filter-grid {
                grid-template-columns: 1fr;
            }

            .kp-keyword-field,
            .kp-filter-actions {
                grid-column: auto;
            }

            .kp-filter-actions {
                justify-content: stretch;
            }

            .kp-filter-actions .btn {
                flex: 1;
            }

            .kp-toolbar {
                align-items: stretch;
                flex-direction: column;
                padding-inline: 14px;
            }

            .kp-toolbar .btn {
                align-self: flex-end;
            }

            .kp-pagination-footer {
                grid-template-columns: 1fr;
                justify-items: center;
                padding-block: 12px;
            }

            .kp-page-size,
            .kp-pagination-nav {
                justify-content: center;
            }

            .kp-modal-meta {
                grid-template-columns: 1fr;
            }

            .kp-loan-modal .modal-header,
            .kp-loan-modal .modal-body,
            .kp-loan-modal .modal-footer {
                padding-inline: 16px;
            }

            .kp-loan-modal .modal-footer .btn {
                flex: 1 1 auto;
                min-width: 0;
            }
        }
    </style>
</head>
<body class="scm-dashboard scm-dashboard-kaprodi kaprodi-loan-page">
<aside class="dashboard-sidebar" aria-label="Navigasi Panel Kaprodi">
    <a class="sidebar-brand" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>">
        <span class="sidebar-brand-mark"><i class="bi bi-building-check"></i></span>
        <span><strong>SCM FIK</strong><small>Panel Kaprodi</small></span>
    </a>
    <div class="sidebar-caption">Pengajuan prodi</div>
    <nav class="sidebar-nav">
        <a class="sidebar-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>">
            <i class="bi bi-grid-1x2"></i><span>Panel</span>
        </a>
        <a class="sidebar-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=ajukan') ?>">
            <i class="bi bi-plus-square"></i><span>Ajukan Kebutuhan</span>
        </a>
        <a class="sidebar-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>">
            <i class="bi bi-clock-history"></i><span>Riwayat Pengajuan</span>
        </a>
        <a class="sidebar-link active" href="<?= base_url('index.php/kaprodi/peminjaman') ?>" aria-current="page">
            <i class="bi bi-patch-check"></i><span>Approval Peminjaman</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <span class="sidebar-status-dot"></span><span>System operational</span>
    </div>
</aside>

<div class="dashboard-content">
<header class="topbar sticky-top">
    <div class="container-fluid px-3 px-lg-4 py-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div class="dashboard-topbar-brand d-flex align-items-center gap-3">
                <span class="brand-mark"><i class="bi bi-building-check"></i></span>
                <div>
                    <div class="fw-bold">Panel Kaprodi</div>
                </div>
            </div>
            <div class="topbar-actions d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button
                        id="kaprodiNotificationButton"
                        class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifikasi"
                    >
                        <i class="bi bi-bell"></i>
                        <?php if ($notif_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                        <div class="fw-bold px-2 py-1">Notifikasi</div>
                        <?php if (empty($notif_items)): ?>
                            <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                        <?php else: ?>
                            <?php foreach ($notif_items as $notification): ?>
                                <?php $notification_link = base_url('index.php/kaprodi/dashboard/notifikasi/' . (int) $notification->id_notifikasi); ?>
                                <a
                                    class="dropdown-item rounded-3 py-2 <?= empty($notification->is_read) ? 'bg-light' : '' ?>"
                                    href="<?= html_escape($notification_link) ?>"
                                >
                                    <div class="fw-semibold small"><?= html_escape($notification->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($notification->pesan) ?></div>
                                    <div class="small text-muted mt-1"><?= date('d/m/Y H:i', strtotime($notification->created_at)) ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-light btn-sm rounded-circle theme-toggle"
                    data-theme-toggle
                    aria-label="Ubah tema"
                    title="Ubah tema"
                >
                    <i class="bi bi-moon-stars" aria-hidden="true"></i>
                </button>
                <a href="<?= base_url('index.php/dashboard') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-globe me-1"></i> Web User
                </a>
                <a href="<?= base_url('index.php/auth/logout') ?>" class="btn btn-sm btn-fik rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container-fluid kp-page-content px-3 px-lg-4">
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?= html_escape($this->session->flashdata('success')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <div class="kp-page-heading">
        <div class="row align-items-end">
            <div class="col-lg">
                <h1>Approval Peminjaman</h1>
            </div>
            <div class="col-lg-auto">
                <div class="kp-context">
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>
    </div>

    <section class="kp-card kp-summary">
        <h2 class="kp-section-title">Approval Peminjaman oleh Kaprodi</h2>
        <span class="kp-count-badge">
            <span class="kp-status-dot"></span>
            <?= count($pengajuan) ?> menunggu ACC
        </span>
    </section>

    <section class="kp-card kp-filter-card mt-3" aria-label="Filter pengajuan peminjaman">
        <?php render_kaprodi_client_filter('approval', 'kaprodiApprovalFilters'); ?>
    </section>

    <section class="kp-card kp-table-card mb-3" aria-labelledby="kaprodiApprovalTitle">
        <div class="kp-section-header">
            <h2 id="kaprodiApprovalTitle" class="kp-section-title">Pengajuan Menunggu ACC</h2>
        </div>
        <div class="table-responsive">
            <table class="table kp-table">
                <thead>
                <tr>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="number">
                            No. Peminjaman <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="peminjam">
                            Nama Peminjam <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="barang">
                            Barang <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="lab">
                            Laboratorium <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="masa">
                            Masa Pinjam <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-approval-sort="status">
                            Status <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col" class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody id="kaprodiApprovalBody">
                <?php if (empty($pengajuan)): ?>
                    <tr class="kp-empty-row">
                        <td colspan="7">Tidak ada pengajuan menunggu ACC Kaprodi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengajuan as $p): ?>
                        <?php
                        $number = $p->group_id ?? $p->id_peminjaman ?? '-';
                        $item_names = [];
                        $item_search = [];
                        $laboratories = [];
                        foreach (($p->detail_barang ?? []) as $detail) {
                            $item_names[] = $detail->nama_aset ?? '-';
                            $item_search[] = trim((string) ($detail->nama_aset ?? '') . ' ' . (string) ($detail->kode_aset ?? ''));
                            if (!empty($detail->nama_ruangan)) {
                                $laboratories[] = $detail->nama_ruangan;
                            }
                        }
                        $laboratories = array_values(array_unique($laboratories));
                        $period = masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null);
                        $status = $p->status ?? '-';
                        $search_text = implode(' ', [
                            $number,
                            $p->id_peminjaman ?? '',
                            $p->nama_peminjam ?? '',
                            $p->nim_nip ?? '',
                            implode(' ', $item_names),
                            implode(' ', $laboratories),
                            $period,
                            $status,
                        ]);
                        ?>
                        <tr
                            data-approval-row
                            data-search="<?= html_escape($search_text) ?>"
                            data-date-start="<?= html_escape(substr((string) ($p->tanggal_pinjam ?? ''), 0, 10)) ?>"
                            data-filter-number="<?= html_escape(trim((string) $number . ' ' . (string) ($p->id_peminjaman ?? ''))) ?>"
                            data-filter-peminjam="<?= html_escape(trim((string) ($p->nama_peminjam ?? '') . ' ' . (string) ($p->nim_nip ?? ''))) ?>"
                            data-filter-barang="<?= html_escape(implode(' ', $item_search)) ?>"
                            data-filter-lab="<?= html_escape(implode(' ', $laboratories)) ?>"
                            data-filter-masa="<?= html_escape(implode(' ', [substr((string) ($p->tanggal_pinjam ?? ''), 0, 10), substr((string) ($p->tanggal_kembali_rencana ?? ''), 0, 10), $period])) ?>"
                            data-filter-status="<?= html_escape($status) ?>"
                            data-sort-number="<?= html_escape($number) ?>"
                            data-sort-peminjam="<?= html_escape($p->nama_peminjam ?? '') ?>"
                            data-sort-barang="<?= html_escape(implode(' ', $item_names)) ?>"
                            data-sort-lab="<?= html_escape(implode(' ', $laboratories)) ?>"
                            data-sort-masa="<?= (int) strtotime((string) ($p->tanggal_pinjam ?? '')) ?>"
                            data-sort-status="<?= html_escape($status) ?>"
                        >
                            <td>
                                <div class="kp-primary-text"><?= html_escape($number) ?></div>
                                <div class="kp-secondary-text">ID <?= html_escape($p->id_peminjaman ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="kp-primary-text"><?= html_escape($p->nama_peminjam ?? '-') ?></div>
                                <div class="kp-secondary-text"><?= html_escape($p->nim_nip ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="kp-item-list">
                                    <?php if (empty($p->detail_barang)): ?>
                                        <span>-</span>
                                    <?php else: ?>
                                        <?php foreach ($p->detail_barang as $detail): ?>
                                            <div>
                                                <span class="kp-primary-text"><?= html_escape($detail->nama_aset ?? '-') ?></span>
                                                <span class="kp-item-meta"><?= (int) ($detail->jumlah_pinjam ?? 0) ?> unit</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= html_escape(implode(', ', $laboratories) ?: '-') ?></td>
                            <td>
                                <div class="kp-primary-text"><?= html_escape(tanggal_indonesia($p->tanggal_pinjam ?? null)) ?></div>
                                <div class="kp-secondary-text">s.d. <?= html_escape(tanggal_indonesia($p->tanggal_kembali_rencana ?? null)) ?></div>
                            </td>
                            <td>
                                <span class="kp-status-badge <?= kaprodi_loan_status_tone($status) ?>">
                                    <span class="kp-status-dot"></span><?= html_escape($status) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary kp-detail-button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#kaprodiApproval<?= (int) $p->id_peminjaman ?>"
                                >
                                    <i class="bi bi-eye me-1"></i>Detail
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="kaprodiApprovalEmpty" class="kp-empty-row d-none">
                        <td colspan="7">Tidak ada hasil yang sesuai dengan filter.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($pengajuan)): ?>
            <div class="kp-pagination-footer">
                <div class="kp-page-size">
                    <label for="kaprodiApprovalPageSize">Tampilkan:</label>
                    <select id="kaprodiApprovalPageSize" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">Semua</option>
                    </select>
                    <span id="kaprodiApprovalTotal"></span>
                </div>
                <div id="kaprodiApprovalPageStatus" class="kp-page-status"></div>
                <nav class="kp-pagination-nav" aria-label="Paging pengajuan">
                    <ul id="kaprodiApprovalPagination" class="kp-pagination"></ul>
                </nav>
            </div>
        <?php endif; ?>
    </section>

    <section class="kp-card" aria-labelledby="kaprodiReturnTitle">
        <div class="kp-section-header">
            <h2 id="kaprodiReturnTitle" class="kp-section-title">Status Pengembalian (Read-only)</h2>
        </div>
        <?php if (!empty($pengembalian)): ?>
            <?php render_kaprodi_client_filter('return', 'kaprodiReturnFilters'); ?>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table kp-table kp-return-table">
                <thead>
                <tr>
                    <th scope="col">No</th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-return-sort="peminjam">
                            Peminjam <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-return-sort="barang">
                            Nama Barang <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-return-sort="masa">
                            Masa Pinjam <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                    <th scope="col">
                        <button class="kp-sort-button" type="button" data-return-sort="status">
                            Status Pengembalian <i class="bi bi-arrow-down-up"></i>
                        </button>
                    </th>
                </tr>
                </thead>
                <tbody id="kaprodiReturnBody">
                <?php if (empty($pengembalian)): ?>
                    <tr class="kp-empty-row">
                        <td colspan="5">Belum ada data pengembalian.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pengembalian as $return_index => $p): ?>
                        <?php
                        $item_names = [];
                        foreach (($p->detail_barang ?? []) as $detail) {
                            $item_names[] = $detail->nama_aset ?? '-';
                        }
                        $period = masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null);
                        $status = $p->status ?? '-';
                        $search_text = implode(' ', [
                            $p->nama_peminjam ?? '',
                            $p->nim_nip ?? '',
                            implode(' ', $item_names),
                            $period,
                            $status,
                        ]);
                        ?>
                        <tr
                            data-return-row
                            data-search="<?= html_escape($search_text) ?>"
                            data-filter-peminjam="<?= html_escape(trim((string) ($p->nama_peminjam ?? '') . ' ' . (string) ($p->nim_nip ?? ''))) ?>"
                            data-filter-barang="<?= html_escape(implode(' ', $item_names)) ?>"
                            data-filter-masa="<?= html_escape(implode(' ', [substr((string) ($p->tanggal_pinjam ?? ''), 0, 10), substr((string) ($p->tanggal_kembali_rencana ?? ''), 0, 10), $period])) ?>"
                            data-filter-status="<?= html_escape($status) ?>"
                            data-sort-peminjam="<?= html_escape($p->nama_peminjam ?? '') ?>"
                            data-sort-barang="<?= html_escape(implode(' ', $item_names)) ?>"
                            data-sort-masa="<?= (int) strtotime((string) ($p->tanggal_pinjam ?? '')) ?>"
                            data-sort-status="<?= html_escape($status) ?>"
                        >
                            <td class="kp-number-cell" data-row-number><?= (int) $return_index + 1 ?></td>
                            <td>
                                <div class="kp-primary-text"><?= html_escape($p->nama_peminjam ?? '-') ?></div>
                                <div class="kp-secondary-text"><?= html_escape($p->nim_nip ?? '-') ?></div>
                            </td>
                            <td><?= html_escape(implode(', ', $item_names) ?: '-') ?></td>
                            <td><?= html_escape($period) ?></td>
                            <td>
                                <span class="kp-status-badge <?= kaprodi_loan_status_tone($status) ?>">
                                    <span class="kp-status-dot"></span><?= html_escape($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="kaprodiReturnEmpty" class="kp-empty-row d-none">
                        <td colspan="5">Tidak ada hasil yang sesuai dengan pencarian.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($pengembalian)): ?>
            <div class="kp-pagination-footer">
                <div class="kp-page-size">
                    <label for="kaprodiReturnPageSize">Tampilkan:</label>
                    <select id="kaprodiReturnPageSize" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">Semua</option>
                    </select>
                    <span id="kaprodiReturnTotal"></span>
                </div>
                <div id="kaprodiReturnPageStatus" class="kp-page-status"></div>
                <nav class="kp-pagination-nav" aria-label="Paging status pengembalian">
                    <ul id="kaprodiReturnPagination" class="kp-pagination"></ul>
                </nav>
            </div>
        <?php endif; ?>
    </section>
</main>
</div>

<?php foreach ($pengajuan as $p): ?>
    <?php
    $number = $p->group_id ?? $p->id_peminjaman ?? '-';
    $status = $p->status ?? '-';
    ?>
    <div
        class="modal fade kp-loan-modal"
        id="kaprodiApproval<?= (int) $p->id_peminjaman ?>"
        tabindex="-1"
        aria-labelledby="kaprodiApprovalTitle<?= (int) $p->id_peminjaman ?>"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form
                method="post"
                class="modal-content"
                action="<?= base_url('index.php/kaprodi/peminjaman/setujui/' . $p->id_peminjaman) ?>"
            >
                <div class="modal-header">
                    <div class="w-100 pe-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="kp-modal-kicker">Peminjaman</div>
                                <h2 id="kaprodiApprovalTitle<?= (int) $p->id_peminjaman ?>" class="modal-title">
                                    <?= html_escape($number) ?>
                                </h2>
                            </div>
                            <span class="kp-status-badge <?= kaprodi_loan_status_tone($status) ?>">
                                <span class="kp-status-dot"></span><?= html_escape($status) ?>
                            </span>
                        </div>
                        <div class="kp-modal-meta">
                            <div class="kp-meta-box">
                                <div class="kp-meta-label">Peminjam</div>
                                <div class="kp-meta-value"><?= html_escape($p->nama_peminjam ?? '-') ?></div>
                                <div class="kp-secondary-text">NIM/NIP: <?= html_escape($p->nim_nip ?? '-') ?></div>
                            </div>
                            <div class="kp-meta-box">
                                <div class="kp-meta-label">Periode</div>
                                <div class="kp-meta-value">
                                    <?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <section class="kp-modal-section">
                        <div class="kp-modal-section-title">Keperluan</div>
                        <p class="kp-purpose"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></p>
                    </section>

                    <section class="kp-modal-section">
                        <div class="kp-modal-section-title">Detail Barang</div>
                        <div class="kp-detail-table table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Kode</th>
                                    <th>Laboratorium</th>
                                    <th class="text-center">Jumlah</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($p->detail_barang)): ?>
                                    <tr><td colspan="4" class="text-center">Detail barang tidak tersedia.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($p->detail_barang as $detail): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= html_escape($detail->nama_aset ?? '-') ?></td>
                                            <td><?= html_escape($detail->kode_aset ?? '-') ?></td>
                                            <td><?= html_escape($detail->nama_ruangan ?? '-') ?></td>
                                            <td class="text-center"><?= (int) ($detail->jumlah_pinjam ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="kp-modal-section">
                        <label for="kaprodiNote<?= (int) $p->id_peminjaman ?>" class="kp-modal-section-title">Catatan ACC Kaprodi</label>
                        <textarea
                            id="kaprodiNote<?= (int) $p->id_peminjaman ?>"
                            name="catatan_kaprodi"
                            class="form-control"
                            rows="3"
                            placeholder="Catatan persetujuan atau alasan penolakan"
                        ></textarea>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button
                        type="submit"
                        formaction="<?= base_url('index.php/kaprodi/peminjaman/tolak/' . $p->id_peminjaman) ?>"
                        class="btn btn-outline-danger"
                        onclick="return confirm('Tolak pengajuan ini? Pastikan alasan sudah diisi.')"
                    >
                        <i class="bi bi-x-lg me-1"></i>Tolak
                    </button>
                    <button
                        type="submit"
                        class="btn btn-success"
                        onclick="return confirm('Setujui dan teruskan ke Laboran?')"
                    >
                        <i class="bi bi-check2 me-1"></i>Setujui
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
        new bootstrap.Tooltip(element);
    });

    function initMultiFilter(rootId) {
        var root = document.getElementById(rootId);
        if (!root) return null;
        var list = root.querySelector('[data-filter-list]');
        var maxFilters = Number(root.dataset.maxFilters || 4);
        if (!list) return root;

        function notify() {
            root.dispatchEvent(new CustomEvent('kp:filterchange'));
        }

        function syncInput(row, clearValue) {
            var select = row.querySelector('.kp-multi-filter-field');
            var input = row.querySelector('.kp-multi-filter-value');
            var option = select && select.options[select.selectedIndex];
            if (!select || !input || !option) return;
            if (clearValue) input.value = '';
            input.type = option.dataset.inputType || 'search';
            input.placeholder = option.dataset.placeholder || 'Ketik untuk mencari';
        }

        function updateButtons() {
            var rows = Array.prototype.slice.call(list.querySelectorAll('[data-filter-row]'));
            rows.forEach(function (row, index) {
                row.querySelector('.kp-multi-filter-remove').disabled = rows.length === 1;
                row.querySelector('.kp-multi-filter-add').disabled = rows.length >= maxFilters || index !== rows.length - 1;
            });
        }

        function addRow() {
            var rows = list.querySelectorAll('[data-filter-row]');
            if (!rows.length || rows.length >= maxFilters) return null;
            var sourceSelect = rows[0].querySelector('.kp-multi-filter-field');
            var row = document.createElement('div');
            row.className = 'kp-multi-filter-row';
            row.dataset.filterRow = '';
            row.innerHTML =
                '<select class="form-select kp-multi-filter-field" aria-label="Jenis filter ' + (rows.length + 1) + '">' + sourceSelect.innerHTML + '</select>' +
                '<input type="search" class="form-control kp-multi-filter-value" autocomplete="off" aria-label="Nilai filter ' + (rows.length + 1) + '">' +
                '<div class="kp-multi-filter-tools">' +
                    '<button type="button" class="btn btn-outline-secondary kp-multi-filter-icon kp-multi-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>' +
                    '<button type="button" class="btn btn-outline-primary kp-multi-filter-icon kp-multi-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>' +
                '</div>';
            list.appendChild(row);
            syncInput(row, false);
            updateButtons();
            return row;
        }

        list.querySelectorAll('[data-filter-row]').forEach(function (row) { syncInput(row, false); });
        updateButtons();
        list.addEventListener('click', function (event) {
            var row = event.target.closest('[data-filter-row]');
            if (!row) return;
            if (event.target.closest('.kp-multi-filter-add')) {
                var added = addRow();
                if (added) added.querySelector('.kp-multi-filter-value').focus();
            } else if (event.target.closest('.kp-multi-filter-remove') && list.querySelectorAll('[data-filter-row]').length > 1) {
                row.remove();
                updateButtons();
                notify();
            }
        });
        list.addEventListener('change', function (event) {
            if (!event.target.matches('.kp-multi-filter-field')) return;
            var row = event.target.closest('[data-filter-row]');
            syncInput(row, true);
            row.querySelector('.kp-multi-filter-value').focus();
            notify();
        });
        list.addEventListener('input', function (event) {
            if (event.target.matches('.kp-multi-filter-value')) notify();
        });
        return root;
    }

    function initClientTable(options) {
        var body = document.getElementById(options.bodyId);
        if (!body) {
            return;
        }

        var filterRoot = initMultiFilter(options.filterRootId);
        var rows = Array.prototype.slice.call(body.querySelectorAll(options.rowSelector));
        if (!rows.length) {
            return;
        }

        rows.forEach(function (row, index) {
            row.dataset.originalIndex = String(index);
        });

        var pageSizeSelect = document.getElementById(options.pageSizeId);
        var totalElement = document.getElementById(options.totalId);
        var pageStatusElement = document.getElementById(options.pageStatusId);
        var paginationElement = document.getElementById(options.paginationId);
        var emptyElement = document.getElementById(options.emptyId);
        var sortButtons = Array.prototype.slice.call(document.querySelectorAll(options.sortSelector));
        var currentPage = 1;
        var sortKey = '';
        var sortDirection = 'asc';

        function normalized(value) {
            return String(value || '').trim().toLocaleLowerCase('id');
        }

        function filteredRows() {
            var criteria = filterRoot ? Array.prototype.slice.call(filterRoot.querySelectorAll('[data-filter-row]')).map(function (filterRow) {
                return {
                    field: filterRow.querySelector('.kp-multi-filter-field').value,
                    value: normalized(filterRow.querySelector('.kp-multi-filter-value').value)
                };
            }).filter(function (criterion) { return criterion.value !== ''; }) : [];

            return rows.filter(function (row) {
                return criteria.every(function (criterion) {
                    var key = 'filter' + criterion.field.charAt(0).toUpperCase() + criterion.field.slice(1);
                    return normalized(row.dataset[key]).indexOf(criterion.value) !== -1;
                });
            });
        }

        function sortedRows(list) {
            if (!sortKey) {
                return list.slice().sort(function (left, right) {
                    return Number(left.dataset.originalIndex) - Number(right.dataset.originalIndex);
                });
            }

            return list.slice().sort(function (left, right) {
                var leftValue = left.dataset['sort' + sortKey.charAt(0).toUpperCase() + sortKey.slice(1)] || '';
                var rightValue = right.dataset['sort' + sortKey.charAt(0).toUpperCase() + sortKey.slice(1)] || '';
                var comparison;

                if (sortKey === 'masa') {
                    comparison = Number(leftValue) - Number(rightValue);
                } else {
                    comparison = normalized(leftValue).localeCompare(normalized(rightValue), 'id', {
                        numeric: true,
                        sensitivity: 'base'
                    });
                }

                if (comparison === 0) {
                    comparison = Number(left.dataset.originalIndex) - Number(right.dataset.originalIndex);
                }

                return sortDirection === 'asc' ? comparison : -comparison;
            });
        }

        function pageSizeFor(total) {
            if (!pageSizeSelect || pageSizeSelect.value === 'all') {
                return Math.max(total, 1);
            }
            return Math.max(Number(pageSizeSelect.value) || 10, 1);
        }

        function createPageButton(label, page, disabled, active, ariaLabel, ellipsis) {
            var item = document.createElement('li');
            if (ellipsis) {
                item.className = 'page-item disabled';
                item.setAttribute('aria-hidden', 'true');
                var separator = document.createElement('span');
                separator.className = 'kp-page-button page-link';
                separator.textContent = '...';
                item.appendChild(separator);
                return item;
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'kp-page-button' + (active ? ' is-active' : '');
            button.textContent = label;
            button.disabled = disabled;
            if (ariaLabel) {
                button.setAttribute('aria-label', ariaLabel);
            }
            if (active) {
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', function () {
                currentPage = page;
                render();
            });
            item.appendChild(button);
            return item;
        }

        function renderPagination(totalPages) {
            if (!paginationElement) {
                return;
            }

            paginationElement.innerHTML = '';
            paginationElement.appendChild(createPageButton('Previous', currentPage - 1, currentPage <= 1, false, 'Halaman sebelumnya'));

            var pageTokens = totalPages <= 7
                ? Array.from({ length: totalPages }, function (_, index) { return index + 1; })
                : currentPage <= 3
                    ? [1, 2, 3, 4, 5, 'ellipsis', totalPages]
                    : currentPage >= totalPages - 2
                        ? [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages]
                        : [1, 'ellipsis', currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, 'ellipsis', totalPages];
            pageTokens.forEach(function (token) {
                if (typeof token === 'string') {
                    paginationElement.appendChild(createPageButton('...', currentPage, true, false, 'Pemisah halaman', true));
                } else {
                    paginationElement.appendChild(createPageButton(String(token), token, false, token === currentPage, 'Halaman ' + token));
                }
            });

            paginationElement.appendChild(createPageButton('Next', currentPage + 1, currentPage >= totalPages, false, 'Halaman berikutnya'));
        }

        function updateSortButtons() {
            sortButtons.forEach(function (button) {
                var buttonKey = button.getAttribute(options.sortAttribute);
                var icon = button.querySelector('i');
                var header = button.closest('th');
                var isCurrent = buttonKey === sortKey;

                if (icon) {
                    icon.className = isCurrent
                        ? (sortDirection === 'asc' ? 'bi bi-sort-up-alt' : 'bi bi-sort-down')
                        : 'bi bi-arrow-down-up';
                }
                if (header) {
                    header.setAttribute('aria-sort', isCurrent ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
                }
            });
        }

        function render() {
            var visibleRows = sortedRows(filteredRows());
            var pageSize = pageSizeFor(visibleRows.length);
            var totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;

            rows.forEach(function (row) {
                row.classList.add('d-none');
            });

            visibleRows.forEach(function (row, index) {
                body.insertBefore(row, emptyElement || null);
                var numberElement = row.querySelector('[data-row-number]');
                if (numberElement) {
                    numberElement.textContent = String(index + 1);
                }
                row.classList.toggle('d-none', index < start || index >= end);
            });

            if (emptyElement) {
                emptyElement.classList.toggle('d-none', visibleRows.length !== 0);
            }
            if (totalElement) {
                totalElement.textContent = 'Total item: ' + visibleRows.length;
            }
            if (pageStatusElement) {
                pageStatusElement.textContent = 'Halaman: ' + currentPage + ' dari ' + totalPages;
            }

            renderPagination(totalPages);
            updateSortButtons();
        }

        if (filterRoot) {
            filterRoot.addEventListener('kp:filterchange', function () {
                currentPage = 1;
                render();
            });
        }

        if (pageSizeSelect) {
            pageSizeSelect.addEventListener('change', function () {
                currentPage = 1;
                render();
            });
        }

        sortButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var nextKey = button.getAttribute(options.sortAttribute);
                if (sortKey === nextKey) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey = nextKey;
                    sortDirection = 'asc';
                }
                currentPage = 1;
                render();
            });
        });

        render();
    }

    initClientTable({
        bodyId: 'kaprodiApprovalBody',
        rowSelector: '[data-approval-row]',
        filterRootId: 'kaprodiApprovalFilters',
        pageSizeId: 'kaprodiApprovalPageSize',
        totalId: 'kaprodiApprovalTotal',
        pageStatusId: 'kaprodiApprovalPageStatus',
        paginationId: 'kaprodiApprovalPagination',
        emptyId: 'kaprodiApprovalEmpty',
        sortSelector: '[data-approval-sort]',
        sortAttribute: 'data-approval-sort'
    });

    initClientTable({
        bodyId: 'kaprodiReturnBody',
        rowSelector: '[data-return-row]',
        filterRootId: 'kaprodiReturnFilters',
        pageSizeId: 'kaprodiReturnPageSize',
        totalId: 'kaprodiReturnTotal',
        pageStatusId: 'kaprodiReturnPageStatus',
        paginationId: 'kaprodiReturnPagination',
        emptyId: 'kaprodiReturnEmpty',
        sortSelector: '[data-return-sort]',
        sortAttribute: 'data-return-sort'
    });
});
</script>
</body>
</html>
