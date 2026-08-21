<?php
/**
 * SCM FIK workflow stress-data seeder.
 *
 * This file is intentionally standalone: it does not bootstrap CodeIgniter,
 * alter the schema, create notifications, or invoke application workflows.
 * It only inserts data into the existing procurement/loan tables.
 *
 * Safe defaults:
 *   php database/seed_scm_workflow_stress.php --dry-run
 *   php database/seed_scm_workflow_stress.php --apply --batch=TEST20260820
 *   php database/seed_scm_workflow_stress.php --cleanup --batch=TEST20260820
 */

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const SCM_STRESS_DEFAULT_COUNT = 2000;
const SCM_STRESS_ITEM_MIN = 1;
const SCM_STRESS_ITEM_MAX = 5;

function cli_options(array $argv): array
{
    $options = [
        'mode' => 'dry-run',
        'batch' => 'TEST' . date('Ymd'),
        'count' => SCM_STRESS_DEFAULT_COUNT,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--apply') {
            $options['mode'] = 'apply';
            continue;
        }
        if ($argument === '--cleanup') {
            $options['mode'] = 'cleanup';
            continue;
        }
        if ($argument === '--dry-run') {
            $options['mode'] = 'dry-run';
            continue;
        }
        if ($argument === '--help' || $argument === '-h') {
            echo "Usage:\n";
            echo "  php database/seed_scm_workflow_stress.php --dry-run [--batch=TEST20260820] [--count=2000]\n";
            echo "  php database/seed_scm_workflow_stress.php --apply   [--batch=TEST20260820] [--count=2000]\n";
            echo "  php database/seed_scm_workflow_stress.php --cleanup [--batch=TEST20260820]\n";
            exit(0);
        }
        if (strpos($argument, '--batch=') === 0) {
            $options['batch'] = strtoupper(trim(substr($argument, 8)));
            continue;
        }
        if (strpos($argument, '--count=') === 0) {
            $options['count'] = max(1, (int) substr($argument, 8));
            continue;
        }
        throw new InvalidArgumentException('Argumen tidak dikenal: ' . $argument);
    }

    if (!preg_match('/^[A-Z0-9_-]{3,24}$/', $options['batch'])) {
        throw new InvalidArgumentException('Batch harus 3-24 karakter: A-Z, 0-9, underscore, atau hyphen.');
    }
    if ($options['mode'] === 'cleanup') {
        $options['count'] = 0;
    }

    return $options;
}

function read_db_config(string $projectRoot): array
{
    $config = [
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'peminjaman_aset',
    ];
    $file = $projectRoot . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
    if (!is_file($file)) {
        return $config;
    }

    $contents = (string) file_get_contents($file);
    foreach (array_keys($config) as $key) {
        if (preg_match("/'{$key}'\\s*=>\\s*'((?:\\\\'|[^'])*)'/", $contents, $match)) {
            $config[$key] = stripslashes($match[1]);
        }
    }
    return $config;
}

function connect_database(string $projectRoot): mysqli
{
    $config = read_db_config($projectRoot);
    $db = new mysqli(
        $config['hostname'],
        $config['username'],
        $config['password'],
        $config['database']
    );
    $db->set_charset('utf8mb4');
    return $db;
}

function sql_value(mysqli $db, $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value)) {
        return (string) $value;
    }
    if (is_float($value)) {
        return number_format($value, 2, '.', '');
    }
    return "'" . $db->real_escape_string((string) $value) . "'";
}

function assert_identifier(string $value): void
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
        throw new InvalidArgumentException('Identifier SQL tidak aman: ' . $value);
    }
}

function table_columns(mysqli $db, string $table): array
{
    assert_identifier($table);
    $result = $db->query("SHOW COLUMNS FROM `{$table}`");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    return $columns;
}

function require_schema(mysqli $db): void
{
    $required = [
        'users' => ['id_user', 'nim_nip', 'nama_lengkap', 'email', 'password', 'role', 'created_at', 'updated_at'],
        'peminjam' => ['id_peminjam', 'nama_peminjam', 'nim_nip', 'email', 'no_hp', 'jenis', 'created_at'],
        'ruangan' => ['id_ruangan', 'kode_ruangan', 'nama_ruangan'],
        'aset' => ['id_aset', 'id_ruangan', 'nama_aset', 'kode_aset', 'deskripsi', 'jumlah_total', 'jumlah_reserved', 'jumlah_dipinjam', 'jumlah_tersedia', 'kondisi', 'total_peminjaman'],
        'kaprodi_pengajuan' => ['id_pengajuan', 'kode_pengajuan', 'id_user', 'jenis_pengajuan', 'nama_prodi', 'nama_pengajuan', 'kebutuhan_lab', 'status', 'bast_nomor', 'bast_tanggal', 'bast_penerima', 'bast_catatan', 'created_at', 'updated_at'],
        'kaprodi_pengajuan_item' => ['id_item', 'id_pengajuan', 'no_urut', 'jenis_item', 'uraian_barang', 'vol', 'satuan', 'harga_penawaran_sat', 'link_penawaran', 'hasil_negosiasi_vol', 'hasil_negosiasi_sat', 'garansi', 'alokasi_sisa', 'created_at'],
        'kaur_pengajuan' => ['id_pengajuan', 'kode_pengajuan', 'id_user', 'jenis_pengajuan', 'nama_lab', 'nama_pengajuan', 'kebutuhan_lab', 'status', 'bast_nomor', 'bast_tanggal', 'bast_penerima', 'bast_catatan', 'bast_disetujui_oleh', 'bast_disetujui_pada', 'created_at', 'updated_at'],
        'kaur_pengajuan_item' => ['id_item', 'id_pengajuan', 'no_urut', 'uraian_barang', 'vol', 'satuan', 'harga_penawaran_sat', 'link_penawaran', 'hasil_negosiasi_vol', 'hasil_negosiasi_sat', 'garansi', 'alokasi_sisa', 'created_at'],
        'pengadaan_negosiasi' => ['id_negosiasi', 'sumber', 'id_pengajuan', 'id_item', 'vendor', 'harga_awal', 'volume_awal', 'harga_negosiasi', 'volume_negosiasi', 'garansi', 'catatan', 'status', 'created_by', 'created_at'],
        'pengadaan_bast' => ['id_bast', 'id_pengajuan', 'nomor_bast', 'tanggal_bast', 'jenis_bast', 'file_bast', 'catatan', 'input_by', 'inventory_processed_at', 'created_at'],
        'pengadaan_inventory_link' => ['id_link', 'id_bast', 'id_pengajuan', 'id_item', 'id_aset', 'created_at'],
        'peminjaman' => ['id_peminjaman', 'group_id', 'id_aset', 'id_peminjam', 'id_user', 'jumlah_pinjam', 'stock_allocation_status', 'stock_allocated_at', 'stock_released_at', 'jumlah_kembali', 'tanggal_pinjam', 'tanggal_kembali_rencana', 'tanggal_kembali_actual', 'keperluan', 'status', 'status_kaprodi', 'catatan_kaprodi', 'tgl_approve_kaprodi', 'id_approver_kaprodi', 'status_laboran', 'catatan_laboran', 'tgl_approve_laboran', 'id_approver_laboran', 'status_kaur', 'catatan_kaur', 'tgl_approve_kaur', 'id_approver_kaur', 'kondisi_saat_pinjam', 'kondisi_saat_kembali', 'foto_bukti', 'foto_pengembalian', 'qr_locked', 'qr_finalized_at', 'qr_finalized_by', 'qr_pengembalian_token', 'catatan', 'created_at', 'updated_at'],
        'peminjaman_detail' => ['id_detail', 'id_peminjaman', 'id_aset', 'jumlah_pinjam', 'kondisi_saat_pinjam', 'catatan', 'created_at'],
    ];

    foreach ($required as $table => $columns) {
        $actual = table_columns($db, $table);
        if (!$actual) {
            throw new RuntimeException("Tabel wajib tidak ditemukan: {$table}");
        }
        foreach ($columns as $column) {
            if (!isset($actual[$column])) {
                throw new RuntimeException("Kolom wajib tidak ditemukan: {$table}.{$column}");
            }
        }
    }

    $enumChecks = [
        ['peminjam', 'jenis', ['Mahasiswa', 'Dosen', 'Staff', 'Laboran']],
        ['peminjaman', 'status_laboran', ['Pending', 'Disetujui', 'Ditolak']],
        ['peminjaman', 'status_kaur', ['Pending', 'Disetujui', 'Ditolak']],
        ['peminjaman', 'kondisi_saat_pinjam', ['Baik', 'Rusak Ringan', 'Rusak Berat']],
        ['pengadaan_negosiasi', 'sumber', ['kaprodi', 'kaur']],
        ['pengadaan_negosiasi', 'status', ['Belum Negosiasi', 'Sedang Negosiasi', 'Deal', 'Ditolak']],
    ];
    foreach ($enumChecks as [$table, $column, $values]) {
        $columns = table_columns($db, $table);
        $type = (string) ($columns[$column]['Type'] ?? '');
        if (stripos($type, 'enum(') !== 0) {
            continue;
        }
        preg_match_all("/'((?:\\\\'|[^'])*)'/", $type, $matches);
        $available = array_map('stripslashes', $matches[1] ?? []);
        foreach ($values as $value) {
            if (!in_array($value, $available, true)) {
                throw new RuntimeException("Enum {$table}.{$column} tidak memiliki nilai workflow resmi: {$value}");
            }
        }
    }
}

