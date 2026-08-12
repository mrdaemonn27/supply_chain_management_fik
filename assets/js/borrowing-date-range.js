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
            day: "numeric",
            month: "short",
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
            startLabel.textContent = formatDate(startDate);
            endLabel.textContent = formatDate(endDate);
            startLabel.classList.toggle("is-placeholder", !startDate);
            endLabel.classList.toggle("is-placeholder", !endDate);

            if (!startDate) {
                helper.textContent = "Pilih tanggal pengambilan terlebih dahulu.";
            } else if (!endDate) {
                helper.textContent = "Sekarang pilih rencana tanggal pengembalian.";
            } else {
                helper.textContent = "Rentang tanggal telah dipilih. Anda masih dapat mengubahnya.";
            }
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
                if (isWithinRange(date, startDate, endDate)) button.classList.add("is-range");
                if (sameDay(date, startDate)) button.classList.add("is-range-start");
                if (sameDay(date, endDate)) button.classList.add("is-range-end");
                days.appendChild(button);
            }

            addRangeRails(days, monthDate, firstDay, totalDays);
            wrapper.appendChild(days);
            return wrapper;
        }

        function addRangeRails(days, monthDate, firstDay, totalDays) {
            if (!startDate || !endDate || sameDay(startDate, endDate)) return;

            var monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1);
            var monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth(), totalDays);
            if (endDate.getTime() < monthStart.getTime() || startDate.getTime() > monthEnd.getTime()) return;

            var rangeStart = startDate.getTime() > monthStart.getTime() ? startDate : monthStart;
            var rangeEnd = endDate.getTime() < monthEnd.getTime() ? endDate : monthEnd;
            var rangeStartIndex = firstDay + rangeStart.getDate() - 1;
            var rangeEndIndex = firstDay + rangeEnd.getDate() - 1;
            var startRow = Math.floor(rangeStartIndex / 7);
            var endRow = Math.floor(rangeEndIndex / 7);
            var row;

            for (row = startRow; row <= endRow; row += 1) {
                var rowStartIndex = row === startRow ? rangeStartIndex : row * 7;
                var rowEndIndex = row === endRow ? rangeEndIndex : (row * 7) + 6;
                var startOffset = row === startRow && sameDay(rangeStart, startDate) ? 0.5 : 0;
                var endOffset = row === endRow && sameDay(rangeEnd, endDate) ? 0.5 : 1;
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
            months.appendChild(renderMonth(addMonths(viewDate, 1)));
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

        months.addEventListener("click", function (event) {
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
