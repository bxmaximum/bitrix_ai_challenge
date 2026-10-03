<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @global CMain $APPLICATION */

$APPLICATION->SetTitle('Лак&Точка — студия маникюра в центре Москвы, онлайн-запись');
$APPLICATION->SetPageProperty('description', 'Студия маникюра и педикюра «Лак&Точка»: четыре мастера, стойкий гель-лак, авторский дизайн. Выберите время на сайте — запись за минуту.');

$img = SITE_TEMPLATE_PATH . '/img/';

$gallery = [
    ['gallery-01.jpg', 'Нейтральные ногти с мерцающим покрытием на сером текстиле'],
    ['gallery-02.jpg', 'Длинные ногти с градиентом и золотой фольгой, кольца на пальцах'],
    ['gallery-03.jpg', 'Чёрные ногти с золотым узором на фоне серого свитера'],
    ['gallery-04.jpg', 'Молочный френч с мраморными акцентами'],
    ['gallery-05.jpg', 'Голубой градиент на квадратных ногтях'],
    ['gallery-06.jpg', 'Тёмно-синий, золотой и белый дизайн в руках на светлом свитере'],
    ['gallery-07.jpg', 'Зимний дизайн с рисунком дерева на ногтях'],
    ['gallery-08.jpg', 'Красные ногти с надписью love'],
    ['gallery-09.jpg', 'Яркие цветные акценты на коротких ногтях'],
    ['gallery-10.jpg', 'Кольцо и аккуратный нюдовый маникюр'],
];

$reviews = [
    ['Алина К.', 'Маникюр с гель-лаком', 'Пришла «на пять минут проверить» и осталась в восторге. Форма идеальная, покрытие без пузырей, держится уже третью неделю. Отдельное спасибо за чай и тишину.'],
    ['Ирина М.', 'Авторский дизайн', 'Принесла картинку из интернета, Анастасия предложила упростить линии под мою длину — и получилось лучше оригинала. Теперь только к ней.'],
    ['Екатерина В.', 'Педикюр', 'Редко бывает, чтобы после педикюра ничего не болело. Аппаратная обработка аккуратная, кожа гладкая, цена совпала с той, что назвали при записи.'],
    ['Полина С.', 'Наращивание', 'Ногти были тонкие и ломались. Дарья сделала лёгкую форму, никакой «лопаты». Хожу уже второй месяц на коррекции — всё держится.'],
    ['Мария Т.', 'SPA-уход для рук', 'Записалась онлайн в 23:40, утром уже было сообщение. Парафин, массаж, чай — полтора часа без телефона. Лучший подарок себе.'],
];
?>

<section class="lt-hero" aria-labelledby="lt-hero-title" data-hero>
    <div class="lt-hero__sticky">
        <img class="lt-hero__img" src="<?= $img ?>hero.jpg" width="2000" height="1333" fetchpriority="high"
             alt="Мастер делает маникюр клиентке: руки с нежным покрытием на светлом столе">
        <div class="lt-hero__shade" aria-hidden="true"></div>
        <div class="lt-hero__bars" aria-hidden="true" data-bars></div>
        <div class="lt-container lt-hero__content">
            <h1 class="lt-hero__title" id="lt-hero-title">Маникюр, в&nbsp;котором<br>всё&nbsp;точно</h1>
            <p class="lt-hero__lead">Студия «Лак&amp;Точка» в центре Москвы: четыре мастера, стойкое покрытие и час, который принадлежит только вам.</p>
            <a class="lt-btn lt-btn--light" href="#booking" data-scroll>Записаться</a>
        </div>
    </div>
</section>

