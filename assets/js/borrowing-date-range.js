(function () {
    "use strict";

    var WEEKDAYS = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

    function fromIso(value) {
        if (!value || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
        var parts = value.split("-");
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    }

    function toIso(date) {
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, "0"), String(date.getDate()).padStart(2, "0")].join("-");
    }

    function sameDay(first, second) {
        return first && second && toIso(first) === toIso(second);
    }

    function isWithinRange(date, start, end) {
        if (!start || !end) return false;
        var value = date.getTime();
        return value > start.getTime() && value < end.getTime();
    }

    function addMonths(date, amount) {
        return new Date(date.getFullYear(), date.getMonth() + amount, 1);
    }

    function formatDate(date) {
        if (!date) return "Pilih tanggal";
        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }).format(date);
    }

    function formatMonth(date) {
        return new Intl.DateTimeFormat("id-ID", {
            month: "long",
            year: "numeric"
        }).format(date);
    }

    function initialiseRangePicker(root) {
        var form = root.closest("form");
        var startInput = root.querySelector("[data-date-range-start-input]");
        var endInput = root.querySelector("[data-date-range-end-input]");
        var trigger = root.querySelector("[data-date-range-trigger]");
        var panel = root.querySelector("[data-date-range-panel]");
        var months = root.querySelector("[data-date-range-months]");
        var startLabel = root.querySelector("[data-date-range-start]");
        var endLabel = root.querySelector("[data-date-range-end]");
        var helper = root.querySelector("[data-date-range-helper]");
        var feedback = root.querySelector("[data-date-range-feedback]");
        var previous = root.querySelector("[data-date-range-prev]");
        var next = root.querySelector("[data-date-range-next]");

        if (!form || !startInput || !endInput || !trigger || !panel || !months) return;

        var minDate = fromIso(root.dataset.minDate || startInput.min) || new Date();
        minDate.setHours(0, 0, 0, 0);
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var startDate = fromIso(startInput.value);
        var endDate = fromIso(endInput.value);
        var viewDate = new Date((startDate || minDate).getFullYear(), (startDate || minDate).getMonth(), 1);
        var dragState = null;
        var ignoreClickUntil = 0;

        startInput.required = false;
        endInput.required = false;
        startInput.type = "hidden";
        endInput.type = "hidden";
        document.body.classList.add("date-range-ready");

        function setOpen(open) {
            panel.hidden = !open;
            trigger.setAttribute("aria-expanded", String(open));
            if (open) render();
        }

        function updateSummary() {
            var visibleRange = getVisibleRange();
            startLabel.textContent = formatDate(visibleRange.start);
            endLabel.textContent = formatDate(visibleRange.end);
            startLabel.classList.toggle("is-placeholder", !visibleRange.start);
            endLabel.classList.toggle("is-placeholder", !visibleRange.end);

            if (dragState) {
                helper.textContent = dragState.isHandleDrag
                    ? "Geser untuk mengubah tanggal, lepaskan untuk menetapkan."
                    : "Lepaskan pointer untuk menetapkan rentang tanggal.";
            } else if (!startDate) {
                helper.textContent = "Pilih tanggal pengambilan terlebih dahulu.";
            } else if (!endDate) {
                helper.textContent = "Sekarang pilih rencana tanggal pengembalian.";
            } else {
                helper.textContent = "Rentang tanggal telah dipilih. Geser bulatan tanggal untuk mengubahnya.";
            }
        }

        function getVisibleRange() {
            if (!dragState) return { start: startDate, end: endDate };
            var dragAnchor = dragState.anchor;
            var dragEnd = dragState.preview;
            return dragEnd.getTime() < dragAnchor.getTime()
                ? { start: dragEnd, end: dragAnchor }
                : { start: dragAnchor, end: dragEnd };
        }

        function clearError() {
            root.classList.remove("has-error");
            feedback.textContent = "";
        }

        function renderMonth(monthDate) {
            var wrapper = document.createElement("div");
            wrapper.className = "borrowing-date-range__month";

            var title = document.createElement("div");
            title.className = "borrowing-date-range__month-title";
            title.textContent = formatMonth(monthDate);
            wrapper.appendChild(title);

            var weekdays = document.createElement("div");
            weekdays.className = "borrowing-date-range__weekdays";
            WEEKDAYS.forEach(function (weekday) {
                var label = document.createElement("span");
                label.textContent = weekday;
                weekdays.appendChild(label);
            });
            wrapper.appendChild(weekdays);

            var days = document.createElement("div");
            days.className = "borrowing-date-range__days";
            if (dragState) days.classList.add("is-dragging");
            var firstDay = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1).getDay();
            var totalDays = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();
            var day;

            for (day = 0; day < firstDay; day += 1) {
                var spacer = document.createElement("span");
                spacer.className = "borrowing-date-range__day-spacer";
                spacer.setAttribute("aria-hidden", "true");
                days.appendChild(spacer);
            }

            for (day = 1; day <= totalDays; day += 1) {
                var date = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
                var button = document.createElement("button");
                button.type = "button";
                button.className = "borrowing-date-range__day";
                button.textContent = day;
                button.dataset.dateRangeDate = toIso(date);
                button.setAttribute("aria-label", formatDate(date));

                if (date.getTime() < minDate.getTime()) button.disabled = true;
                if (sameDay(date, today)) button.classList.add("is-today");
                var visibleRange = getVisibleRange();
                if (isWithinRange(date, visibleRange.start, visibleRange.end)) button.classList.add("is-range");
                if (sameDay(date, visibleRange.start)) button.classList.add("is-range-start");
                if (sameDay(date, visibleRange.end)) button.classList.add("is-range-end");
                days.appendChild(button);
            }

            addRangeRails(days, monthDate, firstDay, totalDays, getVisibleRange());
            wrapper.appendChild(days);
            return wrapper;
        }

        function addRangeRails(days, monthDate, firstDay, totalDays, range) {
            var rangeStartDate = range.start;
            var rangeEndDate = range.end;
            if (!rangeStartDate || !rangeEndDate || sameDay(rangeStartDate, rangeEndDate)) return;

            var monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
            var monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth(), totalDays);
            if (rangeEndDate.getTime() < monthStart.getTime() || rangeStartDate.getTime() > monthEnd.getTime()) return;

            var rangeStart = rangeStartDate.getTime() > monthStart.getTime() ? rangeStartDate : monthStart;
            var rangeEnd = rangeEndDate.getTime() < monthEnd.getTime() ? rangeEndDate : monthEnd;
            var rangeStartIndex = firstDay + rangeStart.getDate() - 1;
            var rangeEndIndex = firstDay + rangeEnd.getDate() - 1;
            var startRow = Math.floor(rangeStartIndex / 7);
            var endRow = Math.floor(rangeEndIndex / 7);
            var row;

            for (row = startRow; row <= endRow; row += 1) {
                var rowStartIndex = row === startRow ? rangeStartIndex : row * 7;
                var rowEndIndex = row === endRow ? rangeEndIndex : (row * 7) + 6;
                var startOffset = row === startRow && sameDay(rangeStart, rangeStartDate) ? 0.5 : 0;
                var endOffset = row === endRow && sameDay(rangeEnd, rangeEndDate) ? 0.5 : 1;
                var left = ((rowStartIndex % 7) + startOffset) * (100 / 7);
                var right = ((rowEndIndex % 7) + endOffset) * (100 / 7);
                var rail = document.createElement("span");

                rail.className = "borrowing-date-range__range-rail";
                rail.style.setProperty("--range-row", row);
                rail.style.setProperty("--range-left", left + "%");
                rail.style.setProperty("--range-right", right + "%");
                rail.setAttribute("aria-hidden", "true");
                days.appendChild(rail);
            }
        }

        function render() {
            months.innerHTML = "";
            months.appendChild(renderMonth(viewDate));
            updateSummary();
        }

        function chooseDate(date) {
            if (!startDate || (startDate && endDate)) {
                startDate = date;
                endDate = null;
            } else if (date.getTime() < startDate.getTime()) {
                startDate = date;
            } else {
                endDate = date;
            }

            startInput.value = startDate ? toIso(startDate) : "";
            endInput.value = endDate ? toIso(endDate) : "";
            clearError();
            render();
        }

        function dateFromPointer(event) {
            var target = document.elementFromPoint(event.clientX, event.clientY);
            var button = target && target.closest("[data-date-range-date]");
            if (!button || button.disabled || !months.contains(button)) return null;
            return fromIso(button.dataset.dateRangeDate);
        }

        function finishDrag(event) {
            if (!dragState) return;
            if (event && event.pointerId !== undefined && event.pointerId !== dragState.pointerId) return;
            var selection = dragState;
            dragState = null;
            months.classList.remove("is-dragging");
            if (months.hasPointerCapture && months.hasPointerCapture(selection.pointerId)) {
                months.releasePointerCapture(selection.pointerId);
            }
            ignoreClickUntil = Date.now() + 300;

            if (!selection.moved) {
                chooseDate(selection.origin);
                return;
            }

            startDate = selection.preview.getTime() < selection.anchor.getTime() ? selection.preview : selection.anchor;
            endDate = selection.preview.getTime() < selection.anchor.getTime() ? selection.anchor : selection.preview;
            startInput.value = toIso(startDate);
            endInput.value = toIso(endDate);
            clearError();
            render();
        }

        trigger.addEventListener("click", function () {
            setOpen(panel.hidden);
        });

        previous.addEventListener("click", function () {
            var candidate = addMonths(viewDate, -1);
            if (candidate.getFullYear() > minDate.getFullYear() || (candidate.getFullYear() === minDate.getFullYear() && candidate.getMonth() >= minDate.getMonth())) {
                viewDate = candidate;
                render();
            }
        });

        next.addEventListener("click", function () {
            viewDate = addMonths(viewDate, 1);
            render();
        });

        months.addEventListener("pointerdown", function (event) {
            if (!event.isPrimary || (event.pointerType === "mouse" && event.button !== 0)) return;
            var button = event.target.closest("[data-date-range-date]");
            if (!button || button.disabled) return;
            var selectedDate = fromIso(button.dataset.dateRangeDate);
            if (!selectedDate) return;
            event.preventDefault();

            // Kalau yang ditekan adalah salah satu ujung (handle) dari rentang yang
            // sudah lengkap, jadikan ujung SATUNYA sebagai jangkar supaya menggeser
            // satu bulatan tidak menghapus ujung lainnya (perilaku ala slider).
            var anchor = selectedDate;
            var isHandleDrag = false;
            if (startDate && endDate && !sameDay(startDate, endDate)) {
                var isStartHandle = button.classList.contains("is-range-start") && !button.classList.contains("is-range-end");
                var isEndHandle = button.classList.contains("is-range-end") && !button.classList.contains("is-range-start");
                if (isStartHandle) {
                    anchor = endDate;
                    isHandleDrag = true;
                } else if (isEndHandle) {
                    anchor = startDate;
                    isHandleDrag = true;
                }
            }

            dragState = {
                origin: selectedDate,
                anchor: anchor,
                preview: selectedDate,
                moved: false,
                isHandleDrag: isHandleDrag,
                pointerId: event.pointerId
            };
            months.classList.add("is-dragging");
            if (months.setPointerCapture) months.setPointerCapture(event.pointerId);
            updateSummary();
        });

        months.addEventListener("pointermove", function (event) {
            if (!dragState || event.pointerId !== dragState.pointerId) return;
            event.preventDefault();
            var selectedDate = dateFromPointer(event);
            if (!selectedDate || sameDay(selectedDate, dragState.preview)) return;
            dragState.preview = selectedDate;
            dragState.moved = true;
            render();
        });

        months.addEventListener("pointerup", finishDrag);
        months.addEventListener("pointercancel", function (event) {
            if (!dragState || event.pointerId !== dragState.pointerId) return;
            if (months.hasPointerCapture && months.hasPointerCapture(dragState.pointerId)) {
                months.releasePointerCapture(dragState.pointerId);
            }
            dragState = null;
            months.classList.remove("is-dragging");
            render();
        });

        months.addEventListener("dragstart", function (event) { event.preventDefault(); });

        months.addEventListener("click", function (event) {
            if (Date.now() < ignoreClickUntil) return;
            var button = event.target.closest("[data-date-range-date]");
            if (!button || button.disabled) return;
            var selectedDate = fromIso(button.dataset.dateRangeDate);
            if (selectedDate) chooseDate(selectedDate);
        });

        document.addEventListener("click", function (event) {
            if (!root.contains(event.target)) setOpen(false);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !panel.hidden) {
                setOpen(false);
                trigger.focus();
            }
        });

        form.addEventListener("submit", function (event) {
            if (startInput.value && endInput.value) return;

            event.preventDefault();
            root.classList.add("has-error");
            feedback.textContent = "Pilih tanggal pengambilan dan rencana pengembalian terlebih dahulu.";
            setOpen(true);
            trigger.focus();
        });

        render();
    }

    function initAllDateRangePickers() {
        document.querySelectorAll("[data-borrowing-date-range]").forEach(initialiseRangePicker);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAllDateRangePickers, { once: true });
    } else {
        initAllDateRangePickers();
    }
})();
