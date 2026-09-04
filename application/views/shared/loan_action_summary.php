<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$summary_item = isset($loan_action_item) && is_object($loan_action_item) ? $loan_action_item : null;
if (!$summary_item) return;

$summary_details = isset($summary_item->detail_barang) && is_array($summary_item->detail_barang)
    ? $summary_item->detail_barang
    : [];
$summary_total_units = 0;
foreach ($summary_details as $summary_detail) {
    $summary_total_units += max(0, (int) ($summary_detail->jumlah_pinjam ?? 0));
}
if ($summary_total_units === 0 && isset($summary_item->total_jumlah)) {
    $summary_total_units = max(0, (int) $summary_item->total_jumlah);
}
$summary_total_types = count($summary_details);
if ($summary_total_types === 0 && isset($summary_item->total_jenis)) {
    $summary_total_types = max(0, (int) $summary_item->total_jenis);
}
$summary_days = durasi_pinjam_hari($summary_item->tanggal_pinjam ?? null, $summary_item->tanggal_kembali_rencana ?? null);
$summary_identifier = $summary_item->group_id ?? $summary_item->id_peminjaman ?? '-';
$summary_show_status = !isset($loan_action_show_status) || $loan_action_show_status;
?>
<section class="loan-action-summary" aria-label="Ringkasan transaksi peminjaman">
    <div class="loan-action-summary__heading">
        <div>
            <div class="loan-action-summary__eyebrow">Ringkasan transaksi</div>
            <div class="loan-action-summary__id"><?= html_escape($summary_identifier) ?></div>
        </div>
        <?php if ($summary_show_status): ?>
            <span class="loan-action-summary__status"><?= html_escape($summary_item->status ?? '-') ?></span>
        <?php endif; ?>
    </div>

    <div class="loan-action-summary__metrics">
        <div class="loan-action-summary__metric"><span>Jenis barang</span><strong><?= $summary_total_types ?></strong></div>
        <div class="loan-action-summary__metric"><span>Total unit</span><strong><?= $summary_total_units ?></strong></div>
        <div class="loan-action-summary__metric"><span>Lama pinjam</span><strong><?= $summary_days > 0 ? $summary_days . ' hari' : '-' ?></strong></div>
    </div>

    <dl class="loan-action-summary__facts">
        <div><dt>Peminjam</dt><dd><?= html_escape($summary_item->nama_peminjam ?? '-') ?></dd></div>
        <div><dt>NIM/NIP</dt><dd><?= html_escape($summary_item->nim_nip ?? '-') ?></dd></div>
        <div><dt>Program studi</dt><dd><?= html_escape($summary_item->prodi ?? $summary_item->prodi_peminjam ?? '-') ?></dd></div>
        <div><dt>Status peminjam</dt><dd><?= html_escape($summary_item->jenis_peminjam ?? '-') ?></dd></div>
        <div><dt>Tanggal pinjam</dt><dd><?= html_escape(tanggal_indonesia($summary_item->tanggal_pinjam ?? null)) ?></dd></div>
        <div><dt>Rencana kembali</dt><dd><?= html_escape(tanggal_indonesia($summary_item->tanggal_kembali_rencana ?? null)) ?></dd></div>
        <div class="loan-action-summary__fact-wide"><dt>Keperluan</dt><dd><?= nl2br(html_escape($summary_item->keperluan ?? '-')) ?></dd></div>
    </dl>

    <div class="loan-action-summary__table-wrap">
        <table class="table table-sm align-middle loan-action-summary__table mb-0">
            <thead><tr><th>Barang</th><th>Kode</th><th>Ruangan</th><th class="text-end">Unit</th></tr></thead>
            <tbody>
            <?php if (empty($summary_details)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Detail barang tidak tersedia.</td></tr>
            <?php else: foreach ($summary_details as $summary_detail): ?>
                <tr>
                    <td class="fw-semibold"><?= html_escape($summary_detail->nama_aset ?? '-') ?></td>
                    <td><?= html_escape($summary_detail->kode_aset ?? '-') ?></td>
                    <td><?= html_escape($summary_detail->nama_ruangan ?? 'Belum ditentukan') ?></td>
                    <td class="text-end fw-semibold"><?= (int) ($summary_detail->jumlah_pinjam ?? 0) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php unset($summary_item, $summary_details, $summary_total_units, $summary_total_types, $summary_days, $summary_identifier, $summary_show_status, $summary_detail); ?>
