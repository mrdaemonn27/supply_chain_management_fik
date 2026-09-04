<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$loan_progress = scm_loan_progress($loan_progress_item);
$loan_progress_is_compact = !empty($loan_progress_compact);

if (!$loan_progress_is_compact) {
    include APPPATH . 'views/shared/loan_progress_detail_content.php';
    unset($loan_progress, $loan_progress_is_compact, $loan_progress_step);
    return;
}

$loan_progress_record_id = (int) ($loan_progress_item->id_peminjaman ?? 0);
$loan_progress_modal_id = 'loanProgressDetail' . $loan_progress_record_id;
$loan_progress_external_target = isset($loan_progress_detail_target) && trim((string) $loan_progress_detail_target) !== ''
    ? trim((string) $loan_progress_detail_target)
    : '';
$loan_progress_target = $loan_progress_external_target !== '' ? $loan_progress_external_target : '#' . $loan_progress_modal_id;
?>
<div class="loan-progress loan-progress--compact" aria-label="Status peminjaman: <?= html_escape($loan_progress['status_label']); ?>">
    <div class="loan-progress__heading">
        <span class="loan-progress__stage"><?= html_escape($loan_progress['stage_label']); ?></span>
        <span class="loan-progress__count">Tahap <?= (int) $loan_progress['current_index'] + 1; ?> dari <?= (int) $loan_progress['total_steps']; ?></span>
    </div>
    <div class="loan-progress__track" role="list" aria-label="Delapan tahap alur peminjaman">
        <?php foreach ($loan_progress['steps'] as $loan_progress_step): ?>
            <span class="loan-progress__unit <?= html_escape($loan_progress_step['state']); ?>" role="listitem" tabindex="0"
                title="Tahap <?= (int) $loan_progress_step['number']; ?>: <?= html_escape($loan_progress_step['label']); ?> — <?= html_escape($loan_progress_step['state_label']); ?>"
                aria-label="Tahap <?= (int) $loan_progress_step['number']; ?> dari <?= (int) $loan_progress['total_steps']; ?>, <?= html_escape($loan_progress_step['label']); ?>, <?= html_escape($loan_progress_step['state_label']); ?>"
                data-loan-step="<?= (int) $loan_progress_step['number']; ?>" data-loan-step-total="<?= (int) $loan_progress['total_steps']; ?>"
                data-loan-step-label="<?= html_escape($loan_progress_step['label']); ?>" data-loan-step-description="<?= html_escape($loan_progress_step['description']); ?>" data-loan-step-state="<?= html_escape($loan_progress_step['state_label']); ?>">
                <span class="loan-progress__dot" aria-hidden="true"></span>
            </span>
        <?php endforeach; ?>
    </div>
    <span class="loan-progress__status is-<?= html_escape($loan_progress['tone']); ?>"><?= html_escape($loan_progress['status_label']); ?></span>
    <?php if ($loan_progress['raw_status'] === 'Menunggu ACC Kaprodi' && !empty($loan_progress['kaprodi_deadline_at'])): ?>
        <span class="loan-progress__deadline"><i class="bi bi-clock" aria-hidden="true"></i>Batas: <?= html_escape(waktu_indonesia($loan_progress['kaprodi_deadline_at'])) ?></span>
    <?php endif; ?>
    <div class="loan-progress-launcher">
        <button
            type="button"
            class="loan-progress-detail-button"
            data-bs-toggle="modal"
            data-bs-target="<?= html_escape($loan_progress_target); ?>"
            aria-label="Lihat detail delapan tahap peminjaman <?= html_escape($loan_progress_item->group_id ?? $loan_progress_record_id); ?>"
        >
            <i class="bi bi-diagram-3" aria-hidden="true"></i>
            <span>Lihat Detail</span>
        </button>
    </div>
</div>

<?php if ($loan_progress_external_target === ''): ?>
<div
    class="modal fade loan-progress-detail-modal"
    id="<?= html_escape($loan_progress_modal_id); ?>"
    tabindex="-1"
    aria-labelledby="<?= html_escape($loan_progress_modal_id); ?>Title"
    aria-hidden="true"
    data-loan-progress-modal
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="loan-progress-detail-modal__eyebrow">Detail proses</div>
                    <h2 class="modal-title" id="<?= html_escape($loan_progress_modal_id); ?>Title">Progress Peminjaman</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <?php include APPPATH . 'views/shared/loan_progress_detail_content.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php unset($loan_progress, $loan_progress_is_compact, $loan_progress_record_id, $loan_progress_modal_id, $loan_progress_external_target, $loan_progress_target, $loan_progress_step); ?>
