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
function query_kaprodi($filters, $page, $tab = 'riwayat', $kategori = '', $per_page = null) {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    if ($tab !== '') {
        $params['tab'] = $tab;
    }
    if ($kategori !== '') {
        $params['kategori'] = $kategori;
        if ($kategori === 'barang') {
            $params['jenis_pengajuan'] = 'Barang';
        } elseif ($kategori === 'jasa') {
            $params['jenis_pengajuan'] = 'Jasa';
        } elseif ($kategori === 'gabungan') {
            unset($params['jenis_pengajuan']);
        }
    }
    if ($per_page !== null && $per_page !== '') {
        $params['per_page'] = $per_page;
    }
    return http_build_query($params);
}
function table_row_number_kaprodi($index, $page = 1, $per_page = '10') {
    $page = max(1, (int) $page);
    if ((string) $per_page === 'all') {
        return (int) $index + 1;
    }
    $page_size = max(1, (int) $per_page ?: 10);
    return (($page - 1) * $page_size) + (int) $index + 1;
}
function compact_pagination_kaprodi($current, $last) {
    $current = max(1, min((int) $current, max(1, (int) $last)));
    $last = max(1, (int) $last);
    if ($last <= 7) return range(1, $last);
    if ($current <= 3) return array_merge(range(1, 5), ['ellipsis-after', $last]);
    if ($current >= $last - 2) return array_merge([$last - 4, $last - 3, $last - 2, $last - 1, $last]);
    return array_merge([1, 'ellipsis-before'], range($current - 2, $current + 2), ['ellipsis-after', $last]);
}
function history_filter_config_kaprodi() {
    return [
        'kode' => ['label' => 'Kode pengajuan', 'placeholder' => 'Cari kode pengajuan'],
        'pengajuan' => ['label' => 'Pengajuan / prodi', 'placeholder' => 'Cari nama pengajuan atau program studi'],
        'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari Barang, Jasa, atau Barang dan Jasa'],
        'kebutuhan' => ['label' => 'Kebutuhan / item', 'placeholder' => 'Cari kebutuhan atau nama item'],
        'status' => ['label' => 'Status', 'placeholder' => 'Cari status pengajuan'],
        'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal pengajuan', 'type' => 'date'],
    ];
}
function render_history_filter_kaprodi($rows, $hidden = []) {
    $fields = history_filter_config_kaprodi();
    $rows = is_array($rows) && !empty($rows) ? array_slice(array_values($rows), 0, 4) : [['field' => 'kode', 'value' => '']];
    ?>
    <form method="get" action="<?= base_url('index.php/kaprodi/dashboard') ?>" class="kaprodi-multi-filter scm-search-filter" data-kaprodi-multi-filter data-max-filters="4">
        <?php foreach ($hidden as $name => $value): ?>
            <input type="hidden" name="<?= html_escape($name) ?>" value="<?= html_escape((string) $value) ?>">
        <?php endforeach; ?>
        <div class="kaprodi-filter-heading">
            <h3><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter pencarian</h3>
        </div>
        <div class="kaprodi-filter-list" data-filter-list>
            <?php foreach ($rows as $index => $row): ?>
                <?php
                    $field = isset($fields[$row['field'] ?? '']) ? (string) $row['field'] : 'kode';
                    $meta = $fields[$field];
                ?>
                <div class="kaprodi-filter-row" data-filter-row>
                    <select name="filter_field[]" class="form-select kaprodi-filter-field" aria-label="Jenis filter <?= $index + 1 ?>">
                        <?php foreach ($fields as $field_key => $field_meta): ?>
                            <option value="<?= html_escape($field_key) ?>" data-input-type="<?= html_escape($field_meta['type'] ?? 'search') ?>" data-placeholder="<?= html_escape($field_meta['placeholder']) ?>" <?= $field === $field_key ? 'selected' : '' ?>><?= html_escape($field_meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="<?= html_escape($meta['type'] ?? 'search') ?>" name="filter_value[]" class="form-control kaprodi-filter-value" value="<?= html_escape($row['value'] ?? '') ?>" placeholder="<?= html_escape($meta['placeholder']) ?>" autocomplete="off" aria-label="Nilai filter <?= $index + 1 ?>">
                    <div class="kaprodi-filter-tools">
                        <button type="button" class="btn btn-outline-secondary kaprodi-filter-icon kaprodi-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-primary kaprodi-filter-icon kaprodi-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="scm-search-filter__actions mt-3">
            <button type="submit" class="btn scm-search-filter__apply"><i class="bi bi-search"></i> Terapkan filter</button>
            <button type="button" class="btn scm-search-filter__reset" data-kaprodi-filter-reset><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
    </form>
    <?php
}
$filters = $filters ?? [];
$stats = $stats ?? ['total' => 0, 'pengajuan' => 0, 'negosiasi' => 0, 'deal' => 0, 'selesai' => 0];
$dashboard_stats = $dashboard_stats ?? [
    'total_pengajuan' => 0, 'menunggu_proses' => 0, 'sedang_negosiasi' => 0,
    'disetujui_deal' => 0, 'direvisi' => 0, 'ditolak' => 0,
    'total_nilai_pengajuan' => 0, 'pengajuan_bulan_ini' => 0,
];
$dashboard_year = (int) ($dashboard_year ?? date('Y'));
$dashboard_years = $dashboard_years ?? [$dashboard_year];
$dashboard_monthly_submissions = $dashboard_monthly_submissions ?? array_fill(0, 12, 0);
$dashboard_monthly_values = $dashboard_monthly_values ?? array_fill(0, 12, 0);
$dashboard_status_breakdown = $dashboard_status_breakdown ?? ['Pengajuan' => 0, 'Sedang Negosiasi' => 0, 'Deal' => 0, 'Revisi' => 0, 'Ditolak' => 0];
$dashboard_type_breakdown = $dashboard_type_breakdown ?? ['Barang' => 0, 'Jasa' => 0, 'Barang dan Jasa' => 0];
$dashboard_activity = $dashboard_activity ?? [];
$page = $page ?? 1;
$per_page = $per_page ?? '10';
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan ?? []);
$active_tab = $active_tab ?? 'panel';
$active_tab = in_array($active_tab, ['panel', 'ajukan', 'riwayat'], true) ? $active_tab : 'panel';
$active_category = $active_category ?? 'gabungan';
$focus_id = (int) ($filters['id_pengajuan'] ?? 0);
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
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
        .kaprodi-filter-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
        .kaprodi-filter-heading h3 { margin: 0; color: var(--scm-text, #202124); font-size: 1rem; font-weight: 700; }
        .kaprodi-filter-heading h3 i { color: #ea5b1a; }
        .kaprodi-filter-heading p { margin: 3px 0 0; color: var(--scm-muted, #6b7280); font-size: .76rem; }
        .kaprodi-filter-note { color: var(--scm-muted, #6b7280); font-size: .73rem; white-space: nowrap; }
        .kaprodi-filter-list { display: grid; gap: 10px; }
        .kaprodi-filter-row { display: grid; grid-template-columns: minmax(210px, .72fr) minmax(280px, 1.55fr) auto; align-items: center; gap: 10px; }
        .kaprodi-filter-row .form-select, .kaprodi-filter-row .form-control { min-height: 44px; border-color: var(--scm-border, #d8dde3); color: var(--scm-text, #202124); background-color: var(--scm-surface, #fff); }
        .kaprodi-filter-tools { display: flex; align-items: center; gap: 8px; }
        .kaprodi-filter-icon { width: 44px; height: 44px; display: inline-flex; flex: 0 0 44px; align-items: center; justify-content: center; padding: 0; border-radius: 50%; }
        .kaprodi-filter-add { border-color: #ea5b1a; color: #ea5b1a; }
        .kaprodi-filter-add:hover { border-color: #ea5b1a; color: #fff; background: #ea5b1a; }
        .kaprodi-filter-icon:disabled { opacity: .38; }
        .summary-card { min-height: 96px; padding: 18px; }
        .summary-card .value { font-weight: 700; font-size: 1.5rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .jenis-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 132px;
            min-height: 32px;
            padding: 6px 12px;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 152px; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .history-table-card {
            --history-bg: var(--scm-surface, #111416);
            --history-head-bg: var(--scm-surface-strong, #181b1e);
            --history-border: var(--scm-border, #2b2f33);
            --history-text: var(--scm-text, #f7f7f7);
            --history-muted: var(--scm-muted, #a8adb5);
            border: 1px solid var(--history-border);
            border-radius: 10px;
            background: var(--history-bg);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        }
        html.scm-theme-light .history-table-card {
            --history-bg: #ffffff;
            --history-head-bg: #f9fafb;
            --history-border: #e5e7eb;
            --history-text: #1f2937;
            --history-muted: #6b7280;
        }
        html.scm-theme-light .scm-dashboard .panel-card.history-table-card {
            border-color: #e5e7eb !important;
            background: #ffffff !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
        }
        html.scm-theme-light .scm-dashboard .history-table.table-clean thead th {
            color: #6b7280 !important;
            background: #f9fafb !important;
            border-color: #e5e7eb !important;
        }
        .history-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .scm-dashboard .history-table {
            min-width: 1184px;
            margin: 0;
            --bs-table-bg: var(--history-bg) !important;
            --bs-table-color: var(--history-text) !important;
            --bs-table-border-color: var(--history-border) !important;
        }
        .scm-dashboard .history-table.table-clean thead th {
            padding: 14px 18px;
            color: var(--history-muted) !important;
            background: var(--history-head-bg) !important;
            border-color: var(--history-border) !important;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .scm-dashboard .history-table tbody tr { height: 152px; }
        .scm-dashboard .history-table tbody td {
            padding: 16px 18px;
            color: var(--history-text);
            background: var(--history-bg);
            border-color: var(--history-border);
            line-height: 1.5;
            vertical-align: middle;
            transition: background-color .16s ease;
        }
        .scm-dashboard .history-table tbody tr:hover > td { background: rgba(234, 91, 26, .045); }
        .scm-dashboard .history-table th:nth-child(1), .scm-dashboard .history-table td:nth-child(1) { width: 64px; min-width: 64px; text-align: center; }
        .scm-dashboard .history-table th:nth-child(2), .scm-dashboard .history-table td:nth-child(2) { min-width: 180px; }
        .scm-dashboard .history-table th:nth-child(3), .scm-dashboard .history-table td:nth-child(3) { min-width: 250px; }
        .scm-dashboard .history-table th:nth-child(4), .scm-dashboard .history-table td:nth-child(4) { width: 160px; text-align: center; }
        .scm-dashboard .history-table th:nth-child(5), .scm-dashboard .history-table td:nth-child(5) { min-width: 320px; }
        .scm-dashboard .history-table th:nth-child(6), .scm-dashboard .history-table td:nth-child(6) { width: 180px; text-align: center; }
        .scm-dashboard .history-table th:nth-child(7), .scm-dashboard .history-table td:nth-child(7) { min-width: 145px; white-space: nowrap; }
        .scm-dashboard .history-table th:nth-child(8), .scm-dashboard .history-table td:nth-child(8) { min-width: 120px; white-space: nowrap; }
        .scm-dashboard .history-table td:nth-child(3) > .fw-semibold,
        .scm-dashboard .history-table td:nth-child(5) > .text-muted {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .scm-dashboard .history-table td:nth-child(5) > .small:not(.text-muted) {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .scm-dashboard .history-table td:nth-child(5) > .small:nth-of-type(n + 4) { display: none; }
        .history-row-number { color: var(--history-muted) !important; font-weight: 600; font-variant-numeric: tabular-nums; }
        .scm-dashboard .history-table .jenis-badge,
        .scm-dashboard .history-table .status-pill {
            min-height: 32px;
            padding: 6px 12px;
            border: 1px solid #e5e7eb !important;
            border-radius: 8px;
            color: #374151 !important;
            background: #ffffff !important;
            box-shadow: none;
            font-size: .75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        .scm-dashboard .history-table .jenis-badge { min-width: 132px; }
        .scm-dashboard .history-table .status-pill { min-width: 152px; }
        .history-pagination-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid var(--history-border);
            color: var(--history-muted);
            background: var(--history-head-bg);
        }
        .history-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; }
        .history-pagination-summary, .history-pagination-status { font-size: .72rem; white-space: nowrap; }
        .history-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .history-pagination-status { text-align: center; }
        .history-table-pagination { margin: 0; }
        .history-table-pagination .page-link {
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .35rem .58rem;
            border-color: var(--history-border) !important;
            color: var(--history-text) !important;
            background: var(--history-bg) !important;
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .history-table-pagination .page-link:hover { color: var(--scm-orange, #ff7900) !important; background: var(--history-head-bg) !important; }
        .history-table-pagination .page-item.active .page-link {
            color: #ffffff !important;
            background: var(--scm-orange, #ff7900) !important;
            border-color: var(--scm-orange, #ff7900) !important;
        }
        .history-table-pagination .page-item.disabled .page-link { color: var(--history-muted) !important; background: var(--history-head-bg) !important; opacity: .62; }
        html.scm-theme-light .history-table-pagination .page-link { color: #374151 !important; background: #ffffff !important; border-color: #e1e5e8 !important; }
        html.scm-theme-light .history-table-pagination .page-link:hover { color: #d65b18 !important; background: #f8f9fa !important; }
        html.scm-theme-light .history-table-pagination .page-item.active .page-link { color: #ffffff !important; background: #ea5b1a !important; border-color: #ea5b1a !important; }
        html.scm-theme-light .history-table-pagination .page-item.disabled .page-link { color: #8b949e !important; background: #f8f9fa !important; }
        .kaprodi-request-form {
            --request-surface: var(--scm-surface, #121415);
            --request-surface-soft: var(--scm-surface-strong, #181a1b);
            --request-border: var(--scm-border, #292d30);
            --request-text: var(--scm-text, #f4f5f6);
            --request-muted: var(--scm-muted, #9da3aa);
            --request-hover: rgba(255, 121, 0, .055);
            color: var(--request-text);
        }
        html.scm-theme-light .kaprodi-request-form {
            --request-surface: #ffffff;
            --request-surface-soft: #f8f9fa;
            --request-border: #e1e5e8;
            --request-text: #22282d;
            --request-muted: #68727b;
            --request-hover: rgba(234, 91, 26, .045);
        }
        .request-card {
            overflow: hidden;
            border: 1px solid var(--request-border);
            border-radius: 12px;
            background: var(--request-surface);
            box-shadow: 0 8px 22px rgba(0, 0, 0, .08);
            transition: border-color .18s ease, box-shadow .18s ease;
        }
        html.scm-theme-light .request-card { box-shadow: 0 8px 22px rgba(36, 43, 48, .045); }
        .request-card:hover { border-color: color-mix(in srgb, var(--request-border) 65%, var(--scm-orange, #ff7900)); }
        .request-card-heading { display: flex; align-items: center; gap: .75rem; margin-bottom: 1.25rem; }
        .request-card-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 121, 0, .28);
            border-radius: 9px;
            color: var(--scm-orange, #ff7900);
            background: rgba(255, 121, 0, .08);
        }
        .request-card-heading h2, .request-items-header h2 { margin: 0; font-size: 1rem; font-weight: 700; }
        .request-card-heading p, .request-items-header p { margin: .2rem 0 0; color: var(--request-muted); font-size: .75rem; line-height: 1.5; }
        .request-info-card { padding: 1.35rem; }
        .request-info-card .form-label { margin-bottom: .48rem; font-size: .79rem; }
        .request-info-card .form-control, .request-info-card .form-select { min-height: 44px; }
        .request-info-card textarea.form-control { min-height: 112px; resize: vertical; }
        .request-items-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.35rem;
            border-bottom: 1px solid var(--request-border);
        }
        .request-row-controls { display: flex; align-items: center; justify-content: flex-end; gap: .65rem; }
        .bulk-row-control { width: 244px; flex: 0 0 244px; }
        .bulk-row-control .input-group-text, .bulk-row-control .form-control, .bulk-row-control .btn { min-height: 40px; }
        .bulk-row-control .form-control { max-width: 64px; text-align: center; }
        .request-row-controls > .btn { min-height: 40px; white-space: nowrap; }
        .request-items-body { padding: 1.2rem 1.35rem 1.35rem; }
        .request-stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: .7rem;
            margin-bottom: 1rem;
        }
        .request-stat-card {
            min-width: 0;
            min-height: 86px;
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .75rem;
            border: 1px solid var(--request-border);
            border-radius: 9px;
            background: var(--request-surface-soft);
            transition: transform .18s ease, border-color .18s ease, background-color .18s ease;
        }
        .request-stat-card:hover { transform: translateY(-2px); border-color: rgba(255, 121, 0, .42); }
        .request-stat-card.is-accent { border-color: rgba(255, 121, 0, .32); background: rgba(255, 121, 0, .065); }
        .request-stat-icon {
            width: 31px;
            height: 31px;
            flex: 0 0 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--request-border);
            border-radius: 8px;
            color: var(--request-muted);
            background: var(--request-surface);
        }
        .request-stat-card.is-accent .request-stat-icon { color: var(--scm-orange, #ff7900); border-color: rgba(255, 121, 0, .3); }
        .request-stat-label { overflow: hidden; color: var(--request-muted); font-size: .67rem; line-height: 1.25; white-space: nowrap; text-overflow: ellipsis; }
        .request-stat-value { margin-top: .24rem; color: var(--request-text); font-size: .9rem; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .request-stat-card.is-accent .request-stat-value { color: var(--scm-orange, #ff7900); }
        .need-table-shell { overflow: hidden; border: 1px solid var(--request-border); border-radius: 10px; background: var(--request-surface); }
        .need-table-scroll { max-height: min(620px, 62vh); overflow: auto; scrollbar-color: #8a9197 transparent; }
        .need-table-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid var(--request-border);
            background: var(--request-surface-soft);
        }
        .need-page-size { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; color: var(--request-muted); font-size: .72rem; }
        .need-page-size .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .need-pagination-status { color: var(--request-muted); font-size: .72rem; text-align: center; white-space: nowrap; }
        .need-table-pagination { margin: 0; }
        .need-table-pagination .page-link {
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .35rem .58rem;
            border-color: var(--request-border) !important;
            color: var(--request-text) !important;
            background: var(--request-surface) !important;
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .need-table-pagination .page-link:hover { color: var(--scm-orange, #ff7900) !important; background: var(--request-surface-soft) !important; }
        .need-table-pagination .page-item.active .page-link {
            color: #ffffff !important;
            background: var(--scm-orange, #ff7900) !important;
            border-color: var(--scm-orange, #ff7900) !important;
        }
        .need-table-pagination .page-item.disabled .page-link { color: var(--request-muted) !important; background: var(--request-surface-soft) !important; opacity: .62; }
        .need-table-pagination .page-item.is-ellipsis .page-link { cursor: default; color: var(--request-muted) !important; }
        html.scm-theme-light .need-table-pagination .page-link { color: #374151 !important; background: #ffffff !important; border-color: #e1e5e8 !important; }
        html.scm-theme-light .need-table-pagination .page-link:hover { color: #d65b18 !important; background: #f8f9fa !important; }
        html.scm-theme-light .need-table-pagination .page-item.active .page-link { color: #ffffff !important; background: #ea5b1a !important; border-color: #ea5b1a !important; }
        html.scm-theme-light .need-table-pagination .page-item.disabled .page-link { color: #8b949e !important; background: #f8f9fa !important; }
        .kaprodi-request-form .need-row.need-page-enter { animation: needRowReveal .16s ease both; }
        @keyframes needRowReveal { from { opacity: .45; transform: translate3d(0, 3px, 0); } to { opacity: 1; transform: translate3d(0, 0, 0); } }
        .need-table-head,
        .kaprodi-request-form .need-row > .row {
            display: grid;
            grid-template-columns: 48px minmax(240px, 1.6fr) 90px 105px 150px 150px minmax(190px, 1.2fr) 40px;
            gap: .75rem;
            align-items: center;
            min-width: 1080px;
        }
        .kaprodi-request-form:has(#jenisPengajuan option[value="Barang dan Jasa"]:checked) .need-table-head,
        .kaprodi-request-form:has(#jenisPengajuan option[value="Barang dan Jasa"]:checked) .need-row > .row {
            grid-template-columns: 48px 140px minmax(230px, 1.5fr) 90px 105px 150px 150px minmax(180px, 1.1fr) 40px;
            min-width: 1230px;
        }
        .need-table-head {
            position: sticky;
            top: 0;
            z-index: 4;
            padding: .78rem 1rem;
            color: var(--request-muted);
            background: var(--request-surface-soft);
            border-bottom: 1px solid var(--request-border);
            box-shadow: 0 3px 8px rgba(0, 0, 0, .04);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .035em;
            text-transform: uppercase;
        }
        .need-table-head > span { display: flex; align-items: center; min-height: 22px; }
        .need-table-head > span:first-child, .need-table-head > span:last-child { justify-content: center; }
        .need-table-head > .item-type-heading, .item-type-wrap { display: none; }
        .kaprodi-request-form:has(#jenisPengajuan option[value="Barang dan Jasa"]:checked) .item-type-heading { display: flex; }
        .need-table-body { display: block; }
        .scm-dashboard .kaprodi-request-form .need-row {
            padding: 0 !important;
            border: 0 !important;
            border-bottom: 1px solid var(--request-border) !important;
            border-radius: 0 !important;
            color: var(--request-text) !important;
            background: var(--request-surface) !important;
            box-shadow: none !important;
            transition: background-color .16s ease;
        }
        .scm-dashboard .kaprodi-request-form .need-row:last-child { border-bottom: 0 !important; }
        .scm-dashboard .kaprodi-request-form .need-row:hover { background: var(--request-hover) !important; }
        .kaprodi-request-form .need-row > .row { margin: 0; padding: .85rem 1rem; }
        .kaprodi-request-form .need-row > .row > [class*="col-"] { width: auto; max-width: none; padding: 0; }
        .kaprodi-request-form .need-row .form-label { display: none; }
        .kaprodi-request-form .need-row .form-control, .kaprodi-request-form .need-row .form-select { min-height: 42px; font-size: .78rem; }
        .kaprodi-request-form .need-row .row-number {
            display: inline-block;
            width: auto;
            height: auto;
            padding: 0;
            border: 0;
            border-radius: 0;
            color: var(--request-text);
            background: transparent;
            box-shadow: none;
            font-size: .78rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
        }
        .kaprodi-request-form .need-row > .row > :first-child { min-height: 42px; display: flex; align-items: center; justify-content: center; }
        .kaprodi-request-form .remove-need {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            justify-self: center;
            align-self: center;
            padding: 0;
            border-radius: 8px;
        }
        .kaprodi-request-form .remove-need:disabled { opacity: .35; }
        .kaprodi-request-form .subtotal-preview { background: var(--request-surface-soft) !important; box-shadow: none !important; }
        .request-submit-bar {
            position: sticky;
            bottom: .75rem;
            z-index: 9;
            display: flex;
            justify-content: flex-end;
            padding: 1rem 0 .15rem;
            pointer-events: none;
        }
        .request-submit-bar::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(to bottom, transparent, var(--scm-bg, #0b0c0d) 72%);
            pointer-events: none;
        }
        .request-submit-bar .btn { min-height: 46px; padding-inline: 1.35rem; box-shadow: 0 8px 20px rgba(234, 91, 26, .2); pointer-events: auto; }
        .nav-tabs .nav-link { color: #495057; font-weight: 600; }
        .nav-tabs .nav-link.active { color: #ea5b1a; border-bottom-color: #ea5b1a; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        .scm-dashboard-kaprodi .topbar-actions { margin-left: auto; }
        .kaprodi-overview { color: var(--scm-text, #f7f7f7); }
        .kaprodi-overview-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1.35rem; }
        .kaprodi-overview-head h1 { margin: 0; font-size: clamp(1.45rem, 2vw, 2rem); letter-spacing: -.02em; }
        .kaprodi-overview-head p { margin: .35rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .9rem; }
        .kaprodi-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; margin-bottom: .9rem; }
        .kaprodi-stat-card { position: relative; min-height: 132px; padding: 1rem; overflow: hidden; border: 1px solid var(--scm-border, #2b2f33); border-top: 2px solid var(--scm-orange, #ff7900); border-radius: 12px; background: var(--scm-surface, #111416); box-shadow: 0 8px 22px rgba(0,0,0,.12); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
        .kaprodi-stat-card:hover { transform: translateY(-3px); border-color: rgba(255, 121, 0, .65); box-shadow: 0 12px 28px rgba(0,0,0,.2); }
        .kaprodi-stat-card.is-muted { border-top-color: #8b949e; }
        .kaprodi-stat-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,121,0,.6); border-radius: 9px; color: var(--scm-orange, #ff7900); margin-bottom: .75rem; }
        .kaprodi-stat-card.is-muted .kaprodi-stat-icon { border-color: #68717a; color: #aeb6bd; }
        .kaprodi-stat-label { color: var(--scm-muted, #a8adb5); font-size: .72rem; }
        .kaprodi-stat-value { margin-top: .18rem; color: var(--scm-text, #f7f7f7); font-size: clamp(1.25rem, 2vw, 1.65rem); font-weight: 700; line-height: 1.15; }
        .kaprodi-stat-value.is-currency { font-size: clamp(1rem, 1.6vw, 1.35rem); }
        .kaprodi-stat-note { margin-top: .45rem; color: var(--scm-muted, #a8adb5); font-size: .68rem; }
        .kaprodi-chart-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .9fr); gap: .9rem; margin-bottom: .9rem; }
        .kaprodi-chart-panel, .kaprodi-activity-panel, .kaprodi-quick-panel { min-width: 0; border: 1px solid var(--scm-border, #2b2f33); border-radius: 12px; background: var(--scm-surface, #111416); box-shadow: 0 8px 22px rgba(0,0,0,.12); }
        .kaprodi-chart-panel { padding: 1rem; min-height: 310px; }
        .kaprodi-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: .7rem; }
        .kaprodi-panel-heading h2 { margin: 0; color: var(--scm-text, #f7f7f7); font-size: .92rem; font-weight: 700; }
        .kaprodi-panel-heading p { margin: .22rem 0 0; color: var(--scm-muted, #a8adb5); font-size: .7rem; }
        .kaprodi-chart-wrap { position: relative; height: 245px; }
        .kaprodi-bottom-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(280px, .9fr); gap: .9rem; }
        .kaprodi-activity-panel, .kaprodi-quick-panel { padding: 1rem; }
        .kaprodi-activity-list { max-height: 320px; overflow-y: auto; padding-right: .2rem; }
        .kaprodi-activity-item { display: flex; align-items: flex-start; gap: .7rem; padding: .75rem 0; border-bottom: 1px solid var(--scm-border, #2b2f33); color: inherit; text-decoration: none; }
        .kaprodi-activity-item:last-child { border-bottom: 0; }
        .kaprodi-activity-item:hover .kaprodi-activity-title { color: var(--scm-orange, #ff7900); }
        .kaprodi-activity-icon { width: 31px; height: 31px; flex: 0 0 31px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,121,0,.48); border-radius: 8px; color: var(--scm-orange, #ff7900); background: rgba(255,121,0,.09); }
        .kaprodi-activity-title { color: var(--scm-text, #f7f7f7); font-size: .75rem; font-weight: 700; transition: color .18s ease; }
        .kaprodi-activity-description, .kaprodi-activity-time { color: var(--scm-muted, #a8adb5); font-size: .68rem; }
        .kaprodi-activity-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; margin-top: .2rem; }
        .kaprodi-activity-status { display: inline-flex; padding: .15rem .4rem; border-radius: 999px; background: rgba(255,121,0,.12); color: var(--scm-orange, #ff7900); font-size: .62rem; font-weight: 700; }
        .kaprodi-quick-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .kaprodi-quick-link { display: flex; align-items: center; gap: .5rem; min-height: 48px; padding: .65rem .7rem; border: 1px solid var(--scm-border, #2b2f33); border-radius: 9px; color: var(--scm-text, #f7f7f7); background: rgba(255,255,255,.015); font-size: .72rem; font-weight: 600; text-decoration: none; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
        .kaprodi-quick-link:hover { color: var(--scm-text, #f7f7f7); border-color: rgba(255,121,0,.7); background: rgba(255,121,0,.08); transform: translateY(-1px); }
        .kaprodi-quick-link i { color: var(--scm-orange, #ff7900); font-size: 1rem; }
        .theme-toggle { flex: 0 0 38px !important; height: 38px; padding: 0 !important; width: 38px; }
        html.scm-theme-light {
            --scm-bg: #f3f4f6;
            --scm-surface: #ffffff;
            --scm-surface-strong: #eef0f2;
            --scm-border: #dfe3e6;
            --scm-text: #1c2024;
            --scm-muted: #68727b;
            --scm-orange-soft: rgba(234, 91, 26, .1);
        }
        html.scm-theme-light .scm-dashboard { background: var(--scm-bg) !important; color: var(--scm-text) !important; }
        html.scm-theme-light .scm-dashboard .dashboard-sidebar { background: #ffffff; border-color: #e3e6e8; }
        html.scm-theme-light .scm-dashboard .sidebar-link { color: #59636b; }
        html.scm-theme-light .scm-dashboard .sidebar-link i { color: #7e878e; }
        html.scm-theme-light .scm-dashboard .sidebar-link:hover, html.scm-theme-light .scm-dashboard .sidebar-link.active { color: #ffffff; }
        html.scm-theme-light .scm-dashboard .sidebar-footer { border-color: #e3e6e8; }
        html.scm-theme-light .scm-dashboard .topbar { background: #ffffff !important; border-color: #e3e6e8 !important; box-shadow: 0 5px 18px rgba(35, 42, 47, .06); }
        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light { color: #4e5961; border-color: #cbd2d7; }
        html.scm-theme-light .scm-dashboard .topbar .btn-outline-light:hover { color: #1c2024; background: #f1f3f4; border-color: #aeb7bd; }
        html.scm-theme-light .scm-dashboard main, html.scm-theme-light .scm-dashboard .dashboard-content { background: var(--scm-bg); }
        html.scm-theme-light .scm-dashboard .summary-card, html.scm-theme-light .scm-dashboard .menu-card, html.scm-theme-light .scm-dashboard .panel-card, html.scm-theme-light .scm-dashboard .quick-link, html.scm-theme-light .scm-dashboard .need-row, html.scm-theme-light .scm-dashboard .item-card, html.scm-theme-light .scm-dashboard .fill-summary, html.scm-theme-light .scm-dashboard .subtotal-preview { background: #ffffff !important; border-color: var(--scm-border) !important; box-shadow: 0 8px 22px rgba(36, 43, 48, .06) !important; }
        html.scm-theme-light .scm-dashboard .summary-value, html.scm-theme-light .scm-dashboard .summary-card .value, html.scm-theme-light .scm-dashboard .summary-card .summary-value { color: #1c2024; }
        html.scm-theme-light .scm-dashboard .form-control, html.scm-theme-light .scm-dashboard .form-select, html.scm-theme-light .scm-dashboard .input-group-text, html.scm-theme-light .scm-dashboard .form-control:disabled, html.scm-theme-light .scm-dashboard .form-select:disabled { color: #283138 !important; background-color: #ffffff !important; border-color: #cfd6da !important; }
        html.scm-theme-light .scm-dashboard .form-control::placeholder { color: #89939a !important; }
        html.scm-theme-light .scm-dashboard .table { --bs-table-color: #293238; }
        html.scm-theme-light .scm-dashboard .table-clean thead th, html.scm-theme-light .scm-dashboard .table-light, html.scm-theme-light .scm-dashboard .table-light th { color: #5b666e !important; background: #f0f2f3 !important; border-color: var(--scm-border) !important; }
        html.scm-theme-light .scm-dashboard .table-hover > tbody > tr:hover > *, html.scm-theme-light .scm-dashboard .table-striped > tbody > tr:nth-of-type(odd) > * { color: #1c2024; --bs-table-accent-bg: rgba(234, 91, 26, .04); }
        html.scm-theme-light .scm-dashboard .btn-outline-dark, html.scm-theme-light .scm-dashboard .btn-outline-secondary { color: #56616a !important; border-color: #b9c2c8 !important; }
        html.scm-theme-light .scm-dashboard .btn-outline-dark:hover, html.scm-theme-light .scm-dashboard .btn-outline-secondary:hover { color: #1c2024 !important; background: #e9edef !important; }
        html.scm-theme-light .scm-dashboard .alert { color: #334047; background: #ffffff; border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard .modal-content, html.scm-theme-light .scm-dashboard .dropdown-menu { color: var(--scm-text); background: #ffffff; border-color: var(--scm-border) !important; }
        html.scm-theme-light .scm-dashboard .modal-header, html.scm-theme-light .scm-dashboard .modal-footer { border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard .modal .btn-close { filter: none; }
        html.scm-theme-light .scm-dashboard .dropdown-item { color: #39444b; }
        html.scm-theme-light .scm-dashboard .dropdown-item:hover, html.scm-theme-light .scm-dashboard .dropdown-item:focus { color: #1c2024; background: #f0f2f3; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-stat-card, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-chart-panel, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-panel, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-panel { background: #ffffff; border-color: var(--scm-border); box-shadow: 0 8px 22px rgba(36, 43, 48, .06); }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-stat-value, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-panel-heading h2, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-title, html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link { color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link { background: #f7f8f9; }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-quick-link:hover { color: #1c2024; background: rgba(234, 91, 26, .08); }
        html.scm-theme-light .scm-dashboard-kaprodi .kaprodi-activity-item { border-color: rgba(35, 42, 47, .1); }
        @media (max-width: 1199.98px) {
            .kaprodi-stat-grid { grid-template-columns: repeat(4, minmax(145px, 1fr)); overflow-x: auto; padding-bottom: .3rem; }
            .kaprodi-stat-card { min-width: 145px; }
            .request-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 991.98px) {
            .kaprodi-chart-grid, .kaprodi-bottom-grid { grid-template-columns: 1fr; }
            .request-items-header { align-items: flex-start; flex-direction: column; }
            .request-row-controls { width: 100%; justify-content: flex-start; }
        }
        @media (max-width: 767.98px) {
            .kaprodi-filter-heading { flex-direction: column; gap: 7px; }
            .kaprodi-filter-note { white-space: normal; }
            .kaprodi-filter-row { grid-template-columns: 1fr; gap: 8px; padding: 12px; border: 1px solid var(--scm-border, #e8eaed); border-radius: 10px; }
            .kaprodi-filter-tools { justify-content: flex-end; }
            .topbar-actions { width: 100%; }
            .topbar-actions { justify-content: flex-end; }
            .topbar-actions .btn { flex: 0 0 auto; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
            .history-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .history-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
            .request-info-card, .request-items-body { padding: 1rem; }
            .request-items-header { padding: 1rem; }
            .request-row-controls { flex-wrap: wrap; }
            .bulk-row-control { width: min(100%, 244px); flex-basis: 244px; }
            .request-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .request-stat-card { min-height: 78px; padding: .65rem; }
            .need-table-shell { overflow: visible; border: 0; background: transparent; }
            .need-table-scroll { max-height: none; overflow: visible; }
            .need-table-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; margin-top: .75rem; border: 1px solid var(--request-border); border-radius: 10px; }
            .need-page-size { justify-content: center; }
            .need-table-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
            .need-table-head { display: none !important; }
            .need-table-body { display: grid; gap: .75rem; }
            .scm-dashboard .kaprodi-request-form .need-row {
                border: 1px solid var(--request-border) !important;
                border-radius: 10px !important;
                overflow: hidden;
            }
            .kaprodi-request-form .need-row > .row,
            .kaprodi-request-form:has(#jenisPengajuan option[value="Barang dan Jasa"]:checked) .need-row > .row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: .85rem;
                min-width: 0;
                padding: 1rem;
            }
            .kaprodi-request-form .need-row > .row > [class*="col-"] { min-width: 0; }
            .kaprodi-request-form .need-row .form-label { display: block; min-height: 18px; margin-bottom: .4rem; color: var(--request-muted); font-size: .7rem; }
            .kaprodi-request-form .need-row > .row > :first-child,
            .kaprodi-request-form .item-type-wrap,
            .kaprodi-request-form .item-name-col,
            .kaprodi-request-form .need-row > .row > :nth-last-child(2) { grid-column: 1 / -1; }
            .kaprodi-request-form .need-row > .row > :first-child { justify-content: center; }
            .kaprodi-request-form .need-row > .row > :last-child { grid-column: 2; justify-self: end; }
            .kaprodi-request-form .remove-need { justify-self: end; }
            .request-submit-bar { bottom: .35rem; padding-top: .75rem; }
        }
        @media (max-width: 575.98px) {
            .kaprodi-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); overflow-x: visible; }
            .kaprodi-stat-card { min-width: 0; min-height: 118px; padding: .8rem; }
            .kaprodi-stat-icon { margin-bottom: .55rem; }
            .kaprodi-quick-grid { grid-template-columns: 1fr; }
            .kaprodi-chart-panel, .kaprodi-activity-panel, .kaprodi-quick-panel { padding: .8rem; }
            .request-row-controls, .request-row-controls > .btn, .bulk-row-control { width: 100%; flex-basis: 100%; }
            .request-row-controls > .btn { justify-content: center; }
            .request-stat-card { align-items: flex-start; flex-direction: column; gap: .45rem; min-height: 100px; }
            .request-stat-label { white-space: normal; }
            .history-table-pagination .page-link { min-width: 32px; min-height: 32px; padding-inline: .48rem; }
            .need-table-pagination .page-link { min-width: 32px; min-height: 32px; padding-inline: .48rem; }
            .request-submit-bar .btn { width: 100%; }
        }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body class="scm-dashboard scm-dashboard-kaprodi">
    <aside class="dashboard-sidebar" aria-label="Navigasi Panel Kaprodi">
        <a class="sidebar-brand" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>">
            <span class="sidebar-brand-mark"><i class="bi bi-building-check"></i></span>
            <span><strong>SCM FIK</strong><small>Panel Kaprodi</small></span>
        </a>
        <div class="sidebar-caption">Pengajuan prodi</div>
        <nav class="sidebar-nav">
            <a class="sidebar-link <?= $active_tab === 'panel' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=panel') ?>"><i class="bi bi-grid-1x2"></i><span>Panel</span></a>
            <a class="sidebar-link <?= $active_tab === 'ajukan' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=ajukan') ?>"><i class="bi bi-plus-square"></i><span>Ajukan Kebutuhan</span></a>
            <a class="sidebar-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>"><i class="bi bi-clock-history"></i><span>Riwayat Pengajuan</span></a>
            <a class="sidebar-link" href="<?= base_url('index.php/kaprodi/peminjaman') ?>"><i class="bi bi-patch-check"></i><span>Approval Peminjaman</span></a>
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>System operational</span></div>
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
                        <button id="kaprodiNotificationButton" class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                            <div class="fw-bold px-2 py-1">Notifikasi</div>
                            <?php if (empty($notif_items)): ?>
                                <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                                <?php $notif_link = base_url('index.php/kaprodi/dashboard/notifikasi/' . (int) $n->id_notifikasi); ?>
                                <a class="dropdown-item rounded-3 py-2 <?= empty($n->is_read) ? 'bg-light' : '' ?>" href="<?= html_escape($notif_link) ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
                                    <div class="small text-muted mt-1"><?= date('d/m/Y H:i', strtotime($n->created_at)) ?></div>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-circle theme-toggle" data-theme-toggle aria-label="Aktifkan mode terang" title="Aktifkan mode terang">
                        <i class="bi bi-sun" aria-hidden="true"></i>
                    </button>
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
        <?php if ($focus_id > 0): ?>
            <div class="alert alert-info rounded-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <span><i class="bi bi-funnel me-1"></i> Menampilkan pengajuan terkait notifikasi saja.</span>
                <a class="btn btn-sm btn-outline-primary rounded-pill" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>">Kembali ke Semua Data</a>
            </div>
        <?php endif; ?>

        <?php if ($active_tab === 'panel'): ?>
            <section class="kaprodi-overview" aria-labelledby="kaprodiOverviewTitle">
                <div class="kaprodi-overview-head">
                    <div>
                        <h1 id="kaprodiOverviewTitle">Dashboard Kaprodi</h1>
                    </div>
                </div>

                <?php
                $kaprodi_stat_cards = [
                    ['label' => 'Total Pengajuan', 'value' => (int) $dashboard_stats['total_pengajuan'], 'icon' => 'bi-inboxes', 'note' => 'Semua pengajuan Anda', 'muted' => false, 'currency' => false],
                    ['label' => 'Menunggu Proses', 'value' => (int) $dashboard_stats['menunggu_proses'], 'icon' => 'bi-hourglass-split', 'note' => 'Belum diproses Kaur', 'muted' => false, 'currency' => false],
                    ['label' => 'Sedang Negosiasi', 'value' => (int) $dashboard_stats['sedang_negosiasi'], 'icon' => 'bi-chat-square-text', 'note' => 'Sedang ditindaklanjuti', 'muted' => false, 'currency' => false],
                    ['label' => 'Disetujui / Deal', 'value' => (int) $dashboard_stats['disetujui_deal'], 'icon' => 'bi-check2-circle', 'note' => 'Siap ke proses berikutnya', 'muted' => false, 'currency' => false],
                    ['label' => 'Direvisi', 'value' => (int) $dashboard_stats['direvisi'], 'icon' => 'bi-pencil-square', 'note' => 'Perlu diperbaiki', 'muted' => true, 'currency' => false],
                    ['label' => 'Ditolak', 'value' => (int) $dashboard_stats['ditolak'], 'icon' => 'bi-x-circle', 'note' => 'Proses berhenti', 'muted' => true, 'currency' => false],
                    ['label' => 'Total Nilai Pengajuan', 'value' => (float) $dashboard_stats['total_nilai_pengajuan'], 'icon' => 'bi-wallet2', 'note' => 'Estimasi sebelum negosiasi', 'muted' => false, 'currency' => true],
                    ['label' => 'Pengajuan Bulan Ini', 'value' => (int) $dashboard_stats['pengajuan_bulan_ini'], 'icon' => 'bi-calendar-plus', 'note' => 'Periode ' . date('F Y'), 'muted' => false, 'currency' => false],
                ];
                ?>
                <div class="kaprodi-stat-grid">
                    <?php foreach ($kaprodi_stat_cards as $card): ?>
                        <article class="kaprodi-stat-card <?= $card['muted'] ? 'is-muted' : '' ?>">
                            <span class="kaprodi-stat-icon"><i class="bi <?= html_escape($card['icon']) ?>"></i></span>
                            <div class="kaprodi-stat-label"><?= html_escape($card['label']) ?></div>
                            <div class="kaprodi-stat-value <?= $card['currency'] ? 'is-currency' : '' ?>" data-counter="<?= html_escape((string) $card['value']) ?>" data-counter-currency="<?= $card['currency'] ? '1' : '0' ?>"><?= $card['currency'] ? 'Rp 0' : '0' ?></div>
                            <div class="kaprodi-stat-note"><?= $card['note'] ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="kaprodi-chart-grid">
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><h2>Pengajuan per Bulan</h2><i class="bi bi-bar-chart-line text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiMonthlySubmissionChart" aria-label="Grafik pengajuan per bulan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><h2>Status Pengajuan</h2><i class="bi bi-pie-chart text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiStatusChart" aria-label="Grafik status pengajuan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><h2>Nilai Pengajuan</h2><i class="bi bi-graph-up-arrow text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiValueChart" aria-label="Grafik nilai pengajuan"></canvas></div>
                    </article>
                    <article class="kaprodi-chart-panel">
                        <div class="kaprodi-panel-heading"><h2>Jenis Pengajuan</h2><i class="bi bi-ui-checks-grid text-secondary"></i></div>
                        <div class="kaprodi-chart-wrap"><canvas id="kaprodiTypeChart" aria-label="Grafik jenis pengajuan"></canvas></div>
                    </article>
                </div>

                <div class="kaprodi-bottom-grid">
                    <article class="kaprodi-activity-panel">
                        <div class="kaprodi-panel-heading"><h2>Recent Activity</h2><i class="bi bi-activity text-secondary"></i></div>
                        <div class="kaprodi-activity-list">
                            <?php if (empty($dashboard_activity)): ?>
                                <div class="small text-secondary py-4 text-center">Belum ada aktivitas pengajuan.</div>
                            <?php else: foreach ($dashboard_activity as $activity): ?>
                                <a class="kaprodi-activity-item" href="<?= html_escape($activity['link'] ?? '#') ?>">
                                    <span class="kaprodi-activity-icon"><i class="bi <?= html_escape($activity['icon'] ?? 'bi-bell') ?>"></i></span>
                                    <span class="flex-grow-1"><span class="kaprodi-activity-title d-block"><?= html_escape($activity['title'] ?? 'Aktivitas') ?></span><span class="kaprodi-activity-description d-block"><?= html_escape($activity['description'] ?? '') ?></span><span class="kaprodi-activity-meta"><span class="kaprodi-activity-time"><i class="bi bi-clock me-1"></i><?= !empty($activity['time']) ? date('d/m/Y H:i', strtotime($activity['time'])) : '-' ?></span><span class="kaprodi-activity-status"><?= html_escape($activity['status'] ?? '') ?></span></span></span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </article>
                    <article class="kaprodi-quick-panel">
                        <div class="kaprodi-panel-heading"><h2>Quick Action</h2><i class="bi bi-lightning-charge text-secondary"></i></div>
                        <div class="kaprodi-quick-grid">
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=ajukan') ?>"><i class="bi bi-plus-square"></i><span>Ajukan Kebutuhan</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/dashboard?tab=riwayat') ?>"><i class="bi bi-clock-history"></i><span>Riwayat Pengajuan</span></a>
                            <a class="kaprodi-quick-link" href="#kaprodiNotificationButton" data-kaprodi-notifications><i class="bi bi-bell"></i><span>Lihat Notifikasi</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category, $per_page)) ?>"><i class="bi bi-file-earmark-spreadsheet"></i><span>Preview Laporan</span></a>
                            <a class="kaprodi-quick-link" href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category, $per_page) . '&download=1') ?>"><i class="bi bi-download"></i><span>Export Excel</span></a>
                        </div>
                    </article>
                </div>
            </section>
        <?php else: ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">Pengajuan Barang dan Jasa</h1>
            </div>
            <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
        </div>

        <ul class="nav nav-tabs mb-3" id="kaprodiTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link <?= $active_tab === 'ajukan' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-ajukan" type="button" aria-selected="<?= $active_tab === 'ajukan' ? 'true' : 'false' ?>"><i class="bi bi-plus-circle me-1"></i> Ajukan</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button" aria-selected="<?= $active_tab === 'riwayat' ? 'true' : 'false' ?>"><i class="bi bi-clock-history me-1"></i> Riwayat</button></li>
        </ul>

        <div class="tab-content">
            <section class="tab-pane fade <?= $active_tab === 'ajukan' ? 'show active' : '' ?>" id="tab-ajukan">
                <form action="<?= base_url('index.php/kaprodi/pengajuan/simpan') ?>" method="post" class="kaprodi-request-form mb-4">
                    <section class="request-card request-info-card mb-4" aria-labelledby="requestInfoTitle">
                        <div class="request-card-heading">
                            <span class="request-card-icon"><i class="bi bi-file-earmark-text"></i></span>
                            <div>
                                <h2 id="requestInfoTitle">Informasi Pengajuan</h2>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Jenis Pengajuan</label>
                                <select name="jenis_pengajuan" id="jenisPengajuan" class="form-select" required>
                                    <option value="Barang">Barang</option>
                                    <option value="Jasa">Jasa</option>
                                    <option value="Barang dan Jasa">Barang dan Jasa</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Program Studi</label>
                                <input type="text" name="nama_prodi" class="form-control" placeholder="Contoh: S1 Desain Komunikasi Visual" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Nama Pengajuan</label>
                                <input type="text" name="nama_pengajuan" class="form-control" placeholder="Contoh: Kebutuhan studio fotografi" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Keterangan Kebutuhan</label>
                                <textarea name="kebutuhan_lab" class="form-control" rows="3" placeholder="Jelaskan alasan kebutuhan, prioritas, atau ruangan terkait."></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="request-card request-items-card" aria-labelledby="requestItemsTitle">
                        <header class="request-items-header">
                            <div>
                                <h2 id="requestItemsTitle">Daftar Kebutuhan</h2>
                            </div>
                            <div class="request-row-controls">
                                <div class="input-group input-group-sm bulk-row-control">
                                    <span class="input-group-text">Jumlah Baris</span>
                                    <input type="number" id="bulkRows" class="form-control" min="1" max="100" value="1">
                                    <button type="button" class="btn btn-outline-dark" id="generateRows">Buat</button>
                                </div>
                                <button type="button" class="btn btn-outline-dark rounded-pill px-3 d-inline-flex align-items-center" id="addNeed"><i class="bi bi-plus-lg me-1"></i> Tambah Baris</button>
                                <button type="button" class="btn btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center" id="removeAllRows"><i class="bi bi-trash3 me-1"></i> Hapus Semua</button>
                            </div>
                        </header>

                        <div class="request-items-body">
                            <div class="request-stats-grid" aria-label="Ringkasan pengisian">
                                <div class="request-stat-card">
                                    <span class="request-stat-icon"><i class="bi bi-list-ol"></i></span>
                                    <div><div class="request-stat-label">Total Baris</div><div class="request-stat-value" id="totalRows">0</div></div>
                                </div>
                                <div class="request-stat-card">
                                    <span class="request-stat-icon"><i class="bi bi-check2-circle"></i></span>
                                    <div><div class="request-stat-label">Sudah Terisi</div><div class="request-stat-value" id="filledRows">0</div></div>
                                </div>
                                <div class="request-stat-card">
                                    <span class="request-stat-icon"><i class="bi bi-circle"></i></span>
                                    <div><div class="request-stat-label">Masih Kosong</div><div class="request-stat-value" id="emptyRows">0</div></div>
                                </div>
                                <div class="request-stat-card">
                                    <span class="request-stat-icon"><i class="bi bi-calculator"></i></span>
                                    <div><div class="request-stat-label">Sebelum Pajak</div><div class="request-stat-value" id="totalBeforeTax">Rp 0</div></div>
                                </div>
                                <div class="request-stat-card">
                                    <span class="request-stat-icon"><i class="bi bi-percent"></i></span>
                                    <div><div class="request-stat-label">Pajak <?= (int) SCM_TAX_PERCENT ?>%</div><div class="request-stat-value" id="taxValue">Rp 0</div></div>
                                </div>
                                <div class="request-stat-card is-accent">
                                    <span class="request-stat-icon"><i class="bi bi-wallet2"></i></span>
                                    <div><div class="request-stat-label">Setelah Pajak</div><div class="request-stat-value" id="totalAfterTax">Rp 0</div></div>
                                </div>
                            </div>

                            <button type="button" id="removeEmptyRows" hidden aria-hidden="true" tabindex="-1"></button>
                            <button type="button" id="resetForm" hidden aria-hidden="true" tabindex="-1"></button>

                            <div class="need-table-shell">
                                <div class="need-table-scroll">
                                    <div class="need-table-head" aria-hidden="true">
                                        <span>No.</span>
                                        <span class="item-type-heading">Jenis Item</span>
                                        <span>Nama Item / Pekerjaan</span>
                                        <span>Volume</span>
                                        <span>Satuan</span>
                                        <span>Harga Satuan</span>
                                        <span>Subtotal</span>
                                        <span>Link Referensi</span>
                                        <span>Aksi</span>
                                    </div>
                                    <div id="needList" class="need-table-body">
                                        <div class="need-row">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-2 col-lg-1 text-center">
                                                    <span class="row-number">1</span>
                                                </div>
                                                <div class="col-10 col-lg-2 item-type-wrap">
                                                    <label class="form-label small fw-semibold">Jenis Item</label>
                                                    <select name="jenis_item[]" class="form-select need-type">
                                                        <option value="Barang">Barang</option>
                                                        <option value="Jasa">Jasa</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-lg-3 item-name-col">
                                                    <label class="form-label small fw-semibold need-name-label">Nama Barang</label>
                                                    <input type="text" name="uraian_barang[]" class="form-control need-name" placeholder="Contoh: Kamera mirrorless">
                                                </div>
                                                <div class="col-6 col-lg-1">
                                                    <label class="form-label small fw-semibold">Volume</label>
                                                    <input type="number" name="vol[]" class="form-control need-volume" min="1" step="1" value="1">
                                                </div>
                                                <div class="col-6 col-lg-1">
                                                    <label class="form-label small fw-semibold">Satuan</label>
                                                    <input type="text" name="satuan[]" class="form-control" value="unit">
                                                </div>
                                                <div class="col-md-6 col-lg-2">
                                                    <label class="form-label small fw-semibold">Harga Satuan</label>
                                                    <input type="text" name="harga_penawaran_sat[]" class="form-control money-input need-price" placeholder="Rp 0">
                                                </div>
                                                <div class="col-md-6 col-lg-2">
                                                    <label class="form-label small fw-semibold">Subtotal</label>
                                                    <input type="text" class="form-control subtotal-preview" value="Rp 0" readonly>
                                                </div>
                                                <div class="col-lg-2">
                                                    <label class="form-label small fw-semibold">Link Referensi</label>
                                                    <input type="url" name="link_penawaran[]" class="form-control" placeholder="https://...">
                                                </div>
                                                <div class="col-lg-1 d-grid">
                                                    <button type="button" class="btn btn-outline-danger remove-need" disabled aria-label="Hapus baris"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <footer class="need-table-footer" aria-label="Navigasi daftar kebutuhan">
                                    <div class="need-page-size">
                                        <label for="needPageSize">Tampilkan:</label>
                                        <select id="needPageSize" class="form-select form-select-sm" aria-label="Jumlah baris per halaman">
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                        <span id="needPaginationTotal">Total item: 0</span>
                                    </div>
                                    <div class="need-pagination-status" id="needPaginationStatus" aria-live="polite">Halaman: 1 dari 1</div>
                                    <nav aria-label="Pagination daftar kebutuhan">
                                        <ul class="pagination pagination-sm need-table-pagination" id="needPagination"></ul>
                                    </nav>
                                </footer>
                            </div>
                        </div>
                    </section>

                    <div class="request-submit-bar">
                        <button class="btn btn-fik rounded-pill fw-semibold"><i class="bi bi-send me-1"></i> Kirim Pengajuan</button>
                    </div>
                </form>
            </section>

            <section class="tab-pane fade <?= $active_tab === 'riwayat' ? 'show active' : '' ?>" id="tab-riwayat">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0">Riwayat Pengajuan</h2>
                    </div>
                    <a href="<?= base_url('index.php/kaprodi/pengajuan/export_pengajuan?' . query_kaprodi($filters, 1, 'riwayat', $active_category, $per_page)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel</a>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php
                    $category_tabs = [
                        'barang' => 'Pengajuan Barang',
                        'jasa' => 'Pengajuan Jasa',
                        'gabungan' => 'Barang dan Jasa',
                    ];
                    foreach ($category_tabs as $category_key => $category_label):
                    ?>
                        <a class="btn btn-sm rounded-pill <?= $active_category === $category_key ? 'btn-fik' : 'btn-outline-secondary' ?>" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, 1, 'riwayat', $category_key, $per_page)) ?>">
                            <?= html_escape($category_label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="panel-card p-3 p-lg-4 mb-3">
                    <?php render_history_filter_kaprodi($filter_rows ?? [], [
                        'tab' => 'riwayat',
                        'kategori' => $active_category,
                        'per_page' => $per_page,
                    ]); ?>
                </div>

                <div class="panel-card overflow-hidden history-table-card">
                    <div class="table-responsive">
                        <table class="table table-clean align-middle mb-0 history-table">
                            <thead>
                                <tr>
                                    <th>No</th>
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
                                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data pengajuan sesuai filter.</td></tr>
                                <?php else: foreach ($pengajuan as $history_index => $p): ?>
                                    <tr>
                                        <td class="history-row-number"><?= table_row_number_kaprodi($history_index, $page, $per_page) ?></td>
                                        <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= html_escape($p->nama_pengajuan) ?></div>
                                            <div class="small text-muted"><?= html_escape($p->nama_prodi) ?></div>
                                        </td>
                                        <td><span class="jenis-badge"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                        <td style="min-width: 300px;">
                                            <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                            <?php foreach (($p->items ?? []) as $item): ?>
                                                <?php $item_subtotal = (float) $item->vol * (float) $item->harga_penawaran_sat; ?>
                                                <div class="small"><i class="bi bi-dot"></i><?= html_escape($item->uraian_barang) ?> - <?= rtrim(rtrim(number_format((float) $item->vol, 2, ',', '.'), '0'), ',') ?> <?= html_escape($item->satuan) ?> <?php if ($item_subtotal > 0): ?> · Rp <?= number_format($item_subtotal, 0, ',', '.') ?><?php endif; ?></div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><span class="status-pill <?= status_class_kaprodi($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                        <td class="text-end"><a href="<?= base_url('index.php/kaprodi/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview</a></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="history-pagination-footer">
                        <div class="history-pagination-summary">
                            <label for="historyPageSize">Tampilkan:</label>
                            <select id="historyPageSize" class="form-select form-select-sm" aria-label="Jumlah riwayat per halaman">
                                <option value="10" <?= (string) $per_page === '10' ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= (string) $per_page === '25' ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= (string) $per_page === '50' ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= (string) $per_page === '100' ? 'selected' : '' ?>>100</option>
                            </select>
                            <span>Total item: <?= (int) $total_rows ?></span>
                        </div>
                        <div class="history-pagination-status">Halaman: <?= (int) $page ?> dari <?= (int) $total_pages ?></div>
                        <nav>
                            <ul class="pagination pagination-sm history-table-pagination">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, max(1, $page - 1), 'riwayat', $active_category, $per_page)) ?>">Previous</a></li>
                                <?php foreach (compact_pagination_kaprodi($page, $total_pages) as $i): ?>
                                    <?php if (is_string($i)): ?>
                                        <li class="page-item disabled" aria-hidden="true"><span class="page-link">...</span></li>
                                    <?php else: ?>
                                        <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, $i, 'riwayat', $active_category, $per_page)) ?>"><?= $i ?></a></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= base_url('index.php/kaprodi/dashboard?' . query_kaprodi($filters, min($total_pages, $page + 1), 'riwayat', $active_category, $per_page)) ?>">Next</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </section>
        </div>
        <?php endif; ?>
    </main>
    </div>

    <template id="needTemplate">
        <div class="need-row">
            <div class="row g-2 align-items-end">
                <div class="col-2 col-lg-1 text-center">
                    <span class="row-number">1</span>
                </div>
                <div class="col-10 col-lg-2 item-type-wrap">
                    <label class="form-label small fw-semibold">Jenis Item</label>
                    <select name="jenis_item[]" class="form-select need-type">
                        <option value="Barang">Barang</option>
                        <option value="Jasa">Jasa</option>
                    </select>
                </div>
                <div class="col-12 col-lg-3 item-name-col">
                    <label class="form-label small fw-semibold need-name-label">Nama Barang</label>
                    <input type="text" name="uraian_barang[]" class="form-control need-name" placeholder="Contoh: Kamera mirrorless">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-semibold">Volume</label>
                    <input type="number" name="vol[]" class="form-control need-volume" min="1" step="1" value="1">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label small fw-semibold">Satuan</label>
                    <input type="text" name="satuan[]" class="form-control" value="unit">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold">Harga Satuan</label>
                    <input type="text" name="harga_penawaran_sat[]" class="form-control money-input need-price" placeholder="Rp 0">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label class="form-label small fw-semibold">Subtotal</label>
                    <input type="text" class="form-control subtotal-preview" value="Rp 0" readonly>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const panel = document.querySelector('.kaprodi-overview');
            const chartInstances = [];
            window.kaprodiSyncChartTheme = () => {
                const light = document.documentElement.classList.contains('scm-theme-light');
                const textColor = light ? '#68727b' : '#a8adb5';
                const gridColor = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                chartInstances.forEach((chart) => {
                    if (chart.options.scales) {
                        Object.values(chart.options.scales).forEach((scale) => {
                            if (scale.ticks) scale.ticks.color = textColor;
                            if (scale.grid) scale.grid.color = gridColor;
                        });
                    }
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = textColor;
                    if (chart.options.plugins?.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = light ? '#ffffff' : '#181a1b';
                        chart.options.plugins.tooltip.titleColor = light ? '#273138' : '#f3f5f6';
                        chart.options.plugins.tooltip.bodyColor = light ? '#273138' : '#f3f5f6';
                        chart.options.plugins.tooltip.borderColor = gridColor;
                    }
                    chart.data.datasets.forEach((dataset) => {
                        if (chart.config.type === 'doughnut') dataset.borderColor = light ? '#ffffff' : '#111416';
                    });
                    chart.update('none');
                });
            };
            if (!panel) return;

            const formatNumber = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
            const formatCurrency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            panel.querySelectorAll('[data-counter]').forEach((element) => {
                const target = Number(element.dataset.counter || 0);
                const isCurrency = element.dataset.counterCurrency === '1';
                const duration = 720;
                const started = performance.now();
                const tick = (now) => {
                    const progress = Math.min(1, (now - started) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = target * eased;
                    element.textContent = isCurrency ? formatCurrency.format(value).replace(',00', '') : formatNumber.format(value);
                    if (progress < 1) window.requestAnimationFrame(tick);
                };
                window.requestAnimationFrame(tick);
            });

            if (typeof Chart === 'undefined') return;
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const orange = '#ff7900';
            const orangeSoft = '#ffad66';
            const gray = '#9ca3aa';
            const isLightTheme = document.documentElement.classList.contains('scm-theme-light');
            const grid = isLightTheme ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
            const text = isLightTheme ? '#68727b' : '#a8adb5';
            const common = { responsive: true, maintainAspectRatio: false, animation: { duration: 650 }, plugins: { legend: { labels: { color: text, usePointStyle: true, boxWidth: 8, font: { size: 10 } } }, tooltip: { backgroundColor: isLightTheme ? '#ffffff' : '#181a1b', titleColor: isLightTheme ? '#273138' : '#f3f5f6', bodyColor: isLightTheme ? '#273138' : '#f3f5f6', borderColor: grid, borderWidth: 1 } } };
            const axis = { ticks: { color: text, font: { size: 10 } }, grid: { color: grid } };
            const monthly = <?= json_encode(array_values($dashboard_monthly_submissions), JSON_NUMERIC_CHECK) ?>;
            const values = <?= json_encode(array_values($dashboard_monthly_values), JSON_NUMERIC_CHECK) ?>;
            const statusLabels = <?= json_encode(array_keys($dashboard_status_breakdown), JSON_UNESCAPED_UNICODE) ?>;
            const statusValues = <?= json_encode(array_values($dashboard_status_breakdown), JSON_NUMERIC_CHECK) ?>;
            const typeLabels = <?= json_encode(array_keys($dashboard_type_breakdown), JSON_UNESCAPED_UNICODE) ?>;
            const typeValues = <?= json_encode(array_values($dashboard_type_breakdown), JSON_NUMERIC_CHECK) ?>;

            const monthlyCanvas = document.getElementById('kaprodiMonthlySubmissionChart');
            if (monthlyCanvas) chartInstances.push(new Chart(monthlyCanvas, { type: 'bar', data: { labels, datasets: [{ label: 'Pengajuan', data: monthly, backgroundColor: orange, borderRadius: 5, maxBarThickness: 28 }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, precision: 0 } } } } }));
            const statusCanvas = document.getElementById('kaprodiStatusChart');
            if (statusCanvas) chartInstances.push(new Chart(statusCanvas, { type: 'doughnut', data: { labels: statusLabels, datasets: [{ data: statusValues, backgroundColor: [orange, orangeSoft, '#d66518', gray, '#626a72'], borderColor: isLightTheme ? '#ffffff' : '#111416', borderWidth: 3, hoverOffset: 6 }] }, options: { ...common, cutout: '68%', plugins: { ...common.plugins, legend: { ...common.plugins.legend, position: 'bottom' } } } }));
            const valueCanvas = document.getElementById('kaprodiValueChart');
            if (valueCanvas) chartInstances.push(new Chart(valueCanvas, { type: 'line', data: { labels, datasets: [{ label: 'Nilai estimasi', data: values, borderColor: orange, backgroundColor: 'rgba(255, 121, 0, .13)', fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: orange }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, callback: (value) => 'Rp ' + formatNumber.format(value) } } } } }));
            const typeCanvas = document.getElementById('kaprodiTypeChart');
            if (typeCanvas) chartInstances.push(new Chart(typeCanvas, { type: 'bar', data: { labels: typeLabels, datasets: [{ label: 'Jumlah', data: typeValues, backgroundColor: [orange, orangeSoft, gray], borderRadius: 5, maxBarThickness: 44 }] }, options: { ...common, scales: { x: axis, y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, precision: 0 } } }, plugins: { ...common.plugins, legend: { display: false } } } }));

            const notificationButton = document.getElementById('kaprodiNotificationButton');
            panel.querySelectorAll('[data-kaprodi-notifications]').forEach((link) => link.addEventListener('click', (event) => {
                event.preventDefault();
                if (!notificationButton || typeof bootstrap === 'undefined') return;
                bootstrap.Dropdown.getOrCreateInstance(notificationButton).show();
                notificationButton.focus();
            }));
        })();
    </script>
    <script>
        window.addEventListener('scm:themechange', () => {
            if (typeof window.kaprodiSyncChartTheme === 'function') window.kaprodiSyncChartTheme();
        });
        if (typeof window.kaprodiSyncChartTheme === 'function') window.kaprodiSyncChartTheme();
    </script>
    <script>
        (() => {
            document.querySelectorAll('[data-kaprodi-multi-filter]').forEach((form) => {
                const list = form.querySelector('[data-filter-list]');
                if (!list) return;
                const maxFilters = Number(form.dataset.maxFilters || 4);
                let submitTimer;

                const syncInput = (row, clearValue = false) => {
                    const select = row.querySelector('.kaprodi-filter-field');
                    const input = row.querySelector('.kaprodi-filter-value');
                    const option = select?.selectedOptions?.[0];
                    if (!select || !input || !option) return;
                    if (clearValue) input.value = '';
                    input.type = option.dataset.inputType || 'search';
                    input.placeholder = option.dataset.placeholder || 'Ketik untuk mencari';
                };

                const updateButtons = () => {
                    const rows = Array.from(list.querySelectorAll('[data-filter-row]'));
                    rows.forEach((row, index) => {
                        row.querySelector('.kaprodi-filter-remove').disabled = rows.length === 1;
                        row.querySelector('.kaprodi-filter-add').disabled = rows.length >= maxFilters || index !== rows.length - 1;
                    });
                };

                const addRow = () => {
                    const rows = list.querySelectorAll('[data-filter-row]');
                    if (!rows.length || rows.length >= maxFilters) return null;
                    const sourceSelect = rows[0].querySelector('.kaprodi-filter-field');
                    const row = document.createElement('div');
                    row.className = 'kaprodi-filter-row';
                    row.dataset.filterRow = '';
                    row.innerHTML = `
                        <select name="filter_field[]" class="form-select kaprodi-filter-field" aria-label="Jenis filter ${rows.length + 1}">${sourceSelect.innerHTML}</select>
                        <input type="search" name="filter_value[]" class="form-control kaprodi-filter-value" value="" autocomplete="off" aria-label="Nilai filter ${rows.length + 1}">
                        <div class="kaprodi-filter-tools">
                            <button type="button" class="btn btn-outline-secondary kaprodi-filter-icon kaprodi-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                            <button type="button" class="btn btn-outline-primary kaprodi-filter-icon kaprodi-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        </div>`;
                    list.appendChild(row);
                    syncInput(row);
                    updateButtons();
                    return row;
                };

                list.querySelectorAll('[data-filter-row]').forEach((row) => syncInput(row));
                updateButtons();
                list.addEventListener('click', (event) => {
                    const row = event.target.closest('[data-filter-row]');
                    if (!row) return;
                    if (event.target.closest('.kaprodi-filter-add')) {
                        addRow()?.querySelector('.kaprodi-filter-value')?.focus();
                    } else if (event.target.closest('.kaprodi-filter-remove') && list.querySelectorAll('[data-filter-row]').length > 1) {
                        row.remove();
                        updateButtons();
                        form.requestSubmit();
                    }
                });
                list.addEventListener('change', (event) => {
                    if (!event.target.matches('.kaprodi-filter-field')) return;
                    const row = event.target.closest('[data-filter-row]');
                    syncInput(row, true);
                    row.querySelector('.kaprodi-filter-value')?.focus();
                });
                list.addEventListener('input', (event) => {
                    if (!event.target.matches('.kaprodi-filter-value')) return;
                    window.clearTimeout(submitTimer);
                    submitTimer = window.setTimeout(() => form.requestSubmit(), 650);
                });
                form.querySelector('[data-kaprodi-filter-reset]')?.addEventListener('click', () => {
                    const rows = Array.from(list.querySelectorAll('[data-filter-row]'));
                    rows.slice(1).forEach((row) => row.remove());
                    const first = rows[0];
                    if (first) {
                        const select = first.querySelector('.kaprodi-filter-field');
                        if (select) select.selectedIndex = 0;
                        syncInput(first, true);
                    }
                    updateButtons();
                    form.requestSubmit();
                });
            });
        })();
    </script>
    <script>
        const historyPageSize = document.getElementById('historyPageSize');
        if (historyPageSize) {
            historyPageSize.addEventListener('change', () => {
                const targetUrl = new URL(window.location.href);
                targetUrl.searchParams.set('tab', 'riwayat');
                targetUrl.searchParams.set('page', '1');
                targetUrl.searchParams.set('per_page', historyPageSize.value);
                window.location.assign(targetUrl.toString());
            });
        }

        const needList = document.getElementById('needList');
        const template = document.getElementById('needTemplate');
        const bulkRows = document.getElementById('bulkRows');
        const jenisPengajuan = document.getElementById('jenisPengajuan');
        const requestForm = document.querySelector('form[action*="kaprodi/pengajuan/simpan"]');
        const needPageSize = document.getElementById('needPageSize');
        const needPagination = document.getElementById('needPagination');
        const needPaginationTotal = document.getElementById('needPaginationTotal');
        const needPaginationStatus = document.getElementById('needPaginationStatus');
        const needTableScroll = document.querySelector('.need-table-scroll');
        const rupiahFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });

        if (!needList || !template || !bulkRows || !jenisPengajuan || !requestForm || !needPageSize || !needPagination || !needPaginationTotal || !needPaginationStatus) {
            // Panel overview tidak memerlukan inisialisasi form pengajuan.
        } else {

        let currentNeedPage = 1;
        let needRowsPerPage = 10;

        function parseMoney(value) {
            return Number(String(value || '').replace(/[^0-9]/g, '')) || 0;
        }

        function formatMoney(value) {
            return rupiahFormatter.format(value).replace(',00', '');
        }

        function rows() {
            return Array.from(needList.querySelectorAll('.need-row'));
        }

        function paginationTokens(totalPages, currentPage) {
            if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);
            if (currentPage <= 3) return [1, 2, 3, 4, 5, 'ellipsis', totalPages];
            if (currentPage >= totalPages - 2) return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
            return [1, 'ellipsis', currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, 'ellipsis', totalPages];
        }

        function appendPaginationItem(label, page, options = {}) {
            const item = document.createElement('li');
            item.className = 'page-item';
            if (options.active) item.classList.add('active');
            if (options.disabled) item.classList.add('disabled');
            if (options.ellipsis) item.classList.add('is-ellipsis');

            if (options.ellipsis) {
                const span = document.createElement('span');
                span.className = 'page-link';
                span.textContent = label;
                item.appendChild(span);
            } else {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = label;
                button.dataset.page = String(page);
                button.disabled = Boolean(options.disabled);
                if (options.active) button.setAttribute('aria-current', 'page');
                item.appendChild(button);
            }
            needPagination.appendChild(item);
        }

        function renderNeedPagination(animateRows = false) {
            const currentRows = rows();
            const pageSize = needRowsPerPage === Infinity ? Math.max(currentRows.length, 1) : needRowsPerPage;
            const totalPages = Math.max(1, Math.ceil(currentRows.length / pageSize));
            currentNeedPage = Math.min(Math.max(1, currentNeedPage), totalPages);
            const firstIndex = (currentNeedPage - 1) * pageSize;
            const lastIndex = firstIndex + pageSize;

            currentRows.forEach((row, index) => {
                const isVisible = index >= firstIndex && index < lastIndex;
                row.hidden = !isVisible;
                row.classList.remove('need-page-enter');
                if (isVisible && animateRows) {
                    window.requestAnimationFrame(() => row.classList.add('need-page-enter'));
                }
            });

            needPaginationTotal.textContent = `Total item: ${currentRows.length}`;
            needPaginationStatus.textContent = `Halaman: ${currentNeedPage} dari ${totalPages}`;
            needPagination.innerHTML = '';
            appendPaginationItem('Previous', currentNeedPage - 1, { disabled: currentNeedPage === 1 });
            paginationTokens(totalPages, currentNeedPage).forEach((token) => {
                if (token === 'ellipsis') {
                    appendPaginationItem('...', currentNeedPage, { ellipsis: true });
                    return;
                }
                appendPaginationItem(String(token), token, { active: token === currentNeedPage });
            });
            appendPaginationItem('Next', currentNeedPage + 1, { disabled: currentNeedPage === totalPages });
        }

        function rowHasData(row) {
            return Boolean(
                row.querySelector('.need-name')?.value.trim() ||
                row.querySelector('.need-price')?.value.trim() ||
                row.querySelector('input[name="link_penawaran[]"]')?.value.trim()
            );
        }

        function appendEmptyRow() {
            needList.appendChild(template.content.cloneNode(true));
        }

        function reindexRows() {
            rows().forEach((row, index) => {
                const number = row.querySelector('.row-number');
                if (number) number.textContent = index + 1;
                row.dataset.rowNumber = index + 1;
                row.classList.toggle('is-empty', !rowHasData(row));
            });
            bulkRows.value = rows().length;
        }

        function syncTypeFields() {
            const mode = jenisPengajuan.value;
            rows().forEach((row) => {
                const typeWrap = row.querySelector('.item-type-wrap');
                const typeInput = row.querySelector('.need-type');
                const label = row.querySelector('.need-name-label');
                const nameInput = row.querySelector('.need-name');
                if (typeWrap) typeWrap.style.display = mode === 'Barang dan Jasa' ? 'block' : 'none';
                if (typeInput && mode !== 'Barang dan Jasa') typeInput.value = mode;
                if (label) label.textContent = mode === 'Jasa' ? 'Nama Jasa' : (mode === 'Barang dan Jasa' ? 'Uraian Item' : 'Nama Barang');
                if (nameInput) {
                    nameInput.placeholder = mode === 'Jasa'
                        ? 'Contoh: Jasa kalibrasi mesin'
                        : (mode === 'Barang dan Jasa' ? 'Contoh: Kamera / jasa instalasi' : 'Contoh: Kamera mirrorless');
                }
            });
        }

        function refreshRows(options = {}) {
            reindexRows();
            syncTypeFields();
            updateSummary();
            if (options.showLastPage) {
                const pageSize = needRowsPerPage === Infinity ? Math.max(rows().length, 1) : needRowsPerPage;
                currentNeedPage = Math.max(1, Math.ceil(rows().length / pageSize));
            }
            renderNeedPagination(Boolean(options.animateRows));
        }

        function applyRowCount(target) {
            const requested = Number(target);
            const count = Number.isFinite(requested) ? Math.max(0, Math.min(100, Math.floor(requested))) : rows().length;
            const currentRows = rows();
            const isGrowing = count > currentRows.length;
            if (count < currentRows.length) {
                const removedRows = currentRows.slice(count);
                if (removedRows.some(rowHasData)) {
                    const confirmed = window.confirm('Baris berisi data akan dihapus dari bagian paling bawah. Lanjutkan?');
                    if (!confirmed) {
                        bulkRows.value = currentRows.length;
                        return;
                    }
                }
                currentRows.slice(count).forEach((row) => row.remove());
            } else {
                for (let i = currentRows.length; i < count; i++) appendEmptyRow();
            }
            refreshRows({ showLastPage: isGrowing, animateRows: isGrowing });
        }

        document.getElementById('addNeed').addEventListener('click', () => {
            appendEmptyRow();
            refreshRows({ showLastPage: true, animateRows: true });
        });
        document.getElementById('generateRows').addEventListener('click', () => applyRowCount(bulkRows.value));
        bulkRows.addEventListener('change', () => applyRowCount(bulkRows.value));
        jenisPengajuan.addEventListener('change', () => {
            syncTypeFields();
            updateSummary();
        });
        needPageSize.addEventListener('change', () => {
            const oldPageSize = needRowsPerPage === Infinity ? Math.max(rows().length, 1) : needRowsPerPage;
            const firstVisibleIndex = (currentNeedPage - 1) * oldPageSize;
            needRowsPerPage = needPageSize.value === 'all' ? Infinity : Math.max(1, Number(needPageSize.value) || 10);
            const newPageSize = needRowsPerPage === Infinity ? Math.max(rows().length, 1) : needRowsPerPage;
            currentNeedPage = Math.floor(firstVisibleIndex / newPageSize) + 1;
            renderNeedPagination(true);
        });
        needPagination.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-page]');
            if (!button || button.disabled) return;
            currentNeedPage = Number(button.dataset.page) || 1;
            renderNeedPagination(true);
            if (needTableScroll) needTableScroll.scrollTo({ top: 0, behavior: 'smooth' });
        });

        needList.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-need');
            if (!button) return;
            const row = button.closest('.need-row');
            if (rowHasData(row) && !window.confirm('Hapus baris ini beserta data yang sudah diisi?')) return;
            row.remove();
            refreshRows();
        });
        needList.addEventListener('input', (event) => {
            if (event.target.matches('.need-name, .need-volume, .need-price, input[name="link_penawaran[]"]')) {
                updateSummary();
            }
        });
        needList.addEventListener('blur', (event) => {
            if (event.target.matches('.need-price')) {
                const value = parseMoney(event.target.value);
                event.target.value = value > 0 ? formatMoney(value) : '';
                updateSummary();
            }
        }, true);
        document.getElementById('removeEmptyRows').addEventListener('click', () => {
            const emptyRows = rows().filter((row) => !rowHasData(row));
            if (!emptyRows.length) {
                window.alert('Tidak ada baris kosong untuk dihapus.');
                return;
            }
            emptyRows.forEach((row) => row.remove());
            refreshRows();
        });
        document.getElementById('removeAllRows').addEventListener('click', () => {
            if (!rows().length) return;
            if (!window.confirm('Hapus seluruh baris pengajuan? Data yang sudah diisi akan hilang.')) return;
            needList.innerHTML = '';
            currentNeedPage = 1;
            refreshRows();
        });
        document.getElementById('resetForm').addEventListener('click', () => {
            if (rows().some(rowHasData) && !window.confirm('Reset form dan hapus semua data yang sudah diisi?')) return;
            requestForm.reset();
            jenisPengajuan.value = 'Barang';
            needList.innerHTML = '';
            appendEmptyRow();
            currentNeedPage = 1;
            needRowsPerPage = 10;
            needPageSize.value = '10';
            refreshRows();
        });
        requestForm.addEventListener('submit', (event) => {
            if (!rows().some((row) => row.querySelector('.need-name')?.value.trim() !== '')) {
                event.preventDefault();
                alert('Minimal satu baris kebutuhan wajib diisi.');
            }
        });
        function updateSummary() {
            const currentRows = rows();
            let total = 0;
            let filled = 0;
            currentRows.forEach((row) => {
                const name = row.querySelector('.need-name')?.value.trim() || '';
                const volume = Math.max(0, Number(row.querySelector('.need-volume')?.value || 0));
                const price = parseMoney(row.querySelector('.need-price')?.value);
                const subtotal = volume * price;
                if (name !== '') {
                    filled++;
                }
                total += subtotal;
                const subtotalInput = row.querySelector('.subtotal-preview');
                if (subtotalInput) {
                    subtotalInput.value = formatMoney(subtotal);
                }
            });
            const tax = total * <?= json_encode(SCM_TAX_RATE) ?>;
            document.getElementById('totalRows').textContent = currentRows.length;
            document.getElementById('filledRows').textContent = filled;
            document.getElementById('emptyRows').textContent = Math.max(0, currentRows.length - filled);
            document.getElementById('totalBeforeTax').textContent = formatMoney(total);
            document.getElementById('taxValue').textContent = formatMoney(tax);
            document.getElementById('totalAfterTax').textContent = formatMoney(total + tax);
        }
        refreshRows();
        }
    </script>
</body>
</html>
