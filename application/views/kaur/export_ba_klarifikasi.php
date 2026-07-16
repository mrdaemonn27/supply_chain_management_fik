<?php
defined('BASEPATH') OR exit('No direct script access allowed');
echo "\xEF\xBB\xBF";

if (!function_exists('ba_excel_escape')) {
    function ba_excel_escape($value) {
        return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ba_excel_rp')) {
    function ba_excel_rp($value) {
        $value = (float) ($value ?? 0);
        return $value > 0 ? number_format($value, 0, ',', '.') : '';
    }
}

if (!function_exists('ba_excel_num')) {
    function ba_excel_num($value) {
        return rtrim(rtrim(number_format((float) ($value ?? 0), 2, ',', '.'), '0'), ',');
    }
}

if (!function_exists('ba_excel_can_show_negosiasi')) {
    function ba_excel_can_show_negosiasi($pengajuan, $show_negosiasi) {
        if ($show_negosiasi) {
            return true;
        }

        return in_array((string) ($pengajuan->status ?? ''), ['Deal', 'Disetujui', 'Approval', 'BAST', 'Inventarisasi', 'Selesai'], true);
    }
}

$pengajuan_list = $pengajuan_list ?? (!empty($pengajuan) ? [$pengajuan] : []);
$role_label = $role_label ?? 'Kaur Laboratorium';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= ba_excel_escape($title ?? 'Berita Acara Klarifikasi') ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; }
        table.ba-table { border-collapse: collapse; width: 100%; table-layout: fixed; font-size: 10pt; margin-bottom: 28px; }
        .ba-table th, .ba-table td { border: 1px solid #333; padding: 5px; vertical-align: middle; }
        .ba-table th { background: #d9d9d9; font-weight: bold; text-align: center; }
        .title { font-size: 13pt; font-weight: bold; text-align: center; border: 0 !important; }
        .meta { border: 0 !important; font-size: 9pt; color: #333; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .group-row td { font-weight: bold; background: #eeeeee; }
        .no-border { border: 0 !important; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<?php if (empty($pengajuan_list)): ?>
    <table class="ba-table">
        <tr><td class="title">Tidak ada data pengajuan.</td></tr>
    </table>
<?php else: foreach ($pengajuan_list as $index_pengajuan => $pengajuan): ?>
    <?php
    $items = (array) ($pengajuan->items ?? []);
    $show_result = ba_excel_can_show_negosiasi($pengajuan, (bool) ($show_negosiasi ?? false));
    $sub_penawaran = 0;
    $sub_negosiasi = 0;
    $nama_unit = $pengajuan->nama_prodi ?? $pengajuan->nama_lab ?? '-';
    $jenis = $pengajuan->jenis_pengajuan ?? 'Barang';
    ?>
    <table class="ba-table">
        <colgroup>
            <col style="width: 4%;">
            <col style="width: 25%;">
            <col style="width: 5%;">
            <col style="width: 6%;">
            <col style="width: 10%;">
            <col style="width: 11%;">
            <col style="width: 25%;">
            <col style="width: 5%;">
            <col style="width: 10%;">
            <col style="width: 11%;">
            <col style="width: 10%;">
        </colgroup>
        <tr><td colspan="11" class="title">BERITA ACARA KLARIFIKASI</td></tr>
        <tr><td colspan="11" class="title">PENGADAAN BARANG/JASA INVENTARIS KANTOR FIK</td></tr>
        <tr><td colspan="11" class="meta">&nbsp;</td></tr>
        <tr>
            <td colspan="3" class="meta">Kode</td>
            <td colspan="8" class="meta">: <?= ba_excel_escape($pengajuan->kode_pengajuan ?? '-') ?></td>
        </tr>
        <tr>
            <td colspan="3" class="meta">Unit/Prodi</td>
            <td colspan="8" class="meta">: <?= ba_excel_escape($nama_unit) ?></td>
        </tr>
        <tr>
            <td colspan="3" class="meta">Jenis Pengajuan</td>
            <td colspan="8" class="meta">: <?= ba_excel_escape($jenis) ?></td>
        </tr>
        <tr>
            <td colspan="3" class="meta">Nama Pengajuan</td>
            <td colspan="8" class="meta">: <?= ba_excel_escape($pengajuan->nama_pengajuan ?? '-') ?></td>
        </tr>
        <tr>
            <td colspan="3" class="meta">Keterangan</td>
            <td colspan="8" class="meta">: <?= ba_excel_escape($pengajuan->kebutuhan_lab ?? '-') ?></td>
        </tr>
        <tr><td colspan="11" class="meta">&nbsp;</td></tr>
        <tr>
            <th rowspan="2">No.</th>
            <th rowspan="2">Uraian Barang/Pekerjaan</th>
            <th rowspan="2">Vol</th>
            <th rowspan="2">Satuan</th>
            <th colspan="2">HARGA PENAWARAN (Rp)</th>
            <th rowspan="2">Uraian Barang/Pekerjaan</th>
            <th colspan="3">HASIL NEGOSIASI (Rp)</th>
            <th rowspan="2">Garansi</th>
        </tr>
        <tr>
            <th>Harga Sat</th>
            <th>Jmlh Harga</th>
            <th>Vol</th>
            <th>Harga Sat</th>
            <th>Jmlh Harga</th>
        </tr>
        <tr class="group-row">
            <td></td>
            <td><?= ba_excel_escape($pengajuan->nama_pengajuan ?? '-') ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><?= ba_excel_escape($pengajuan->nama_pengajuan ?? '-') ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php if (empty($items)): ?>
            <tr><td colspan="11" class="center">Tidak ada item pengajuan.</td></tr>
        <?php else: foreach ($items as $i => $item): ?>
            <?php
            $vol = (float) ($item->vol ?? 0);
            $harga_sat = (float) ($item->harga_penawaran_sat ?? 0);
            $jumlah_penawaran = $vol * $harga_sat;
            $raw_nego_vol = isset($item->hasil_negosiasi_vol) ? $item->hasil_negosiasi_vol : null;
            $raw_nego_sat = isset($item->hasil_negosiasi_sat) ? $item->hasil_negosiasi_sat : null;
            $nego_vol = ($raw_nego_vol !== null && $raw_nego_vol !== '') ? (float) $raw_nego_vol : $vol;
            $nego_sat = ($raw_nego_sat !== null && $raw_nego_sat !== '') ? (float) $raw_nego_sat : 0;
            $jumlah_negosiasi = $show_result ? ($nego_vol * $nego_sat) : 0;
            $sub_penawaran += $jumlah_penawaran;
            $sub_negosiasi += $jumlah_negosiasi;
            ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td><?= ba_excel_escape($item->uraian_barang ?? '-') ?></td>
                <td class="center"><?= ba_excel_num($vol) ?></td>
                <td class="center"><?= ba_excel_escape($item->satuan ?? '-') ?></td>
                <td class="right"><?= ba_excel_rp($harga_sat) ?></td>
                <td class="right"><?= ba_excel_rp($jumlah_penawaran) ?></td>
                <td><?= ba_excel_escape($item->uraian_barang ?? '-') ?></td>
                <td class="center"><?= $show_result ? ba_excel_num($nego_vol) : '' ?></td>
                <td class="right"><?= $show_result ? ba_excel_rp($nego_sat) : '' ?></td>
                <td class="right"><?= $show_result ? ba_excel_rp($jumlah_negosiasi) : '' ?></td>
                <td><?= $show_result ? ba_excel_escape(!empty($item->garansi) ? $item->garansi : '-') : 'Menunggu Deal' ?></td>
            </tr>
        <?php endforeach; endif; ?>
        <?php
        $ppn_penawaran = $sub_penawaran * 0.11;
        $total_penawaran = $sub_penawaran + $ppn_penawaran;
        $ppn_negosiasi = $sub_negosiasi * 0.11;
        $total_negosiasi = $sub_negosiasi + $ppn_negosiasi;
        ?>
        <tr>
            <td colspan="5" class="right bold">Sub Total :</td>
            <td class="right bold"><?= ba_excel_rp($sub_penawaran) ?></td>
            <td colspan="3" class="right bold">Sub Total :</td>
            <td class="right bold"><?= $show_result ? ba_excel_rp($sub_negosiasi) : '' ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="5" class="right bold">PPN 11% :</td>
            <td class="right bold"><?= ba_excel_rp($ppn_penawaran) ?></td>
            <td colspan="3" class="right bold">PPN 11% :</td>
            <td class="right bold"><?= $show_result ? ba_excel_rp($ppn_negosiasi) : '' ?></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="5" class="right bold">Total :</td>
            <td class="right bold"><?= ba_excel_rp($total_penawaran) ?></td>
            <td colspan="3" class="right bold">Total :</td>
            <td class="right bold"><?= $show_result ? ba_excel_rp($total_negosiasi) : '' ?></td>
            <td></td>
        </tr>
        <tr><td colspan="11" class="no-border">&nbsp;</td></tr>
        <tr>
            <td colspan="5" class="no-border">Bandung, <?= date('d F Y') ?></td>
            <td colspan="2" class="no-border"></td>
            <td colspan="4" class="no-border">Status: <?= ba_excel_escape($pengajuan->status ?? '-') ?></td>
        </tr>
        <tr>
            <td colspan="5" class="no-border bold">FAKULTAS INDUSTRI KREATIF</td>
            <td colspan="2" class="no-border"></td>
            <td colspan="4" class="no-border bold"><?= ba_excel_escape(strtoupper($role_label)) ?></td>
        </tr>
        <tr><td colspan="11" class="no-border" style="height: 52px;">&nbsp;</td></tr>
        <tr>
            <td colspan="5" class="no-border">1. ........................................</td>
            <td colspan="2" class="no-border"></td>
            <td colspan="4" class="no-border">1. ........................................</td>
        </tr>
    </table>
    <?php if ($index_pengajuan < count($pengajuan_list) - 1): ?><div class="page-break"></div><?php endif; ?>
<?php endforeach; endif; ?>
</body>
</html>
