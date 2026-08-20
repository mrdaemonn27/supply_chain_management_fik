(function () {
    'use strict';

    function init(root) {
        var form = root.querySelector('[data-bulk-form]');
        var toolbar = root.querySelector('[data-bulk-toolbar]');
        var selectAll = root.querySelector('[data-bulk-select-all]');
        var checks = Array.from(root.querySelectorAll('[data-bulk-row]:not(:disabled)'));
        var count = root.querySelector('[data-bulk-count]');
        var note = form ? form.querySelector('[name="bulk_note"]') : null;
        var rejectReason = root.querySelector('[data-bulk-reject-reason]');
        var rejectTrigger = root.querySelector('[data-bulk-reject-submit]');
        var rejectAction = form ? form.querySelector('[data-bulk-reject-action]') : null;
        var approveAction = form ? form.querySelector('[data-bulk-approve-action]') : null;
        if (!form || !toolbar || !selectAll || !checks.length) return;

        function visibleChecks() {
            return checks.filter(function (check) {
                var row = check.closest('tr');
                return !row || !row.hidden;
            });
        }

        function selectedChecks() {
            return checks.filter(function (check) { return check.checked; });
        }

        function sync() {
            var visible = visibleChecks();
            var selectedVisible = visible.filter(function (check) { return check.checked; });
            var selected = selectedChecks().length;
            toolbar.hidden = selected === 0;
            if (count) count.textContent = selected + ' data terpilih';
            selectAll.checked = visible.length > 0 && selectedVisible.length === visible.length;
            selectAll.indeterminate = selectedVisible.length > 0 && selectedVisible.length < visible.length;
            selectAll.disabled = visible.length === 0;
        }

        selectAll.addEventListener('change', function () {
            visibleChecks().forEach(function (check) { check.checked = selectAll.checked; });
            sync();
        });
        checks.forEach(function (check) { check.addEventListener('change', sync); });

        if (approveAction) {
            approveAction.addEventListener('click', function (event) {
                if (!window.confirm('Setujui semua pengajuan yang dipilih?')) event.preventDefault();
                if (note) note.value = '';
            });
        }

        if (rejectTrigger && rejectReason && rejectAction) {
            rejectTrigger.addEventListener('click', function () {
                var value = rejectReason.value.trim();
                if (!value) {
                    rejectReason.setCustomValidity('Alasan penolakan wajib diisi.');
                    rejectReason.reportValidity();
                    return;
                }
                rejectReason.setCustomValidity('');
                if (note) note.value = value;
                form.requestSubmit(rejectAction);
            });
            rejectReason.addEventListener('input', function () { rejectReason.setCustomValidity(''); });
        }

        new MutationObserver(sync).observe(root, { subtree: true, attributes: true, attributeFilter: ['hidden'] });
        sync();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bulk-approval]').forEach(init);
    });
}());
