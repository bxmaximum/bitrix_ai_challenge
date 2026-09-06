<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

$weekStart = htmlspecialcharsbx((string)$arResult['WEEK_START']);
$sessid = htmlspecialcharsbx((string)$arResult['SESSID']);
?>
<section class="bk" data-booking
         data-week-start="<?= $weekStart ?>"
         data-sessid="<?= $sessid ?>"
         data-list-action="bxmax:booking.api.slots.list"
         data-create-action="bxmax:booking.api.bookings.create">
    <p class="bk__eyebrow">Онлайн-запись</p>
    <h2 class="bk__title">Your self-care <em>awaits</em></h2>
    <p class="bk__lead">Выберите услугу, мастера и свободный час — остальное мы возьмём на себя.</p>

    <form class="bk__form" data-booking-form novalidate>
        <input type="hidden" name="sessid" value="<?= $sessid ?>">

        <fieldset class="bk__fieldset">
            <legend class="bk__legend">Услуга</legend>
            <label class="bk__label" for="bk-service">Что делаем</label>
            <select class="bk__select" id="bk-service" name="serviceId" required data-service>
                <option value="">Выберите услугу</option>
                <?php foreach ($arResult['SERVICES'] as $service): ?>
                    <option value="<?= (int)$service['id'] ?>"><?= htmlspecialcharsbx((string)$service['name']) ?> — <?= (int)$service['price'] ?> ₽</option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <fieldset class="bk__fieldset">
            <legend class="bk__legend">Мастер</legend>
            <label class="bk__label" for="bk-master">К кому</label>
            <select class="bk__select" id="bk-master" name="masterId" required data-master>
                <option value="">Выберите мастера</option>
                <?php foreach ($arResult['MASTERS'] as $master): ?>
                    <option value="<?= (int)$master['id'] ?>"><?php
                        echo htmlspecialcharsbx((string)$master['name']);
                        if (!empty($master['specialization'])) {
                            echo ' — ' . htmlspecialcharsbx((string)$master['specialization']);
                        }
                    ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <fieldset class="bk__fieldset">
            <legend class="bk__legend">Слот</legend>
            <div class="bk__week">
                <button type="button" class="bk__week-btn" data-week-prev aria-label="Предыдущая неделя">←</button>
                <p class="bk__week-label" data-week-label></p>
                <button type="button" class="bk__week-btn" data-week-next aria-label="Следующая неделя">→</button>
            </div>
            <div class="bk__slots" role="listbox" aria-label="Свободные слоты" data-slots tabindex="0"></div>
            <p class="bk__hint">Стрелки листают слоты, Enter выбирает. Занятые остаются в списке, но недоступны.</p>
        </fieldset>

        <fieldset class="bk__fieldset">
            <legend class="bk__legend">Контакты</legend>
            <div class="bk__row">
                <p>
                    <label class="bk__label" for="bk-name">Имя</label>
                    <input class="bk__input" id="bk-name" name="name" type="text" autocomplete="name" required minlength="2">
                </p>
                <p>
                    <label class="bk__label" for="bk-phone">Телефон</label>
                    <input class="bk__input" id="bk-phone" name="phone" type="tel" autocomplete="tel" required placeholder="+7 900 000-00-00">
                </p>
            </div>
            <p class="bk__consent">
                <input id="bk-consent" name="consent" type="checkbox" value="1" required data-consent>
                <label for="bk-consent">Соглашаюсь на <a href="/privacy/">обработку персональных данных</a></label>
            </p>
        </fieldset>

        <p class="bk__status" role="status" aria-live="polite" data-status></p>

        <button class="bk__submit" type="submit" data-submit>Записаться</button>
    </form>

    <div class="bk__success" hidden data-success role="status" aria-live="polite">
        <h3>Вы записаны</h3>
        <p data-success-text></p>
        <button type="button" class="bk__submit" data-success-reset>Новая запись</button>
    </div>
</section>
