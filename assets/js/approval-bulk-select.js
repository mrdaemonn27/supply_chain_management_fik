(function () {
    'use strict';

    var REQUEST_TIMEOUT = 20000;
    var BATCH_SIZE = 10;

    function init(root) {
        var form = root.querySelector('[data-bulk-form]');
        var toolbar = root.querySelector('[data-bulk-toolbar]');
        var selectAll = root.querySelector('[data-bulk-select-all]');
        var count = root.querySelector('[data-bulk-count]');
        var note = form ? form.querySelector('[name="bulk_note"]') : null;
        var rejectReason = root.querySelector('[data-bulk-reject-reason]');
        var rejectTrigger = root.querySelector('[data-bulk-reject-submit]');
        var rejectAction = form ? form.querySelector('[data-bulk-reject-action]') : null;
        var approveAction = form ? form.querySelector('[data-bulk-approve-action]') : null;
        var feedback = root.querySelector('[data-bulk-feedback]');
        var noun = root.dataset.bulkNoun || 'pengajuan';
        var successLabel = root.dataset.bulkSuccessLabel || '';
        var confirmMessage = root.dataset.bulkConfirm || '';
        var confirmLabel = root.dataset.bulkConfirmLabel || 'Setujui Proses';
        var reloadOnSuccess = root.dataset.bulkReloadOnSuccess === '1';
        var busy = false;
        if (!form || !toolbar || !selectAll) return;

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.setAttribute('data-bulk-feedback', '');
            feedback.setAttribute('role', 'status');
            feedback.setAttribute('aria-live', 'polite');
            feedback.hidden = true;
            form.before(feedback);
        }

        function checks() {
            return Array.from(root.querySelectorAll('[data-bulk-row]'));
        }

        function availableChecks() {
            return checks().filter(function (check) { return !check.disabled; });
        }

        function visibleChecks() {
            return availableChecks().filter(function (check) {
                var row = check.closest('tr');
                return !row || (!row.hidden && !row.classList.contains('d-none'));
            });
        }

        function selectedChecks() {
            return checks().filter(function (check) {
                return check.checked && check.dataset.bulkPermanentDisabled !== '1' && check.closest('tr')?.dataset.bulkProcessed !== '1';
            });
        }

        function sync() {
            if (busy) {
                toolbar.hidden = false;
                selectAll.disabled = true;
                return;
            }
            var visible = visibleChecks();
            var selectedVisible = visible.filter(function (check) { return check.checked; });
            var selected = selectedChecks().length;
            toolbar.hidden = selected === 0;
            if (count) count.textContent = selected + ' data terpilih';
            selectAll.checked = visible.length > 0 && selectedVisible.length === visible.length;
            selectAll.indeterminate = selectedVisible.length > 0 && selectedVisible.length < visible.length;
            selectAll.disabled = busy || visible.length === 0;
        }

        function showFeedback(message, type) {
            feedback.className = 'approval-bulk-feedback alert alert-' + type + ' mx-3 mb-3';
            feedback.textContent = message;
            feedback.hidden = false;
        }

        function setBusy(state) {
            busy = state;
            toolbar.setAttribute('aria-busy', state ? 'true' : 'false');
            form.querySelectorAll('button').forEach(function (button) { button.disabled = state; });
            if (rejectTrigger) rejectTrigger.disabled = state;
            checks().forEach(function (check) { check.disabled = state || check.dataset.bulkPermanentDisabled === '1' || check.closest('tr')?.dataset.bulkProcessed === '1'; });
            root.classList.toggle('is-bulk-loading', state);
            if (count && state) count.textContent = 'Request sedang diproses…';
            if (!state) {
                checks().forEach(function (check) {
                    var row = check.closest('tr');
                    check.disabled = check.dataset.bulkPermanentDisabled === '1' || Boolean(row && row.dataset.bulkProcessed === '1');
                });
            }
            sync();
        }

        function updateActionableCount(value) {
            if (!Number.isFinite(Number(value))) return;
            root.querySelectorAll('[data-bulk-actionable-count]').forEach(function (element) {
                element.textContent = String(Math.max(0, Number(value)));
            });
        }

        function confirmBulkAction(selected, message) {
            if (!window.bootstrap || !bootstrap.Modal) {
                return Promise.resolve(window.confirm(message));
            }
            var modalElement = document.getElementById('bulkActionConfirmationModal');
            if (!modalElement) {
                modalElement = document.createElement('div');
                modalElement.id = 'bulkActionConfirmationModal';
                modalElement.className = 'modal fade';
                modalElement.tabIndex = -1;
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><div class="bulk-confirm-eyebrow">Konfirmasi proses</div><h2 class="modal-title h5 fw-bold mb-0">Periksa Data Terpilih</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><p class="bulk-confirm-message"></p><div class="bulk-confirm-list"></div><p class="bulk-confirm-note"><i class="bi bi-info-circle"></i><span>Pastikan peminjam, barang, jumlah, dan periode sudah sesuai sebelum dilanjutkan.</span></p></div><div class="modal-footer bulk-confirm-footer"><button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-success rounded-pill px-4" data-bulk-confirm-submit><i class="bi bi-check2-circle me-1"></i>Setujui Proses</button></div></div></div>';
                document.body.appendChild(modalElement);
            }

            var list = modalElement.querySelector('.bulk-confirm-list');
            modalElement.querySelector('.bulk-confirm-message').textContent = message;
            var confirmSubmit = modalElement.querySelector('[data-bulk-confirm-submit]');
            confirmSubmit.replaceChildren();
            var confirmIcon = document.createElement('i');
            confirmIcon.className = 'bi bi-check2-circle me-1';
            confirmSubmit.append(confirmIcon, document.createTextNode(confirmLabel));
            list.replaceChildren();
            selected.forEach(function (check, index) {
                var row = check.closest('tr');
                var item = document.createElement('article');
                item.className = 'bulk-confirm-item';
                var borrower = row?.dataset.filterPeminjam || '';
                var goods = row?.dataset.filterBarang || '';
                var quantity = row?.dataset.filterJumlah || '';
                var period = row?.dataset.filterMasa || '';
                if (!borrower && row) {
                    var cells = Array.from(row.querySelectorAll('td')).filter(function (cell) {
                        return !cell.matches('.approval-bulk-cell') && !cell.querySelector('[data-bulk-action]');
                    });
                    borrower = cells[1]?.innerText.trim() || cells[0]?.innerText.trim() || ('Data #' + check.value);
                    goods = cells[2]?.innerText.trim() || '';
                    period = cells[3]?.innerText.trim() || '';
                }
                var title = document.createElement('div');
                title.className = 'bulk-confirm-item__title';
                title.textContent = (index + 1) + '. ' + (borrower || ('Transaksi #' + check.value));
                var meta = document.createElement('div');
                meta.className = 'bulk-confirm-item__meta';
                [goods, quantity ? quantity + ' unit' : '', period].filter(Boolean).forEach(function (value) {
                    var span = document.createElement('span');
                    span.textContent = value;
                    meta.appendChild(span);
                });
                item.append(title, meta);
                list.appendChild(item);
            });

            return new Promise(function (resolve) {
                var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                var submit = modalElement.querySelector('[data-bulk-confirm-submit]');
                var settled = false;
                var finish = function (result) {
                    if (settled) return;
                    settled = true;
                    submit.removeEventListener('click', approve);
                    modalElement.removeEventListener('hidden.bs.modal', cancel);
                    resolve(result);
                };
                var approve = function () { finish(true); modal.hide(); };
                var cancel = function () { finish(false); };
                submit.addEventListener('click', approve, { once: true });
                modalElement.addEventListener('hidden.bs.modal', cancel, { once: true });
                modal.show();
            });
        }

        function applyProcessed(payload) {
            var processed = new Set((payload.processed_ids || []).map(String));
            checks().forEach(function (check) {
                var row = check.closest('tr');
                check.checked = false;
                if (!processed.has(String(check.value)) || !row) return;
                row.dataset.bulkProcessed = '1';
                row.classList.add('approval-row-processed');
                check.disabled = true;
                var statusCell = row.querySelector('[data-bulk-status]');
                if (statusCell) {
                    var status = document.createElement('span');
                status.className = 'soft-badge approval-bulk-result ' + (payload.action === 'reject' ? 'text-bg-danger' : 'text-bg-success');
                    status.textContent = payload.status || (payload.action === 'reject' ? 'Ditolak' : 'Disetujui');
                    statusCell.replaceChildren(status);
                }
                row.querySelectorAll('[data-bulk-action]').forEach(function (button) { button.disabled = true; });
            });
            selectAll.checked = false;
            selectAll.indeterminate = false;
            updateActionableCount(payload.actionable_remaining);
        }

        function submitFallback(action) {
            var actionField = form.querySelector('[data-bulk-fallback-action]');
            if (!actionField) {
                actionField = document.createElement('input');
                actionField.type = 'hidden';
                actionField.name = 'action';
                actionField.setAttribute('data-bulk-fallback-action', '');
                form.appendChild(actionField);
            }
            actionField.value = action;

            // Aksi AJAX menonaktifkan checkbox saat request berlangsung.
            // Aktifkan kembali pilihan yang sah agar nilainya ikut terkirim
            // dalam submit form native.
            selectedChecks().forEach(function (check) {
                check.disabled = false;
            });

            // Semua endpoint bulk masih mendukung submit form biasa. Jalur ini
            // menjaga approval tetap berjalan bila ekstensi/server lokal
            // menyisipkan HTML sehingga respons fetch tidak dapat dibaca JSON.
            HTMLFormElement.prototype.submit.call(form);
        }

        async function requestBatch(action, ids, noteValue) {
            var body = new FormData(form);
            body.delete('loan_ids[]');
            ids.forEach(function (id) { body.append('loan_ids[]', id); });
            body.set('action', action);
            body.set('bulk_note', noteValue || '');
            body.set('ajax', '1');
            var controller = new AbortController();
            var timer = window.setTimeout(function () { controller.abort(); }, REQUEST_TIMEOUT);

            try {
                var response = await fetch(form.action, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var rawPayload = await response.text();
                var payload;
                try {
                    payload = rawPayload ? JSON.parse(rawPayload.replace(/^\uFEFF/, '').trim()) : {};
                } catch (error) {
                    // Jika sesi berakhir dan browser menerima halaman login,
                    // arahkan pengguna ke tujuan redirect alih-alih menampilkan
                    // error teknis dari fetch.
                    if (response.redirected && response.url) {
                        window.location.assign(response.url);
                        return new Promise(function () {});
                    }
                    submitFallback(action);
                    return new Promise(function () {});
                }
                return { response: response, payload: payload };
            } finally {
                window.clearTimeout(timer);
            }
        }

        async function submitAjax(action) {
            if (busy) return;
            var selected = selectedChecks();
            if (!selected.length) {
                showFeedback('Pilih minimal satu ' + noun + ' yang dapat diproses.', 'warning');
                return;
            }
            if (action !== 'reject') {
                var message = confirmMessage || ('Setujui semua ' + noun + ' yang dipilih?');
                if (!await confirmBulkAction(selected, message)) return;
            }

            var ids = selected.map(function (check) { return check.value; });
            var noteValue = note ? note.value : '';
            var processedTotal = 0;
            var skippedTotal = 0;
            feedback.hidden = true;
            setBusy(true);

            try {
                for (var offset = 0; offset < ids.length; offset += BATCH_SIZE) {
                    var batch = ids.slice(offset, offset + BATCH_SIZE);
                    var lastNumber = Math.min(offset + batch.length, ids.length);
                    if (count) count.textContent = 'Memproses ' + (offset + 1) + '–' + lastNumber + ' dari ' + ids.length + ' data…';

                    var result = await requestBatch(action, batch, noteValue);
                    var response = result.response;
                    var payload = result.payload || {};
                    if (response.status === 401 || response.status === 403) {
                        throw Object.assign(new Error(payload.message || 'Sesi atau izin tidak valid.'), { payload: payload });
                    }
                    if (!response.ok && response.status !== 409) {
                        throw Object.assign(new Error(payload.message || 'Bulk action gagal diproses.'), { payload: payload });
                    }

                    processedTotal += Number(payload.processed || 0);
                    skippedTotal += Number(payload.skipped || 0);
                    applyProcessed(payload);
                }

                var actionLabel = successLabel || (action === 'reject' ? 'ditolak' : 'disetujui');
                var message = processedTotal + ' ' + noun + ' berhasil ' + actionLabel + '.';
                if (skippedTotal > 0) message += ' ' + skippedTotal + ' dilewati karena status atau izin sudah berubah.';
                showFeedback(message, skippedTotal > 0 ? 'warning' : 'success');
                if (note) note.value = '';
                if (rejectReason) rejectReason.value = '';
                var modalElement = rejectReason ? rejectReason.closest('.modal') : null;
                if (modalElement && window.bootstrap) bootstrap.Modal.getInstance(modalElement)?.hide();
                if (reloadOnSuccess && processedTotal > 0) {
                    window.setTimeout(function () { window.location.reload(); }, 900);
                }
            } catch (error) {
                var payload = error.payload || {};
                var message = error.name === 'AbortError'
                    ? 'Request melewati batas waktu. Status data mungkin sudah berubah; periksa kembali sebelum mengulang.'
                    : (payload.message || error.message || 'Server gagal memproses request.');
                if (processedTotal > 0) message = processedTotal + ' data sudah berhasil diproses sebelum proses terhenti. ' + message;
                showFeedback(message, payload.partial ? 'warning' : 'danger');
                if (payload.redirect) window.setTimeout(function () { window.location.assign(payload.redirect); }, 1200);
            } finally {
                setBusy(false);
            }
        }

        checks().forEach(function (check) { check.dataset.bulkPermanentDisabled = check.disabled ? '1' : '0'; });
        selectAll.addEventListener('change', function () {
            visibleChecks().forEach(function (check) { check.checked = selectAll.checked; });
            sync();
        });
        root.addEventListener('change', function (event) {
            if (event.target.matches('[data-bulk-row]')) sync();
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitter = event.submitter;
            var action = submitter && (submitter.value || submitter.dataset.bulkAction);
            if (action) submitAjax(action);
        });

        if (approveAction) approveAction.dataset.bulkAction = 'approve';
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

        sync();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bulk-approval]').forEach(init);
    });
}());
