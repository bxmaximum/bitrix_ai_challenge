(function () {
    'use strict';

    function boot() {
    var root = document.querySelector('[data-booking]');
    if (!root) {
        return;
    }

    var form = root.querySelector('[data-booking-form]');
    var serviceEl = root.querySelector('[data-service]');
    var masterEl = root.querySelector('[data-master]');
    var slotsEl = root.querySelector('[data-slots]');
    var statusEl = root.querySelector('[data-status]');
    var weekLabel = root.querySelector('[data-week-label]');
    var successEl = root.querySelector('[data-success]');
    var successText = root.querySelector('[data-success-text]');
    var sessid = root.getAttribute('data-sessid') || '';
    var weekStart = root.getAttribute('data-week-start') || mondayOf(new Date());
    var selectedSlot = null;
    var slots = [];
    var focusIndex = -1;

    function mondayOf(date) {
        var d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        var day = d.getDay();
        var diff = day === 0 ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return isoDate(d);
    }

    function isoDate(d) {
        var m = d.getMonth() + 1;
        var day = d.getDate();
        return d.getFullYear() + '-' + pad(m) + '-' + pad(day);
    }

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function addDays(iso, days) {
        var p = iso.split('-');
        var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
        d.setDate(d.getDate() + days);
        return isoDate(d);
    }

    function formatWeek(iso) {
        var start = iso.split('-');
        var s = new Date(Number(start[0]), Number(start[1]) - 1, Number(start[2]));
        var e = new Date(s);
        e.setDate(e.getDate() + 6);
        return pad(s.getDate()) + '.' + pad(s.getMonth() + 1) + ' — ' + pad(e.getDate()) + '.' + pad(e.getMonth() + 1);
    }

    function ajax(action, data) {
        var body = new URLSearchParams();
        body.set('sessid', sessid);
        Object.keys(data).forEach(function (key) {
            body.set(key, String(data[key]));
        });
        var url = '/bitrix/services/main/ajax.php?action=' + encodeURIComponent(action);
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Bitrix-Csrf-Token': sessid
            },
            body: body
        }).then(function (res) {
            return res.json();
        });
    }

    function loadSlots() {
        selectedSlot = null;
        slotsEl.innerHTML = '';
        if (!masterEl.value) {
            weekLabel.textContent = formatWeek(weekStart);
            return Promise.resolve();
        }
        weekLabel.textContent = formatWeek(weekStart);
        return ajax(root.getAttribute('data-list-action'), {
            masterId: masterEl.value,
            weekStart: weekStart
        }).then(function (json) {
            if (!json || json.status !== 'success') {
                var code = json && json.errors && json.errors[0] ? json.errors[0].code : '';
                setStatus(code === 'MASTER_NOT_FOUND' ? 'Мастер не найден' : 'Не удалось загрузить слоты');
                return;
            }
            slots = json.data.slots || [];
            renderSlots();
        });
    }

    function renderSlots() {
        slotsEl.innerHTML = '';
        if (!slots.length) {
            slotsEl.innerHTML = '<p class="bk__empty">На эту неделю слотов нет</p>';
            return;
        }
        slots.forEach(function (slot, index) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'bk__slot';
            btn.setAttribute('role', 'option');
            btn.dataset.index = String(index);
            var start = wallTime(slot.startsAt);
            btn.textContent = weekdayName(start.dow) + ' ' + pad(start.h) + ':' + pad(start.mi);
            var taken = slot.status === 'taken';
            btn.setAttribute('aria-disabled', taken ? 'true' : 'false');
            btn.setAttribute('aria-selected', 'false');
            if (taken) {
                btn.classList.add('is-taken');
            } else {
                btn.addEventListener('click', function () {
                    selectSlot(index);
                });
            }
            slotsEl.appendChild(btn);
        });
    }

    function wallTime(iso) {
        var m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
        if (!m) {
            var d = new Date(iso);
            return { y: d.getFullYear(), mo: d.getMonth() + 1, d: d.getDate(), h: d.getHours(), mi: d.getMinutes(), dow: d.getDay() };
        }
        var y = Number(m[1]);
        var mo = Number(m[2]);
        var d = Number(m[3]);
        return {
            y: y,
            mo: mo,
            d: d,
            h: Number(m[4]),
            mi: Number(m[5]),
            dow: new Date(y, mo - 1, d).getDay()
        };
    }

    function weekdayName(dow) {
        return ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'][dow];
    }

    function selectSlot(index) {
        var slot = slots[index];
        if (!slot || slot.status === 'taken') {
            return;
        }
        selectedSlot = slot;
        focusIndex = index;
        Array.prototype.forEach.call(slotsEl.querySelectorAll('[role="option"]'), function (el, i) {
            var on = i === index;
            el.setAttribute('aria-selected', on ? 'true' : 'false');
            el.classList.toggle('is-selected', on);
        });
        setStatus('');
    }

    function setStatus(message, isError) {
        statusEl.textContent = message || '';
        statusEl.classList.toggle('is-error', Boolean(isError) && Boolean(message));
    }

    slotsEl.addEventListener('keydown', function (event) {
        var enabled = [];
        slots.forEach(function (slot, i) {
            if (slot.status !== 'taken') {
                enabled.push(i);
            }
        });
        if (!enabled.length) {
            return;
        }
        var pos = enabled.indexOf(focusIndex);
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
            event.preventDefault();
            pos = pos < 0 ? 0 : Math.min(enabled.length - 1, pos + 1);
            selectSlot(enabled[pos]);
            slotsEl.querySelector('[data-index="' + enabled[pos] + '"]').focus();
        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
            event.preventDefault();
            pos = pos < 0 ? 0 : Math.max(0, pos - 1);
            selectSlot(enabled[pos]);
            slotsEl.querySelector('[data-index="' + enabled[pos] + '"]').focus();
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (pos >= 0) {
                selectSlot(enabled[pos]);
            }
        }
    });

    masterEl.addEventListener('change', loadSlots);
    root.querySelector('[data-week-prev]').addEventListener('click', function () {
        var min = mondayOf(new Date());
        var next = addDays(weekStart, -7);
        if (next < min) {
            next = min;
        }
        weekStart = next;
        loadSlots();
    });
    root.querySelector('[data-week-next]').addEventListener('click', function () {
        var max = addDays(mondayOf(new Date()), 7);
        var next = addDays(weekStart, 7);
        if (next > max) {
            next = max;
        }
        weekStart = next;
        loadSlots();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setStatus('');
        if (!serviceEl.value || !masterEl.value) {
            setStatus('Выберите услугу и мастера', true);
            return;
        }
        if (!selectedSlot) {
            setStatus('Выберите свободный слот', true);
            return;
        }
        var name = form.querySelector('#bk-name').value.trim();
        var phone = form.querySelector('#bk-phone').value.trim();
        var consent = form.querySelector('[data-consent]').checked;
        if (!consent) {
            setStatus('Нужно согласие на обработку данных', true);
            return;
        }
        var submit = root.querySelector('[data-submit]');
        submit.disabled = true;
        ajax(root.getAttribute('data-create-action'), {
            slotId: selectedSlot.id,
            serviceId: serviceEl.value,
            name: name,
            phone: phone,
            consent: consent ? '1' : '0'
        }).then(function (json) {
            submit.disabled = false;
            if (!json || json.status !== 'success') {
                var err = json && json.errors && json.errors[0] ? json.errors[0] : {};
                var map = {
                    SLOT_TAKEN: 'Этот слот уже занят — выберите другой',
                    SLOT_NOT_FOUND: 'Слот не найден',
                    VALIDATION: 'Проверьте имя и телефон',
                    CONSENT_REQUIRED: 'Нужно согласие на обработку данных'
                };
                setStatus(map[err.code] || 'Не получилось записаться', true);
                if (err.code === 'SLOT_TAKEN') {
                    loadSlots();
                }
                return;
            }
            var start = wallTime(selectedSlot.startsAt);
            var serviceName = serviceEl.options[serviceEl.selectedIndex].text;
            var masterName = masterEl.options[masterEl.selectedIndex].text;
            successText.textContent = 'Заявка №' + json.data.bookingId + ': ' + serviceName + ', ' + masterName +
                ', ' + pad(start.d) + '.' + pad(start.mo) + ' в ' + pad(start.h) + ':' + pad(start.mi) +
                '. Имя: ' + name + ', телефон: ' + phone + '.';
            form.hidden = true;
            successEl.hidden = false;
            loadSlots();
        }).catch(function () {
            submit.disabled = false;
            setStatus('Сеть недоступна, попробуйте ещё раз', true);
        });
    });

    root.querySelector('[data-success-reset]').addEventListener('click', function () {
        successEl.hidden = true;
        form.hidden = false;
        form.reset();
        selectedSlot = null;
        slotsEl.innerHTML = '';
        setStatus('');
        weekStart = mondayOf(new Date());
        weekLabel.textContent = formatWeek(weekStart);
    });

    weekLabel.textContent = formatWeek(weekStart);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
