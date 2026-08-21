(function (window, document) {
    'use strict';

    var formatter = new Intl.NumberFormat('id-ID');
    var updating = false;
    var scheduled = false;
    var sequence = 0;

    function numberFromText(value) {
        var digits = String(value || '').replace(/[^0-9]/g, '');
        return digits === '' ? 0 : Number(digits);
    }

    function paginationFooters() {
        return Array.from(document.querySelectorAll(
            '[class*="pagination-footer"], .kp-pagination-footer, .blokir-footer, .distribution-pagination'
        )).filter(function (footer) {
            return (footer.dataset.scmPaginationEnhanced === 'true' || footer.querySelector('select')) && footer.querySelector('nav');
        });
    }

    function normalizeOptions(select) {
        var current = select.value === 'all' ? '100' : select.value;
        var labels = ['10', '25', '50', '100'];
        var changed = !labels.includes(current);
        select.innerHTML = '';
        labels.forEach(function (value) {
            select.add(new Option(value, value, false, value === current));
        });
        if (!labels.includes(current)) select.value = '10';
        return changed;
    }

    function totalElement(summary) {
        var spans = Array.from(summary.querySelectorAll('span'));
        return spans.find(function (span) { return /total\s*item/i.test(span.textContent); }) || spans[0] || null;
    }

    function updateTotal(summary) {
        var total = totalElement(summary);
        if (!total) return 0;
        var value = numberFromText(total.textContent);
        var nested = Array.from(total.querySelectorAll('span')).find(function (span) {
            return /^\s*[\d.]+\s*$/.test(span.textContent);
        });
        if (nested) nested.textContent = formatter.format(value);
        else total.textContent = 'Total item: ' + formatter.format(value);
        return value;
    }

    function findTableAnchor(footer) {
        var parent = footer.parentElement;
        var siblings = parent ? Array.from(parent.children) : [];
        var footerIndex = siblings.indexOf(footer);
        var candidate = siblings.slice(0, footerIndex).reverse().find(function (element) {
            return element.matches('.table-responsive, [class*="table-wrap"], [class*="table-container"]') || element.querySelector('table');
        });
        if (candidate) return candidate;

        var table = parent ? parent.querySelector('table') : null;
        if (!table) return footer;
        return table.closest('.table-responsive, [class*="table-wrap"], [class*="table-container"]') || table;
    }

    function updateStatus(footer, summary, select) {
        var status = footer.querySelector('[class*="pagination-status"], [class*="page-status"], [class*="page-info"]');
        if (!status) return;
        var pageMatch = status.textContent.match(/Halaman\s*:\s*(\d+)/i);
        if (pageMatch) status.dataset.scmPage = pageMatch[1];
        var page = Math.max(1, Number(status.dataset.scmPage || 1));
        var perPage = Math.max(1, Number(select.value || 10));
        var total = updateTotal(summary);
        var first = total > 0 ? ((page - 1) * perPage) + 1 : 0;
        var last = total > 0 ? Math.min(total, page * perPage) : 0;
        if (first > total && total > 0) {
            first = Math.max(1, total - ((total - 1) % perPage));
            last = total;
        }
        var label = total > 0
            ? 'Menampilkan ' + formatter.format(first) + '–' + formatter.format(last) + ' dari ' + formatter.format(total) + ' data'
            : 'Menampilkan 0 dari 0 data';
        if (status.textContent !== label) status.textContent = label;
    }

    function enhance(footer) {
        var top = footer.dataset.scmPaginationTop
            ? document.getElementById(footer.dataset.scmPaginationTop)
            : null;
        var select = top ? top.querySelector('select') : footer.querySelector('select');
        var summary = select ? select.closest('div') : null;
        if (!select || !summary) return;

        if (footer.dataset.scmPaginationEnhanced !== 'true') {
            var optionChanged = normalizeOptions(select);
            top = document.createElement('div');
            top.id = 'scmPaginationTop' + (++sequence);
            top.className = 'scm-pagination-top';
            top.setAttribute('aria-label', 'Pengaturan jumlah data');
            var anchor = findTableAnchor(footer);
            anchor.parentNode.insertBefore(top, anchor);
            top.appendChild(summary);
            summary.classList.add('scm-pagination-top__summary');
            footer.classList.add('scm-pagination-bottom');
            footer.dataset.scmPaginationEnhanced = 'true';
            footer.dataset.scmPaginationTop = top.id;
            if (optionChanged) window.setTimeout(function () {
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }, 0);
        }
        updateStatus(footer, summary, select);
    }

    function scan() {
        if (updating) return;
        updating = true;
        paginationFooters().forEach(enhance);
        updating = false;
        scheduled = false;
    }

    function schedule() {
        if (updating || scheduled) return;
        scheduled = true;
        window.setTimeout(scan, 0);
    }

    new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true, characterData: true });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan, { once: true });
    else scan();
}(window, document));
