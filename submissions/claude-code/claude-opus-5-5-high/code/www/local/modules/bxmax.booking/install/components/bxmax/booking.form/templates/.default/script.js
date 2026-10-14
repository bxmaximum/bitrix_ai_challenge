/* Форма онлайн-записи bxmax:booking.form. Ванильный JS без зависимостей. */
(function () {
	'use strict';

	var MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
	var MONTHS_SHORT = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
	var DAYS = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
	var DAYS_SHORT = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];

	var TEXT = {
		loading: 'Загружаем расписание…',
		loadError: 'Не удалось загрузить расписание. Проверьте соединение и попробуйте ещё раз.',
		noSlots: 'нет окошек',
		weekEmpty: 'На этой неделе у мастера нет свободного времени — загляните на следующую.',
		slotTaken: 'Это время занято — выберите другое окошко.',
		slotGone: 'Пока вы заполняли форму, это время заняли. Выберите другое — расписание обновлено.',
		chooseService: 'Выберите услугу.',
		chooseMaster: 'Выберите мастера.',
		chooseSlot: 'Выберите дату и время.',
		name: 'Укажите имя.',
		phone: 'Укажите телефон в формате +7 900 000-00-00.',
		consent: 'Без согласия на обработку данных мы не сможем записать вас.',
		checkForm: 'Проверьте форму: ',
		sending: 'Отправляем заявку…',
		network: 'Нет связи с сервером. Попробуйте ещё раз или позвоните нам.',
		success: 'Вы записаны',
		selected: 'Выбрано: ',
		free: 'свободно',
		taken: 'занято'
	};

	function pad(n) {
		return (n < 10 ? '0' : '') + n;
	}

	function parseYmd(value) {
		var p = value.split('-');
		return new Date(+p[0], +p[1] - 1, +p[2]);
	}

	function toYmd(date) {
		return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
	}

	function addDays(ymd, days) {
		var d = parseYmd(ymd);
		d.setDate(d.getDate() + days);
		return toYmd(d);
	}

	function humanDate(ymd) {
		var d = parseYmd(ymd);
		return DAYS[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()];
	}

	function initForm(root) {
		var config;
		try {
			config = JSON.parse(root.getAttribute('data-config'));
		} catch (e) {
			return;
		}

		var form = root.querySelector('form');
		if (!form) {
			return;
		}

		var listbox = root.querySelector('[data-slots]');
		var placeholder = root.querySelector('[data-slots-placeholder]');
		var weekRange = root.querySelector('[data-week-range]');
		var weekButtons = root.querySelectorAll('[data-week]');
		var statusEl = root.querySelector('[data-status]');
		var summaryEl = root.querySelector('[data-summary]');
		var successEl = root.querySelector('[data-success]');
		var submitBtn = form.querySelector('button[type="submit"]');
		var slotInput = form.elements.slotId;

		var state = {
			weekStart: config.weekMin,
			slots: [],
			slotId: null,
			requestId: 0,
			sending: false
		};

		var servicesById = {};
		config.services.forEach(function (s) { servicesById[s.id] = s; });
		var mastersById = {};
		config.masters.forEach(function (m) { mastersById[m.id] = m; });

		function value(name) {
			var el = form.elements[name];
			if (!el) {
				return '';
			}
			if (el.length !== undefined && !el.tagName) {
				for (var i = 0; i < el.length; i++) {
					if (el[i].checked) {
						return el[i].value;
					}
				}
				return '';
			}
			if (el.type === 'checkbox') {
				return el.checked ? el.value : '';
			}
			return el.value;
		}

		function setStatus(text, kind) {
			statusEl.textContent = text || '';
			statusEl.className = 'bf__status' + (kind ? ' bf__status--' + kind : '');
		}

		function fieldError(field, message) {
			var box = root.querySelector('[data-error-for="' + field + '"]');
			var inputs = form.querySelectorAll('[name="' + field + '"]');
			if (field === 'slotId') {
				inputs = [listbox];
			}
			for (var i = 0; i < inputs.length; i++) {
				if (message) {
					inputs[i].setAttribute('aria-invalid', 'true');
				} else {
					inputs[i].removeAttribute('aria-invalid');
				}
			}
			if (box) {
				box.textContent = message || '';
				box.hidden = !message;
			}
		}

		function clearErrors() {
			['serviceId', 'slotId', 'name', 'phone', 'consent'].forEach(function (f) { fieldError(f, ''); });
		}

		/* ---------- услуги и мастера ---------- */

		function syncMasters() {
			var serviceId = parseInt(value('serviceId'), 10) || 0;
			var masterLabels = root.querySelectorAll('.bf-master');
			for (var i = 0; i < masterLabels.length; i++) {
				var label = masterLabels[i];
				var input = label.querySelector('input');
				var ids = (label.getAttribute('data-master-services') || '').split(',').filter(Boolean).map(Number);
				var can = !serviceId || ids.length === 0 || ids.indexOf(serviceId) !== -1;
				input.disabled = !can;
				label.classList.toggle('is-disabled', !can);
				label.querySelector('.bf-master__na').hidden = can;
				if (!can && input.checked) {
					input.checked = false;
					resetSlots();
				}
			}
		}

		function updateSummary() {
			var parts = [];
			var service = servicesById[value('serviceId')];
			var master = mastersById[value('masterId')];
			var slot = findSlot(state.slotId);
			if (service) {
				parts.push(service.name);
			}
			if (master) {
				parts.push(master.name);
			}
			if (slot) {
				parts.push(humanDate(slot.startsAt.slice(0, 10)) + ', ' + slot.startsAt.slice(11, 16));
			}
			summaryEl.textContent = parts.length ? TEXT.selected + parts.join(' · ') : '';
		}

		/* ---------- неделя и слоты ---------- */

		function findSlot(id) {
			for (var i = 0; i < state.slots.length; i++) {
				if (state.slots[i].id === id) {
					return state.slots[i];
				}
			}
			return null;
		}

		function updateWeekNav() {
			var end = addDays(state.weekStart, 6);
			var a = parseYmd(state.weekStart);
			var b = parseYmd(end);
			weekRange.textContent = a.getDate() + (a.getMonth() !== b.getMonth() ? ' ' + MONTHS[a.getMonth()] : '') + ' — ' + b.getDate() + ' ' + MONTHS[b.getMonth()];
			weekButtons.forEach(function (btn) {
				var dir = +btn.getAttribute('data-week');
				var disabled = dir < 0 ? state.weekStart <= config.weekMin : state.weekStart >= config.weekMax;
				btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
			});
		}

		function resetSlots() {
			state.slots = [];
			setSelected(null);
			listbox.innerHTML = '';
			listbox.hidden = true;
			placeholder.hidden = false;
		}

		function loadSlots(focusAfter) {
			var masterId = value('masterId');
			updateWeekNav();
			if (!masterId) {
				resetSlots();
				return Promise.resolve();
			}
			var requestId = ++state.requestId;
			listbox.setAttribute('aria-busy', 'true');
			placeholder.hidden = false;
			placeholder.textContent = TEXT.loading;

			var url = config.apiSlots + '&masterId=' + encodeURIComponent(masterId) + '&weekStart=' + encodeURIComponent(state.weekStart);
			return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
				.then(function (r) { return r.json(); })
				.then(function (json) {
					if (requestId !== state.requestId) {
						return;
					}
					listbox.removeAttribute('aria-busy');
					if (json.status !== 'success') {
						throw new Error((json.errors && json.errors[0] && json.errors[0].message) || TEXT.loadError);
					}
					state.slots = json.data.slots || [];
					if (state.slotId && !isFree(findSlot(state.slotId))) {
						setSelected(null);
					}
					renderSlots(focusAfter);
				})
				.catch(function (err) {
					if (requestId !== state.requestId) {
						return;
					}
					listbox.removeAttribute('aria-busy');
					listbox.hidden = true;
					placeholder.hidden = false;
					placeholder.textContent = err && err.message ? err.message : TEXT.loadError;
				});
		}

		function isFree(slot) {
			return !!slot && slot.status === 'free';
		}

		function renderSlots(focusAfter) {
			var byDay = {};
			state.slots.forEach(function (s) {
				var day = s.startsAt.slice(0, 10);
				(byDay[day] = byDay[day] || []).push(s);
			});

			var html = '';
			var baseId = form.getAttribute('aria-describedby').replace('-status', '');
			var anyFree = false;
			for (var i = 0; i < 7; i++) {
				var ymd = addDays(state.weekStart, i);
				var d = parseYmd(ymd);
				var dayId = baseId + '-d' + i;
				var daySlots = byDay[ymd] || [];
				html += '<div class="bf-day" role="group" aria-labelledby="' + dayId + '">';
				html += '<p class="bf-day__label" id="' + dayId + '" role="presentation">' +
					'<span class="bf-day__dow">' + DAYS_SHORT[d.getDay()] + '</span> ' +
					'<span class="bf-day__date">' + d.getDate() + ' ' + MONTHS_SHORT[d.getMonth()] + '</span>' +
					'<span class="visually-hidden">, ' + DAYS[d.getDay()] + ', ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + '</span></p>';
				html += '<div class="bf-day__slots" role="presentation">';
				if (!daySlots.length) {
					html += '<span class="bf-day__none" role="presentation">' + TEXT.noSlots + '</span>';
				}
				daySlots.forEach(function (s) {
					var free = isFree(s);
					anyFree = anyFree || free;
					var time = s.startsAt.slice(11, 16);
					var selected = s.id === state.slotId;
					html += '<div class="bf-slot' + (free ? '' : ' is-taken') + (selected ? ' is-selected' : '') + '"' +
						' role="option" id="' + baseId + '-s' + s.id + '"' +
						' data-slot-id="' + s.id + '" data-day="' + i + '"' +
						' aria-selected="' + (selected ? 'true' : 'false') + '"' +
						(free ? ' aria-disabled="false"' : ' aria-disabled="true"') +
						' aria-label="' + time + ', ' + (free ? TEXT.free : TEXT.taken) + '"' +
						' tabindex="-1">' + time + '</div>';
				});
				html += '</div></div>';
			}

			listbox.innerHTML = html;
			listbox.hidden = false;
			placeholder.hidden = anyFree;
			placeholder.textContent = anyFree ? '' : TEXT.weekEmpty;
			updateRoving();
			if (focusAfter) {
				var target = listbox.querySelector('[tabindex="0"]');
				if (target) {
					target.focus();
				}
			}
		}

		function options() {
			return Array.prototype.slice.call(listbox.querySelectorAll('[role="option"]'));
		}

		function updateRoving(active) {
			var opts = options();
			if (!opts.length) {
				return;
			}
			var current = active || listbox.querySelector('[aria-selected="true"]') ||
				listbox.querySelector('[role="option"][aria-disabled="false"]') || opts[0];
			opts.forEach(function (o) { o.tabIndex = o === current ? 0 : -1; });
		}

		function setSelected(id) {
			state.slotId = id;
			slotInput.value = id ? String(id) : '';
			options().forEach(function (o) {
				var on = +o.getAttribute('data-slot-id') === id;
				o.setAttribute('aria-selected', on ? 'true' : 'false');
				o.classList.toggle('is-selected', on);
			});
			if (id) {
				fieldError('slotId', '');
			}
			updateSummary();
		}

		function choose(option) {
			if (!option) {
				return;
			}
			if (option.getAttribute('aria-disabled') === 'true') {
				setStatus(TEXT.slotTaken);
				return;
			}
			setStatus('');
			setSelected(+option.getAttribute('data-slot-id'));
			updateRoving(option);
		}

		function moveFocus(option) {
			if (!option) {
				return;
			}
			updateRoving(option);
			option.focus();
		}

		listbox.addEventListener('click', function (e) {
			var option = e.target.closest('[role="option"]');
			if (option) {
				choose(option);
				moveFocus(option);
			}
		});

		listbox.addEventListener('keydown', function (e) {
			var opts = options();
			var current = document.activeElement && document.activeElement.closest('[role="option"]');
			var index = opts.indexOf(current);
			if (index === -1) {
				return;
			}
			var next = null;
			switch (e.key) {
				case 'ArrowRight':
				case 'ArrowDown':
					next = opts[Math.min(index + 1, opts.length - 1)];
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					next = opts[Math.max(index - 1, 0)];
					break;
				case 'Home':
					next = opts[0];
					break;
				case 'End':
					next = opts[opts.length - 1];
					break;
				case 'PageDown':
				case 'PageUp':
					var day = +current.getAttribute('data-day');
					var step = e.key === 'PageDown' ? 1 : -1;
					for (var d = day + step; d >= 0 && d < 7 && !next; d += step) {
						next = listbox.querySelector('[role="option"][data-day="' + d + '"]');
					}
					next = next || current;
					break;
				case 'Enter':
				case ' ':
					e.preventDefault();
					choose(current);
					return;
				default:
					return;
			}
			e.preventDefault();
			moveFocus(next);
		});

		weekButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (btn.getAttribute('aria-disabled') === 'true') {
					return;
				}
				state.weekStart = addDays(state.weekStart, 7 * +btn.getAttribute('data-week'));
				loadSlots(false);
			});
		});

		form.addEventListener('change', function (e) {
			var name = e.target.name;
			if (name === 'serviceId') {
				fieldError('serviceId', '');
				syncMasters();
				updateSummary();
			} else if (name === 'masterId') {
				setSelected(null);
				loadSlots(false);
			} else if (name === 'consent' && e.target.checked) {
				fieldError('consent', '');
			}
		});

		form.addEventListener('input', function (e) {
			if (e.target.name === 'name' || e.target.name === 'phone') {
				fieldError(e.target.name, '');
			}
		});

		/* ---------- отправка ---------- */

		function phoneLooksValid(phone) {
			var digits = phone.replace(/\D+/g, '');
			if (/[^\d\s()+\-.]/.test(phone)) {
				return false;
			}
			return (digits.length === 11 && /^[78]/.test(digits)) || (digits.length === 10 && digits[0] === '9') ||
				(phone.trim().charAt(0) === '+' && digits.length >= 10 && digits.length <= 15);
		}

		function validate() {
			var errors = [];
			if (!value('serviceId')) {
				errors.push(['serviceId', TEXT.chooseService]);
			}
			if (!value('masterId')) {
				errors.push(['slotId', TEXT.chooseMaster]);
			} else if (!state.slotId) {
				errors.push(['slotId', TEXT.chooseSlot]);
			}
			if (!value('name').trim()) {
				errors.push(['name', TEXT.name]);
			}
			if (!phoneLooksValid(value('phone'))) {
				errors.push(['phone', TEXT.phone]);
			}
			if (!value('consent')) {
				errors.push(['consent', TEXT.consent]);
			}
			return errors;
		}

		function focusField(field) {
			var target = field === 'slotId'
				? (listbox.hidden ? form.querySelector('[name="masterId"]:not(:disabled)') : listbox.querySelector('[tabindex="0"]'))
				: form.querySelector('[name="' + field + '"]:not(:disabled)');
			if (target) {
				target.focus();
			}
		}

		function showErrors(errors) {
			errors.forEach(function (err) { fieldError(err[0], err[1]); });
			setStatus(TEXT.checkForm + errors.map(function (e) { return e[1]; }).join(' '), 'error');
			focusField(errors[0][0]);
		}

		function send(retried) {
			var body = new FormData();
			body.append('sessid', config.sessid);
			body.append('slotId', slotInput.value);
			body.append('serviceId', value('serviceId'));
			body.append('name', value('name').trim());
			body.append('phone', value('phone').trim());
			body.append('consent', value('consent') ? 'Y' : '');

			return fetch(config.apiCreate, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'Accept': 'application/json', 'X-Bitrix-Csrf-Token': config.sessid }
			}).then(function (r) { return r.json(); }).then(function (json) {
				var csrf = (json.errors || []).filter(function (e) { return e.code === 'invalid_csrf'; })[0];
				if (csrf && !retried && csrf.customData && csrf.customData.csrf) {
					config.sessid = csrf.customData.csrf;
					return send(true);
				}
				return json;
			});
		}

		function setSending(on) {
			state.sending = on;
			submitBtn.setAttribute('aria-disabled', on ? 'true' : 'false');
			form.setAttribute('aria-busy', on ? 'true' : 'false');
			if (on) {
				setStatus(TEXT.sending);
			}
		}

		function handleApiErrors(errors) {
			var fieldErrors = [];
			var slotGone = false;
			errors.forEach(function (err) {
				switch (err.code) {
					case 'CONSENT_REQUIRED':
						fieldErrors.push(['consent', err.message]);
						break;
					case 'VALIDATION':
						fieldErrors.push([(err.customData && err.customData.field) || 'name', err.message]);
						break;
					case 'SLOT_TAKEN':
					case 'SLOT_NOT_FOUND':
						slotGone = true;
						break;
					default:
						fieldErrors.push([null, err.message]);
				}
			});

			if (slotGone) {
				setSelected(null);
				loadSlots(false).then(function () {
					fieldError('slotId', TEXT.slotGone);
					setStatus(TEXT.slotGone, 'error');
					focusField('slotId');
				});
				return;
			}

			var known = fieldErrors.filter(function (e) { return e[0]; });
			if (known.length) {
				showErrors(known);
			} else {
				setStatus(fieldErrors.map(function (e) { return e[1]; }).join(' ') || TEXT.network, 'error');
			}
		}

		function showSuccess(bookingId) {
			var service = servicesById[value('serviceId')];
			var master = mastersById[value('masterId')];
			var slot = findSlot(state.slotId);
			var fields = {
				id: '№' + bookingId,
				service: service ? service.name : '',
				master: master ? master.name : '',
				when: slot ? humanDate(slot.startsAt.slice(0, 10)) + ', ' + slot.startsAt.slice(11, 16) + '–' + slot.endsAt.slice(11, 16) : '',
				name: value('name').trim(),
				phone: value('phone').trim()
			};
			Object.keys(fields).forEach(function (key) {
				var el = successEl.querySelector('[data-success-field="' + key + '"]');
				if (el) {
					el.textContent = fields[key];
				}
			});

			if (slot) {
				slot.status = 'taken';
			}
			setStatus('');
			form.hidden = true;
			successEl.hidden = false;
			successEl.focus();
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (state.sending) {
				return;
			}
			clearErrors();
			var errors = validate();
			if (errors.length) {
				showErrors(errors);
				return;
			}

			setSending(true);
			send(false).then(function (json) {
				setSending(false);
				if (json.status === 'success' && json.data && json.data.bookingId) {
					showSuccess(json.data.bookingId);
				} else {
					handleApiErrors(json.errors || []);
				}
			}).catch(function () {
				setSending(false);
				setStatus(TEXT.network, 'error');
			});
		});

		successEl.querySelector('[data-restart]').addEventListener('click', function () {
			var keepService = value('serviceId');
			var keepMaster = value('masterId');
			form.reset();
			clearErrors();
			setSelected(null);
			successEl.hidden = true;
			form.hidden = false;
			if (keepService) {
				form.querySelector('[name="serviceId"][value="' + keepService + '"]').checked = true;
			}
			if (keepMaster) {
				form.querySelector('[name="masterId"][value="' + keepMaster + '"]').checked = true;
			}
			syncMasters();
			// Обновляем расписание: только что занятое окно должно стать недоступным.
			loadSlots(false).then(function () { focusField('slotId'); });
		});

		/* Кнопки «Записаться» в карточках услуг: предвыбор услуги. */
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('[data-book-service]');
			if (!trigger) {
				return;
			}
			var input = form.querySelector('[name="serviceId"][value="' + trigger.getAttribute('data-book-service') + '"]');
			if (input && !form.hidden) {
				input.checked = true;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}
		});

		syncMasters();
		updateWeekNav();
		updateSummary();
	}

	function boot() {
		var roots = document.querySelectorAll('[data-booking-form]');
		for (var i = 0; i < roots.length; i++) {
			initForm(roots[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
