(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        var panel = document.getElementById('faqAssistant');
        var launcher = document.getElementById('faqLauncher');
        var closeButton = document.getElementById('faqClose');
        var chat = document.getElementById('faqChat');
        var search = document.getElementById('faqSearch');
        var clearSearch = document.getElementById('faqSearchClear');
        var resultCount = document.getElementById('faqResultCount');
        var emptyState = document.getElementById('faqEmpty');
        var dataNode = document.getElementById('faqData');

        if (!panel || !launcher || !chat || !search || !dataNode) return;

        var faqs = [];
        try {
            faqs = JSON.parse(dataNode.textContent || '[]');
        } catch (error) {
            faqs = [];
        }

        var questionButtons = Array.prototype.slice.call(panel.querySelectorAll('[data-faq-question]'));
        var openButtons = Array.prototype.slice.call(document.querySelectorAll('[data-faq-open]'));
        var lastTrigger = launcher;
        var answering = false;
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function normalize(value) {
            var text = String(value || '').toLocaleLowerCase('id');
            return typeof text.normalize === 'function'
                ? text.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : text;
        }

        function scrollChat() {
            window.requestAnimationFrame(function () {
                chat.scrollTop = chat.scrollHeight;
            });
        }

        function openAssistant(trigger) {
            lastTrigger = trigger || document.activeElement || launcher;
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            launcher.setAttribute('aria-expanded', 'true');
            document.body.classList.add('faq-is-open');
            window.setTimeout(function () { search.focus(); }, reduceMotion ? 0 : 180);
        }

        function closeAssistant() {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            launcher.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('faq-is-open');
            if (lastTrigger && typeof lastTrigger.focus === 'function') lastTrigger.focus();
        }

        function buildMessage(role, message, source) {
            var row = document.createElement('div');
            row.className = 'faq-message faq-message--' + role;

            if (role === 'assistant') {
                var avatar = document.createElement('span');
                avatar.className = 'faq-message__avatar';
                avatar.setAttribute('aria-hidden', 'true');
                var avatarIcon = document.createElement('i');
                avatarIcon.className = 'bi bi-stars';
                avatar.appendChild(avatarIcon);
                row.appendChild(avatar);
            }

            var bubble = document.createElement('div');
            bubble.className = 'faq-message__bubble';
            bubble.textContent = message;

            if (source) {
                var sourceNode = document.createElement('span');
                sourceNode.className = 'faq-message__source';
                sourceNode.textContent = 'Sumber: ' + source;
                bubble.appendChild(sourceNode);
            }

            row.appendChild(bubble);
            return row;
        }

        function buildTyping() {
            var row = document.createElement('div');
            row.className = 'faq-message faq-message--assistant';
            row.setAttribute('data-faq-typing', '');

            var avatar = document.createElement('span');
            avatar.className = 'faq-message__avatar';
            avatar.setAttribute('aria-hidden', 'true');
            var avatarIcon = document.createElement('i');
            avatarIcon.className = 'bi bi-stars';
            avatar.appendChild(avatarIcon);

            var bubble = document.createElement('div');
            bubble.className = 'faq-message__bubble';
            bubble.setAttribute('aria-label', 'FAQ Assistant sedang menyiapkan jawaban');
            var typing = document.createElement('span');
            typing.className = 'faq-typing';
            typing.setAttribute('aria-hidden', 'true');
            typing.appendChild(document.createElement('i'));
            typing.appendChild(document.createElement('i'));
            typing.appendChild(document.createElement('i'));
            bubble.appendChild(typing);

            row.appendChild(avatar);
            row.appendChild(bubble);
            return row;
        }

        function chooseQuestion(index) {
            var faq = faqs[index];
            if (!faq || answering) return;

            answering = true;
            chat.appendChild(buildMessage('user', faq.question));
            var typing = buildTyping();
            chat.appendChild(typing);
            scrollChat();

            window.setTimeout(function () {
                typing.remove();
                chat.appendChild(buildMessage('assistant', faq.answer, faq.source_reference));
                answering = false;
                scrollChat();
            }, reduceMotion ? 0 : 420);
        }

        function filterQuestions() {
            var query = normalize(search.value.trim());
            var visible = 0;

            questionButtons.forEach(function (button) {
                var index = Number(button.getAttribute('data-faq-index'));
                var faq = faqs[index] || {};
                var haystack = normalize([
                    faq.question,
                    faq.answer,
                    faq.keywords,
                    faq.category,
                    faq.source_reference
                ].join(' '));
                var matches = !query || haystack.indexOf(query) !== -1;
                button.hidden = !matches;
                if (matches) visible += 1;
            });

            if (resultCount) resultCount.textContent = visible + ' pertanyaan';
            if (emptyState) emptyState.classList.toggle('is-visible', visible === 0);
            if (clearSearch) clearSearch.classList.toggle('is-visible', query.length > 0);
        }

        launcher.addEventListener('click', function () { openAssistant(launcher); });
        if (closeButton) closeButton.addEventListener('click', closeAssistant);

        openButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                openAssistant(button);
                var index = button.getAttribute('data-faq-index');
                if (index !== null && index !== '') {
                    window.setTimeout(function () { chooseQuestion(Number(index)); }, reduceMotion ? 0 : 160);
                }
            });
        });

        questionButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                chooseQuestion(Number(button.getAttribute('data-faq-index')));
            });
        });

        search.addEventListener('input', filterQuestions);
        search.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            var firstVisible = questionButtons.find(function (button) { return !button.hidden; });
            if (firstVisible) {
                event.preventDefault();
                firstVisible.click();
            }
        });

        if (clearSearch) {
            clearSearch.addEventListener('click', function () {
                search.value = '';
                filterQuestions();
                search.focus();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) closeAssistant();
        });

        filterQuestions();
    });
})();
