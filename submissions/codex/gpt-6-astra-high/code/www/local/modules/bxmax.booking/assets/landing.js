(() => {
  'use strict';
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduced && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('motion');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    }), {threshold: 0.06});
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  }
  const menu = document.querySelector('.menu-toggle');
  const mobile = document.getElementById('mobile-menu');
  menu.addEventListener('click', () => {
    const open = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(open)); mobile.hidden = !open;
    menu.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
  });
  function closeMenu() { mobile.hidden = true; menu.setAttribute('aria-expanded', 'false'); menu.setAttribute('aria-label', 'Открыть меню'); }
  mobile.addEventListener('click', e => { if (e.target.closest('a')) closeMenu(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !mobile.hidden) { closeMenu(); menu.focus(); } });
  const privacy = document.getElementById('privacy');
  document.querySelectorAll('[data-policy]').forEach(el => el.addEventListener('click', () => privacy.showModal()));
  privacy.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', () => privacy.close()));
  const gallery = [...document.querySelectorAll('#gallery-track figure')];
  let galleryPage = 0;
  document.querySelectorAll('[data-gallery]').forEach(button => button.addEventListener('click', () => {
    galleryPage = (galleryPage + Number(button.dataset.gallery) + 2) % 2;
    gallery.forEach((figure, i) => { figure.style.display = Math.floor(i / 2) === galleryPage ? 'block' : 'none'; });
    document.getElementById('gallery-count').textContent = '0' + (galleryPage + 1) + ' / 02';
  }));
  const form = document.getElementById('booking-form');
  if (!form) return;
  const $ = id => document.getElementById(id);
  const service = $('booking-service'), master = $('booking-master');
  const days = $('booking-days'), slotsBox = $('booking-slots');
  const formStatus = $('form-status'), slotsStatus = $('slots-status');
  const submit = $('booking-submit');
  const dayMs = 86400000;
  const asDate = s => new Date(s + 'T12:00:00Z');
  const iso = d => d.toISOString().slice(0, 10);
  const shift = (date, n) => new Date(date.getTime() + dayMs * n);
  const today = asDate(form.dataset.today), end = shift(today, 13);
  const monday = d => shift(d, -((d.getUTCDay() + 6) % 7));
  const firstWeek = monday(today), lastWeek = monday(end);
  let week = new Date(firstWeek), selectedDay = iso(today), selectedSlot = null, slots = [], fetching = false, posting = false, controller;
  const formatDay = d => new Intl.DateTimeFormat('ru-RU', {day: 'numeric', month: 'long', timeZone: 'UTC'}).format(d);
  const formatTime = s => new Intl.DateTimeFormat('ru-RU', {hour:'2-digit',minute:'2-digit',timeZone:'Europe/Saratov'}).format(new Date(s));
  const formatFull = s => new Intl.DateTimeFormat('ru-RU', {weekday:'long',day:'numeric',month:'long',hour:'2-digit',minute:'2-digit',timeZone:'Europe/Saratov'}).format(new Date(s));
  function announceSelection() {
    $('selection-status').textContent = selectedSlot ? 'Вы выбрали: ' + formatFull(selectedSlot.startsAt) + ' · ' + master.selectedOptions[0].text : 'Время ещё не выбрано';
  }
  function renderDays() {
    $('week-label').textContent = formatDay(week) + ' — ' + formatDay(shift(week,6));
    $('week-prev').disabled = week <= firstWeek || posting;
    $('week-next').disabled = week >= lastWeek || posting;
    days.replaceChildren();
    for (let i=0; i<7; i++) {
      const date=shift(week,i), value=iso(date), available=date>=today && date<=end;
      const b=document.createElement('button'); b.type='button'; b.dataset.day=value;
      b.setAttribute('role','tab'); b.setAttribute('aria-selected',String(value===selectedDay)); b.setAttribute('aria-disabled',String(!available));
      b.id='booking-day-'+value; b.setAttribute('aria-controls','slot-panel'); b.setAttribute('aria-label',formatDay(date)+(available?'':', запись недоступна'));
      b.tabIndex=value===selectedDay?0:-1;
      b.append(document.createTextNode(['ПН','ВТ','СР','ЧТ','ПТ','СБ','ВС'][i]));
      const strong=document.createElement('strong'); strong.textContent=String(date.getUTCDate()); b.append(strong);
      b.addEventListener('click',()=> { if(!available||posting)return; selectedDay=value; selectedSlot=null; renderDays(); renderSlots(); announceSelection(); days.querySelector(`[data-day="${value}"]`).focus(); });
      days.append(b);
    }
  }
  function renderSlots() {
    const focused=document.activeElement?.dataset.slot;
    const current=slots.filter(s=>s.startsAt.slice(0,10)===selectedDay);
    if(selectedSlot && !slots.some(s=>s.id===selectedSlot.id && s.status==='free')) { selectedSlot=null; announceSelection(); }
    slotsBox.replaceChildren();
    $('day-label').textContent=formatDay(asDate(selectedDay));
    $('slot-panel').setAttribute('aria-labelledby','booking-day-'+selectedDay);
    current.forEach((slot,i)=> {
      const b=document.createElement('button'); b.type='button'; b.dataset.slot=slot.id;
      const taken=slot.status==='taken'; b.textContent=formatTime(slot.startsAt);
      b.setAttribute('role','option'); b.setAttribute('aria-selected',String(selectedSlot?.id===slot.id)); b.setAttribute('aria-disabled',String(taken));
      b.setAttribute('aria-label',formatTime(slot.startsAt)+(taken?' — занято':' — свободно'));
      b.tabIndex=selectedSlot? (selectedSlot.id===slot.id?0:-1) : (i===0?0:-1);
      b.addEventListener('click',()=> { if(taken||posting)return; selectedSlot=slot; renderSlots(); announceSelection(); slotsBox.querySelector(`[data-slot="${slot.id}"]`).focus(); });
      slotsBox.append(b);
    });
    if(focused) slotsBox.querySelector(`[data-slot="${focused}"]`)?.focus({preventScroll:true});
    if(!current.length && !fetching) slotsStatus.textContent='На этот день расписания пока нет. Выберите другую дату.';
  }
  function keyboardNav(container, event) {
    const buttons=[...container.querySelectorAll('button')]; const index=buttons.indexOf(document.activeElement);
    if(index<0)return;
    let next;
    if(event.key==='ArrowRight'||event.key==='ArrowDown')next=(index+1)%buttons.length;
    if(event.key==='ArrowLeft'||event.key==='ArrowUp')next=(index+buttons.length-1)%buttons.length;
    if(event.key==='Home')next=0;
    if(event.key==='End')next=buttons.length-1;
    if(next!==undefined) { event.preventDefault(); buttons.forEach(b=>b.tabIndex=-1); buttons[next].tabIndex=0; buttons[next].focus(); }
  }
  days.addEventListener('keydown',e=>keyboardNav(days,e)); slotsBox.addEventListener('keydown',e=>keyboardNav(slotsBox,e));
  async function loadSlots(silent=false) {
    controller?.abort(); controller=new AbortController(); const thisController=controller;
    fetching=true; slotsBox.setAttribute('aria-busy','true');
    if(!silent) { slotsStatus.textContent='Загружаем свободное время…'; slots=[]; renderSlots(); }
    try {
      const query=new URLSearchParams({action:'bxmax:booking.api.slots.list',masterId:master.value,weekStart:iso(week)});
      const res=await fetch('/bitrix/services/main/ajax.php?'+query,{signal:thisController.signal,credentials:'same-origin',cache:'no-store'});
      const json=await res.json(); if(json.status!=='success')throw new Error(json.errors?.[0]?.message||'Не удалось загрузить расписание');
      if(controller!==thisController)return;
      slots=json.data.slots; fetching=false; slotsStatus.textContent=''; renderSlots();
    } catch(e) {
      if(e.name==='AbortError')return;
      slots=[]; selectedSlot=null; fetching=false; renderSlots(); announceSelection(); slotsStatus.textContent='Не удалось загрузить время. Проверьте соединение и нажмите «Повторить».';
      const retry=document.createElement('button');retry.type='button';retry.className='policy-link';retry.textContent='Повторить';retry.addEventListener('click',()=>loadSlots());slotsStatus.append(' ',retry);
    } finally { if(controller===thisController){fetching=false;slotsBox.setAttribute('aria-busy','false');} }
  }
  function moveWeek(n) {
    if(posting)return; const target=shift(week,n*7); if(target<firstWeek||target>lastWeek)return;
    week=target;selectedDay=iso(week<today?today:week); selectedSlot=null;renderDays();announceSelection();loadSlots();
  }
  $('week-prev').addEventListener('click',()=>moveWeek(-1)); $('week-next').addEventListener('click',()=>moveWeek(1));
  master.addEventListener('change',()=>{selectedSlot=null;announceSelection();loadSlots();});
  document.querySelectorAll('[data-service]').forEach(el=>el.addEventListener('click',()=>{if(!posting)service.value=el.dataset.service;}));
  document.querySelectorAll('[data-master]').forEach(el=>el.addEventListener('click',()=>{if(!posting){master.value=el.dataset.master;master.dispatchEvent(new Event('change'));}}));
  function setPosting(value) {
    posting=value; submit.disabled=value;master.disabled=value;service.disabled=value;
    submit.textContent=value?'Сохраняем запись…':'Подтвердить запись ↗'; renderDays();
  }
  form.addEventListener('submit',async event=>{
    event.preventDefault(); if(posting)return; formStatus.textContent='';
    ['booking-name','booking-phone','booking-consent'].forEach(id=>$(id).removeAttribute('aria-invalid'));
    function invalid(id,message){$(id).setAttribute('aria-invalid','true');formStatus.textContent=message;$(id).focus();}
    if(!$('booking-consent').checked){invalid('booking-consent','Для записи нужно ваше согласие на обработку данных.');return;}
    const name=$('booking-name').value.trim(), phone=$('booking-phone').value.trim();
    if(name.length<2||name.length>100||! /^[\p{L}\p{M}\s’'-]+$/u.test(name)){invalid('booking-name','Введите имя: от 2 до 100 букв.');return;}
    if(!/^[+\d()\s-]{10,30}$/.test(phone)||phone.replace(/\D/g,'').length<10||phone.replace(/\D/g,'').length>15){invalid('booking-phone','Введите телефон с кодом страны, например +7 900 123-45-67.');return;}
    if(!selectedSlot||fetching){formStatus.textContent='Выберите свободное время для записи.';slotsBox.querySelector('button')?.focus();return;}
    const chosen={...selectedSlot};const detail=service.selectedOptions[0].text+' · '+master.selectedOptions[0].text+' · '+formatFull(chosen.startsAt)+' (Саратов, UTC+4)';
    const body=new URLSearchParams({slotId:chosen.id,serviceId:service.value,name,phone,consent:'1',sessid:form.dataset.sessid});
    controller?.abort();setPosting(true);
    try {
      const response=await fetch('/bitrix/services/main/ajax.php?action=bxmax:booking.api.bookings.create',{method:'POST',body,credentials:'same-origin'});
      const json=await response.json();
      if(json.status!=='success') {
        const error=json.errors?.[0];
        if(error?.code==='SLOT_TAKEN'){selectedSlot=null;await loadSlots();announceSelection();}
        throw new Error(error?.message||'Запись не сохранилась. Попробуйте ещё раз.');
      }
      form.hidden=true;$('booking-success').hidden=false;$('success-detail').textContent='Запись №'+json.data.bookingId+' · '+detail;
      $('booking-success').focus();slots=slots.map(s=>s.id===chosen.id?{...s,status:'taken'}:s);selectedSlot=null;renderSlots();
    } catch(e) {formStatus.textContent=e.message||'Нет связи с сервером. Проверьте соединение.';formStatus.focus();}
    finally {setPosting(false);}
  });
  $('booking-again').addEventListener('click',()=>{
    $('booking-success').hidden=true;form.hidden=false;$('booking-name').value='';$('booking-phone').value='';$('booking-consent').checked=false;
    formStatus.textContent='';selectedSlot=null;announceSelection();loadSlots();service.focus();
  });
  document.addEventListener('visibilitychange',()=>{if(!document.hidden&&!posting&&!form.hidden)loadSlots(true);});
  setInterval(()=>{if(!document.hidden&&!posting&&!fetching&&!form.hidden)loadSlots(true);},15000);
  renderDays();loadSlots();
})();