<section class="lt-intro" id="about" aria-labelledby="lt-intro-title">
    <div class="lt-intro__bg" aria-hidden="true"></div>
    <div class="lt-container lt-intro__inner">
        <p class="lt-kicker">Студия маникюра на Малой Ордынке</p>
        <svg class="lt-orn" aria-hidden="true" focusable="false"><use href="#lt-ornament"/></svg>
        <div class="lt-arch lt-intro__arch" data-reveal>
            <img src="<?= $img ?>studio-1.jpg" width="500" height="749" loading="lazy" decoding="async"
                 alt="Светлый зал студии: круглое зеркало, рабочий стол мастера и баночки с лаком">
        </div>
        <h2 class="lt-title lt-title--center" id="lt-intro-title" data-split>Красота — это внимание к&nbsp;деталям</h2>
        <p class="lt-text lt-text--center">Мы открылись девять лет назад с одной идеей: маникюр не должен быть спешкой. Один мастер — один гость. Инструменты вскрываются при вас, лак подбирается под тон кожи, а&nbsp;пока сохнет покрытие, вы пьёте чай и ни&nbsp;о чём не думаете.</p>
        <a class="lt-btn lt-btn--dark" href="#services" data-scroll>Смотреть услуги</a>
    </div>
</section>

<section class="lt-services" id="services" aria-labelledby="lt-services-title">
    <div class="lt-container">
        <svg class="lt-orn lt-orn--wide" aria-hidden="true" focusable="false"><use href="#lt-ornament-wide"/></svg>
        <h2 class="lt-title" id="lt-services-title" data-split>Услуги, продуманные до&nbsp;мелочей</h2>
        <p class="lt-subtitle">От классического ухода до авторского дизайна — цены без сюрпризов</p>
    </div>

    <div class="lt-carousel" data-carousel role="region" aria-roledescription="карусель" aria-label="Услуги студии">
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'services', [
            'IBLOCK_TYPE' => 'lak_tochka',
            'IBLOCK_ID' => lt_iblock_id('services'),
            'NEWS_COUNT' => 20,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'SORT_BY2' => 'ID',
            'SORT_ORDER2' => 'ASC',
            'FIELD_CODE' => ['PREVIEW_PICTURE', 'PREVIEW_TEXT'],
            'PROPERTY_CODE' => ['PRICE', 'DURATION'],
            'CHECK_DATES' => 'N',
            'SET_TITLE' => 'N',
            'SET_BROWSER_TITLE' => 'N',
            'SET_META_KEYWORDS' => 'N',
            'SET_META_DESCRIPTION' => 'N',
            'SET_LAST_MODIFIED' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'HIDE_LINK_WHEN_NO_DETAIL' => 'Y',
            'DISPLAY_TOP_PAGER' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => 3600,
            'CACHE_GROUPS' => 'N',
        ]); ?>
        <div class="lt-container lt-carousel__nav">
            <a class="lt-btn lt-btn--dark" href="#booking" data-scroll>Выбрать услугу и время</a>
            <div class="lt-arrows">
                <button class="lt-arrow lt-arrow--prev" type="button" data-prev aria-label="Назад"><svg aria-hidden="true" focusable="false"><use href="#lt-arrow"/></svg></button>
                <button class="lt-arrow" type="button" data-next aria-label="Вперёд"><svg aria-hidden="true" focusable="false"><use href="#lt-arrow"/></svg></button>
            </div>
        </div>
    </div>
</section>

