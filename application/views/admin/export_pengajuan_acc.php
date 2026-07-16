<?php
defined('BASEPATH') OR exit('No direct script access allowed');
echo "\xEF\xBB\xBF";

function excel_cell($value) {
    return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= excel_cell($title ?? 'Laporan Pengajuan Sampai Tahap ACC') ?></title>
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
            <td colspan="16" class="title"><?= excel_cell($title ?? 'Laporan Pengajuan Sampai Tahap ACC') ?></td>
        </tr>
        <tr>
            <td colspan="16" class="meta">Dicetak: <?= date('d/m/Y H:i') ?> WIB</td>
        </tr>
        <tr>
            <th>No</th>
            <th>ID Transaksi</th>
            <th>Nama Peminjam</th>
            <th>NIM/NIP</th>
            <th>Daftar Barang</th>
            <th>Total Jenis</th>
            <th>Total Unit</th>
            <th>Tanggal Pinjam</th>
            <th>Tanggal Kembali</th>
            <th>Keperluan</th>
            <th>Status Pengajuan</th>
            <th>Status Laboran</th>
            <th>Tanggal Cek Laboran</th>
            <th>Status Kaur</th>
            <th>Tanggal ACC Kaur</th>
            <th>Catatan</th>
        </tr>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="16" style="text-align:center;">Tidak ada data.</td>
            </tr>
        <?php else: foreach ($rows as $index => $row): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= excel_cell($row->group_id ?: $row->id_peminjaman) ?></td>
                <td><?= excel_cell($row->nama_peminjam) ?></td>
                <td><?= excel_cell($row->nim_nip) ?></td>
                <td><?= excel_cell($row->daftar_barang) ?></td>
                <td><?= (int) ($row->total_jenis ?? 0) ?></td>
                <td><?= (int) ($row->total_jumlah ?? 0) ?></td>
                <td><?= excel_cell($row->tanggal_pinjam) ?></td>
                <td><?= excel_cell($row->tanggal_kembali_rencana) ?></td>
                <td><?= excel_cell($row->keperluan) ?></td>
                <td><?= excel_cell($row->status) ?></td>
                <td><?= excel_cell($row->status_laboran) ?></td>
                <td><?= excel_cell($row->tgl_approve_laboran) ?></td>
                <td><?= excel_cell($row->status_kaur) ?></td>
                <td><?= excel_cell($row->tgl_approve_kaur) ?></td>
                <td>
                    Laboran: <?= excel_cell($row->catatan_laboran) ?><br>
                    Kaur: <?= excel_cell($row->catatan_kaur) ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </table>
</body>
</html>