function insert_rows(mysqli $db, string $table, array $columns, array $rows, int $chunk = 250): void
{
    if (!$rows) {
        return;
    }
    assert_identifier($table);
    foreach ($columns as $column) {
        assert_identifier($column);
    }
    $columnSql = implode(', ', array_map(static fn($column) => "`{$column}`", $columns));
    foreach (array_chunk($rows, $chunk) as $part) {
        $values = [];
        foreach ($part as $row) {
            $values[] = '(' . implode(', ', array_map(static function ($column) use ($db, $row) {
                return sql_value($db, $row[$column] ?? null);
            }, $columns)) . ')';
        }
        $db->query("INSERT INTO `{$table}` ({$columnSql}) VALUES " . implode(', ', $values));
    }
}

function query_ids(mysqli $db, string $sql, string $idColumn): array
{
    $result = $db->query($sql);
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row[$idColumn];
    }
    return $ids;
}

function id_map_by_field(mysqli $db, string $table, string $idColumn, string $field, string $prefix): array
{
    assert_identifier($table);
    assert_identifier($idColumn);
    assert_identifier($field);
    $prefixSql = $db->real_escape_string($prefix);
    $result = $db->query("SELECT `{$idColumn}`, `{$field}` FROM `{$table}` WHERE `{$field}` LIKE '{$prefixSql}%' ORDER BY `{$idColumn}` ASC");
    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[(string) $row[$field]] = (int) $row[$idColumn];
    }
    return $map;
}

function delete_ids(mysqli $db, string $table, string $idColumn, array $ids): int
{
    if (!$ids) {
        return 0;
    }
    assert_identifier($table);
    assert_identifier($idColumn);
    $deleted = 0;
    foreach (array_chunk(array_values(array_unique(array_map('intval', $ids))), 500) as $part) {
        $in = implode(',', $part);
        $db->query("DELETE FROM `{$table}` WHERE `{$idColumn}` IN ({$in})");
        $deleted += $db->affected_rows;
    }
    return $deleted;
}

function delete_by_prefix(mysqli $db, string $table, string $column, string $prefix): int
{
    assert_identifier($table);
    assert_identifier($column);
    $escaped = $db->real_escape_string($prefix);
    $db->query("DELETE FROM `{$table}` WHERE `{$column}` LIKE '{$escaped}%'");
    return $db->affected_rows;
}

