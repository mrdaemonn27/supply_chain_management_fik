(function () {
    "use strict";

    var initialized = false;
    var destroyed = false;
    var hero = document.querySelector(".particle-hero");

    if (!hero) return;

    var canvasHost = hero.querySelector(".particle-hero__canvas");
    var ui = hero.querySelector(".particle-hero__ui");
    var titleElement = hero.querySelector(".particle-hero__title");
    var mist = hero.querySelector(".particle-hero__mist");
    var paletteStatus = hero.querySelector("[data-palette-status]");
    var reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)");
    var shapeUrl = hero.getAttribute("data-shape-url");
    var textureUrl = hero.getAttribute("data-particle-url");

    var renderer = null;
    var scene = null;
    var camera = null;
    var particleGroup = null;
    var points = null;
    var geometry = null;
    var material = null;
    var spriteTexture = null;
    var resizeObserver = null;
    var visibilityObserver = null;
    var scrollTimeline = null;
    var paletteTween = null;
    var rippleTween = null;
    var frameId = 0;
    var visible = true;
    var pageVisible = !document.hidden;
    var lastTime = performance.now();
    var pointerTarget = { x: 0, y: 0 };
    var pointerCurrent = { x: 0, y: 0 };
    var pointerWorld = null;
    var focusTarget = 0;
    var focusCurrent = 0;
    var paletteIndex = 0;
    var formationWidth = 14;
    var formationHeight = 8;
    var formationBaseX = 0;
    var formationBaseY = 0;

    var palettes = [
        { name: "blue", mix: 0.94, a: 0x246bfd, b: 0x22d3ee, c: 0x8b5cf6, h: 0xffffff },
        { name: "green", mix: 0.94, a: 0x059669, b: 0x67f58a, c: 0x22d3ee, h: 0xffffff },
        { name: "red", mix: 0.94, a: 0xff3038, b: 0xfb7185, c: 0xec4899, h: 0xffffff },
        { name: "purple", mix: 0.94, a: 0x8b5cf6, b: 0xc084fc, c: 0x3b82f6, h: 0xfdf2ff }
    ];

    var uniforms = {
        uTime: { value: 0 },
        uFormation: { value: 0 },
        uDisperse: { value: 0 },
        uZoom: { value: 1 },
        uOpacity: { value: 1 },
        uPixelRatio: { value: 1 },
        uPointScale: { value: 1 },
        uPointer: { value: null },
        uFocus: { value: 0 },
        uRippleOrigin: { value: null },
        uRipple: { value: 2 },
        uRippleStrength: { value: 0 },
        uColorA: { value: null },
        uColorB: { value: null },
        uColorC: { value: null },
        uHighlight: { value: null },
        uPaletteMix: { value: palettes[0].mix },
        uTexture: { value: null }
    };

    var vertexShader = [
        "precision highp float;",
        "attribute vec3 aTarget;",
        "attribute vec3 aScatter;",
        "attribute vec4 aSeed;",
        "attribute float aSize;",
        "attribute float aAtmosphere;",
        "attribute vec3 aImageColor;",
        "attribute float aBrightness;",
        "uniform float uTime;",
        "uniform float uFormation;",
        "uniform float uDisperse;",
        "uniform float uZoom;",
        "uniform float uPixelRatio;",
        "uniform float uPointScale;",
        "uniform vec2 uPointer;",
        "uniform float uFocus;",
        "uniform vec2 uRippleOrigin;",
        "uniform float uRipple;",
        "uniform float uRippleStrength;",
        "varying float vAlpha;",
        "varying float vColorMix;",
        "varying float vAccentMix;",
        "varying float vHighlight;",
        "varying float vRipple;",
        "varying vec3 vImageColor;",
        "varying float vBrightness;",
        "varying float vClarity;",
        "void main() {",
        "  float f = clamp(uFormation, 0.0, 1.0);",
        "  float settle = 1.0 - pow(1.0 - f, 3.0);",
        "  vec3 pos = mix(aScatter, aTarget, settle);",
        "  float alive = smoothstep(0.34, 1.0, f);",
        "  float pointerDistance = length(aTarget.xy - uPointer);",
        "  float localFocus = exp(-pointerDistance * pointerDistance * 0.62);",
        "  float clarity = clamp(uFocus * localFocus * (1.0 - aAtmosphere), 0.0, 1.0);",
        "  float activity = mix(1.0, 0.18, clarity);",
        "  float speed = mix(0.42, 1.18, aSeed.w);",
        "  float phase = aSeed.x * 19.7 + aSeed.y * 31.3 + aSeed.z * 11.9;",
        "  float waveA = sin(uTime * speed + phase + aTarget.y * 0.22);",
        "  float waveB = cos(uTime * speed * 0.83 + phase * 1.19 + aTarget.x * 0.18);",
        "  float waveC = sin(uTime * speed * 0.57 + phase * 1.73 + aTarget.x * 0.11 - aTarget.y * 0.09);",
        "  float microWave = cos(uTime * speed * 1.67 + phase * 0.61);",
        "  float amplitude = mix(0.024, 0.062, aSeed.z) * activity;",
        "  float foregroundFreedom = smoothstep(0.94, 1.0, aSeed.w) * (1.0 - clarity * 0.55);",
        "  vec2 turbulence = vec2(waveA + waveB * 0.43, waveB + microWave * 0.37);",
        "  vec2 slowDrift = vec2(sin(uTime * 0.19 + phase), cos(uTime * 0.16 + phase * 1.31));",
        "  slowDrift *= (0.011 + aSeed.x * 0.014) * activity;",
        "  vec2 breathingDirection = normalize(aTarget.xy + vec2(0.001));",
        "  float breathing = sin(uTime * (0.32 + aSeed.y * 0.22) + phase) * (0.006 + aSeed.z * 0.009) * activity;",
        "  pos.xy += (turbulence * amplitude + slowDrift + breathingDirection * breathing) * alive;",
        "  pos.xy += vec2(waveB, waveA) * foregroundFreedom * 0.055 * alive;",
        "  pos.z += (waveC * (0.055 + aSeed.z * 0.075) + microWave * 0.024 + foregroundFreedom * waveA * 0.12) * activity * alive;",
        "  pos.z = mix(pos.z, aTarget.z + waveC * 0.012, clarity * 0.76);",
        "  vec2 rippleDelta = aTarget.xy - uRippleOrigin;",
        "  float rippleDistance = length(rippleDelta);",
        "  float ringDistance = abs(rippleDistance - uRipple * 8.0);",
        "  float ring = exp(-ringDistance * ringDistance * 3.2) * uRippleStrength;",
        "  pos.xy += normalize(rippleDelta + vec2(0.0001)) * ring * 0.22;",
        "  pos.z += ring * (0.5 + aSeed.z * 0.8);",
        "  vec3 disperseDirection = normalize(aScatter - aTarget + vec3(0.001));",
        "  pos += disperseDirection * uDisperse * (0.9 + aSeed.z * 3.2);",
        "  pos.z += uDisperse * (aSeed.x - 0.5) * 3.8;",
        "  pos.xy *= uZoom;",
        "  pos.z += (uZoom - 1.0) * 0.55;",
        "  vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);",
        "  gl_Position = projectionMatrix * mvPosition;",
        "  float perspective = 34.0 / max(2.0, -mvPosition.z);",
        "  gl_PointSize = clamp(aSize * uPointScale * uPixelRatio * perspective * (1.0 + clarity * 0.2), 1.0, 22.0);",
        "  vAlpha = mix(0.16 + aBrightness * 0.94 + clarity * 0.22, 0.11, aAtmosphere) * (1.0 - uDisperse * 0.58);",
        "  vColorMix = fract(aSeed.x * 1.73 + aTarget.x * 0.055 + aTarget.y * 0.035);",
        "  vAccentMix = fract(aSeed.y * 2.17 + aTarget.y * 0.08);",
        "  vHighlight = smoothstep(0.76, 1.0, aBrightness) * 0.7 + smoothstep(0.94, 0.995, aSeed.z) + clarity * 0.2 + ring * 0.9;",
        "  vRipple = ring;",
        "  vImageColor = aImageColor;",
        "  vBrightness = aBrightness;",
        "  vClarity = clarity;",
        "}"
    ].join("\n");

    var fragmentShader = [
        "precision highp float;",
        "uniform sampler2D uTexture;",
        "uniform vec3 uColorA;",
        "uniform vec3 uColorB;",
        "uniform vec3 uColorC;",
        "uniform vec3 uHighlight;",
        "uniform float uPaletteMix;",
        "uniform float uOpacity;",
        "varying float vAlpha;",
        "varying float vColorMix;",
        "varying float vAccentMix;",
        "varying float vHighlight;",
        "varying float vRipple;",
        "varying vec3 vImageColor;",
        "varying float vBrightness;",
        "varying float vClarity;",
        "void main() {",
        "  vec4 texel = texture2D(uTexture, gl_PointCoord);",
        "  float luminance = max(texel.r, max(texel.g, texel.b));",
        "  float spriteAlpha = smoothstep(0.015, 0.86, luminance);",
        "  if (spriteAlpha < 0.008) discard;",
        "  vec3 paletteColor = mix(uColorA, uColorB, smoothstep(0.08, 0.92, vColorMix));",
        "  paletteColor = mix(paletteColor, uColorC, smoothstep(0.68, 0.98, vAccentMix) * 0.66);",
        "  vec3 color = mix(vImageColor, paletteColor * (0.58 + vBrightness * 0.64), uPaletteMix);",
        "  color = mix(color, uHighlight, clamp(vHighlight * 0.68 + vRipple * 0.4, 0.0, 0.88));",
        "  color *= 0.82 + vBrightness * 0.36 + vClarity * 0.12;",
        "  float alpha = spriteAlpha * clamp(vAlpha, 0.08, 1.0) * uOpacity;",
        "  gl_FragColor = vec4(color * (0.78 + luminance * 0.55), alpha);",
        "}"
    ].join("\n");

    function getQuality() {
        var width = window.innerWidth;
        var cores = navigator.hardwareConcurrency || 4;
        var memory = navigator.deviceMemory || 4;
        var count = width >= 1280 ? 56000 : (width >= 768 ? 34000 : 15000);

        if (cores <= 4 || memory <= 4) count = Math.round(count * 0.78);
        if (reducedMotion.matches) count = Math.min(count, width >= 768 ? 18000 : 8000);

        return {
            count: Math.max(7200, count),
            rasterWidth: width >= 1280 ? 860 : (width >= 768 ? 720 : 560),
            pixelRatio: Math.min(window.devicePixelRatio || 1, width >= 768 ? 1.5 : 1.2),
            pointScale: width >= 1280 ? 1.08 : (width >= 768 ? 1.16 : 1.3)
        };
    }

    function seededRandom(seed) {
        var state = seed >>> 0;
        return function () {
            state += 0x6D2B79F5;
            var value = state;
            value = Math.imul(value ^ value >>> 15, value | 1);
            value ^= value + Math.imul(value ^ value >>> 7, value | 61);
            return ((value ^ value >>> 14) >>> 0) / 4294967296;
        };
    }

    function loadSourceImage(url) {
        return new Promise(function (resolve, reject) {
            var image = new Image();
            image.decoding = "async";
            image.onload = function () { resolve(image); };
            image.onerror = function () { reject(new Error("Shape teks hero tidak dapat dimuat")); };
            image.src = url;
        });
    }

    function sampleTextShapePoints(image, count, rasterWidth) {
        if (!image || !image.naturalWidth || !image.naturalHeight) {
            throw new Error("Shape teks hero tidak valid");
        }

        var aspect = image.naturalHeight / image.naturalWidth;
        var width = rasterWidth;
        var height = Math.max(280, Math.round(width * aspect));
        var canvas = document.createElement("canvas");
        var context = canvas.getContext("2d", { willReadFrequently: true });
        var random = seededRandom(20260810);
        var textCount = Math.floor(count * 0.976);
        var target = new Float32Array(count * 3);
        var atmosphere = new Float32Array(count);
        var imageColor = new Float32Array(count * 3);
        var brightness = new Float32Array(count);
        var worldWidth = 12.4;
        var worldHeight = worldWidth * aspect;
        var pixelCount = width * height;
        var cumulativeWeight = new Float64Array(pixelCount);
        var pixels;
        var totalWeight = 0;
        var i;

        canvas.width = width;
        canvas.height = height;
        context.clearRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);
        pixels = context.getImageData(0, 0, width, height).data;

        // The SVG is a transparent text mask. Sampling only non-transparent pixels
        // keeps nearly every particle inside the letterforms instead of producing a
        // uniform star field across the hero.
        for (var pixelIndex = 0; pixelIndex < pixelCount; pixelIndex += 1) {
            var alpha = pixels[pixelIndex * 4 + 3] / 255;
            var weight = alpha > 0.035 ? Math.pow(alpha, 0.62) : 0;
            totalWeight += weight;
            cumulativeWeight[pixelIndex] = totalWeight;
        }

        if (totalWeight <= 0) {
            throw new Error("Shape teks hero tidak memiliki area yang dapat disampling");
        }

        function weightedPixelIndex() {
            var value = random() * totalWeight;
            var low = 0;
            var high = cumulativeWeight.length - 1;

            while (low < high) {
                var middle = (low + high) >>> 1;
                if (cumulativeWeight[middle] < value) low = middle + 1;
                else high = middle;
            }

            return low;
        }

        function writeTextPoint(index, px, py, depthJitter) {
            var x = Math.max(0, Math.min(width - 1, Math.floor(px)));
            var y = Math.max(0, Math.min(height - 1, Math.floor(py)));
            var pixelIndex = (y * width + x) * 4;
            var alpha = pixels[pixelIndex + 3] / 255;
            var isBrandLine = py / height > 0.64;
            var offset = index * 3;

            target[offset] = (px / width - 0.5) * worldWidth;
            target[offset + 1] = (0.5 - py / height) * worldHeight;
            target[offset + 2] = (py / height - 0.5) * 0.16 + depthJitter;
            imageColor[offset] = isBrandLine ? 0.945 : 0.95;
            imageColor[offset + 1] = isBrandLine ? 0.353 : 0.95;
            imageColor[offset + 2] = isBrandLine ? 0.169 : 0.93;
            brightness[index] = 0.62 + alpha * 0.38;
            atmosphere[index] = 0;
        }

        for (i = 0; i < textCount; i += 1) {
            var selectedIndex = weightedPixelIndex();
            var sampleX = selectedIndex % width;
            var sampleY = Math.floor(selectedIndex / width);
            writeTextPoint(
                i,
                sampleX + (random() - 0.5) * 0.78,
                sampleY + (random() - 0.5) * 0.78,
                (random() - 0.5) * 0.28
            );
        }

        // A very small ambient field adds depth without filling the dark negative space.
        for (i = textCount; i < count; i += 1) {
            var targetIndex = i * 3;
            target[targetIndex] = (random() - 0.5) * worldWidth * 1.12;
            target[targetIndex + 1] = (random() - 0.5) * worldHeight * 0.92;
            target[targetIndex + 2] = 0.8 + random() * 2.8;
            imageColor[targetIndex] = 0.12;
            imageColor[targetIndex + 1] = 0.28;
            imageColor[targetIndex + 2] = 0.7;
            brightness[i] = 0.16 + random() * 0.2;
            atmosphere[i] = 1;
        }

        return {
            target: target,
            atmosphere: atmosphere,
            imageColor: imageColor,
            brightness: brightness,
            worldWidth: worldWidth,
            worldHeight: worldHeight
        };
    }

    function createAttributes(sampled, count) {
        var random = seededRandom(731042);
        var scatter = new Float32Array(count * 3);
        var seed = new Float32Array(count * 4);
        var size = new Float32Array(count);

        for (var i = 0; i < count; i += 1) {
            var angle = random() * Math.PI * 2;
            var elevation = (random() - 0.5) * Math.PI;
            var radius = 7.5 + Math.pow(random(), 0.55) * 12.5;
            scatter[i * 3] = Math.cos(angle) * Math.cos(elevation) * radius;
            scatter[i * 3 + 1] = Math.sin(elevation) * radius * 0.72;
            scatter[i * 3 + 2] = Math.sin(angle) * Math.cos(elevation) * radius + (random() - 0.5) * 7;
            seed[i * 4] = random();
            seed[i * 4 + 1] = random();
            seed[i * 4 + 2] = random();
            seed[i * 4 + 3] = random();

            var rarity = random();
            var luminanceScale = 0.84 + sampled.brightness[i] * 0.5;
            size[i] = (rarity > 0.995 ? 4.8 + random() * 2.2
                : (rarity > 0.95 ? 2.5 + random() * 1.7 : 1.08 + random() * 1.42)) * luminanceScale;
        }

        return { scatter: scatter, seed: seed, size: size };
    }

    function createFallbackTexture() {
        var canvas = document.createElement("canvas");
        canvas.width = canvas.height = 64;
        var context = canvas.getContext("2d");
        var glow = context.createRadialGradient(32, 32, 0, 32, 32, 31);
        glow.addColorStop(0, "rgba(255,255,255,1)");
        glow.addColorStop(0.25, "rgba(255,255,255,.88)");
        glow.addColorStop(1, "rgba(255,255,255,0)");
        context.fillStyle = glow;
        context.fillRect(0, 0, 64, 64);
        var texture = new THREE.CanvasTexture(canvas);
        texture.generateMipmaps = false;
        texture.minFilter = THREE.LinearFilter;
        return texture;
    }

    function loadParticleTexture(url) {
        return new Promise(function (resolve) {
            new THREE.TextureLoader().load(url, function (texture) {
                texture.generateMipmaps = false;
                texture.minFilter = THREE.LinearFilter;
                texture.magFilter = THREE.LinearFilter;
                resolve(texture);
            }, undefined, function () {
                resolve(createFallbackTexture());
            });
        });
    }

    function setPalette(index, animate) {
        var palette = palettes[index];
        var duration = animate ? 1 : 0;
        var targets = [
            { color: uniforms.uColorA.value, value: palette.a },
            { color: uniforms.uColorB.value, value: palette.b },
            { color: uniforms.uColorC.value, value: palette.c },
            { color: uniforms.uHighlight.value, value: palette.h }
        ];

        if (paletteTween) paletteTween.kill();

        if (!animate || !window.gsap) {
            targets.forEach(function (target) { target.color.setHex(target.value); });
            uniforms.uPaletteMix.value = palette.mix;
        } else {
            paletteTween = gsap.timeline({ defaults: { duration: duration, ease: "power2.inOut" } });
            targets.forEach(function (target) {
                var next = new THREE.Color(target.value);
                paletteTween.to(target.color, { r: next.r, g: next.g, b: next.b }, 0);
            });
            paletteTween.to(uniforms.uPaletteMix, { value: palette.mix }, 0);
        }

        hero.setAttribute("data-palette", palette.name);
        if (paletteStatus) paletteStatus.textContent = "Palet particle " + palette.name + " aktif";
    }

    function fitFormation() {
        if (!camera || !particleGroup) return;
        var rect = hero.getBoundingClientRect();
        var aspect = Math.max(0.35, rect.width / Math.max(1, rect.height));
        var visibleHeight = 2 * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * camera.position.z;
        var visibleWidth = visibleHeight * aspect;
        var titleRect = titleElement ? titleElement.getBoundingClientRect() : rect;
        var targetWidth = visibleWidth * Math.min(0.92, titleRect.width / Math.max(1, rect.width));
        var targetHeight = visibleHeight * Math.min(0.72, titleRect.height / Math.max(1, rect.height));
        var scale = Math.min(targetWidth / formationWidth, targetHeight / formationHeight) * 0.98;

        formationBaseX = (((titleRect.left + titleRect.width * 0.5) - rect.left) / rect.width - 0.5) * visibleWidth;
        formationBaseY = (0.5 - ((titleRect.top + titleRect.height * 0.5) - rect.top) / rect.height) * visibleHeight;
        particleGroup.scale.setScalar(scale);
        particleGroup.position.set(formationBaseX, formationBaseY, 0);
    }

    function handleResize() {
        if (!renderer || !camera || !hero) return;
        var width = Math.max(1, hero.clientWidth);
        var height = Math.max(1, hero.clientHeight);
        var quality = getQuality();
        renderer.setPixelRatio(quality.pixelRatio);
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        uniforms.uPixelRatio.value = quality.pixelRatio;
        uniforms.uPointScale.value = quality.pointScale;
        fitFormation();
    }

    function pointerToWorld(event) {
        var rect = hero.getBoundingClientRect();
        pointerWorld.set(
            ((event.clientX - rect.left) / rect.width) * 2 - 1,
            -((event.clientY - rect.top) / rect.height) * 2 + 1,
            0.2
        );
        pointerWorld.unproject(camera).sub(camera.position).normalize();
        var distance = -camera.position.z / pointerWorld.z;
        return pointerWorld.multiplyScalar(distance).add(camera.position);
    }

    function pointerToParticleSpace(event) {
        var world = pointerToWorld(event);
        if (particleGroup) {
            particleGroup.updateMatrixWorld(true);
            particleGroup.worldToLocal(world);
        }
        return world;
    }

    function onPointerMove(event) {
        if (!finePointer.matches || reducedMotion.matches || !camera) return;
        var rect = hero.getBoundingClientRect();
        var normalizedX = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        var normalizedY = -(((event.clientY - rect.top) / rect.height) * 2 - 1);
        pointerTarget.x = normalizedX;
        pointerTarget.y = normalizedY;

        var world = pointerToParticleSpace(event);
        uniforms.uPointer.value.set(world.x, world.y);
        // The cursor is a local focus lens: nearby pixels condense while the rest keeps flowing.
        focusTarget = 1;
    }

    function onPointerLeave() {
        pointerTarget.x = 0;
        pointerTarget.y = 0;
        focusTarget = 0;
    }

    function onHeroClick(event) {
        if (event.target.closest("a, button, input, select, textarea, label")) return;
        if (!camera || !window.gsap) return;

        var world = pointerToParticleSpace(event);
        uniforms.uRippleOrigin.value.set(world.x, world.y);
        uniforms.uRipple.value = 0;
        uniforms.uRippleStrength.value = reducedMotion.matches ? 0.18 : 1;

        if (rippleTween) rippleTween.kill();
        rippleTween = gsap.timeline()
            .to(uniforms.uRipple, { value: 1.18, duration: reducedMotion.matches ? 0.35 : 1.15, ease: "power2.out" }, 0)
            .to(uniforms.uRippleStrength, { value: 0, duration: 0.7, ease: "power2.out" }, 0.48);

        paletteIndex = (paletteIndex + 1) % palettes.length;
        setPalette(paletteIndex, true);
    }

    function setupInteraction() {
        hero.addEventListener("pointermove", onPointerMove, { passive: true });
        hero.addEventListener("pointerleave", onPointerLeave, { passive: true });
        hero.addEventListener("click", onHeroClick);
    }

    function setupScrollAnimation() {
        if (reducedMotion.matches || !window.gsap || !window.ScrollTrigger) return;
        gsap.registerPlugin(ScrollTrigger);
        var nextScene = hero.nextElementSibling;
        var nextSceneContent = nextScene ? nextScene.querySelector(".scene__in") : null;
        scrollTimeline = gsap.timeline({
            scrollTrigger: {
                trigger: hero,
                start: "top top",
                end: "bottom top",
                scrub: 1,
                invalidateOnRefresh: true
            }
        });
        if (mist) gsap.set(mist, { autoAlpha: 0, yPercent: 12, scaleY: 0.9, transformOrigin: "50% 100%" });
        if (nextSceneContent) gsap.set(nextSceneContent, { y: 48, autoAlpha: 0 });
        scrollTimeline
            .to(uniforms.uZoom, { value: 1.045, duration: 0.3, ease: "power1.out" }, 0)
            .to(ui, { y: -30, autoAlpha: 0.08, duration: 0.52, ease: "power1.in" }, 0.4)
            .to(uniforms.uDisperse, { value: 1.12, duration: 0.52, ease: "power1.in" }, 0.48)
            .to(uniforms.uOpacity, { value: 0.05, duration: 0.46, ease: "power1.in" }, 0.53);
        if (mist) {
            scrollTimeline
                .to(mist, { autoAlpha: 1, duration: 0.22, ease: "power1.out" }, 0.32)
                .to(mist, { yPercent: -2, scaleY: 1.02, duration: 0.42, ease: "power1.inOut" }, 0.34);
        }
        if (nextSceneContent) scrollTimeline.to(nextSceneContent, { y: 0, autoAlpha: 1, duration: 0.3, ease: "power2.out" }, 0.48);
    }

    function openingAnimation() {
        if (!window.gsap || reducedMotion.matches) {
            uniforms.uFormation.value = 1;
            focusTarget = reducedMotion.matches ? 0.72 : 0;
            focusCurrent = focusTarget;
            uniforms.uFocus.value = focusCurrent;
            if (ui) ui.style.opacity = "1";
            return;
        }

        gsap.set(canvasHost, { autoAlpha: 0 });
        gsap.set(ui ? ui.children : [], { y: 22, autoAlpha: 0 });
        gsap.timeline({ defaults: { ease: "power3.out" } })
            .to(canvasHost, { autoAlpha: 1, duration: 0.7 }, 0)
            .to(uniforms.uFormation, { value: 1, duration: 2.45, ease: "expo.inOut" }, 0.12)
            .to(ui ? ui.children : [], { y: 0, autoAlpha: 1, duration: 0.9, stagger: 0.1 }, 1.15);
    }

    function animate(now) {
        if (destroyed) return;
        frameId = requestAnimationFrame(animate);
        if (!visible || !pageVisible || !renderer || !scene || !camera) {
            lastTime = now || performance.now();
            return;
        }

        var stamp = now || performance.now();
        var delta = Math.min(50, stamp - lastTime);
        lastTime = stamp;
        if (!reducedMotion.matches) uniforms.uTime.value += delta * 0.001;

        var pointerDamping = 1 - Math.exp(-delta * 0.0055);
        var focusDamping = 1 - Math.exp(-delta * 0.0068);
        pointerCurrent.x += (pointerTarget.x - pointerCurrent.x) * pointerDamping;
        pointerCurrent.y += (pointerTarget.y - pointerCurrent.y) * pointerDamping;
        focusCurrent += (focusTarget - focusCurrent) * focusDamping;
        uniforms.uFocus.value = focusCurrent;

        if (particleGroup && finePointer.matches && !reducedMotion.matches) {
            var idleX = Math.sin(uniforms.uTime.value * 0.13) * 0.006;
            var idleY = Math.cos(uniforms.uTime.value * 0.11) * 0.004;
            particleGroup.rotation.y += ((pointerCurrent.x * 0.018 + idleX) - particleGroup.rotation.y) * 0.055;
            particleGroup.rotation.x += ((-pointerCurrent.y * 0.012 + idleY) - particleGroup.rotation.x) * 0.055;
            particleGroup.position.x += ((formationBaseX + pointerCurrent.x * 0.065 + Math.sin(uniforms.uTime.value * 0.16) * 0.018) - particleGroup.position.x) * 0.055;
            particleGroup.position.y += ((formationBaseY + pointerCurrent.y * 0.025 + Math.cos(uniforms.uTime.value * 0.14) * 0.014) - particleGroup.position.y) * 0.055;
        }

        if (!reducedMotion.matches) camera.position.z = 11 + Math.sin(uniforms.uTime.value * 0.18) * 0.03;

        renderer.render(scene, camera);
    }

    function setupVisibility() {
        if ("IntersectionObserver" in window) {
            visibilityObserver = new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                lastTime = performance.now();
            }, { rootMargin: "120px" });
            visibilityObserver.observe(hero);
        }

        document.addEventListener("visibilitychange", onDocumentVisibility);
    }

    function onDocumentVisibility() {
        pageVisible = !document.hidden;
        lastTime = performance.now();
    }

    function buildParticleSystem(sampled, attributes, count, texture) {
        geometry = new THREE.BufferGeometry();
        geometry.setAttribute("position", new THREE.BufferAttribute(sampled.target, 3));
        geometry.setAttribute("aTarget", new THREE.BufferAttribute(sampled.target, 3));
        geometry.setAttribute("aScatter", new THREE.BufferAttribute(attributes.scatter, 3));
        geometry.setAttribute("aSeed", new THREE.BufferAttribute(attributes.seed, 4));
        geometry.setAttribute("aSize", new THREE.BufferAttribute(attributes.size, 1));
        geometry.setAttribute("aAtmosphere", new THREE.BufferAttribute(sampled.atmosphere, 1));
        geometry.setAttribute("aImageColor", new THREE.BufferAttribute(sampled.imageColor, 3));
        geometry.setAttribute("aBrightness", new THREE.BufferAttribute(sampled.brightness, 1));

        formationWidth = sampled.worldWidth;
        formationHeight = sampled.worldHeight;

        uniforms.uTexture.value = texture;
        material = new THREE.ShaderMaterial({
            uniforms: uniforms,
            vertexShader: vertexShader,
            fragmentShader: fragmentShader,
            transparent: true,
            depthTest: true,
            depthWrite: false,
            blending: THREE.NormalBlending
        });

        points = new THREE.Points(geometry, material);
        points.frustumCulled = false;
        particleGroup = new THREE.Group();
        particleGroup.add(points);
        scene.add(particleGroup);
        fitFormation();
    }

    function initScene() {
        var quality = getQuality();
        var width = Math.max(1, hero.clientWidth);
        var height = Math.max(1, hero.clientHeight);

        renderer = new THREE.WebGLRenderer({ alpha: true, antialias: false, powerPreference: "high-performance" });
        renderer.setClearColor(0x020204, 0);
        renderer.setPixelRatio(quality.pixelRatio);
        renderer.setSize(width, height, false);
        renderer.domElement.setAttribute("aria-hidden", "true");
        canvasHost.appendChild(renderer.domElement);

        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(46, width / height, 0.1, 80);
        camera.position.set(0, 0, 11);
        pointerWorld = new THREE.Vector3();

        uniforms.uPointer.value = new THREE.Vector2(99, 99);
        uniforms.uRippleOrigin.value = new THREE.Vector2(99, 99);
        uniforms.uColorA.value = new THREE.Color(palettes[0].a);
        uniforms.uColorB.value = new THREE.Color(palettes[0].b);
        uniforms.uColorC.value = new THREE.Color(palettes[0].c);
        uniforms.uHighlight.value = new THREE.Color(palettes[0].h);
        uniforms.uPaletteMix.value = palettes[0].mix;
        uniforms.uPixelRatio.value = quality.pixelRatio;
        uniforms.uPointScale.value = quality.pointScale;

        return Promise.all([
            loadSourceImage(shapeUrl),
            loadParticleTexture(textureUrl)
        ]).then(function (assets) {
            var sampled = sampleTextShapePoints(assets[0], quality.count, quality.rasterWidth);
            var attributes = createAttributes(sampled, quality.count);
            spriteTexture = assets[1];
            buildParticleSystem(sampled, attributes, quality.count, spriteTexture);
            hero.classList.add("is-particle-ready");
            hero.setAttribute("data-particle-count", String(quality.count));
            setPalette(0, false);
            setupInteraction();
            setupScrollAnimation();
            setupVisibility();
            handleResize();
            openingAnimation();
            animate(performance.now());

            if ("ResizeObserver" in window) {
                resizeObserver = new ResizeObserver(handleResize);
                resizeObserver.observe(hero);
            } else {
                window.addEventListener("resize", handleResize, { passive: true });
            }

            if (window.ScrollTrigger) window.ScrollTrigger.refresh();
        });
    }

    function showFallback(error) {
        hero.classList.remove("is-particle-ready");
        hero.classList.add("is-particle-fallback");
        if (error) console.warn("Hero particle memakai fallback:", error);
    }

    function hasWebGL() {
        try {
            var testCanvas = document.createElement("canvas");
            return !!(window.WebGLRenderingContext && (testCanvas.getContext("webgl") || testCanvas.getContext("experimental-webgl")));
        } catch (error) {
            return false;
        }
    }

    function initHeroParticles() {
        if (initialized || destroyed) return;
        initialized = true;

        if (!canvasHost || !shapeUrl || !textureUrl || !window.THREE || !hasWebGL()) {
            showFallback(new Error("WebGL atau aset hero tidak tersedia"));
            return;
        }

        initScene().catch(showFallback);
    }

    function destroy() {
        if (destroyed) return;
        destroyed = true;
        cancelAnimationFrame(frameId);
        hero.removeEventListener("pointermove", onPointerMove);
        hero.removeEventListener("pointerleave", onPointerLeave);
        hero.removeEventListener("click", onHeroClick);
        document.removeEventListener("visibilitychange", onDocumentVisibility);
        window.removeEventListener("resize", handleResize);
        if (resizeObserver) resizeObserver.disconnect();
        if (visibilityObserver) visibilityObserver.disconnect();
        if (scrollTimeline) {
            if (scrollTimeline.scrollTrigger) scrollTimeline.scrollTrigger.kill();
            scrollTimeline.kill();
        }
        if (paletteTween) paletteTween.kill();
        if (rippleTween) rippleTween.kill();
        if (geometry) geometry.dispose();
        if (material) material.dispose();
        if (spriteTexture) spriteTexture.dispose();
        if (renderer) {
            renderer.dispose();
            if (renderer.domElement.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement);
        }
    }

    window.addEventListener("scm:landing-ready", initHeroParticles, { once: true });
    window.addEventListener("pagehide", destroy, { once: true });

    if (!document.body.classList.contains("is-loading")) initHeroParticles();
    window.setTimeout(function () {
        if (!initialized && !destroyed) initHeroParticles();
    }, 6500);
})();
