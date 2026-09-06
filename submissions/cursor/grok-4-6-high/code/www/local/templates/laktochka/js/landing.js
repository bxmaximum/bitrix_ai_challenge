(function () {
    'use strict';

    function boot() {
    var loader = document.querySelector('[data-loader]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (loader && !reduce) {
        window.setTimeout(function () {
            loader.classList.add('is-done');
        }, 900);
    } else if (loader) {
        loader.classList.add('is-done');
    }

    var menu = document.querySelector('[data-menu]');
    var openBtn = document.querySelector('[data-menu-open]');
    var closeBtn = document.querySelector('[data-menu-close]');

    function setMenu(open) {
        if (!menu || !openBtn) {
            return;
        }
        menu.hidden = !open;
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            closeBtn.focus();
        } else {
            openBtn.focus();
        }
    }

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            setMenu(true);
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setMenu(false);
        });
    }
    if (menu) {
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setMenu(false);
            });
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && menu && !menu.hidden) {
            setMenu(false);
        }
    });

    if (!reduce && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18 });
        document.querySelectorAll('.reveal').forEach(function (el) {
            io.observe(el);
        });
    } else {
        document.querySelectorAll('.reveal').forEach(function (el) {
            el.classList.add('is-in');
        });
    }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
