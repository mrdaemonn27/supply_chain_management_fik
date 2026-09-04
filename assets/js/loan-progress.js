(function () {
    'use strict';

    function initLoanProgressLabels() {
        document.querySelectorAll('[data-loan-progress-modal]').forEach(function (modal) {
            if (modal.parentElement !== document.body) document.body.appendChild(modal);
        });
        var steps = Array.from(document.querySelectorAll('[data-loan-step]'));
        if (!steps.length) return;

        var tooltip = document.createElement('div');
        tooltip.id = 'loanProgressTooltip';
        tooltip.className = 'loan-progress-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;
        tooltip.innerHTML = '<span class="loan-progress-tooltip__eyebrow"></span><strong></strong><p></p><em></em>';
        document.body.appendChild(tooltip);
        var activeStep = null;

        function positionTooltip(step) {
            var anchor = step.querySelector('.loan-progress__dot') || step;
            var anchorRect = anchor.getBoundingClientRect();
            var tooltipRect = tooltip.getBoundingClientRect();
            var gap = 10;
            var left = anchorRect.left + (anchorRect.width / 2) - (tooltipRect.width / 2);
            left = Math.max(12, Math.min(left, window.innerWidth - tooltipRect.width - 12));
            var top = anchorRect.top - tooltipRect.height - gap;
            if (top < 12) top = anchorRect.bottom + gap;
            tooltip.style.left = Math.round(left) + 'px';
            tooltip.style.top = Math.round(top) + 'px';
        }

        function showTooltip(step) {
            if (!step) return;
            activeStep?.removeAttribute('aria-describedby');
            activeStep = step;
            tooltip.querySelector('.loan-progress-tooltip__eyebrow').textContent = 'Tahap ' + step.dataset.loanStep + ' dari ' + (step.dataset.loanStepTotal || '8');
            tooltip.querySelector('strong').textContent = step.dataset.loanStepLabel || '-';
            tooltip.querySelector('p').textContent = step.dataset.loanStepDescription || '';
            tooltip.querySelector('em').textContent = step.dataset.loanStepState || '';
            tooltip.hidden = false;
            positionTooltip(step);
            requestAnimationFrame(function () { tooltip.classList.add('is-visible'); });
            step.setAttribute('aria-describedby', tooltip.id);
        }

        function hideTooltip(force) {
            if (!activeStep) return;
            if (!force && document.activeElement === activeStep) return;
            activeStep.removeAttribute('aria-describedby');
            activeStep = null;
            tooltip.classList.remove('is-visible');
            window.setTimeout(function () {
                if (!activeStep) tooltip.hidden = true;
            }, 150);
        }

        steps.forEach(function (step) {
            var nativeTitle = step.getAttribute('title');
            if (nativeTitle) {
                step.dataset.nativeTitle = nativeTitle;
                step.removeAttribute('title');
            }
            step.addEventListener('pointerenter', function () { showTooltip(step); });
            step.addEventListener('pointerleave', function () { hideTooltip(false); });
            step.addEventListener('focus', function () { showTooltip(step); });
            step.addEventListener('blur', function () { hideTooltip(true); });
            step.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideTooltip(true);
                    step.blur();
                }
            });
        });

        window.addEventListener('scroll', function () { hideTooltip(true); }, true);
        window.addEventListener('resize', function () { hideTooltip(true); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoanProgressLabels);
    } else {
        initLoanProgressLabels();
    }
}());
