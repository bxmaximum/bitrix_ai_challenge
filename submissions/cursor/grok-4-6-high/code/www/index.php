<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Application\Service\CatalogService;

/** @var CMain $APPLICATION */

$APPLICATION->SetTitle('Лак&Точка — студия маникюра');
$APPLICATION->SetPageProperty('description', 'Студия маникюра в Москве: гель, дизайн, SPA-педикюр. Онлайн-запись к мастеру.');

$tpl = SITE_TEMPLATE_PATH;
$services = [];
$masters = [];
if (Loader::includeModule('bxmax.booking')) {
    $catalog = ServiceLocator::getInstance()->get(CatalogService::class);
    $services = $catalog->getServices();
    $masters = $catalog->getMasters();
}

$reviews = [
    ['text' => 'Пришла с обгрызенными стрессами ногтями — ушла с формой, которой не стыдно в видеозвонке. Алина не уговаривает на наращивание, если можно спасти свои.', 'name' => 'Катя, копирайтер', 'meta' => 'гель-лак · Алина'],
    ['text' => 'Дизайн «как в карусели» обычно выглядит дешевле референса. У Марии — наоборот: спокойнее и дороже, без миллиона страз.', 'name' => 'Ира, архитектор', 'meta' => 'nail art · Мария'],
    ['text' => 'Педикюр без героизма и крови. Кира говорит, что будет, до того как начнёт, и держит слово. Редкость.', 'name' => 'Оля, продюсер', 'meta' => 'SPA-педикюр · Кира'],
    ['text' => 'Японский уход вместо плёнки. Ногти наконец перестали слоиться через неделю после снятия. Записываюсь к Софье раз в три недели, как к стоматологу — без драмы.', 'name' => 'Лена, юрист', 'meta' => 'японский · Софья'],
    ['text' => 'Запись с телефона за две минуты, слот не «зависает». Администратор не звонит «подтвердить», если я уже подтвердила формой.', 'name' => 'Настя, продакт', 'meta' => 'онлайн-запись'],
];
?>

<section class="hero" id="offer" style="background-image:url('<?= htmlspecialcharsbx($tpl) ?>/images/hero.jpg')">
    <div class="hero__inner">
        <h1 class="hero__word">Лак&amp;Точка</h1>
        <a class="btn hero__cta" href="#booking">Записаться</a>
        <div class="hero__bottom">
            <svg class="hero__feather" viewBox="0 0 72 18" aria-hidden="true">
                <path d="M2 9 C18 2, 36 2, 70 9" fill="none" stroke="currentColor" stroke-width="1"/>
                <path d="M10 9 L14 4 M18 9 L22 3 M26 9 L30 4 M34 9 L38 3" stroke="currentColor" stroke-width="1"/>
            </svg>
            <p class="hero__tag">Unwind, restore, and reconnect</p>
            <a class="hero__scroll" href="#about">Scroll</a>
        </div>
    </div>
</section>

<section class="arch" id="about">
    <h2 class="arch__title reveal">Студия маникюра, где цвет держится, а пальцы не устают от «идеала»</h2>
</section>

<section class="section section--olive">
    <p class="kicker reveal">О студии</p>
    <h2 class="serif reveal">Представьте кабинет, где тишина не из колонок, а потому что мастеру не нужно орать через фрезер</h2>
    <p class="lede reveal">На Никитской, без витрины «акция 990». Четыре мастера, стерилка на виду, лаки с составом, который можно прочитать без лупы.</p>
    <div class="round-photo reveal">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/about.jpg" alt="Мастер делает маникюр: близкий кадр рук и покрытия">
    </div>
</section>

<section class="section section--olive">
    <div class="split reveal">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-4.jpg" alt="Рабочее место студии: стол, лампа, инструменты">
        <div class="split__grid">
            <div>
                <h3>Past wisdom</h3>
                <p>Классика и японский уход — когда свои ногти ещё можно спасти, а не прятать под акрилом.</p>
            </div>
            <div>
                <h3>Modern experiences</h3>
                <p>Гель, втирка, тонкий арт. Форма, которая живёт в клавиатуре, а не только в сторис.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--olive" id="services">
    <p class="kicker reveal">Treatments</p>
    <h2 class="serif reveal">Лак&amp;Точка <em>меню</em></h2>
    <p class="lede reveal">Восемь позиций — без скрытых «допов за форму». Цена на карточке, длительность в слоте.</p>
    <div class="cards">
        <?php foreach ($services as $service): ?>
            <article class="card reveal">
                <?php if ($service['picture'] !== ''): ?>
                    <img src="<?= htmlspecialcharsbx($service['picture']) ?>" alt="<?= htmlspecialcharsbx($service['name']) ?>">
                <?php endif; ?>
                <div class="card__body">
                    <h3><?= htmlspecialcharsbx($service['name']) ?></h3>
                    <p><?= htmlspecialcharsbx($service['previewText']) ?></p>
                    <p class="price"><?= (int)$service['price'] ?> ₽ · <?= (int)$service['duration'] ?> мин</p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--olive" id="gallery">
    <p class="kicker reveal">Gallery</p>
    <h2 class="serif reveal">Step into tranquillity</h2>
    <p class="lede reveal">Не сток «идеальных пальцев», а то, что реально уходит из кабинета. Свет — дневной, без жёлтой лампы.</p>
    <div class="gallery reveal">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-1.jpg" alt="Гель-лак на короткой квадратной форме">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-2.jpg" alt="Нюдовое покрытие и аккуратный кутикульный край">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-3.jpg" alt="Палитра лаков на столе мастера">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-4.jpg" alt="Инструменты и лампа в кабинете">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-5.jpg" alt="Френч с тонкой улыбкой">
        <img src="<?= htmlspecialcharsbx($tpl) ?>/images/gallery-6.jpg" alt="Дизайн с тонкой графикой на безымянном">
    </div>
</section>

<section class="section section--olive" id="masters">
    <p class="kicker reveal">Masters</p>
    <h2 class="serif reveal">Люди, к которым возвращаются</h2>
    <p class="lede reveal">Не «универсалы на всё». Специализация написана честно — чтобы вы не гадали в лифте.</p>
    <div class="cards">
        <?php foreach ($masters as $master): ?>
            <article class="card reveal">
                <?php if ($master['picture'] !== ''): ?>
                    <img src="<?= htmlspecialcharsbx($master['picture']) ?>" alt="Мастер <?= htmlspecialcharsbx($master['name']) ?>">
                <?php endif; ?>
                <div class="card__body">
                    <h3><?= htmlspecialcharsbx($master['name']) ?></h3>
                    <p><?= htmlspecialcharsbx($master['specialization']) ?></p>
                    <p><?= htmlspecialcharsbx($master['previewText']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--olive" id="reviews">
    <p class="kicker reveal">Голоса</p>
    <h2 class="serif reveal">Не «всё супер», а что именно сработало</h2>
    <div class="reviews">
        <?php foreach ($reviews as $review): ?>
            <blockquote class="reveal">
                <p class="quote"><?= htmlspecialcharsbx($review['text']) ?></p>
                <p class="quote-meta"><?= htmlspecialcharsbx($review['name']) ?> · <?= htmlspecialcharsbx($review['meta']) ?></p>
            </blockquote>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--olive" id="booking">
    <?php
    $APPLICATION->IncludeComponent('bxmax:booking.form', '.default', [], false);
    ?>
</section>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
