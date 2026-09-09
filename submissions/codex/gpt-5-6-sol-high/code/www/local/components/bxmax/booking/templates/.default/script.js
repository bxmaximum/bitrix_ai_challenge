(function () {
    'use strict';
    function init() {

    const root = document.querySelector('[data-booking-root]');
    if (!root) return;

    const form = root.querySelector('[data-booking-form]');
    const serviceSelect = form.elements.serviceId;
    const masterSelect = form.elements.masterId;
    const slotInput = root.querySelector('[data-slot-input]');
    const slotsNode = root.querySelector('[data-slots]');
    const weekLabel = root.querySelector('[data-week-label]');
    const weekPrev = root.querySelector('[data-week-prev]');
    const weekNext = root.querySelector('[data-week-next]');
    const statusNode = root.querySelector('[data-booking-status]');
    const successNode = root.querySelector('[data-booking-success]');
    const successDetails = root.querySelector('[data-success-details]');
    const currentMonday = startOfWeek(new Date());
    let shownMonday = new Date(currentMonday);
    let selectedSlot = null;

    function startOfWeek(date) {
        const result = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const day = result.getDay() || 7;
        result.setDate(result.getDate() - day + 1);
        return result;
    }

    function dateKey(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatWeek(date) {
        const end = new Date(date);
        end.setDate(end.getDate() + 6);
        const compact = new Intl.DateTimeFormat('ru-RU', {day: 'numeric', month: 'short'});
        return `${compact.format(date)} — ${compact.format(end)}`;
    }

    function setStatus(message, isError) {
        statusNode.textContent = message;
        statusNode.classList.toggle('is-error', Boolean(isError));
    }

    async function loadSlots() {
        selectedSlot = null;
        slotInput.value = '';
        weekLabel.textContent = formatWeek(shownMonday);
        weekPrev.disabled = shownMonday <= currentMonday;
        const lastWeek = new Date(currentMonday);
        lastWeek.setDate(lastWeek.getDate() + 7);
        weekNext.disabled = shownMonday >= lastWeek;

        if (!masterSelect.value) {
            slotsNode.innerHTML = '<p class="slots__placeholder">Сначала выберите мастера.</p>';
            return;
        }

        slotsNode.setAttribute('aria-busy', 'true');
        slotsNode.innerHTML = '<p class="slots__placeholder">Проверяем расписание…</p>';
        const query = new URLSearchParams({masterId: masterSelect.value, weekStart: dateKey(shownMonday)});
        try {
            const response = await fetch(`/bitrix/services/main/ajax.php?action=bxmax:booking.api.slots.list&${query}`, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            });
            const payload = await response.json();
            if (payload.status !== 'success') throw new Error(payload.errors?.[0]?.message || 'Не удалось загрузить расписание.');
            renderSlots(payload.data.slots || []);
        } catch (error) {
            slotsNode.innerHTML = '';
            const message = document.createElement('p');
            message.className = 'slots__placeholder';
            message.textContent = error.message;
            slotsNode.append(message);
        } finally {
            slotsNode.removeAttribute('aria-busy');
        }
    }

    function renderSlots(slots) {
        slotsNode.innerHTML = '';
        if (!slots.length) {
            slotsNode.innerHTML = '<p class="slots__placeholder">На этой неделе свободных часов нет.</p>';
            return;
        }
        const groups = new Map();
        slots.forEach((slot) => {
            const date = new Date(`${slot.startsAt.slice(0, 10)}T12:00:00`);
            const key = slot.startsAt.slice(0, 10);
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push({...slot, date});
        });
        const dayFormat = new Intl.DateTimeFormat('ru-RU', {weekday: 'short', day: 'numeric', month: 'short'});
        groups.forEach((daySlots) => {
            const group = document.createElement('div');
            group.className = 'slots__day';
            const title = document.createElement('strong');
            title.textContent = dayFormat.format(daySlots[0].date);
            group.append(title);
            const times = document.createElement('div');
            times.className = 'slots__times';
            daySlots.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'slot';
                button.setAttribute('role', 'option');
                button.setAttribute('aria-selected', 'false');
                button.textContent = slot.startsAt.slice(11, 16);
                button.dataset.slotId = slot.id;
                if (slot.status === 'taken') {
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');
                    button.title = 'Время занято';
                } else {
                    button.addEventListener('click', () => selectSlot(button, slot));
                }
                times.append(button);
            });
            group.append(times);
            slotsNode.append(group);
        });
    }

    function selectSlot(button, slot) {
        slotsNode.querySelectorAll('[aria-selected="true"]').forEach((item) => item.setAttribute('aria-selected', 'false'));
        button.setAttribute('aria-selected', 'true');
        slotInput.value = String(slot.id);
        slotInput.setAttribute('value', String(slot.id));
        selectedSlot = slot;
        setStatus('', false);
    }

    masterSelect.addEventListener('change', loadSlots);
    weekPrev.addEventListener('click', () => { shownMonday.setDate(shownMonday.getDate() - 7); loadSlots(); });
    weekNext.addEventListener('click', () => { shownMonday.setDate(shownMonday.getDate() + 7); loadSlots(); });
    slotsNode.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
        const options = [...slotsNode.querySelectorAll('.slot:not(:disabled)')];
        const current = options.indexOf(event.target);
        if (current < 0 || options.length === 0) return;
        event.preventDefault();
        let next = current;
        if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = options.length - 1;
        else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (current - 1 + options.length) % options.length;
        else next = (current + 1) % options.length;
        options[next].focus();
    });

    document.querySelectorAll('.js-pick-service').forEach((button) => button.addEventListener('click', () => {
        serviceSelect.value = button.dataset.serviceId;
        root.scrollIntoView({behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
        serviceSelect.focus({preventScroll: true});
    }));
    document.querySelectorAll('.js-pick-master').forEach((button) => button.addEventListener('click', () => {
        masterSelect.value = button.dataset.masterId;
        shownMonday = new Date(currentMonday);
        loadSlots();
        root.scrollIntoView({behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
        masterSelect.focus({preventScroll: true});
    }));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setStatus('', false);
        if (!form.reportValidity()) return;
        if (!selectedSlot) {
            setStatus('Выберите свободное время.', true);
            slotsNode.focus?.();
            return;
        }
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Подтверждаем…';
        const data = new URLSearchParams({
            sessid: root.dataset.sessid,
            slotId: String(selectedSlot.id),
            serviceId: serviceSelect.value,
            name: form.elements.name.value,
            phone: form.elements.phone.value,
            consent: form.elements.consent.checked ? '1' : '0'
        });
        try {
            const response = await fetch('/bitrix/services/main/ajax.php?action=bxmax:booking.api.bookings.create', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json'},
                body: data
            });
            const payload = await response.json();
            if (payload.status !== 'success') {
                const code = payload.errors?.[0]?.code;
                if (code === 'SLOT_TAKEN') await loadSlots();
                throw new Error(payload.errors?.[0]?.message || 'Не удалось оформить запись.');
            }
            showSuccess(payload.data.bookingId);
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            submit.disabled = false;
            submit.innerHTML = 'Подтвердить запись <span aria-hidden="true">↗</span>';
        }
    });

    function showSuccess(bookingId) {
        form.hidden = true;
        successDetails.innerHTML = '';
        const rows = [
            ['Номер', `№${bookingId}`],
            ['Услуга', serviceSelect.options[serviceSelect.selectedIndex].text],
            ['Мастер', masterSelect.options[masterSelect.selectedIndex].text],
            ['Время', formatSlotDate(selectedSlot.startsAt)]
        ];
        rows.forEach(([label, value]) => {
            const row = document.createElement('p');
            const term = document.createElement('span');
            const detail = document.createElement('strong');
            term.textContent = label;
            detail.textContent = value;
            row.append(term, detail);
            successDetails.append(row);
        });
        successNode.hidden = false;
        successNode.focus();
    }

    function formatSlotDate(iso) {
        const [year, month, day, hour, minute] = iso.slice(0, 16).split(/[-T:]/).map(Number);
        return new Intl.DateTimeFormat('ru-RU', {dateStyle: 'long', timeStyle: 'short'}).format(new Date(year, month - 1, day, hour, minute));
    }

    loadSlots();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
    else init();
})();
