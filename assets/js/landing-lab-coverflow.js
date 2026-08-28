(function () {
    'use strict';

    function initCoverflow(root) {
        if (!root || root.dataset.labCoverflowReady === 'true') return;

        var viewport = root.querySelector('[data-lab-viewport]');
        var cards = Array.prototype.slice.call(root.querySelectorAll('[data-lab-card]'));
        var previous = root.querySelector('[data-lab-prev]');
        var next = root.querySelector('[data-lab-next]');
        var pagination = root.querySelector('[data-lab-pagination]');
        var currentLabel = root.querySelector('[data-lab-current]');
        var totalLabel = root.querySelector('[data-lab-total]');

        if (!viewport || !cards.length || !previous || !next || !pagination) return;

        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var active = cards.length > 2 ? 1 : 0;
        var timer = 0;
        var resizeFrame = 0;
        var pointerId = null;
        var pointerStartX = 0;
        var ignoreClickUntil = 0;
        var dots = [];

        root.dataset.labCoverflowReady = 'true';
        root.classList.add('is-ready');
        root.tabIndex = 0;
        if (totalLabel) totalLabel.textContent = String(cards.length).padStart(2, '0');

        cards.forEach(function (card, index) {
            card.dataset.labIndex = String(index);
            card.setAttribute('role', 'group');
            card.setAttribute('aria-roledescription', 'slide');
            card.setAttribute('aria-label', 'Laboratorium ' + (index + 1) + ' dari ' + cards.length);

            card.addEventListener('click', function () {
                if (performance.now() < ignoreClickUntil || index === active) return;
                goTo(index);
            });

            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'lab-coverflow__dot';
            dot.setAttribute('aria-label', 'Tampilkan laboratorium ' + (index + 1));
            dot.addEventListener('click', function () { goTo(index); });
            pagination.appendChild(dot);
            dots.push(dot);
        });

        function relativeOffset(index) {
            var offset = index - active;
            var half = cards.length / 2;
            if (offset > half) offset -= cards.length;
            if (offset < -half) offset += cards.length;
            return offset;
        }

        function render() {
            var width = viewport.clientWidth || root.clientWidth || window.innerWidth;
            var mobile = width < 640;
            var spacing = mobile
                ? Math.min(320, Math.max(245, width * 0.80))
                : Math.min(440, Math.max(330, width * 0.38));

            cards.forEach(function (card, index) {
                var offset = relativeOffset(index);
                var distance = Math.abs(offset);
                var visible = distance <= 2;
                var scale = distance === 0 ? 1 : (distance === 1 ? 0.82 : 0.68);
                var opacity = distance === 0 ? 1 : (distance === 1 ? 0.58 : 0.18);
                var x = offset * spacing;
                var y = distance * (mobile ? 10 : 17);
                var z = distance * -135;
                var rotate = offset * (mobile ? -5 : -9);

                card.style.transform = 'translate3d(calc(-50% + ' + x + 'px), ' + y + 'px, ' + z + 'px) rotateY(' + rotate + 'deg) scale(' + scale + ')';
                card.style.zIndex = String(20 - distance);
                card.style.setProperty('--lab-opacity', String(opacity));
                card.style.filter = distance === 0 ? 'none' : 'blur(' + (distance * 1.35) + 'px) saturate(' + (1 - distance * 0.12) + ')';
                card.classList.toggle('is-visible', visible);
                card.classList.toggle('is-near', distance === 1);
                card.classList.toggle('is-active', distance === 0);
                card.setAttribute('aria-hidden', distance === 0 ? 'false' : 'true');
                card.tabIndex = distance === 0 ? 0 : -1;
            });

            dots.forEach(function (dot, index) {
                var selected = index === active;
                dot.classList.toggle('is-active', selected);
                dot.setAttribute('aria-current', selected ? 'true' : 'false');
            });

            if (currentLabel) currentLabel.textContent = String(active + 1).padStart(2, '0');
            if (dots[active] && typeof pagination.scrollTo === 'function') {
                pagination.scrollTo({
                    left: dots[active].offsetLeft - (pagination.clientWidth - dots[active].offsetWidth) / 2,
                    behavior: 'smooth'
                });
            }

            previous.disabled = cards.length < 2;
            next.disabled = cards.length < 2;
        }

        function goTo(index) {
            active = (index + cards.length) % cards.length;
            render();
            restartAutoplay();
        }

        function stopAutoplay() {
            if (!timer) return;
            window.clearInterval(timer);
            timer = 0;
        }

        function startAutoplay() {
            stopAutoplay();
            if (reducedMotion.matches || cards.length < 2 || document.hidden) return;
            timer = window.setInterval(function () {
                active = (active + 1) % cards.length;
                render();
            }, 6500);
        }

        function restartAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        previous.addEventListener('click', function () { goTo(active - 1); });
        next.addEventListener('click', function () { goTo(active + 1); });

        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goTo(active - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                goTo(active + 1);
            }
        });

        viewport.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' && event.button !== 0) return;
            pointerId = event.pointerId;
            pointerStartX = event.clientX;
            viewport.setPointerCapture(pointerId);
            root.classList.add('is-dragging');
            stopAutoplay();
        });

        viewport.addEventListener('pointerup', function (event) {
            if (pointerId !== event.pointerId) return;
            var delta = event.clientX - pointerStartX;
            root.classList.remove('is-dragging');
            pointerId = null;

            if (Math.abs(delta) > 44) {
                ignoreClickUntil = performance.now() + 280;
                goTo(active + (delta < 0 ? 1 : -1));
            } else {
                startAutoplay();
            }
        });

        viewport.addEventListener('pointercancel', function () {
            pointerId = null;
            root.classList.remove('is-dragging');
            startAutoplay();
        });

        root.addEventListener('pointerenter', stopAutoplay);
        root.addEventListener('pointerleave', startAutoplay);
        root.addEventListener('focusin', stopAutoplay);
        root.addEventListener('focusout', function (event) {
            if (!root.contains(event.relatedTarget)) startAutoplay();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopAutoplay();
            else startAutoplay();
        });

        window.addEventListener('resize', function () {
            window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(render);
        }, { passive: true });

        if (typeof reducedMotion.addEventListener === 'function') {
            reducedMotion.addEventListener('change', function () {
                render();
                restartAutoplay();
            });
        }

        render();
        startAutoplay();
    }

    function init() {
        document.querySelectorAll('[data-lab-coverflow]').forEach(initCoverflow);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
