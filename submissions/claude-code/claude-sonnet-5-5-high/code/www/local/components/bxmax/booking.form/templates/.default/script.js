(function () {
    'use strict';

    var ACTION_LIST = 'bxmax:booking.api.slots.list';
    var ACTION_CREATE = 'bxmax:booking.api.bookings.create';
    var REFRESH_MS = 20000;

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    // Работаем с датами как с «YYYY-MM-DD» в UTC, чтобы часовой пояс браузера не сдвигал дни.
    function parseDay(str) {
        var p = str.split('-');
        return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2]));
    }
    function fmtDay(d) {
        return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate());
    }
    function addDays(str, n) {
        var d = parseDay(str);
        d.setUTCDate(d.getUTCDate() + n);
        return fmtDay(d);
    }
    function mondayOf(str) {
        var d = parseDay(str);
        var dow = (d.getUTCDay() + 6) % 7;
        d.setUTCDate(d.getUTCDate() - dow);
        return fmtDay(d);
    }

    var longFmt = new Intl.DateTimeFormat('ru-RU', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
    var weekdayFmt = new Intl.DateTimeFormat('ru-RU', { weekday: 'short', timeZone: 'UTC' });
    var dayFmt = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', timeZone: 'UTC' });
    var monthFmt = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', timeZone: 'UTC' });

    function Booking(root) {
        this.root = root;
        this.cfg = JSON.parse(root.getAttribute('data-config'));
        this.msg = this.cfg.messages;
        this.form = root.querySelector('[data-bk-form]');
        this.daysEl = root.querySelector('[data-bk-days]');
        this.weekLabel = root.querySelector('[data-bk-week-label]');
        this.prevBtn = root.querySelector('[data-bk-prev]');
        this.nextBtn = root.querySelector('[data-bk-next]');
        this.selectedEl = root.querySelector('[data-bk-selected]');
        this.statusEl = root.querySelector('[data-bk-status]');
        this.submitBtn = root.querySelector('[data-bk-submit]');
        this.successEl = root.querySelector('[data-bk-success]');
        this.detailsEl = root.querySelector('[data-bk-details]');

        this.currentMonday = mondayOf(this.cfg.today);
        this.weekStart = this.currentMonday;
        this.slots = [];
        this.selected = null; // {id, startsAt}
        this.requestSeq = 0;
        this.busy = false;

        this.bind();
        this.loadSlots();
        this.timer = setInterval(this.refreshIfVisible.bind(this), REFRESH_MS);
    }

    Booking.prototype.field = function (name) {
        return this.form.elements[name];
    };

    Booking.prototype.masterId = function () {
        var el = this.form.querySelector('input[name="masterId"]:checked');
        return el ? +el.value : 0;
    };

    Booking.prototype.serviceId = function () {
        var el = this.form.querySelector('input[name="serviceId"]:checked');
        return el ? +el.value : 0;
    };

    Booking.prototype.bind = function () {
        var self = this;

        this.form.addEventListener('submit', function (e) {
            e.preventDefault();
            self.submit();
        });
        this.form.addEventListener('change', function (e) {
            var t = e.target;
            if (t.name === 'masterId') {
                self.selected = null;
                self.weekStart = self.currentMonday;
                self.updateSelectedText();
                self.loadSlots();
            }
            if (t.name) { self.clearError(t.name); }
        });
        this.form.addEventListener('input', function (e) {
            if (e.target.name) { self.clearError(e.target.name); }
        });

        this.prevBtn.addEventListener('click', function () { self.shiftWeek(-1); });
        this.nextBtn.addEventListener('click', function () { self.shiftWeek(1); });

        this.daysEl.addEventListener('click', function (e) {
            var opt = e.target.closest('[role="option"]');
            if (opt) { self.choose(opt); }
        });
        this.daysEl.addEventListener('keydown', this.onKeydown.bind(this));

        this.root.querySelector('[data-bk-again]').addEventListener('click', function () { self.reset(); });

        // кнопки «Записаться» в карточках услуг/мастеров лендинга
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-booking-service], [data-booking-master]');
            if (!trigger) { return; }
            var sid = trigger.getAttribute('data-booking-service');
            var mid = trigger.getAttribute('data-booking-master');
            if (sid) { self.preselect('serviceId', sid); }
            if (mid) { self.preselect('masterId', mid); }
        });
    };

    Booking.prototype.preselect = function (name, value) {
        var input = this.form.querySelector('input[name="' + name + '"][value="' + value + '"]');
        if (!input || input.checked) { return; }
        input.checked = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /* ---------- слоты ---------- */

    Booking.prototype.maxMonday = function () {
        return addDays(this.currentMonday, 7 * this.cfg.weeksAhead);
    };

    Booking.prototype.shiftWeek = function (dir) {
        var next = addDays(this.weekStart, 7 * dir);
        if (next < this.currentMonday || next > this.maxMonday()) { return; }
        this.weekStart = next;
        this.selected = null;
        this.updateSelectedText();
        this.loadSlots();
        this.daysEl.focus && this.focusFirstOption();
    };

    Booking.prototype.updateWeekNav = function () {
        var end = addDays(this.weekStart, 6);
        this.weekLabel.textContent = monthFmt.format(parseDay(this.weekStart)) + ' — ' + monthFmt.format(parseDay(end));
        this.prevBtn.disabled = this.weekStart <= this.currentMonday;
        this.nextBtn.disabled = this.weekStart >= this.maxMonday();
    };

    Booking.prototype.api = function (action, params, method) {
        var url = this.cfg.ajaxUrl + '?action=' + encodeURIComponent(action);
        var init = { method: method || 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
        if (init.method === 'GET') {
            Object.keys(params || {}).forEach(function (k) { url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); });
        } else {
            var fd = new FormData();
            Object.keys(params || {}).forEach(function (k) { fd.append(k, params[k]); });
            init.body = fd;
        }
        return fetch(url, init).then(function (r) { return r.json(); });
    };

    Booking.prototype.loadSlots = function (silent) {
        var self = this;
        var seq = ++this.requestSeq;
        var masterId = this.masterId();
        this.updateWeekNav();
        if (!masterId) { return Promise.resolve(); }
        if (!silent) {
            this.daysEl.setAttribute('aria-busy', 'true');
            this.renderPlaceholder(this.msg.loading);
        }
        return this.api(ACTION_LIST, { masterId: masterId, weekStart: this.weekStart }).then(function (res) {
            if (seq !== self.requestSeq) { return; }
            self.daysEl.removeAttribute('aria-busy');
            self.slots = (res && res.data && res.data.slots) || [];
            // выбранный слот могли занять в другой вкладке
            if (self.selected) {
                var still = self.slots.filter(function (s) { return s.id === self.selected.id && s.status === 'free'; })[0];
                if (!still) { self.selected = null; self.updateSelectedText(); }
            }
            self.renderDays();
        }).catch(function () {
            if (seq !== self.requestSeq) { return; }
            self.daysEl.removeAttribute('aria-busy');
            if (!silent) { self.renderPlaceholder(self.msg.errNetwork); }
        });
    };

    Booking.prototype.refreshIfVisible = function () {
        if (document.hidden || this.busy || !this.successEl.hidden) { return; }
        var active = document.activeElement;
        var hadFocusInside = active && this.daysEl.contains(active);
        var focusedId = hadFocusInside ? active.getAttribute('data-slot-id') : null;
        var self = this;
        this.loadSlots(true).then(function () {
            if (focusedId) {
                var again = self.daysEl.querySelector('[data-slot-id="' + focusedId + '"]');
                if (again) { again.focus(); }
            }
        });
    };

    Booking.prototype.renderPlaceholder = function (text) {
        this.daysEl.textContent = '';
        var p = document.createElement('p');
        p.className = 'bk__placeholder';
        p.textContent = text;
        this.daysEl.appendChild(p);
    };

    Booking.prototype.renderDays = function () {
        var byDay = {};
        this.slots.forEach(function (s) {
            var day = s.startsAt.slice(0, 10);
            (byDay[day] = byDay[day] || []).push(s);
        });

        this.daysEl.textContent = '';
        if (!this.slots.length) {
            this.renderPlaceholder(this.msg.noSlots);
            return;
        }

        var frag = document.createDocumentFragment();
        var firstFree = null;
        for (var i = 0; i < 7; i++) {
            var day = addDays(this.weekStart, i);
            var list = byDay[day] || [];
            var date = parseDay(day);

            var group = document.createElement('div');
            group.className = 'bk__day';
            group.setAttribute('role', 'group');
            group.setAttribute('aria-label', longFmt.format(date));

            var head = document.createElement('div');
            head.className = 'bk__day-head';
            head.setAttribute('aria-hidden', 'true');
            var wd = document.createElement('span');
            wd.className = 'bk__day-name';
            wd.textContent = weekdayFmt.format(date);
            var dt = document.createElement('span');
            dt.className = 'bk__day-date';
            dt.textContent = dayFmt.format(date);
            head.appendChild(wd);
            head.appendChild(dt);
            group.appendChild(head);

            var wrap = document.createElement('div');
            wrap.className = 'bk__times';
            list.forEach(function (s) {
                var free = s.status === 'free';
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bk__slot';
                btn.setAttribute('role', 'option');
                btn.setAttribute('data-slot-id', s.id);
                btn.setAttribute('data-starts-at', s.startsAt);
                btn.setAttribute('aria-selected', this.selected && this.selected.id === s.id ? 'true' : 'false');
                if (!free) { btn.setAttribute('aria-disabled', 'true'); }
                btn.setAttribute('tabindex', '-1');
                var time = s.startsAt.slice(11, 16);
                btn.textContent = time;
                if (!free) {
                    var sr = document.createElement('span');
                    sr.className = 'bk__sr';
                    sr.textContent = ', ' + this.msg.taken;
                    btn.appendChild(sr);
                } else if (!firstFree) {
                    firstFree = btn;
                }
                wrap.appendChild(btn);
            }, this);
            if (!list.length) {
                var none = document.createElement('p');
                none.className = 'bk__none';
                none.textContent = this.msg.noDay;
                wrap.appendChild(none);
            }
            group.appendChild(wrap);
            frag.appendChild(group);
        }
        this.daysEl.appendChild(frag);

        // единая точка входа в listbox для клавиатуры (roving tabindex)
        var current = this.daysEl.querySelector('[aria-selected="true"]') || firstFree || this.daysEl.querySelector('[role="option"]');
        if (current) { current.setAttribute('tabindex', '0'); }
    };

    Booking.prototype.options = function () {
        return Array.prototype.slice.call(this.daysEl.querySelectorAll('[role="option"]'));
    };

    Booking.prototype.focusFirstOption = function () { /* фокус остаётся на кнопке недели */ };

    Booking.prototype.moveFocus = function (opt) {
        if (!opt) { return; }
        this.options().forEach(function (o) { o.setAttribute('tabindex', '-1'); });
        opt.setAttribute('tabindex', '0');
        opt.focus();
    };

    Booking.prototype.onKeydown = function (e) {
        var opt = e.target.closest('[role="option"]');
        if (!opt) { return; }
        var all = this.options();
        var idx = all.indexOf(opt);
        var group = opt.closest('.bk__day');
        var inDay = Array.prototype.slice.call(group.querySelectorAll('[role="option"]'));
        var dayIdx = inDay.indexOf(opt);
        var groups = Array.prototype.slice.call(this.daysEl.querySelectorAll('.bk__day'));
        var gIdx = groups.indexOf(group);
        var target = null;

        function sameRow(delta) {
            var g = groups[gIdx + delta];
            while (g && !g.querySelector('[role="option"]')) { g = groups[groups.indexOf(g) + delta]; }
            if (!g) { return null; }
            var items = g.querySelectorAll('[role="option"]');
            return items[Math.min(dayIdx, items.length - 1)];
        }

        switch (e.key) {
            case 'ArrowDown': target = inDay[dayIdx + 1] || null; break;
            case 'ArrowUp': target = inDay[dayIdx - 1] || null; break;
            case 'ArrowRight': target = sameRow(1); break;
            case 'ArrowLeft': target = sameRow(-1); break;
            case 'Home': target = all[0]; break;
            case 'End': target = all[all.length - 1]; break;
            case 'PageDown': e.preventDefault(); if (!this.nextBtn.disabled) { this.nextBtn.click(); } return;
            case 'PageUp': e.preventDefault(); if (!this.prevBtn.disabled) { this.prevBtn.click(); } return;
            case ' ':
            case 'Enter':
                e.preventDefault();
                this.choose(opt);
                return;
            default: return;
        }
        if (target) {
            e.preventDefault();
            this.moveFocus(target);
        }
    };

    Booking.prototype.choose = function (opt) {
        this.moveFocus(opt);
        if (opt.getAttribute('aria-disabled') === 'true') { return; }
        this.options().forEach(function (o) { o.setAttribute('aria-selected', 'false'); });
        opt.setAttribute('aria-selected', 'true');
        this.selected = { id: +opt.getAttribute('data-slot-id'), startsAt: opt.getAttribute('data-starts-at') };
        this.clearError('slotId');
        this.updateSelectedText();
    };

    Booking.prototype.slotLabel = function (startsAt) {
        return longFmt.format(parseDay(startsAt.slice(0, 10))) + ', ' + startsAt.slice(11, 16);
    };

    Booking.prototype.updateSelectedText = function () {
        this.selectedEl.textContent = this.selected ? this.msg.selected + ': ' + this.slotLabel(this.selected.startsAt) : '';
    };

    /* ---------- форма ---------- */

    Booking.prototype.setError = function (name, text) {
        var box = this.root.querySelector('[data-bk-error="' + name + '"]');
        if (box) { box.textContent = text; }
        var input = this.form.elements[name];
        if (input && input.setAttribute && input.type !== 'radio') { input.setAttribute('aria-invalid', 'true'); }
    };

    Booking.prototype.clearError = function (name) {
        var box = this.root.querySelector('[data-bk-error="' + name + '"]');
        if (box) { box.textContent = ''; }
        var input = this.form.elements[name];
        if (input && input.removeAttribute && input.type !== 'radio') { input.removeAttribute('aria-invalid'); }
        this.statusEl.textContent = '';
        this.statusEl.classList.remove('is-error');
    };

    Booking.prototype.clearAllErrors = function () {
        var self = this;
        Array.prototype.forEach.call(this.root.querySelectorAll('[data-bk-error]'), function (box) {
            self.clearError(box.getAttribute('data-bk-error'));
        });
    };

    Booking.prototype.validate = function () {
        var errors = [];
        var name = this.field('name').value.trim();
        var digits = this.field('phone').value.replace(/\D+/g, '');
        if (!this.serviceId()) { errors.push(['serviceId', this.msg.errService]); }
        if (!this.masterId()) { errors.push(['masterId', this.msg.errMaster]); }
        if (!this.selected) { errors.push(['slotId', this.msg.errSlot]); }
        if (name.length < 2) { errors.push(['name', this.msg.errName]); }
        if (digits.length < 10 || digits.length > 15) { errors.push(['phone', this.msg.errPhone]); }
        if (!this.field('consent').checked) { errors.push(['consent', this.msg.errConsent]); }
        return errors;
    };

    Booking.prototype.focusFor = function (name) {
        var el;
        if (name === 'serviceId') { el = this.form.querySelector('input[name="serviceId"]'); }
        else if (name === 'masterId') { el = this.form.querySelector('input[name="masterId"]'); }
        else if (name === 'slotId') { el = this.daysEl.querySelector('[tabindex="0"]') || this.daysEl; }
        else { el = this.form.elements[name]; }
        if (el && el.focus) { el.focus(); }
    };

    Booking.prototype.setBusy = function (busy) {
        this.busy = busy;
        this.submitBtn.disabled = busy;
        this.submitBtn.textContent = busy ? this.msg.sending : this.msg.submit;
        this.form.setAttribute('aria-busy', busy ? 'true' : 'false');
    };

    Booking.prototype.submit = function () {
        var self = this;
        if (this.busy) { return; }
        this.clearAllErrors();

        var errors = this.validate();
        if (errors.length) {
            errors.forEach(function (e) { self.setError(e[0], e[1]); });
            this.focusFor(errors[0][0]);
            return;
        }

        this.setBusy(true);
        var snapshot = {
            service: this.cfg.services[this.serviceId()],
            master: this.cfg.masters[this.masterId()],
            slot: this.selected,
            name: this.field('name').value.trim(),
            phone: this.field('phone').value.trim()
        };

        this.api(ACTION_CREATE, {
            slotId: this.selected.id,
            serviceId: this.serviceId(),
            name: snapshot.name,
            phone: snapshot.phone,
            consent: 'Y'
        }, 'POST').then(function (res) {
            self.setBusy(false);
            if (res && res.status === 'success') {
                self.showSuccess(snapshot, res.data && res.data.bookingId);
                return;
            }
            self.handleErrors((res && res.errors) || []);
        }).catch(function () {
            self.setBusy(false);
            self.showStatus(self.msg.errNetwork);
        });
    };

    Booking.prototype.showStatus = function (text) {
        this.statusEl.textContent = text;
        this.statusEl.classList.add('is-error');
    };

    Booking.prototype.handleErrors = function (errors) {
        var code = errors.length ? errors[0].code : '';
        var message = errors.length ? errors[0].message : '';
        var customData = (errors[0] && errors[0].customData) || {};
        var self = this;

        if (code === 'SLOT_TAKEN' || code === 'SLOT_NOT_FOUND') {
            this.selected = null;
            this.updateSelectedText();
            this.showStatus(this.msg.errSlotTaken);
            this.setError('slotId', this.msg.errSlot);
            this.loadSlots().then(function () { self.focusFor('slotId'); });
        } else if (code === 'CONSENT_REQUIRED') {
            this.setError('consent', this.msg.errConsent);
            this.focusFor('consent');
        } else if (code === 'VALIDATION' && customData.field && this.form.elements[customData.field]) {
            this.setError(customData.field, message || this.msg.errGeneric);
            this.focusFor(customData.field);
        } else if (code === 'VALIDATION' && customData.field === 'serviceId') {
            this.setError('serviceId', this.msg.errService);
            this.focusFor('serviceId');
        } else {
            this.showStatus(this.msg.errGeneric);
        }
    };

    Booking.prototype.showSuccess = function (snap, bookingId) {
        var rows = [
            ['Номер записи', '№ ' + (bookingId || '—')],
            ['Услуга', snap.service ? snap.service.name : ''],
            ['Мастер', snap.master ? snap.master.name : ''],
            ['Дата и время', this.slotLabel(snap.slot.startsAt)],
            ['Имя', snap.name],
            ['Телефон', snap.phone]
        ];
        this.detailsEl.textContent = '';
        rows.forEach(function (r) {
            var wrap = document.createElement('div');
            var dt = document.createElement('dt');
            var dd = document.createElement('dd');
            dt.textContent = r[0];
            dd.textContent = r[1];
            wrap.appendChild(dt);
            wrap.appendChild(dd);
            this.detailsEl.appendChild(wrap);
        }, this);

        this.form.hidden = true;
        this.successEl.hidden = false;
        this.successEl.focus();
        this.root.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    };

    Booking.prototype.reset = function () {
        this.form.reset();
        this.clearAllErrors();
        this.selected = null;
        this.updateSelectedText();
        this.weekStart = this.currentMonday;
        this.successEl.hidden = true;
        this.form.hidden = false;
        this.loadSlots();
        var first = this.form.querySelector('input[name="serviceId"]');
        if (first) { first.focus(); }
    };

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-bk]'), function (root) {
            if (!root.__booking) { root.__booking = new Booking(root); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
