<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Политика обработки персональных данных — Лак&Точка');
?>
<article class="policy container">
    <p class="label section-label">Документы</p>
    <h1 class="policy__title">Политика обработки <em>персональных данных</em></h1>
    <div class="policy__body">
        <p>ИП Соколова А. В. (студия маникюра «Лак&amp;Точка», Москва, ул. Покровка, 31, стр. 2) обрабатывает персональные данные посетителей сайта в соответствии с Федеральным законом № 152-ФЗ «О персональных данных».</p>
        <h2>Какие данные мы получаем</h2>
        <p>Имя и номер телефона, которые вы указываете в форме онлайн-записи, а также выбранные услугу, мастера и время визита. Вместе с заявкой мы сохраняем дату и время, когда вы дали согласие на обработку данных.</p>
        <h2>Зачем</h2>
        <p>Чтобы записать вас к мастеру, подтвердить или перенести визит и связаться с вами по поводу записи. Мы не передаём данные третьим лицам и не используем их для рекламных рассылок.</p>
        <h2>Сколько храним</h2>
        <p>Заявки хранятся не дольше трёх лет с даты визита, после чего удаляются. Вы можете отозвать согласие в любой момент — напишите на <a href="mailto:hello@lak-tochka.ru">hello@lak-tochka.ru</a> или позвоните по телефону <a href="tel:+74951284012">+7 (495) 128-40-12</a>, и мы удалим ваши данные в течение 10 рабочих дней.</p>
        <p><a class="link-arrow" href="<?= SITE_DIR ?>#booking"><span class="link-arrow__circle" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M20 12H4m6-6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg></span><span class="link-arrow__text">Вернуться к записи</span></a></p>
    </div>
</article>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
