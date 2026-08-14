(function (window, document) {
    'use strict';

    const normalize = value => String(value || '').trim().toLocaleLowerCase('id');
    const roots = new WeakMap();

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

    function notify(root) {
        const state = roots.get(root);
        if (!state) return;
        clearTimeout(state.timer);
        state.timer = setTimeout(() => {
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
        }, root.dataset.mode === 'server' ? 500 : 120);
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
        roots.set(root, { timer: null });
        root.querySelectorAll('.admin-multi-filter__row').forEach(row => configureValue(root, row));
        root.addEventListener('click', event => {
            const add = event.target.closest('[data-filter-add]');
            const remove = event.target.closest('[data-filter-remove]');
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
        getCriteria: criteria,
        matches: function (row, items) {
            return items.every(item => normalize(row.dataset['filter' + item.field.charAt(0).toUpperCase() + item.field.slice(1)]).includes(normalize(item.value)));
        },
        reset: function (root) {
            const rows = Array.from(root.querySelectorAll('.admin-multi-filter__row'));
            rows.slice(1).forEach(row => row.remove());
            const input = rows[0]?.querySelector('.admin-multi-filter__value');
            if (input) input.value = '';
            refresh(root);
            notify(root);
        }
    };

    document.querySelectorAll('[data-admin-multi-filter]').forEach(init);
}(window, document));
