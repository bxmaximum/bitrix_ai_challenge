<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

$asset = '/local/assets/lak-tochka/images/';
?>
<section class="about section-shell reveal" id="about" aria-labelledby="about-title">
    <div class="eyebrow">О студии</div>
    <div class="about__statement">
        <h2 id="about-title">Мы замедляем время, чтобы ваши руки выглядели <em>безупречно</em>.</h2>
        <p>«Лак&Точка» — камерная студия, где не торопят и не навязывают. Одноразовые наборы вскрываем при вас, инструменты проходят полный цикл стерилизации, а оттенок выбираем столько, сколько нужно.</p>
    </div>
    <div class="about__collage" aria-label="Атмосфера студии">
        <img src="<?= $asset ?>work-1.jpg" alt="Мастер аккуратно выполняет маникюр" class="about__image about__image--small" loading="lazy">
        <img src="<?= $asset ?>work-2.jpg" alt="Деталь нежного маникюра" class="about__image about__image--large" loading="lazy">
        <img src="<?= $asset ?>work-5.jpg" alt="Ухоженные ногти после процедуры" class="about__image about__image--tall" loading="lazy">
    </div>
</section>

<section class="services section-shell" id="services" aria-labelledby="services-title">
    <div class="section-heading reveal">
        <div>
            <span class="eyebrow">Наше меню</span>
            <h2 id="services-title">Ритуалы <em>заботы</em></h2>
        </div>
        <p>В стоимость уже входят одноразовый набор, стерильные инструменты и чашка хорошего чая.</p>
    </div>
    <div class="service-list">
        <?php foreach ($arResult['SERVICES'] as $index => $service): ?>
            <article class="service-card reveal" data-service-card="<?= (int)$service['ID'] ?>">
                <span class="service-card__number"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <div class="service-card__main">
                    <span class="service-card__duration"><?= htmlspecialcharsbx($service['DURATION']) ?></span>
                    <h3><?= htmlspecialcharsbx($service['NAME']) ?></h3>
                    <p><?= htmlspecialcharsbx($service['DESCRIPTION']) ?></p>
                </div>
                <div class="service-card__aside">
                    <strong><?= number_format((float)$service['PRICE'], 0, ',', ' ') ?> ₽</strong>
                    <button type="button" class="text-link js-pick-service" data-service-id="<?= (int)$service['ID'] ?>">Выбрать <span aria-hidden="true">↗</span></button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="gallery section-shell" id="works" aria-labelledby="works-title">
    <div class="section-heading reveal">
        <div><span class="eyebrow">Недавние работы</span><h2 id="works-title">Тихая <em>роскошь</em></h2></div>
        <p>Чистая форма, тонкое покрытие и дизайн, который живёт вместе с вашим гардеробом.</p>
    </div>
    <div class="gallery__grid">
        <figure class="gallery__item gallery__item--hero reveal"><img src="<?= $asset ?>work-3.jpg" alt="Глянцевый маникюр с черепаховым дизайном" loading="lazy"></figure>
        <figure class="gallery__item gallery__item--top reveal"><img src="<?= $asset ?>work-4.jpg" alt="Минималистичный дизайн ногтей" loading="lazy"></figure>
        <figure class="gallery__item gallery__item--bottom reveal"><img src="<?= $asset ?>work-2.jpg" alt="Маникюр в нежных оттенках" loading="lazy"></figure>
        <figure class="gallery__item gallery__item--wide reveal"><img src="<?= $asset ?>work-1.jpg" alt="Процесс профессионального маникюра" loading="lazy"></figure>
    </div>
</section>

<section class="masters section-shell" id="masters" aria-labelledby="masters-title">
    <div class="section-heading reveal">
        <div><span class="eyebrow">Команда</span><h2 id="masters-title">Ваши <em>мастера</em></h2></div>
        <p>У каждого свой почерк, но единый стандарт: деликатная работа и честный результат.</p>
    </div>
    <div class="masters__grid">
        <?php foreach ($arResult['MASTERS'] as $master): ?>
            <article class="master-card reveal">
                <div class="master-card__image"><img src="<?= htmlspecialcharsbx($master['IMAGE']) ?>" alt="Мастер <?= htmlspecialcharsbx($master['NAME']) ?>" loading="lazy"></div>
                <div class="master-card__meta"><span><?= htmlspecialcharsbx($master['EXPERIENCE']) ?> опыта</span><span>Лак&Точка</span></div>
                <h3><?= htmlspecialcharsbx($master['NAME']) ?></h3>
                <p class="master-card__specialty"><?= htmlspecialcharsbx($master['SPECIALTY']) ?></p>
                <p><?= htmlspecialcharsbx($master['DESCRIPTION']) ?></p>
                <button type="button" class="pill-button js-pick-master" data-master-id="<?= (int)$master['ID'] ?>">Записаться к мастеру</button>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="reviews" id="reviews" aria-labelledby="reviews-title">
    <div class="reviews__inner section-shell reveal">
        <span class="eyebrow">Love notes</span>
        <h2 id="reviews-title">«Здесь помнят, какой кофе я люблю, и каждый раз попадают <em>в настроение</em>»</h2>
        <div class="reviews__track" data-reviews>
            <article class="review is-active"><p>«Покрытие тонкое, у кутикулы идеально, спустя три недели ни одного скола. Аня — волшебница и очень спокойный человек».</p><span>Алина, постоянная гостья</span></article>
            <article class="review"><p>«Пришла с очень ломкими ногтями. За три визита София вернула им форму и длину — без “бетонного” слоя базы».</p><span>Марина, укрепление</span></article>
            <article class="review"><p>«Мира поняла моё “хочу что-то заметное, но тихое” с полуслова. Получился самый красивый микрофренч».</p><span>Елена, дизайн</span></article>
            <article class="review"><p>«Педикюр действительно комфортный: никакой спешки, всё очень чисто, стопы мягкие почти месяц».</p><span>Ольга, SMART-педикюр</span></article>
            <article class="review"><p>«Пришла перед свадьбой и впервые совсем не волновалась за руки. Получилось благородно и очень “моё”».</p><span>Дарья, свадебный маникюр</span></article>
        </div>
        <div class="reviews__controls" aria-label="Навигация по отзывам">
            <button type="button" class="round-button" data-review-prev aria-label="Предыдущий отзыв">←</button>
            <span data-review-count>01 / 05</span>
            <button type="button" class="round-button" data-review-next aria-label="Следующий отзыв">→</button>
        </div>
    </div>