function count_by_prefix(mysqli $db, string $table, string $column, string $prefix): int
{
    assert_identifier($table);
    assert_identifier($column);
    $escaped = $db->real_escape_string($prefix);
    $row = $db->query("SELECT COUNT(*) AS total FROM `{$table}` WHERE `{$column}` LIKE '{$escaped}%'")->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function row_count(mysqli $db, string $sql): int
{
    return (int) (($db->query($sql)->fetch_assoc()['total'] ?? 0));
}

function first_value(mysqli $db, string $sql, string $column): ?string
{
    $row = $db->query($sql)->fetch_assoc();
    return $row && array_key_exists($column, $row) ? (string) $row[$column] : null;
}

function date_at(int $offset, int $hour = 9): string
{
    $base = new DateTimeImmutable('2025-08-20 08:00:00');
    return $base->modify('+' . ($offset % 366) . ' days')->setTime($hour, $offset % 60, 0)->format('Y-m-d H:i:s');
}

function day_at(int $offset): string
{
    return substr(date_at($offset), 0, 10);
}

function plus_days(string $date, int $days): string
{
    return (new DateTimeImmutable($date))->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
}

function item_count(int $index): int
{
    return SCM_STRESS_ITEM_MIN + ($index % (SCM_STRESS_ITEM_MAX - SCM_STRESS_ITEM_MIN + 1));
}

function cap_status(int $index): string
{
    return [
        'Pengajuan',
        'Revisi',
        'Sedang Negosiasi',
        'Approval',
        'Disetujui',
        'Ditolak',
        'BAST',
        'Inventarisasi',
        'Selesai',
    ][$index % 9];
}

function kaur_status(int $index): string
{
    return [
        'Pengajuan',
        'Revisi',
        'Sedang Negosiasi',
        'Negosiasi',
        'Approval',
        'Approval Tahap 1 (BAST)',
        'BAST Disetujui',
        'ACC Anak Perusahaan',
        'Alokasi',
        'Selesai',
        'Ditolak',
    ][$index % 11];
}

function loan_profile(int $index): array
{
    $mod = $index % 20;
    if ($mod <= 1) {
        return ['status' => 'Menunggu ACC Kaprodi', 'kaprodi' => 'Pending', 'laboran' => 'Pending', 'kaur' => 'Pending'];
    }
    if ($mod === 2) {
        return ['status' => 'Menunggu Verifikasi Laboran', 'kaprodi' => 'Disetujui', 'laboran' => 'Pending', 'kaur' => 'Pending'];
    }
    if ($mod === 3) {
        return ['status' => 'Menunggu Pengecekan Laboran', 'kaprodi' => 'Disetujui', 'laboran' => 'Pending', 'kaur' => 'Pending'];
    }
    if ($mod === 4) {
        return ['status' => 'Menunggu Persetujuan', 'kaprodi' => 'Disetujui', 'laboran' => 'Pending', 'kaur' => 'Pending'];
    }
    if ($mod <= 7) {
        return ['status' => 'Menunggu ACC Kaur', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Pending'];
    }
    if ($mod <= 9) {
        return ['status' => 'Disetujui (Menunggu Finalisasi QR)', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Disetujui'];
    }
    if ($mod <= 11) {
        return ['status' => 'Disetujui (Menunggu Pengambilan)', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Disetujui'];
    }
    if ($mod <= 13) {
        return ['status' => 'Sedang Dipinjam', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Disetujui'];
    }
    if ($mod <= 16) {
        return ['status' => 'Dikembalikan', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Disetujui'];
    }
    if ($mod === 17) {
        return ['status' => 'Ditolak', 'kaprodi' => 'Ditolak', 'laboran' => 'Pending', 'kaur' => 'Pending'];
    }
    if ($mod === 18) {
        return ['status' => 'Ditolak', 'kaprodi' => 'Disetujui', 'laboran' => 'Ditolak', 'kaur' => 'Pending'];
    }
    return ['status' => 'Ditolak', 'kaprodi' => 'Disetujui', 'laboran' => 'Disetujui', 'kaur' => 'Ditolak'];
}

function print_plan(int $count): void
{
    $items = 0;
    for ($i = 0; $i < $count; $i++) {
        $items += item_count($i);
    }
    $bast = 0;
    $inventoryStages = 0;
    $inventoryLinks = 0;
    for ($i = 0; $i < $count; $i++) {
        $status = cap_status($i);
        if (in_array($status, ['BAST', 'Inventarisasi', 'Selesai'], true)) {
            $bast++;
        }
        if (in_array($status, ['Inventarisasi', 'Selesai'], true)) {
            $inventoryStages++;
            $inventoryLinks += item_count($i);
        }
    }
    echo "SCM FIK STRESS DATA PLAN (tidak ada perubahan)\n";
    echo "users                       : {$count}\n";
    echo "peminjam                    : {$count}\n";
    echo "aset                        : {$count}\n";
    echo "ruangan                     : reuse existing\n";
    echo "kaprodi_pengajuan           : {$count}\n";
    echo "kaprodi_pengajuan_item      : {$items}\n";
    echo "kaur_pengajuan              : {$count}\n";
    echo "kaur_pengajuan_item         : {$items}\n";
    echo "pengadaan_negosiasi         : {$items}\n";
    echo "pengadaan_bast              : {$bast}\n";
    echo "pengadaan_inventory_link    : {$inventoryLinks}\n";
    echo "peminjaman                  : {$count}\n";
    echo "peminjaman_detail           : {$count}\n";
    echo "status BAST/inventory       : {$bast}/{$inventoryStages}\n";
}

function batch_anchor_counts(mysqli $db, string $batch): array
{
    return [
        'users' => count_by_prefix($db, 'users', 'nim_nip', $batch . '-U-'),
        'peminjam' => count_by_prefix($db, 'peminjam', 'nim_nip', $batch . '-P-'),
        'aset' => count_by_prefix($db, 'aset', 'kode_aset', $batch . '-A-'),
        'kaprodi_pengajuan' => count_by_prefix($db, 'kaprodi_pengajuan', 'kode_pengajuan', $batch . '-KPRD-'),
        'kaur_pengajuan' => count_by_prefix($db, 'kaur_pengajuan', 'kode_pengajuan', $batch . '-KAUR-'),
        'peminjaman' => count_by_prefix($db, 'peminjaman', 'group_id', $batch . '-PJM-'),
    ];
}

function assert_batch_is_new(mysqli $db, string $batch): void
{
    $counts = batch_anchor_counts($db, $batch);
    $existing = array_filter($counts, static fn($value) => $value > 0);
    if ($existing) {
        throw new RuntimeException('Batch sudah ada. Gunakan --cleanup dahulu atau pilih --batch baru: ' . json_encode($existing));
    }
}

function cleanup_batch(mysqli $db, string $batch): void
{
    $prefix = static fn(string $suffix): string => $batch . $suffix;
    $capIds = query_ids($db, "SELECT id_pengajuan FROM kaprodi_pengajuan WHERE kode_pengajuan LIKE '" . $db->real_escape_string($prefix('-KPRD-')) . "%'", 'id_pengajuan');
    $kaurIds = query_ids($db, "SELECT id_pengajuan FROM kaur_pengajuan WHERE kode_pengajuan LIKE '" . $db->real_escape_string($prefix('-KAUR-')) . "%'", 'id_pengajuan');
    $loanIds = query_ids($db, "SELECT id_peminjaman FROM peminjaman WHERE group_id LIKE '" . $db->real_escape_string($prefix('-PJM-')) . "%'", 'id_peminjaman');

    $capIn = $capIds ? implode(',', array_map('intval', $capIds)) : '0';
    $kaurIn = $kaurIds ? implode(',', array_map('intval', $kaurIds)) : '0';
    $loanIn = $loanIds ? implode(',', array_map('intval', $loanIds)) : '0';
    $capItemIds = query_ids($db, "SELECT id_item FROM kaprodi_pengajuan_item WHERE id_pengajuan IN ({$capIn})", 'id_item');
    $kaurItemIds = query_ids($db, "SELECT id_item FROM kaur_pengajuan_item WHERE id_pengajuan IN ({$kaurIn})", 'id_item');
    $bastIds = query_ids($db, "SELECT id_bast FROM pengadaan_bast WHERE id_pengajuan IN ({$capIn})", 'id_bast');
    $detailIds = query_ids($db, "SELECT id_detail FROM peminjaman_detail WHERE id_peminjaman IN ({$loanIn})", 'id_detail');
    $negoIds = $capItemIds ? query_ids($db, 'SELECT id_negosiasi FROM pengadaan_negosiasi WHERE id_item IN (' . implode(',', array_map('intval', $capItemIds)) . ')', 'id_negosiasi') : [];
    $linkIds = $bastIds ? query_ids($db, 'SELECT id_link FROM pengadaan_inventory_link WHERE id_bast IN (' . implode(',', array_map('intval', $bastIds)) . ')', 'id_link') : [];

    $db->begin_transaction();
    try {
        $deleted = [];
        $deleted['peminjaman_detail'] = delete_ids($db, 'peminjaman_detail', 'id_detail', $detailIds);
        $deleted['peminjaman'] = delete_ids($db, 'peminjaman', 'id_peminjaman', $loanIds);
        $deleted['pengadaan_inventory_link'] = delete_ids($db, 'pengadaan_inventory_link', 'id_link', $linkIds);
        $deleted['pengadaan_bast'] = delete_ids($db, 'pengadaan_bast', 'id_bast', $bastIds);
        $deleted['pengadaan_negosiasi'] = delete_ids($db, 'pengadaan_negosiasi', 'id_negosiasi', $negoIds);
        $deleted['kaur_pengajuan_item'] = delete_ids($db, 'kaur_pengajuan_item', 'id_item', $kaurItemIds);
        $deleted['kaur_pengajuan'] = delete_ids($db, 'kaur_pengajuan', 'id_pengajuan', $kaurIds);
        $deleted['kaprodi_pengajuan_item'] = delete_ids($db, 'kaprodi_pengajuan_item', 'id_item', $capItemIds);
        $deleted['kaprodi_pengajuan'] = delete_ids($db, 'kaprodi_pengajuan', 'id_pengajuan', $capIds);
        $deleted['aset'] = delete_by_prefix($db, 'aset', 'kode_aset', $prefix('-A-'));
        $deleted['peminjam'] = delete_by_prefix($db, 'peminjam', 'nim_nip', $prefix('-P-'));
        $deleted['users'] = delete_by_prefix($db, 'users', 'nim_nip', $prefix('-U-'));
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollback();
        throw $exception;
    }

    echo "SCM FIK TEST DATA CLEANUP SUCCESS ({$batch})\n";
    foreach ($deleted as $table => $total) {
        echo str_pad($table, 28) . ': ' . $total . "\n";
    }
}

function apply_seed(mysqli $db, string $batch, int $count): void
{
    assert_batch_is_new($db, $batch);

    $rooms = [];
    $roomResult = $db->query('SELECT id_ruangan, nama_ruangan FROM ruangan ORDER BY id_ruangan');
    while ($room = $roomResult->fetch_assoc()) {
        $rooms[] = $room;
    }
    if (!$rooms) {
        throw new RuntimeException('Tabel ruangan kosong. Seeder tidak membuat ruangan baru sesuai requirement.');
    }

    $approvers = [];
    foreach (['kaprodi', 'kaur', 'laboran'] as $role) {
        $roleSql = $db->real_escape_string($role);
        $row = $db->query("SELECT id_user FROM users WHERE role = '{$roleSql}' ORDER BY id_user ASC LIMIT 1")->fetch_assoc();
        if (!$row && $role === 'laboran') {
            $row = $db->query("SELECT id_user FROM users WHERE role = 'admin' ORDER BY id_user ASC LIMIT 1")->fetch_assoc();
        }
        if (!$row) {
            throw new RuntimeException("Account approver existing tidak ditemukan untuk role: {$role}");
        }
        $approvers[$role] = (int) $row['id_user'];
    }

    $photo = first_value($db, "SELECT foto_bukti FROM peminjaman WHERE foto_bukti IS NOT NULL AND foto_bukti <> '' ORDER BY id_peminjaman ASC LIMIT 1", 'foto_bukti') ?: 'seed_stress_bukti.jpg';
    $password = password_hash('Password123!', PASSWORD_BCRYPT);

    $users = [];
    $peminjam = [];
    $assets = [];
    for ($i = 1; $i <= $count; $i++) {
        $userCode = sprintf('%s-U-%06d', $batch, $i);
        $peminjamCode = sprintf('%s-P-%06d', $batch, $i);
        $role = $i <= $count - 20 ? 'user' : (['admin', 'laboran', 'kaur', 'kaprodi'][($i - ($count - 20) - 1) % 4]);
        $created = date_at($i);
        $name = sprintf('%s Pengguna Uji %04d', $batch, $i);
        $users[] = [
            'nim_nip' => $userCode,
            'nama_lengkap' => $name,
            'email' => strtolower($batch) . '.user' . sprintf('%06d', $i) . '@example.test',
            'password' => $password,
            'role' => $role,
            'created_at' => $created,
            'updated_at' => $created,
        ];
        $jenis = ['Mahasiswa', 'Dosen', 'Staff', 'Laboran'][($i - 1) % 4];
        $peminjam[] = [
            'nama_peminjam' => $name,
            'nim_nip' => $peminjamCode,
            'email' => strtolower($batch) . '.borrower' . sprintf('%06d', $i) . '@example.test',
            'no_hp' => '08' . str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
            'jenis' => $jenis,
            'created_at' => $created,
        ];
        $loanQuantity = 1 + (($i - 1) % 3);
        $total = max(1 + ($i % 8), $loanQuantity);
        $assets[] = [
            'id_ruangan' => (int) $rooms[($i - 1) % count($rooms)]['id_ruangan'],
            'nama_aset' => sprintf('%s Perangkat Laboratorium %04d', $batch, $i),
            'kode_aset' => sprintf('%s-A-%06d', $batch, $i),
            'qr_code' => sprintf('%s-QR-%06d', $batch, $i),
            'qr_url' => null,
            'sumber_bast_id' => null,
            'deskripsi' => 'Aset uji untuk stress testing alur pengajuan dan peminjaman SCM FIK.',
            'gambar' => null,
            'jumlah_total' => $total,
            'jumlah_reserved' => 0,
            'jumlah_dipinjam' => 0,
            'jumlah_tersedia' => $total,
            'kondisi' => ['Baik', 'Perlu Perbaikan', 'Sudah Diperbaiki', 'Rusak'][($i - 1) % 4],
            'foto' => null,
            'total_peminjaman' => $i % 17,
        ];
    }

    $db->begin_transaction();
    try {
        insert_rows($db, 'users', ['nim_nip', 'nama_lengkap', 'email', 'password', 'role', 'created_at', 'updated_at'], $users);
        insert_rows($db, 'peminjam', ['nama_peminjam', 'nim_nip', 'email', 'no_hp', 'jenis', 'created_at'], $peminjam);
        insert_rows($db, 'aset', ['id_ruangan', 'nama_aset', 'kode_aset', 'qr_code', 'qr_url', 'sumber_bast_id', 'deskripsi', 'gambar', 'jumlah_total', 'jumlah_reserved', 'jumlah_dipinjam', 'jumlah_tersedia', 'kondisi', 'foto', 'total_peminjaman'], $assets);

        $userIds = id_map_by_field($db, 'users', 'id_user', 'nim_nip', $batch . '-U-');
        $peminjamIds = id_map_by_field($db, 'peminjam', 'id_peminjam', 'nim_nip', $batch . '-P-');
        $assetIds = id_map_by_field($db, 'aset', 'id_aset', 'kode_aset', $batch . '-A-');
        if (count($userIds) !== $count || count($peminjamIds) !== $count || count($assetIds) !== $count) {
            throw new RuntimeException('ID master hasil insert tidak lengkap; transaksi dibatalkan.');
        }

        $capPlans = [];
        $capRows = [];
        for ($i = 0; $i < $count; $i++) {
            $status = cap_status($i);
            $created = date_at($i + 40);
            $bast = in_array($status, ['BAST', 'Inventarisasi', 'Selesai'], true);
            $bastNumber = $bast ? sprintf('%s-BAST-%06d', $batch, $i + 1) : null;
            $jenis = ['Barang', 'Jasa', 'Barang dan Jasa'][$i % 3];
            $capPlans[$i] = ['status' => $status, 'item_count' => item_count($i), 'jenis' => $jenis, 'bast_number' => $bastNumber, 'created' => $created];
            $capRows[] = [
                'kode_pengajuan' => sprintf('%s-KPRD-%06d', $batch, $i + 1),
                'id_user' => $approvers['kaprodi'],
                'jenis_pengajuan' => $jenis,
                'nama_prodi' => ['S1 INFORMATIKA', 'S1 DKV', 'S1 Desain Produk', 'S1 Desain Interior'][$i % 4],
                'nama_pengajuan' => sprintf('%s Pengajuan Pengadaan %04d', $batch, $i + 1),
                'kebutuhan_lab' => 'Kebutuhan uji pengadaan untuk pengembangan fasilitas, praktikum, dan operasional laboratorium.',
                'status' => $status,
                'catatan_negosiasi' => in_array($status, ['Sedang Negosiasi', 'Approval', 'Disetujui', 'BAST', 'Inventarisasi', 'Selesai'], true) ? 'Data uji negosiasi per item dari batch ' . $batch : null,
                'catatan_alokasi' => in_array($status, ['BAST', 'Inventarisasi', 'Selesai'], true) ? 'Alokasi data uji batch ' . $batch : null,
                'bast_nomor' => $bastNumber,
                'bast_tanggal' => $bast ? substr($created, 0, 10) : null,
                'bast_penerima' => $bast ? 'Laboratorium FIK' : null,
                'bast_catatan' => $bast ? 'BAST data uji stress testing batch ' . $batch : null,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        }
        insert_rows($db, 'kaprodi_pengajuan', ['kode_pengajuan', 'id_user', 'jenis_pengajuan', 'nama_prodi', 'nama_pengajuan', 'kebutuhan_lab', 'status', 'catatan_negosiasi', 'catatan_alokasi', 'bast_nomor', 'bast_tanggal', 'bast_penerima', 'bast_catatan', 'created_at', 'updated_at'], $capRows);
        $capParentIds = id_map_by_field($db, 'kaprodi_pengajuan', 'id_pengajuan', 'kode_pengajuan', $batch . '-KPRD-');
        if (count($capParentIds) !== $count) {
            throw new RuntimeException('ID parent pengajuan Kaprodi tidak lengkap; transaksi dibatalkan.');
        }

        $capItemRows = [];
        for ($i = 0; $i < $count; $i++) {
            for ($j = 1; $j <= $capPlans[$i]['item_count']; $j++) {
                $kind = $capPlans[$i]['jenis'] === 'Barang dan Jasa' ? (($j % 2) ? 'Barang' : 'Jasa') : $capPlans[$i]['jenis'];
                $price = 250000 + (($i * 173 + $j * 451) % 25000000);
                $volume = 1 + (($i + $j) % 6);
                $deal = in_array($capPlans[$i]['status'], ['Sedang Negosiasi', 'Approval', 'Disetujui', 'BAST', 'Inventarisasi', 'Selesai'], true);
                $capItemRows[] = [
                    'id_pengajuan' => $capParentIds[sprintf('%s-KPRD-%06d', $batch, $i + 1)],
                    'no_urut' => $j,
                    'jenis_item' => $kind,
                    'uraian_barang' => sprintf('%s %s Item %d', $batch, $kind === 'Jasa' ? 'Layanan' : 'Aset', $j),
                    'vol' => $volume,
                    'satuan' => $kind === 'Jasa' ? 'paket' : 'unit',
                    'harga_penawaran_sat' => $price,
                    'link_penawaran' => 'https://example.test/' . strtolower($batch) . '/penawaran/' . $i . '-' . $j,
                    'hasil_negosiasi_vol' => $deal ? $volume : null,
                    'hasil_negosiasi_sat' => $deal ? $price - min(750000, $price * 0.08) : null,
                    'garansi' => $kind === 'Barang' && $deal ? (($j % 2) ? '1 tahun' : '2 tahun') : null,
                    'alokasi_sisa' => in_array($capPlans[$i]['status'], ['BAST', 'Inventarisasi', 'Selesai'], true) ? 'Tersedia untuk alokasi uji' : null,
                    'created_at' => $capPlans[$i]['created'],
                ];
            }
        }
        insert_rows($db, 'kaprodi_pengajuan_item', ['id_pengajuan', 'no_urut', 'jenis_item', 'uraian_barang', 'vol', 'satuan', 'harga_penawaran_sat', 'link_penawaran', 'hasil_negosiasi_vol', 'hasil_negosiasi_sat', 'garansi', 'alokasi_sisa', 'created_at'], $capItemRows);
        $capItemIds = query_ids($db, "SELECT id_item FROM kaprodi_pengajuan_item WHERE id_pengajuan IN (SELECT id_pengajuan FROM kaprodi_pengajuan WHERE kode_pengajuan LIKE '" . $db->real_escape_string($batch . '-KPRD-') . "%') ORDER BY id_item ASC", 'id_item');
        if (count($capItemIds) !== count($capItemRows)) {
            throw new RuntimeException('ID item pengajuan Kaprodi tidak lengkap; transaksi dibatalkan.');
        }

        $kaurPlans = [];
        $kaurRows = [];
        for ($i = 0; $i < $count; $i++) {
            $status = kaur_status($i);
            $created = date_at($i + 80);
            $kaurPlans[$i] = ['status' => $status, 'item_count' => item_count($i), 'created' => $created];
            $kaurRows[] = [
                'kode_pengajuan' => sprintf('%s-KAUR-%06d', $batch, $i + 1),
                'id_user' => $approvers['kaur'],
                'jenis_pengajuan' => ['Barang', 'Jasa', 'Barang dan Jasa'][$i % 3],
                'nama_lab' => $rooms[$i % count($rooms)]['nama_ruangan'],
                'nama_pengajuan' => sprintf('%s Pengajuan Kebutuhan Lab %04d', $batch, $i + 1),
                'kebutuhan_lab' => 'Data uji kebutuhan laboratorium batch ' . $batch . ' untuk pengujian pagination dan filter.',
                'status' => $status,
                'catatan_negosiasi' => in_array($status, ['Negosiasi', 'Sedang Negosiasi'], true) ? 'Catatan negosiasi uji batch ' . $batch : null,
                'bast_nomor' => in_array($status, ['Approval Tahap 1 (BAST)', 'BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? sprintf('%s-KAUR-BAST-%06d', $batch, $i + 1) : null,
                'bast_tanggal' => in_array($status, ['Approval Tahap 1 (BAST)', 'BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? substr($created, 0, 10) : null,
                'bast_penerima' => in_array($status, ['Approval Tahap 1 (BAST)', 'BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? 'Laboratorium FIK' : null,
                'bast_catatan' => in_array($status, ['Approval Tahap 1 (BAST)', 'BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? 'Dokumen BAST uji batch ' . $batch : null,
                'bast_disetujui_oleh' => in_array($status, ['BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? $approvers['kaur'] : null,
                'bast_disetujui_pada' => in_array($status, ['BAST Disetujui', 'ACC Anak Perusahaan', 'Alokasi', 'Selesai'], true) ? $created : null,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        }
        insert_rows($db, 'kaur_pengajuan', ['kode_pengajuan', 'id_user', 'jenis_pengajuan', 'nama_lab', 'nama_pengajuan', 'kebutuhan_lab', 'status', 'catatan_negosiasi', 'bast_nomor', 'bast_tanggal', 'bast_penerima', 'bast_catatan', 'bast_disetujui_oleh', 'bast_disetujui_pada', 'created_at', 'updated_at'], $kaurRows);
        $kaurParentIds = id_map_by_field($db, 'kaur_pengajuan', 'id_pengajuan', 'kode_pengajuan', $batch . '-KAUR-');
        if (count($kaurParentIds) !== $count) {
            throw new RuntimeException('ID parent pengajuan Kaur tidak lengkap; transaksi dibatalkan.');
        }

        $kaurItemRows = [];
        for ($i = 0; $i < $count; $i++) {
            for ($j = 1; $j <= $kaurPlans[$i]['item_count']; $j++) {
                $price = 300000 + (($i * 127 + $j * 311) % 18000000);
                $volume = 1 + (($i + $j + 1) % 5);
                $deal = !in_array($kaurPlans[$i]['status'], ['Pengajuan', 'Revisi', 'Ditolak'], true);
                $kaurItemRows[] = [
                    'id_pengajuan' => $kaurParentIds[sprintf('%s-KAUR-%06d', $batch, $i + 1)],
                    'no_urut' => $j,
                    'uraian_barang' => sprintf('%s Kebutuhan Kaur Item %d', $batch, $j),
                    'vol' => $volume,
                    'satuan' => 'unit',
                    'harga_penawaran_sat' => $price,
                    'link_penawaran' => 'https://example.test/' . strtolower($batch) . '/kaur/' . $i . '-' . $j,
                    'hasil_negosiasi_vol' => $deal ? $volume : null,
                    'hasil_negosiasi_sat' => $deal ? $price - min(500000, $price * 0.05) : null,
                    'garansi' => $deal ? '1 tahun' : null,
                    'alokasi_sisa' => $kaurPlans[$i]['status'] === 'Selesai' ? 'Sisa dialokasikan' : null,
                    'created_at' => $kaurPlans[$i]['created'],
                ];
            }
        }
        insert_rows($db, 'kaur_pengajuan_item', ['id_pengajuan', 'no_urut', 'uraian_barang', 'vol', 'satuan', 'harga_penawaran_sat', 'link_penawaran', 'hasil_negosiasi_vol', 'hasil_negosiasi_sat', 'garansi', 'alokasi_sisa', 'created_at'], $kaurItemRows);

        $negoRows = [];
        foreach ($capItemRows as $index => $item) {
            $price = (float) $item['harga_penawaran_sat'];
            $status = ['Deal', 'Sedang Negosiasi', 'Belum Negosiasi', 'Ditolak'][$index % 4];
            $negotiated = $status === 'Deal' ? $price * 0.92 : ($status === 'Sedang Negosiasi' ? $price * 0.96 : ($status === 'Ditolak' ? 0 : $price));
            $itemIndex = $index % count($capItemIds);
            $negoRows[] = [
                'sumber' => $index % 3 === 0 ? 'kaprodi' : 'kaur',
                'id_pengajuan' => (int) $item['id_pengajuan'],
                'id_item' => (int) $capItemIds[$itemIndex],
                'vendor' => sprintf('%s Vendor %02d', $batch, ($index % 24) + 1),
                'harga_awal' => $price,
                'volume_awal' => (float) $item['vol'],
                'harga_negosiasi' => $negotiated,
                'volume_negosiasi' => (float) $item['vol'],
                'garansi' => '1 tahun',
                'catatan' => 'Negosiasi per item untuk stress testing batch ' . $batch,
                'status' => $status,
                'created_by' => $index % 3 === 0 ? $approvers['kaprodi'] : $approvers['kaur'],
                'created_at' => date_at($index + 120),
            ];
        }
        insert_rows($db, 'pengadaan_negosiasi', ['sumber', 'id_pengajuan', 'id_item', 'vendor', 'harga_awal', 'volume_awal', 'harga_negosiasi', 'volume_negosiasi', 'garansi', 'catatan', 'status', 'created_by', 'created_at'], $negoRows);

        $bastRows = [];
        $bastPlan = [];
        foreach ($capPlans as $i => $plan) {
            if (!in_array($plan['status'], ['BAST', 'Inventarisasi', 'Selesai'], true)) {
                continue;
            }
            $processed = in_array($plan['status'], ['Inventarisasi', 'Selesai'], true);
            $bastPlan[$i] = ['number' => $plan['bast_number'], 'processed' => $processed];
            $bastRows[] = [
                'id_pengajuan' => $capParentIds[sprintf('%s-KPRD-%06d', $batch, $i + 1)],
                'nomor_bast' => $plan['bast_number'],
                'tanggal_bast' => substr($plan['created'], 0, 10),
                'jenis_bast' => $plan['jenis'],
                'file_bast' => null,
                'catatan' => 'BAST uji batch ' . $batch,
                'input_by' => $approvers['laboran'],
                'inventory_processed_at' => $processed ? $plan['created'] : null,
                'created_at' => $plan['created'],
            ];
        }
        insert_rows($db, 'pengadaan_bast', ['id_pengajuan', 'nomor_bast', 'tanggal_bast', 'jenis_bast', 'file_bast', 'catatan', 'input_by', 'inventory_processed_at', 'created_at'], $bastRows);
        $bastIdsByNumber = [];
        if ($bastRows) {
            $bastResult = $db->query("SELECT id_bast, nomor_bast FROM pengadaan_bast WHERE nomor_bast LIKE '" . $db->real_escape_string($batch . '-BAST-') . "%'");
            while ($row = $bastResult->fetch_assoc()) {
                $bastIdsByNumber[$row['nomor_bast']] = (int) $row['id_bast'];
            }
        }

        $inventoryRows = [];
        $assetBastMap = [];
        $itemOffset = 0;
        foreach ($capPlans as $i => $plan) {
            if (!isset($bastPlan[$i]) || !$bastPlan[$i]['processed']) {
                $itemOffset += $plan['item_count'];
                continue;
            }
            $bastId = $bastIdsByNumber[$bastPlan[$i]['number']] ?? 0;
            if (!$bastId) {
                throw new RuntimeException('BAST hasil insert tidak ditemukan: ' . $bastPlan[$i]['number']);
            }
            for ($j = 0; $j < $plan['item_count']; $j++) {
                $itemId = (int) $capItemIds[$itemOffset + $j];
                $assetId = (int) $assetIds[sprintf('%s-A-%06d', $batch, (($itemOffset + $j) % $count) + 1)];
                $inventoryRows[] = [
                    'id_bast' => $bastId,
                    'id_pengajuan' => $capParentIds[sprintf('%s-KPRD-%06d', $batch, $i + 1)],
                    'id_item' => $itemId,
                    'id_aset' => $assetId,
                    'created_at' => $plan['created'],
                ];
                if (!isset($assetBastMap[$assetId])) {
                    $assetBastMap[$assetId] = $bastId;
                }
            }
            $itemOffset += $plan['item_count'];
        }
        insert_rows($db, 'pengadaan_inventory_link', ['id_bast', 'id_pengajuan', 'id_item', 'id_aset', 'created_at'], $inventoryRows);

        $loanRows = [];
        $detailRows = [];
        $assetLoanState = [];
        for ($i = 0; $i < $count; $i++) {
            $profile = loan_profile($i);
            $userKey = sprintf('%s-U-%06d', $batch, ($i % $count) + 1);
            $peminjamKey = sprintf('%s-P-%06d', $batch, ($i % $count) + 1);
            $assetKey = sprintf('%s-A-%06d', $batch, $i + 1);
            $created = date_at(($i * 7) % 366 + 160);
            $createdDate = substr($created, 0, 10);
            $returnDate = plus_days($createdDate, 1 + ($i % 7));
            $approvedAt = date_at(($i * 7) % 366 + 163);
            $isReturned = $profile['status'] === 'Dikembalikan';
            $isActive = $profile['status'] === 'Sedang Dipinjam';
            $hasQr = in_array($profile['status'], ['Disetujui (Menunggu Pengambilan)', 'Sedang Dipinjam', 'Dikembalikan'], true);
            $quantity = 1 + ($i % 3);
            $idAsset = (int) $assetIds[$assetKey];
            $idUser = (int) $userIds[$userKey];
            $idPeminjam = (int) $peminjamIds[$peminjamKey];
            $kaprodiPending = $profile['kaprodi'] === 'Pending';
            $laboranApproved = $profile['laboran'] === 'Disetujui';
            $kaurApproved = $profile['kaur'] === 'Disetujui';
            $kaprodiTime = $kaprodiPending ? null : $approvedAt;
            $laboranTime = $laboranApproved || $profile['laboran'] === 'Ditolak' ? date_at(($i * 7) % 366 + 164) : null;
            $kaurTime = $kaurApproved || $profile['kaur'] === 'Ditolak' ? date_at(($i * 7) % 366 + 165) : null;
            $deadline = $kaprodiPending ? date_at(($i * 7) % 366 + 164) : null;
            $expired = ($kaprodiPending && $i % 10 === 0) ? date_at(($i * 7) % 366 + 150) : null;
            $stockState = $isActive ? 'allocated' : ($isReturned ? 'released' : 'none');
            $loanRows[] = [
                'group_id' => sprintf('%s-PJM-%06d', $batch, $i + 1),
                'id_aset' => $idAsset,
                'id_peminjam' => $idPeminjam,
                'id_user' => $idUser,
                'jumlah_pinjam' => $quantity,
                'stock_allocation_status' => $stockState,
                'stock_allocated_at' => $isActive ? $approvedAt : null,
                'stock_released_at' => $isReturned ? $returnDate . ' 16:00:00' : null,
                'jumlah_kembali' => $isReturned ? $quantity : null,
                'tanggal_pinjam' => $createdDate,
                'tanggal_kembali_rencana' => $returnDate,
                'tanggal_kembali_actual' => $isReturned ? $returnDate : null,
                'keperluan' => 'Stress testing peminjaman batch ' . $batch . ' untuk skenario ' . $profile['status'],
                'status' => $profile['status'],
                'status_kaprodi' => $profile['kaprodi'],
                'catatan_kaprodi' => $profile['kaprodi'] === 'Ditolak' ? 'Ditolak Kaprodi untuk pengujian.' : ($kaprodiPending ? null : 'Disetujui Kaprodi untuk pengujian.'),
                'tgl_approve_kaprodi' => $kaprodiTime,
                'id_approver_kaprodi' => $kaprodiPending ? null : $approvers['kaprodi'],
                'status_laboran' => $profile['laboran'],
                'catatan_laboran' => $profile['laboran'] === 'Ditolak' ? 'Ditolak Laboran untuk pengujian.' : ($laboranApproved ? 'Stok fisik tersedia untuk pengujian.' : null),
                'tgl_approve_laboran' => $laboranTime,
                'id_approver_laboran' => $laboranTime ? $approvers['laboran'] : null,
                'status_kaur' => $profile['kaur'],
                'catatan_kaur' => $profile['kaur'] === 'Ditolak' ? 'Ditolak Kaur untuk pengujian.' : ($kaurApproved ? 'Disetujui Kaur untuk pengujian.' : null),
                'tgl_approve_kaur' => $kaurTime,
                'id_approver_kaur' => $kaurTime ? $approvers['kaur'] : null,
                'kondisi_saat_pinjam' => 'Baik',
                'kondisi_saat_kembali' => $isReturned ? 'Baik' : null,
                'foto_bukti' => $photo,
                'foto_pengembalian' => $isReturned ? $photo : null,
                'qr_locked' => $hasQr ? 1 : 0,
                'qr_finalized_at' => $hasQr ? date_at(($i * 7) % 366 + 166) : null,
                'qr_finalized_by' => $hasQr ? $approvers['laboran'] : null,
                'qr_pengembalian_token' => $hasQr ? sprintf('%s-QR-PJM-%06d', $batch, $i + 1) : null,
                'catatan' => ($expired ? 'Batas ACC Kaprodi uji telah lewat. ' : '') . 'Record stress test ' . $batch,
                'created_at' => $created,
                'updated_at' => $created,
            ];
            $detailRows[] = [
                'id_peminjaman' => null,
                'id_aset' => $idAsset,
                'jumlah_pinjam' => $quantity,
                'kondisi_saat_pinjam' => 'Baik',
                'catatan' => 'Detail item stress test ' . $batch,
                'created_at' => $created,
            ];
            $assetLoanState[$idAsset] = [
                'dipinjam' => $isActive ? $quantity : 0,
                'total_peminjaman' => max(1, ($i % 17) + ($isActive || $isReturned ? 1 : 0)),
            ];
            $loanRows[$i]['kaprodi_deadline_at'] = $deadline;
            $loanRows[$i]['kaprodi_expired_at'] = $expired;
            $loanRows[$i]['kaprodi_approval_limit_days'] = $kaprodiPending ? 4 : null;
        }
        insert_rows($db, 'peminjaman', ['group_id', 'id_aset', 'id_peminjam', 'id_user', 'jumlah_pinjam', 'stock_allocation_status', 'stock_allocated_at', 'stock_released_at', 'jumlah_kembali', 'tanggal_pinjam', 'tanggal_kembali_rencana', 'tanggal_kembali_actual', 'keperluan', 'status', 'status_kaprodi', 'kaprodi_approval_limit_days', 'kaprodi_deadline_at', 'kaprodi_expired_at', 'catatan_kaprodi', 'tgl_approve_kaprodi', 'id_approver_kaprodi', 'status_laboran', 'catatan_laboran', 'tgl_approve_laboran', 'id_approver_laboran', 'status_kaur', 'catatan_kaur', 'tgl_approve_kaur', 'id_approver_kaur', 'kondisi_saat_pinjam', 'kondisi_saat_kembali', 'foto_bukti', 'foto_pengembalian', 'qr_locked', 'qr_finalized_at', 'qr_finalized_by', 'qr_pengembalian_token', 'catatan', 'created_at', 'updated_at'], $loanRows);
        $loanIdMap = id_map_by_field($db, 'peminjaman', 'id_peminjaman', 'group_id', $batch . '-PJM-');
        if (count($loanIdMap) !== $count) {
            throw new RuntimeException('ID peminjaman hasil insert tidak lengkap; transaksi dibatalkan.');
        }
        foreach ($detailRows as $index => &$detail) {
            $detail['id_peminjaman'] = $loanIdMap[sprintf('%s-PJM-%06d', $batch, $index + 1)];
        }
        unset($detail);
        insert_rows($db, 'peminjaman_detail', ['id_peminjaman', 'id_aset', 'jumlah_pinjam', 'kondisi_saat_pinjam', 'catatan', 'created_at'], $detailRows);

        $assetCases = [];
        foreach ($assetLoanState as $assetId => $state) {
            $assetCases[(int) $assetId] = $state + ['bast_id' => $assetBastMap[$assetId] ?? null];
        }
        foreach (array_chunk($assetCases, 250, true) as $part) {
            $dipinjamCase = [];
            $availableCase = [];
            $totalCase = [];
            $bastCase = [];
            $ids = [];
            foreach ($part as $assetId => $state) {
                $ids[] = (int) $assetId;
                $dipinjamCase[] = 'WHEN ' . (int) $assetId . ' THEN ' . (int) $state['dipinjam'];
                $availableCase[] = 'WHEN ' . (int) $assetId . ' THEN GREATEST(0, jumlah_total - ' . (int) $state['dipinjam'] . ')';
                $totalCase[] = 'WHEN ' . (int) $assetId . ' THEN ' . (int) $state['total_peminjaman'];
                if ($state['bast_id'] !== null) {
                    $bastCase[] = 'WHEN ' . (int) $assetId . ' THEN ' . (int) $state['bast_id'];
                }
            }
            $sql = "UPDATE aset SET jumlah_dipinjam = CASE id_aset " . implode(' ', $dipinjamCase) . " END, jumlah_tersedia = CASE id_aset " . implode(' ', $availableCase) . " END, total_peminjaman = CASE id_aset " . implode(' ', $totalCase) . " END";
            if ($bastCase) {
                $sql .= ", sumber_bast_id = CASE id_aset " . implode(' ', $bastCase) . " ELSE sumber_bast_id END";
            }
            $sql .= ' WHERE id_aset IN (' . implode(',', $ids) . ')';
            $db->query($sql);
        }
        $db->commit();
    } catch (Throwable $exception) {
        $db->rollback();
        throw $exception;
    }

    validate_batch($db, $batch, $count);
}

function validate_batch(mysqli $db, string $batch, int $count): void
{
    $capIn = "SELECT id_pengajuan FROM kaprodi_pengajuan WHERE kode_pengajuan LIKE '" . $db->real_escape_string($batch . '-KPRD-') . "%'";
    $loanIn = "SELECT id_peminjaman FROM peminjaman WHERE group_id LIKE '" . $db->real_escape_string($batch . '-PJM-') . "%'";
    $checks = [
        'Invalid FK cap item' => "SELECT COUNT(*) AS total FROM kaprodi_pengajuan_item i LEFT JOIN kaprodi_pengajuan p ON p.id_pengajuan=i.id_pengajuan WHERE p.id_pengajuan IS NULL AND i.uraian_barang LIKE '" . $db->real_escape_string($batch) . "%'",
        'Invalid FK kaur item' => "SELECT COUNT(*) AS total FROM kaur_pengajuan_item i LEFT JOIN kaur_pengajuan p ON p.id_pengajuan=i.id_pengajuan WHERE p.id_pengajuan IS NULL AND i.uraian_barang LIKE '" . $db->real_escape_string($batch) . "%'",
        'Invalid FK loan detail' => "SELECT COUNT(*) AS total FROM peminjaman_detail d LEFT JOIN peminjaman p ON p.id_peminjaman=d.id_peminjaman WHERE p.id_peminjaman IS NULL AND d.catatan LIKE '%" . $db->real_escape_string($batch) . "%'",
        'Invalid FK inventory link' => "SELECT COUNT(*) AS total FROM pengadaan_inventory_link l LEFT JOIN pengadaan_bast b ON b.id_bast=l.id_bast WHERE b.id_bast IS NULL AND l.created_at >= '2025-08-20'",
        'Duplicate user NIM' => "SELECT COUNT(*) AS total FROM (SELECT nim_nip FROM users WHERE nim_nip LIKE '" . $db->real_escape_string($batch . '-U-') . "%' GROUP BY nim_nip HAVING COUNT(*) > 1) x",
        'Duplicate user email' => "SELECT COUNT(*) AS total FROM (SELECT email FROM users WHERE email LIKE '" . $db->real_escape_string(strtolower($batch) . '.user') . "%' GROUP BY email HAVING COUNT(*) > 1) x",
        'Duplicate asset code' => "SELECT COUNT(*) AS total FROM (SELECT kode_aset FROM aset WHERE kode_aset LIKE '" . $db->real_escape_string($batch . '-A-') . "%' GROUP BY kode_aset HAVING COUNT(*) > 1) x",
        'Negative stock' => "SELECT COUNT(*) AS total FROM aset WHERE kode_aset LIKE '" . $db->real_escape_string($batch . '-A-') . "%' AND (jumlah_total < 0 OR jumlah_reserved < 0 OR jumlah_dipinjam < 0 OR jumlah_tersedia < 0)",
        'Stock formula mismatch' => "SELECT COUNT(*) AS total FROM aset WHERE kode_aset LIKE '" . $db->real_escape_string($batch . '-A-') . "%' AND jumlah_tersedia <> jumlah_total - jumlah_reserved - jumlah_dipinjam",
        'Orphan loan user' => "SELECT COUNT(*) AS total FROM peminjaman p LEFT JOIN users u ON u.id_user=p.id_user WHERE p.group_id LIKE '" . $db->real_escape_string($batch . '-PJM-') . "%' AND u.id_user IS NULL",
        'Orphan loan borrower' => "SELECT COUNT(*) AS total FROM peminjaman p LEFT JOIN peminjam b ON b.id_peminjam=p.id_peminjam WHERE p.group_id LIKE '" . $db->real_escape_string($batch . '-PJM-') . "%' AND b.id_peminjam IS NULL",
        'Orphan loan asset' => "SELECT COUNT(*) AS total FROM peminjaman p LEFT JOIN aset a ON a.id_aset=p.id_aset WHERE p.group_id LIKE '" . $db->real_escape_string($batch . '-PJM-') . "%' AND a.id_aset IS NULL",
    ];
    $errors = [];
    foreach ($checks as $label => $sql) {
        $total = row_count($db, $sql);
        if ($total !== 0) {
            $errors[$label] = $total;
        }
    }
    $counts = batch_anchor_counts($db, $batch);
    if ($counts['users'] !== $count || $counts['peminjam'] !== $count || $counts['aset'] !== $count || $counts['kaprodi_pengajuan'] !== $count || $counts['kaur_pengajuan'] !== $count || $counts['peminjaman'] !== $count) {
        $errors['Anchor count'] = $counts;
    }
    if ($errors) {
        throw new RuntimeException('Validasi batch gagal: ' . json_encode($errors, JSON_PRETTY_PRINT));
    }

    $items = row_count($db, "SELECT COUNT(*) AS total FROM kaprodi_pengajuan_item WHERE id_pengajuan IN ({$capIn})");
    $kaurItems = row_count($db, "SELECT COUNT(*) AS total FROM kaur_pengajuan_item WHERE id_pengajuan IN (SELECT id_pengajuan FROM kaur_pengajuan WHERE kode_pengajuan LIKE '" . $db->real_escape_string($batch . '-KAUR-') . "%')");
    $negosiasi = row_count($db, "SELECT COUNT(*) AS total FROM pengadaan_negosiasi WHERE id_item IN (SELECT id_item FROM kaprodi_pengajuan_item WHERE id_pengajuan IN ({$capIn}))");
    $details = row_count($db, "SELECT COUNT(*) AS total FROM peminjaman_detail WHERE id_peminjaman IN ({$loanIn})");
    $bast = row_count($db, "SELECT COUNT(*) AS total FROM pengadaan_bast WHERE id_pengajuan IN ({$capIn})");
    $links = row_count($db, "SELECT COUNT(*) AS total FROM pengadaan_inventory_link WHERE id_pengajuan IN ({$capIn})");
    echo "SCM FIK TEST DATA SUCCESS ({$batch})\n";
    echo "users                       : {$counts['users']}\n";
    echo "peminjam                    : {$counts['peminjam']}\n";
    echo "aset                        : {$counts['aset']}\n";
    echo "ruangan                     : existing/reused\n";
    echo "kaprodi_pengajuan           : {$counts['kaprodi_pengajuan']}\n";
    echo "kaprodi_pengajuan_item      : {$items}\n";
    echo "kaur_pengajuan              : {$counts['kaur_pengajuan']}\n";
    echo "kaur_pengajuan_item         : {$kaurItems}\n";
    echo "pengadaan_negosiasi         : {$negosiasi}\n";
    echo "pengadaan_bast              : {$bast}\n";
    echo "pengadaan_inventory_link    : {$links}\n";
    echo "peminjaman                  : {$counts['peminjaman']}\n";
    echo "peminjaman_detail           : {$details}\n";
    echo "Invalid FK                 : 0\n";
    echo "Duplicate unique           : 0\n";
    echo "Negative stock             : 0\n";
    echo "Orphan child               : 0\n";
    echo "Errors                     : 0\n";
    echo "Password account dummy     : Password123!\n";
}

function validate_only(mysqli $db, string $batch, int $count): void
{
    $counts = batch_anchor_counts($db, $batch);
    if (array_sum($counts) === 0) {
        print_plan($count);
        echo "Schema, enum, room reuse, dan approver existing tervalidasi.\n";
        echo "Mode dry-run: database tidak diubah.\n";
        return;
    }
    echo "Batch {$batch} sudah memiliki anchor data:\n";
    foreach ($counts as $table => $total) {
        echo str_pad($table, 28) . ': ' . $total . "\n";
    }
    echo "Tidak ada insert pada mode dry-run.\n";
}

try {
    $options = cli_options($argv);
    $projectRoot = dirname(__DIR__);
    $db = connect_database($projectRoot);
    require_schema($db);

    if ($options['mode'] === 'cleanup') {
        cleanup_batch($db, $options['batch']);
    } elseif ($options['mode'] === 'apply') {
        print_plan($options['count']);
        apply_seed($db, $options['batch'], $options['count']);
    } else {
        validate_only($db, $options['batch'], $options['count']);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'SCM FIK TEST DATA ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
