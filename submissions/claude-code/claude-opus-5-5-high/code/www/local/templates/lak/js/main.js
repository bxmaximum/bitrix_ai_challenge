/* Лак&Точка: поведение лендинга. Ванильный JS, без зависимостей. */
(function () {
	'use strict';

	var doc = document.documentElement;
	doc.classList.add('js');

	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function clamp(v, min, max) {
		return Math.min(max, Math.max(min, v));
	}

	ready(function () {
		var header = document.querySelector('[data-header]');

		/* ---------- Шапка: прозрачная над hero, плотная после прокрутки ---------- */
		function updateHeader() {
			if (header) {
				header.classList.toggle('is-solid', window.scrollY > 40);
			}
		}

		/* ---------- Появление блоков при скролле ---------- */
		var revealItems = document.querySelectorAll('[data-reveal]');
		if ('IntersectionObserver' in window && !reduceMotion.matches) {
			var revealObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						revealObserver.unobserve(entry.target);
					}
				});
			}, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });
			revealItems.forEach(function (el) { revealObserver.observe(el); });
		} else {
			revealItems.forEach(function (el) { el.classList.add('is-visible'); });
		}

		/* ---------- Пословное «проявление» абзаца ---------- */
		var wordBlocks = document.querySelectorAll('[data-words]');
		wordBlocks.forEach(function (block) {
			splitWords(block);
		});

		function splitWords(node) {
			Array.prototype.slice.call(node.childNodes).forEach(function (child) {
				if (child.nodeType === 3) {
					var parts = child.textContent.split(/(\s+)/);
					var frag = document.createDocumentFragment();
					parts.forEach(function (part) {
						if (!part) {
							return;
						}
						if (/^\s+$/.test(part)) {
							frag.appendChild(document.createTextNode(part));
						} else {
							var span = document.createElement('span');
							span.className = 'word';
							span.textContent = part;
							frag.appendChild(span);
						}
					});
					child.parentNode.replaceChild(frag, child);
				} else if (child.nodeType === 1) {
					splitWords(child);
				}
			});
		}

		/* ---------- Связанные со скроллом эффекты ---------- */
		var parallaxItems = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
		var slideItems = Array.prototype.slice.call(document.querySelectorAll('[data-slide]'));
		var hscroll = document.querySelector('[data-hscroll]');
		var hscrollTrack = hscroll ? hscroll.querySelector('[data-hscroll-track]') : null;
		var wideQuery = window.matchMedia('(min-width: 900px)');
		var ticking = false;

		function setupHscroll() {
			if (!hscroll || !hscrollTrack) {
				return;
			}
			var pinned = wideQuery.matches && !reduceMotion.matches;
			hscroll.classList.toggle('is-pinned', pinned);
			if (pinned) {
				var distance = Math.max(0, hscrollTrack.scrollWidth - window.innerWidth);
				hscroll.style.height = (window.innerHeight + distance) + 'px';
				hscrollTrack.removeAttribute('tabindex');
			} else {
				hscroll.style.height = '';
				hscrollTrack.style.transform = '';
				hscrollTrack.setAttribute('tabindex', '0');
			}
		}

		function onScroll() {
			updateHeader();
			if (reduceMotion.matches) {
				return;
			}
			var vh = window.innerHeight;

			parallaxItems.forEach(function (el) {
				var rect = el.parentElement.getBoundingClientRect();
				if (rect.bottom < -200 || rect.top > vh + 200) {
					return;
				}
				var speed = parseFloat(el.getAttribute('data-parallax')) || 0;
				var center = rect.top + rect.height / 2 - vh / 2;
				el.style.transform = 'translate3d(0,' + (center * -speed).toFixed(1) + 'px,0)';
			});

			slideItems.forEach(function (el) {
				var rect = el.getBoundingClientRect();
				// 0 — строка у нижнего края экрана, 1 — в верхней трети: строки «съезжаются» к композиции.
				var progress = clamp((vh - rect.top) / (vh * 0.75), 0, 1);
				var shift = parseFloat(el.getAttribute('data-slide')) || 0;
				el.style.transform = 'translate3d(' + (shift * (1 - progress)).toFixed(2) + 'vw,0,0)';
			});

			wordBlocks.forEach(function (block) {
				var words = block.querySelectorAll('.word');
				var rect = block.getBoundingClientRect();
				var progress = clamp((vh * 0.85 - rect.top) / (rect.height + vh * 0.35), 0, 1);
				var lit = Math.round(progress * words.length);
				for (var i = 0; i < words.length; i++) {
					words[i].classList.toggle('is-on', i < lit);
				}
			});

			if (hscroll && hscroll.classList.contains('is-pinned')) {
				var r = hscroll.getBoundingClientRect();
				var total = hscroll.offsetHeight - vh;
				var p = total > 0 ? clamp(-r.top / total, 0, 1) : 0;
				var distance = Math.max(0, hscrollTrack.scrollWidth - window.innerWidth);
				hscrollTrack.style.transform = 'translate3d(' + (-distance * p).toFixed(1) + 'px,0,0)';
			}
		}

		function requestTick() {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(function () {
					ticking = false;
					onScroll();
				});
			}
		}

		function resetMotion() {
			if (reduceMotion.matches) {
				parallaxItems.concat(slideItems).forEach(function (el) { el.style.transform = ''; });
				wordBlocks.forEach(function (b) {
					b.querySelectorAll('.word').forEach(function (w) { w.classList.add('is-on'); });
				});
			}
			setupHscroll();
			onScroll();
		}

		window.addEventListener('scroll', requestTick, { passive: true });
		window.addEventListener('resize', function () { setupHscroll(); requestTick(); });
		window.addEventListener('load', function () { setupHscroll(); requestTick(); });
		if (reduceMotion.addEventListener) {
			reduceMotion.addEventListener('change', resetMotion);
			wideQuery.addEventListener('change', resetMotion);
		}
		resetMotion();

		/* ---------- Меню: модальный диалог ---------- */
		var menu = document.querySelector('[data-menu]');
		var openBtn = document.querySelector('[data-menu-open]');
		var closeBtn = menu ? menu.querySelector('[data-menu-close]') : null;
		var lastFocus = null;

		function focusables(root) {
			return Array.prototype.slice.call(root.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'))
				.filter(function (el) { return el.offsetParent !== null; });
		}

		function openMenu() {
			lastFocus = document.activeElement;
			menu.hidden = false;
			document.body.classList.add('is-menu-open');
			openBtn.setAttribute('aria-expanded', 'true');
			// Принудительный reflow, чтобы переход opacity сработал после снятия hidden.
			void menu.offsetWidth;
			menu.classList.add('is-open');
			closeBtn.focus();
		}

		function closeMenu(restoreFocus) {
			menu.classList.remove('is-open');
			document.body.classList.remove('is-menu-open');
			openBtn.setAttribute('aria-expanded', 'false');
			var hide = function () { menu.hidden = true; };
			if (reduceMotion.matches) {
				hide();
			} else {
				window.setTimeout(hide, 450);
			}
			if (restoreFocus !== false && lastFocus) {
				lastFocus.focus();
			}
		}

		if (menu && openBtn && closeBtn) {
			openBtn.addEventListener('click', openMenu);
			closeBtn.addEventListener('click', function () { closeMenu(true); });
			menu.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					e.preventDefault();
					closeMenu(true);
				} else if (e.key === 'Tab') {
					var items = focusables(menu);
					var first = items[0];
					var last = items[items.length - 1];
					if (e.shiftKey && document.activeElement === first) {
						e.preventDefault();
						last.focus();
					} else if (!e.shiftKey && document.activeElement === last) {
						e.preventDefault();
						first.focus();
					}
				}
			});
			menu.querySelectorAll('[data-menu-link]').forEach(function (link) {
				link.addEventListener('click', function () { closeMenu(false); });
			});
		}

		/* ---------- Мастера: вкладки, переключение по наведению и клавишам ---------- */
		document.querySelectorAll('[data-tabs]').forEach(function (root) {
			var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));

			function activate(tab, focus) {
				tabs.forEach(function (t) {
					var on = t === tab;
					t.setAttribute('aria-selected', on ? 'true' : 'false');
					t.tabIndex = on ? 0 : -1;
					t.classList.toggle('is-active', on);
					var panel = document.getElementById(t.getAttribute('aria-controls'));
					if (panel) {
						panel.hidden = !on;
					}
				});
				if (focus) {
					tab.focus();
				}
			}

			tabs.forEach(function (tab, index) {
				tab.addEventListener('click', function () { activate(tab, false); });
				tab.addEventListener('mouseenter', function () {
					if (window.matchMedia('(hover: hover)').matches && tab.getAttribute('aria-selected') !== 'true') {
						activate(tab, false);
					}
				});
				tab.addEventListener('keydown', function (e) {
					var next = null;
					if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
						next = tabs[(index + 1) % tabs.length];
					} else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
						next = tabs[(index - 1 + tabs.length) % tabs.length];
					} else if (e.key === 'Home') {
						next = tabs[0];
					} else if (e.key === 'End') {
						next = tabs[tabs.length - 1];
					}
					if (next) {
						e.preventDefault();
						activate(next, true);
					}
				});
			});
		});

		/* ---------- Переход к форме записи с предвыбором мастера ---------- */
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('[data-book-master]');
			if (!trigger) {
				return;
			}
			var input = document.querySelector('[data-booking-form] input[name="masterId"][value="' + trigger.getAttribute('data-book-master') + '"]');
			if (input && !input.disabled) {
				input.checked = true;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});
	});
})();
