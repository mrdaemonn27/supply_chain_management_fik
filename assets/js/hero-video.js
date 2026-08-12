(function () {
    "use strict";

    var hero = document.querySelector("[data-video-hero]");
    if (!hero || hero.dataset.videoInitialized === "1") return;
    hero.dataset.videoInitialized = "1";

    var video = hero.querySelector(".video-hero__video");
    var media = hero.querySelector(".video-hero__media");
    var parallax = hero.querySelector(".video-hero__parallax");
    var ui = hero.querySelector(".video-hero__ui");
    var outline = hero.querySelector(".video-hero__outline");
    var kicker = hero.querySelector(".video-hero__kicker");
    var titleLines = Array.prototype.slice.call(hero.querySelectorAll(".video-hero__title-line-inner"));
    var lead = hero.querySelector(".video-hero__lead");
    var actions = hero.querySelector(".video-hero__actions");
    var mist = hero.querySelector(".video-hero__mist");
    var pulse = hero.querySelector(".video-hero__pulse");
    var paletteStatus = hero.querySelector("[data-palette-status]");
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
    var timeline = null;
    var idleTween = null;
    var visible = true;
    var paletteIndex = 0;
    var paletteTimer = null;

    var palettes = [
        { name: "blue", accent: "#ff6b2c", a: "rgba(19, 79, 143, .42)", b: "rgba(0, 190, 200, .18)", c: "rgba(24, 34, 92, .26)" },
        { name: "green", accent: "#ff7440", a: "rgba(9, 104, 86, .42)", b: "rgba(44, 190, 151, .17)", c: "rgba(13, 68, 79, .24)" },
        { name: "red", accent: "#ff6336", a: "rgba(135, 24, 36, .42)", b: "rgba(226, 74, 40, .17)", c: "rgba(82, 19, 31, .24)" },
        { name: "purple", accent: "#ff7450", a: "rgba(74, 37, 143, .42)", b: "rgba(177, 58, 170, .16)", c: "rgba(37, 29, 104, .26)" }
    ];

    function setMoodVariables(prefix, palette) {
        hero.style.setProperty("--" + prefix + "a", palette.a);
        hero.style.setProperty("--" + prefix + "b", palette.b);
        hero.style.setProperty("--" + prefix + "c", palette.c);
    }

    function applyMood(index, animate) {
        var next = palettes[index];
        var current = palettes[paletteIndex];
        if (!animate || !window.gsap || reducedMotion.matches) {
            setMoodVariables("mood-", next);
            hero.style.setProperty("--mood-accent", next.accent);
            hero.classList.remove("is-mood-transitioning");
        } else {
            setMoodVariables("next-", next);
            hero.style.setProperty("--mood-accent", next.accent);
            hero.classList.add("is-mood-transitioning");
            if (paletteTimer) window.clearTimeout(paletteTimer);
            paletteTimer = window.setTimeout(function () {
                setMoodVariables("mood-", next);
                hero.classList.remove("is-mood-transitioning");
            }, 980);
        }

        if (paletteStatus) paletteStatus.textContent = "Mood video " + next.name + " aktif";
        if (!current) return;
    }

    function animateOpening() {
        if (!window.gsap || reducedMotion.matches) {
            if (video) video.style.opacity = ".84";
            return;
        }

        if (outline) gsap.set(outline, { xPercent: -50, yPercent: -50, x: -34, autoAlpha: 0, scale: 1.02, filter: "blur(2px)" });
        if (kicker) gsap.set(kicker, { y: 18, autoAlpha: 0, filter: "blur(7px)" });
        if (titleLines.length) gsap.set(titleLines, { yPercent: 105, autoAlpha: 0, filter: "blur(8px)" });
        if (lead) gsap.set(lead, { y: 20, autoAlpha: 0, filter: "blur(6px)" });
        if (actions) gsap.set(actions, { y: 18, autoAlpha: 0, filter: "blur(5px)" });

        var opening = gsap.timeline({ defaults: { ease: "power3.out" } });
        opening
            .fromTo(video, { autoAlpha: .28, scale: 1.045 }, { autoAlpha: .84, scale: 1, duration: .9 }, 0)
            .to(outline, { x: 0, autoAlpha: .44, scale: 1, filter: "blur(0px)", duration: 1.15 }, .08)
            .to(kicker, { y: 0, autoAlpha: 1, filter: "blur(0px)", duration: .62 }, .26)
            .to(titleLines[0], { yPercent: 0, autoAlpha: 1, filter: "blur(0px)", duration: .8 }, .42)
            .to(titleLines[1], { yPercent: 0, autoAlpha: 1, filter: "blur(0px)", duration: .8 }, .52)
            .to(lead, { y: 0, autoAlpha: 1, filter: "blur(0px)", duration: .7 }, .82)
            .to(actions, { y: 0, autoAlpha: 1, filter: "blur(0px)", duration: .72 }, 1.02)
            .eventCallback("onComplete", startIdle);
    }

    function startIdle() {
        if (!window.gsap || reducedMotion.matches || !video || idleTween) return;
        idleTween = gsap.to(video, {
            scale: 1.012,
            duration: 6.5,
            ease: "sine.inOut",
            repeat: -1,
            yoyo: true,
            paused: !visible
        });
    }

    function setupMoodClick() {
        hero.addEventListener("click", function (event) {
            if (event.target.closest("a, button, input, select, textarea, label")) return;

            var rect = hero.getBoundingClientRect();
            hero.style.setProperty("--pulse-x", ((event.clientX - rect.left) / rect.width * 100).toFixed(2) + "%");
            hero.style.setProperty("--pulse-y", ((event.clientY - rect.top) / rect.height * 100).toFixed(2) + "%");
            if (pulse) {
                pulse.classList.remove("is-active");
                void pulse.offsetWidth;
                pulse.classList.add("is-active");
            }

            paletteIndex = (paletteIndex + 1) % palettes.length;
            applyMood(paletteIndex, true);
        });
    }

    function setupParallax() {
        if (!finePointer.matches || reducedMotion.matches || !window.gsap || !parallax) return;

        var moveX = gsap.quickTo(parallax, "x", { duration: .8, ease: "power3.out" });
        var moveY = gsap.quickTo(parallax, "y", { duration: .8, ease: "power3.out" });
        var rotateX = gsap.quickTo(parallax, "rotationX", { duration: 1, ease: "power3.out" });
        var rotateY = gsap.quickTo(parallax, "rotationY", { duration: 1, ease: "power3.out" });

        hero.addEventListener("pointermove", function (event) {
            var rect = hero.getBoundingClientRect();
            var x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            var y = ((event.clientY - rect.top) / rect.height) * 2 - 1;
            moveX(x * 6);
            moveY(y * 5);
            rotateX(-y * .7);
            rotateY(x * 1.1);
        }, { passive: true });

        hero.addEventListener("pointerleave", function () {
            moveX(0); moveY(0); rotateX(0); rotateY(0);
        }, { passive: true });
    }

    function setupScroll() {
        if (!window.gsap || !window.ScrollTrigger || reducedMotion.matches) return;
        gsap.registerPlugin(ScrollTrigger);

        var nextScene = hero.nextElementSibling;
        var nextCopy = nextScene ? nextScene.querySelector(".scene__copy") : null;
        if (mist) gsap.set(mist, { autoAlpha: 0, yPercent: 26, scaleY: .94, transformOrigin: "50% 100%" });
        if (nextCopy) gsap.set(nextCopy, { y: 36, autoAlpha: 0 });

        timeline = gsap.timeline({
            scrollTrigger: {
                trigger: hero,
                start: "top top",
                end: "bottom top",
                scrub: .75,
                invalidateOnRefresh: true,
                onUpdate: function (self) {
                    if (self.progress > .02 && self.progress < .98) {
                        if (idleTween) idleTween.pause();
                    } else if (visible) {
                        if (idleTween) idleTween.play();
                    }
                }
            }
        });

        timeline
            .to(media, { x: -18, y: -8, scale: 1.035, duration: .46, ease: "power1.out" }, 0)
            .to(mist, { autoAlpha: .24, yPercent: 14, scaleY: .98, duration: .2, ease: "power1.out" }, .52)
            .to(mist, { autoAlpha: 1, yPercent: 0, scaleY: 1, duration: .36, ease: "power2.inOut" }, .64)
            .to(media, { autoAlpha: .48, scale: .99, duration: .32, ease: "power1.in" }, .72);

        if (nextCopy) timeline.to(nextCopy, { y: 0, autoAlpha: 1, duration: .28, ease: "power2.out" }, .8);
    }

    function setupVisibility() {
        if ("IntersectionObserver" in window) {
            var observer = new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (!video || reducedMotion.matches) return;
                if (visible) {
                    var play = video.play();
                    if (play && play.catch) play.catch(function () {});
                    if (idleTween) idleTween.play();
                } else {
                    video.pause();
                    if (idleTween) idleTween.pause();
                }
            }, { threshold: .08 });
            observer.observe(hero);
        }

        document.addEventListener("visibilitychange", function () {
            if (document.hidden) video.pause();
            else if (visible && !reducedMotion.matches) {
                var play = video.play();
                if (play && play.catch) play.catch(function () {});
            }
        });
    }

    function startVideo() {
        if (!video) return;

        if (reducedMotion.matches) {
            video.pause();
            return;
        }

        var fallbackTimer = null;
        var fallbackUsed = false;

        function clearFallbackTimer() {
            if (!fallbackTimer) return;
            window.clearTimeout(fallbackTimer);
            fallbackTimer = null;
        }

        function switchToFallback() {
            if (fallbackUsed || !video.dataset.fallbackSrc) return;

            fallbackUsed = true;
            clearFallbackTimer();
            hero.classList.add("is-video-fallback");
            video.pause();
            video.src = video.dataset.fallbackSrc;
            video.load();

            var fallbackPlay = video.play();
            if (fallbackPlay && fallbackPlay.catch) fallbackPlay.catch(function () {});
        }

        video.addEventListener("loadedmetadata", clearFallbackTimer, { once: true });
        video.addEventListener("error", switchToFallback, { once: true });
        fallbackTimer = window.setTimeout(function () {
            if (video.readyState < 1) switchToFallback();
        }, 3500);

        var play = video.play();
        if (play && play.catch) play.catch(function () {
            // Some codec failures are not surfaced immediately; the guarded timeout handles them.
        });
    }

    setMoodVariables("mood-", palettes[0]);
    applyMood(0, false);
    startVideo();
    setupMoodClick();
    setupParallax();
    setupScroll();
    setupVisibility();
    animateOpening();

    window.addEventListener("load", function () {
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
    }, { once: true });
})();
