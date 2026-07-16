<?php
defined('BASEPATH') OR exit('No direct script access allowed');
echo "\xEF\xBB\xBF";

function acc_excel_cell($value) {
    return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8');
}
function acc_excel_rp($value) {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}
function acc_excel_items($items) {
    $lines = [];
    foreach ((array) $items as $item) {
        $lines[] = $item->uraian_barang . ' - ' . rtrim(rtrim(number_format((float) $item->vol, 2, ',', '.'), '0'), ',') . ' ' . $item->satuan;
    }
    return implode('; ', $lines);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= acc_excel_cell($title ?? 'Laporan Pengajuan Sampai Tahap ACC') ?></title>
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
        th, td { border: 1px solid #777; padding: 6px; vertical-align: top; }
        th { background: #ea5b1a; color: #fff; font-weight: bold; text-align: center; }
        .title { font-size: 16px; font-weight: bold; text-align: center; }
        .meta { color: #555; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="14" class="title"><?= acc_excel_cell($title ?? 'Laporan Pengajuan Sampai Tahap ACC') ?></td>
        </tr>
        <tr>
            <td colspan="14" class="meta">Dicetak: <?= date('d/m/Y H:i') ?> WIB</td>
        </tr>
        <tr>
            <th>No</th>
            <th>Kode Pengajuan</th>
            <th>Kaprodi/User</th>
            <th>Program Studi</th>
            <th>Jenis</th>
            <th>Nama Pengajuan</th>
            <th>Kebutuhan Lab</th>
            <th>Daftar Barang/Jasa</th>
            <th>Status</th>
            <th>Subtotal Penawaran</th>
            <th>Total Setelah PPN</th>
            <th>Catatan Approval</th>
            <th>Tanggal Pengajuan</th>
            <th>Update Terakhir</th>
        </tr>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="14" style="text-align:center;">Tidak ada data.</td>
            </tr>
        <?php else: foreach ($rows as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= acc_excel_cell($row->kode_pengajuan) ?></td>
                <td><?= acc_excel_cell($row->nama_lengkap) ?></td>
                <td><?= acc_excel_cell($row->nama_prodi) ?></td>
                <td><?= acc_excel_cell($row->jenis_pengajuan) ?></td>
                <td><?= acc_excel_cell($row->nama_pengajuan) ?></td>
                <td><?= acc_excel_cell($row->kebutuhan_lab) ?></td>
                <td><?= acc_excel_cell(acc_excel_items($row->items ?? [])) ?></td>
                <td><?= acc_excel_cell($row->status) ?></td>
                <td><?= acc_excel_rp($row->summary['subtotal_penawaran'] ?? 0) ?></td>
                <td><?= acc_excel_rp($row->summary['total_penawaran'] ?? 0) ?></td>
                <td><?= acc_excel_cell($row->catatan_approval) ?></td>
                <td><?= acc_excel_cell($row->created_at) ?></td>
                <td><?= acc_excel_cell($row->updated_at) ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </table>
</body>
</html>