<section class="lt-details" aria-labelledby="lt-details-title" data-pin>
    <div class="lt-details__stage" data-pin-stage>
        <img class="lt-watermark" src="<?= $img ?>mark.svg" width="520" height="420" alt="" aria-hidden="true">
        <div class="lt-details__center lt-container">
            <svg class="lt-orn" aria-hidden="true" focusable="false"><use href="#lt-ornament"/></svg>
            <h2 class="lt-title lt-title--center lt-title--xl" id="lt-details-title" data-split>Каждая деталь — удовольствие</h2>
            <p class="lt-text lt-text--center">Мягкий свет, тишина, стерильность без запаха «больницы», удобные кресла и&nbsp;мастера, которые не&nbsp;торопятся. Мы продумали студию так, чтобы вам не&nbsp;хотелось уходить.</p>
            <ul class="lt-facts" aria-label="Студия в цифрах">
                <li><strong>9 лет</strong><span>на рынке</span></li>
                <li><strong>12&nbsp;000+</strong><span>маникюров</span></li>
                <li><strong>4</strong><span>мастера</span></li>
            </ul>
        </div>
        <div class="lt-float lt-float--a lt-arch" data-float data-in="0.02" data-out="0.55"><img src="<?= $img ?>studio-2.jpg" width="320" height="480" loading="lazy" decoding="async" alt="Полки с лаками для ногтей на золотистых стеллажах"></div>
        <div class="lt-float lt-float--b" data-float data-in="0.12" data-out="0.68"><img src="<?= $img ?>studio-3.jpg" width="320" height="320" loading="lazy" decoding="async" alt="Набор накладных ногтей нюдовых оттенков на белом фоне"></div>
        <div class="lt-float lt-float--c lt-arch" data-float data-in="0.28" data-out="0.85"><img src="<?= $img ?>studio-4.jpg" width="320" height="480" loading="lazy" decoding="async" alt="Кресло и ванночка в педикюрной зоне студии"></div>
        <div class="lt-float lt-float--d" data-float data-in="0.42" data-out="0.98"><img src="<?= $img ?>gallery-10.jpg" width="320" height="320" loading="lazy" decoding="async" alt="Аккуратный нюдовый маникюр и золотое кольцо"></div>
    </div>
</section>

<section class="lt-escape" aria-labelledby="lt-escape-title">
    <div class="lt-container lt-escape__grid">
        <div class="lt-escape__photo" data-reveal>
            <img src="<?= $img ?>service-7.jpg" width="700" height="700" loading="lazy" decoding="async" alt="Рука с нежным маникюром держит цветок оранжевой календулы">
        </div>
        <div class="lt-escape__text">
            <svg class="lt-orn lt-orn--wide lt-orn--left" aria-hidden="true" focusable="false"><use href="#lt-ornament-wide"/></svg>
            <h2 class="lt-title" id="lt-escape-title" data-split>Ваш час&nbsp;для себя</h2>
            <p class="lt-text">Приходите на первый визит — мы подарим SPA-ритуал для рук к&nbsp;любой услуге от&nbsp;2&nbsp;000&nbsp;₽. Скажите администратору слово «точка» при записи или оставьте заметку к&nbsp;заявке.</p>
            <a class="lt-btn lt-btn--dark" href="#booking" data-scroll>Выбрать время</a>
        </div>
    </div>
</section>

<section class="lt-gallery" id="gallery" aria-labelledby="lt-gallery-title">
    <div class="lt-container">
        <h2 class="lt-title" id="lt-gallery-title" data-split>Работы наших мастеров</h2>
    </div>
    <div class="lt-carousel lt-carousel--tiles" data-carousel role="region" aria-roledescription="карусель" aria-label="Галерея работ">
        <ul class="lt-tiles" data-track>
            <?php foreach ($gallery as [$file, $alt]): ?>
                <li class="lt-tile" data-slide><img src="<?= $img . $file ?>" width="520" height="520" loading="lazy" decoding="async" alt="<?= htmlspecialchars($alt) ?>"></li>
            <?php endforeach; ?>
        </ul>
        <div class="lt-container lt-carousel__nav">
            <p class="lt-handle">@laktochka.studio <span class="lt-handle__note">— свежие работы каждую неделю</span></p>
            <div class="lt-arrows">
                <button class="lt-arrow lt-arrow--prev" type="button" data-prev aria-label="Назад"><svg aria-hidden="true" focusable="false"><use href="#lt-arrow"/></svg></button>
                <button class="lt-arrow" type="button" data-next aria-label="Вперёд"><svg aria-hidden="true" focusable="false"><use href="#lt-arrow"/></svg></button>
            </div>
        </div>
    </div>
