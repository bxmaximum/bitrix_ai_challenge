/* Виджет онлайн-записи: слоты, недели, отправка заявки.
   Ванильный JS, без зависимостей. Работает с клавиатуры. */
(function () {
	'use strict';

	var WEEKDAYS = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
	var MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
		'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

	var ERRORS = {
		MASTER_NOT_FOUND: 'Мастер больше не принимает. Выберите другого.',
		SLOT_NOT_FOUND: 'Это время исчезло из расписания. Обновите список и выберите другое.',
		SLOT_TAKEN: 'Это время только что заняли. Выберите соседний слот.',
		VALIDATION: 'Проверьте имя, телефон и выбранную услугу.',
		CONSENT_REQUIRED: 'Без согласия на обработку персональных данных запись невозможна.'
	};

	function pad(value) {
		return value < 10 ? '0' + value : String(value);
	}

	/** Дата берётся из строки ISO как есть — без пересчёта в часовой пояс браузера. */
	function dayKey(iso) {
		return iso.slice(0, 10);
	}

	function timeLabel(iso) {
		return iso.slice(11, 16);
	}

	function parseDayKey(key) {
		var parts = key.split('-');
		return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
	}

	function toDayKey(date) {
		return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
	}

	function shiftWeek(key, weeks) {
		var date = parseDayKey(key);
		date.setDate(date.getDate() + weeks * 7);
		return toDayKey(date);
	}

	function Booking(root) {
		this.root = root;
		this.config = JSON.parse(root.getAttribute('data-config'));

		this.form = root.querySelector('[data-form]');
		this.serviceEl = root.querySelector('[data-service]');
		this.masterEl = root.querySelector('[data-master]');
		this.slotsEl = root.querySelector('[data-slots]');
		this.weekLabel = root.querySelector('[data-week-label]');
		this.prevBtn = root.querySelector('[data-week-prev]');
		this.nextBtn = root.querySelector('[data-week-next]');
		this.nameEl = root.querySelector('[data-name]');
		this.phoneEl = root.querySelector('[data-phone]');
		this.consentEl = root.querySelector('[data-consent]');
		this.messageEl = root.querySelector('[data-message]');
		this.submitEl = root.querySelector('[data-submit]');
		this.successEl = root.querySelector('[data-success]');
		this.againEl = root.querySelector('[data-again]');

		this.weekStart = this.config.weekStart;
		this.slots = [];
		this.selected = null;
		this.requestId = 0;

		this.bind();
		this.renderWeekLabel();
		this.load();
	}

	Booking.prototype.bind = function () {
		var self = this;

		this.masterEl.addEventListener('change', function () {
			self.selected = null;
			self.load();
		});

		this.prevBtn.addEventListener('click', function () {
			self.moveWeek(-1);
		});

		this.nextBtn.addEventListener('click', function () {
			self.moveWeek(1);
		});

		this.slotsEl.addEventListener('click', function (event) {
			var option = event.target.closest('[role="option"]');
			if (option) {
				self.select(option);
			}
		});

		this.slotsEl.addEventListener('keydown', function (event) {
			self.onKeydown(event);
		});

		this.form.addEventListener('submit', function (event) {
			event.preventDefault();
			self.submit();
		});

		this.againEl.addEventListener('click', function () {
			self.reset();
		});
	};

	Booking.prototype.moveWeek = function (direction) {
		var next = shiftWeek(this.weekStart, direction);
		if (next < this.config.weekMin || next > this.config.weekMax) {
			return;
		}
		this.weekStart = next;
		this.selected = null;
		this.renderWeekLabel();
		this.load();
	};

	Booking.prototype.renderWeekLabel = function () {
		var from = parseDayKey(this.weekStart);
		var to = parseDayKey(this.weekStart);
		to.setDate(to.getDate() + 6);

		var label = from.getDate() + ' ' + MONTHS[from.getMonth()] + ' — '
			+ to.getDate() + ' ' + MONTHS[to.getMonth()];

		this.weekLabel.textContent = label;
		this.prevBtn.disabled = shiftWeek(this.weekStart, -1) < this.config.weekMin;
		this.nextBtn.disabled = shiftWeek(this.weekStart, 1) > this.config.weekMax;
	};

	Booking.prototype.load = function () {
		var self = this;
		var ticket = ++this.requestId;

		this.slotsEl.setAttribute('aria-busy', 'true');
		this.slotsEl.innerHTML = '<p class="bk__slots-empty">Загружаем расписание…</p>';

		var params = new URLSearchParams({
			action: this.config.actionSlots,
			masterId: this.masterEl.value,
			weekStart: this.weekStart,
			sessid: this.config.sessid
		});

		fetch(this.config.ajaxUrl + '?' + params.toString(), {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (ticket !== self.requestId) {
					return;
				}
				if (payload.status !== 'success') {
					self.slots = [];
					self.renderSlots();
					self.say(self.describe(payload.errors), 'error');
					return;
				}
				self.slots = payload.data.slots || [];
				self.renderSlots();
			})
			.catch(function () {
				if (ticket !== self.requestId) {
					return;
				}
				self.slots = [];
				self.renderSlots();
				self.say('Не удалось загрузить расписание. Проверьте связь и попробуйте ещё раз.', 'error');
			});
	};

	Booking.prototype.renderSlots = function () {
		this.slotsEl.setAttribute('aria-busy', 'false');
		this.slotsEl.innerHTML = '';

		if (!this.slots.length) {
			var empty = document.createElement('p');
			empty.className = 'bk__slots-empty';
			empty.textContent = 'На эту неделю у мастера нет расписания. Посмотрите следующую.';
			this.slotsEl.appendChild(empty);
			return;
		}

		var days = {};
		var order = [];

		this.slots.forEach(function (slot) {
			var key = dayKey(slot.startsAt);
			if (!days[key]) {
				days[key] = [];
				order.push(key);
			}
			days[key].push(slot);
		});

		var self = this;
		var firstEnabled = null;

		order.forEach(function (key) {
			var date = parseDayKey(key);
			var group = document.createElement('div');
			group.className = 'bk-day';
			group.setAttribute('role', 'group');
			group.setAttribute('aria-label', WEEKDAYS[date.getDay()] + ', ' + date.getDate() + ' ' + MONTHS[date.getMonth()]);

			var title = document.createElement('p');
			title.className = 'bk-day__title';
			title.innerHTML = '<span class="bk-day__weekday">' + WEEKDAYS[date.getDay()]
				+ '</span><span class="bk-day__date">' + date.getDate() + '</span>';
			title.setAttribute('aria-hidden', 'true');
			group.appendChild(title);

			var list = document.createElement('div');
			list.className = 'bk-day__slots';

			days[key].forEach(function (slot) {
				var button = document.createElement('button');
				var taken = slot.status === 'taken';

				button.type = 'button';
				button.className = 'bk-slot' + (taken ? ' bk-slot--taken' : '');
				button.setAttribute('role', 'option');
				button.setAttribute('aria-selected', 'false');
				button.setAttribute('data-slot-id', String(slot.id));
				button.setAttribute('tabindex', '-1');
				button.textContent = timeLabel(slot.startsAt);

				var readable = WEEKDAYS[date.getDay()] + ', ' + date.getDate() + ' ' + MONTHS[date.getMonth()]
					+ ', ' + timeLabel(slot.startsAt);

				if (taken) {
					button.setAttribute('aria-disabled', 'true');
					button.setAttribute('aria-label', readable + ' — занято');
				} else {
					button.setAttribute('aria-label', readable + ' — свободно');
					if (!firstEnabled) {
						firstEnabled = button;
					}
				}

				list.appendChild(button);
			});

			group.appendChild(list);
			self.slotsEl.appendChild(group);
		});

		var options = this.options();
		if (options.length) {
			(firstEnabled || options[0]).setAttribute('tabindex', '0');
		}
	};

	Booking.prototype.options = function () {
		return Array.prototype.slice.call(this.slotsEl.querySelectorAll('[role="option"]'));
	};

	Booking.prototype.select = function (option) {
		if (option.getAttribute('aria-disabled') === 'true') {
			this.say('Это время уже занято — выберите другое.', 'error');
			return;
		}

		this.options().forEach(function (item) {
			item.setAttribute('aria-selected', 'false');
			item.setAttribute('tabindex', '-1');
		});

		option.setAttribute('aria-selected', 'true');
		option.setAttribute('tabindex', '0');
		option.focus();

		this.selected = {
			id: Number(option.getAttribute('data-slot-id')),
			label: option.getAttribute('aria-label').replace(' — свободно', '')
		};

		this.say('');
	};

	Booking.prototype.onKeydown = function (event) {
		var options = this.options();
		if (!options.length) {
			return;
		}

		var current = document.activeElement;
		var index = options.indexOf(current);
		if (index === -1) {
			return;
		}

		var next = null;

		switch (event.key) {
			case 'ArrowRight':
			case 'ArrowDown':
				next = options[Math.min(options.length - 1, index + 1)];
				break;
			case 'ArrowLeft':
			case 'ArrowUp':
				next = options[Math.max(0, index - 1)];
				break;
			case 'Home':
				next = options[0];
				break;
			case 'End':
				next = options[options.length - 1];
				break;
			case 'Enter':
			case ' ':
			case 'Spacebar':
				event.preventDefault();
				this.select(current);
				return;
			default:
				return;
		}

		if (next) {
			event.preventDefault();
			options.forEach(function (item) {
				item.setAttribute('tabindex', '-1');
			});
			next.setAttribute('tabindex', '0');
			next.focus();
		}
	};

	Booking.prototype.say = function (text, kind) {
		this.messageEl.textContent = text || '';
		this.messageEl.className = 'bk__message' + (text ? ' bk__message--' + (kind || 'info') : '');
	};

	Booking.prototype.describe = function (errors) {
		if (!errors || !errors.length) {
			return 'Что-то пошло не так. Попробуйте ещё раз.';
		}

		var code = errors[0].code;
		return ERRORS[code] || errors[0].message || 'Что-то пошло не так. Попробуйте ещё раз.';
	};

	Booking.prototype.submit = function () {
		var self = this;

		if (!this.selected) {
			this.say('Сначала выберите свободное время в расписании.', 'error');
			this.slotsEl.focus();
			return;
		}

		if (!this.consentEl.checked) {
			this.say(ERRORS.CONSENT_REQUIRED, 'error');
			this.consentEl.focus();
			return;
		}

		var body = new FormData();
		body.append('sessid', this.config.sessid);
		body.append('slotId', String(this.selected.id));
		body.append('serviceId', this.serviceEl.value);
		body.append('name', this.nameEl.value);
		body.append('phone', this.phoneEl.value);
		body.append('consent', '1');

		this.submitEl.disabled = true;
		this.say('Отправляем заявку…', 'info');

		fetch(this.config.ajaxUrl + '?action=' + encodeURIComponent(this.config.actionCreate), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			body: body
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				self.submitEl.disabled = false;

				if (payload.status !== 'success') {
					self.say(self.describe(payload.errors), 'error');
					if (payload.errors && payload.errors[0]
						&& (payload.errors[0].code === 'SLOT_TAKEN' || payload.errors[0].code === 'SLOT_NOT_FOUND')) {
						self.selected = null;
						self.load();
					}
					return;
				}

				self.showSuccess(payload.data.bookingId);
			})
			.catch(function () {
				self.submitEl.disabled = false;
				self.say('Заявка не ушла — проверьте связь и попробуйте ещё раз.', 'error');
			});
	};

	Booking.prototype.showSuccess = function (bookingId) {
		var service = this.serviceEl.options[this.serviceEl.selectedIndex];
		var master = this.masterEl.options[this.masterEl.selectedIndex];

		this.root.querySelector('[data-success-title]').textContent = 'Ждём вас, ' + this.nameEl.value.trim();
		this.root.querySelector('[data-success-service]').textContent = service ? service.textContent.trim() : '';
		this.root.querySelector('[data-success-master]').textContent = master ? master.textContent.trim() : '';
		this.root.querySelector('[data-success-time]').textContent = this.selected ? this.selected.label : '';
		this.root.querySelector('[data-success-phone]').textContent = this.phoneEl.value.trim();
		this.root.querySelector('[data-success-id]').textContent = '№ ' + bookingId;

		this.form.hidden = true;
		this.successEl.hidden = false;
		this.successEl.focus();
	};

	Booking.prototype.reset = function () {
		this.form.hidden = false;
		this.successEl.hidden = true;
		this.selected = null;
		this.nameEl.value = '';
		this.phoneEl.value = '';
		this.consentEl.checked = false;
		this.say('');
		this.load();
	};

	function init() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-booking]'), function (root) {
			new Booking(root);
		});
	}

	if (document.readyState !== 'loading') {
		init();
	} else {
		document.addEventListener('DOMContentLoaded', init);
	}
})();
