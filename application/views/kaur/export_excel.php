<?php
function excel_kaur_rp($value) { return number_format((float) $value, 0, ',', '.'); }
function excel_kaur_num($value) { return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ','); }
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10pt; }
        th, td { border: 1px solid #333; padding: 5px; vertical-align: middle; }
        th { background: #d9d9d9; font-weight: bold; text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .no-border { border: 0; }
        .title { font-size: 13pt; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="14" class="title no-border">BERITA ACARA KLARIFIKASI</td></tr>
        <tr><td colspan="14" class="title no-border">PENGADAAN BARANG INVENTARIS LABORATORIUM FIK</td></tr>
        <tr><td colspan="14" class="no-border">Kode: <?= html_escape($pengajuan->kode_pengajuan) ?></td></tr>
        <tr><td colspan="14" class="no-border">Lab: <?= html_escape($pengajuan->nama_lab) ?></td></tr>
        <tr><td colspan="14" class="no-border">Kebutuhan Lab: <?= html_escape($pengajuan->kebutuhan_lab) ?></td></tr>
        <tr><td colspan="14" class="no-border"></td></tr>
        <tr>
            <th rowspan="2">No.</th>
            <th rowspan="2">Uraian Barang/Pekerjaan</th>
            <th rowspan="2">Vol</th>
            <th rowspan="2">Satuan</th>
            <th colspan="4">HARGA PENAWARAN KAUR (Rp)</th>
            <th rowspan="2">Uraian Barang/Pekerjaan</th>
            <th colspan="3">HASIL NEGOSIASI (Rp)</th>
            <th rowspan="2">Garansi</th>
            <th rowspan="2">Alokasi Sisa</th>
        </tr>
        <tr>
            <th>Harga Sat</th><th>Jmlh Harga</th><th>Harga +20%</th><th>Link</th>
            <th>Vol</th><th>Harga Sat</th><th>Jmlh Harga</th>
        </tr>
        <?php foreach($pengajuan->items as $i => $item): ?>
            <?php
            $jumlah_penawaran = (float)$item->vol * (float)$item->harga_penawaran_sat;
            $jumlah_markup = (float)$item->vol * ((float)$item->harga_penawaran_sat * 1.2);
            $nego_vol = $item->hasil_negosiasi_vol !== null ? (float)$item->hasil_negosiasi_vol : (float)$item->vol;
            $nego_sat = $item->hasil_negosiasi_sat !== null ? (float)$item->hasil_negosiasi_sat : 0;
            $jumlah_nego = $nego_vol * $nego_sat;
            ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td><?= html_escape($item->uraian_barang) ?></td>
                <td class="center"><?= excel_kaur_num($item->vol) ?></td>
                <td class="center"><?= html_escape($item->satuan) ?></td>
                <td class="right"><?= excel_kaur_rp($item->harga_penawaran_sat) ?></td>
                <td class="right"><?= excel_kaur_rp($jumlah_penawaran) ?></td>
                <td class="right"><?= excel_kaur_rp($jumlah_markup) ?></td>
                <td><?= html_escape($item->link_penawaran ?: '-') ?></td>
                <td><?= html_escape($item->uraian_barang) ?></td>
                <td class="center"><?= excel_kaur_num($nego_vol) ?></td>
                <td class="right"><?= excel_kaur_rp($nego_sat) ?></td>
                <td class="right"><?= excel_kaur_rp($jumlah_nego) ?></td>
                <td><?= html_escape($item->garansi ?: '-') ?></td>
                <td><?= html_escape($item->alokasi_sisa ?: '-') ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><td colspan="6" class="right bold">Sub Total (+20%)</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['subtotal_markup']) ?></td><td></td><td colspan="3" class="right bold">Sub Total</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['subtotal_negosiasi']) ?></td><td colspan="2"></td></tr>
        <tr><td colspan="6" class="right bold">PPN 11%</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['ppn_penawaran']) ?></td><td></td><td colspan="3" class="right bold">PPN 11%</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['ppn_negosiasi']) ?></td><td colspan="2"></td></tr>
        <tr><td colspan="6" class="right bold">Total</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['total_penawaran']) ?></td><td></td><td colspan="3" class="right bold">Total</td><td class="right bold"><?= excel_kaur_rp($pengajuan->summary['total_negosiasi']) ?></td><td colspan="2"></td></tr>
        <tr><td colspan="14" class="no-border"></td></tr>
        <tr><td colspan="7" class="no-border">BAST Tahap 1: <?= html_escape($pengajuan->bast_nomor ?: '-') ?></td><td colspan="7" class="no-border">Anak Perusahaan: <?= html_escape($pengajuan->anak_perusahaan ?: '-') ?></td></tr>
        <tr><td colspan="7" class="no-border">Tanggal BAST: <?= html_escape($pengajuan->bast_tanggal ?: '-') ?></td><td colspan="7" class="no-border">Penerima: <?= html_escape($pengajuan->bast_penerima ?: '-') ?></td></tr>
        <tr><td colspan="7" class="no-border">Status: <?= html_escape($pengajuan->status) ?></td><td colspan="7" class="no-border">Disetujui Pada: <?= html_escape($pengajuan->bast_disetujui_pada ?: '-') ?></td></tr>
        <tr><td colspan="14" class="no-border">Catatan BAST: <?= html_escape($pengajuan->bast_catatan ?: '-') ?></td></tr>
        <tr><td colspan="14" class="no-border">Catatan Alokasi: <?= html_escape($pengajuan->catatan_alokasi ?: '-') ?></td></tr>
    </table>
</body>
</html>
