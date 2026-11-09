/**
 * Лак&Точка — поведение лендинга. Чистый JS, без библиотек.
 * Шапка, мобильное меню, карусель услуг, закреплённые секции с всплывающими арками,
 * появление блоков и побуквенное проявление заголовков по прокрутке.
 */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

	function clamp(value, min, max) {
		return Math.min(max, Math.max(min, value));
	}

	function ready(callback) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', callback);
		} else {
			callback();
		}
	}

	ready(function () {
		var header = document.querySelector('[data-header]');
		var hero = document.querySelector('[data-hero]');
		var blinds = hero ? hero.querySelector('.hero__blinds') : null;
		var panel = document.getElementById('panel');
		var pins = [];
		var splits = [];
		var ticking = false;

		/* ---------- мобильное меню ---------- */

		var burger = document.querySelector('[data-burger]');
		var nav = document.querySelector('[data-nav]');
		var burgerLabel = burger ? burger.querySelector('[data-burger-label]') : null;

		function setMenu(open, returnFocus) {
			if (!burger || !nav) {
				return;
			}

			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			nav.classList.toggle('is-open', open);
			header.classList.toggle('is-menu-open', open);
			if (burgerLabel) {
				burgerLabel.textContent = open ? 'Закрыть меню' : 'Открыть меню';
			}
			if (!open && returnFocus) {
				burger.focus();
			}
		}

		if (burger && nav) {
			burger.addEventListener('click', function () {
				setMenu(burger.getAttribute('aria-expanded') !== 'true', false);
			});

			nav.addEventListener('click', function (event) {
				if (event.target.closest('a')) {
					setMenu(false, false);
				}
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') {
					setMenu(false, true);
				}
			});

			// Клавиатурный фокус ушёл из шапки — меню закрывается, чтобы не перекрывать страницу.
			header.addEventListener('focusout', function (event) {
				if (burger.getAttribute('aria-expanded') === 'true' && event.relatedTarget && !header.contains(event.relatedTarget)) {
					setMenu(false, false);
				}
			});
		}

		/* ---------- побуквенное проявление заголовков ---------- */

		function splitHeading(heading) {
			var label = heading.textContent.replace(/\s+/g, ' ').trim();
			var chars = [];

			Array.prototype.slice.call(heading.childNodes).forEach(function (node) {
				if (node.nodeType !== 3) {
					return;
				}

				var fragment = document.createDocumentFragment();
				node.textContent.split(/(\s+)/).forEach(function (part) {
					if (!part) {
						return;
					}
					if (/^\s+$/.test(part)) {
						fragment.appendChild(document.createTextNode(' '));

						return;
					}

					var word = document.createElement('span');
					word.className = 'split-word';
					Array.prototype.forEach.call(part, function (symbol) {
						var char = document.createElement('span');
						char.className = 'split-char';
						char.textContent = symbol;
						word.appendChild(char);
						chars.push(char);
					});
					fragment.appendChild(word);
				});

				heading.replaceChild(fragment, node);
			});

			// Скринридер читает заголовок целиком, а не по буквам.
			heading.setAttribute('aria-label', label);
			Array.prototype.forEach.call(heading.children, function (child) {
				child.setAttribute('aria-hidden', 'true');
			});

			return {element: heading, chars: chars, shown: 0};
		}

		if (!reduceMotion.matches) {
			Array.prototype.forEach.call(document.querySelectorAll('[data-split]'), function (heading) {
				splits.push(splitHeading(heading));
			});
		}

		/* ---------- закреплённые секции ---------- */

		function setPinned(enabled) {
			pins = [];
			Array.prototype.forEach.call(document.querySelectorAll('[data-pin]'), function (section) {
				section.classList.toggle('is-pinned', enabled);
				if (enabled) {
					pins.push(section);
				} else {
					section.style.removeProperty('--p');
				}
			});
		}

		setPinned(!reduceMotion.matches);

		/* ---------- всё, что зависит от прокрутки ---------- */

		function update() {
			ticking = false;

			var scrollY = window.pageYOffset;
			var viewport = window.innerHeight;

			if (header) {
				// Панель администратора Битрикса не должна прятаться под фиксированной шапкой.
				var panelBottom = panel ? Math.max(0, panel.getBoundingClientRect().bottom) : 0;
				header.style.setProperty('--header-top', panelBottom + 'px');
				header.classList.toggle('is-stuck', scrollY > 40);
			}

			if (blinds && !reduceMotion.matches) {
				var p = clamp(scrollY / (viewport * 0.8), 0, 1);
				blinds.style.setProperty('--p', p.toFixed(3));
				blinds.style.setProperty('--p2', clamp((p - 0.25) / 0.75, 0, 1).toFixed(3));
			}

			pins.forEach(function (section) {
				var rect = section.getBoundingClientRect();
				if (rect.bottom < -viewport || rect.top > viewport * 2) {
					return;
				}

				var distance = section.offsetHeight - viewport;
				section.style.setProperty('--p', clamp(-rect.top / distance, 0, 1).toFixed(4));
			});

			splits.forEach(function (split) {
				var rect = split.element.getBoundingClientRect();
				if (rect.top > viewport || split.shown === split.chars.length) {
					return;
				}

				var progress = clamp((viewport * 0.92 - rect.top) / (viewport * 0.42), 0, 1);
				var count = Math.round(progress * split.chars.length);
				for (var i = split.shown; i < count; i++) {
					split.chars[i].classList.add('is-on');
				}
				split.shown = Math.max(split.shown, count);
			});
		}

		function requestUpdate() {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		}

		window.addEventListener('scroll', requestUpdate, {passive: true});
		window.addEventListener('resize', requestUpdate);
		update();

		var onMotionChange = function () {
			setPinned(!reduceMotion.matches);
			requestUpdate();
		};
		if (reduceMotion.addEventListener) {
			reduceMotion.addEventListener('change', onMotionChange);
		} else if (reduceMotion.addListener) {
			reduceMotion.addListener(onMotionChange);
		}

		/* ---------- появление блоков ---------- */

		var reveals = document.querySelectorAll('.reveal');
		if ('IntersectionObserver' in window && !reduceMotion.matches) {
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-in');
						observer.unobserve(entry.target);
					}
				});
			}, {rootMargin: '0px 0px -8% 0px', threshold: 0.12});

			Array.prototype.forEach.call(reveals, function (element) {
				observer.observe(element);
			});
		} else {
			Array.prototype.forEach.call(reveals, function (element) {
				element.classList.add('is-in');
			});
		}

		/* ---------- карусель услуг ---------- */

		Array.prototype.forEach.call(document.querySelectorAll('[data-carousel]'), function (carousel) {
			var track = carousel.querySelector('[data-carousel-track]');
			var prev = carousel.querySelector('[data-carousel-prev]');
			var next = carousel.querySelector('[data-carousel-next]');
			if (!track || !prev || !next) {
				return;
			}

			function step() {
				var slide = track.querySelector('.carousel__slide');
				var gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;

				return slide ? slide.getBoundingClientRect().width + gap : track.clientWidth;
			}

			function sync() {
				var max = track.scrollWidth - track.clientWidth;
				prev.disabled = track.scrollLeft <= 2;
				next.disabled = track.scrollLeft >= max - 2;
			}

			function move(direction) {
				track.scrollBy({left: direction * step(), behavior: reduceMotion.matches ? 'auto' : 'smooth'});
			}

			prev.addEventListener('click', function () { move(-1); });
			next.addEventListener('click', function () { move(1); });
			track.addEventListener('scroll', sync, {passive: true});
			window.addEventListener('resize', sync);
			sync();
		});

		/* ---------- предвыбор услуги и мастера в форме записи ---------- */

		document.addEventListener('click', function (event) {
			var link = event.target.closest('[data-book-service], [data-book-master]');
			if (!link) {
				return;
			}

			var detail = {};
			if (link.hasAttribute('data-book-service')) {
				detail.serviceId = Number(link.getAttribute('data-book-service'));
			}
			if (link.hasAttribute('data-book-master')) {
				detail.masterId = Number(link.getAttribute('data-book-master'));
			}

			// Сам переход к #booking остаётся обычной якорной ссылкой.
			document.dispatchEvent(new CustomEvent('bxmax:booking:select', {detail: detail}));
		});
	});
})();
