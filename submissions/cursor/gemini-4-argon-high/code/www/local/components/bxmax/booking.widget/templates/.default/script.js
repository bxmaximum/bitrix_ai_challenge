(function () {
    'use strict';

    var MONTHS_SHORT = ['янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    var WEEKDAYS_SHORT = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    function parseIsoDate(ymd) {
        var parts = String(ymd).split('-');
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 0, 0, 0);
    }

    function formatIsoDate(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function addDays(ymd, days) {
        var dt = parseIsoDate(ymd);
        dt.setDate(dt.getDate() + days);
        return formatIsoDate(dt);
    }

    function formatWeekLabel(weekStartYmd) {
        var start = parseIsoDate(weekStartYmd);
        var end = parseIsoDate(addDays(weekStartYmd, 6));
        var startStr = start.getDate() + ' ' + MONTHS_SHORT[start.getMonth()];
        var endStr = end.getDate() + ' ' + MONTHS_SHORT[end.getMonth()] + ' ' + end.getFullYear();
        return startStr + ' — ' + endStr;
    }

    function formatSlotTimeLabel(isoStr) {
        var match = String(isoStr).match(/T(\d{2}:\d{2})/);
        if (match) {
            return match[1];
        }
        var d = new Date(isoStr);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function extractDateKey(isoStr) {
        var match = String(isoStr).match(/^(\d{4}-\d{2}-\d{2})/);
        if (match) {
            return match[1];
        }
        var d = new Date(isoStr);
        return formatIsoDate(d);
    }

    function formatFullSlotDateTime(startsAtIso, endsAtIso) {
        var dateKey = extractDateKey(startsAtIso);
        var dt = parseIsoDate(dateKey);
        var dayIdx = (dt.getDay() + 6) % 7;
        var dayLabel = WEEKDAYS_SHORT[dayIdx] + ', ' + dt.getDate() + ' ' + MONTHS_SHORT[dt.getMonth()] + ' ' + dt.getFullYear();
        var startTime = formatSlotTimeLabel(startsAtIso);
        var endTime = endsAtIso ? formatSlotTimeLabel(endsAtIso) : '';
        return dayLabel + ', ' + startTime + (endTime ? ' – ' + endTime : '');
    }

    function initBookingWidget() {
        var root = document.querySelector('[data-booking-widget]');
        var config = window.BXMAX_BOOKING_CONFIG;
        if (!root || !config) {
            return;
        }

        var state = {
            serviceId: Number(config.defaultServiceId) || 0,
            masterId: Number(config.defaultMasterId) || 0,
            weekStart: String(config.defaultWeekStart || ''),
            slotId: 0,
            selectedSlotMeta: null,
            slots: Array.isArray(config.initialSlots) ? config.initialSlots.slice() : [],
            loadingSlots: false,
            submitting: false
        };

        var formView = root.querySelector('[data-booking-form-view]');
        var successView = root.querySelector('[data-booking-success-view]');
        var form = root.querySelector('#bxmax-booking-form');

        var serviceInput = root.querySelector('#bxmax-booking-service-input');
        var masterInput = root.querySelector('#bxmax-booking-master-input');
        var slotInput = root.querySelector('#bxmax-booking-slot-input');

        var serviceListbox = root.querySelector('[data-service-listbox]');
        var masterListbox = root.querySelector('[data-master-listbox]');
        var slotsListbox = root.querySelector('[data-slots-listbox]');

        var weekPrevBtn = root.querySelector('[data-week-prev]');
        var weekNextBtn = root.querySelector('[data-week-next]');
        var weekLabelEl = root.querySelector('[data-week-label]');

        var summaryBar = root.querySelector('[data-booking-summary]');
        var feedbackBox = root.querySelector('[data-booking-feedback]');
        var submitBtn = root.querySelector('[data-booking-submit]');
        var resetBtn = root.querySelector('[data-booking-reset]');

        var nameInput = root.querySelector('[data-input-name]');
        var phoneInput = root.querySelector('[data-input-phone]');
        var consentInput = root.querySelector('[data-input-consent]');

        function getSelectedServiceObj() {
            var list = config.services || [];
            for (var i = 0; i < list.length; i++) {
                if (Number(list[i].id) === Number(state.serviceId)) {
                    return list[i];
                }
            }
            return list[0] || null;
        }

        function getSelectedMasterObj() {
            var list = config.masters || [];
            for (var i = 0; i < list.length; i++) {
                if (Number(list[i].id) === Number(state.masterId)) {
                    return list[i];
                }
            }
            return list[0] || null;
        }

        function showFeedback(message) {
            if (!feedbackBox) {
                return;
            }
            if (!message) {
                feedbackBox.hidden = true;
                feedbackBox.textContent = '';
                return;
            }
            feedbackBox.hidden = false;
            feedbackBox.textContent = message;
        }

        function updateSummaryBar() {
            if (!summaryBar) {
                return;
            }
            var srv = getSelectedServiceObj();
            var mst = getSelectedMasterObj();
            var parts = [];
            if (srv) {
                parts.push('Услуга: ' + srv.name + ' (' + srv.priceFormatted + ')');
            }
            if (mst) {
                parts.push('Мастер: ' + mst.name);
            }
            if (state.selectedSlotMeta) {
                parts.push('Время: ' + formatFullSlotDateTime(state.selectedSlotMeta.startsAt, state.selectedSlotMeta.endsAt));
            } else {
                parts.push('Слот: не выбран');
            }
            summaryBar.textContent = parts.join(' · ');
        }

        function syncServiceButtons() {
            if (!serviceListbox) {
                return;
            }
            var btns = serviceListbox.querySelectorAll('[data-service-option]');
            btns.forEach(function (btn) {
                var id = Number(btn.getAttribute('data-service-id'));
                var selected = id === state.serviceId;
                btn.classList.toggle('is-selected', selected);
                btn.setAttribute('aria-selected', selected ? 'true' : 'false');
                btn.setAttribute('tabindex', selected ? '0' : '-1');
            });
            if (serviceInput) {
                serviceInput.value = String(state.serviceId || '');
            }
            updateSummaryBar();
        }

        function syncMasterButtons() {
            if (!masterListbox) {
                return;
            }
            var btns = masterListbox.querySelectorAll('[data-master-option]');
            btns.forEach(function (btn) {
                var id = Number(btn.getAttribute('data-master-id'));
                var selected = id === state.masterId;
                btn.classList.toggle('is-selected', selected);
                btn.setAttribute('aria-selected', selected ? 'true' : 'false');
                btn.setAttribute('tabindex', selected ? '0' : '-1');
            });
            if (masterInput) {
                masterInput.value = String(state.masterId || '');
            }
            updateSummaryBar();
        }

        function renderSchedule() {
            if (!slotsListbox) {
                return;
            }
            if (weekLabelEl) {
                weekLabelEl.textContent = formatWeekLabel(state.weekStart);
            }

            var grouped = {};
            for (var d = 0; d < 7; d++) {
                var dayKey = addDays(state.weekStart, d);
                grouped[dayKey] = [];
            }

            state.slots.forEach(function (slot) {
                var key = extractDateKey(slot.startsAt);
                if (grouped[key]) {
                    grouped[key].push(slot);
                }
            });

            slotsListbox.innerHTML = '';

            var firstFocusableAssigned = false;

            for (var i = 0; i < 7; i++) {
                var currentKey = addDays(state.weekStart, i);
                var dt = parseIsoDate(currentKey);
                var dayCol = document.createElement('div');
                dayCol.className = 'bxmax-booking__day-col';

                var head = document.createElement('div');
                head.className = 'bxmax-booking__day-head';

                var wkSpan = document.createElement('span');
                wkSpan.className = 'bxmax-booking__day-weekday';
                wkSpan.textContent = WEEKDAYS_SHORT[i];

                var dateSpan = document.createElement('span');
                dateSpan.className = 'bxmax-booking__day-date';
                dateSpan.textContent = dt.getDate() + ' ' + MONTHS_SHORT[dt.getMonth()];

                head.appendChild(wkSpan);
                head.appendChild(dateSpan);
                dayCol.appendChild(head);

                var slotList = document.createElement('div');
                slotList.className = 'bxmax-booking__slot-list';

                var daySlots = grouped[currentKey] || [];
                if (daySlots.length === 0) {
                    var emptyEl = document.createElement('div');
                    emptyEl.className = 'bxmax-booking__empty-day';
                    emptyEl.textContent = 'Нет слотов';
                    slotList.appendChild(emptyEl);
                } else {
                    daySlots.forEach(function (slot) {
                        var isTaken = slot.status === 'taken';
                        var isSelected = !isTaken && Number(slot.id) === Number(state.slotId);
                        var timeStr = formatSlotTimeLabel(slot.startsAt);

                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'bxmax-booking__slot';
                        btn.setAttribute('role', 'option');
                        btn.setAttribute('data-slot-option', '');
                        btn.setAttribute('data-slot-id', String(slot.id));
                        btn.setAttribute('data-starts-at', String(slot.startsAt));
                        btn.setAttribute('data-ends-at', String(slot.endsAt));
                        btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                        btn.setAttribute('aria-disabled', isTaken ? 'true' : 'false');
                        btn.setAttribute(
                            'aria-label',
                            WEEKDAYS_SHORT[i] + ' ' + dt.getDate() + ' ' + MONTHS_SHORT[dt.getMonth()] + ', ' + timeStr + (isTaken ? ', занято' : ', свободно')
                        );

                        if (isSelected || (!firstFocusableAssigned && !isTaken && state.slotId === 0)) {
                            btn.setAttribute('tabindex', '0');
                            firstFocusableAssigned = true;
                        } else {
                            btn.setAttribute('tabindex', '-1');
                        }

                        btn.textContent = timeStr;
                        slotList.appendChild(btn);
                    });
                }

                dayCol.appendChild(slotList);
                slotsListbox.appendChild(dayCol);
            }

            // Если все слоты на неделе заняты, оставляем первый слот доступным для фокуса клавиатурой
            if (!firstFocusableAssigned) {
                var firstSlotBtn = slotsListbox.querySelector('[data-slot-option]');
                if (firstSlotBtn) {
                    firstSlotBtn.setAttribute('tabindex', '0');
                }
            }
        }

        function selectSlot(slotId, startsAt, endsAt, focusEl) {
            state.slotId = Number(slotId);
            state.selectedSlotMeta = {
                id: Number(slotId),
                startsAt: startsAt,
                endsAt: endsAt
            };
            if (slotInput) {
                slotInput.value = String(state.slotId);
            }
            showFeedback('');

            var allBtns = slotsListbox.querySelectorAll('[data-slot-option]');
            allBtns.forEach(function (btn) {
                var id = Number(btn.getAttribute('data-slot-id'));
                var isSelected = id === state.slotId;
                btn.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                btn.setAttribute('tabindex', isSelected ? '0' : '-1');
            });

            if (focusEl) {
                focusEl.focus();
            }
            updateSummaryBar();
        }

        function loadSlots() {
            if (!state.masterId) {
                return;
            }
            state.loadingSlots = true;
            var url = config.endpoints.slotsList
                + '&masterId=' + encodeURIComponent(String(state.masterId))
                + '&weekStart=' + encodeURIComponent(String(state.weekStart));

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (json) {
                    state.loadingSlots = false;
                    if (json && json.status === 'success' && json.data && Array.isArray(json.data.slots)) {
                        state.slots = json.data.slots;
                        // Проверяем, остался ли выбранный слот свободным в текущем списке
                        var stillFree = state.slots.some(function (s) {
                            return Number(s.id) === Number(state.slotId) && s.status === 'free';
                        });
                        if (!stillFree) {
                            state.slotId = 0;
                            state.selectedSlotMeta = null;
                            if (slotInput) {
                                slotInput.value = '';
                            }
                        }
                        renderSchedule();
                        updateSummaryBar();
                    } else {
                        var errMsg = (json && json.errors && json.errors[0] && json.errors[0].message)
                            ? json.errors[0].message
                            : 'Не удалось загрузить расписание слотов.';
                        showFeedback(errMsg);
                    }
                })
                .catch(function () {
                    state.loadingSlots = false;
                    showFeedback('Ошибка соединения при загрузке расписания.');
                });
        }

        function attachListboxKeyboard(container, itemSelector, onActivate) {
            if (!container) {
                return;
            }
            container.addEventListener('keydown', function (e) {
                var items = Array.prototype.slice.call(container.querySelectorAll(itemSelector));
                if (!items.length) {
                    return;
                }
                var current = document.activeElement;
                var idx = items.indexOf(current);
                if (idx === -1) {
                    idx = 0;
                }

                var nextIdx = idx;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    nextIdx = (idx + 1) % items.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    nextIdx = (idx - 1 + items.length) % items.length;
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    nextIdx = 0;
                } else if (e.key === 'End') {
                    e.preventDefault();
                    nextIdx = items.length - 1;
                } else if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    if (items[idx]) {
                        onActivate(items[idx]);
                    }
                    return;
                } else {
                    return;
                }

                items.forEach(function (el, i) {
                    el.setAttribute('tabindex', i === nextIdx ? '0' : '-1');
                });
                items[nextIdx].focus();
            });
        }

        if (serviceListbox) {
            serviceListbox.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-service-option]');
                if (!btn) {
                    return;
                }
                state.serviceId = Number(btn.getAttribute('data-service-id'));
                syncServiceButtons();
                btn.focus();
            });

            attachListboxKeyboard(serviceListbox, '[data-service-option]', function (btn) {
                state.serviceId = Number(btn.getAttribute('data-service-id'));
                syncServiceButtons();
                btn.focus();
            });
        }

        if (masterListbox) {
            masterListbox.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-master-option]');
                if (!btn) {
                    return;
                }
                var newMasterId = Number(btn.getAttribute('data-master-id'));
                if (newMasterId !== state.masterId) {
                    state.masterId = newMasterId;
                    state.slotId = 0;
                    state.selectedSlotMeta = null;
                    if (slotInput) {
                        slotInput.value = '';
                    }
                    syncMasterButtons();
                    loadSlots();
                }
                btn.focus();
            });

            attachListboxKeyboard(masterListbox, '[data-master-option]', function (btn) {
                btn.click();
            });
        }

        if (slotsListbox) {
            slotsListbox.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-slot-option]');
                if (!btn) {
                    return;
                }
                if (btn.getAttribute('aria-disabled') === 'true') {
                    showFeedback('Этот слот уже занят. Пожалуйста, выберите свободное время.');
                    return;
                }
                selectSlot(
                    btn.getAttribute('data-slot-id'),
                    btn.getAttribute('data-starts-at'),
                    btn.getAttribute('data-ends-at'),
                    btn
                );
            });

            attachListboxKeyboard(slotsListbox, '[data-slot-option]', function (btn) {
                if (btn.getAttribute('aria-disabled') === 'true') {
                    showFeedback('Этот слот уже занят. Пожалуйста, выберите свободное время.');
                    return;
                }
                selectSlot(
                    btn.getAttribute('data-slot-id'),
                    btn.getAttribute('data-starts-at'),
                    btn.getAttribute('data-ends-at'),
                    btn
                );
            });
        }

        if (weekPrevBtn) {
            weekPrevBtn.addEventListener('click', function () {
                state.weekStart = addDays(state.weekStart, -7);
                loadSlots();
            });
        }

        if (weekNextBtn) {
            weekNextBtn.addEventListener('click', function () {
                state.weekStart = addDays(state.weekStart, 7);
                loadSlots();
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (state.submitting) {
                    return;
                }
                showFeedback('');

                var nameVal = nameInput ? nameInput.value.trim() : '';
                var phoneVal = phoneInput ? phoneInput.value.trim() : '';
                var consentVal = consentInput && consentInput.checked ? '1' : '';

                if (!state.slotId) {
                    showFeedback('Пожалуйста, выберите свободный слот времени в расписании.');
                    return;
                }

                state.submitting = true;
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                var formData = new URLSearchParams();
                formData.set('slotId', String(state.slotId));
                formData.set('serviceId', String(state.serviceId));
                formData.set('name', nameVal);
                formData.set('phone', phoneVal);
                if (consentVal) {
                    formData.set('consent', '1');
                }

                fetch(config.endpoints.bookingsCreate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json'
                    },
                    body: formData.toString(),
                    credentials: 'same-origin'
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (json) {
                        state.submitting = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }

                        if (json && json.status === 'success' && json.data && json.data.bookingId) {
                            var srv = getSelectedServiceObj();
                            var mst = getSelectedMasterObj();
                            var slotMeta = state.selectedSlotMeta;

                            // Помечаем слот занятым в локальном состоянии немедленно
                            state.slots = state.slots.map(function (s) {
                                if (Number(s.id) === Number(state.slotId)) {
                                    return {
                                        id: s.id,
                                        startsAt: s.startsAt,
                                        endsAt: s.endsAt,
                                        status: 'taken'
                                    };
                                }
                                return s;
                            });

                            var idNode = root.querySelector('[data-success-booking-id]');
                            var srvNode = root.querySelector('[data-success-service]');
                            var mstNode = root.querySelector('[data-success-master]');
                            var dtNode = root.querySelector('[data-success-datetime]');
                            var clNode = root.querySelector('[data-success-client]');

                            if (idNode) {
                                idNode.textContent = '#' + json.data.bookingId;
                            }
                            if (srvNode) {
                                srvNode.textContent = srv ? (srv.name + ' (' + srv.priceFormatted + ')') : '';
                            }
                            if (mstNode) {
                                mstNode.textContent = mst ? mst.name : '';
                            }
                            if (dtNode && slotMeta) {
                                dtNode.textContent = formatFullSlotDateTime(slotMeta.startsAt, slotMeta.endsAt);
                            }
                            if (clNode) {
                                clNode.textContent = nameVal + ', ' + phoneVal;
                            }

                            state.slotId = 0;
                            state.selectedSlotMeta = null;
                            if (slotInput) {
                                slotInput.value = '';
                            }
                            renderSchedule();

                            if (formView && successView) {
                                formView.hidden = true;
                                successView.hidden = false;
                                successView.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        } else {
                            var err = (json && json.errors && json.errors[0]) ? json.errors[0] : null;
                            var msg = (err && err.message) ? err.message : 'Не удалось оформить запись. Проверьте поля формы.';
                            showFeedback(msg);
                            if (err && err.code === 'SLOT_TAKEN') {
                                loadSlots();
                            }
                        }
                    })
                    .catch(function () {
                        state.submitting = false;
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        showFeedback('Ошибка сети при отправке заявки. Попробуйте ещё раз.');
                    });
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (form) {
                    form.reset();
                }
                showFeedback('');
                if (successView && formView) {
                    successView.hidden = true;
                    formView.hidden = false;
                }
                loadSlots();
            });
        }

        // Публичный интерфейс для выбора услуги или мастера из секций лендинга
        window.BxmaxBookingWidget = {
            selectService: function (serviceId) {
                var id = Number(serviceId);
                if (!id) {
                    return;
                }
                state.serviceId = id;
                syncServiceButtons();
                if (successView && formView && !successView.hidden) {
                    successView.hidden = true;
                    formView.hidden = false;
                }
            },
            selectMaster: function (masterId) {
                var id = Number(masterId);
                if (!id) {
                    return;
                }
                if (id !== state.masterId) {
                    state.masterId = id;
                    state.slotId = 0;
                    state.selectedSlotMeta = null;
                    if (slotInput) {
                        slotInput.value = '';
                    }
                    syncMasterButtons();
                    loadSlots();
                }
                if (successView && formView && !successView.hidden) {
                    successView.hidden = true;
                    formView.hidden = false;
                }
            }
        };

        renderSchedule();
        updateSummaryBar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBookingWidget);
    } else {
        initBookingWidget();
    }
})();
