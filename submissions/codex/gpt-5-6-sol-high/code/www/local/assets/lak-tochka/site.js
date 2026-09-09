(function () {
    'use strict';
    function init() {
    const header = document.querySelector('[data-site-header]');
    const menuButton = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    function updateHeader() { header?.classList.toggle('is-scrolled', scrollY > 40); }
    addEventListener('scroll', updateHeader, {passive: true}); updateHeader();
    menuButton?.addEventListener('click', () => {
        const open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        header.classList.toggle('is-menu-open', open); document.body.classList.toggle('menu-open', open);
    });
    menu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
        menuButton.setAttribute('aria-expanded', 'false'); header.classList.remove('is-menu-open'); document.body.classList.remove('menu-open');
    }));
    if (reduced) document.querySelectorAll('.reveal').forEach((item) => item.classList.add('is-visible'));
    else {
        const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
        }), {threshold: .12});
        document.querySelectorAll('.reveal').forEach((item) => observer.observe(item));
    }
    const reviews = Array.from(document.querySelectorAll('.review'));
    const counter = document.querySelector('[data-review-count]'); let reviewIndex = 0;
    function showReview(next) {
        if (!reviews.length) return; reviewIndex = (next + reviews.length) % reviews.length;
        reviews.forEach((item, index) => item.classList.toggle('is-active', index === reviewIndex));
        if (counter) counter.textContent = `${String(reviewIndex + 1).padStart(2, '0')} / ${String(reviews.length).padStart(2, '0')}`;
    }
    document.querySelector('[data-review-prev]')?.addEventListener('click', () => showReview(reviewIndex - 1));
    document.querySelector('[data-review-next]')?.addEventListener('click', () => showReview(reviewIndex + 1));
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
    else init();
})();