</section>

<section class="lt-masters-section" id="masters" aria-labelledby="lt-masters-title">
    <div class="lt-container">
        <svg class="lt-orn lt-orn--wide" aria-hidden="true" focusable="false"><use href="#lt-ornament-wide"/></svg>
        <h2 class="lt-title" id="lt-masters-title" data-split>Мастера, которым доверяют руки</h2>
        <p class="lt-subtitle">Каждый специализируется на своём — выбирайте по задаче или по настроению</p>
        <?php $APPLICATION->IncludeComponent('bitrix:news.list', 'masters', [
            'IBLOCK_TYPE' => 'lak_tochka',
            'IBLOCK_ID' => lt_iblock_id('masters'),
            'NEWS_COUNT' => 12,
            'SORT_BY1' => 'SORT',
            'SORT_ORDER1' => 'ASC',
            'SORT_BY2' => 'ID',
            'SORT_ORDER2' => 'ASC',
            'FIELD_CODE' => ['PREVIEW_PICTURE', 'PREVIEW_TEXT'],
            'PROPERTY_CODE' => ['SPECIALIZATION', 'EXPERIENCE'],
            'CHECK_DATES' => 'N',
            'SET_TITLE' => 'N',
            'SET_BROWSER_TITLE' => 'N',
            'SET_META_KEYWORDS' => 'N',
            'SET_META_DESCRIPTION' => 'N',
            'SET_LAST_MODIFIED' => 'N',
            'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
            'ADD_SECTIONS_CHAIN' => 'N',
            'HIDE_LINK_WHEN_NO_DETAIL' => 'Y',
            'DISPLAY_TOP_PAGER' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => 3600,
            'CACHE_GROUPS' => 'N',
        ]); ?>
    </div>
</section>

<section class="lt-reviews" id="reviews" aria-labelledby="lt-reviews-title" data-pin>
    <div class="lt-reviews__stage" data-pin-stage>
        <img class="lt-watermark lt-watermark--right" src="<?= $img ?>mark.svg" width="520" height="420" alt="" aria-hidden="true">
        <div class="lt-reviews__center lt-container">
            <h2 class="lt-title lt-title--center lt-title--xl" id="lt-reviews-title" data-split>Нам доверяют — вот что говорят гости</h2>
            <svg class="lt-orn" aria-hidden="true" focusable="false"><use href="#lt-ornament"/></svg>
        </div>
        <ul class="lt-quotes">
            <?php foreach ($reviews as $i => [$author, $service, $text]): ?>
                <li class="lt-quote lt-quote--<?= $i + 1 ?>" data-float data-in="<?= [0.0, 0.14, 0.3, 0.46, 0.6][$i] ?>" data-out="<?= [0.5, 0.64, 0.8, 0.96, 1.0][$i] ?>">
                    <figure>
                        <blockquote><p><?= htmlspecialchars($text) ?></p></blockquote>
                        <figcaption><span class="lt-quote__name"><?= htmlspecialchars($author) ?></span><span class="lt-quote__service"><?= htmlspecialchars($service) ?></span></figcaption>
                    </figure>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="lt-booking" aria-label="Онлайн-запись">
    <?php if (\Bitrix\Main\Loader::includeModule('bxmax.booking')): ?>
        <?php $APPLICATION->IncludeComponent('bxmax:booking.form', '.default', [
            'ANCHOR_ID' => 'booking',
            'WEEKS_AHEAD' => 2,
        ]); ?>
    <?php else: ?>
        <div class="lt-container lt-booking__fallback" id="booking">
            <h2 class="lt-title lt-title--center">Запишитесь по&nbsp;телефону</h2>
            <p class="lt-text lt-text--center">Онлайн-запись временно недоступна. Позвоните нам: <a href="tel:+74951234567">+7&nbsp;(495)&nbsp;123-45-67</a> — подберём удобное время.</p>
        </div>
    <?php endif; ?>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
