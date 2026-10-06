/* =========================================================================
   Лак&Точка — поведение лендинга.
   Ванильный JS без библиотек: появление по скроллу, «жалюзи» в hero,
   скраб-морфинг арки, карусель карточек и подсветка активного пункта меню.
   ========================================================================= */

(function () {
	'use strict';

	var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var hasIO = 'IntersectionObserver' in window;

	/* ------------------------------------------------ разбивка заголовка по буквам */

	function splitChars(root) {
		if (root.dataset.splitDone === '1') {
			return;
		}

		var index = 0;

		function process(parent) {
			Array.prototype.slice.call(parent.childNodes).forEach(function (node) {
				if (node.nodeType === 3) {
					var text = node.textContent;
					if (!text.trim()) {
						return;
					}

					var fragment = document.createDocumentFragment();
					text.trim().split(/\s+/).forEach(function (word, wordIndex) {
						if (wordIndex > 0) {
							fragment.appendChild(document.createTextNode(' '));
						}

						var wordEl = document.createElement('span');
						wordEl.className = 'word';

						Array.from(word).forEach(function (character) {
							var charEl = document.createElement('span');
							charEl.className = 'char';
							charEl.style.setProperty('--i', index++);
							charEl.textContent = character;
							wordEl.appendChild(charEl);
						});

						fragment.appendChild(wordEl);
					});

					parent.replaceChild(fragment, node);
				} else if (node.nodeType === 1 && node.tagName !== 'BR') {
					process(node);
				}
			});
		}

		process(root);
		root.dataset.splitDone = '1';
	}

	/* ------------------------------------------------------------------ появление */

	function revealAll() {
		document.querySelectorAll('[data-reveal], [data-fade], [data-split]').forEach(function (element) {
			element.classList.add('is-visible', 'is-ready');
		});
	}

	/**
	 * Появление считаем по положению элемента, а не через IntersectionObserver:
	 * у [data-reveal] скрытое состояние — это clip-path с нулевой площадью, а
	 * Chrome учитывает собственный clip-path цели и никогда не сообщает о
	 * пересечении, из-за чего элемент оставался бы невидимым навсегда.
	 */
	function initReveal() {
		var targets = Array.prototype.slice.call(document.querySelectorAll('[data-reveal], [data-fade], [data-split]'));

		if (!targets.length) {
			return;
		}

		if (reduced) {
			revealAll();
			return;
		}

		var ticking = false;

		function check() {
			ticking = false;
			var limit = window.innerHeight * 0.88;

			targets = targets.filter(function (element) {
				if (element.getBoundingClientRect().top >= limit) {
					return true;
				}

				element.classList.add('is-visible', 'is-ready');

				return false;
			});

			if (!targets.length) {
				window.removeEventListener('scroll', onScroll);
			}
		}

		function onScroll() {
			if (ticking) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame(check);
		}

		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
		check();
	}

	/* --------------------------------------------------------------- hero-жалюзи */

	function initHero() {
		var hero = document.querySelector('.hero');
		if (!hero) {
			return;
		}

		window.requestAnimationFrame(function () {
			window.setTimeout(function () {
				hero.classList.add('is-ready');
			}, 120);
		});
	}

	/* ------------------------------------------------ скраб-морфинг радиуса арки */

	function initArch() {
		var arch = document.querySelector('.arch__frame');
		if (!arch || reduced || window.innerWidth < 1024) {
			return;
		}

		var from = 1250;
		var to = 450;
		var ticking = false;

		function update() {
			ticking = false;
			var rect = arch.getBoundingClientRect();
			var start = window.innerHeight * 0.96;
			var end = window.innerHeight * 0.1;
			var progress = (start - rect.top) / (start - end);
			progress = Math.max(0, Math.min(1, progress));
			arch.style.setProperty('--arch-radius', Math.round(from - (from - to) * progress) + 'px');
		}

		function onScroll() {
			if (ticking) {
				return;
			}
			ticking = true;
			window.requestAnimationFrame(update);
		}

		arch.style.setProperty('--arch-radius', from + 'px');
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
		update();
	}

	/* ------------------------------------------------------- карусели карточек */

	function initScrollers() {
		document.querySelectorAll('[data-scroller]').forEach(function (scroller) {
			var name = scroller.dataset.scroller;
			var prev = document.querySelector('[data-scroller-prev="' + name + '"]');
			var next = document.querySelector('[data-scroller-next="' + name + '"]');

			function step() {
				var item = scroller.children[0];
				if (!item) {
					return 320;
				}

				var gap = parseFloat(window.getComputedStyle(scroller).columnGap || '20') || 20;

				return item.getBoundingClientRect().width + gap;
			}

			function sync() {
				var max = scroller.scrollWidth - scroller.clientWidth - 2;

				if (prev) {
					prev.disabled = scroller.scrollLeft <= 2;
				}
				if (next) {
					next.disabled = scroller.scrollLeft >= max;
				}
			}

			if (prev) {
				prev.addEventListener('click', function () {
					scroller.scrollBy({ left: -step(), behavior: reduced ? 'auto' : 'smooth' });
				});
			}

			if (next) {
				next.addEventListener('click', function () {
					scroller.scrollBy({ left: step(), behavior: reduced ? 'auto' : 'smooth' });
				});
			}

			scroller.addEventListener('scroll', sync, { passive: true });
			window.addEventListener('resize', sync);
			sync();
		});
	}

	/* ------------------------------------------------- активный пункт меню по секции */

	function initSectionNav() {
		if (!hasIO) {
			return;
		}

		var links = {};
		document.querySelectorAll('[data-nav-list] a[href^="#"]').forEach(function (link) {
			links[link.getAttribute('href').slice(1)] = link;
		});

		var sections = Object.keys(links)
			.map(function (id) { return document.getElementById(id); })
			.filter(Boolean);

		if (!sections.length) {
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				var link = links[entry.target.id];
				if (!link) {
					return;
				}

				if (entry.isIntersecting) {
					Object.keys(links).forEach(function (id) {
						links[id].removeAttribute('aria-current');
					});
					link.setAttribute('aria-current', 'true');
				}
			});
		}, { rootMargin: '-45% 0px -50% 0px' });

		sections.forEach(function (section) {
			observer.observe(section);
		});
	}

	/* -------------------------------------------- предвыбор услуги/мастера в записи */

	function initPreselect() {
		document.querySelectorAll('[data-book-service], [data-book-master]').forEach(function (trigger) {
			trigger.addEventListener('click', function (event) {
				var detail = {};

				if (trigger.dataset.bookService) {
					detail.serviceId = parseInt(trigger.dataset.bookService, 10);
				}
				if (trigger.dataset.bookMaster) {
					detail.masterId = parseInt(trigger.dataset.bookMaster, 10);
				}

				event.preventDefault();
				document.dispatchEvent(new CustomEvent('lt:preselect', { detail: detail }));

				var target = document.getElementById('booking');
				if (target) {
					target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
				}
			});
		});
	}

	/* --------------------------------------------------------------- мобильное меню */

	function initMobileMenu() {
		var toggle = document.querySelector('[data-menu-toggle]');
		var nav = document.querySelector('[data-site-nav]');

		if (!toggle || !nav) {
			return;
		}

		function setOpen(open) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
			nav.classList.toggle('is-open', open);
			document.body.classList.toggle('is-locked', open);
		}

		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true');
		});

		nav.addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				setOpen(false);
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
				setOpen(false);
				toggle.focus();
			}
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth >= 900 && toggle.getAttribute('aria-expanded') === 'true') {
				setOpen(false);
			}
		});
	}

	/* ------------------------------------------------------------------- запуск */

	document.addEventListener('DOMContentLoaded', function () {
		if (!reduced) {
			document.querySelectorAll('[data-split]').forEach(splitChars);
		}

		initHero();
		initReveal();
		initArch();
		initScrollers();
		initSectionNav();
		initPreselect();
		initMobileMenu();
	});
})();
