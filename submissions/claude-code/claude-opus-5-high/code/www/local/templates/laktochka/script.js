/* Лак&Точка — движение лендинга: побуквенное проявление заголовков,
   появление секций, лёгкий параллакс и карусели. Без библиотек. */
(function () {
	'use strict';

	var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	/* --- Шапка: фон появляется после первого экрана --------------------- */
	function initHeader() {
		var header = document.getElementById('siteHeader');
		if (!header) {
			return;
		}

		var apply = function () {
			header.classList.toggle('is-stuck', window.scrollY > 40);
		};

		apply();
		window.addEventListener('scroll', apply, { passive: true });
	}

	/* --- Мобильное меню --------------------------------------------------- */
	function initBurger() {
		var burger = document.getElementById('burger');
		var nav = document.getElementById('siteNav');
		if (!burger || !nav) {
			return;
		}

		var close = function () {
			burger.setAttribute('aria-expanded', 'false');
			burger.setAttribute('aria-label', 'Открыть меню');
			nav.classList.remove('is-open');
		};

		burger.addEventListener('click', function () {
			var open = burger.getAttribute('aria-expanded') === 'true';
			burger.setAttribute('aria-expanded', open ? 'false' : 'true');
			burger.setAttribute('aria-label', open ? 'Открыть меню' : 'Закрыть меню');
			nav.classList.toggle('is-open', !open);
		});

		nav.addEventListener('click', function (event) {
			if (event.target.closest('a')) {
				close();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				close();
			}
		});
	}

	/* --- Разбивка заголовков на буквы ------------------------------------- */
	/* Заголовок разбивается на слова, слова — на буквы: перенос строки допустим
	   только между словами, иначе слово рвётся посередине. */
	function splitHeading(node) {
		var text = node.textContent.replace(/\s+/g, ' ').trim();
		var fragment = document.createDocumentFragment();
		var tokens = text.split(' ');

		tokens.forEach(function (token, index) {
			var word = document.createElement('span');
			word.className = 'word';
			word.setAttribute('aria-hidden', 'true');

			Array.prototype.forEach.call(token, function (letter) {
				var span = document.createElement('span');
				span.className = 'char';
				span.textContent = letter;
				word.appendChild(span);
			});

			fragment.appendChild(word);

			if (index < tokens.length - 1) {
				fragment.appendChild(document.createTextNode(' '));
			}
		});

		var label = document.createElement('span');
		label.className = 'visually-hidden';
		label.textContent = text;

		node.textContent = '';
		node.appendChild(label);
		node.appendChild(fragment);

		return Array.prototype.slice.call(node.querySelectorAll('.char'));
	}

	function initSplitHeadings() {
		var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-split]'));
		if (!nodes.length) {
			return;
		}

		if (reduced.matches) {
			nodes.forEach(function (node) {
				node.removeAttribute('data-split');
			});
			return;
		}

		var items = [];

		nodes.forEach(function (node) {
			var chars = splitHeading(node);

			/* Заголовок первого экрана проявляется сразу после загрузки, а не по скроллу */
			if (node.getAttribute('data-split-mode') === 'load') {
				chars.forEach(function (char, index) {
					window.setTimeout(function () {
						char.classList.add('is-lit');
					}, 220 + index * 34);
				});
				return;
			}

			items.push({ node: node, chars: chars });
		});

		if (!items.length) {
			return;
		}

		var ticking = false;

		var paint = function () {
			ticking = false;
			var viewport = window.innerHeight;

			items.forEach(function (item) {
				var rect = item.node.getBoundingClientRect();
				/* Прогресс: 0 — заголовок только вошёл снизу, 1 — дошёл до трети экрана */
				var progress = (viewport - rect.top) / (viewport * 0.62);
				progress = Math.max(0, Math.min(1, progress));

				var lit = Math.round(progress * item.chars.length);
				for (var i = 0; i < item.chars.length; i++) {
					item.chars[i].classList.toggle('is-lit', i < lit);
				}
			});
		};

		var onScroll = function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(paint);
			}
		};

		paint();
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
	}

	/* --- Появление блоков ------------------------------------------------- */
	function initReveal() {
		var nodes = Array.prototype.slice.call(document.querySelectorAll('.reveal'));
		if (!nodes.length) {
			return;
		}

		if (reduced.matches) {
			nodes.forEach(function (node) {
				node.classList.add('is-in');
			});
			return;
		}

		var ticking = false;

		/* Проверяем положение сами, а не через IntersectionObserver: при прыжке
		   по якорю блок может проскочить порог наблюдателя и остаться невидимым. */
		var paint = function () {
			ticking = false;
			var limit = window.innerHeight * 0.92;

			nodes = nodes.filter(function (node) {
				if (node.getBoundingClientRect().top < limit) {
					node.classList.add('is-in');

					return false;
				}

				return true;
			});

			if (!nodes.length) {
				window.removeEventListener('scroll', onScroll);
				window.removeEventListener('resize', onScroll);
			}
		};

		var onScroll = function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(paint);
			}
		};

		paint();
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
	}

	/* --- Параллакс плавающих элементов ------------------------------------ */
	function initParallax() {
		var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
		if (!nodes.length || reduced.matches) {
			return;
		}

		var ticking = false;

		var paint = function () {
			ticking = false;
			var viewport = window.innerHeight;

			nodes.forEach(function (node) {
				var rect = node.getBoundingClientRect();
				if (rect.bottom < -240 || rect.top > viewport + 240) {
					return;
				}
				var speed = parseFloat(node.getAttribute('data-parallax')) || 0.1;
				var center = rect.top + rect.height / 2 - viewport / 2;
				node.style.transform = 'translate3d(0,' + (-center * speed).toFixed(1) + 'px,0)';
			});
		};

		var onScroll = function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(paint);
			}
		};

		paint();
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
	}

	/* --- Карусели --------------------------------------------------------- */
	function initCarousels() {
		var carousels = document.querySelectorAll('[data-carousel]');

		Array.prototype.forEach.call(carousels, function (root) {
			var track = root.querySelector('[data-carousel-track]');
			var prev = root.querySelector('[data-carousel-prev]');
			var next = root.querySelector('[data-carousel-next]');
			if (!track) {
				return;
			}

			var step = function () {
				var card = track.firstElementChild;
				return card ? card.getBoundingClientRect().width + 24 : 300;
			};

			var sync = function () {
				var max = track.scrollWidth - track.clientWidth - 2;
				if (prev) {
					prev.disabled = track.scrollLeft <= 2;
				}
				if (next) {
					next.disabled = track.scrollLeft >= max;
				}
			};

			if (prev) {
				prev.addEventListener('click', function () {
					track.scrollBy({ left: -step(), behavior: reduced.matches ? 'auto' : 'smooth' });
				});
			}
			if (next) {
				next.addEventListener('click', function () {
					track.scrollBy({ left: step(), behavior: reduced.matches ? 'auto' : 'smooth' });
				});
			}

			track.addEventListener('scroll', sync, { passive: true });
			window.addEventListener('resize', sync);
			sync();
		});
	}

	ready(function () {
		initHeader();
		initBurger();
		initSplitHeadings();
		initReveal();
		initParallax();
		initCarousels();
	});
})();
