/* Лак&Точка — движение и интерактив лендинга. Ванильный JS, без библиотек. */
(function () {
    'use strict';

    var doc = document;
    var root = doc.documentElement;
    var mqMotion = window.matchMedia('(prefers-reduced-motion: no-preference)');
    var mqWide = window.matchMedia('(min-width: 768px)');
    var motion = mqMotion.matches;

    if (motion) { root.classList.add('lt-motion'); }

    function clamp(v, min, max) { return Math.min(max, Math.max(min, v)); }
    function pinOn() { return motion && mqWide.matches; }

    var ticking = false;
    var tasks = [];
    function schedule() {
        if (ticking) { return; }
        ticking = true;
        requestAnimationFrame(function () {
            ticking = false;
            for (var i = 0; i < tasks.length; i++) { tasks[i](); }
        });
    }

    /* ---------- шапка и меню ---------- */
    function initHeader() {
        var header = doc.querySelector('[data-header]');
        var burger = doc.querySelector('[data-burger]');
        if (!header) { return; }

        function onScroll() { header.classList.toggle('is-scrolled', window.scrollY > 24); }
        tasks.push(onScroll);
        onScroll();

        if (!burger) { return; }
        function setOpen(open) {
            header.classList.toggle('is-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        burger.addEventListener('click', function () { setOpen(burger.getAttribute('aria-expanded') !== 'true'); });
        header.addEventListener('click', function (e) { if (e.target.closest('a')) { setOpen(false); } });
        doc.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && header.classList.contains('is-open')) { setOpen(false); burger.focus(); }
        });
    }

    /* ---------- hero: «шторка» из золотых полос и параллакс ---------- */
    function initHero() {
        var hero = doc.querySelector('[data-hero]');
        if (!hero || !motion) { return; }
        var barsBox = hero.querySelector('[data-bars]');
        var img = hero.querySelector('.lt-hero__img');
        var bars = [];
        var count = window.innerWidth < 768 ? 14 : 30;
        for (var i = 0; i < count; i++) {
            var s = doc.createElement('span');
            barsBox.appendChild(s);
            bars.push(s);
        }
        tasks.push(function () {
            var y = window.scrollY;
            var h = hero.offsetHeight;
            if (y > h) { return; }
            var p = clamp(y / (h * 0.6), 0, 1);
            for (var i = 0; i < bars.length; i++) {
                var local = clamp((p - (i / bars.length) * 0.55) / 0.45, 0, 1);
                bars[i].style.transform = 'scaleY(' + local.toFixed(3) + ')';
            }
            if (img) { img.style.transform = 'translate3d(0,' + (y * 0.18).toFixed(1) + 'px,0)'; }
        });
    }

    /* ---------- заголовки: посимвольное проявление при скролле ---------- */
    var splits = [];
    function initSplit() {
        if (!motion) { return; }
        var nodes = doc.querySelectorAll('[data-split]');
        Array.prototype.forEach.call(nodes, function (el) {
            var text = el.textContent.replace(/\s+/g, ' ').trim();
            el.setAttribute('aria-label', text);
            el.textContent = '';
            var chars = [];
            text.split(' ').forEach(function (word, wi, all) {
                var w = doc.createElement('span');
                w.className = 'lt-word';
                w.style.display = 'inline-block';
                w.style.whiteSpace = 'nowrap';
                w.setAttribute('aria-hidden', 'true');
                for (var i = 0; i < word.length; i++) {
                    var c = doc.createElement('span');
                    c.className = 'lt-char';
                    c.textContent = word.charAt(i);
                    c.style.opacity = '.14';
                    w.appendChild(c);
                    chars.push(c);
                }
                el.appendChild(w);
                if (wi < all.length - 1) { el.appendChild(doc.createTextNode(' ')); }
            });
            splits.push({ el: el, chars: chars, pin: el.closest('[data-pin]') });
        });

        tasks.push(function () {
            var vh = window.innerHeight;
            splits.forEach(function (item) {
                var p;
                if (item.pin && pinOn()) {
                    var rect = item.pin.getBoundingClientRect();
                    var total = item.pin.offsetHeight - vh;
                    p = clamp((-rect.top / total) / 0.16, 0, 1);
                } else {
                    var top = item.el.getBoundingClientRect().top;
                    p = clamp((vh * 0.92 - top) / (vh * 0.42), 0, 1);
                }
                var n = item.chars.length;
                var r = p * (n + 7);
                for (var i = 0; i < n; i++) {
                    var o = clamp((r - i) / 7, 0, 1);
                    item.chars[i].style.opacity = (0.14 + 0.86 * o).toFixed(2);
                }
            });
        });
    }

    /* ---------- закреплённые секции: фото и цитаты проплывают мимо заголовка ---------- */
    function initPins() {
        var pins = Array.prototype.slice.call(doc.querySelectorAll('[data-pin]'));
        if (!pins.length) { return; }
        var states = pins.map(function (pin) {
            return {
                pin: pin,
                stage: pin.querySelector('[data-pin-stage]'),
                items: Array.prototype.slice.call(pin.querySelectorAll('[data-float]'))
            };
        });

        function clear() {
            states.forEach(function (s) { s.items.forEach(function (el) { el.style.transform = ''; }); });
        }
        mqWide.addEventListener && mqWide.addEventListener('change', function () { if (!pinOn()) { clear(); } schedule(); });

        tasks.push(function () {
            if (!pinOn()) { return; }
            var vh = window.innerHeight;
            states.forEach(function (s) {
                var rect = s.pin.getBoundingClientRect();
                if (rect.bottom < -vh || rect.top > vh * 2) { return; }
                var total = s.pin.offsetHeight - vh;
                var p = clamp(-rect.top / total, 0, 1);
                var stageH = s.stage.clientHeight;
                s.items.forEach(function (el) {
                    var tin = parseFloat(el.getAttribute('data-in')) || 0;
                    var tout = parseFloat(el.getAttribute('data-out')) || 1;
                    var t = clamp((p - tin) / (tout - tin), 0, 1);
                    var y = (1 - t) * (stageH + 20) + t * -(el.offsetHeight + 60);
                    el.style.transform = 'translate3d(0,' + y.toFixed(1) + 'px,0)';
                });
            });
        });
    }

    /* ---------- мягкое появление блоков ---------- */
    function initReveal() {
        var nodes = doc.querySelectorAll('[data-reveal]');
        if (!motion || !('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(nodes, function (n) { n.classList.add('is-in'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        Array.prototype.forEach.call(nodes, function (n, i) {
            n.style.transitionDelay = (i % 4) * 90 + 'ms';
            io.observe(n);
        });
    }

    /* ---------- карусели (услуги, работы) ---------- */
    function initCarousels() {
        Array.prototype.forEach.call(doc.querySelectorAll('[data-carousel]'), function (box) {
            var track = box.querySelector('[data-track]');
            var prev = box.querySelector('[data-prev]');
            var next = box.querySelector('[data-next]');
            if (!track) { return; }

            function step() {
                var first = track.querySelector('[data-slide]');
                if (!first) { return track.clientWidth * 0.8; }
                var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                return first.getBoundingClientRect().width + gap;
            }
            function update() {
                var max = track.scrollWidth - track.clientWidth - 2;
                if (prev) { prev.disabled = track.scrollLeft <= 2; }
                if (next) { next.disabled = track.scrollLeft >= max; }
            }
            function go(dir) {
                track.scrollBy({ left: dir * step(), behavior: motion ? 'smooth' : 'auto' });
            }
            if (prev) { prev.addEventListener('click', function () { go(-1); }); }
            if (next) { next.addEventListener('click', function () { go(1); }); }
            track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
            window.addEventListener('resize', update);
            update();
        });
    }

    /* ---------- прочее ---------- */
    function initMisc() {
        var details = doc.querySelector('[data-privacy]');
        doc.addEventListener('click', function (e) {
            var link = e.target.closest('a[href$="#privacy"]');
            if (link && details) {
                e.preventDefault();
                details.open = true;
                details.scrollIntoView({ behavior: motion ? 'smooth' : 'auto', block: 'center' });
                var sum = details.querySelector('summary');
                if (sum) { sum.focus({ preventScroll: true }); }
            }
            var top = e.target.closest('[data-to-top]');
            if (top) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: motion ? 'smooth' : 'auto' });
            }
        });
        if (window.location.hash === '#privacy' && details) { details.open = true; }
    }

    function init() {
        initHeader();
        initHero();
        initSplit();
        initPins();
        initReveal();
        initCarousels();
        initMisc();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        schedule();
    }

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