</section>

<section class="booking" id="booking" aria-labelledby="booking-title" data-booking-root data-sessid="<?= htmlspecialcharsbx($arResult['SESSID']) ?>">
    <div class="booking__intro section-shell reveal">
        <span class="eyebrow">Онлайн-запись</span>
        <h2 id="booking-title">Ваш час <em>для себя</em></h2>
        <p>Выберите услугу, мастера и удобное время. Подтверждение появится сразу после отправки.</p>
    </div>
    <div class="booking__panel section-shell">
        <form class="booking-form" data-booking-form novalidate>
            <div class="booking-form__selectors">
                <label for="booking-service">Услуга</label>
                <select id="booking-service" name="serviceId" required>
                    <option value="">Выберите услугу</option>
                    <?php foreach ($arResult['SERVICES'] as $service): ?>
                        <option value="<?= (int)$service['ID'] ?>"><?= htmlspecialcharsbx($service['NAME']) ?> — <?= number_format((float)$service['PRICE'], 0, ',', ' ') ?> ₽</option>
                    <?php endforeach; ?>
                </select>

                <label for="booking-master">Мастер</label>
                <select id="booking-master" name="masterId" required>
                    <option value="">Выберите мастера</option>
                    <?php foreach ($arResult['MASTERS'] as $master): ?>
                        <option value="<?= (int)$master['ID'] ?>"><?= htmlspecialcharsbx($master['NAME']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="slot-picker" aria-labelledby="slot-title">
                <div class="slot-picker__head">
                    <div><span class="booking-step">02</span><h3 id="slot-title">Свободное время</h3></div>
                    <div class="slot-picker__week-controls">
                        <button type="button" class="round-button" data-week-prev aria-label="Предыдущая неделя">←</button>
                        <strong data-week-label>—</strong>
                        <button type="button" class="round-button" data-week-next aria-label="Следующая неделя">→</button>
                    </div>
                </div>
                <div class="slots" role="listbox" aria-label="Свободные слоты" data-slots>
                    <p class="slots__placeholder">Сначала выберите мастера.</p>
                </div>
                <input type="hidden" name="slotId" data-slot-input>
            </div>

            <div class="booking-form__details">
                <div><span class="booking-step">03</span><h3>Контакты</h3></div>
                <div class="field">
                    <label for="booking-name">Ваше имя</label>
                    <input id="booking-name" name="name" type="text" autocomplete="name" minlength="2" maxlength="120" required aria-describedby="booking-status">
                </div>
                <div class="field">
                    <label for="booking-phone">Телефон</label>
                    <input id="booking-phone" name="phone" type="tel" autocomplete="tel" placeholder="+7 900 000-00-00" pattern="[+0-9()\-\s]{7,32}" required aria-describedby="booking-status">
                </div>
                <label class="consent" for="booking-consent">
                    <input id="booking-consent" name="consent" type="checkbox" value="1" required>
                    <span>Согласна на обработку персональных данных для оформления записи</span>
                </label>
                <button class="submit-button" type="submit">Подтвердить запись <span aria-hidden="true">↗</span></button>
                <p id="booking-status" class="booking-status" data-booking-status role="status" aria-live="polite"></p>
            </div>
        </form>
        <div class="booking-success" data-booking-success hidden tabindex="-1" role="status" aria-live="polite">
            <span class="booking-success__mark" aria-hidden="true">✓</span>
            <span class="eyebrow">Запись подтверждена</span>
            <h3>До встречи в <em>«Лак&Точка»</em></h3>
            <div data-success-details></div>
            <a href="#contacts" class="pill-button">Как нас найти</a>
        </div>
    </div>
</section>
