(function (window, document) {
    'use strict';
    if (window.__scmFilterAutocompleteReady) return;
    window.__scmFilterAutocompleteReady = true;

    const inputSelector = [
        'input[type="search"]',
        '.admin-multi-filter__value',
        '.admin-filter-value',
        '.kaprodi-filter-value',
        '.kaur-filter-value',
        '.kp-multi-filter-value'
    ].join(',');
    const fieldSelectors = [
        '.admin-multi-filter__field',
        '.admin-filter-field',
        '.kaprodi-filter-field',
        '.kaur-filter-field',
        '.kp-multi-filter-field'
    ].join(',');
    const rowSelector = '.admin-multi-filter__row, .admin-filter-row, [data-filter-row]';
    const normalize = value => String(value || '').trim().toLocaleLowerCase('id');
    const popup = document.createElement('div');
    popup.className = 'scm-autocomplete';
    popup.hidden = true;
    popup.setAttribute('role', 'listbox');
    popup.setAttribute('aria-label', 'Saran pencarian');
    document.body.appendChild(popup);

    let activeInput = null;
    let activeIndex = -1;
    let suggestions = [];
    const eligible = input => input?.matches?.(inputSelector) && input.type !== 'date';

    function unique(values) {
        const found = new Set();
        return values.map(value => String(value || '').replace(/\s+/g, ' ').trim())
            .filter(value => value.length >= 2 && value.length <= 120)
            .filter(value => {
                const key = normalize(value);
                if (!key || found.has(key)) return false;
                found.add(key);
                return true;
            });
    }

    function explicitValues(input) {
        const owner = input.closest('[data-autocomplete-values]') || input;
        const encoded = owner.dataset.autocompleteValues || input.dataset.autocompleteValues || '';
        if (!encoded) return [];
        try {
            const parsed = JSON.parse(encoded);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function selectedField(input) {
        const row = input.closest(rowSelector);
        return row?.querySelector(fieldSelectors)?.value || '';
    }

    function valuesFromNode(node, raw) {
        const values = [raw];
        node.querySelectorAll('td, h5, [data-autocomplete-label], .card-title, .fw-semibold').forEach(child => {
            child.innerText.split(/\n+/).forEach(value => values.push(value));
        });
        return values;
    }

    function documentValues(input) {
        const values = explicitValues(input);
        const field = selectedField(input).replace(/[^a-zA-Z0-9_-]/g, '');
        const attribute = field ? 'data-filter-' + field : 'data-search';
        document.querySelectorAll('[' + attribute + ']').forEach(node => {
            values.push.apply(values, valuesFromNode(node, node.getAttribute(attribute) || ''));
        });

        if (input.classList.contains('faq-search__input')) {
            document.querySelectorAll('[data-faq-question], .faq-question, .faq-assistant__question').forEach(node => values.push(node.innerText));
        }

        if (!values.length) {
            document.querySelectorAll('table tbody tr').forEach(row => {
                row.querySelectorAll('td').forEach(cell => cell.innerText.split(/\n+/).forEach(value => values.push(value)));
            });
        }
        return unique(values);
    }

    function position() {
        if (!activeInput || popup.hidden) return;
        const rect = activeInput.getBoundingClientRect();
        popup.style.left = Math.max(8, rect.left) + 'px';
        popup.style.top = Math.min(window.innerHeight - 80, rect.bottom + 6) + 'px';
        popup.style.width = Math.max(240, rect.width) + 'px';
    }

    function render(input) {
        activeInput = input;
        input.setAttribute('autocomplete', 'off');
        const query = normalize(input.value);
        suggestions = documentValues(input)
            .filter(value => !query || normalize(value).includes(query))
            .filter(value => normalize(value) !== query)
            .slice(0, 8);
        activeIndex = -1;
        popup.replaceChildren();

        if (!suggestions.length) {
            popup.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            return;
        }

        suggestions.forEach((value, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'scm-autocomplete__item';
            button.setAttribute('role', 'option');
            button.dataset.index = String(index);
            const icon = document.createElement('i');
            icon.className = 'bi bi-search';
            icon.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.textContent = value;
            button.append(icon, label);
            popup.appendChild(button);
        });
        popup.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        position();
    }

    function choose(index) {
        if (!activeInput || !suggestions[index]) return;
        activeInput.value = suggestions[index];
        activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        activeInput.dispatchEvent(new Event('change', { bubbles: true }));
        close();
        activeInput.focus();
    }

    function close() {
        if (activeInput) activeInput.setAttribute('aria-expanded', 'false');
        popup.hidden = true;
        activeIndex = -1;
    }

    function markActive(index) {
        const items = Array.from(popup.querySelectorAll('.scm-autocomplete__item'));
        if (!items.length) return;
        activeIndex = (index + items.length) % items.length;
        items.forEach((item, itemIndex) => {
            const active = itemIndex === activeIndex;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        items[activeIndex].scrollIntoView({ block: 'nearest' });
    }

    document.addEventListener('focusin', event => {
        if (eligible(event.target)) render(event.target);
    });
    document.addEventListener('input', event => {
        if (eligible(event.target)) render(event.target);
    });
    document.addEventListener('change', event => {
        if (!event.target.matches(fieldSelectors)) return;
        const input = event.target.closest(rowSelector)?.querySelector(inputSelector);
        if (eligible(input) && document.activeElement === input) render(input);
        else close();
    });
    document.addEventListener('keydown', event => {
        if (event.target !== activeInput || popup.hidden) return;
        if (event.key === 'ArrowDown') { event.preventDefault(); markActive(activeIndex + 1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); markActive(activeIndex - 1); }
        else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); choose(activeIndex); }
        else if (event.key === 'Escape') close();
    });
    popup.addEventListener('mousedown', event => {
        const item = event.target.closest('.scm-autocomplete__item');
        if (!item) return;
        event.preventDefault();
        choose(Number(item.dataset.index));
    });
    document.addEventListener('mousedown', event => {
        if (event.target !== activeInput && !popup.contains(event.target)) close();
    });
    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);
}(window, document));
