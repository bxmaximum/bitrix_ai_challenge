(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* 1. Круговая раскладка текста (.circular-title), как в Emblem.init референса Aesthetic Lab */
    function initCircularEmblems() {
        var elements = document.querySelectorAll('[data-circular-text]');
        elements.forEach(function (el) {
            var text = el.getAttribute('data-circular-text') || el.textContent || '';
            el.textContent = '';
            el.setAttribute('aria-hidden', 'true');
            var len = text.length;
            if (!len) {
                return;
            }
            for (var i = 0; i < len; i++) {
                var span = document.createElement('span');
                span.textContent = text[i];
                var deg = (360 / len) * i;
                span.style.transform = 'rotateZ(' + deg + 'deg)';
                el.appendChild(span);
            }
        });
    }

    /* 2. Раскрытие хиро от центра (.banner-split-text) и при скролле (.split-text) с сохранением переноса строк */
    function initSplitTextAnimations() {
        var bannerHeadings = document.querySelectorAll('.banner-split-text');
        bannerHeadings.forEach(function (heading) {
            if (prefersReducedMotion) {
                heading.classList.add('is-ready');
                return;
            }
            var text = (heading.textContent || '').trim();
            heading.setAttribute('aria-label', text);
            heading.textContent = '';
            var words = text.split(/\s+/);
            var mid = (words.length - 1) / 2;

            words.forEach(function (word, idx) {
                var span = document.createElement('span');
                span.className = 'word-unit';
                span.setAttribute('aria-hidden', 'true');
                span.textContent = word;
                var distFromCenter = idx - mid;
                var offsetPx = Math.round(distFromCenter * 14);
                span.style.transform = 'translate3d(' + offsetPx + 'px, 0, 0)';
                span.style.opacity = '0.92';
                span.style.transitionDelay = (Math.abs(distFromCenter) * 0.04) + 's';
                heading.appendChild(span);
                if (idx < words.length - 1) {
                    heading.appendChild(document.createTextNode(' '));
                }
            });

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    heading.classList.add('is-ready');
                });
            });
        });

        var scrollHeadings = document.querySelectorAll('.split-text');
        scrollHeadings.forEach(function (heading) {
            if (prefersReducedMotion) {
                heading.classList.add('is-inview');
                return;
            }
            var text = (heading.textContent || '').trim();
            heading.setAttribute('aria-label', text);
            heading.textContent = '';
            var words = text.split(/\s+/);
            words.forEach(function (word, wIdx) {
                var span = document.createElement('span');
                span.className = 'word-unit';
                span.setAttribute('aria-hidden', 'true');
                span.textContent = word;
                span.style.transitionDelay = (wIdx * 0.06) + 's';
                heading.appendChild(span);
                if (wIdx < words.length - 1) {
                    heading.appendChild(document.createTextNode(' '));
                }
            });
        });

        if (!prefersReducedMotion && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-inview');
                    }
                });
            }, { threshold: 0.2 });

            scrollHeadings.forEach(function (h) {
                observer.observe(h);
            });
        } else {
            scrollHeadings.forEach(function (h) {
                h.classList.add('is-inview');
            });
        }
    }

    /* 3. Мобильное меню (.navbar-toggler / .navbar-collapse) */
    function initMobileNav() {
        var toggler = document.querySelector('[data-nav-toggler]');
        var collapse = document.querySelector('[data-nav-collapse]');
        if (!toggler || !collapse) {
            return;
        }

        function closeMenu() {
            toggler.classList.remove('open');
            toggler.setAttribute('aria-expanded', 'false');
            collapse.classList.remove('show');
        }

        toggler.addEventListener('click', function () {
            var isOpen = toggler.classList.toggle('open');
            toggler.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            collapse.classList.toggle('show', isOpen);
        });

        collapse.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && collapse.classList.contains('show')) {
                closeMenu();
                toggler.focus();
            }
        });
    }

    /* 4. Слайдер отзывов (.testimonial-slider) */
    function initTestimonialSlider() {
        var slider = document.querySelector('[data-testimonial-slider]');
        if (!slider) {
            return;
        }
        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-testimonial-slide]'));
        var prevBtn = slider.querySelector('[data-testimonial-prev]');
        var nextBtn = slider.querySelector('[data-testimonial-next]');
        var counterEl = slider.querySelector('[data-testimonial-counter]');
        if (!slides.length) {
            return;
        }

        var current = 0;

        function showSlide(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (slide, idx) {
                var active = idx === current;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
            if (counterEl) {
                counterEl.textContent = (current + 1) + ' / ' + slides.length;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                showSlide(current - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                showSlide(current + 1);
            });
        }

        showSlide(0);
    }

    /* 5. Следование превью-фотографии услуги за курсором (.services-list-block) на десктопе */
    function initServicesHoverPreview() {
        if (prefersReducedMotion) {
            return;
        }
        var blocks = document.querySelectorAll('.services-list-block');
        blocks.forEach(function (block) {
            var imgWrap = block.querySelector('.service-list-img');
            if (!imgWrap) {
                return;
            }
            block.addEventListener('mousemove', function (e) {
                if (window.innerWidth < 992) {
                    return;
                }
                var rect = block.getBoundingClientRect();
                var relX = (e.clientX - rect.left) / Math.max(1, rect.width);
                var offsetPercent = (relX - 0.5) * 40;
                imgWrap.style.transform = 'translate3d(' + offsetPercent.toFixed(1) + '%, 0, 0)';
            });
            block.addEventListener('mouseleave', function () {
                imgWrap.style.transform = 'translate3d(0, 8px, 0)';
            });
        });
    }

    /* 6. Связь кнопок «Выбрать время» у услуг и «Записаться к мастеру» с виджетом записи */
    function initQuickPickButtons() {
        document.addEventListener('click', function (e) {
            var srvBtn = e.target.closest('[data-pick-service]');
            if (srvBtn) {
                e.preventDefault();
                var serviceId = Number(srvBtn.getAttribute('data-pick-service'));
                if (window.BxmaxBookingWidget && typeof window.BxmaxBookingWidget.selectService === 'function') {
                    window.BxmaxBookingWidget.selectService(serviceId);
                }
                var bookingSection = document.getElementById('booking');
                if (bookingSection) {
                    bookingSection.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                }
                return;
            }

            var mstBtn = e.target.closest('[data-pick-master]');
            if (mstBtn) {
                e.preventDefault();
                var masterId = Number(mstBtn.getAttribute('data-pick-master'));
                if (window.BxmaxBookingWidget && typeof window.BxmaxBookingWidget.selectMaster === 'function') {
                    window.BxmaxBookingWidget.selectMaster(masterId);
                }
                var target = document.getElementById('booking');
                if (target) {
                    target.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
                }
            }
        });
    }

    function init() {
        initCircularEmblems();
        initSplitTextAnimations();
        initMobileNav();
        initTestimonialSlider();
        initServicesHoverPreview();
        initQuickPickButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
