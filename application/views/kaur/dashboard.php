<?php
function rp_kaur($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
function num_kaur($value) {
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
}
function status_class_kaur($status) {
    $map = [
        'Pengajuan' => 'status-pengajuan',
        'Revisi' => 'status-revisi',
        'Negosiasi' => 'status-negosiasi',
        'Sedang Negosiasi' => 'status-negosiasi',
        'Deal' => 'status-deal',
        'Disetujui' => 'status-approval',
        'Disetujui (Menunggu Finalisasi QR)' => 'status-Disetujui-Menunggu-Finalisasi-QR-',
        'Approval' => 'status-approval',
        'BAST' => 'status-bast',
        'Inventarisasi' => 'status-inventory',
        'Selesai' => 'status-selesai',
        'Ditolak' => 'status-ditolak',
    ];
    return $map[$status] ?? 'status-pengajuan';
}
function loan_status_tone_kaur($status) {
    $status = strtolower(trim((string) $status));
    if ($status === '') return 'is-pending';
    if (strpos($status, 'tolak') !== false) return 'is-rejected';
    if (in_array($status, ['dikembalikan', 'selesai', 'disetujui'], true)) return 'is-completed';
    if (in_array($status, [
        'menunggu acc kaur',
        'disetujui (menunggu finalisasi qr)',
        'disetujui (menunggu pengambilan)',
        'sedang dipinjam',
        'dipinjam',
    ], true)) return 'is-current';
    return 'is-pending';
}
function loan_approval_states_kaur($loan) {
    $status = (string) ($loan->status ?? '');
    $state = static function ($complete, $current) {
        return $complete ? 'is-complete' : ($current ? 'is-current' : 'is-pending');
    };
    return [
        'status' => $status,
        'diajukan' => $status !== '' ? 'is-complete' : 'is-current',
        'kaprodi' => $state(($loan->status_kaprodi ?? '') === 'Disetujui', $status === 'Menunggu ACC Kaprodi'),
        'laboran' => $state(($loan->status_laboran ?? '') === 'Disetujui', in_array($status, ['Menunggu Verifikasi Laboran', 'Menunggu Pengecekan Laboran'], true)),
        'kaur' => $state(($loan->status_kaur ?? '') === 'Disetujui', $status === 'Menunggu ACC Kaur'),
        'qr' => $state((int) ($loan->qr_locked ?? 0) === 1 || !empty($loan->qr_finalized_at), $status === 'Disetujui (Menunggu Finalisasi QR)'),
        'selesai' => $state($status === 'Dikembalikan', in_array($status, ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dipinjam'], true)),
    ];
}
function table_row_number_kaur($index, $page = 1, $per_page = '10') {
    $page = max(1, (int) $page);
    if ((string) $per_page === 'all') {
        return (int) $index + 1;
    }
    $page_size = max(1, (int) $per_page ?: 10);
    return (($page - 1) * $page_size) + (int) $index + 1;
}
function query_kaur($filters, $page, $per_page = null) {
    $params = [];
    foreach ((array) $filters as $key => $value) {
        if ($value !== '' && $value !== null) {
            $params[$key] = $value;
        }
    }
    $params['page'] = $page;
    if ($per_page !== null && $per_page !== '') {
        $params['per_page'] = $per_page;
    }
    return http_build_query($params);
}
function sort_url_kaur($module, $filters, $field, $page = 1, $per_page = null) {
    $current_sort = $filters['sort_by'] ?? '';
    $current_dir = $filters['sort_dir'] ?? '';
    $next_dir = ($current_sort === $field && strtoupper($current_dir) === 'ASC') ? 'desc' : 'asc';
    $new_filters = $filters;
    $new_filters['sort_by'] = $field;
    $new_filters['sort_dir'] = $next_dir;
    return kaur_module_url($module) . '?' . query_kaur($new_filters, $page, $per_page);
}
function sort_icon_kaur($filters, $field) {
    $current_sort = $filters['sort_by'] ?? '';
    $current_dir = strtoupper((string) ($filters['sort_dir'] ?? ''));
    if ($current_sort !== $field) {
        return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size:.75rem;"></i>';
    }
    return $current_dir === 'ASC'
        ? '<i class="bi bi-sort-up-alt ms-1" style="font-size:.8rem;"></i>'
        : '<i class="bi bi-sort-down ms-1" style="font-size:.8rem;"></i>';
}
function kaur_filter_config($module) {
    $configs = [
        'pengajuan' => [
            'kode' => ['label' => 'Kode pengajuan', 'placeholder' => 'Cari kode pengajuan'],
            'prodi' => ['label' => 'Program studi', 'placeholder' => 'Cari program studi'],
            'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari Barang, Jasa, atau Barang dan Jasa'],
            'kebutuhan' => ['label' => 'Kebutuhan / item', 'placeholder' => 'Cari kebutuhan atau nama item'],
            'status' => ['label' => 'Status', 'placeholder' => 'Cari status pengajuan'],
            'tanggal' => ['label' => 'Tanggal pengajuan', 'placeholder' => 'Pilih tanggal pengajuan', 'type' => 'date'],
        ],
        'negosiasi' => [
            'kode' => ['label' => 'Kode pengajuan', 'placeholder' => 'Cari kode pengajuan'],
            'pengajuan' => ['label' => 'Nama pengajuan', 'placeholder' => 'Cari nama pengajuan'],
            'prodi' => ['label' => 'Program studi', 'placeholder' => 'Cari program studi'],
            'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari jenis pengajuan'],
            'item' => ['label' => 'Item', 'placeholder' => 'Cari nama item'],
            'vendor' => ['label' => 'Vendor', 'placeholder' => 'Cari nama vendor'],
            'status_negosiasi' => ['label' => 'Status negosiasi', 'placeholder' => 'Cari status negosiasi'],
        ],
        'approval' => [
            'kode' => ['label' => 'Kode pengajuan', 'placeholder' => 'Cari kode pengajuan'],
            'tanggal' => ['label' => 'Tanggal', 'placeholder' => 'Pilih tanggal pengajuan', 'type' => 'date'],
            'prodi' => ['label' => 'Program studi', 'placeholder' => 'Cari program studi'],
            'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari jenis pengajuan'],
            'vendor' => ['label' => 'Vendor', 'placeholder' => 'Cari nama vendor'],
            'total_harga' => ['label' => 'Total harga', 'placeholder' => 'Cari nominal total harga'],
            'status_negosiasi' => ['label' => 'Status negosiasi', 'placeholder' => 'Cari status negosiasi'],
            'status' => ['label' => 'Status approval', 'placeholder' => 'Cari status approval'],
        ],
        'peminjaman' => [
            'peminjam' => ['label' => 'Peminjam / NIM', 'placeholder' => 'Cari peminjam / NIM'],
            'barang' => ['label' => 'Nama barang / kode', 'placeholder' => 'Cari nama barang / kode'],
            'tanggal' => ['label' => 'Masa pinjam', 'placeholder' => 'Pilih tanggal pinjam', 'type' => 'date'],
            'status_approval' => ['label' => 'Alur approval', 'placeholder' => 'Cari status alur approval'],
        ],
        'bast' => [
            'kode' => ['label' => 'Kode pengajuan', 'placeholder' => 'Cari kode pengajuan'],
            'prodi' => ['label' => 'Prodi / kegiatan', 'placeholder' => 'Cari prodi atau kegiatan'],
            'jenis' => ['label' => 'Jenis', 'placeholder' => 'Cari jenis pengajuan'],
            'nomor_bast' => ['label' => 'Nomor BAST', 'placeholder' => 'Cari nomor BAST'],
            'tanggal_bast' => ['label' => 'Tanggal BAST', 'placeholder' => 'Pilih tanggal BAST', 'type' => 'date'],
        ],
        'laporan' => [
            'pengajuan' => ['label' => 'Pengajuan', 'placeholder' => 'Cari kode, nama pengajuan, atau prodi'],
            'item' => ['label' => 'Item', 'placeholder' => 'Cari nama item'],
            'vendor' => ['label' => 'Vendor', 'placeholder' => 'Cari nama vendor'],
            'harga_awal' => ['label' => 'Harga awal', 'placeholder' => 'Cari nominal harga awal'],
            'harga_akhir' => ['label' => 'Harga akhir', 'placeholder' => 'Cari nominal harga akhir'],
            'selisih' => ['label' => 'Selisih', 'placeholder' => 'Cari nominal selisih'],
            'volume' => ['label' => 'Volume', 'placeholder' => 'Cari jumlah volume'],
            'garansi' => ['label' => 'Garansi', 'placeholder' => 'Cari keterangan garansi'],
            'catatan' => ['label' => 'Catatan', 'placeholder' => 'Cari isi catatan'],
        ],
    ];
    return $configs[$module] ?? [];
}
function render_kaur_multi_filter($module, $rows, $hidden = []) {
    $fields = kaur_filter_config($module);
    if (empty($fields)) return;
    $rows = is_array($rows) && !empty($rows) ? array_slice(array_values($rows), 0, 4) : [['field' => array_key_first($fields), 'value' => '']];
    ?>
    <form method="get" action="<?= kaur_module_url($module) ?>" class="kaur-multi-filter" data-kaur-multi-filter data-max-filters="4">
        <?php foreach ($hidden as $name => $value): ?>
            <input type="hidden" name="<?= html_escape($name) ?>" value="<?= html_escape((string) $value) ?>">
        <?php endforeach; ?>
        <div class="kaur-filter-heading">
            <div><h3><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter pencarian</h3><p>Tambahkan hingga 4 kriteria untuk mempersempit data.</p></div>
            <span class="kaur-filter-note"><i class="bi bi-lightning-charge me-1" aria-hidden="true"></i>Hasil diperbarui saat Anda mengetik</span>
        </div>
        <div class="kaur-filter-list" data-filter-list>
            <?php foreach ($rows as $index => $row): ?>
                <?php
                    $field = isset($fields[$row['field'] ?? '']) ? (string) $row['field'] : (string) array_key_first($fields);
                    $meta = $fields[$field];
                    $input_type = $meta['type'] ?? 'search';
                ?>
                <div class="kaur-filter-row" data-filter-row>
                    <select name="filter_field[]" class="form-select kaur-filter-field" aria-label="Jenis filter <?= $index + 1 ?>">
                        <?php foreach ($fields as $field_key => $field_meta): ?>
                            <option value="<?= html_escape($field_key) ?>" data-input-type="<?= html_escape($field_meta['type'] ?? 'search') ?>" data-placeholder="<?= html_escape($field_meta['placeholder']) ?>" <?= $field === $field_key ? 'selected' : '' ?>><?= html_escape($field_meta['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="<?= html_escape($input_type) ?>" name="filter_value[]" class="form-control kaur-filter-value" value="<?= html_escape($row['value'] ?? '') ?>" placeholder="<?= html_escape($meta['placeholder']) ?>" autocomplete="off" aria-label="Nilai filter <?= $index + 1 ?>">
                    <div class="kaur-filter-tools">
                        <button type="button" class="btn btn-outline-secondary kaur-filter-icon kaur-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-outline-primary kaur-filter-icon kaur-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="visually-hidden">Terapkan filter</button>
    </form>
    <?php
}
function terbilang_kaur($number) {
    $number = (int) $number;
    $words = ['', 'SATU', 'DUA', 'TIGA', 'EMPAT', 'LIMA', 'ENAM', 'TUJUH', 'DELAPAN', 'SEMBILAN', 'SEPULUH', 'SEBELAS'];
    if ($number < 12) return $words[$number];
    if ($number < 20) return terbilang_kaur($number - 10) . ' BELAS';
    if ($number < 100) return trim(terbilang_kaur((int) ($number / 10)) . ' PULUH ' . terbilang_kaur($number % 10));
    if ($number < 200) return trim('SERATUS ' . terbilang_kaur($number - 100));
    if ($number < 1000) return trim(terbilang_kaur((int) ($number / 100)) . ' RATUS ' . terbilang_kaur($number % 100));
    if ($number < 2000) return trim('SERIBU ' . terbilang_kaur($number - 1000));
    if ($number < 1000000) return trim(terbilang_kaur((int) ($number / 1000)) . ' RIBU ' . terbilang_kaur($number % 1000));
    return (string) $number;
}
function tanggal_terbilang_kaur($date_string) {
    if (empty($date_string)) return ['hari' => '-', 'tanggal' => '-', 'bulan' => '-', 'tahun' => '-', 'label' => '-'];
    $timestamp = strtotime($date_string);
    if (!$timestamp) return ['hari' => '-', 'tanggal' => '-', 'bulan' => '-', 'tahun' => '-', 'label' => '-'];
    $hari_list = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
    $bulan_list = ['', 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'];
    $hari = strtoupper($hari_list[(int) date('w', $timestamp)]);
    $tanggal_angka = (int) date('j', $timestamp);
    $bulan_angka = (int) date('n', $timestamp);
    $tahun_angka = (int) date('Y', $timestamp);
    $result = [
        'hari' => $hari,
        'tanggal' => terbilang_kaur($tanggal_angka),
        'bulan' => $bulan_list[$bulan_angka],
        'tahun' => terbilang_kaur($tahun_angka),
    ];
    $result['label'] = "Pada hari {$result['hari']} tanggal {$result['tanggal']} Bulan {$result['bulan']} Tahun {$result['tahun']}";
    return $result;
}
$filters = $filters ?? [];
$stats = $stats ?? ['pengajuan' => 0, 'total_pengajuan' => 0, 'negosiasi' => 0, 'sedang_negosiasi' => 0, 'deal' => 0, 'deal_approval' => 0, 'menunggu_approval' => 0, 'bast' => 0, 'total_bast' => 0, 'laporan_deal' => 0];
$anggaran = $anggaran ?? ['tahun' => date('Y'), 'total_anggaran' => 0, 'total_pengeluaran' => 0, 'sisa_anggaran' => 0, 'persentase_penggunaan' => 0, 'catatan' => null];
$dashboard_year = (int) ($dashboard_year ?? $anggaran['tahun'] ?? date('Y'));
$dashboard_years = $dashboard_years ?? [$dashboard_year];
$dashboard_monthly_submissions = $dashboard_monthly_submissions ?? array_fill(0, 12, 0);
$dashboard_status_breakdown = $dashboard_status_breakdown ?? ['Pengajuan' => 0, 'Sedang Negosiasi' => 0, 'Deal' => 0, 'Ditolak' => 0, 'Revisi' => 0];
$dashboard_negotiation = $dashboard_negotiation ?? ['harga_awal' => 0, 'harga_negosiasi' => 0, 'penghematan' => 0];
$dashboard_activity = $dashboard_activity ?? [];
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$total_rows = $total_rows ?? count($pengajuan_kaprodi ?? []);
$notif_items = isset($notifikasi) && is_array($notifikasi) ? $notifikasi : [];
$notif_count = (int) ($unread_notifikasi ?? 0);
$active_module = $active_module ?? 'overview';
$module_meta = [
    'overview' => ['title' => 'Dashboard Kaur Laboratorium', 'desc' => 'Pilih fitur operasional Kaur Laboratorium dari panel berikut.'],
    'pengajuan' => ['title' => 'Pengajuan Kaprodi', 'desc' => 'Pantau seluruh kebutuhan barang dan jasa dari prodi.'],
    'negosiasi' => ['title' => 'Negosiasi Pengadaan', 'desc' => 'Pilih vendor dan simpan riwayat harga negosiasi.'],
    'approval' => ['title' => 'Approval Pengadaan', 'desc' => 'Setujui, revisi, atau tolak pengajuan setelah negosiasi selesai.'],
    'peminjaman' => ['title' => 'ACC Peminjaman', 'desc' => 'ACC final setelah Kaprodi dan Laboran; QR kemudian difinalkan Laboran.'],
    'anggaran' => ['title' => 'Alokasi Anggaran', 'desc' => 'Kelola total anggaran, pengeluaran, sisa, dan persentase penggunaan.'],
    'bast' => ['title' => 'BAST', 'desc' => 'Input dokumen BAST dari Logistik dan proses barang ke inventory.'],
    'laporan' => ['title' => 'Laporan', 'desc' => 'Lihat hasil akhir negosiasi yang sudah Deal.'],
];
$module = $module_meta[$active_module] ?? $module_meta['overview'];
$is_overview = $active_module === 'overview';
function kaur_module_url($module) {
    return base_url('index.php/kaur/dashboard' . ($module === 'overview' ? '' : '/' . $module));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= html_escape($title ?? 'Dashboard Kaur Laboratorium') ?></title>
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
        .kaur-multi-filter { padding: 18px; border: 1px solid var(--scm-border, #e8eaed); border-radius: 10px; background: var(--scm-surface, #fff); }
        .panel-card .kaur-multi-filter { padding: 0; border: 0; border-radius: 0; background: transparent; }
        .kaur-filter-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
        .kaur-filter-heading h3 { margin: 0; color: var(--scm-text, #202124); font-size: 1rem; font-weight: 700; }
        .kaur-filter-heading h3 i { color: #ea5b1a; }
        .kaur-filter-heading p { margin: 3px 0 0; color: var(--scm-muted, #6b7280); font-size: .76rem; }
        .kaur-filter-note { color: var(--scm-muted, #6b7280); font-size: .73rem; white-space: nowrap; }
        .kaur-filter-list { display: grid; gap: 10px; }
        .kaur-filter-row { display: grid; grid-template-columns: minmax(210px, .72fr) minmax(280px, 1.55fr) auto; align-items: center; gap: 10px; }
        .kaur-filter-row .form-select, .kaur-filter-row .form-control { min-height: 44px; border-color: var(--scm-border, #d8dde3); color: var(--scm-text, #202124); background-color: var(--scm-surface, #fff); }
        .kaur-filter-tools { display: flex; align-items: center; gap: 8px; }
        .kaur-filter-icon { width: 44px; height: 44px; display: inline-flex; flex: 0 0 44px; align-items: center; justify-content: center; padding: 0; border-radius: 50%; }
        .kaur-filter-add { border-color: #ea5b1a; color: #ea5b1a; }
        .kaur-filter-add:hover { border-color: #ea5b1a; color: #fff; background: #ea5b1a; }
        .kaur-filter-icon:disabled { opacity: .38; }
        .summary-card { min-height: 96px; padding: 18px; }
        .summary-card .value { font-weight: 700; font-size: 1.5rem; line-height: 1; }
        .summary-card .label { color: #6c757d; font-size: .82rem; margin-top: 8px; }
        .quick-link { border: 1px solid #e8eaed; border-radius: 8px; color: #202124; text-decoration: none; background: #fff; transition: .18s ease; min-height: 74px; }
        .quick-link:hover { transform: translateY(-2px); border-color: rgba(234, 91, 26, .35); color: #202124; }
        .quick-icon { width: 38px; height: 38px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(234, 91, 26, .12); color: #c24a13; }
        .module-strip { position: sticky; top: 78px; z-index: 15; background: rgba(245, 246, 248, .96); backdrop-filter: blur(8px); border-bottom: 1px solid #e8eaed; }
        .module-strip .nav { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 2px; }
        .module-strip .nav-link { color: #495057; white-space: nowrap; border-radius: 999px; font-size: .86rem; font-weight: 600; }
        .module-strip .nav-link.active { background: #ea5b1a; color: #fff; }
        .table-clean thead th { font-size: .76rem; text-transform: uppercase; letter-spacing: .04em; color: #5f6368; background: #f8f9fa; border-bottom: 1px solid #e8eaed; white-space: nowrap; }
        .table-clean td { vertical-align: middle; }
        .status-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .status-pengajuan { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-revisi, .status-negosiasi { background: rgba(245, 158, 11, .16); color: #a16207; }
        .status-Disetujui-Menunggu-Finalisasi-QR- { background: rgba(13, 110, 253, .12); color: #0d6efd; }
        .status-deal, .status-approval { background: rgba(25, 135, 84, .12); color: #198754; }
        .status-bast { background: rgba(13, 202, 240, .15); color: #087990; }
        .status-inventory, .status-selesai { background: rgba(32, 201, 151, .14); color: #087f5b; }
        .status-ditolak { background: rgba(220, 53, 69, .12); color: #dc3545; }
        .section-anchor { scroll-margin-top: 92px; }
        .kaur-submission-table-card {
            --submission-bg: var(--scm-surface, #111416);
            --submission-head-bg: var(--scm-surface-strong, #181b1e);
            --submission-border: var(--scm-border, #2b2f33);
            --submission-text: var(--scm-text, #f7f7f7);
            --submission-muted: var(--scm-muted, #a8adb5);
            overflow: hidden;
            border: 1px solid var(--submission-border);
            border-radius: 10px;
            background: var(--submission-bg);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        }
        html.scm-theme-light .kaur-submission-table-card {
            --submission-bg: #ffffff;
            --submission-head-bg: #f9fafb;
            --submission-border: #e5e7eb;
            --submission-text: #1f2937;
            --submission-muted: #6b7280;
        }
        html.scm-theme-light .scm-dashboard .panel-card.kaur-submission-table-card {
            border-color: #e5e7eb !important;
            background: #ffffff !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
        }
        .kaur-submission-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .scm-dashboard .kaur-row-number { color: var(--submission-muted, var(--scm-muted)); font-weight: 600; text-align: center; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .scm-dashboard .kaur-submission-table {
            min-width: 1184px;
            margin: 0;
            --bs-table-bg: var(--submission-bg) !important;
            --bs-table-color: var(--submission-text) !important;
            --bs-table-border-color: var(--submission-border) !important;
        }
        .scm-dashboard .kaur-submission-table.table-clean thead th {
            padding: 14px 18px;
            color: var(--submission-muted) !important;
            background: var(--submission-head-bg) !important;
            border-color: var(--submission-border) !important;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        html.scm-theme-light .scm-dashboard .kaur-submission-table.table-clean thead th {
            color: #6b7280 !important;
            background: #f9fafb !important;
            border-color: #e5e7eb !important;
        }
        .scm-dashboard .kaur-submission-table tbody tr { height: auto; }
        .scm-dashboard .kaur-submission-table tbody td {
            padding: 16px 18px;
            color: var(--submission-text);
            background: var(--submission-bg);
            border-color: var(--submission-border);
            line-height: 1.5;
            vertical-align: middle;
            transition: background-color .16s ease;
        }
        .scm-dashboard .kaur-submission-table tbody tr:hover > td { background: rgba(234, 91, 26, .045); }
        .scm-dashboard .kaur-submission-table th:nth-child(1), .scm-dashboard .kaur-submission-table td:nth-child(1) { width: 64px; min-width: 64px; text-align: center; }
        .scm-dashboard .kaur-submission-table th:nth-child(2), .scm-dashboard .kaur-submission-table td:nth-child(2) { min-width: 185px; }
        .scm-dashboard .kaur-submission-table th:nth-child(3), .scm-dashboard .kaur-submission-table td:nth-child(3) { min-width: 250px; }
        .scm-dashboard .kaur-submission-table th:nth-child(4), .scm-dashboard .kaur-submission-table td:nth-child(4) { width: 160px; text-align: center; }
        .scm-dashboard .kaur-submission-table th:nth-child(5), .scm-dashboard .kaur-submission-table td:nth-child(5) { min-width: 360px; }
        .scm-dashboard .kaur-submission-table th:nth-child(6), .scm-dashboard .kaur-submission-table td:nth-child(6) { width: 180px; text-align: center; }
        .scm-dashboard .kaur-submission-table th:nth-child(7), .scm-dashboard .kaur-submission-table td:nth-child(7) { min-width: 145px; white-space: nowrap; }
        .scm-dashboard .kaur-submission-table th:nth-child(8), .scm-dashboard .kaur-submission-table td:nth-child(8) { min-width: 120px; white-space: nowrap; }
        .scm-dashboard .kaur-submission-table td:nth-child(3) > .small,
        .scm-dashboard .kaur-submission-table td:nth-child(5) > .text-muted {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .scm-dashboard .kaur-submission-table td:nth-child(5) > .small:not(.text-muted) {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .scm-dashboard .kaur-submission-table td:nth-child(5) > .small:nth-of-type(n + 4) { display: none; }
        .scm-dashboard .kaur-submission-table .kaur-kind-badge,
        .scm-dashboard .kaur-submission-table .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
        .scm-dashboard .kaur-submission-table .kaur-kind-badge { min-width: 132px; }
        .scm-dashboard .kaur-submission-table .status-pill { min-width: 152px; }
        .kaur-submission-pagination-footer {
            display: grid;
            grid-template-columns: minmax(0, auto) 1fr minmax(0, auto);
            align-items: center;
            gap: 1rem;
            min-height: 64px;
            padding: .75rem 1rem;
            border-top: 1px solid var(--submission-border);
            color: var(--submission-muted);
            background: var(--submission-head-bg);
        }
        .kaur-submission-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; }
        .kaur-submission-pagination-summary, .kaur-submission-pagination-status { font-size: .72rem; white-space: nowrap; }
        .kaur-submission-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; font-size: .72rem; }
        .kaur-submission-pagination-status { text-align: center; }
        .kaur-submission-pagination { margin: 0; }
        .scm-dashboard .kaur-submission-table-card .kaur-submission-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            min-height: 34px;
            padding: .35rem .58rem;
            border-color: var(--submission-border) !important;
            color: var(--submission-text) !important;
            background: var(--submission-bg) !important;
            font-size: .72rem;
            line-height: 1;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease;
        }
        .scm-dashboard .kaur-submission-table-card .kaur-submission-pagination .page-link:hover { color: var(--scm-orange, #ff7900) !important; background: var(--submission-head-bg) !important; }
        .scm-dashboard .kaur-submission-table-card .kaur-submission-pagination .page-item.active .page-link {
            color: #ffffff !important;
            background: var(--scm-orange, #ff7900) !important;
            border-color: var(--scm-orange, #ff7900) !important;
        }
        .scm-dashboard .kaur-submission-table-card .kaur-submission-pagination .page-item.disabled .page-link { color: var(--submission-muted) !important; background: var(--submission-head-bg) !important; opacity: .62; }
        .kaur-report-table-header { padding: 22px 22px 16px; }
        .kaur-report-toolbar { padding: 0 22px 18px; border-bottom: 1px solid var(--submission-border); }
        .kaur-report-search { display: grid; grid-template-columns: minmax(280px, 1fr) auto; align-items: end; gap: 10px; }
        .kaur-report-search .form-label { margin-bottom: 6px; color: var(--submission-text); font-size: .72rem; font-weight: 700; }
        .kaur-report-search .input-group-text, .kaur-report-search .form-control { min-height: 42px; border-color: var(--submission-border); font-size: .78rem; }
        .kaur-report-search .input-group-text { color: var(--submission-muted); background: var(--submission-bg); }
        .kaur-report-search-actions { align-self: start; display: flex; align-items: center; gap: 8px; margin-top: 25px; }
        .kaur-report-search-actions .btn { display: inline-flex; width: 110px; min-width: 110px; height: 42px; min-height: 42px; align-items: center; justify-content: center; padding: 0 14px !important; font-size: .72rem; font-weight: 600; white-space: nowrap; }
        .kaur-report-search-note { margin-top: 6px; color: var(--submission-muted); font-size: .68rem; }
        .kaur-report-sort-link { display: inline-flex; align-items: center; gap: 3px; color: inherit !important; text-decoration: none; }
        .kaur-report-sort-link:hover, .kaur-report-sort-link:focus-visible { color: var(--scm-orange, #ff7900) !important; }
        .kaur-report-sort-link.is-active { color: var(--scm-orange, #ff7900) !important; }
        .kaur-report-sort-link.is-active i { color: inherit !important; }
        .kaur-report-sort-link i { font-size: .72rem; }
        .scm-dashboard .kaur-report-table {
            min-width: 1444px;
            margin: 0;
            --bs-table-bg: var(--submission-bg) !important;
            --bs-table-color: var(--submission-text) !important;
            --bs-table-border-color: var(--submission-border) !important;
        }
        .scm-dashboard .kaur-report-table.table-clean thead th {
            padding: 13px 14px;
            color: var(--submission-muted) !important;
            background: var(--submission-head-bg) !important;
            border-color: var(--submission-border) !important;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .scm-dashboard .kaur-report-table tbody td {
            padding: 14px;
            color: var(--submission-text);
            background: var(--submission-bg);
            border-color: var(--submission-border);
            line-height: 1.45;
            vertical-align: middle;
            transition: background-color .16s ease;
        }
        .scm-dashboard .kaur-report-table tbody tr:hover > td { background: rgba(234, 91, 26, .045); }
        .scm-dashboard .kaur-report-table th:nth-child(1), .scm-dashboard .kaur-report-table td:nth-child(1) { width: 64px; min-width: 64px; text-align: center; }
        .scm-dashboard .kaur-report-table th:nth-child(2), .scm-dashboard .kaur-report-table td:nth-child(2) { min-width: 245px; }
        .scm-dashboard .kaur-report-table th:nth-child(3), .scm-dashboard .kaur-report-table td:nth-child(3) { min-width: 230px; }
        .scm-dashboard .kaur-report-table th:nth-child(4), .scm-dashboard .kaur-report-table td:nth-child(4) { min-width: 190px; }
        .scm-dashboard .kaur-report-table th:nth-child(5), .scm-dashboard .kaur-report-table td:nth-child(5),
        .scm-dashboard .kaur-report-table th:nth-child(6), .scm-dashboard .kaur-report-table td:nth-child(6),
        .scm-dashboard .kaur-report-table th:nth-child(7), .scm-dashboard .kaur-report-table td:nth-child(7) { min-width: 130px; white-space: nowrap; }
        .scm-dashboard .kaur-report-table th:nth-child(8), .scm-dashboard .kaur-report-table td:nth-child(8) { min-width: 105px; }
        .scm-dashboard .kaur-report-table th:nth-child(9), .scm-dashboard .kaur-report-table td:nth-child(9) { min-width: 145px; }
        .scm-dashboard .kaur-report-table th:nth-child(10), .scm-dashboard .kaur-report-table td:nth-child(10) { min-width: 210px; }
        @media (max-width: 767.98px) {
            .kaur-multi-filter { padding: 14px; }
            .kaur-filter-heading { flex-direction: column; gap: 7px; }
            .kaur-filter-note { white-space: normal; }
            .kaur-filter-row { grid-template-columns: 1fr; gap: 8px; padding: 12px; border: 1px solid var(--scm-border, #e8eaed); border-radius: 10px; }
            .kaur-filter-tools { justify-content: flex-end; }
            .kaur-report-table-header, .kaur-report-toolbar { padding-left: 16px; padding-right: 16px; }
            .kaur-report-search { grid-template-columns: 1fr; }
            .kaur-report-search-actions { width: 100%; flex-wrap: wrap; margin-top: 0; }
            .kaur-report-search-actions .btn { width: auto; min-width: 0; flex: 1 1 120px; }
        }
        .negotiation-request-list {
            --negotiation-bg: var(--scm-surface, #121415);
            --negotiation-soft: var(--scm-surface-strong, #181a1b);
            --negotiation-border: var(--scm-border, #292d30);
            --negotiation-text: var(--scm-text, #f4f5f6);
            --negotiation-muted: var(--scm-muted, #9ca3aa);
            display: grid;
            gap: 14px;
        }
        html.scm-theme-light .negotiation-request-list {
            --negotiation-bg: #ffffff;
            --negotiation-soft: #f9fafb;
            --negotiation-border: #e5e7eb;
            --negotiation-text: #1f2937;
            --negotiation-muted: #6b7280;
        }
        .scm-dashboard .negotiation-request-card {
            overflow: hidden;
            border: 1px solid var(--negotiation-border) !important;
            border-radius: 8px;
            color: var(--negotiation-text) !important;
            background: var(--negotiation-bg) !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .05) !important;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .scm-dashboard .negotiation-request-card:hover {
            border-color: rgba(234, 91, 26, .34) !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08) !important;
            transform: translateY(-1px);
        }
        .negotiation-request-header { padding: 18px 20px 16px; }
        .negotiation-request-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
        .negotiation-request-code { color: var(--negotiation-text); font-size: .72rem; font-weight: 600; letter-spacing: .025em; }
        .negotiation-request-title { margin: 4px 0 0; color: var(--negotiation-text); font-size: 1rem; font-weight: 700; line-height: 1.4; }
        .negotiation-request-heading .status-pill {
            justify-content: center;
            min-width: 138px;
            min-height: 32px;
            border: 1px solid var(--negotiation-border);
            border-radius: 8px;
            color: var(--negotiation-text) !important;
            background: var(--negotiation-soft) !important;
        }
        .negotiation-request-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 18px; margin-top: 13px; color: var(--negotiation-muted); font-size: .76rem; font-weight: 500; }
        .negotiation-request-meta span { display: inline-flex; align-items: center; gap: 6px; }
        .negotiation-request-meta i { color: var(--negotiation-muted); }
        html.scm-theme-light .negotiation-request-code { color: #1f2937; }
        html.scm-theme-light .negotiation-request-meta,
        html.scm-theme-light .negotiation-request-meta i { color: #4b5563; }
        .negotiation-detail-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 48px;
            padding: 10px 20px;
            border: 0;
            border-top: 1px solid var(--negotiation-border);
            color: var(--negotiation-text);
            background: var(--negotiation-soft);
            font-size: .78rem;
            font-weight: 600;
            text-align: left;
            transition: color .16s ease, background-color .16s ease;
        }
        .negotiation-detail-toggle:hover { color: var(--scm-orange, #ea5b1a); background: rgba(234, 91, 26, .055); }
        .negotiation-detail-toggle i { transition: transform .2s ease; }
        .negotiation-detail-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
        .negotiation-detail-body { border-top: 1px solid var(--negotiation-border); }
        .negotiation-detail-toolbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; padding: 16px 20px; background: var(--negotiation-bg); }
        .negotiation-detail-toolbar h3 { margin: 0; color: var(--negotiation-text); font-size: .88rem; font-weight: 700; }
        .negotiation-detail-toolbar p { margin: 4px 0 0; color: var(--negotiation-muted); font-size: .72rem; }
        .negotiation-group-status-wrap { flex: 0 0 260px; }
        .negotiation-group-status-wrap label { display: block; margin-bottom: 5px; color: var(--negotiation-muted); font-size: .68rem; font-weight: 600; }
        .negotiation-group-status-form { display: flex; gap: 8px; align-items: stretch; }
        .negotiation-group-status-form .negotiation-group-status { flex: 1 1 auto; min-width: 0; }
        .negotiation-group-save-btn { flex: 0 0 auto; white-space: nowrap; font-size: .74rem; min-height: 34px; }
        .approval-item-rejected { opacity: .55; }
        .approval-item-rejected td { text-decoration: line-through; text-decoration-color: rgba(220, 53, 69, .45); }
        .approval-item-rejected .status-pill { text-decoration: none; }
        .negotiation-items-scroll { overflow: visible; }
        .negotiation-items-table {
            display: grid;
            gap: 16px;
            padding: 0 20px 20px;
        }
        .scm-dashboard .negotiation-form {
            margin: 0;
            padding: 20px;
            border: 1px solid var(--negotiation-border);
            border-radius: 8px;
            color: var(--negotiation-text);
            background: var(--negotiation-soft);
            box-shadow: 0 4px 14px rgba(15, 23, 42, .035);
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }
        .scm-dashboard .negotiation-form:hover {
            border-color: rgba(234, 91, 26, .32);
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            transform: translateY(-1px);
        }
        .negotiation-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--negotiation-border);
        }
        .negotiation-item-name { min-width: 0; }
        .negotiation-item-name strong { display: block; overflow-wrap: anywhere; font-size: .86rem; line-height: 1.45; }
        .negotiation-item-name span { display: block; margin-top: 3px; color: var(--negotiation-muted); font-size: .69rem; }
        .negotiation-item-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(234, 91, 26, .28);
            border-radius: 8px;
            color: var(--scm-orange, #ea5b1a);
            background: rgba(234, 91, 26, .07);
            font-size: .72rem;
            font-weight: 700;
        }
        .negotiation-form-grid { --bs-gutter-x: 16px; --bs-gutter-y: 16px; }
        .negotiation-field { min-width: 0; }
        .negotiation-field-label { display: block; margin-bottom: 6px; color: var(--negotiation-text); font-size: .7rem; font-weight: 600; }
        .negotiation-field .form-control,
        .negotiation-field .form-select { min-height: 42px; padding: .5rem .7rem; font-size: .74rem; }
        .negotiation-field .form-control:disabled,
        .negotiation-field .form-control[readonly] { color: var(--negotiation-muted) !important; background: var(--negotiation-bg) !important; border-color: var(--negotiation-border) !important; opacity: .9; }
        .negotiation-field .form-text { margin-top: 5px; color: var(--negotiation-muted); font-size: .63rem; }
        .negotiation-form-actions { display: flex; justify-content: flex-end; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--negotiation-border); }
        .negotiation-save-btn { min-width: 118px; min-height: 40px; white-space: nowrap; font-size: .74rem; }
        .negotiation-pagination-shell { margin-top: 14px; }
        .negotiation-pagination-shell .kaur-submission-pagination-footer { border-top: 0; }
        @media (max-width: 767.98px) {
            .negotiation-request-header { padding: 16px; }
            .negotiation-request-heading { flex-direction: column; gap: 10px; }
            .negotiation-request-heading .status-pill { align-self: flex-start; }
            .negotiation-detail-toggle { padding-inline: 16px; }
            .negotiation-detail-toolbar { align-items: stretch; flex-direction: column; padding: 14px 16px; }
            .negotiation-group-status-wrap { flex-basis: auto; width: 100%; }
            .negotiation-items-table { padding: 0 16px 16px; }
            .scm-dashboard .negotiation-form { padding: 16px; }
        }
        @media (max-width: 575.98px) {
            .negotiation-item-header { align-items: flex-start; }
            .negotiation-form-actions { display: block; }
            .negotiation-save-btn { width: 100%; }
        }
        .approval-table-card {
            --approval-bg: var(--scm-surface, #111416);
            --approval-head-bg: var(--scm-surface-strong, #181b1e);
            --approval-border: var(--scm-border, #2b2f33);
            --approval-text: var(--scm-text, #f7f7f7);
            --approval-muted: var(--scm-muted, #a8adb5);
            overflow: hidden;
            border: 1px solid var(--approval-border) !important;
            border-radius: 10px;
            color: var(--approval-text);
            background: var(--approval-bg) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
        }
        html.scm-theme-light .approval-table-card {
            --approval-bg: #ffffff;
            --approval-head-bg: #f9fafb;
            --approval-border: #e5e7eb;
            --approval-text: #1f2937;
            --approval-muted: #6b7280;
            border-color: #e5e7eb !important;
            background: #ffffff !important;
        }
        .approval-table-heading { padding: 20px 22px 16px; }
        .approval-table-heading h2 { color: var(--approval-text); }
        .approval-table-heading .text-muted { color: var(--approval-muted) !important; }
        .approval-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .scm-dashboard .approval-table {
            min-width: 1444px;
            margin: 0;
            --bs-table-bg: var(--approval-bg) !important;
            --bs-table-color: var(--approval-text) !important;
            --bs-table-border-color: var(--approval-border) !important;
        }
        .scm-dashboard .approval-table.table-clean thead th {
            padding: 14px 16px;
            color: var(--approval-muted) !important;
            background: var(--approval-head-bg) !important;
            border-color: var(--approval-border) !important;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .scm-dashboard .approval-table tbody tr { height: 76px; }
        .scm-dashboard .approval-table tbody td {
            padding: 14px 16px;
            color: var(--approval-text);
            background: var(--approval-bg);
            border-color: var(--approval-border);
            font-size: .78rem;
            line-height: 1.45;
            vertical-align: middle;
            transition: background-color .16s ease;
        }
        .scm-dashboard .approval-table tbody tr:hover > td { background: rgba(234, 91, 26, .04); }
        .scm-dashboard .approval-table th:nth-child(1), .scm-dashboard .approval-table td:nth-child(1) { width: 64px; min-width: 64px; text-align: center; }
        .scm-dashboard .approval-table th:nth-child(2), .scm-dashboard .approval-table td:nth-child(2) { min-width: 215px; }
        .scm-dashboard .approval-table th:nth-child(3), .scm-dashboard .approval-table td:nth-child(3) { min-width: 145px; white-space: nowrap; }
        .scm-dashboard .approval-table th:nth-child(4), .scm-dashboard .approval-table td:nth-child(4) { min-width: 170px; }
        .scm-dashboard .approval-table th:nth-child(5), .scm-dashboard .approval-table td:nth-child(5) { width: 165px; text-align: center; }
        .scm-dashboard .approval-table th:nth-child(6), .scm-dashboard .approval-table td:nth-child(6) { min-width: 230px; }
        .scm-dashboard .approval-table th:nth-child(7), .scm-dashboard .approval-table td:nth-child(7) { min-width: 155px; white-space: nowrap; }
        .scm-dashboard .approval-table th:nth-child(8), .scm-dashboard .approval-table td:nth-child(8),
        .scm-dashboard .approval-table th:nth-child(9), .scm-dashboard .approval-table td:nth-child(9) { width: 175px; text-align: center; }
        .scm-dashboard .approval-table th:nth-child(10), .scm-dashboard .approval-table td:nth-child(10) { width: 125px; text-align: right; }
        .scm-dashboard .approval-table .approval-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 148px;
            height: 34px;
            padding: 6px 12px;
            border: 1px solid var(--approval-border) !important;
            border-radius: 8px;
            color: var(--approval-text) !important;
            background: var(--approval-bg) !important;
            font-size: .7rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        html.scm-theme-light .scm-dashboard .approval-table .approval-badge { color: #374151 !important; background: #ffffff !important; border-color: #e5e7eb !important; }
        .approval-detail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            min-height: 36px;
            border-width: 1px;
            font-size: .72rem;
            transition: color .16s ease, background-color .16s ease, border-color .16s ease, transform .16s ease;
        }
        .approval-detail-btn:hover { transform: translateY(-1px); }
        .approval-table-card .kaur-submission-pagination-footer { border-top-color: var(--approval-border); color: var(--approval-muted); background: var(--approval-head-bg); }
        .approval-detail-modal .approval-detail-items-table { min-width: 960px; margin-bottom: 18px; }
        .approval-detail-modal .approval-detail-items-table th,
        .approval-detail-modal .approval-detail-items-table td { padding: 9px 10px; vertical-align: middle; }
        .approval-detail-item { display: flex; align-items: center; gap: 8px; min-width: 300px; }
        .approval-detail-item-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 84px;
            width: 84px;
            height: 28px;
            padding: 4px 8px;
            border: 1px solid var(--scm-border, #2b2f33);
            border-radius: 8px;
            color: var(--scm-text, #f7f7f7);
            background: var(--scm-surface-strong, #181b1e);
            font-size: .68rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
        }
        html.scm-theme-light .approval-detail-item-badge { color: #374151; background: #ffffff; border-color: #e5e7eb; }
        @media (max-width: 767.98px) {
            .approval-table-heading { padding: 18px 16px 14px; }
            .scm-dashboard .approval-table tbody tr { height: 72px; }
        }
        .bast-table-card {
            --bast-bg: var(--scm-surface, #111416);
            --bast-head-bg: var(--scm-surface-strong, #181b1e);
            --bast-border: var(--scm-border, #2b2f33);
            --bast-text: var(--scm-text, #f7f7f7);
            --bast-muted: var(--scm-muted, #a8adb5);
            overflow: hidden;
            border: 1px solid var(--bast-border) !important;
            border-radius: 10px;
            color: var(--bast-text);
            background: var(--bast-bg) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
        }
        html.scm-theme-light .bast-table-card {
            --bast-bg: #ffffff;
            --bast-head-bg: #f9fafb;
            --bast-border: #e5e7eb;
            --bast-text: #1f2937;
            --bast-muted: #6b7280;
            border-color: #e5e7eb !important;
            background: #ffffff !important;
        }
        .bast-table-toolbar { padding: 20px 22px 16px; }
        .bast-table-toolbar h2 { color: var(--bast-text); }
        .bast-table-toolbar .text-muted { color: var(--bast-muted) !important; }
        .bast-summary-badge {
            min-height: 32px;
            padding: 7px 12px;
            border: 1px solid var(--bast-border);
            border-radius: 8px;
            color: var(--bast-text);
            background: var(--bast-head-bg);
            font-size: .7rem;
            font-weight: 600;
        }
        .bast-table-card .table-responsive { scrollbar-color: #cfd4da transparent; }
        .scm-dashboard .bast-table {
            min-width: 1304px;
            margin: 0;
            --bs-table-bg: var(--bast-bg) !important;
            --bs-table-color: var(--bast-text) !important;
            --bs-table-border-color: var(--bast-border) !important;
        }
        .scm-dashboard .bast-table.table-clean thead th {
            padding: 14px 16px;
            color: var(--bast-muted) !important;
            background: var(--bast-head-bg) !important;
            border-color: var(--bast-border) !important;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .035em;
            vertical-align: middle;
        }
        .scm-dashboard .bast-table tbody tr { height: 76px; }
        .scm-dashboard .bast-table tbody td {
            padding: 14px 16px;
            color: var(--bast-text);
            background: var(--bast-bg);
            border-color: var(--bast-border);
            font-size: .78rem;
            line-height: 1.45;
            vertical-align: middle;
            transition: background-color .16s ease;
        }
        .scm-dashboard .bast-table tbody tr:hover > td { background: rgba(234, 91, 26, .04); }
        .scm-dashboard .bast-table th:nth-child(1), .scm-dashboard .bast-table td:nth-child(1) { width: 64px; min-width: 64px; text-align: center; }
        .scm-dashboard .bast-table th:nth-child(2), .scm-dashboard .bast-table td:nth-child(2) { min-width: 215px; }
        .scm-dashboard .bast-table th:nth-child(3), .scm-dashboard .bast-table td:nth-child(3) { min-width: 300px; }
        .scm-dashboard .bast-table th:nth-child(4), .scm-dashboard .bast-table td:nth-child(4) { width: 160px; text-align: center; }
        .scm-dashboard .bast-table th:nth-child(5), .scm-dashboard .bast-table td:nth-child(5) { min-width: 140px; }
        .scm-dashboard .bast-table th:nth-child(6), .scm-dashboard .bast-table td:nth-child(6) { min-width: 145px; white-space: nowrap; }
        .scm-dashboard .bast-table th:nth-child(7), .scm-dashboard .bast-table td:nth-child(7) { min-width: 330px; text-align: right; }
        .bast-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 132px;
            height: 34px;
            padding: 6px 12px;
            border: 1px solid var(--bast-border) !important;
            border-radius: 8px;
            color: var(--bast-text) !important;
            background: var(--bast-bg) !important;
            font-size: .7rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            white-space: nowrap;
        }
        html.scm-theme-light .bast-badge { color: #374151 !important; background: #ffffff !important; border-color: #e5e7eb !important; }
        .bast-actions { display: inline-flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
        .bast-action-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; font-size: .72rem; transition: transform .16s ease, color .16s ease, background-color .16s ease, border-color .16s ease; }
        .bast-action-btn:hover { transform: translateY(-1px); }
        .bast-table-card .kaur-submission-pagination-footer { border-top-color: var(--bast-border); color: var(--bast-muted); background: var(--bast-head-bg); }
        @media (max-width: 767.98px) {
            .bast-table-toolbar { padding: 18px 16px 14px; }
            .scm-dashboard .bast-table tbody tr { height: 72px; }
        }
        .item-card { border: 1px solid #e8eaed; border-radius: 8px; background: #fff; }
        .mini-label { font-size: .74rem; color: #6c757d; font-weight: 600; }
        .progress { height: 10px; border-radius: 999px; }
        .notif-bell { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 38px; }
        .notif-menu { width: min(380px, calc(100vw - 32px)); max-height: min(420px, calc(100vh - 110px)); overflow-y: auto; }
        @media (max-width: 767.98px) {
            .topbar-actions { width: 100%; flex-wrap: wrap; justify-content: flex-end; }
            .topbar-actions .btn { flex: 0 0 auto; }
            .topbar-actions .notif-bell { flex: 0 0 38px; }
            .summary-card { min-height: auto; }
            .module-strip { top: 126px; }
            .kaur-submission-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .kaur-submission-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
        }

        .scm-dashboard-kaur .dashboard-content { background: var(--scm-bg); }
        .kaur-overview-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 22px; }
        .kaur-overview-eyebrow { color: var(--scm-orange); font-size: .72rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .kaur-overview-heading h2 { color: var(--scm-text); font-size: clamp(1.45rem, 2vw, 2rem); margin: 5px 0 7px; }
        .kaur-overview-heading p { color: var(--scm-muted); margin: 0; font-size: .9rem; }
        .kaur-year-filter { background: var(--scm-surface); border: 1px solid var(--scm-border); border-radius: 10px; padding: 10px 12px; min-width: 180px; }
        .kaur-year-filter label { color: var(--scm-muted); display: block; font-size: .7rem; font-weight: 700; letter-spacing: .05em; margin-bottom: 5px; text-transform: uppercase; }
        .kaur-year-filter .form-select { background-color: #17191a; border-color: var(--scm-border); color: var(--scm-text); }
        .kaur-stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .kaur-stat-card { background: linear-gradient(145deg, #151819 0%, #101213 100%); border: 1px solid var(--scm-border); border-radius: 14px; min-height: 148px; overflow: hidden; padding: 18px; position: relative; transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease; }
        .kaur-stat-card:hover { border-color: rgba(255, 121, 0, .55); box-shadow: 0 14px 28px rgba(0, 0, 0, .2); transform: translateY(-3px); }
        .kaur-stat-card::after { background: currentColor; content: ''; height: 3px; left: 0; opacity: .65; position: absolute; right: 0; top: 0; }
        .kaur-stat-top { align-items: flex-start; display: flex; justify-content: space-between; gap: 10px; }
        .kaur-stat-icon { align-items: center; border: 1px solid currentColor; border-radius: 10px; display: inline-flex; flex: 0 0 38px; height: 38px; justify-content: center; opacity: .95; width: 38px; }
        .kaur-stat-label { color: var(--scm-muted); font-size: .78rem; line-height: 1.35; margin-top: 15px; }
        .kaur-stat-value { color: var(--scm-text); font-size: clamp(1.1rem, 1.75vw, 1.65rem); font-weight: 700; letter-spacing: .01em; line-height: 1.2; margin-top: 6px; overflow-wrap: anywhere; }
        .kaur-stat-blue, .kaur-stat-amber, .kaur-stat-green, .kaur-stat-red, .kaur-stat-orange, .kaur-stat-cyan, .kaur-stat-purple { color: var(--scm-orange); }
        .kaur-stat-slate { color: #aeb6ba; }
        .kaur-stat-blue .kaur-stat-value, .kaur-stat-amber .kaur-stat-value, .kaur-stat-green .kaur-stat-value, .kaur-stat-red .kaur-stat-value, .kaur-stat-orange .kaur-stat-value, .kaur-stat-cyan .kaur-stat-value, .kaur-stat-purple .kaur-stat-value, .kaur-stat-slate .kaur-stat-value { color: #f5f7f8; }
        .kaur-chart-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .85fr); gap: 14px; margin-bottom: 14px; }
        .kaur-chart-panel, .kaur-activity-panel, .kaur-quick-panel { background: #111314; border: 1px solid var(--scm-border); border-radius: 14px; min-width: 0; padding: 18px; }
        .kaur-chart-header, .kaur-panel-header { align-items: flex-start; display: flex; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .kaur-chart-header h3, .kaur-panel-header h3 { color: var(--scm-text); font-size: .95rem; font-weight: 600; margin: 0; }
        .kaur-chart-header p, .kaur-panel-header p { color: var(--scm-muted); font-size: .76rem; margin: 4px 0 0; }
        .kaur-chart-note { color: var(--scm-muted); font-size: .73rem; white-space: nowrap; }
        .kaur-chart-wrap { height: 260px; position: relative; }
        .kaur-chart-wrap canvas { height: 100% !important; max-width: 100%; width: 100% !important; }
        .kaur-chart-fallback { align-items: center; color: var(--scm-muted); display: none; height: 100%; justify-content: center; text-align: center; }
        .kaur-budget-list { display: grid; gap: 12px; margin-top: 8px; }
        .kaur-budget-row { align-items: center; display: flex; justify-content: space-between; gap: 12px; }
        .kaur-budget-row span { color: var(--scm-muted); font-size: .78rem; }
        .kaur-budget-row strong { color: var(--scm-text); font-size: .8rem; }
        .kaur-savings { color: var(--scm-orange); font-size: .78rem; font-weight: 600; }
        .kaur-bottom-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 14px; }
        .kaur-activity-list { max-height: 368px; overflow-y: auto; padding-right: 3px; }
        .kaur-activity-item { align-items: flex-start; border-bottom: 1px solid rgba(255, 255, 255, .07); display: flex; gap: 12px; padding: 11px 0; }
        .kaur-activity-item:first-child { padding-top: 2px; }
        .kaur-activity-item:last-child { border-bottom: 0; }
        .kaur-activity-icon { align-items: center; background: rgba(255, 121, 0, .13); border: 1px solid rgba(255, 121, 0, .2); border-radius: 9px; color: var(--scm-orange); display: inline-flex; flex: 0 0 34px; height: 34px; justify-content: center; width: 34px; }
        .kaur-activity-copy { min-width: 0; }
        .kaur-activity-title { color: var(--scm-text); font-size: .8rem; font-weight: 600; }
        .kaur-activity-description { color: var(--scm-muted); font-size: .74rem; margin-top: 3px; overflow-wrap: anywhere; }
        .kaur-activity-meta { align-items: center; color: var(--scm-muted); display: flex; flex-wrap: wrap; font-size: .68rem; gap: 7px; margin-top: 6px; }
        .kaur-activity-meta .status-pill { font-size: .62rem; padding: 3px 7px; }
        .kaur-quick-grid { display: grid; gap: 9px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kaur-quick-action { align-items: center; background: #17191a; border: 1px solid var(--scm-border); border-radius: 10px; color: var(--scm-text); display: flex; gap: 9px; min-height: 54px; padding: 10px; text-decoration: none; transition: .2s ease; }
        .kaur-quick-action:hover { background: rgba(255, 121, 0, .11); border-color: rgba(255, 121, 0, .5); color: #fff; transform: translateY(-2px); }
        .kaur-quick-action i { color: var(--scm-orange); font-size: 1rem; }
        .kaur-quick-action span { font-size: .75rem; font-weight: 600; line-height: 1.25; }
        .topbar-actions { margin-left: auto; }
        .theme-toggle { flex: 0 0 38px !important; height: 38px; padding: 0 !important; width: 38px; }
        .scm-dashboard-kaur .text-warning { color: var(--scm-orange) !important; }
        .scm-dashboard-kaur .kaur-activity-meta .status-pengajuan,
        .scm-dashboard-kaur .kaur-activity-meta .status-revisi,
        .scm-dashboard-kaur .kaur-activity-meta .status-negosiasi,
        .scm-dashboard-kaur .kaur-activity-meta .status-deal,
        .scm-dashboard-kaur .kaur-activity-meta .status-approval,
        .scm-dashboard-kaur .kaur-activity-meta .status-bast,
        .scm-dashboard-kaur .kaur-activity-meta .status-inventory,
        .scm-dashboard-kaur .kaur-activity-meta .status-selesai { background: rgba(255, 121, 0, .14); color: #ffb477; }
        .scm-dashboard-kaur .kaur-activity-meta .status-ditolak { background: rgba(255, 255, 255, .09); color: #c6cdd1; }
        @media (max-width: 1199.98px) { .kaur-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 991.98px) { .kaur-overview-heading { align-items: stretch; flex-direction: column; gap: 14px; } .kaur-year-filter { width: 100%; } .kaur-chart-grid, .kaur-bottom-grid { grid-template-columns: 1fr; } }
        @media (max-width: 575.98px) { .kaur-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; } .kaur-stat-card { min-height: 128px; padding: 14px; } .kaur-stat-icon { flex-basis: 32px; height: 32px; width: 32px; } .kaur-stat-label { font-size: .7rem; margin-top: 11px; } .kaur-stat-value { font-size: 1.08rem; } .kaur-chart-panel, .kaur-activity-panel, .kaur-quick-panel { padding: 14px; } .kaur-chart-wrap { height: 230px; } .kaur-chart-note { white-space: normal; text-align: right; } }

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
        html.scm-theme-light .scm-dashboard-kaur .kaur-year-filter, html.scm-theme-light .scm-dashboard-kaur .kaur-chart-panel, html.scm-theme-light .scm-dashboard-kaur .kaur-activity-panel, html.scm-theme-light .scm-dashboard-kaur .kaur-quick-panel { background: #ffffff; border-color: var(--scm-border); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-year-filter .form-select { background-color: #ffffff; color: #283138; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-stat-card { background: linear-gradient(145deg, #ffffff 0%, #f7f8f9 100%); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-stat-value, html.scm-theme-light .scm-dashboard-kaur .kaur-stat-slate .kaur-stat-value { color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-quick-action { background: #f7f8f9; border-color: var(--scm-border); color: #273138; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-quick-action:hover { background: rgba(234, 91, 26, .08); color: #1c2024; }
        html.scm-theme-light .scm-dashboard-kaur .kaur-activity-item { border-color: rgba(35, 42, 47, .1); }
        html.scm-theme-light .scm-dashboard-kaur .kaur-chart-wrap canvas { color: #263138; }

        /* ACC Peminjaman Kaur — uses the same compact language as Laboran. */
        .scm-dashboard.kaur-loan-page .dashboard-sidebar { width: 204px; }
        .scm-dashboard.kaur-loan-page .dashboard-content { margin-left: 204px; }
        .kaur-loan-page-heading { align-items: center; margin-bottom: 18px !important; }
        .kaur-loan-page-heading h1 { font-size: 1.35rem; line-height: 1.3; }
        .kaur-loan-page-heading p { font-size: .8rem; }
        .kaur-loan-summary, .kaur-loan-filter-card, .kaur-loan-table-card, .kaur-loan-return-card { overflow: hidden; border-radius: 8px; }
        .kaur-loan-heading { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 18px 20px; }
        .kaur-loan-heading h2, .kaur-loan-return-heading h2 { color: var(--scm-text); font-size: 1rem; }
        .kaur-loan-heading .text-muted, .kaur-loan-return-heading .text-muted { color: var(--scm-muted) !important; }
        .kaur-loan-heading__actions { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px; }
        .kaur-loan-heading__actions .btn { min-height: 34px; font-size: .7rem; font-weight: 600; }
        .kaur-loan-count { display: inline-flex; align-items: center; min-height: 34px; padding: 6px 11px; border: 1px solid #f2cf85; border-radius: 999px; color: #8a5800; background: #fff8e8; font-size: .72rem; font-weight: 700; white-space: nowrap; }
        .kaur-loan-filter-card { padding: 16px 18px; }
        .kaur-loan-filter-grid { display: grid; grid-template-columns: minmax(280px, 1.7fr) minmax(170px, .72fr) minmax(170px, .72fr) auto; align-items: start; gap: 12px; }
        .kaur-loan-filter-field label { display: block; margin: 0 0 6px; color: var(--scm-text); font-size: .72rem; font-weight: 700; }
        .kaur-loan-filter-field .form-control { min-height: 42px; border-color: var(--scm-border); border-radius: 8px; font-size: .78rem; }
        .kaur-loan-filter-field .input-group-text { min-width: 42px; justify-content: center; border-color: var(--scm-border); border-radius: 8px 0 0 8px; color: var(--scm-muted); }
        .kaur-loan-filter-field .input-group .form-control { border-radius: 0 8px 8px 0; }
        .kaur-loan-filter-helper { margin-top: 6px; color: var(--scm-muted); font-size: .68rem; }
        .kaur-loan-filter-actions { display: flex; align-items: center; gap: 8px; margin-top: 23px; }
        .kaur-loan-filter-actions .btn { min-height: 42px; border-radius: 999px !important; font-size: .72rem; font-weight: 600; white-space: nowrap; }
        .kaur-loan-table-wrap, .kaur-loan-return-table-wrap { scrollbar-color: #cfd4da transparent; }
        .scm-dashboard .kaur-loan-table, .scm-dashboard .kaur-loan-return-table { min-width: 1160px; margin: 0; --bs-table-bg: var(--scm-surface) !important; --bs-table-color: var(--scm-text) !important; --bs-table-border-color: var(--scm-border) !important; }
        .scm-dashboard .kaur-loan-table { min-width: 1180px; }
        .scm-dashboard .kaur-loan-table thead th, .scm-dashboard .kaur-loan-return-table thead th { padding: 12px 16px; color: var(--scm-text) !important; background: var(--scm-surface-strong) !important; border-color: var(--scm-border) !important; font-family: inherit; font-size: .7rem; font-weight: 700 !important; letter-spacing: .035em; text-transform: uppercase; white-space: nowrap; vertical-align: middle; }
        .scm-dashboard .kaur-loan-table thead th *, .scm-dashboard .kaur-loan-return-table thead th * { color: inherit !important; font-weight: 700 !important; }
        .scm-dashboard .kaur-loan-table tbody td, .scm-dashboard .kaur-loan-return-table tbody td { padding: 12px 16px; color: var(--scm-text); background: var(--scm-surface); border-color: var(--scm-border); font-size: .76rem; line-height: 1.4; vertical-align: middle; }
        .scm-dashboard .kaur-loan-table thead th { padding: 14px 18px; }
        .scm-dashboard .kaur-loan-table tbody tr { min-height: 82px; }
        .scm-dashboard .kaur-loan-table tbody td { padding: 16px 18px; line-height: 1.5; }
        .scm-dashboard .kaur-loan-table tbody tr:hover > td, .scm-dashboard .kaur-loan-return-table tbody tr:hover > td { background: rgba(234, 91, 26, .04); }
        .scm-dashboard .kaur-loan-table th:nth-child(1), .scm-dashboard .kaur-loan-table td:nth-child(1) { width: 72px; min-width: 72px; }
        .scm-dashboard .kaur-loan-table th:nth-child(2), .scm-dashboard .kaur-loan-table td:nth-child(2) { min-width: 250px; }
        .scm-dashboard .kaur-loan-table th:nth-child(3), .scm-dashboard .kaur-loan-table td:nth-child(3) { min-width: 360px; }
        .scm-dashboard .kaur-loan-table th:nth-child(4), .scm-dashboard .kaur-loan-table td:nth-child(4) { min-width: 185px; white-space: nowrap; }
        .scm-dashboard .kaur-loan-table th:nth-child(5), .scm-dashboard .kaur-loan-table td:nth-child(5) { min-width: 220px; }
        .scm-dashboard .kaur-loan-table th:nth-child(6), .scm-dashboard .kaur-loan-table td:nth-child(6) { min-width: 140px; text-align: right; white-space: nowrap; }
        .kaur-loan-number, .kaur-loan-person { font-weight: 700; }
        .kaur-loan-meta { margin-top: 2px; color: var(--scm-muted); font-size: .68rem; }
        .kaur-loan-assets { display: grid; gap: 2px; }
        .kaur-loan-assets__summary { color: var(--scm-text); font-weight: 700; }
        .kaur-loan-assets__detail { color: var(--scm-muted); font-size: .7rem; white-space: normal; }
        .kaur-loan-asset { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; max-width: 300px; padding-bottom: 4px; border-bottom: 1px solid var(--scm-border); }
        .kaur-loan-asset:last-child { padding-bottom: 0; border-bottom: 0; }
        .kaur-loan-asset__name { min-width: 0; overflow: hidden; color: var(--scm-text); font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .kaur-loan-asset__qty { flex: 0 0 auto; color: var(--scm-muted); font-size: .68rem; white-space: nowrap; }
        .kaur-loan-labs { display: grid; gap: 3px; color: var(--scm-text); }
        .kaur-loan-lab { display: block; max-width: 210px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .kaur-loan-dates { display: grid; gap: 2px; }
        .kaur-loan-dates__start { color: var(--scm-text); font-weight: 600; }
        .kaur-loan-dates__end { color: var(--scm-muted); font-size: .68rem; }
        .kaur-loan-status-badge { display: inline-flex; align-items: center; gap: 7px; max-width: 205px; padding: 6px 9px; border: 1px solid var(--scm-border); border-radius: 999px; color: var(--scm-muted); background: var(--scm-surface-strong); font-size: .68rem; font-weight: 600; line-height: 1.3; }
        .kaur-loan-status-badge::before { width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: #9aa3af; content: ''; }
        .kaur-loan-status-badge.is-completed { color: #146c43; border-color: #a3cfbb; background: #eaf7f0; }
        .kaur-loan-status-badge.is-completed::before { background: #2f9e68; }
        .kaur-loan-status-badge.is-current { color: #8a5800; border-color: #f2cf85; background: #fff8e8; }
        .kaur-loan-status-badge.is-current::before { background: #ea5b1a; }
        .kaur-loan-status-badge.is-pending { color: #5f6368; border-color: #dfe3e7; background: #f8f9fa; }
        .kaur-loan-status-badge.is-rejected { color: #b02a37; border-color: #f1aeb5; background: #fff4f5; }
        .kaur-loan-status-badge.is-rejected::before { background: #dc4c5a; }
        .kaur-loan-approval-summary { display: grid; gap: 5px; width: 100%; min-width: 0; text-align: left; }
        .kaur-loan-approval-status { display: inline-flex; align-items: center; min-width: 0; color: var(--scm-text); font-size: .72rem; font-weight: 600; line-height: 1.25; white-space: normal; }
        .kaur-loan-approval-status::before { width: 7px; height: 7px; flex: 0 0 7px; margin-right: 7px; border-radius: 50%; background: #ea5b1a; content: ''; }
        .kaur-loan-approval-detail { display: inline-flex; align-items: center; gap: 4px; width: fit-content; padding: 0; border: 0; color: var(--scm-muted); background: transparent; font-size: .68rem; font-weight: 600; text-decoration: none; }
        .kaur-loan-approval-detail:hover { color: var(--scm-orange); text-decoration: underline; }
        .kaur-loan-approval-detail i { transition: transform .16s ease; }
        .kaur-loan-approval-detail:hover i { transform: translateX(2px); }
        .kaur-loan-manage-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 108px; min-height: 36px; border-radius: 999px !important; font-size: .72rem; font-weight: 600; }
        .kaur-approval-timeline { position: relative; display: grid; gap: 0; margin: 2px 0 18px; }
        .kaur-approval-timeline::before { position: absolute; top: 15px; bottom: 15px; left: 14px; width: 1px; background: var(--scm-border); content: ''; }
        .kaur-approval-timeline__item { position: relative; display: grid; grid-template-columns: 30px minmax(0, 1fr); align-items: center; min-height: 40px; }
        .kaur-approval-timeline__marker { position: relative; z-index: 1; display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; border: 1px solid var(--scm-border); border-radius: 50%; color: var(--scm-muted); background: var(--scm-surface-strong); font-size: .72rem; }
        .kaur-approval-timeline__label { padding-left: 10px; color: var(--scm-muted); font-size: .78rem; font-weight: 600; }
        .kaur-approval-timeline__item.is-complete .kaur-approval-timeline__marker { color: #177245; border-color: #bfe5cd; background: #ecf8f1; }
        .kaur-approval-timeline__item.is-complete .kaur-approval-timeline__label { color: #177245; }
        .kaur-approval-timeline__item.is-current .kaur-approval-timeline__marker { color: #a94714; border-color: #f1b28d; background: #fff4ec; }
        .kaur-approval-timeline__item.is-current .kaur-approval-timeline__label { color: #a94714; }
        .kaur-approval-timeline__item.is-rejected .kaur-approval-timeline__marker { color: #b4232f; border-color: #f1c4c8; background: #fff1f2; }
        .kaur-approval-timeline__item.is-rejected .kaur-approval-timeline__label { color: #b4232f; }
        .kaur-approval-detail-modal .modal-dialog { max-width: 440px; }
        .kaur-approval-detail-modal .modal-content { overflow: hidden; border: 1px solid var(--scm-border); border-radius: 14px; background: var(--scm-surface); box-shadow: 0 18px 50px rgba(31, 41, 55, .14); }
        .kaur-approval-detail-modal .modal-header { padding: 16px 18px 12px; border-bottom: 1px solid var(--scm-border); }
        .kaur-approval-detail-modal .modal-title { color: var(--scm-text); font-size: 1rem; }
        .kaur-approval-detail-modal .modal-body { padding: 16px 18px 18px; }
        .kaur-approval-detail-modal__status { padding-top: 14px; border-top: 1px solid var(--scm-border); }
        .kaur-approval-status-badge { display: inline-flex; max-width: 100%; align-items: center; padding: 6px 10px; border-radius: 999px; font-size: .7rem; font-weight: 700; line-height: 1.25; white-space: normal; }
        .kaur-approval-status-badge.is-completed { color: #177245; background: #e8f5ee; }
        .kaur-approval-status-badge.is-current { color: #a94714; background: #fff0e6; }
        .kaur-approval-status-badge.is-pending { color: var(--scm-muted); background: var(--scm-surface-strong); }
        .kaur-approval-status-badge.is-rejected { color: #b4232f; background: #fff1f2; }
        .kaur-approval-detail-modal__evidence { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--scm-border); }
        .kaur-approval-detail-modal__evidence-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .kaur-approval-evidence-btn { display: inline-flex; min-height: 36px; align-items: center; justify-content: center; gap: 6px; padding: 7px 12px; border-radius: 999px; font-size: .68rem; font-weight: 600; }
        .kaur-approval-detail-modal__evidence-empty { color: var(--scm-muted); font-size: .72rem; }
        html.scm-theme-dark .kaur-approval-timeline__item.is-complete .kaur-approval-timeline__marker { color: #8ed9ae; border-color: rgba(67, 209, 122, .35); background: rgba(67, 209, 122, .1); }
        html.scm-theme-dark .kaur-approval-timeline__item.is-complete .kaur-approval-timeline__label { color: #8ed9ae; }
        html.scm-theme-dark .kaur-approval-timeline__item.is-current .kaur-approval-timeline__marker { color: #ffc078; border-color: rgba(255, 160, 70, .38); background: rgba(234, 91, 26, .12); }
        html.scm-theme-dark .kaur-approval-timeline__item.is-current .kaur-approval-timeline__label { color: #ffc078; }
        html.scm-theme-dark .kaur-approval-status-badge.is-completed { color: #8ed9ae; background: rgba(67, 209, 122, .1); }
        html.scm-theme-dark .kaur-approval-status-badge.is-current { color: #ffc078; background: rgba(234, 91, 26, .12); }
        html.scm-theme-dark .kaur-approval-status-badge.is-rejected { color: #ff9aa3; background: rgba(255, 92, 99, .1); }
        .kaur-loan-detail-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 92px; min-height: 34px; border-radius: 999px !important; font-size: .7rem; font-weight: 600; transition: transform .16s ease, background-color .16s ease; }
        .kaur-loan-detail-btn:hover { transform: translateY(-1px); }
        .kaur-loan-return-heading { padding: 18px 20px 14px; border-bottom: 1px solid var(--scm-border); }
        .kaur-return-toolbar { display: grid; grid-template-columns: minmax(280px, 1fr) auto; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--scm-border); background: var(--scm-surface-strong); }
        .kaur-return-search .input-group-text, .kaur-return-search .form-control { min-height: 42px; border-color: var(--scm-border); font-size: .76rem; }
        .kaur-return-search .input-group-text { color: var(--scm-muted); background: var(--scm-surface); }
        .kaur-return-toolbar-actions { display: flex; align-items: center; gap: 8px; }
        .kaur-return-toolbar-actions .btn { display: inline-flex; min-width: 98px; min-height: 42px; align-items: center; justify-content: center; font-size: .72rem; font-weight: 600; }
        .kaur-return-search-note { grid-column: 1 / -1; margin-top: -3px; color: var(--scm-muted); font-size: .68rem; }
        .kaur-return-sort-button { display: inline-flex; align-items: center; gap: 4px; padding: 0; border: 0; color: inherit; background: transparent; font: inherit; font-weight: 700; letter-spacing: inherit; text-transform: inherit; white-space: nowrap; }
        .kaur-return-sort-button:hover, .kaur-return-sort-button:focus-visible, .kaur-return-sort-button.is-active { color: var(--scm-orange); }
        .kaur-return-sort-button:focus-visible { outline: 2px solid rgba(234, 91, 26, .28); outline-offset: 3px; border-radius: 3px; }
        .kaur-return-sort-button i { font-size: .7rem; }
        .kaur-loan-return-table th:nth-child(1), .kaur-loan-return-table td:nth-child(1) { width: 58px; min-width: 58px; text-align: center; white-space: nowrap; }
        .kaur-loan-return-table th:nth-child(2), .kaur-loan-return-table td:nth-child(2) { min-width: 200px; }
        .kaur-loan-return-table th:nth-child(3), .kaur-loan-return-table td:nth-child(3) { min-width: 300px; }
        .kaur-loan-return-table th:nth-child(4), .kaur-loan-return-table td:nth-child(4) { min-width: 210px; }
        .kaur-loan-return-table th:nth-child(5), .kaur-loan-return-table td:nth-child(5) { min-width: 190px; }
        .kaur-return-pagination-footer { display: grid; grid-template-columns: minmax(0, auto) 1fr minmax(0, auto); align-items: center; gap: 1rem; min-height: 64px; padding: .75rem 1rem; border-top: 1px solid var(--scm-border); color: var(--scm-muted); background: var(--scm-surface-strong); }
        .kaur-return-pagination-summary { display: flex; align-items: center; flex-wrap: wrap; gap: .55rem; font-size: .72rem; white-space: nowrap; }
        .kaur-return-pagination-summary .form-select { width: 92px; min-height: 34px; padding-top: .3rem; padding-bottom: .3rem; border-color: var(--scm-border); font-size: .72rem; }
        .kaur-return-pagination-status { font-size: .72rem; text-align: center; white-space: nowrap; }
        .kaur-return-pagination { margin: 0; }
        .scm-dashboard .kaur-return-pagination .page-link { display: inline-flex; min-width: 34px; min-height: 34px; align-items: center; justify-content: center; padding: .35rem .58rem; border-color: var(--scm-border) !important; color: var(--scm-text) !important; background: var(--scm-surface) !important; font-size: .72rem; line-height: 1; }
        .scm-dashboard .kaur-return-pagination .page-link:hover { color: var(--scm-orange) !important; background: var(--scm-surface-strong) !important; }
        .scm-dashboard .kaur-return-pagination .page-item.active .page-link { color: #fff !important; background: var(--scm-orange) !important; border-color: var(--scm-orange) !important; }
        .scm-dashboard .kaur-return-pagination .page-item.disabled .page-link { color: var(--scm-muted) !important; background: var(--scm-surface-strong) !important; opacity: .62; }
        html.scm-theme-light .scm-dashboard .kaur-return-pagination .page-link,
        html.scm-theme-light .scm-dashboard .kaur-return-pagination .page-item.disabled .page-link { color: #1f2937 !important; background: #fff !important; border-color: #e1e5e9 !important; opacity: 1; }
        html.scm-theme-light .scm-dashboard .kaur-return-pagination .page-item.disabled .page-link { color: #9aa0a6 !important; }
        html.scm-theme-light .scm-dashboard .kaur-return-pagination .page-link:hover { color: #ea5b1a !important; background: #fff7f2 !important; }
        html.scm-theme-light .scm-dashboard .kaur-return-pagination .page-item.active .page-link { color: #fff !important; background: #ff7900 !important; border-color: #ff7900 !important; }
        .kaur-loan-modal .modal-dialog { max-width: 780px; }
        .kaur-loan-modal .modal-content { overflow: hidden; border: 1px solid var(--scm-border); border-radius: 14px; background: var(--scm-surface); box-shadow: 0 18px 50px rgba(31, 41, 55, .16); }
        .kaur-loan-modal .modal-header { align-items: flex-start; padding: 18px 20px 16px; border-bottom: 1px solid var(--scm-border); background: var(--scm-surface); }
        .kaur-loan-modal__eyebrow, .kaur-loan-modal__section-title { color: var(--scm-muted); font-size: .66rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .kaur-loan-modal .modal-title { margin-top: 3px; color: var(--scm-text); font-size: 1.05rem; }
        .kaur-loan-modal__metadata { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 12px; }
        .kaur-loan-modal__metadata-item { min-width: 0; }
        .kaur-loan-modal__metadata-label { color: var(--scm-muted); font-size: .64rem; }
        .kaur-loan-modal__metadata-value { margin-top: 2px; overflow-wrap: anywhere; color: var(--scm-text); font-size: .74rem; font-weight: 600; }
        .kaur-loan-modal .btn-close { flex: 0 0 auto; margin: 0 0 0 12px; padding: 9px; border: 1px solid var(--scm-border); border-radius: 50%; background-size: 10px; }
        .kaur-loan-modal .modal-body { padding: 18px 20px 20px; color: var(--scm-text); }
        .kaur-loan-modal__section + .kaur-loan-modal__section { margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--scm-border); }
        .kaur-loan-modal__section-title { margin-bottom: 8px; }
        .kaur-loan-modal__need { color: var(--scm-text); font-size: .78rem; line-height: 1.55; }
        .kaur-loan-modal__items-wrap { border: 1px solid var(--scm-border); border-radius: 10px; overflow-x: auto; }
        .kaur-loan-modal__items { min-width: 620px; margin: 0; }
        .kaur-loan-modal__items th { padding: 10px 12px; color: var(--scm-text) !important; background: var(--scm-surface-strong) !important; border-color: var(--scm-border); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .035em; }
        .kaur-loan-modal__items td { padding: 10px 12px; border-color: var(--scm-border); font-size: .74rem; vertical-align: middle; }
        .kaur-loan-modal textarea { min-height: 96px; border-color: var(--scm-border); border-radius: 9px; font-size: .76rem; resize: vertical; }
        .kaur-loan-modal .modal-footer { gap: 8px; padding: 14px 20px; border-top: 1px solid var(--scm-border); background: var(--scm-surface-strong); }
        .kaur-loan-modal .modal-footer .btn { min-height: 38px; font-size: .72rem; font-weight: 600; }
        html.scm-theme-dark .kaur-loan-status-badge.is-completed { color: #8ed9ae; border-color: rgba(67, 209, 122, .35); background: rgba(67, 209, 122, .1); }
        html.scm-theme-dark .kaur-loan-status-badge.is-current, html.scm-theme-dark .kaur-loan-count { color: #ffc078; border-color: rgba(255, 160, 70, .38); background: rgba(234, 91, 26, .12); }
        html.scm-theme-dark .kaur-loan-status-badge.is-pending { color: #c0c6cc; border-color: #41474c; background: #202326; }
        html.scm-theme-dark .kaur-loan-status-badge.is-rejected { color: #ff9aa3; border-color: rgba(255, 92, 99, .38); background: rgba(255, 92, 99, .1); }
        @media (max-width: 1199.98px) {
            .kaur-loan-filter-grid { grid-template-columns: minmax(260px, 1.5fr) repeat(2, minmax(160px, .75fr)); }
            .kaur-loan-filter-actions { grid-column: 1 / -1; margin-top: 0; }
        }
        @media (max-width: 991.98px) {
            .scm-dashboard.kaur-loan-page .dashboard-sidebar { width: 100%; }
            .scm-dashboard.kaur-loan-page .dashboard-content { margin-left: 0; }
            .kaur-loan-filter-grid { grid-template-columns: 1fr 1fr; }
            .kaur-loan-filter-keyword, .kaur-loan-filter-actions { grid-column: 1 / -1; }
        }
        @media (max-width: 767.98px) {
            .kaur-loan-page-heading { align-items: flex-start; }
            .kaur-loan-heading { align-items: flex-start; flex-direction: column; padding: 16px; }
            .kaur-loan-heading__actions { width: 100%; justify-content: flex-start; }
            .kaur-loan-filter-card { padding: 16px; }
            .kaur-loan-filter-grid { grid-template-columns: 1fr; }
            .kaur-loan-filter-keyword, .kaur-loan-filter-actions { grid-column: auto; }
            .kaur-loan-return-heading { padding: 16px; }
            .kaur-return-toolbar { grid-template-columns: 1fr; padding: 14px 16px; }
            .kaur-return-toolbar-actions { width: 100%; }
            .kaur-return-toolbar-actions .btn { flex: 1 1 auto; }
            .kaur-return-search-note { grid-column: auto; }
            .kaur-loan-modal .modal-dialog { margin: .75rem; }
            .kaur-loan-modal__metadata { grid-template-columns: 1fr; gap: 8px; }
            .kaur-loan-modal .modal-header, .kaur-loan-modal .modal-body, .kaur-loan-modal .modal-footer { padding-left: 16px; padding-right: 16px; }
            .kaur-loan-modal .modal-footer { justify-content: stretch; }
            .kaur-loan-modal .modal-footer .btn { flex: 1 1 auto; }
            .kaur-return-pagination-footer { grid-template-columns: 1fr; justify-items: center; gap: .65rem; }
            .kaur-return-pagination-footer nav { max-width: 100%; overflow-x: auto; padding-bottom: 2px; }
        }
    </style>
    <link rel="stylesheet" href="<?= base_url('assets/dashboard-theme.css') ?>">
    <?php include APPPATH . 'views/shared/theme_assets.php'; ?>
</head>
<body class="scm-dashboard scm-dashboard-kaur<?= $active_module === 'peminjaman' ? ' kaur-loan-page' : '' ?>">
    <aside class="dashboard-sidebar" aria-label="Navigasi Panel Kaur">
        <a class="sidebar-brand" href="<?= kaur_module_url('overview') ?>">
            <span class="sidebar-brand-mark"><i class="bi bi-diagram-3"></i></span>
            <span><strong>SCM FIK</strong><small>Panel Kaur Laboratorium</small></span>
        </a>
        <div class="sidebar-caption">Pengadaan &amp; aset</div>
        <nav class="sidebar-nav">
            <?php foreach (['overview' => ['Panel', 'bi-grid-1x2-fill'], 'pengajuan' => ['Pengajuan', 'bi-inboxes'], 'negosiasi' => ['Negosiasi', 'bi-chat-square-text'], 'approval' => ['Approval', 'bi-patch-check'], 'peminjaman' => ['ACC Peminjaman', 'bi-qr-code-scan'], 'anggaran' => ['Alokasi Anggaran', 'bi-cash-coin'], 'bast' => ['BAST', 'bi-file-earmark-pdf'], 'laporan' => ['Laporan', 'bi-file-earmark-spreadsheet']] as $key => $item): ?>
                <a class="sidebar-link <?= $active_module === $key ? 'active' : '' ?>" href="<?= kaur_module_url($key) ?>"><i class="bi <?= $item[1] ?>"></i><span><?= html_escape($item[0]) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer"><span class="sidebar-status-dot"></span><span>System operational</span></div>
    </aside>
    <div class="dashboard-content">
    <header class="topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4 py-3">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="dashboard-topbar-brand d-flex align-items-center gap-3">
                    <span class="brand-mark"><i class="bi bi-diagram-3"></i></span>
                    <div>
                        <div class="fw-bold">Panel Kaur Laboratorium</div>
                        <div class="small text-white-50">Pengajuan, negosiasi, approval, BAST, dan laporan</div>
                    </div>
                </div>
                <div class="topbar-actions d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm rounded-circle notif-bell position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <?php if ($notif_count > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notif_count ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2 notif-menu">
                            <div class="fw-bold px-2 py-1">Notifikasi</div>
                            <?php if (empty($notif_items)): ?>
                                <div class="small text-muted px-2 py-3">Belum ada notifikasi.</div>
                            <?php else: foreach ($notif_items as $n): ?>
                                <a class="dropdown-item rounded-3 py-2" href="<?= site_url('dashboard/notifikasi/' . (int) $n->id_notifikasi) ?>">
                                    <div class="fw-semibold small"><?= html_escape($n->judul) ?></div>
                                    <div class="small text-muted text-wrap"><?= html_escape($n->pesan) ?></div>
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

    <div class="module-strip">
        <div class="container-fluid px-3 px-lg-4 py-2">
            <nav class="nav gap-2" aria-label="Navigasi fitur Kaur">
                <?php foreach (['overview' => 'Panel', 'pengajuan' => 'Pengajuan', 'negosiasi' => 'Negosiasi', 'approval' => 'Approval', 'peminjaman' => 'ACC Peminjaman', 'anggaran' => 'Alokasi Anggaran', 'bast' => 'BAST', 'laporan' => 'Laporan'] as $key => $label): ?>
                    <a class="nav-link <?= $active_module === $key ? 'active' : '' ?>" href="<?= kaur_module_url($key) ?>"><?= html_escape($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <?php if($this->session->flashdata('success')): ?>
            <div class="alert alert-success rounded-3"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger rounded-3"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4 kaur-page-header<?= $active_module === 'peminjaman' ? ' kaur-loan-page-heading' : '' ?>">
            <div>
                <h1 class="h3 fw-bold mb-1"><?= html_escape($module['title']) ?></h1>
                <p class="text-muted mb-0"><?= html_escape($module['desc']) ?></p>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <?php if (!$is_overview): ?>
                    <a href="<?= kaur_module_url('overview') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-grid me-1"></i> Panel Kaur</a>
                <?php endif; ?>
                <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?></div>
            </div>
        </div>

        <?php if ($is_overview): ?>
        <?php
            $stat_cards = [
                ['label' => 'Total Pengajuan', 'value' => (int) ($stats['total_pengajuan'] ?? $stats['pengajuan']), 'icon' => 'bi-inboxes', 'class' => 'kaur-stat-blue', 'format' => 'number'],
                ['label' => 'Sedang Negosiasi', 'value' => (int) ($stats['sedang_negosiasi'] ?? $stats['negosiasi']), 'icon' => 'bi-chat-square-text', 'class' => 'kaur-stat-amber', 'format' => 'number'],
                ['label' => 'Deal / Approval', 'value' => (int) ($stats['deal_approval'] ?? $stats['deal']), 'icon' => 'bi-patch-check', 'class' => 'kaur-stat-green', 'format' => 'number'],
                ['label' => 'Menunggu Approval', 'value' => (int) ($stats['menunggu_approval'] ?? 0), 'icon' => 'bi-hourglass-split', 'class' => 'kaur-stat-red', 'format' => 'number'],
                ['label' => 'Total Anggaran Tahunan', 'value' => (float) ($anggaran['total_anggaran'] ?? 0), 'icon' => 'bi-wallet2', 'class' => 'kaur-stat-orange', 'format' => 'currency'],
                ['label' => 'Anggaran Terpakai', 'value' => (float) ($anggaran['total_pengeluaran'] ?? 0), 'icon' => 'bi-graph-up-arrow', 'class' => 'kaur-stat-cyan', 'format' => 'currency'],
                ['label' => 'Sisa Anggaran', 'value' => (float) ($anggaran['sisa_anggaran'] ?? 0), 'icon' => 'bi-piggy-bank', 'class' => 'kaur-stat-purple', 'format' => 'currency'],
                ['label' => 'Total Dokumen BAST', 'value' => (int) ($stats['total_bast'] ?? $stats['bast']), 'icon' => 'bi-file-earmark-pdf', 'class' => 'kaur-stat-slate', 'format' => 'number'],
            ];
            $quick_actions = [
                ['id' => 'pengajuan', 'icon' => 'bi-inboxes', 'label' => 'Pengajuan'],
                ['id' => 'negosiasi', 'icon' => 'bi-chat-square-text', 'label' => 'Negosiasi'],
                ['id' => 'approval', 'icon' => 'bi-patch-check', 'label' => 'Approval'],
                ['id' => 'peminjaman', 'icon' => 'bi-qr-code-scan', 'label' => 'ACC Peminjaman'],
                ['id' => 'anggaran', 'icon' => 'bi-cash-coin', 'label' => 'Alokasi Anggaran'],
                ['id' => 'bast', 'icon' => 'bi-file-earmark-pdf', 'label' => 'Input BAST'],
                ['id' => 'laporan', 'icon' => 'bi-file-earmark-spreadsheet', 'label' => 'Laporan'],
            ];
        ?>
        <section class="kaur-overview">
            <div class="kaur-stat-grid">
                <?php foreach ($stat_cards as $card): ?>
                    <article class="kaur-stat-card <?= $card['class'] ?>">
                        <div class="kaur-stat-top">
                            <span class="kaur-stat-icon"><i class="bi <?= $card['icon'] ?>"></i></span>
                            <i class="bi bi-three-dots text-white-50" aria-hidden="true"></i>
                        </div>
                        <div class="kaur-stat-label"><?= html_escape($card['label']) ?></div>
                        <div class="kaur-stat-value" data-counter="<?= (float) $card['value'] ?>" data-counter-format="<?= html_escape($card['format']) ?>">
                            <?= $card['format'] === 'currency' ? rp_kaur($card['value']) : number_format((int) $card['value'], 0, ',', '.') ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="kaur-chart-grid">
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Pengajuan per Bulan</h3><p>Jumlah pengajuan yang masuk sepanjang <?= $dashboard_year ?>.</p></div>
                        <span class="kaur-chart-note">Jan - Des</span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="submissionChart" aria-label="Grafik pengajuan per bulan"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Status Pengajuan</h3><p>Distribusi status tahun <?= $dashboard_year ?>.</p></div>
                        <span class="kaur-chart-note">Overview</span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="statusChart" aria-label="Grafik status pengajuan"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
            </div>

            <div class="kaur-chart-grid">
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Penggunaan Anggaran</h3><p>Pagu dan realisasi berdasarkan hasil Deal.</p></div>
                        <span class="kaur-chart-note"><?= html_escape((string) $dashboard_year) ?></span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="budgetChart" aria-label="Grafik penggunaan anggaran"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
                <section class="kaur-chart-panel">
                    <div class="kaur-chart-header">
                        <div><h3>Perbandingan Negosiasi</h3><p>Harga awal dibandingkan harga setelah negosiasi.</p></div>
                        <span class="kaur-savings">Hemat <?= rp_kaur($dashboard_negotiation['penghematan'] ?? 0) ?></span>
                    </div>
                    <div class="kaur-chart-wrap"><canvas id="negotiationChart" aria-label="Grafik perbandingan harga negosiasi"></canvas><div class="kaur-chart-fallback">Grafik belum dapat dimuat pada browser ini.</div></div>
                </section>
            </div>

            <div class="kaur-bottom-grid">
                <section class="kaur-activity-panel">
                    <div class="kaur-panel-header">
                        <div><h3>Recent Activity</h3><p>Aktivitas terbaru yang berkaitan dengan panel Kaur.</p></div>
                        <i class="bi bi-activity text-warning" aria-hidden="true"></i>
                    </div>
                    <div class="kaur-activity-list">
                        <?php if (empty($dashboard_activity)): ?>
                            <div class="text-muted small py-4 text-center">Belum ada aktivitas terbaru.</div>
                        <?php else: foreach ($dashboard_activity as $activity): ?>
                            <?php $activity_time = !empty($activity['time']) ? date('d M Y, H:i', strtotime($activity['time'])) : '-'; ?>
                            <div class="kaur-activity-item">
                                <span class="kaur-activity-icon"><i class="bi <?= html_escape($activity['icon'] ?? 'bi-activity') ?>"></i></span>
                                <div class="kaur-activity-copy">
                                    <div class="kaur-activity-title"><?= html_escape($activity['title'] ?? 'Aktivitas') ?></div>
                                    <div class="kaur-activity-description"><?= html_escape($activity['description'] ?? '-') ?></div>
                                    <div class="kaur-activity-meta"><span><i class="bi bi-clock me-1"></i><?= html_escape($activity_time) ?></span><span class="status-pill <?= status_class_kaur($activity['status'] ?? '') ?>"><?= html_escape($activity['status'] ?? '-') ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </section>

                <section class="kaur-quick-panel">
                    <div class="kaur-panel-header">
                        <div><h3>Quick Action</h3><p>Akses cepat tanpa menggantikan menu sidebar.</p></div>
                        <i class="bi bi-lightning-charge text-warning" aria-hidden="true"></i>
                    </div>
                    <div class="kaur-quick-grid">
                        <?php foreach ($quick_actions as $quick): ?>
                            <a class="kaur-quick-action" href="<?= kaur_module_url($quick['id']) ?>"><i class="bi <?= $quick['icon'] ?>"></i><span><?= html_escape($quick['label']) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'peminjaman'): ?>
        <section id="approval-peminjaman" class="section-anchor mb-4">
            <div class="panel-card kaur-loan-summary mb-3">
                <div class="kaur-loan-heading">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Approval Peminjaman oleh Kaur</h2>
                        <div class="text-muted small">Pengajuan tampil setelah ACC Kaprodi dan pengecekan Laboran. Setelah ACC Kaur, Laboran memfinalkan QR.</div>
                    </div>
                    <div class="kaur-loan-heading__actions">
                        <a href="<?= base_url('index.php/kaur/peminjaman/export_pengajuan_acc') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Preview Excel ACC</a>
                        <span class="kaur-loan-count"><?= count($peminjaman_pending_kaur ?? []) ?> menunggu ACC</span>
                    </div>
                </div>
            </div>
            <div class="mb-3"><?php render_kaur_multi_filter('peminjaman', $filter_rows ?? [], [
                'sort_by' => $filters['sort_by'] ?? '',
                'sort_dir' => $filters['sort_dir'] ?? '',
            ]); ?></div>
            <div class="panel-card kaur-loan-table-card">
            <div class="table-responsive kaur-loan-table-wrap">
                <table class="table table-hover table-clean kaur-loan-table align-middle">
                    <thead><tr>
                        <th>No</th>
                        <th><a href="<?= sort_url_kaur('peminjaman', $filters, 'nama_peminjam') ?>" class="text-decoration-none text-dark">Peminjam <?= sort_icon_kaur($filters, 'nama_peminjam') ?></a></th>
                        <th>Barang</th>
                        <th><a href="<?= sort_url_kaur('peminjaman', $filters, 'tanggal_pinjam') ?>" class="text-decoration-none text-dark">Masa Pinjam <?= sort_icon_kaur($filters, 'tanggal_pinjam') ?></a></th>
                        <th><a href="<?= sort_url_kaur('peminjaman', $filters, 'status') ?>" class="text-decoration-none text-dark">Alur Approval <?= sort_icon_kaur($filters, 'status') ?></a></th>
                        <th class="text-end">Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php if(empty($peminjaman_pending_kaur)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada peminjaman yang menunggu ACC Kaur.</td></tr>
                    <?php else: foreach($peminjaman_pending_kaur as $index => $p): ?>
                        <?php
                            $barang_names = [];
                            $labs = [];
                            foreach (($p->detail_barang ?? []) as $d) {
                                $barang_names[] = $d->nama_aset ?? '-';
                                if (!empty($d->nama_ruangan)) { $labs[] = $d->nama_ruangan; }
                            }
                        ?>
                        <?php
                            $loan_search_text = strtolower(implode(' ', [$p->group_id ?? '', $p->nama_peminjam ?? '', $p->nim_nip ?? '', implode(' ', $barang_names), implode(' ', $labs), $p->status ?? '', $p->keperluan ?? '', tanggal_indonesia($p->tanggal_pinjam ?? null), tanggal_indonesia($p->tanggal_kembali_rencana ?? null)]));
                            $loan_filter_peminjam = strtolower(implode(' ', [$p->nama_peminjam ?? '', $p->nim_nip ?? '']));
                            $loan_filter_barang = strtolower(implode(' ', [$p->group_id ?? '', $p->id_peminjaman ?? '', implode(' ', $barang_names)]));
                            $loan_filter_status = strtolower((string) ($p->status ?? ''));
                            $loan_filter_tanggal = strtolower(implode(' ', [tanggal_indonesia($p->tanggal_pinjam ?? null), tanggal_indonesia($p->tanggal_kembali_rencana ?? null)]));
                            $loan_filter_keperluan = strtolower((string) ($p->keperluan ?? ''));
                        ?>
                        <tr data-kaur-loan-row data-search="<?= html_escape($loan_search_text) ?>" data-filter-peminjam="<?= html_escape($loan_filter_peminjam) ?>" data-filter-barang="<?= html_escape($loan_filter_barang) ?>" data-filter-status="<?= html_escape($loan_filter_status) ?>" data-filter-tanggal="<?= html_escape($loan_filter_tanggal) ?>" data-filter-keperluan="<?= html_escape($loan_filter_keperluan) ?>">
                            <td class="fw-semibold text-muted"><?= $index + 1 ?></td>
                            <td><div class="kaur-loan-person"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="kaur-loan-meta"><?= html_escape($p->nim_nip ?? '-') ?></div></td>
                            <td><div class="kaur-loan-assets"><div class="kaur-loan-assets__summary"><?= (int)($p->total_jenis ?? count($p->detail_barang ?? [])) ?> jenis / <?= (int)($p->total_jumlah ?? 0) ?> unit</div><div class="kaur-loan-assets__detail"><?php if (!empty($p->detail_barang)): foreach (($p->detail_barang ?? []) as $d): ?><?= html_escape($d->nama_aset ?? '-') ?> (<?= (int)($d->jumlah_pinjam ?? 0) ?>), <?php endforeach; else: ?>-<?php endif; ?></div></div></td>
                            <td><span class="kaur-loan-dates" tabindex="0" data-bs-toggle="tooltip" title="<?= html_escape(masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null)) ?>"><span class="kaur-loan-dates__start"><?= tanggal_indonesia($p->tanggal_pinjam ?? null) ?></span><span class="kaur-loan-dates__end">s.d. <?= tanggal_indonesia($p->tanggal_kembali_rencana ?? null) ?></span></span></td>
                            <?php $kaur_status = trim((string) ($p->status ?? '')); $kaur_status_label = $kaur_status !== '' ? $kaur_status : 'Status belum tersedia'; ?>
                            <td><div class="kaur-loan-approval-summary"><span class="kaur-loan-approval-status"><?= html_escape($kaur_status_label) ?></span><button type="button" class="kaur-loan-approval-detail" data-bs-toggle="modal" data-bs-target="#kaurApprovalTimelineModal<?= (int)$p->id_peminjaman ?>">Lihat Detail <i class="bi bi-arrow-right" aria-hidden="true"></i></button></div></td>
                            <td class="text-end"><div class="dropdown"><button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle kaur-loan-manage-btn" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots me-1"></i>Kelola</button><ul class="dropdown-menu dropdown-menu-end"><li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#loanApprovalModal<?= (int)$p->id_peminjaman ?>"><i class="bi bi-patch-check me-2"></i>Proses ACC</button></li></ul></div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                        <?php if(!empty($peminjaman_pending_kaur)): ?><tr id="kaurLoanEmptySearch" class="d-none"><td colspan="6" class="text-center text-muted py-4">Tidak ada hasil yang cocok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </section>
        <?php foreach(($peminjaman_pending_kaur ?? []) as $p): ?>
            <?php
                $kaur_approval = loan_approval_states_kaur($p);
                $kaur_approval_steps = ['diajukan' => 'Diajukan', 'kaprodi' => 'Kaprodi', 'laboran' => 'Laboran', 'kaur' => 'Kaur', 'qr' => 'Final QR', 'selesai' => 'Selesai'];
                $kaur_evidence_url = scm_upload_url($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman');
                $kaur_evidence_exists = scm_upload_exists($p->foto_bukti ?? '', 'assets/uploads/bukti_peminjaman');
            ?>
            <div class="modal fade kaur-approval-detail-modal" id="kaurApprovalTimelineModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-labelledby="kaurApprovalTimelineTitle<?= (int)$p->id_peminjaman ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <div class="small text-uppercase text-muted fw-semibold">Detail proses</div>
                                <h2 class="modal-title h5 fw-bold mb-0" id="kaurApprovalTimelineTitle<?= (int)$p->id_peminjaman ?>">Alur Approval</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="kaur-approval-timeline" aria-label="Timeline approval peminjaman">
                                <?php foreach ($kaur_approval_steps as $step_key => $step_label): ?>
                                    <?php $step_state = $kaur_approval[$step_key]; ?>
                                    <div class="kaur-approval-timeline__item <?= html_escape($step_state) ?>">
                                        <span class="kaur-approval-timeline__marker"><?php if ($step_state === 'is-complete'): ?><i class="bi bi-check2" aria-hidden="true"></i><?php elseif ($step_state === 'is-current'): ?><i class="bi bi-dot" aria-hidden="true"></i><?php else: ?><i class="bi bi-circle" aria-hidden="true"></i><?php endif; ?></span>
                                        <span class="kaur-approval-timeline__label"><?= html_escape($step_label) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="kaur-approval-detail-modal__status">
                                <div class="small text-muted mb-2">Status transaksi</div>
                                <span class="kaur-approval-status-badge <?= html_escape(loan_status_tone_kaur($kaur_approval['status'])) ?>"><?= html_escape($kaur_approval['status'] ?: '-') ?></span>
                            </div>
                            <div class="kaur-approval-detail-modal__evidence">
                                <div class="small text-muted">Bukti pendukung</div>
                                <div class="kaur-approval-detail-modal__evidence-actions">
                                    <?php if (!empty($p->foto_bukti)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary kaur-approval-evidence-btn" data-bs-toggle="modal" data-bs-target="#kaurLoanEvidence<?= (int)$p->id_peminjaman ?>"><i class="bi bi-image" aria-hidden="true"></i><span>Bukti kondisi</span></button>
                                    <?php else: ?>
                                        <span class="kaur-approval-detail-modal__evidence-empty">Bukti kondisi belum tersedia</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade kaur-loan-modal" id="loanApprovalModal<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>">
                        <div class="modal-header">
                            <div class="flex-grow-1">
                                <div class="kaur-loan-modal__eyebrow">Peminjaman</div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->group_id ?: $p->id_peminjaman) ?></h5>
                                <?php $modal_status = trim((string) ($p->status ?? '')); ?>
                                <div class="mt-2"><span class="kaur-loan-status-badge <?= html_escape(loan_status_tone_kaur($modal_status)) ?>"><?= html_escape($modal_status !== '' ? $modal_status : 'Status belum tersedia') ?></span></div>
                                <div class="kaur-loan-modal__metadata">
                                    <div class="kaur-loan-modal__metadata-item"><div class="kaur-loan-modal__metadata-label">Nama Peminjam</div><div class="kaur-loan-modal__metadata-value"><?= html_escape($p->nama_peminjam ?? '-') ?></div></div>
                                    <div class="kaur-loan-modal__metadata-item"><div class="kaur-loan-modal__metadata-label">User ID / NIM / NIP</div><div class="kaur-loan-modal__metadata-value"><?= html_escape($p->nim_nip ?? '-') ?></div></div>
                                    <div class="kaur-loan-modal__metadata-item"><div class="kaur-loan-modal__metadata-label">Periode</div><div class="kaur-loan-modal__metadata-value"><?= masa_pinjam_indonesia($p->tanggal_pinjam ?? null, $p->tanggal_kembali_rencana ?? null) ?></div></div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="kaur-loan-modal__section">
                                <div class="kaur-loan-modal__section-title">Keperluan</div>
                                <div class="kaur-loan-modal__need"><?= nl2br(html_escape($p->keperluan ?? '-')) ?></div>
                            </div>
                            <div class="kaur-loan-modal__section">
                                <div class="kaur-loan-modal__section-title">Detail Barang</div>
                                <div class="kaur-loan-modal__items-wrap">
                                    <table class="table table-sm kaur-loan-modal__items">
                                        <thead><tr><th>Barang</th><th>Kode</th><th>Laboratorium</th><th class="text-end">Jumlah</th></tr></thead>
                                        <tbody>
                                        <?php foreach(($p->detail_barang ?? []) as $d): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= html_escape($d->nama_aset ?? '-') ?></td>
                                                <td class="text-muted"><?= html_escape($d->kode_aset ?? '-') ?></td>
                                                <td><?= html_escape($d->nama_ruangan ?? '-') ?></td>
                                                <td class="text-end fw-semibold"><?= (int)($d->jumlah_pinjam ?? 0) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="kaur-loan-modal__section">
                                <label class="kaur-loan-modal__section-title d-block" for="kaurLoanNote<?= (int)$p->id_peminjaman ?>">Catatan ACC Kaur</label>
                                <textarea id="kaurLoanNote<?= (int)$p->id_peminjaman ?>" name="catatan_kaur" class="form-control" rows="3" placeholder="Catatan persetujuan atau alasan penolakan."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/tolak/'.$p->id_peminjaman) ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak peminjaman ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <button formaction="<?= base_url('index.php/kaur/peminjaman/setujui/'.$p->id_peminjaman) ?>" class="btn btn-success rounded-pill px-3" onclick="return confirm('Setujui peminjaman ini? QR akan menunggu finalisasi Laboran.')"><i class="bi bi-check2-circle me-1"></i> Setujui</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php if (!empty($p->foto_bukti)): ?>
                <div class="modal fade" id="kaurLoanEvidence<?= (int)$p->id_peminjaman ?>" tabindex="-1" aria-labelledby="kaurLoanEvidenceTitle<?= (int)$p->id_peminjaman ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header"><h2 class="modal-title h5 fw-bold" id="kaurLoanEvidenceTitle<?= (int)$p->id_peminjaman ?>">Bukti Kondisi Awal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                            <div class="modal-body text-center bg-light"><?php if ($kaur_evidence_exists): ?><img class="img-fluid rounded-3" style="max-height:70vh;object-fit:contain" src="<?= html_escape($kaur_evidence_url) ?>" alt="Bukti kondisi awal"><?php else: ?><div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File bukti kondisi tidak ditemukan di penyimpanan.</div><?php endif; ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <section class="panel-card kaur-loan-return-card mb-4">
            <div class="kaur-loan-return-heading"><h2 class="h5 fw-bold mb-1">Status Pengembalian (Read-only)</h2><div class="text-muted small">Pengembalian cukup dikonfirmasi Laboran. Kaur hanya memantau status tanpa tombol approve atau tolak.</div></div>
            <?php if(!empty($pengembalian_readonly)): ?>
            <div class="kaur-return-toolbar">
                <div class="kaur-return-search"><div class="input-group"><span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span><input id="kaurReturnSearch" type="search" class="form-control" placeholder="Cari peminjam, NIM/NIP, barang, masa pinjam, atau status" autocomplete="off" aria-label="Cari status pengembalian" aria-describedby="kaurReturnSearchNote"></div></div>
                <div class="kaur-return-toolbar-actions"><button id="kaurReturnReset" type="button" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button></div>
                <div id="kaurReturnSearchNote" class="kaur-return-search-note"><i class="bi bi-lightning-charge me-1"></i>Hasil diperbarui saat Anda mengetik. Klik judul kolom untuk mengurutkan.</div>
            </div>
            <?php endif; ?>
            <div class="table-responsive kaur-loan-return-table-wrap"><table class="table table-clean kaur-loan-return-table align-middle"><thead><tr>
                <th>No</th>
                <th aria-sort="none"><button type="button" class="kaur-return-sort-button" data-kaur-return-sort="peminjam">Peminjam <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                <th aria-sort="none"><button type="button" class="kaur-return-sort-button" data-kaur-return-sort="barang">Nama Barang <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                <th aria-sort="none"><button type="button" class="kaur-return-sort-button" data-kaur-return-sort="masa">Masa Pinjam <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
                <th aria-sort="none"><button type="button" class="kaur-return-sort-button" data-kaur-return-sort="status">Status Pengembalian <i class="bi bi-arrow-down-up" aria-hidden="true"></i></button></th>
            </tr></thead><tbody id="kaurReturnTableBody">
            <?php if(empty($pengembalian_readonly)): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi pengembalian.</td></tr><?php else: foreach($pengembalian_readonly as $return_index => $p): ?>
                <?php $return_names=[]; foreach(($p->detail_barang ?? []) as $d){$return_names[]=$d->nama_aset ?? '-';} $return_item_text=implode(', ',$return_names) ?: '-'; $return_period=masa_pinjam_indonesia($p->tanggal_pinjam ?? null,$p->tanggal_kembali_rencana ?? null); $return_search=strtolower(implode(' ',[$p->nama_peminjam ?? '',$p->nim_nip ?? '',$return_item_text,$return_period,$p->status ?? ''])); $return_date_sort=strtotime((string)($p->tanggal_pinjam ?? '')) ?: 0; ?>
                <tr data-kaur-return-row data-search="<?= html_escape($return_search) ?>" data-sort-peminjam="<?= html_escape(strtolower((string)($p->nama_peminjam ?? ''))) ?>" data-sort-barang="<?= html_escape(strtolower($return_item_text)) ?>" data-sort-masa="<?= (int)$return_date_sort ?>" data-sort-status="<?= html_escape(strtolower((string)($p->status ?? ''))) ?>"><td class="fw-semibold text-muted"><?= (int) $return_index + 1 ?></td><td><div class="kaur-loan-person"><?= html_escape($p->nama_peminjam ?? '-') ?></div><div class="kaur-loan-meta"><?= html_escape($p->nim_nip ?? '-') ?></div></td><td><?= html_escape($return_item_text) ?></td><td><span tabindex="0" data-bs-toggle="tooltip" title="<?= html_escape($return_period) ?>"><?= $return_period ?></span></td><td><span class="kaur-loan-status-badge <?= html_escape(loan_status_tone_kaur($p->status ?? '')) ?>"><?= html_escape($p->status ?? '-') ?></span></td></tr>
            <?php endforeach; endif; ?>
            <?php if(!empty($pengembalian_readonly)): ?><tr id="kaurReturnEmptySearch" hidden><td colspan="5" class="text-center text-muted py-4">Tidak ada status pengembalian yang cocok.</td></tr><?php endif; ?>
            </tbody></table></div>
            <?php if(!empty($pengembalian_readonly)): ?>
            <div class="kaur-return-pagination-footer">
                <div class="kaur-return-pagination-summary">
                    <label for="kaurReturnPageSize">Tampilkan:</label>
                    <select id="kaurReturnPageSize" class="form-select form-select-sm" aria-label="Jumlah status pengembalian per halaman"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option><option value="all">Semua</option></select>
                    <span>Total item: <span id="kaurReturnTotalItems"><?= count($pengembalian_readonly) ?></span></span>
                </div>
                <div class="kaur-return-pagination-status" id="kaurReturnPageStatus">Halaman: 1 dari 1</div>
                <nav aria-label="Pagination status pengembalian"><ul class="pagination pagination-sm kaur-return-pagination" id="kaurReturnPageNav"></ul></nav>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'pengajuan'): ?>
        <section id="pengajuan" class="section-anchor mb-4">
            <div class="panel-card p-3 p-lg-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Pengajuan Kaprodi</h2>
                    <div class="text-muted small">Data dapat dicari berdasarkan tanggal, jenis, status, dan kata kunci.</div>
                </div>
                <a href="<?= base_url('index.php/kaur/pengajuan/export_pengajuan_acc?' . query_kaur($filters, 1)) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 align-self-start"><i class="bi bi-file-earmark-excel me-1"></i> Export Pengajuan ACC</a>
            </div>
            <?php render_kaur_multi_filter('pengajuan', $filter_rows ?? [], ['per_page' => $per_page ?? '10']); ?>
            </div>

            <div class="panel-card kaur-submission-table-card">
            <div class="table-responsive">
                <table class="table table-clean align-middle mb-0 kaur-submission-table">
                    <thead><tr><th>No</th><th>Kode</th><th>Prodi</th><th>Jenis</th><th>Kebutuhan</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($pengajuan_kaprodi)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada pengajuan sesuai filter.</td></tr>
                        <?php else: foreach ($pengajuan_kaprodi as $submission_index => $p): ?>
                            <tr>
                                <td class="kaur-row-number"><?= table_row_number_kaur($submission_index, $page ?? 1, $per_page ?? '10') ?></td>
                                <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                                <td><div class="fw-semibold"><?= html_escape($p->nama_prodi) ?></div><div class="small text-muted"><?= html_escape($p->nama_pengajuan) ?></div></td>
                                <td><span class="kaur-kind-badge"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                                <td style="min-width: 280px;">
                                    <div class="small text-muted mb-1"><?= html_escape($p->kebutuhan_lab ?: '-') ?></div>
                                    <?php foreach (($p->items ?? []) as $item): ?>
                                        <div class="small"><i class="bi bi-dot"></i><?= html_escape($item->uraian_barang) ?> - <?= num_kaur($item->vol) ?> <?= html_escape($item->satuan) ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td><span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span></td>
                                <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                                <td class="text-end"><a href="<?= base_url('index.php/kaur/pengajuan/export_excel/'.$p->id_pengajuan) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="kaur-submission-pagination-footer">
                <div class="kaur-submission-pagination-summary">
                    <label for="kaurSubmissionPageSize">Tampilkan:</label>
                    <select id="kaurSubmissionPageSize" class="form-select form-select-sm" aria-label="Jumlah pengajuan per halaman">
                        <option value="10" <?= (string) ($per_page ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= (string) ($per_page ?? '10') === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= (string) ($per_page ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                        <option value="all" <?= (string) ($per_page ?? '10') === 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                    <span>Total item: <?= (int) $total_rows ?></span>
                </div>
                <div class="kaur-submission-pagination-status">Halaman: <?= (int) $page ?> dari <?= (int) $total_pages ?></div>
                <nav>
                    <ul class="pagination pagination-sm kaur-submission-pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, max(1, $page - 1), $per_page ?? '10') ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, $i, $per_page ?? '10') ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('pengajuan') . '?' . query_kaur($filters, min($total_pages, $page + 1), $per_page ?? '10') ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'negosiasi'): ?>
        <section id="negosiasi" class="section-anchor mb-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Negosiasi oleh Kaur</h2>
                    <div class="text-muted small">Setiap simpan akan menjadi riwayat baru. Kaprodi hanya melihat status sampai hasil Deal.</div>
                </div>
            </div>
            <div class="panel-card p-3 p-lg-4 mb-3">
                <?php render_kaur_multi_filter('negosiasi', $filter_rows ?? [], ['per_page' => $per_page ?? '10']); ?>
            </div>
            <div class="negotiation-request-list">
                <?php if (empty($pengajuan_kaprodi)): ?>
                    <div class="panel-card p-4 text-center text-muted">Belum ada data untuk dinegosiasikan.</div>
                <?php else: foreach ($pengajuan_kaprodi as $p): ?>
                    <?php
                        $negotiation_items = (array) ($p->items ?? []);
                        $collapse_id = 'negotiation-detail-' . (int) $p->id_pengajuan;
                        $total_estimate = (float) ($p->summary['total_penawaran'] ?? $p->summary['total_setelah_pajak'] ?? 0);
                    ?>
                    <article class="panel-card negotiation-request-card" data-negotiation-group>
                        <div class="negotiation-request-header">
                            <div class="negotiation-request-heading">
                                <div>
                                    <div class="negotiation-request-code"><?= html_escape($p->kode_pengajuan) ?></div>
                                    <h3 class="negotiation-request-title"><?= html_escape($p->nama_pengajuan) ?></h3>
                                </div>
                                <span class="status-pill <?= status_class_kaur($p->status) ?>"><?= html_escape($p->status) ?></span>
                            </div>
                            <div class="negotiation-request-meta">
                                <span><i class="bi bi-mortarboard"></i><?= html_escape($p->nama_prodi) ?></span>
                                <span><i class="bi bi-box-seam"></i><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span>
                                <span><i class="bi bi-list-check"></i><?= count($negotiation_items) ?> item</span>
                                <span><i class="bi bi-cash-stack"></i>Total estimasi <?= rp_kaur($total_estimate) ?></span>
                            </div>
                        </div>
                        <button class="negotiation-detail-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="false" aria-controls="<?= $collapse_id ?>">
                            <span><i class="bi bi-table me-2"></i>Lihat Detail Barang/Jasa</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="<?= $collapse_id ?>">
                            <div class="negotiation-detail-body">
                                <div class="negotiation-detail-toolbar">
                                    <div>
                                        <h3>Rincian Negosiasi</h3>
                                        <p>Harga dan volume awal berasal dari pengajuan Kaprodi. Status negosiasi diatur untuk setiap item.</p>
                                    </div>
                                </div>
                                <div class="negotiation-items-scroll">
                                    <div class="negotiation-items-table" aria-label="Rincian item negosiasi <?= html_escape($p->kode_pengajuan) ?>">
                                        <?php foreach ($negotiation_items as $item_index => $item):
                                            $latest = $item->latest_negosiasi ?? null;
                                            $harga_awal_referensi = (float) ($item->harga_awal_referensi ?? $item->harga_penawaran_sat ?? 0);
                                            $volume_awal_referensi = (float) ($item->volume_awal_referensi ?? $item->vol ?? 0);
                                            $harga_akhir = $latest ? (float) $latest->harga_negosiasi : 0;
                                            $volume_akhir = $latest ? (float) $latest->volume_negosiasi : $volume_awal_referensi;
                                            $total_negosiasi_item = $harga_akhir * $volume_akhir;
                                            $status_negosiasi = $latest && in_array($latest->status, ['Sedang Negosiasi', 'Deal', 'Ditolak'], true) ? $latest->status : 'Sedang Negosiasi';
                                        ?>
                                            <form class="negotiation-form" method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_negosiasi/'.$p->id_pengajuan.'/'.$item->id_item) ?>">
                                                <div class="negotiation-item-header">
                                                    <div class="d-flex align-items-center gap-3 min-w-0">
                                                        <span class="negotiation-item-number"><?= (int) $item_index + 1 ?></span>
                                                        <div class="negotiation-item-name"><strong><?= html_escape($item->uraian_barang) ?></strong><span><?= html_escape($item->jenis_item ?? 'Barang') ?> - <?= html_escape($item->satuan) ?></span></div>
                                                    </div>
                                                </div>
                                                <div class="row negotiation-form-grid">
                                                    <div class="col-lg-4 col-md-6 negotiation-field">
                                                        <label class="negotiation-field-label">Status Negosiasi</label>
                                                        <select name="status" class="form-select negotiation-item-status">
                                                            <?php foreach (['Sedang Negosiasi','Deal','Ditolak'] as $s): ?>
                                                                <option value="<?= $s ?>" <?= $status_negosiasi === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Vendor</label><input type="text" name="vendor" class="form-control" value="<?= html_escape($latest->vendor ?? '') ?>" required></div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Harga Awal</label><input type="text" name="harga_awal" class="form-control" value="<?= rp_kaur($harga_awal_referensi) ?>" disabled readonly aria-readonly="true"><div class="form-text">Referensi dari pengajuan Kaprodi</div></div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Harga Negosiasi</label><input type="text" name="harga_negosiasi" class="form-control money-input negotiation-price" value="<?= $latest && $harga_akhir > 0 ? rp_kaur($harga_akhir) : '' ?>" placeholder="Rp 0" required></div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Volume Awal</label><input type="number" name="volume_awal" class="form-control" value="<?= html_escape($volume_awal_referensi) ?>" disabled readonly aria-readonly="true"><div class="form-text"><?= html_escape($item->satuan) ?> - Referensi dari Kaprodi</div></div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Volume Negosiasi</label><input type="number" name="volume_negosiasi" class="form-control negotiation-volume" min="0.01" step="0.01" value="<?= html_escape($volume_akhir) ?>" required><div class="form-text"><?= html_escape($item->satuan) ?></div></div>
                                                    <div class="col-lg-4 col-md-6 negotiation-field"><label class="negotiation-field-label">Total Hasil Negosiasi</label><input type="text" class="form-control negotiation-total fw-semibold" value="<?= rp_kaur($total_negosiasi_item) ?>" readonly aria-readonly="true"></div>
                                                    <div class="col-12 negotiation-field"><label class="negotiation-field-label">Garansi</label><input type="text" name="garansi" class="form-control" value="<?= html_escape($latest->garansi ?? '') ?>" placeholder="Contoh: 1 tahun"></div>
                                                    <div class="col-12 negotiation-field"><label class="negotiation-field-label">Catatan</label><input type="text" name="catatan" class="form-control" value="<?= html_escape($latest->catatan ?? '') ?>" placeholder="Catatan hasil negosiasi"></div>
                                                </div>
                                                <div class="negotiation-form-actions"><button class="btn btn-fik negotiation-save-btn" type="submit"><i class="bi bi-save me-1"></i>Simpan</button></div>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; endif; ?>
            </div>
            <div class="kaur-submission-table-card negotiation-pagination-shell">
                <div class="kaur-submission-pagination-footer">
                    <div class="kaur-submission-pagination-summary">
                        <label for="kaurNegotiationPageSize">Tampilkan:</label>
                        <select id="kaurNegotiationPageSize" class="form-select form-select-sm" aria-label="Jumlah pengajuan negosiasi per halaman">
                            <option value="10" <?= (string) ($per_page ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= (string) ($per_page ?? '10') === '25' ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= (string) ($per_page ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                            <option value="all" <?= (string) ($per_page ?? '10') === 'all' ? 'selected' : '' ?>>Semua</option>
                        </select>
                        <span>Total item: <?= (int) $total_rows ?></span>
                    </div>
                    <div class="kaur-submission-pagination-status">Halaman: <?= (int) $page ?> dari <?= (int) $total_pages ?></div>
                    <nav>
                        <ul class="pagination pagination-sm kaur-submission-pagination">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, max(1, $page - 1), $per_page ?? '10') ?>">Previous</a></li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, $i, $per_page ?? '10') ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('negosiasi') . '?' . query_kaur($filters, min($total_pages, $page + 1), $per_page ?? '10') ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'approval'): ?>
        <section id="approval" class="section-anchor mb-4">
            <div class="panel-card p-3 p-lg-4 mb-3">
            <div class="approval-table-heading mb-3">
                <h2 class="h5 fw-bold mb-1">Approval Kaur</h2>
                <div class="text-muted small">Kaur dapat menyetujui, meminta revisi, atau menolak pengajuan sesuai kebutuhan proses bisnis.</div>
            </div>
            <?php render_kaur_multi_filter('approval', $filter_rows ?? [], [
                'per_page' => $per_page ?? '10',
                'sort_by' => $filters['sort_by'] ?? '',
                'sort_dir' => $filters['sort_dir'] ?? '',
            ]); ?>
            </div>

            <div class="panel-card kaur-submission-table-card approval-table-card">
            <div class="table-responsive">
                <table class="table table-clean align-middle mb-0 approval-table">
                    <thead><tr>
                        <th>No</th>
                        <th><a href="<?= sort_url_kaur('approval', $filters, 'kode', 1, $per_page ?? '10') ?>" class="text-decoration-none text-dark">Kode Pengajuan <?= sort_icon_kaur($filters, 'kode') ?></a></th>
                        <th><a href="<?= sort_url_kaur('approval', $filters, 'tanggal', 1, $per_page ?? '10') ?>" class="text-decoration-none text-dark">Tanggal <?= sort_icon_kaur($filters, 'tanggal') ?></a></th>
                        <th><a href="<?= sort_url_kaur('approval', $filters, 'prodi', 1, $per_page ?? '10') ?>" class="text-decoration-none text-dark">Program Studi <?= sort_icon_kaur($filters, 'prodi') ?></a></th>
                        <th><a href="<?= sort_url_kaur('approval', $filters, 'jenis', 1, $per_page ?? '10') ?>" class="text-decoration-none text-dark">Jenis <?= sort_icon_kaur($filters, 'jenis') ?></a></th>
                        <th>Vendor</th>
                        <th>Total Harga</th>
                        <th>Status Negosiasi</th>
                        <th><a href="<?= sort_url_kaur('approval', $filters, 'status', 1, $per_page ?? '10') ?>" class="text-decoration-none text-dark">Status Approval <?= sort_icon_kaur($filters, 'status') ?></a></th>
                        <th class="text-end">Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($pengajuan_kaprodi)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-5">Belum ada pengajuan untuk approval.</td></tr>
                    <?php else: foreach (($pengajuan_kaprodi ?? []) as $approval_index => $p): ?>
                        <?php
                            $vendors = [];
                            $nego_statuses = [];
                            $can_approve = !empty($p->items);
                            $has_deal_item = false;
                            foreach (($p->items ?? []) as $approval_item) {
                                $latest = $approval_item->latest_negosiasi ?? null;
                                if ($latest) {
                                    if (!empty($latest->vendor)) { $vendors[] = $latest->vendor; }
                                    $nego_statuses[] = $latest->status;
                                }
                                $item_status = $latest->status ?? null;
                                if (!in_array($item_status, ['Deal', 'Ditolak'], true)) { $can_approve = false; }
                                if ($item_status === 'Deal') { $has_deal_item = true; }
                            }
                            $can_approve = $can_approve && $has_deal_item;
                            $vendor_label = $vendors ? implode(', ', array_unique($vendors)) : '-';
                            $nego_label = $nego_statuses ? implode(', ', array_unique($nego_statuses)) : 'Belum Negosiasi';
                            $total_harga = ($p->summary['total_negosiasi'] ?? 0) > 0 ? $p->summary['total_negosiasi'] : ($p->summary['total_setelah_pajak'] ?? $p->summary['total_penawaran'] ?? 0);
                        ?>
                        <tr>
                            <td class="kaur-row-number"><?= table_row_number_kaur($approval_index, $page ?? 1, $per_page ?? '10') ?></td>
                            <td class="fw-semibold"><?= html_escape($p->kode_pengajuan) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($p->created_at)) ?></td>
                            <td><?= html_escape($p->nama_prodi) ?></td>
                            <td><span class="approval-badge"><?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></span></td>
                            <td><?= html_escape($vendor_label) ?></td>
                            <td><?= rp_kaur($total_harga) ?></td>
                            <td><span class="approval-badge"><?= html_escape($nego_label) ?></span></td>
                            <td><span class="approval-badge"><?= html_escape($p->status) ?></span></td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-success rounded-pill px-3 approval-detail-btn" data-bs-toggle="modal" data-bs-target="#approvalModal<?= (int) $p->id_pengajuan ?>"><i class="bi bi-eye me-1"></i> Detail</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="kaur-submission-pagination-footer">
                <div class="kaur-submission-pagination-summary">
                    <label for="kaurApprovalPageSize">Tampilkan:</label>
                    <select id="kaurApprovalPageSize" class="form-select form-select-sm" aria-label="Jumlah approval per halaman">
                        <option value="10" <?= (string) ($per_page ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= (string) ($per_page ?? '10') === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= (string) ($per_page ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                        <option value="all" <?= (string) ($per_page ?? '10') === 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                    <span>Total item: <?= (int) $total_rows ?></span>
                </div>
                <div class="kaur-submission-pagination-status">Halaman: <?= (int) $page ?> dari <?= (int) $total_pages ?></div>
                <nav>
                    <ul class="pagination pagination-sm kaur-submission-pagination">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, max(1, $page - 1), $per_page ?? '10') ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, $i, $per_page ?? '10') ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('approval') . '?' . query_kaur($filters, min($total_pages, $page + 1), $per_page ?? '10') ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
            </div>
        </section>
        <?php foreach (($pengajuan_kaprodi ?? []) as $p): ?>
            <?php
                $can_approve_modal = !empty($p->items);
                $has_deal_item = false;
                foreach (($p->items ?? []) as $approval_item) {
                    $item_status = $approval_item->latest_negosiasi->status ?? null;
                    if (!in_array($item_status, ['Deal', 'Ditolak'], true)) {
                        $can_approve_modal = false;
                        break;
                    }
                    if ($item_status === 'Deal') {
                        $has_deal_item = true;
                    }
                }
                $can_approve_modal = $can_approve_modal && $has_deal_item;
                $auto_approved = (($p->status ?? '') === 'Approval');
            ?>
            <div class="modal fade approval-detail-modal" id="approvalModal<?= (int) $p->id_pengajuan ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="post" action="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold"><?= html_escape($p->kode_pengajuan) ?> - <?= html_escape($p->nama_pengajuan) ?></h5>
                                <div class="small text-muted"><?= html_escape($p->nama_prodi) ?> - <?= html_escape($p->jenis_pengajuan ?? 'Barang') ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3"><div class="mini-label">Kebutuhan</div><div><?= html_escape($p->kebutuhan_lab ?: '-') ?></div></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle approval-detail-items-table">
                                    <thead class="table-light"><tr><th>Item</th><th>Volume</th><th>Harga Awal</th><th>Vendor</th><th>Harga Negosiasi</th><th>Status</th><th>Garansi</th><th>Catatan</th></tr></thead>
                                    <tbody>
                                        <?php foreach (($p->items ?? []) as $item): $latest = $item->latest_negosiasi ?? null; $item_status = $latest->status ?? null; ?>
                                            <tr class="<?= $item_status === 'Ditolak' ? 'approval-item-rejected' : '' ?>">
                                                <td><div class="approval-detail-item"><span class="approval-detail-item-badge"><?= html_escape($item->jenis_item ?? 'Barang') ?></span><span><?= html_escape($item->uraian_barang) ?></span></div></td>
                                                <td><?= num_kaur($item->volume_awal_referensi ?? $item->vol) ?> <?= html_escape($item->satuan) ?></td>
                                                <td><?= rp_kaur($item->harga_awal_referensi ?? $item->harga_penawaran_sat ?? 0) ?></td>
                                                <td><?= html_escape($latest->vendor ?? '-') ?></td>
                                                <td><?= $latest ? rp_kaur($latest->harga_negosiasi) : '-' ?></td>
                                                <td><span class="status-pill <?= status_class_kaur($item_status ?? '') ?>"><?= html_escape($item_status ?? 'Belum Negosiasi') ?></span></td>
                                                <td><?= html_escape($latest->garansi ?? '-') ?></td>
                                                <td><?= html_escape($latest->catatan ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <label class="form-label small fw-semibold">Catatan Approval / Revisi</label>
                            <textarea name="catatan_approval" class="form-control" rows="3" placeholder="Catatan approval, revisi, atau alasan penolakan."><?= html_escape($p->catatan_approval ?? '') ?></textarea>
                            <?php if ($auto_approved): ?><div class="alert alert-success py-2 small mt-2 mb-0"><i class="bi bi-check-circle me-1"></i> Seluruh item sudah selesai dinegosiasikan. Pengajuan otomatis berstatus Approval dan dapat dilanjutkan ke Alokasi Anggaran/BAST.</div><?php elseif (!$can_approve_modal): ?><div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i> Pengajuan bisa disetujui setelah setiap item berstatus final (Deal atau Ditolak), dengan minimal satu item Deal.</div><?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/revisi') ?>" class="btn btn-warning rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i> Revisi</button>
                            <button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/tolak') ?>" class="btn btn-outline-danger rounded-pill px-3" onclick="return confirm('Tolak pengajuan ini?')"><i class="bi bi-x-lg me-1"></i> Tolak</button>
                            <?php if (!$auto_approved): ?><button formaction="<?= base_url('index.php/kaur/pengajuan/approval/'.$p->id_pengajuan.'/approve') ?>" class="btn btn-success rounded-pill px-3" <?= $can_approve_modal ? '' : 'disabled' ?>><i class="bi bi-check2 me-1"></i> Setujui</button><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($active_module === 'anggaran'): ?>
        <section id="anggaran" class="section-anchor panel-card p-3 p-lg-4 mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="h5 fw-bold mb-2">Pagu Anggaran Tahunan</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="mini-label">Tahun Anggaran</div><div class="fw-bold"><?= (int) $anggaran['tahun'] ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pagu Anggaran</div><div class="fw-bold"><?= rp_kaur($anggaran['total_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengadaan Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengadaan_deal'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Total Pengeluaran Deal</div><div class="fw-bold"><?= rp_kaur($anggaran['total_pengeluaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Sisa Anggaran</div><div class="fw-bold text-success"><?= rp_kaur($anggaran['sisa_anggaran']) ?></div></div>
                        <div class="col-6"><div class="mini-label">Penggunaan</div><div class="fw-bold"><?= number_format((float) $anggaran['persentase_penggunaan'], 1, ',', '.') ?>%</div></div>
                        <div class="col-6"><div class="mini-label">Penghematan CAPEX</div><div class="fw-bold text-primary"><?= rp_kaur($anggaran['penghematan_capex'] ?? 0) ?></div></div>
                        <div class="col-6"><div class="mini-label">Belum Terealisasi</div><div class="fw-bold"><?= (int) ($anggaran['belum_terealisasi'] ?? 0) ?> pengajuan</div></div>
                    </div>
                    <div class="progress"><div class="progress-bar bg-success" style="width: <?= min(100, (float) $anggaran['persentase_penggunaan']) ?>%"></div></div>
                </div>
                <div class="col-lg-6">
                    <form method="post" action="<?= base_url('index.php/kaur/pengajuan/simpan_anggaran') ?>" class="row g-2">
                        <div class="col-md-4"><label class="form-label small fw-semibold">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= (int) $anggaran['tahun'] ?>" required></div>
                        <div class="col-md-8"><label class="form-label small fw-semibold">Total Anggaran</label><input type="text" name="total_anggaran" class="form-control money-input" value="<?= $anggaran['total_anggaran'] ? rp_kaur($anggaran['total_anggaran']) : '' ?>" required></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Catatan</label><textarea name="catatan" class="form-control" rows="2"><?= html_escape($anggaran['catatan'] ?? '') ?></textarea></div>
                        <div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-fik rounded-pill px-4"><i class="bi bi-save me-1"></i> Simpan Anggaran</button></div>
                    </form>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($active_module === 'bast'): ?>
        <?php
            // Merge "siap BAST" (belum upload) dengan "sudah BAST" (sudah upload) jadi satu daftar tabel.
            // Catatan implementasi: idealnya controller Kaur_pengajuan/bast() sudah menyatukan kedua sumber ini
            // (dan menerapkan filter q/tahun/status_bast/tanggal_dari/tanggal_sampai di level query) lalu mengirim
            // variabel $bast_rows langsung. Blok di bawah tetap kompatibel dengan variabel lama ($bast_ready,
            // $bast_list) apabila controller belum diubah.
            if (!isset($bast_rows)) {
                $bast_rows = [];
                $bast_by_id = [];
                foreach (($bast_list ?? []) as $b) {
                    if (!empty($b->id_pengajuan)) { $bast_by_id[$b->id_pengajuan] = $b; }
                }
                foreach (($bast_ready ?? []) as $p) {
                    $match = $bast_by_id[$p->id_pengajuan] ?? null;
                    $p->nomor_bast = $match->nomor_bast ?? null;
                    $p->tanggal_bast = $match->tanggal_bast ?? null;
                    $p->file_bast = $match->file_bast ?? null;
                    $p->catatan_bast = $match->catatan ?? null;
                    unset($bast_by_id[$p->id_pengajuan]);
                    $bast_rows[] = $p;
                }
                foreach ($bast_by_id as $b) {
                    $b->kode_pengajuan = $b->kode_pengajuan ?? ($b->nomor_bast ?? '-');
                    $bast_rows[] = $b;
                }
            }
            $total_rows_bast = count($bast_rows);
            $total_pages_bast = $limit === null ? 1 : max(1, (int) ceil($total_rows_bast / $limit));
            if ($page > $total_pages_bast) { $page = $total_pages_bast; }
            $bast_pending_count = count(array_filter($bast_rows, fn($row) => empty($row->nomor_bast)));
            if ($limit !== null) {
                $bast_rows = array_slice($bast_rows, ($page - 1) * $limit, $limit);
            }
            $bast_years = $bast_years ?? [(int) date('Y')];
            $bast_signer_nama = $bast_signer_nama ?? (($this->session->userdata('nama') ?? null) ?: 'Kaur. Pencatatan & Pengelolaan Aset');
            $bast_signer_jabatan = $bast_signer_jabatan ?? 'Kaur. Pencatatan & Pengelolaan Aset';
        ?>
        <section id="bast" class="section-anchor mb-4">
            <div class="panel-card kaur-submission-table-card bast-table-card">
            <div class="bast-table-toolbar">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">BAST (Berita Acara Serah Terima)</h2>
                    <div class="text-muted small">Cari, unggah dokumen BAST dari Logistik, atau langsung buat/cetak BAST dari data pengajuan.</div>
                </div>
                <span class="bast-summary-badge align-self-start"><?= (int) $bast_pending_count ?> menunggu BAST</span>
            </div>

            <?php render_kaur_multi_filter('bast', $filter_rows ?? [], ['per_page' => $per_page ?? '10']); ?>
            </div>

            <div class="table-responsive">
                <table class="table table-clean align-middle mb-0 bast-table">
                    <thead><tr><th>No</th><th>Kode Pengajuan</th><th>Prodi / Kegiatan</th><th>Jenis</th><th>Nomor BAST</th><th>Tanggal BAST</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($bast_rows)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">Belum ada pengajuan yang siap BAST sesuai filter.</td></tr>
                        <?php else: foreach ($bast_rows as $bast_index => $b): ?>
                            <?php
                                $has_bast = !empty($b->nomor_bast);
                                $b_items = [];
                                foreach (($b->items ?? []) as $bi) {
                                    $b_items[] = [
                                        'uraian' => $bi->uraian_barang ?? '-',
                                        'vol' => (float) ($bi->volume_awal_referensi ?? $bi->vol ?? 0),
                                        'satuan' => $bi->satuan ?? 'Unit',
                                    ];
                                }
                                $terbilang = tanggal_terbilang_kaur($b->tanggal_bast ?? date('Y-m-d'));
                            ?>
                            <tr>
                                <td class="kaur-row-number"><?= table_row_number_kaur($bast_index, $page ?? 1, $per_page ?? '10') ?></td>
                                <td class="fw-semibold"><?= html_escape($b->kode_pengajuan ?? '-') ?></td>
                                <td><div class="fw-semibold"><?= html_escape($b->nama_prodi ?? '-') ?></div><div class="small text-muted"><?= html_escape($b->nama_pengajuan ?? '-') ?></div></td>
                                <td><span class="bast-badge"><?= html_escape($b->jenis_pengajuan ?? 'Barang') ?></span></td>
                                <td><?= html_escape($b->nomor_bast ?? '-') ?></td>
                                <td class="small text-muted"><?= !empty($b->tanggal_bast) ? date('d/m/Y', strtotime($b->tanggal_bast)) : '-' ?></td>
                                <td class="text-end">
                                    <div class="bast-actions">
                                        <?php if (!empty($b->file_bast)): ?>
                                            <a href="<?= base_url($b->file_bast) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 bast-action-btn"><i class="bi bi-file-earmark-text me-1"></i> File</a>
                                        <?php endif; ?>
                                        <?php if (!empty($b->id_pengajuan)): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 bast-action-btn" data-bs-toggle="modal" data-bs-target="#bastUploadModal<?= (int) $b->id_pengajuan ?>"><i class="bi bi-upload me-1"></i> <?= $has_bast ? 'Ganti File' : 'Upload' ?></button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-fik rounded-pill px-3 btn-generate-bast bast-action-btn"
                                            data-nomor="<?= html_escape($b->nomor_bast ?: 'FIK/' . date('y') . '/---') ?>"
                                            data-spk="<?= html_escape($b->spk_no ?? $b->kode_pengajuan ?? '-') ?>"
                                            data-hari="<?= html_escape($terbilang['hari']) ?>"
                                            data-tanggal-kata="<?= html_escape($terbilang['tanggal']) ?>"
                                            data-bulan-kata="<?= html_escape($terbilang['bulan']) ?>"
                                            data-tahun-kata="<?= html_escape($terbilang['tahun']) ?>"
                                            data-ruangan="<?= html_escape($b->ruangan ?? '-') ?>"
                                            data-prodi="<?= html_escape($b->nama_prodi ?? '-') ?>"
                                            data-kegiatan="<?= html_escape($b->nama_pengajuan ?? '-') ?>"
                                            data-kaur-nama="<?= html_escape($bast_signer_nama) ?>"
                                            data-kaur-jabatan="<?= html_escape($bast_signer_jabatan) ?>"
                                            data-kaprodi-nama="<?= html_escape($b->kaprodi_nama ?? '') ?>"
                                            data-items="<?= html_escape(json_encode($b_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                                            <i class="bi bi-file-earmark-richtext me-1"></i> Generate BAST
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="kaur-submission-pagination-footer">
                <div class="kaur-submission-pagination-summary">
                    <label for="kaurBastPageSize">Tampilkan:</label>
                    <select id="kaurBastPageSize" class="form-select form-select-sm" aria-label="Jumlah data BAST per halaman">
                        <option value="10" <?= (string) ($per_page ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= (string) ($per_page ?? '10') === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= (string) ($per_page ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                        <option value="all" <?= (string) ($per_page ?? '10') === 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                    <span>Total item: <?= (int) $total_rows_bast ?></span>
                </div>
                <div class="kaur-submission-pagination-status">Halaman: <?= (int) $page ?> dari <?= (int) $total_pages_bast ?></div>
                <nav>
                    <ul class="pagination pagination-sm kaur-submission-pagination">
                        <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('bast') . '?' . query_kaur($filters, max(1, ($page ?? 1) - 1), $per_page ?? '10') ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= $total_pages_bast; $i++): ?>
                            <li class="page-item <?= $i === (int) ($page ?? 1) ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('bast') . '?' . query_kaur($filters, $i, $per_page ?? '10') ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page ?? 1) >= $total_pages_bast ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('bast') . '?' . query_kaur($filters, min($total_pages_bast, ($page ?? 1) + 1), $per_page ?? '10') ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
            </div>
        </section>

        <?php foreach ($bast_rows as $b): if (empty($b->id_pengajuan)) continue; ?>
            <div class="modal fade" id="bastUploadModal<?= (int) $b->id_pengajuan ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form class="modal-content" method="post" enctype="multipart/form-data" action="<?= base_url('index.php/kaur/pengajuan/simpan_bast/'.$b->id_pengajuan) ?>">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold"><?= html_escape($b->kode_pengajuan ?? '-') ?> - <?= html_escape($b->nama_pengajuan ?? '-') ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-md-6"><label class="form-label small fw-semibold">Nomor BAST</label><input type="text" name="nomor_bast" class="form-control" value="<?= html_escape($b->nomor_bast ?? '') ?>" required></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Tanggal BAST</label><input type="date" name="tanggal_bast" class="form-control" value="<?= html_escape($b->tanggal_bast ?? date('Y-m-d')) ?>" required></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Jenis</label><select name="jenis_bast" class="form-select"><option value="Barang" <?= (($b->jenis_pengajuan ?? 'Barang') === 'Barang') ? 'selected' : '' ?>>Barang</option><option value="Jasa" <?= (($b->jenis_pengajuan ?? '') === 'Jasa') ? 'selected' : '' ?>>Jasa</option><option value="Barang dan Jasa" <?= (($b->jenis_pengajuan ?? '') === 'Barang dan Jasa') ? 'selected' : '' ?>>Barang dan Jasa</option></select></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">File PDF/Scan</label><input type="file" name="file_bast" class="form-control" accept=".pdf,.jpg,.jpeg,.png"<?= empty($b->file_bast) ? ' required' : '' ?>></div>
                            <div class="col-12"><label class="form-label small fw-semibold">Catatan</label><input type="text" name="catatan" class="form-control" value="<?= html_escape($b->catatan_bast ?? '') ?>"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            <button class="btn btn-fik rounded-pill px-4"><i class="bi bi-upload me-1"></i> Simpan BAST</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="modal fade" id="bastGenerateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Preview BAST</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i> Klik pada "Sesuai" atau "Tidak Sesuai" di setiap baris untuk menandai hasil serah terima sebelum mencetak.</div>
                        <div id="bastPreviewArea" class="border rounded-3 p-3" style="font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: .92rem;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-fik rounded-pill px-4" id="bastPrintBtn"><i class="bi bi-printer me-1"></i> Cetak / Simpan PDF</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($active_module === 'laporan'): ?>
        <section id="laporan" class="section-anchor mb-4">
            <div class="panel-card kaur-submission-table-card">
            <div class="kaur-report-table-header">
                <h2 class="h5 fw-bold mb-1">Laporan Hasil Negosiasi</h2>
                <div class="text-muted small">Hanya data dengan status Deal yang tampil sebagai dokumentasi resmi hasil akhir.</div>
            </div>
            <div class="kaur-report-toolbar">
                <?php render_kaur_multi_filter('laporan', $filter_rows ?? [], [
                    'sort_by' => $filters['sort_by'] ?? '',
                    'sort_dir' => $filters['sort_dir'] ?? '',
                    'per_page' => $per_page ?? '10',
                ]); ?>
            </div>
            <div class="table-responsive">
                <table class="table table-clean align-middle kaur-report-table">
                    <thead><tr>
                        <?php $report_sort_by = (string) ($filters['sort_by'] ?? ''); ?>
                        <th>No</th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'kode_pengajuan' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'kode_pengajuan', 1, $per_page ?? '10') ?>">Pengajuan <?= sort_icon_kaur($filters, 'kode_pengajuan') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'item' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'item', 1, $per_page ?? '10') ?>">Item <?= sort_icon_kaur($filters, 'item') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'vendor' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'vendor', 1, $per_page ?? '10') ?>">Vendor <?= sort_icon_kaur($filters, 'vendor') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'harga_awal' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'harga_awal', 1, $per_page ?? '10') ?>">Harga Awal <?= sort_icon_kaur($filters, 'harga_awal') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'harga_akhir' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'harga_akhir', 1, $per_page ?? '10') ?>">Harga Akhir <?= sort_icon_kaur($filters, 'harga_akhir') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'selisih' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'selisih', 1, $per_page ?? '10') ?>">Selisih <?= sort_icon_kaur($filters, 'selisih') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'volume' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'volume', 1, $per_page ?? '10') ?>">Volume <?= sort_icon_kaur($filters, 'volume') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'garansi' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'garansi', 1, $per_page ?? '10') ?>">Garansi <?= sort_icon_kaur($filters, 'garansi') ?></a></th>
                        <th><a class="kaur-report-sort-link <?= $report_sort_by === 'catatan' ? 'is-active' : '' ?>" href="<?= sort_url_kaur('laporan', $filters, 'catatan', 1, $per_page ?? '10') ?>">Catatan <?= sort_icon_kaur($filters, 'catatan') ?></a></th>
                    </tr></thead>
                    <tbody>
                        <?php if (empty($laporan_negosiasi)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-5"><?= !empty($filters['q']) ? 'Tidak ada laporan yang cocok dengan pencarian.' : 'Belum ada negosiasi yang Deal.' ?></td></tr>
                        <?php else: foreach ($laporan_negosiasi as $report_index => $lap): ?>
                            <tr>
                                <td class="kaur-row-number"><?= table_row_number_kaur($report_index, $page ?? 1, $per_page ?? '10') ?></td>
                                <td><div class="fw-semibold"><?= html_escape($lap->kode_pengajuan) ?></div><div class="small text-muted"><?= html_escape($lap->nama_pengajuan) ?></div></td>
                                <td><?= html_escape($lap->uraian_barang) ?></td>
                                <td><?= html_escape($lap->vendor ?: '-') ?></td>
                                <td><?= rp_kaur($lap->harga_awal) ?></td>
                                <td class="fw-semibold text-success"><?= rp_kaur($lap->harga_negosiasi) ?></td>
                                <td><?= rp_kaur($lap->selisih_harga) ?></td>
                                <td><?= num_kaur($lap->volume_negosiasi) ?> <?= html_escape($lap->satuan) ?></td>
                                <td><?= html_escape($lap->garansi ?: '-') ?></td>
                                <td><?= html_escape($lap->catatan ?: '-') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="kaur-submission-pagination-footer">
                <div class="kaur-submission-pagination-summary">
                    <label for="kaurReportPageSize">Tampilkan:</label>
                    <select id="kaurReportPageSize" class="form-select form-select-sm" aria-label="Jumlah laporan negosiasi per halaman">
                        <option value="10" <?= (string) ($per_page ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= (string) ($per_page ?? '10') === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= (string) ($per_page ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                        <option value="all" <?= (string) ($per_page ?? '10') === 'all' ? 'selected' : '' ?>>Semua</option>
                    </select>
                    <span>Total item: <?= (int) ($total_rows_laporan ?? 0) ?></span>
                </div>
                <div class="kaur-submission-pagination-status">Halaman: <?= (int) ($page ?? 1) ?> dari <?= (int) ($total_pages_laporan ?? 1) ?></div>
                <nav aria-label="Pagination laporan hasil negosiasi">
                    <ul class="pagination pagination-sm kaur-submission-pagination">
                        <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('laporan') . '?' . query_kaur($filters, max(1, ($page ?? 1) - 1), $per_page ?? '10') ?>">Previous</a></li>
                        <?php for ($i = 1; $i <= ($total_pages_laporan ?? 1); $i++): ?>
                            <li class="page-item <?= $i === (int) ($page ?? 1) ? 'active' : '' ?>"><a class="page-link" href="<?= kaur_module_url('laporan') . '?' . query_kaur($filters, $i, $per_page ?? '10') ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page ?? 1) >= ($total_pages_laporan ?? 1) ? 'disabled' : '' ?>"><a class="page-link" href="<?= kaur_module_url('laporan') . '?' . query_kaur($filters, min(($total_pages_laporan ?? 1), ($page ?? 1) + 1), $per_page ?? '10') ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            document.querySelectorAll('[data-kaur-multi-filter]').forEach((form) => {
                const list = form.querySelector('[data-filter-list]');
                if (!list) return;
                const maxFilters = Number(form.dataset.maxFilters || 4);
                let submitTimer;

                const syncInput = (row, clearValue = false) => {
                    const select = row.querySelector('.kaur-filter-field');
                    const input = row.querySelector('.kaur-filter-value');
                    const option = select?.selectedOptions?.[0];
                    if (!select || !input || !option) return;
                    if (clearValue) input.value = '';
                    input.type = option.dataset.inputType || 'search';
                    input.placeholder = option.dataset.placeholder || 'Ketik untuk mencari';
                };

                const updateButtons = () => {
                    const rows = Array.from(list.querySelectorAll('[data-filter-row]'));
                    rows.forEach((row, index) => {
                        const remove = row.querySelector('.kaur-filter-remove');
                        const add = row.querySelector('.kaur-filter-add');
                        if (remove) remove.disabled = rows.length === 1;
                        if (add) add.disabled = rows.length >= maxFilters || index !== rows.length - 1;
                    });
                };

                const addRow = () => {
                    const currentRows = list.querySelectorAll('[data-filter-row]');
                    if (!currentRows.length || currentRows.length >= maxFilters) return null;
                    const sourceSelect = currentRows[0].querySelector('.kaur-filter-field');
                    const row = document.createElement('div');
                    row.className = 'kaur-filter-row';
                    row.dataset.filterRow = '';
                    row.innerHTML = `
                        <select name="filter_field[]" class="form-select kaur-filter-field" aria-label="Jenis filter ${currentRows.length + 1}">${sourceSelect.innerHTML}</select>
                        <input type="search" name="filter_value[]" class="form-control kaur-filter-value" value="" autocomplete="off" aria-label="Nilai filter ${currentRows.length + 1}">
                        <div class="kaur-filter-tools">
                            <button type="button" class="btn btn-outline-secondary kaur-filter-icon kaur-filter-remove" aria-label="Hapus filter"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                            <button type="button" class="btn btn-outline-primary kaur-filter-icon kaur-filter-add" aria-label="Tambah filter"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
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
                    if (event.target.closest('.kaur-filter-add')) {
                        addRow()?.querySelector('.kaur-filter-value')?.focus();
                    } else if (event.target.closest('.kaur-filter-remove') && list.querySelectorAll('[data-filter-row]').length > 1) {
                        row.remove();
                        updateButtons();
                        form.requestSubmit();
                    }
                });

                list.addEventListener('change', (event) => {
                    if (!event.target.matches('.kaur-filter-field')) return;
                    const row = event.target.closest('[data-filter-row]');
                    syncInput(row, true);
                    row.querySelector('.kaur-filter-value')?.focus();
                });

                list.addEventListener('input', (event) => {
                    if (!event.target.matches('.kaur-filter-value')) return;
                    window.clearTimeout(submitTimer);
                    submitTimer = window.setTimeout(() => form.requestSubmit(), 650);
                });
            });
        })();
    </script>
    <script>
        (() => {
            const dashboardData = <?= json_encode([
                'monthly' => array_values($dashboard_monthly_submissions),
                'statuses' => $dashboard_status_breakdown,
                'budget' => [
                    'pagu' => (float) ($anggaran['total_anggaran'] ?? 0),
                    'terpakai' => (float) ($anggaran['total_pengeluaran'] ?? 0),
                    'sisa' => (float) ($anggaran['sisa_anggaran'] ?? 0),
                ],
                'negotiation' => [
                    'harga_awal' => (float) ($dashboard_negotiation['harga_awal'] ?? 0),
                    'harga_negosiasi' => (float) ($dashboard_negotiation['harga_negosiasi'] ?? 0),
                ],
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

            const formatRupiah = (value) => 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
            const formatCounter = (value, format) => format === 'currency' ? formatRupiah(value) : Math.round(Number(value) || 0).toLocaleString('id-ID');

            document.querySelectorAll('[data-counter]').forEach((element) => {
                const target = Number(element.dataset.counter || 0);
                const format = element.dataset.counterFormat || 'number';
                const startedAt = performance.now();
                const duration = 760;

                const tick = (now) => {
                    const progress = Math.min(1, (now - startedAt) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    element.textContent = formatCounter(target * eased, format);
                    if (progress < 1) {
                        window.requestAnimationFrame(tick);
                    }
                };
                window.requestAnimationFrame(tick);
            });

            const chartInstances = [];
            const isLightTheme = document.documentElement.classList.contains('scm-theme-light');
            const chartText = isLightTheme ? '#273138' : '#f3f5f6';
            const chartMuted = isLightTheme ? '#68727b' : '#8f989f';
            const chartGrid = isLightTheme ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
            const orange = '#ff7900';
            const orangeSoft = '#f39a52';
            const orangeDeep = '#c65b18';
            const warmGray = '#aeb6ba';
            const darkGray = '#4b5257';
            const chartFont = { family: 'Poppins, sans-serif' };
            const scaleOptions = {
                x: { ticks: { color: chartMuted, font: chartFont }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: chartMuted, precision: 0, font: chartFont }, grid: { color: chartGrid } },
            };
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                plugins: { legend: { labels: { color: chartText, usePointStyle: true, padding: 16, font: chartFont } }, tooltip: { backgroundColor: isLightTheme ? '#ffffff' : '#181a1b', titleColor: chartText, bodyColor: chartText, borderColor: chartGrid, borderWidth: 1 } },
            };

            const showFallback = (canvas) => {
                if (!canvas) return;
                const fallback = canvas.parentElement.querySelector('.kaur-chart-fallback');
                if (fallback) fallback.style.display = 'flex';
                canvas.style.display = 'none';
            };

            if (typeof window.Chart === 'undefined') {
                document.querySelectorAll('.kaur-chart-wrap canvas').forEach(showFallback);
            } else {
                const submissionCanvas = document.getElementById('submissionChart');
                if (submissionCanvas) {
                    const chart = new window.Chart(submissionCanvas, {
                        type: 'bar',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                            datasets: [{ label: 'Pengajuan', data: dashboardData.monthly || [], backgroundColor: orange, borderRadius: 6, maxBarThickness: 24 }],
                        },
                        options: { ...baseOptions, scales: scaleOptions, plugins: { ...baseOptions.plugins, legend: { display: false } } },
                    });
                    chartInstances.push(chart);
                }

                const statusCanvas = document.getElementById('statusChart');
                if (statusCanvas) {
                    const statusLabels = ['Pengajuan', 'Sedang Negosiasi', 'Deal', 'Ditolak', 'Revisi'];
                    const chart = new window.Chart(statusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{ data: statusLabels.map((label) => Number((dashboardData.statuses || {})[label] || 0)), backgroundColor: [orange, orangeSoft, orangeDeep, warmGray, darkGray], borderColor: isLightTheme ? '#ffffff' : '#111314', borderWidth: 4, hoverOffset: 5 }],
                        },
                        options: { ...baseOptions, cutout: '68%', plugins: { ...baseOptions.plugins, legend: { position: 'bottom' } } },
                    });
                    chartInstances.push(chart);
                }

                const budgetCanvas = document.getElementById('budgetChart');
                if (budgetCanvas) {
                    const budget = dashboardData.budget || {};
                    const chart = new window.Chart(budgetCanvas, {
                        type: 'bar',
                        data: { labels: ['Total Pagu', 'Terpakai', 'Sisa'], datasets: [{ label: 'Anggaran', data: [budget.pagu || 0, budget.terpakai || 0, budget.sisa || 0], backgroundColor: [warmGray, orange, orangeDeep], borderRadius: 7, maxBarThickness: 52 }] },
                        options: { ...baseOptions, scales: { ...scaleOptions, y: { ...scaleOptions.y, ticks: { ...scaleOptions.y.ticks, callback: (value) => formatRupiah(value) } } }, plugins: { ...baseOptions.plugins, legend: { display: false }, tooltip: { callbacks: { label: (context) => formatRupiah(context.raw) } } } },
                    });
                    chartInstances.push(chart);
                }

                const negotiationCanvas = document.getElementById('negotiationChart');
                if (negotiationCanvas) {
                    const negotiation = dashboardData.negotiation || {};
                    const chart = new window.Chart(negotiationCanvas, {
                        type: 'bar',
                        data: { labels: ['Harga Awal', 'Harga Setelah Negosiasi'], datasets: [{ label: 'Nilai Pengadaan', data: [negotiation.harga_awal || 0, negotiation.harga_negosiasi || 0], backgroundColor: [warmGray, orange], borderRadius: 7, maxBarThickness: 70 }] },
                        options: { ...baseOptions, scales: { ...scaleOptions, y: { ...scaleOptions.y, ticks: { ...scaleOptions.y.ticks, callback: (value) => formatRupiah(value) } } }, plugins: { ...baseOptions.plugins, legend: { display: false }, tooltip: { callbacks: { label: (context) => formatRupiah(context.raw) } } } },
                    });
                    chartInstances.push(chart);
                }
            }

            window.kaurSyncChartTheme = () => {
                const light = document.documentElement.classList.contains('scm-theme-light');
                const text = light ? '#273138' : '#f3f5f6';
                const muted = light ? '#68727b' : '#8f989f';
                const grid = light ? 'rgba(35, 42, 47, .1)' : 'rgba(255, 255, 255, .08)';
                chartInstances.forEach((chart) => {
                    if (chart.options.scales) {
                        Object.values(chart.options.scales).forEach((scale) => {
                            if (scale.ticks) scale.ticks.color = muted;
                            if (scale.grid) scale.grid.color = grid;
                        });
                    }
                    if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = text;
                    if (chart.options.plugins?.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = light ? '#ffffff' : '#181a1b';
                        chart.options.plugins.tooltip.titleColor = text;
                        chart.options.plugins.tooltip.bodyColor = text;
                        chart.options.plugins.tooltip.borderColor = grid;
                    }
                    chart.data.datasets.forEach((dataset) => {
                        if (chart.config.type === 'doughnut') dataset.borderColor = light ? '#ffffff' : '#111314';
                    });
                    chart.update('none');
                });
            };

            window.addEventListener('scm:themechange', window.kaurSyncChartTheme);
            window.kaurSyncChartTheme();
        })();
    </script>
    <script>
        const parseKaurMoney = (value) => Number(String(value || '').replace(/[^0-9]/g, '')) || 0;

        const updateNegotiationTotal = (form) => {
            const price = parseKaurMoney(form.querySelector('.negotiation-price')?.value);
            const volume = Number(form.querySelector('.negotiation-volume')?.value || 0);
            const total = form.querySelector('.negotiation-total');
            if (total) {
                total.value = 'Rp ' + Math.round(price * volume).toLocaleString('id-ID');
            }
        };

        document.querySelectorAll('.money-input').forEach((input) => {
            input.addEventListener('blur', () => {
                const digits = input.value.replace(/[^0-9]/g, '');
                if (!digits) return;
                input.value = 'Rp ' + Number(digits).toLocaleString('id-ID');
                updateNegotiationTotal(input.closest('.negotiation-form'));
            });
        });

        document.querySelectorAll('.negotiation-form').forEach((form) => {
            form.querySelectorAll('.negotiation-price, .negotiation-volume').forEach((input) => {
                input.addEventListener('input', () => updateNegotiationTotal(form));
            });
            updateNegotiationTotal(form);
        });

        document.querySelectorAll('[data-negotiation-group]').forEach((group) => {
            const statusSelect = group.querySelector('.negotiation-group-status');
            if (!statusSelect) return;
            statusSelect.addEventListener('change', () => {
                group.querySelectorAll('.negotiation-item-status').forEach((input) => {
                    input.value = statusSelect.value;
                });
            });
        });
    </script>
    <script>
        (() => {
            const previewArea = document.getElementById('bastPreviewArea');
            const printBtn = document.getElementById('bastPrintBtn');
            const modalEl = document.getElementById('bastGenerateModal');
            if (!previewArea || !modalEl) return;
            const bsModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
            let currentData = null;

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

            const buildBastHeader = (data) => `
                <div style="text-align:right;font-size:.85rem;">No : ${escapeHtml(data.nomor)}</div>
                <h5 style="text-align:center;font-weight:700;letter-spacing:.4px;margin:10px 0 2px;">BERITA ACARA SERAH TERIMA (BAST)</h5>
                <div style="text-align:center;margin-bottom:14px;">(Pengiriman Aset)</div>
                <p style="text-align:justify;">Pada hari ini <b>${escapeHtml(data.hari)}</b> tanggal <b>${escapeHtml(data.tanggalKata)}</b> Bulan <b>${escapeHtml(data.bulanKata)}</b> Tahun <b>${escapeHtml(data.tahunKata)}</b>, bertempat di kantor Universitas Telkom, Jl. Telekomunikasi No. 1 Terusan Buah Batu Bandung, ruangan <b>${escapeHtml(data.ruangan)}</b> telah dilakukan <b>SERAH TERIMA BARANG</b> (SPK No : <b>${escapeHtml(data.spk)}</b>) antara pihak <b>PENCATATAN &amp; PENGELOLAAN ASET</b> dengan <b>${escapeHtml(data.prodi)} - ${escapeHtml(data.kegiatan)}</b>.</p>
                <p style="margin-bottom:6px;">Adapun rincian barang yang diserahterimakan secara lengkap sebagai berikut:</p>
            `;

            const tableOpen = `<table style="width:100%;border-collapse:collapse;margin:10px 0 4px;font-size:.85rem;">
                <thead>
                    <tr>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">No</th>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">Uraian dan Spesifikasi Barang</th>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">Vol</th>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">Sat</th>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">Hasil Serah Terima</th>
                        <th style="border:1px solid #333;padding:6px;font-weight:700;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>`;
            const tableClose = `</tbody></table>`;
            const emptyRow = `<tr><td colspan="6" style="border:1px solid #333;padding:10px;text-align:center;">Belum ada rincian barang.</td></tr>`;

            const parseItems = (data) => {
                try { return JSON.parse(data.items || '[]'); } catch (e) { return []; }
            };

            // Preview interaktif: setiap baris punya radio "Sesuai" / "Tidak Sesuai" yang bisa diklik.
            const buildBastHtml = (data) => {
                const items = parseItems(data);
                const rows = items.map((item, index) => `
                    <tr>
                        <td style="border:1px solid #333;padding:6px;text-align:center;">${index + 1}.</td>
                        <td style="border:1px solid #333;padding:6px;">${escapeHtml(item.uraian)}</td>
                        <td style="border:1px solid #333;padding:6px;text-align:center;">${Number(item.vol || 0).toLocaleString('id-ID')}</td>
                        <td style="border:1px solid #333;padding:6px;text-align:center;">${escapeHtml(item.satuan)}</td>
                        <td style="border:1px solid #333;padding:6px;text-align:center;white-space:nowrap;">
                            <label style="margin-right:10px;cursor:pointer;font-weight:400;">
                                <input type="radio" name="hasil_row_${index}" value="sesuai" checked> Sesuai
                            </label>
                            <label style="cursor:pointer;font-weight:400;">
                                <input type="radio" name="hasil_row_${index}" value="tidak_sesuai"> Tidak Sesuai
                            </label>
                        </td>
                        <td style="border:1px solid #333;padding:6px;"></td>
                    </tr>`).join('') || emptyRow;

                return buildBastHeader(data) + tableOpen + rows + tableClose;
            };

            // Versi statis untuk dicetak: pilihan radio yang sedang aktif di preview
            // dikonversi jadi tanda centang, tanpa elemen interaktif.
            const buildBastPrintHtml = (data) => {
                const items = parseItems(data);
                const rows = items.map((item, index) => {
                    const selected = previewArea.querySelector(`input[name="hasil_row_${index}"]:checked`);
                    const value = selected ? selected.value : '';
                    const sesuaiMark = value === 'sesuai' ? '&#9745;' : '&#9744;';
                    const tidakMark = value === 'tidak_sesuai' ? '&#9745;' : '&#9744;';
                    return `
                        <tr>
                            <td style="border:1px solid #333;padding:6px;text-align:center;">${index + 1}.</td>
                            <td style="border:1px solid #333;padding:6px;">${escapeHtml(item.uraian)}</td>
                            <td style="border:1px solid #333;padding:6px;text-align:center;">${Number(item.vol || 0).toLocaleString('id-ID')}</td>
                            <td style="border:1px solid #333;padding:6px;text-align:center;">${escapeHtml(item.satuan)}</td>
                            <td style="border:1px solid #333;padding:6px;text-align:center;white-space:nowrap;">${sesuaiMark} Sesuai&nbsp;&nbsp;/&nbsp;&nbsp;${tidakMark} Tidak Sesuai</td>
                            <td style="border:1px solid #333;padding:6px;"></td>
                        </tr>`;
                }).join('') || emptyRow;

                return buildBastHeader(data) + tableOpen + rows + tableClose;
            };

            document.querySelectorAll('.btn-generate-bast').forEach((btn) => {
                btn.addEventListener('click', () => {
                    currentData = {
                        nomor: btn.dataset.nomor,
                        spk: btn.dataset.spk,
                        hari: btn.dataset.hari,
                        tanggalKata: btn.dataset.tanggalKata,
                        bulanKata: btn.dataset.bulanKata,
                        tahunKata: btn.dataset.tahunKata,
                        ruangan: btn.dataset.ruangan,
                        prodi: btn.dataset.prodi,
                        kegiatan: btn.dataset.kegiatan,
                        kaurNama: btn.dataset.kaurNama,
                        kaurJabatan: btn.dataset.kaurJabatan,
                        kaprodiNama: btn.dataset.kaprodiNama,
                        items: btn.dataset.items,
                    };
                    previewArea.innerHTML = buildBastHtml(currentData);
                    if (bsModal) bsModal.show();
                });
            });

            if (printBtn) {
                printBtn.addEventListener('click', () => {
                    if (!currentData) return;
                    const printHtml = buildBastPrintHtml(currentData);
                    const printWindow = window.open('', '_blank', 'width=850,height=1000');
                    if (!printWindow) return;
                    printWindow.document.write(`<!DOCTYPE html><html><head><title>BAST</title>
                        <style>body{font-family:'Calibri','Segoe UI',Arial,sans-serif;padding:32px;color:#111;}</style>
                        </head><body>${printHtml}</body></html>`);
                    printWindow.document.close();
                    printWindow.focus();
                    printWindow.onload = () => printWindow.print();
                });
            }
        })();
    </script>
    <script>
        (() => {
            const pageSize = document.querySelector('#kaurSubmissionPageSize, #kaurNegotiationPageSize, #kaurApprovalPageSize, #kaurBastPageSize, #kaurReportPageSize');
            if (!pageSize) return;
            pageSize.addEventListener('change', () => {
                const targetUrl = new URL(window.location.href);
                targetUrl.searchParams.set('page', '1');
                targetUrl.searchParams.set('per_page', pageSize.value);
                window.location.assign(targetUrl.toString());
            });
        })();
    </script>
    <script>
        (() => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
            const input = document.getElementById('kaurLoanLiveSearch');
            const rows = Array.from(document.querySelectorAll('[data-kaur-loan-row]'));
            const empty = document.getElementById('kaurLoanEmptySearch');
            if (!input) return;
            const applyLiveFilter = () => {
                const query = input.value.trim().toLocaleLowerCase('id');
                let approvalVisible = 0;
                rows.forEach((row) => {
                    const show = !query || (row.dataset.search || '').toLocaleLowerCase('id').includes(query);
                    row.classList.toggle('d-none', !show);
                    if (show && row.closest('#approval-peminjaman')) approvalVisible++;
                });
                if (empty) empty.classList.toggle('d-none', approvalVisible > 0);
            };
            input.addEventListener('input', applyLiveFilter);
            applyLiveFilter();
        })();
        (() => {
            const rows = Array.from(document.querySelectorAll('#kaurReturnTableBody [data-kaur-return-row]'));
            const tableBody = document.getElementById('kaurReturnTableBody');
            const select = document.getElementById('kaurReturnPageSize');
            const status = document.getElementById('kaurReturnPageStatus');
            const nav = document.getElementById('kaurReturnPageNav');
            const input = document.getElementById('kaurReturnSearch');
            const reset = document.getElementById('kaurReturnReset');
            const empty = document.getElementById('kaurReturnEmptySearch');
            const total = document.getElementById('kaurReturnTotalItems');
            const sortButtons = Array.from(document.querySelectorAll('[data-kaur-return-sort]'));
            if (!rows.length || !tableBody || !select || !status || !nav || !input || !reset) return;

            let page = 1;
            let sortKey = '';
            let sortDirection = 'asc';
            rows.forEach((row, index) => { row.dataset.originalIndex = String(index); });
            const normalize = (value) => String(value || '').trim().toLocaleLowerCase('id');
            const pageSize = (rowCount) => select.value === 'all' ? Math.max(rowCount, 1) : Number(select.value) || 10;
            const sortValue = (row, key) => row.dataset['sort' + key.charAt(0).toUpperCase() + key.slice(1)] || '';
            const compareRows = (left, right) => {
                if (!sortKey) return Number(left.dataset.originalIndex) - Number(right.dataset.originalIndex);
                let result;
                if (sortKey === 'masa') {
                    result = Number(sortValue(left, sortKey)) - Number(sortValue(right, sortKey));
                } else {
                    result = sortValue(left, sortKey).localeCompare(sortValue(right, sortKey), 'id', { numeric: true, sensitivity: 'base' });
                }
                return sortDirection === 'asc' ? result : -result;
            };
            const addPageButton = (label, target, disabled = false, active = false) => {
                const item = document.createElement('li');
                item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
                const link = document.createElement('a');
                link.className = 'page-link';
                link.href = '#kaurReturnTableBody';
                link.textContent = label;
                link.setAttribute('aria-label', label);
                if (disabled) {
                    link.tabIndex = -1;
                    link.setAttribute('aria-disabled', 'true');
                } else {
                    link.addEventListener('click', (event) => {
                        event.preventDefault();
                        page = target;
                        render();
                    });
                }
                item.appendChild(link);
                nav.appendChild(item);
            };
            const render = () => {
                const query = normalize(input.value);
                const orderedRows = rows.slice().sort(compareRows);
                orderedRows.forEach((row) => tableBody.insertBefore(row, empty || null));
                const filteredRows = orderedRows.filter((row) => !query || normalize(row.dataset.search).includes(query));
                const size = pageSize(filteredRows.length);
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / size));
                page = Math.min(page, totalPages);
                rows.forEach((row) => { row.hidden = true; });
                filteredRows.forEach((row, index) => {
                    row.hidden = index < (page - 1) * size || index >= page * size;
                });
                if (empty) empty.hidden = filteredRows.length > 0;
                if (total) total.textContent = String(filteredRows.length);
                status.textContent = 'Halaman: ' + page + ' dari ' + totalPages;
                nav.innerHTML = '';
                addPageButton('Previous', Math.max(1, page - 1), page === 1);
                for (let index = 1; index <= totalPages; index += 1) {
                    addPageButton(String(index), index, false, index === page);
                }
                addPageButton('Next', Math.min(totalPages, page + 1), page === totalPages);
                sortButtons.forEach((button) => {
                    const active = button.dataset.kaurReturnSort === sortKey;
                    button.classList.toggle('is-active', active);
                    const icon = button.querySelector('i');
                    if (icon) icon.className = 'bi ' + (active ? (sortDirection === 'asc' ? 'bi-sort-up-alt' : 'bi-sort-down') : 'bi-arrow-down-up');
                    const header = button.closest('th');
                    if (header) header.setAttribute('aria-sort', active ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
                });
            };

            select.addEventListener('change', () => {
                page = 1;
                render();
            });
            input.addEventListener('input', () => {
                page = 1;
                render();
            });
            reset.addEventListener('click', () => {
                input.value = '';
                sortKey = '';
                sortDirection = 'asc';
                page = 1;
                render();
                input.focus();
            });
            sortButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextKey = button.dataset.kaurReturnSort || '';
                    if (sortKey === nextKey) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortKey = nextKey;
                        sortDirection = 'asc';
                    }
                    page = 1;
                    render();
                });
            });
            render();
        })();
    </script>
</body>
</html>
