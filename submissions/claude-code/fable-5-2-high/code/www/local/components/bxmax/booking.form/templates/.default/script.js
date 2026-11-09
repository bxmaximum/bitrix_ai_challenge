/**
 * Форма онлайн-записи bxmax:booking.form.
 * Чистый JS без библиотек: сетка слотов (role="grid"), неделя вперёд, отправка без перезагрузки.
 */
(function () {
	'use strict';

	var WEEKDAYS_SHORT = ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'];
	var WEEKDAYS_FULL = ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'];
	var WEEKDAYS_ACC = ['понедельник', 'вторник', 'среду', 'четверг', 'пятницу', 'субботу', 'воскресенье'];
	var MONTHS_GEN = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

	var TEXT = {
		loading: 'Загружаем расписание…',
		loadError: 'Не удалось загрузить расписание. Проверьте соединение и попробуйте ещё раз.',
		retry: 'Повторить',
		free: 'свободно',
		taken: 'занято',
		none: 'записи нет',
		noChoice: 'Время пока не выбрано.',
		slotRequired: 'Выберите свободное время в расписании.',
		nameInvalid: 'Укажите имя: от 2 до 60 букв.',
		phoneInvalid: 'Укажите телефон в формате +7 900 000-00-00.',
		consentRequired: 'Отметьте согласие на обработку персональных данных — без него мы не можем принять запись.',
		fixErrors: 'Проверьте отмеченные поля — и запись уйдёт.',
		sending: 'Отправляем…',
		networkError: 'Запись не отправилась: нет связи с сайтом. Попробуйте ещё раз или позвоните нам.',
		serverError: 'Что-то пошло не так на нашей стороне. Попробуйте ещё раз или позвоните нам.',
		slotGone: 'Это время только что заняли. Расписание обновлено — выберите другое.',
		successTitle: 'Вы записаны!',
		successNote: 'Если планы изменятся, позвоните нам — перенесём визит.'
	};

	function pad(number) {
		return number < 10 ? '0' + number : String(number);
	}

	// Даты держим строками YYYY-MM-DD и считаем в UTC: время студии не зависит от пояса посетителя.
	function parseDate(value) {
		var parts = value.split('-');

		return new Date(Date.UTC(+parts[0], +parts[1] - 1, +parts[2]));
	}

	function formatDate(date) {
		return date.getUTCFullYear() + '-' + pad(date.getUTCMonth() + 1) + '-' + pad(date.getUTCDate());
	}

	function addDays(value, days) {
		var date = parseDate(value);
		date.setUTCDate(date.getUTCDate() + days);

		return formatDate(date);
	}

	function weekdayIndex(value) {
		return (parseDate(value).getUTCDay() + 6) % 7;
	}

	function dayAndMonth(value) {
		var date = parseDate(value);

		return date.getUTCDate() + ' ' + MONTHS_GEN[date.getUTCMonth()];
	}

	function weekRange(weekStart) {
		var from = parseDate(weekStart);
		var to = parseDate(addDays(weekStart, 6));

		if (from.getUTCMonth() === to.getUTCMonth()) {
			return from.getUTCDate() + '–' + to.getUTCDate() + ' ' + MONTHS_GEN[to.getUTCMonth()];
		}

		return dayAndMonth(weekStart) + ' – ' + dayAndMonth(addDays(weekStart, 6));
	}

	function formatPrice(price) {
		return String(price).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
	}

	function normalizePhone(value) {
		if (/[^\d\s()+\-.]/.test(value)) {
			return null;
		}

		var digits = value.replace(/\D+/g, '');
		if (digits.length === 11 && (digits[0] === '7' || digits[0] === '8')) {
			digits = digits.slice(1);
		}

		return digits.length === 10 && '3489'.indexOf(digits[0]) !== -1 ? '+7' + digits : null;
	}

	function isValidName(value) {
		var name = value.replace(/\s+/g, ' ').trim();

		return name.length >= 2 && name.length <= 60 && /^[A-Za-zА-Яа-яЁёÀ-ɏ][A-Za-zА-Яа-яЁёÀ-ɏ .'’\-]*$/.test(name);
	}

	function initBookingForm(root) {
		var config;
		try {
			config = JSON.parse(root.getAttribute('data-config') || '{}');
		} catch (error) {
			return;
		}

		var form = root.querySelector('[data-role="form"]');
		if (!form || !config.endpoints) {
			return;
		}

		var el = {
			service: root.querySelector('[data-role="service"]'),
			master: root.querySelector('[data-role="master"]'),
			grid: root.querySelector('[data-role="grid"]'),
			gridStatus: root.querySelector('[data-role="grid-status"]'),
			range: root.querySelector('[data-role="range"]'),
			prev: root.querySelector('[data-role="prev"]'),
			next: root.querySelector('[data-role="next"]'),
			choice: root.querySelector('[data-role="choice"]'),
			message: root.querySelector('[data-role="message"]'),
			submit: root.querySelector('[data-role="submit"]'),
			submitText: root.querySelector('[data-role="submit-text"]'),
			success: root.querySelector('[data-role="success"]'),
			successTitle: root.querySelector('[data-role="success-title"]'),
			successList: root.querySelector('[data-role="success-list"]'),
			successNote: root.querySelector('[data-role="success-note"]'),
			again: root.querySelector('[data-role="again"]'),
			name: form.elements.name,
			phone: form.elements.phone,
			consent: form.elements.consent
		};

		var hours = [];
		for (var minutes = config.dayStartHour * 60; minutes + config.slotMinutes <= config.dayEndHour * 60; minutes += config.slotMinutes) {
			hours.push(pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60));
		}

		var state = {
			week: config.firstWeek,
			slots: {},          // 'YYYY-MM-DD HH:MM' -> {id, status}
			selected: null,     // {id, date, time, masterId}
			focus: null,        // {row, col} — позиция клавиатурного фокуса в сетке
			sessid: config.sessid,
			sending: false,
			request: null,
			submitLabel: el.submitText.textContent
		};

		/* ---------- справочники ---------- */

		function findById(list, id) {
			for (var i = 0; i < list.length; i++) {
				if (list[i].id === Number(id)) {
					return list[i];
				}
			}

			return null;
		}

		function mastersFor(serviceId) {
			return config.masters.filter(function (master) {
				return !master.serviceIds.length || master.serviceIds.indexOf(Number(serviceId)) !== -1;
			});
		}

		// Мастера, которые делают выбранную услугу; прежний выбор сохраняем, если он ещё подходит.
		function fillMasters() {
			var current = el.master.value;
			var masters = mastersFor(el.service.value);

			el.master.textContent = '';
			masters.forEach(function (master) {
				var option = document.createElement('option');
				option.value = master.id;
				option.textContent = master.specialization ? master.name + ' — ' + master.specialization.toLowerCase() : master.name;
				el.master.appendChild(option);
			});

			if (masters.some(function (master) { return String(master.id) === current; })) {
				el.master.value = current;
			}
		}

		/* ---------- сообщения и ошибки ---------- */

		function setMessage(text, type) {
			el.message.textContent = text || '';
			el.message.className = 'bk-message' + (text ? ' bk-message--' + (type || 'error') : '');
		}

		function setFieldError(field, text) {
			var box = root.querySelector('[data-error-for="' + field + '"]');
			if (box) {
				box.textContent = text || '';
				box.hidden = !text;
			}

			var control = field === 'slotId' ? el.grid : form.elements[field];
			if (control && control.setAttribute) {
				if (text) {
					control.setAttribute('aria-invalid', 'true');
				} else {
					control.removeAttribute('aria-invalid');
				}
			}
		}

		function clearErrors() {
			['serviceId', 'slotId', 'name', 'phone', 'consent'].forEach(function (field) {
				setFieldError(field, '');
			});
			setMessage('');
		}

		/* ---------- сетка слотов ---------- */

		function cellAt(row, col) {
			return el.grid.querySelector('[data-row="' + row + '"][data-col="' + col + '"]');
		}

		function setRovingCell(cell) {
			var previous = el.grid.querySelector('[role="gridcell"][tabindex="0"]');
			if (previous && previous !== cell) {
				previous.tabIndex = -1;
			}
			if (cell) {
				cell.tabIndex = 0;
				state.focus = {row: +cell.getAttribute('data-row'), col: +cell.getAttribute('data-col')};
			}
		}

		function renderGrid(keepFocus) {
			var fragment = document.createDocumentFragment();
			var freeCount = 0;

			var head = document.createElement('div');
			head.className = 'bk-grid__row bk-grid__row--head';
			head.setAttribute('role', 'row');

			var days = [];
			for (var col = 0; col < 7; col++) {
				var date = addDays(state.week, col);
				days.push(date);

				var header = document.createElement('div');
				header.className = 'bk-grid__day' + (date === config.today ? ' is-today' : '');
				header.setAttribute('role', 'columnheader');
				header.setAttribute('aria-label', WEEKDAYS_FULL[col] + ', ' + dayAndMonth(date));
				header.innerHTML = '<span class="bk-grid__weekday">' + WEEKDAYS_SHORT[col] + '</span>'
					+ '<span class="bk-grid__date">' + parseDate(date).getUTCDate() + '</span>';
				head.appendChild(header);
			}
			fragment.appendChild(head);

			hours.forEach(function (time, row) {
				var line = document.createElement('div');
				line.className = 'bk-grid__row';
				line.setAttribute('role', 'row');

				days.forEach(function (date, col) {
					var slot = state.slots[date + ' ' + time];
					var isFree = !!slot && slot.status === 'free';
					var isSelected = isFree && !!state.selected && state.selected.id === slot.id;
					var cell = document.createElement('div');

					cell.className = 'bk-slot ' + (isFree ? 'is-free' : slot ? 'is-taken' : 'is-none') + (isSelected ? ' is-selected' : '');
					cell.setAttribute('role', 'gridcell');
					cell.setAttribute('data-row', row);
					cell.setAttribute('data-col', col);
					cell.setAttribute('aria-selected', isSelected ? 'true' : 'false');
					cell.tabIndex = -1;

					if (isFree) {
						freeCount++;
						cell.setAttribute('data-slot-id', slot.id);
						cell.setAttribute('data-date', date);
						cell.setAttribute('data-time', time);
					} else {
						// Занятый слот остаётся в дереве доступности и получает фокус, но не выбирается.
						cell.setAttribute('aria-disabled', 'true');
					}

					cell.setAttribute('aria-label', WEEKDAYS_FULL[col] + ', ' + dayAndMonth(date) + ', ' + time + ' — '
						+ (isFree ? TEXT.free : slot ? TEXT.taken : TEXT.none));
					cell.textContent = slot ? time : '—';

					line.appendChild(cell);
				});

				fragment.appendChild(line);
			});

			var hadFocus = keepFocus && el.grid.contains(document.activeElement);

			el.grid.textContent = '';
			el.grid.appendChild(fragment);
			el.grid.setAttribute('aria-busy', 'false');
			el.grid.classList.remove('is-loading');

			// Одна ячейка в порядке табуляции: выбранная, затем прежняя позиция, затем первая свободная.
			var target = el.grid.querySelector('.bk-slot.is-selected')
				|| (state.focus && cellAt(state.focus.row, state.focus.col))
				|| el.grid.querySelector('.bk-slot.is-free')
				|| cellAt(0, 0);
			setRovingCell(target);
			if (hadFocus && target) {
				target.focus();
			}

			el.gridStatus.textContent = weekRange(state.week) + ': '
				+ (freeCount ? 'свободных окон — ' + freeCount : 'свободных окон нет');
		}

		function renderGridMessage(text, withRetry) {
			el.grid.textContent = '';
			el.grid.setAttribute('aria-busy', withRetry ? 'false' : 'true');

			var box = document.createElement('div');
			box.className = 'bk-grid__note';
			box.textContent = text;

			if (withRetry) {
				var retry = document.createElement('button');
				retry.type = 'button';
				retry.className = 'bk-grid__retry';
				retry.textContent = TEXT.retry;
				retry.addEventListener('click', function () { loadSlots(false); });
				box.appendChild(document.createTextNode(' '));
				box.appendChild(retry);
				el.gridStatus.textContent = text;
			}

			el.grid.appendChild(box);
		}

		function updateNav() {
			el.range.textContent = weekRange(state.week);
			el.prev.disabled = state.week <= config.firstWeek;
			el.next.disabled = state.week >= config.lastWeek;
		}

		function updateChoice() {
			if (!state.selected) {
				el.choice.textContent = TEXT.noChoice;
				el.choice.classList.remove('is-set');

				return;
			}

			var master = findById(config.masters, state.selected.masterId);
			var service = findById(config.services, el.service.value);
			var parts = [
				WEEKDAYS_FULL[weekdayIndex(state.selected.date)] + ', ' + dayAndMonth(state.selected.date) + ', ' + state.selected.time
			];
			if (master) {
				parts.push('мастер ' + master.name);
			}
			if (service) {
				parts.push(service.name + ', ' + formatPrice(service.price));
			}

			el.choice.textContent = 'Вы выбрали: ' + parts.join(' · ');
			el.choice.classList.add('is-set');
		}

		function loadSlots(keepFocus) {
			var masterId = el.master.value;
			var week = state.week;

			if (state.request) {
				state.request.abort();
			}
			var controller = typeof AbortController === 'function' ? new AbortController() : null;
			state.request = controller;

			updateNav();
			el.grid.setAttribute('aria-busy', 'true');
			el.grid.classList.add('is-loading');
			if (!el.grid.querySelector('[role="gridcell"]')) {
				renderGridMessage(TEXT.loading, false);
			}

			var url = config.endpoints.slots
				+ '&masterId=' + encodeURIComponent(masterId)
				+ '&weekStart=' + encodeURIComponent(week);

			return fetch(url, {
				credentials: 'same-origin',
				headers: {'X-Requested-With': 'XMLHttpRequest'},
				signal: controller ? controller.signal : undefined
			})
				.then(function (response) { return response.json(); })
				.then(function (payload) {
					if (controller !== state.request) {
						return;
					}
					if (payload.status !== 'success' || !payload.data || !Array.isArray(payload.data.slots)) {
						throw new Error('slots');
					}

					state.slots = {};
					payload.data.slots.forEach(function (slot) {
						// startsAt в ISO-8601 во времени студии: берём дату и часы как записано.
						state.slots[slot.startsAt.slice(0, 10) + ' ' + slot.startsAt.slice(11, 16)] = slot;
					});

					// Выбранное ранее время могли занять, пока посетитель заполнял форму.
					if (state.selected && state.selected.masterId === Number(masterId)) {
						var key = state.selected.date + ' ' + state.selected.time;
						if (state.selected.date >= week && state.selected.date <= addDays(week, 6)
							&& (!state.slots[key] || state.slots[key].status !== 'free')) {
							state.selected = null;
							updateChoice();
						}
					}

					renderGrid(keepFocus);
				})
				.catch(function (error) {
					if (error && error.name === 'AbortError') {
						return;
					}
					renderGridMessage(TEXT.loadError, true);
				});
		}

		function selectCell(cell) {
			if (!cell || cell.getAttribute('aria-disabled') === 'true') {
				return;
			}

			var previous = el.grid.querySelector('.bk-slot.is-selected');
			if (previous) {
				previous.classList.remove('is-selected');
				previous.setAttribute('aria-selected', 'false');
			}

			cell.classList.add('is-selected');
			cell.setAttribute('aria-selected', 'true');

			state.selected = {
				id: Number(cell.getAttribute('data-slot-id')),
				date: cell.getAttribute('data-date'),
				time: cell.getAttribute('data-time'),
				masterId: Number(el.master.value)
			};

			setRovingCell(cell);
			setFieldError('slotId', '');
			updateChoice();
		}

		function changeWeek(direction, keepFocus) {
			var week = addDays(state.week, direction * 7);
			if (week < config.firstWeek || week > config.lastWeek) {
				return;
			}

			state.week = week;
			loadSlots(keepFocus);
		}

		el.grid.addEventListener('click', function (event) {
			var cell = event.target.closest ? event.target.closest('[role="gridcell"]') : null;
			if (!cell) {
				return;
			}

			setRovingCell(cell);
			selectCell(cell);
		});

		el.grid.addEventListener('focusin', function (event) {
			if (event.target.getAttribute && event.target.getAttribute('role') === 'gridcell') {
				setRovingCell(event.target);
			}
		});

		el.grid.addEventListener('keydown', function (event) {
			var cell = event.target;
			if (!cell.getAttribute || cell.getAttribute('role') !== 'gridcell') {
				return;
			}

			var row = +cell.getAttribute('data-row');
			var col = +cell.getAttribute('data-col');
			var lastRow = hours.length - 1;
			var target = null;

			switch (event.key) {
				case 'ArrowRight': target = cellAt(row, Math.min(col + 1, 6)); break;
				case 'ArrowLeft': target = cellAt(row, Math.max(col - 1, 0)); break;
				case 'ArrowDown': target = cellAt(Math.min(row + 1, lastRow), col); break;
				case 'ArrowUp': target = cellAt(Math.max(row - 1, 0), col); break;
				case 'Home': target = event.ctrlKey ? cellAt(0, 0) : cellAt(row, 0); break;
				case 'End': target = event.ctrlKey ? cellAt(lastRow, 6) : cellAt(row, 6); break;
				case 'PageDown': event.preventDefault(); changeWeek(1, true); return;
				case 'PageUp': event.preventDefault(); changeWeek(-1, true); return;
				case 'Enter':
				case ' ':
					event.preventDefault();
					selectCell(cell);

					return;
				default:
					return;
			}

			event.preventDefault();
			if (target) {
				setRovingCell(target);
				target.focus();
			}
		});

		el.prev.addEventListener('click', function () { changeWeek(-1, false); });
		el.next.addEventListener('click', function () { changeWeek(1, false); });

		el.service.addEventListener('change', function () {
			var masterBefore = el.master.value;
			fillMasters();
			setFieldError('serviceId', '');

			if (el.master.value !== masterBefore) {
				state.selected = null;
				loadSlots(false);
			}
			updateChoice();
		});

		el.master.addEventListener('change', function () {
			state.selected = null;
			updateChoice();
			loadSlots(false);
		});

		/* ---------- отправка ---------- */

		function validate() {
			var errors = {};

			if (!state.selected) {
				errors.slotId = TEXT.slotRequired;
			}
			if (!isValidName(el.name.value)) {
				errors.name = TEXT.nameInvalid;
			}
			if (!normalizePhone(el.phone.value)) {
				errors.phone = TEXT.phoneInvalid;
			}
			if (!el.consent.checked) {
				errors.consent = TEXT.consentRequired;
			}

			return errors;
		}

		function showErrors(errors) {
			var order = ['serviceId', 'slotId', 'name', 'phone', 'consent'];
			var first = null;

			order.forEach(function (field) {
				setFieldError(field, errors[field] || '');
				if (errors[field] && !first) {
					first = field;
				}
			});

			if (first) {
				var control = first === 'slotId'
					? el.grid.querySelector('[role="gridcell"][tabindex="0"]')
					: form.elements[first];
				if (control && control.focus) {
					control.focus();
				}
			}
		}

		function setSending(sending) {
			state.sending = sending;
			el.submit.disabled = sending;
			el.submit.setAttribute('aria-busy', sending ? 'true' : 'false');
			el.submitText.textContent = sending ? TEXT.sending : state.submitLabel;
		}

		function send(allowCsrfRetry) {
			var body = new URLSearchParams();
			body.set('slotId', state.selected.id);
			body.set('serviceId', el.service.value);
			body.set('name', el.name.value.trim());
			body.set('phone', el.phone.value.trim());
			body.set('consent', el.consent.checked ? 'Y' : '');
			body.set('sessid', state.sessid);

			return fetch(config.endpoints.create, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'X-Requested-With': 'XMLHttpRequest',
					'X-Bitrix-Csrf-Token': state.sessid
				},
				body: body.toString()
			})
				.then(function (response) { return response.json(); })
				.then(function (payload) {
					var errors = payload.errors || [];
					var csrf = errors.filter(function (error) { return error.code === 'invalid_csrf'; })[0];

					// Сессия истекла, пока страница была открыта: берём новый токен из ответа и повторяем один раз.
					if (csrf && allowCsrfRetry && csrf.customData && csrf.customData.csrf) {
						state.sessid = csrf.customData.csrf;

						return send(false);
					}

					return payload;
				});
		}

		function handleFailure(errors) {
			var fieldErrors = {};
			var slotGone = false;
			var other = '';

			errors.forEach(function (error) {
				var field = error.customData && error.customData.field;

				if (error.code === 'CONSENT_REQUIRED') {
					fieldErrors.consent = error.message;
				} else if (error.code === 'VALIDATION' && field) {
					fieldErrors[field] = error.message;
				} else if (error.code === 'SLOT_TAKEN' || error.code === 'SLOT_NOT_FOUND') {
					slotGone = true;
				} else {
					other = other || TEXT.serverError;
				}
			});

			if (slotGone) {
				state.selected = null;
				updateChoice();
				loadSlots(false);
				setMessage(TEXT.slotGone, 'error');
				setFieldError('slotId', TEXT.slotRequired);
				var cell = el.grid.querySelector('[role="gridcell"][tabindex="0"]');
				if (cell) {
					cell.focus();
				}

				return;
			}

			if (Object.keys(fieldErrors).length) {
				showErrors(fieldErrors);
				setMessage(TEXT.fixErrors, 'error');

				return;
			}

			setMessage(other || TEXT.serverError, 'error');
		}

		function showSuccess(bookingId) {
			var master = findById(config.masters, state.selected.masterId);
			var service = findById(config.services, el.service.value);
			var rows = [
				['Когда', 'в ' + WEEKDAYS_ACC[weekdayIndex(state.selected.date)] + ', ' + dayAndMonth(state.selected.date) + ', в ' + state.selected.time],
				['Мастер', master ? master.name : ''],
				['Услуга', service ? service.name + ' — ' + formatPrice(service.price) : ''],
				['Адрес', config.address || ''],
				['Номер записи', '№ ' + bookingId]
			];

			form.hidden = true;
			el.success.hidden = false;

			// Содержимое вставляем после показа блока: так role="status" гарантированно озвучит успех.
			el.successTitle.textContent = TEXT.successTitle;
			el.successList.textContent = '';
			rows.forEach(function (row) {
				if (!row[1]) {
					return;
				}
				var term = document.createElement('dt');
				var value = document.createElement('dd');
				term.textContent = row[0];
				value.textContent = row[1];
				el.successList.appendChild(term);
				el.successList.appendChild(value);
			});
			el.successNote.textContent = TEXT.successNote;
			el.success.focus();

			// Только что занятый слот сразу становится недоступным в расписании.
			state.selected = null;
			updateChoice();
			loadSlots(false);
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (state.sending) {
				return;
			}

			clearErrors();

			var errors = validate();
			if (Object.keys(errors).length) {
				showErrors(errors);
				setMessage(TEXT.fixErrors, 'error');

				return;
			}

			setSending(true);
			send(true)
				.then(function (payload) {
					setSending(false);

					if (payload.status === 'success' && payload.data && payload.data.bookingId) {
						showSuccess(payload.data.bookingId);

						return;
					}

					handleFailure(payload.errors || []);
				})
				.catch(function () {
					setSending(false);
					setMessage(TEXT.networkError, 'error');
				});
		});

		// Ошибка поля исчезает, как только посетитель начал её исправлять.
		[['name', 'input'], ['phone', 'input'], ['consent', 'change']].forEach(function (pair) {
			form.elements[pair[0]].addEventListener(pair[1], function () {
				setFieldError(pair[0], '');
			});
		});

		el.again.addEventListener('click', function () {
			el.success.hidden = true;
			form.hidden = false;
			clearErrors();
			el.grid.querySelector('[role="gridcell"][tabindex="0"]')
				? el.grid.querySelector('[role="gridcell"][tabindex="0"]').focus()
				: el.service.focus();
		});

		// Карточки услуг и мастеров на странице могут предвыбрать значения формы.
		document.addEventListener('bxmax:booking:select', function (event) {
			var detail = event.detail || {};

			if (detail.serviceId && findById(config.services, detail.serviceId)) {
				el.service.value = String(detail.serviceId);
				fillMasters();
			}
			if (detail.masterId) {
				var master = findById(config.masters, detail.masterId);
				if (master && detail.serviceId === undefined && master.serviceIds.length
					&& master.serviceIds.indexOf(Number(el.service.value)) === -1) {
					// Мастер не делает текущую услугу: переключаемся на первую из его списка.
					el.service.value = String(master.serviceIds[0]);
					fillMasters();
				}
				if (el.master.querySelector('option[value="' + Number(detail.masterId) + '"]')) {
					el.master.value = String(detail.masterId);
				}
			}

			if (!el.success.hidden) {
				el.success.hidden = true;
				form.hidden = false;
			}

			state.selected = null;
			updateChoice();
			loadSlots(false);
		});

		fillMasters();
		updateChoice();
		loadSlots(false);
	}

	function boot() {
		var forms = document.querySelectorAll('[data-booking-form]');
		for (var i = 0; i < forms.length; i++) {
			if (!forms[i].hasAttribute('data-booking-ready')) {
				forms[i].setAttribute('data-booking-ready', '');
				initBookingForm(forms[i]);
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
