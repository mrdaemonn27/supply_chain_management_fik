<?php
$loan_progress = scm_loan_progress($loan_progress_item);
$loan_progress_extra_class = !empty($loan_progress_compact) ? ' loan-progress--compact' : '';
?>
<div class="loan-progress<?= $loan_progress_extra_class; ?>" aria-label="Status peminjaman: <?= html_escape($loan_progress['status_label']); ?>">
    <div class="loan-progress__heading">
        <span class="loan-progress__stage"><?= html_escape($loan_progress['stage_label']); ?></span>
        <span class="loan-progress__count">Tahap <?= (int) $loan_progress['current_index'] + 1; ?> dari <?= (int) $loan_progress['total_steps']; ?></span>
    </div>
    <div class="loan-progress__track" aria-hidden="true">
        <?php foreach ($loan_progress['steps'] as $loan_progress_step): ?>
            <span class="loan-progress__unit <?= html_escape($loan_progress_step['state']); ?>" title="<?= html_escape($loan_progress_step['label']); ?>">
                <span class="loan-progress__dot"></span>
            </span>
        <?php endforeach; ?>
    </div>
    <span class="loan-progress__status is-<?= html_escape($loan_progress['tone']); ?>"><?= html_escape($loan_progress['status_label']); ?></span>
    <?php if ($loan_progress['raw_status'] === 'Menunggu ACC Kaprodi' && !empty($loan_progress['kaprodi_deadline_at'])): ?>
        <span class="d-block mt-1 text-muted" style="font-size:.64rem;"><i class="bi bi-clock me-1" aria-hidden="true"></i>Batas: <?= html_escape(date('d/m/Y H:i', strtotime($loan_progress['kaprodi_deadline_at']))) ?></span>
    <?php endif; ?>
</div>
<?php unset($loan_progress, $loan_progress_extra_class, $loan_progress_step); ?>
