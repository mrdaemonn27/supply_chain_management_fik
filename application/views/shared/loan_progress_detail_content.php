<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="loan-progress loan-progress--detailed" aria-label="Status peminjaman: <?= html_escape($loan_progress['status_label']); ?>">
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
    <div class="loan-progress__all-heading"><i class="bi bi-list-check" aria-hidden="true"></i>Alur lengkap peminjaman</div>
    <ol class="loan-progress__steps loan-progress__steps--visible">
        <?php foreach ($loan_progress['steps'] as $loan_progress_step): ?>
            <li class="loan-progress__step <?= html_escape($loan_progress_step['state']); ?>">
                <span class="loan-progress__step-number" aria-hidden="true"><?= (int) $loan_progress_step['number']; ?></span>
                <span class="loan-progress__step-copy"><strong><?= html_escape($loan_progress_step['label']); ?></strong><small><?= html_escape($loan_progress_step['description']); ?></small><em><?= html_escape($loan_progress_step['state_label']); ?></em></span>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
