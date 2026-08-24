(function (window, document) {
    'use strict';

    var WEEKDAYS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    var sequence = 0;
    var scheduled = false;

    function fromIso(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value || ''))) return null;
        var parts = value.split('-');
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return isNaN(date.getTime()) ? null : date;
    }

    function toIso(date) {
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    }

    function sameDay(first, second) {
        return first && second && toIso(first) === toIso(second);
    }

    function addMonths(date, amount) {
        return new Date(date.getFullYear(), date.getMonth() + amount, 1);
    }

    function formatDate(date) {
        return date ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(date) : 'Tanggal mulai';
    }

    function formatMonth(date) {
        return new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date);
    }

    function parseRange(value) {
        var match = String(value || '').match(/^(\d{4}-\d{2}-\d{2})(?:\.\.(\d{4}-\d{2}-\d{2}))?$/);
        if (!match) return { start: null, end: null };
        var start = fromIso(match[1]);
        var end = fromIso(match[2] || match[1]);
        if (!start || !end) return { start: null, end: null };
        return start.getTime() <= end.getTime() ? { start: start, end: end } : { start: end, end: start };
    }

    function isDateFilter(input) {
        var row;
        var select;
        var root;
        var fields;

        if (!input || !input.matches('.admin-multi-filter__value, .admin-filter-value, .kaprodi-filter-value, .kp-multi-filter-value, .kaur-filter-value')) return false;
        row = input.closest('.admin-multi-filter__row, .admin-filter-row, [data-filter-row]');
        if (!row) return false;

        if (input.matches('.admin-multi-filter__value')) {
            root = input.closest('[data-admin-multi-filter]');
            select = row.querySelector('.admin-multi-filter__field');
            try { fields = JSON.parse(root ? root.dataset.fields || '{}' : '{}'); } catch (error) { fields = {}; }
            return !!(select && fields[select.value] && fields[select.value].type === 'date');
        }

        select = row.querySelector('.admin-filter-field, .kaprodi-filter-field, .kp-multi-filter-field, .kaur-filter-field');
        if (!select) return false;
        if (select.selectedOptions && select.selectedOptions[0] && select.selectedOptions[0].dataset.inputType) {
            return select.selectedOptions[0].dataset.inputType === 'date';
        }
        return select.value === 'tanggal' || select.value === 'masa' || select.value === 'periode' || select.value === 'tanggal_bast';
    }

    function buildMonth(monthDate, state) {
        var month = document.createElement('div');
        month.className = 'scm-filter-date-range__month';

        var title = document.createElement('div');
        title.className = 'scm-filter-date-range__month-title';
        title.textContent = formatMonth(monthDate);
        month.appendChild(title);

        var weekdays = document.createElement('div');
        weekdays.className = 'scm-filter-date-range__weekdays';
        WEEKDAYS.forEach(function (weekday) {
            var label = document.createElement('span');
            label.textContent = weekday;
            weekdays.appendChild(label);
        });
        month.appendChild(weekdays);

        var days = document.createElement('div');
        days.className = 'scm-filter-date-range__days';
        if (state.drag) days.classList.add('is-dragging');
        var firstDay = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1).getDay();
        var totalDays = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();
        var visible = visibleRange(state);
        var day;

        for (day = 0; day < firstDay; day += 1) {
            var spacer = document.createElement('span');
            spacer.className = 'scm-filter-date-range__spacer';
            spacer.setAttribute('aria-hidden', 'true');
            days.appendChild(spacer);
        }

        for (day = 1; day <= totalDays; day += 1) {
            var date = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'scm-filter-date-range__day';
            button.dataset.scmRangeDate = toIso(date);
            button.textContent = day;
            button.setAttribute('aria-label', formatDate(date));
            if (visible.start && visible.end && date.getTime() > visible.start.getTime() && date.getTime() < visible.end.getTime()) button.classList.add('is-range');
            if (sameDay(date, visible.start)) button.classList.add('is-start');
            if (sameDay(date, visible.end)) button.classList.add('is-end');
            days.appendChild(button);
        }

        month.appendChild(days);
        return month;
    }

    function visibleRange(state) {
        if (!state.drag) return { start: state.start, end: state.end };
        return state.drag.preview.getTime() < state.drag.anchor.getTime()
            ? { start: state.drag.preview, end: state.drag.anchor }
            : { start: state.drag.anchor, end: state.drag.preview };
    }

    function dispatchValue(input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function enhance(input) {
        if (input.dataset.scmRangeEnhanced === 'true') return;
        var parsed = parseRange(input.value);
        var state = {
            start: parsed.start,
            end: parsed.end,
            view: new Date((parsed.start || new Date()).getFullYear(), (parsed.start || new Date()).getMonth(), 1),
            drag: null,
            ignoreClickUntil: 0
        };
        var root = document.createElement('div');
        var id = 'scmFilterDateRange' + (++sequence);
        root.className = 'scm-filter-date-range';
        root.dataset.scmFilterDateRange = '';
        root.innerHTML =
            '<button type="button" class="scm-filter-date-range__trigger" aria-expanded="false" aria-controls="' + id + '">' +
                '<i class="bi bi-calendar3 scm-filter-date-range__icon" aria-hidden="true"></i>' +
                '<span class="scm-filter-date-range__value is-empty" data-range-start-label>Tanggal mulai</span>' +
                '<i class="bi bi-arrow-right scm-filter-date-range__separator" aria-hidden="true"></i>' +
                '<span class="scm-filter-date-range__value is-empty" data-range-end-label>Tanggal selesai</span>' +
                '<i class="bi bi-chevron-down scm-filter-date-range__chevron" aria-hidden="true"></i>' +
            '</button>' +
            '<section id="' + id + '" class="scm-filter-date-range__panel" hidden aria-label="Pilih rentang tanggal filter">' +
                '<div class="scm-filter-date-range__header"><div><span class="scm-filter-date-range__eyebrow">Pilih rentang tanggal</span><p class="scm-filter-date-range__helper">Pilih tanggal mulai, lalu tanggal selesai.</p></div>' +
                '<div class="scm-filter-date-range__navigation"><button type="button" class="scm-filter-date-range__nav" data-range-prev aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button><button type="button" class="scm-filter-date-range__nav" data-range-next aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button></div></div>' +
                '<div class="scm-filter-date-range__months"></div>' +
                '<div class="scm-filter-date-range__footer"><span class="scm-filter-date-range__hint"><i class="bi bi-info-circle me-1"></i>Klik dua tanggal atau tahan lalu geser.</span><button type="button" class="scm-filter-date-range__clear">Hapus tanggal</button></div>' +
            '</section>';

        input.parentNode.insertBefore(root, input);
        root.appendChild(input);
        input.type = 'hidden';
        input.dataset.scmRangeEnhanced = 'true';

        var trigger = root.querySelector('.scm-filter-date-range__trigger');
        var panel = root.querySelector('.scm-filter-date-range__panel');
        var months = root.querySelector('.scm-filter-date-range__months');
        var helper = root.querySelector('.scm-filter-date-range__helper');
        var startLabel = root.querySelector('[data-range-start-label]');
        var endLabel = root.querySelector('[data-range-end-label]');

        function updateSummary() {
            var range = visibleRange(state);
            startLabel.textContent = range.start ? formatDate(range.start) : 'Tanggal mulai';
            endLabel.textContent = range.end ? formatDate(range.end) : 'Tanggal selesai';
            startLabel.classList.toggle('is-empty', !range.start);
            endLabel.classList.toggle('is-empty', !range.end);
            helper.textContent = state.drag
                ? 'Lepaskan untuk menetapkan rentang tanggal.'
                : (!state.start ? 'Pilih tanggal mulai, lalu tanggal selesai.' : (!state.end ? 'Sekarang pilih tanggal selesai.' : 'Rentang dipilih. Geser ujung tanggal untuk mengubahnya.'));
        }

        function render() {
            months.innerHTML = '';
            months.appendChild(buildMonth(state.view, state));
            months.appendChild(buildMonth(addMonths(state.view, 1), state));
            updateSummary();
        }

        function commit(closePanel) {
            if (!state.start || !state.end) return;
            input.value = toIso(state.start) + '..' + toIso(state.end);
            render();
            dispatchValue(input);
            if (closePanel) setOpen(false);
        }

        function choose(date) {
            if (!state.start || state.end) {
                state.start = date;
                state.end = null;
                render();
                return;
            }
            if (date.getTime() < state.start.getTime()) {
                state.end = state.start;
                state.start = date;
            } else {
                state.end = date;
            }
            commit(true);
        }

        function setOpen(open) {
            document.querySelectorAll('[data-scm-filter-date-range] .scm-filter-date-range__panel:not([hidden])').forEach(function (other) {
                if (other !== panel) {
                    other.hidden = true;
                    other.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
            });
            panel.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
            if (open) render();
        }

        function dateFromPointer(event) {
            var target = document.elementFromPoint(event.clientX, event.clientY);
            var day = target && target.closest('[data-scm-range-date]');
            return day && months.contains(day) ? fromIso(day.dataset.scmRangeDate) : null;
        }

        function finishDrag(event) {
            if (!state.drag || (event.pointerId !== undefined && event.pointerId !== state.drag.pointerId)) return;
            var drag = state.drag;
            state.drag = null;
            state.ignoreClickUntil = Date.now() + 250;
            if (months.hasPointerCapture && months.hasPointerCapture(drag.pointerId)) months.releasePointerCapture(drag.pointerId);
            if (!drag.moved) {
                choose(drag.origin);
                return;
            }
            state.start = drag.preview.getTime() < drag.anchor.getTime() ? drag.preview : drag.anchor;
            state.end = drag.preview.getTime() < drag.anchor.getTime() ? drag.anchor : drag.preview;
            commit(true);
        }

        trigger.addEventListener('click', function () { setOpen(panel.hidden); });
        root.querySelector('[data-range-prev]').addEventListener('click', function () { state.view = addMonths(state.view, -1); render(); });
        root.querySelector('[data-range-next]').addEventListener('click', function () { state.view = addMonths(state.view, 1); render(); });
        root.querySelector('.scm-filter-date-range__clear').addEventListener('click', function () {
            state.start = null;
            state.end = null;
            input.value = '';
            render();
            dispatchValue(input);
            setOpen(false);
        });

        months.addEventListener('pointerdown', function (event) {
            if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return;
            var day = event.target.closest('[data-scm-range-date]');
            if (!day) return;
            var date = fromIso(day.dataset.scmRangeDate);
            if (!date) return;
            event.preventDefault();
            var anchor = date;
            if (state.start && state.end && sameDay(date, state.start)) anchor = state.end;
            else if (state.start && state.end && sameDay(date, state.end)) anchor = state.start;
            state.drag = { origin: date, anchor: anchor, preview: date, moved: false, pointerId: event.pointerId };
            if (months.setPointerCapture) months.setPointerCapture(event.pointerId);
            render();
        });
        months.addEventListener('pointermove', function (event) {
            if (!state.drag || event.pointerId !== state.drag.pointerId) return;
            event.preventDefault();
            var date = dateFromPointer(event);
            if (!date || sameDay(date, state.drag.preview)) return;
            state.drag.preview = date;
            state.drag.moved = true;
            render();
        });
        months.addEventListener('pointerup', finishDrag);
        months.addEventListener('pointercancel', function () { state.drag = null; render(); });
        months.addEventListener('dragstart', function (event) { event.preventDefault(); });
        months.addEventListener('click', function (event) {
            if (Date.now() < state.ignoreClickUntil) return;
            var day = event.target.closest('[data-scm-range-date]');
            var date = day ? fromIso(day.dataset.scmRangeDate) : null;
            if (date) choose(date);
        });
        document.addEventListener('click', function (event) { if (!root.contains(event.target)) setOpen(false); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !panel.hidden) setOpen(false); });
        render();
    }

    function teardown(input) {
        if (!input || input.dataset.scmRangeEnhanced !== 'true') return;
        var root = input.closest('[data-scm-filter-date-range]');
        if (!root || !root.parentNode) return;
        root.parentNode.insertBefore(input, root);
        input.type = 'search';
        delete input.dataset.scmRangeEnhanced;
        root.remove();
    }

    function scan() {
        scheduled = false;
        document.querySelectorAll('.admin-multi-filter__value, .admin-filter-value, .kaprodi-filter-value, .kp-multi-filter-value, .kaur-filter-value').forEach(function (input) {
            if (isDateFilter(input)) enhance(input);
            else teardown(input);
        });
    }

    function scheduleScan() {
        if (scheduled) return;
        scheduled = true;
        window.setTimeout(scan, 0);
    }

    document.addEventListener('change', scheduleScan);
    new MutationObserver(scheduleScan).observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['type'] });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan, { once: true });
    else scan();
}(window, document));
