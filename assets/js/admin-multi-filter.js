(function (window, document) {
    'use strict';
    if (window.AdminMultiFilter?.__ready) return;

    const normalize = value => String(value || '')
        .trim()
        .toLocaleLowerCase('id')
        .replace(/laboratorium/g, 'lab')
        .replace(/[._\-/]+/g, ' ')
        .replace(/\s+/g, ' ');
    const roots = new WeakMap();

    function matchesDateRange(haystack, value) {
        const match = String(value || '').match(/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/);
        if (!match) return null;
        const dates = String(haystack || '').match(/\d{4}-\d{2}-\d{2}/g) || [];
        if (!dates.length) return false;
        const rowStart = dates.slice().sort()[0];
        const rowEnd = dates.slice().sort().pop();
        return rowStart <= match[2] && rowEnd >= match[1];
    }

    function fields(root) {
        try { return JSON.parse(root.dataset.fields || '{}'); } catch (error) { return {}; }
    }

    function criteria(root) {
        return Array.from(root.querySelectorAll('.admin-multi-filter__row')).map(row => ({
            field: row.querySelector('.admin-multi-filter__field')?.value || '',
            value: row.querySelector('.admin-multi-filter__value')?.value.trim() || ''
        })).filter(item => item.field && item.value);
    }

    function refresh(root) {
        const rows = Array.from(root.querySelectorAll('.admin-multi-filter__row'));
        rows.forEach(row => {
            const remove = row.querySelector('[data-filter-remove]');
            const add = row.querySelector('[data-filter-add]');
            if (remove) remove.disabled = rows.length === 1;
            if (add) add.disabled = rows.length >= 4;
        });
    }

    function configureValue(root, row) {
        const select = row.querySelector('.admin-multi-filter__field');
        const input = row.querySelector('.admin-multi-filter__value');
        const config = fields(root)[select.value] || {};
        input.type = config.type || 'search';
        input.placeholder = config.placeholder || ('Cari ' + (config.label || select.options[select.selectedIndex]?.text || 'data').toLowerCase());
        input.setAttribute('aria-label', input.placeholder);
    }

    function notify(root, immediate) {
        const state = roots.get(root);
        if (!state) return;
        clearTimeout(state.timer);
        const apply = () => {
            if (root.dataset.mode === 'server') {
                const form = root.closest('form');
                if (form) {
                    const page = form.querySelector('[name="page"]');
                    if (page) page.value = '1';
                    form.requestSubmit();
                }
                return;
            }
            root.dispatchEvent(new CustomEvent('admin-multi-filter-change', { bubbles: true, detail: { criteria: criteria(root) } }));
        };
        if (immediate) {
            apply();
            return;
        }
        state.timer = setTimeout(apply, root.dataset.mode === 'server' ? 500 : 120);
    }

    function addRow(root, afterRow) {
        const rows = root.querySelectorAll('.admin-multi-filter__row');
        if (rows.length >= 4) return;
        const template = root.querySelector('template[data-filter-template]');
        const row = template.content.firstElementChild.cloneNode(true);
        afterRow.after(row);
        configureValue(root, row);
        refresh(root);
        row.querySelector('.admin-multi-filter__field').focus();
    }

    function init(root) {
        if (root.dataset.adminMultiFilterReady === 'true') return;
        root.dataset.adminMultiFilterReady = 'true';
        roots.set(root, { timer: null });
        root.querySelectorAll('.admin-multi-filter__row').forEach(row => configureValue(root, row));
        root.addEventListener('click', event => {
            const add = event.target.closest('[data-filter-add]');
            const remove = event.target.closest('[data-filter-remove]');
            const apply = event.target.closest('[data-filter-apply]');
            const reset = event.target.closest('[data-filter-reset]');
            if (apply) { notify(root, true); return; }
            if (reset) { window.AdminMultiFilter.reset(root, true); return; }
            if (add) { addRow(root, add.closest('.admin-multi-filter__row')); return; }
            if (remove && root.querySelectorAll('.admin-multi-filter__row').length > 1) {
                remove.closest('.admin-multi-filter__row').remove();
                refresh(root);
                notify(root);
            }
        });
        root.addEventListener('change', event => {
            if (event.target.matches('.admin-multi-filter__field')) configureValue(root, event.target.closest('.admin-multi-filter__row'));
            if (event.target.matches('.admin-multi-filter__field, .admin-multi-filter__value')) notify(root);
        });
        root.addEventListener('input', event => {
            if (event.target.matches('.admin-multi-filter__value')) notify(root);
        });
        refresh(root);
    }

    window.AdminMultiFilter = {
        __ready: true,
        getCriteria: criteria,
        matches: function (row, items) {
            return items.every(item => {
                const haystack = row.dataset['filter' + item.field.charAt(0).toUpperCase() + item.field.slice(1)];
                const dateMatch = matchesDateRange(haystack, item.value);
                return dateMatch === null ? normalize(haystack).includes(normalize(item.value)) : dateMatch;
            });
        },
        reset: function (root, immediate) {
            const rows = Array.from(root.querySelectorAll('.admin-multi-filter__row'));
            rows.slice(1).forEach(row => row.remove());
            const select = rows[0]?.querySelector('.admin-multi-filter__field');
            if (select && select.options.length) select.selectedIndex = 0;
            const input = rows[0]?.querySelector('.admin-multi-filter__value');
            if (input) input.value = '';
            if (rows[0]) configureValue(root, rows[0]);
            refresh(root);
            notify(root, Boolean(immediate));
        }
    };

    document.querySelectorAll('[data-admin-multi-filter]').forEach(init);
}(window, document));
