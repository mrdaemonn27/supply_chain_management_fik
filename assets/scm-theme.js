(function () {
    'use strict';

    if (window.SCMTheme) return;

    var STORAGE_KEY = 'scm-theme';
    var LEGACY_STORAGE_KEY = 'scm-kaur-theme';
    var allowedThemes = ['light', 'dark'];

    function readStoredTheme() {
        try {
            var stored = window.localStorage.getItem(STORAGE_KEY);
            if (allowedThemes.indexOf(stored) !== -1) return stored;

            var legacy = window.localStorage.getItem(LEGACY_STORAGE_KEY);
            if (allowedThemes.indexOf(legacy) !== -1) {
                window.localStorage.setItem(STORAGE_KEY, legacy);
                return legacy;
            }
        } catch (error) {}

        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function updateButtons(theme) {
        var isDark = theme === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.innerHTML = '<i class="bi ' + (isDark ? 'bi-sun' : 'bi-moon-stars') + '" aria-hidden="true"></i>';
            button.setAttribute('aria-label', isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
            button.setAttribute('title', isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        });
    }

    function applyTheme(theme, persist) {
        var nextTheme = allowedThemes.indexOf(theme) !== -1 ? theme : 'light';
        var root = document.documentElement;
        root.dataset.scmTheme = nextTheme;
        root.classList.toggle('scm-theme-dark', nextTheme === 'dark');
        root.classList.toggle('scm-theme-light', nextTheme === 'light');

        if (persist) {
            try {
                window.localStorage.setItem(STORAGE_KEY, nextTheme);
                window.localStorage.setItem(LEGACY_STORAGE_KEY, nextTheme);
            } catch (error) {}
        }

        updateButtons(nextTheme);
        window.dispatchEvent(new CustomEvent('scm:themechange', { detail: { theme: nextTheme } }));
        return nextTheme;
    }

    function toggleTheme() {
        return applyTheme(document.documentElement.classList.contains('scm-theme-dark') ? 'light' : 'dark', true);
    }

    function buildToggle(extraClasses) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'scm-theme-toggle' + (extraClasses ? ' ' + extraClasses : '');
        button.setAttribute('data-theme-toggle', '');
        return button;
    }

    function createToggle() {
        if (window.self !== window.top || document.querySelector('[data-theme-toggle]')) return;

        var host = document.querySelector('.topbar-actions');
        if (!host) {
            var navbarCandidates = document.querySelectorAll('.navbar-custom .container-fluid > .align-items-center, .admin-navbar .container-fluid > .align-items-center');
            for (var index = 0; index < navbarCandidates.length; index++) {
                if (!navbarCandidates[index].classList.contains('navbar-brand')) {
                    host = navbarCandidates[index];
                    break;
                }
            }
        }

        if (host) {
            var button = buildToggle('');
            host.insertBefore(button, host.firstChild);
            if (host.classList.contains('d-none')) {
                document.body.appendChild(buildToggle('is-floating d-lg-none'));
            }
        } else {
            document.body.appendChild(buildToggle('is-floating'));
        }
    }

    function bindToggles() {
        createToggle();
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            if (button.dataset.scmThemeBound === 'true') return;
            button.dataset.scmThemeBound = 'true';
            button.addEventListener('click', toggleTheme);
        });
        updateButtons(document.documentElement.classList.contains('scm-theme-dark') ? 'dark' : 'light');
    }

    window.SCMTheme = {
        apply: function (theme) { return applyTheme(theme, true); },
        current: function () { return document.documentElement.classList.contains('scm-theme-dark') ? 'dark' : 'light'; },
        toggle: toggleTheme
    };

    applyTheme(readStoredTheme(), false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindToggles, { once: true });
    } else {
        bindToggles();
    }

    window.addEventListener('storage', function (event) {
        if (event.key === STORAGE_KEY && allowedThemes.indexOf(event.newValue) !== -1) {
            applyTheme(event.newValue, false);
        }
    });
})();
