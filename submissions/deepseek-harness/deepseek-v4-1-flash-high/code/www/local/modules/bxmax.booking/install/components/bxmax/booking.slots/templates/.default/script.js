/* =========================================================================
   Виджет онлайн-записи bxmax.booking — ванильный JS без библиотек.
   Общается с модулем через /bitrix/services/main/ajax.php.
   ========================================================================= */

(function () {
	'use strict';

	var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function pad(value) {
		return (value < 10 ? '0' : '') + value;
	}

	function parseYmd(value) {
		var parts = String(value).split('-');

		return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12, 0, 0);
	}

	function ymd(date) {
		return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
	}

	function addDays(date, amount) {
		var copy = new Date(date.getTime());
		copy.setDate(copy.getDate() + amount);

		return copy;
	}

	function initWidget(root) {
		var config = {};
		try {
			config = JSON.parse(root.dataset.config || '{}');
		} catch (error) {
			config = {};
		}

		var errors = config.errors || {};
		var months = config.months || [];
		var weekdays = config.weekdays || [];

		var form = root.querySelector('[data-bw-form]');
		var calendar = root.querySelector('[data-bw-calendar]');
		var weekLabel = root.querySelector('[data-bw-week-label]');
		var weekPrev = root.querySelector('[data-bw-week-prev]');
		var weekNext = root.querySelector('[data-bw-week-next]');
		var message = root.querySelector('[data-bw-message]');
		var live = root.querySelector('[data-bw-live]');
		var submit = root.querySelector('[data-bw-submit]');
		var successBox = root.querySelector('[data-bw-success]');
		var summaryBox = root.querySelector('[data-bw-summary]');
		var againButton = root.querySelector('[data-bw-again]');
		var nameInput = root.querySelector('[data-bw-name]');
		var phoneInput = root.querySelector('[data-bw-phone]');
		var consentInput = root.querySelector('[data-bw-consent]');

		if (!form || !calendar) {
			return;
		}

		var submitLabel = submit ? submit.textContent : '';
		var baseWeek = parseYmd(root.dataset.weekStart);
		var weeksAhead = parseInt(root.dataset.weeks, 10) || 1;

		var state = {
			masterId: radioValue('masterId'),
			serviceId: radioValue('serviceId'),
			weekOffset: 0,
			selected: null,
			slots: [],
			requestId: 0
		};

		function radioValue(name) {
			var input = form.querySelector('input[name="' + name + '"]:checked')
				|| form.querySelector('input[name="' + name + '"]');

			return input ? Number(input.value) : 0;
		}

		function currentWeekStart() {
			return addDays(baseWeek, state.weekOffset * 7);
		}

		function dayLabel(isoDate, short) {
			var date = parseYmd(isoDate);
			var weekday = weekdays[date.getDay()] || '';

			if (short) {
				return weekday + ', ' + date.getDate() + ' ' + (months[date.getMonth()] || '');
			}

			return date.getDate() + ' ' + (months[date.getMonth()] || '');
		}

		function setMessage(text, state_) {
			if (!message) {
				return;
			}

			message.textContent = text || '';
			if (state_) {
				message.setAttribute('data-state', state_);
			} else {
				message.removeAttribute('data-state');
			}
		}

		function announce(text) {
			if (live) {
				live.textContent = text;
			}
		}

		function markInvalid(input, invalid) {
			if (!input) {
				return;
			}

			if (invalid) {
				input.setAttribute('aria-invalid', 'true');
			} else {
				input.removeAttribute('aria-invalid');
			}
		}

		/* ------------------------------------------------------------ сетка слотов */

		function options() {
			return Array.prototype.slice.call(calendar.querySelectorAll('.bw__slot'));
		}

		function syncRovingTabindex(preferredIndex) {
			var list = options();
			if (!list.length) {
				return;
			}

			var index = -1;
			list.forEach(function (button, position) {
				if (String(state.selected) === button.dataset.slotId) {
					index = position;
				}
			});

			if (index < 0) {
				index = typeof preferredIndex === 'number' ? preferredIndex : 0;
			}

			index = Math.max(0, Math.min(list.length - 1, index));
			list.forEach(function (button, position) {
				button.tabIndex = position === index ? 0 : -1;
			});
		}

		function buildSlot(slot) {
			var taken = slot.status === 'taken';
			var button = document.createElement('button');

			button.type = 'button';
			button.className = 'bw__slot';
			button.setAttribute('role', 'option');
			button.dataset.slotId = String(slot.id);
			button.dataset.startsAt = slot.startsAt;
			button.dataset.endsAt = slot.endsAt;
			button.setAttribute('aria-selected', String(state.selected === slot.id));
			button.tabIndex = -1;
			button.textContent = slot.startsAt.slice(11, 16);

			if (taken) {
				button.setAttribute('aria-disabled', 'true');
				var note = document.createElement('span');
				note.className = 'visually-hidden';
				note.textContent = ' — занято';
				button.appendChild(note);
			}

			button.addEventListener('click', function () {
				if (taken) {
					announce('Это время занято');

					return;
				}

				select(slot.id);
			});

			return button;
		}

		function render() {
			calendar.innerHTML = '';

			if (!state.slots.length) {
				calendar.setAttribute('aria-busy', 'false');
				var empty = document.createElement('p');
				empty.className = 'bw__empty';
				empty.textContent = 'На этой неделе свободных окон нет. Посмотрите следующую неделю.';
				calendar.appendChild(empty);

				return;
			}

			var byDay = {};
			state.slots.forEach(function (slot) {
				var day = slot.startsAt.slice(0, 10);
				if (!byDay[day]) {
					byDay[day] = [];
				}
				byDay[day].push(slot);
			});

			Object.keys(byDay).sort().forEach(function (day) {
				var group = document.createElement('div');
				group.className = 'bw__day';
				group.setAttribute('role', 'group');
				group.setAttribute('aria-label', dayLabel(day, true));

				var label = document.createElement('p');
				label.className = 'bw__day-label';
				label.setAttribute('aria-hidden', 'true');
				label.textContent = dayLabel(day, false);
				group.appendChild(label);

				var list = document.createElement('div');
				list.className = 'bw__slots';
				byDay[day].forEach(function (slot) {
					list.appendChild(buildSlot(slot));
				});

				group.appendChild(list);
				calendar.appendChild(group);
			});

			calendar.setAttribute('aria-busy', 'false');
			syncRovingTabindex();
		}

		function select(slotId) {
			state.selected = slotId;
			options().forEach(function (button) {
				button.setAttribute('aria-selected', String(Number(button.dataset.slotId) === slotId));
			});
			syncRovingTabindex();

			var slot = state.slots.filter(function (item) {
				return item.id === slotId;
			})[0];

			if (slot) {
				announce('Выбрано ' + dayLabel(slot.startsAt.slice(0, 10), true) + ', ' + slot.startsAt.slice(11, 16));
			}

			setMessage('');
		}

		/* --------------------------------------------------------------- загрузка */

		function rangeLabel(start) {
			var end = addDays(start, 6);
			var fromMonth = months[start.getMonth()] || '';
			var toMonth = months[end.getMonth()] || '';

			if (fromMonth === toMonth) {
				return start.getDate() + ' — ' + end.getDate() + ' ' + fromMonth;
			}

			return start.getDate() + ' ' + fromMonth + ' — ' + end.getDate() + ' ' + toMonth;
		}

		function loadSlots() {
			state.requestId += 1;
			var requestId = state.requestId;

			calendar.setAttribute('aria-busy', 'true');
			weekLabel.textContent = rangeLabel(currentWeekStart());
			weekPrev.disabled = state.weekOffset <= 0;
			weekNext.disabled = state.weekOffset >= weeksAhead - 1;

			var body = new FormData();
			body.append('sessid', root.dataset.sessid);
			body.append('masterId', state.masterId);
			body.append('weekStart', ymd(currentWeekStart()));

			return fetch(root.dataset.ajaxUrl + '?action=' + encodeURIComponent(root.dataset.actionSlots), {
				method: 'POST',
				body: body,
				credentials: 'same-origin'
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (requestId !== state.requestId) {
						return;
					}

					if (payload.status !== 'success') {
						var code = payload.errors && payload.errors[0] ? payload.errors[0].code : '';
						setMessage(errors[code] || errors.NETWORK || 'Не удалось загрузить расписание.');

						return;
					}

					state.slots = (payload.data && payload.data.slots) || [];

					var stillThere = state.slots.some(function (slot) {
						return slot.id === state.selected && slot.status === 'free';
					});

					if (!stillThere) {
						state.selected = null;
					}

					render();
				})
				.catch(function () {
					if (requestId === state.requestId) {
						setMessage(errors.NETWORK || 'Не удалось загрузить расписание.');
						calendar.setAttribute('aria-busy', 'false');
					}
				});
		}

		/* ------------------------------------------------------------ отправка */

		function summarize(bookingId) {
			var serviceChip = form.querySelector('input[name="serviceId"]:checked');
			var masterChip = form.querySelector('input[name="masterId"]:checked');
			var slot = state.slots.filter(function (item) {
				return item.id === state.selected;
			})[0];

			var rows = [];
			if (serviceChip) {
				rows.push(['Услуга', serviceChip.parentNode.querySelector('.bw-chip__name').textContent]);
			}
			if (masterChip) {
				rows.push(['Мастер', masterChip.parentNode.querySelector('.bw-chip__name').textContent]);
			}
			if (slot) {
				rows.push(['Когда', dayLabel(slot.startsAt.slice(0, 10), true) + ', ' + slot.startsAt.slice(11, 16) + ' — ' + slot.endsAt.slice(11, 16)]);
			}
			rows.push(['Номер заявки', '№ ' + bookingId]);

			summaryBox.innerHTML = '';
			rows.forEach(function (row) {
				var wrapper = document.createElement('div');
				var term = document.createElement('dt');
				var value = document.createElement('dd');
				term.textContent = row[0];
				value.textContent = row[1];
				wrapper.appendChild(term);
				wrapper.appendChild(value);
				summaryBox.appendChild(wrapper);
			});
		}

		function showSuccess(bookingId) {
			summarize(bookingId);
			form.hidden = true;
			successBox.hidden = false;
			successBox.focus({ preventScroll: true });
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			setMessage('');
			markInvalid(nameInput, false);
			markInvalid(phoneInput, false);

			if (!state.selected) {
				setMessage('Выберите свободное время в расписании.');
				var first = options()[0];
				if (first) {
					first.focus();
				}

				return;
			}

			var name = nameInput ? nameInput.value.trim() : '';
			var phone = phoneInput ? phoneInput.value.trim() : '';

			if (name.length < 2) {
				markInvalid(nameInput, true);
				setMessage('Укажите имя — как к вам обращаться.');
				if (nameInput) {
					nameInput.focus();
				}

				return;
			}

			if (phone.replace(/\D/g, '').length < 10) {
				markInvalid(phoneInput, true);
				setMessage('Укажите телефон в формате +7 999 123-45-67.');
				if (phoneInput) {
					phoneInput.focus();
				}

				return;
			}

			if (!consentInput || !consentInput.checked) {
				setMessage(errors.CONSENT_REQUIRED || 'Отметьте согласие на обработку данных.');
				if (consentInput) {
					consentInput.focus();
				}

				return;
			}

			submit.disabled = true;
			submit.textContent = 'Отправляем…';

			var body = new FormData();
			body.append('sessid', root.dataset.sessid);
			body.append('slotId', state.selected);
			body.append('serviceId', state.serviceId);
			body.append('name', name);
			body.append('phone', phone);
			body.append('consent', 'Y');

			fetch(root.dataset.ajaxUrl + '?action=' + encodeURIComponent(root.dataset.actionBook), {
				method: 'POST',
				body: body,
				credentials: 'same-origin'
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					if (payload.status === 'success' && payload.data && payload.data.bookingId) {
						showSuccess(payload.data.bookingId);

						return;
					}

					var code = payload.errors && payload.errors[0] ? payload.errors[0].code : 'VALIDATION';
					setMessage(errors[code] || errors.VALIDATION || 'Не удалось отправить заявку.');

					if (code === 'SLOT_TAKEN' || code === 'SLOT_NOT_FOUND') {
						state.selected = null;
						loadSlots();
					}
				})
				.catch(function () {
					setMessage(errors.NETWORK || 'Не удалось отправить заявку.');
				})
				.then(function () {
					submit.disabled = false;
					submit.textContent = submitLabel;
				});
		});

		/* ------------------------------------------------------------- навигация */

		calendar.addEventListener('keydown', function (event) {
			var list = options();
			if (!list.length) {
				return;
			}

			var current = list.indexOf(document.activeElement);
			var target = null;

			switch (event.key) {
				case 'ArrowRight':
				case 'ArrowDown':
					target = current + 1;
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					target = current - 1;
					break;
				case 'Home':
					target = 0;
					break;
				case 'End':
					target = list.length - 1;
					break;
				case 'Enter':
				case ' ':
					if (current >= 0) {
						event.preventDefault();
						list[current].click();
					}

					return;
				default:
					return;
			}

			event.preventDefault();
			target = Math.max(0, Math.min(list.length - 1, target));
			list.forEach(function (button, position) {
				button.tabIndex = position === target ? 0 : -1;
			});
			list[target].focus();
		});

		weekPrev.addEventListener('click', function () {
			if (state.weekOffset > 0) {
				state.weekOffset -= 1;
				loadSlots();
			}
		});

		weekNext.addEventListener('click', function () {
			if (state.weekOffset < weeksAhead - 1) {
				state.weekOffset += 1;
				loadSlots();
			}
		});

		Array.prototype.slice.call(form.querySelectorAll('input[name="masterId"]')).forEach(function (input) {
			input.addEventListener('change', function () {
				state.masterId = Number(input.value);
				state.selected = null;
				loadSlots();
			});
		});

		Array.prototype.slice.call(form.querySelectorAll('input[name="serviceId"]')).forEach(function (input) {
			input.addEventListener('change', function () {
				state.serviceId = Number(input.value);
			});
		});

		if (againButton) {
			againButton.addEventListener('click', function () {
				successBox.hidden = true;
				form.hidden = false;
				state.selected = null;
				if (consentInput) {
					consentInput.checked = false;
				}
				loadSlots();
				if (nameInput) {
					nameInput.focus();
				}
			});
		}

		document.addEventListener('lt:preselect', function (event) {
			var detail = event.detail || {};

			if (detail.serviceId) {
				var serviceInput = form.querySelector('input[name="serviceId"][value="' + detail.serviceId + '"]');
				if (serviceInput) {
					serviceInput.checked = true;
					state.serviceId = Number(detail.serviceId);
				}
			}

			if (detail.masterId) {
				var masterInput = form.querySelector('input[name="masterId"][value="' + detail.masterId + '"]');
				if (masterInput) {
					masterInput.checked = true;
					state.masterId = Number(detail.masterId);
					state.selected = null;
					loadSlots();
				}
			}

			setMessage('');
		});

		/* ----------------------------------------------------------------- старт */

		if (!state.masterId) {
			weekLabel.textContent = 'Мастера пока не добавлены';
			calendar.setAttribute('aria-busy', 'false');

			return;
		}

		loadSlots();

		// плавная прокрутка выбранного слота в поле зрения при фокусе
		calendar.addEventListener('focusin', function (event) {
			if (event.target.classList.contains('bw__slot') && !reduced) {
				event.target.scrollIntoView({ block: 'nearest', inline: 'nearest' });
			}
		});
	}

	function boot() {
		Array.prototype.slice.call(document.querySelectorAll('[data-booking-widget]')).forEach(initWidget);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
