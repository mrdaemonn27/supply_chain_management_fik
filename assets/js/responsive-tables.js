(function () {
    'use strict';

    var TABLE_SELECTOR = 'table:not([data-scm-table-ignore])';
    var SHELL_SELECTOR = '.table-responsive, [class*="table-wrap"], [class*="table-container"]';

    function normalized(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .replace(/[:*]+$/g, '')
            .trim()
            .toLowerCase();
    }

    function columnType(label) {
        if (/^(pilih|select)$/.test(label)) return 'select';
        if (/^(no\.?|nomor|#)$/.test(label)) return 'index';
        if (/(aksi|action|kelola|operasi)/.test(label)) return 'action';
        if (/(gambar|foto|thumbnail|preview)/.test(label)) return 'media';
        if (/(progress|progres|tahap|alur)/.test(label)) return 'progress';
        if (/(status|kondisi|approval|persetujuan)/.test(label)) return 'status';
        if (/(tanggal|jadwal|masa|periode|waktu|mulai|selesai|dibuat|diubah)/.test(label)) return 'date';
        if (/(jumlah|qty|kuantitas|total|stok|unit|volume|tersedia)/.test(label)) return 'number';
        if (/(^kode$|kode |id transaksi|nim|nip|nomor dokumen|no\. dokumen|no\.? peminjaman|nomor peminjaman|no\.? pengajuan|nomor pengajuan)/.test(label)) return 'code';
        return 'primary';
    }

    function directHeaderCells(table) {
        var rows = table.tHead ? Array.prototype.slice.call(table.tHead.rows) : [];
        if (!rows.length) return [];
        return Array.prototype.slice.call(rows[rows.length - 1].cells);
    }

    function resolveShell(table) {
        var shell = table.closest(SHELL_SELECTOR);
        if (shell) return shell;

        var parent = table.parentElement;
        if (parent && parent.children.length === 1) return parent;
        return table;
    }

    function markLongCell(cell) {
        if (cell.querySelector('input, select, textarea, form, button, .btn, [role="button"]')) return;

        var value = String(cell.innerText || cell.textContent || '').replace(/\s+/g, ' ').trim();
        if (value.length < 44) return;

        cell.classList.add('scm-cell-long');
        if (!cell.hasAttribute('title')) cell.setAttribute('title', value);
    }

    function enhanceTable(table) {
        if (!(table instanceof HTMLTableElement)) return;
        if (!table.tHead || !table.tBodies.length) return;

        var headers = directHeaderCells(table);
        if (!headers.length) return;

        var labels = headers.map(function (header, index) {
            var label = String(header.innerText || header.textContent || '').replace(/\s+/g, ' ').trim();
            if (!label && header.querySelector('input[type="checkbox"]')) return 'Pilih';
            return label || ('Kolom ' + (index + 1));
        });
        var types = labels.map(function (label) { return columnType(normalized(label)); });

        table.dataset.scmTableReady = '1';
        table.classList.add('scm-responsive-table');
        if (headers.length >= 8) table.classList.add('scm-table-dense');
        if (headers.length >= 10) table.classList.add('scm-table-extra-dense');

        resolveShell(table).classList.add('scm-table-shell');

        headers.forEach(function (header, index) {
            header.classList.add('scm-col-' + types[index]);
        });

        Array.prototype.forEach.call(table.tBodies, function (tbody) {
            Array.prototype.forEach.call(tbody.rows, function (row) {
                var cells = Array.prototype.slice.call(row.cells);
                var isEmpty = cells.length === 1 && (
                    cells[0].colSpan > 1 ||
                    cells[0].classList.contains('text-center') ||
                    /tidak ada|belum ada|kosong|memuat/i.test(cells[0].textContent || '')
                );

                if (isEmpty) row.classList.add('scm-empty-row');

                cells.forEach(function (cell, index) {
                    if (!isEmpty) cell.setAttribute('data-label', labels[index] || 'Detail');
                    cell.classList.add('scm-col-' + (types[index] || 'primary'));
                    markLongCell(cell);
                });
            });
        });
    }

    function scan(root) {
        if (!root) return;
        if (root.matches && root.matches(TABLE_SELECTOR)) enhanceTable(root);
        if (root.closest) {
            var ownerTable = root.closest(TABLE_SELECTOR);
            if (ownerTable) enhanceTable(ownerTable);
        }
        if (!root.querySelectorAll) return;
        Array.prototype.forEach.call(root.querySelectorAll(TABLE_SELECTOR), enhanceTable);
    }

    function start() {
        scan(document);

        if (!window.MutationObserver || !document.body) return;
        var observer = new MutationObserver(function (mutations) {
            var affectedTables = new Set();
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches(TABLE_SELECTOR)) affectedTables.add(node);
                    if (node.querySelectorAll) {
                        Array.prototype.forEach.call(node.querySelectorAll(TABLE_SELECTOR), function (table) { affectedTables.add(table); });
                    }
                    if (node.matches && node.matches('tr, td, th, tbody, thead')) {
                        var ownerTable = node.closest(TABLE_SELECTOR);
                        if (ownerTable) affectedTables.add(ownerTable);
                    }
                });
            });
            affectedTables.forEach(enhanceTable);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
}());
