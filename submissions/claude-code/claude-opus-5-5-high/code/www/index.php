<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @var CMain $APPLICATION */
$APPLICATION->SetTitle('Лак&Точка — студия маникюра на Покровке');
$APPLICATION->SetPageProperty('description', 'Маникюр, педикюр и наращивание в студии «Лак&Точка» на Покровке. Стерильные инструменты, 120 оттенков, онлайн-запись к мастеру на удобное время.');

$tpl = SITE_TEMPLATE_PATH;
?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero__media" data-parallax="0.18">
        <img class="hero__img" src="<?= $tpl ?>/images/hero.jpg"
             alt="Гостья студии в светлом льняном костюме сидит на белом стуле на фоне тёплой терракотовой стены"
             width="2200" height="2750" fetchpriority="high">
    </div>
    <div class="hero__content container">
        <h1 class="hero__title hero-in" id="hero-title">
            Маникюр, который <em>держится</em> три&nbsp;недели — и <em>радует</em> каждый день.
        </h1>
        <p class="hero__text hero-in">Студия на Покровке: стерильные инструменты, 120 оттенков и мастера, которые не&nbsp;торопятся.</p>
        <div class="hero__actions hero-in">
            <a class="pill pill--cream" href="#booking">Записаться</a>
            <a class="pill pill--light" href="#services">Услуги и цены</a>
        </div>
    </div>
</section>

<section class="welcome" aria-label="Добро пожаловать">
    <p class="welcome__title display-xl">
        <span class="welcome__line welcome__line--1" data-slide="-14">Добро пожаловать в «Лак&amp;Точку»,</span>
        <span class="welcome__line welcome__line--2" data-slide="16">где маникюр — это <em>ритуал</em>…</span>
    </p>
    <div class="collage">
        <figure class="collage__item collage__item--a" data-parallax="-0.06">
            <img src="<?= $tpl ?>/images/studio-1.jpg" alt="Мастер полирует ногти гостьи за рабочим столом у окна" width="1200" height="800" loading="lazy">
        </figure>
        <figure class="collage__item collage__item--b" data-parallax="0.04">
            <img src="<?= $tpl ?>/images/studio-2.jpg" alt="Мастер наносит гель-лак на ногти гостьи, рядом флаконы цветов" width="900" height="1600" loading="lazy">
        </figure>
        <figure class="collage__item collage__item--c" data-parallax="0.14">
            <img src="<?= $tpl ?>/images/studio-4.jpg" alt="Флаконы лака в оттенках мокко, олива и графит на розовом фоне" width="800" height="986" loading="lazy">
        </figure>
        <figure class="collage__item collage__item--d" data-parallax="-0.1">
            <img src="<?= $tpl ?>/images/studio-3.jpg" alt="Палитра лаков в серо-бежевой гамме на белом столе" width="800" height="1200" loading="lazy">
        </figure>
    </div>
</section>

<section class="about container" id="about" aria-labelledby="about-title">
    <h2 class="label section-label" id="about-title">О студии</h2>
    <p class="about__lead display-l" data-words>
        Мы открыли «Лак&amp;Точку», чтобы маникюр снова стал <em>часом для себя</em>, а&nbsp;не гонкой: без конвейера, без спешки и&nbsp;без&nbsp;компромиссов в&nbsp;стерильности.
    </p>
    <div class="about__body" data-reveal>
        <p>Четыре мастера, шесть мест у большого окна и автоклав, который работает весь день. Инструменты проходят три этапа обработки, пилки и бафы — одноразовые. На маникюр с покрытием мы закладываем полтора часа: успеваем сделать красиво и не торопить вас.</p>
        <a class="link-arrow" href="#masters">
            <span class="link-arrow__circle" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg></span>
            <span class="link-arrow__text">Познакомиться с мастерами</span>
        </a>
    </div>
</section>

<div class="fullbleed">
    <div class="fullbleed__media" data-parallax="0.12">
        <img src="<?= $tpl ?>/images/interior.jpg" alt="Светлый зал студии: белые стены, диван и сухоцветы в напольной вазе" width="2000" height="3000" loading="lazy">
    </div>
</div>

<?php
$APPLICATION->IncludeComponent('bitrix:news.list', 'services', [
    'IBLOCK_TYPE' => 'lak_content',
    'IBLOCK_ID' => 'services',
    'NEWS_COUNT' => 20,
    'SORT_BY1' => 'SORT',
    'SORT_ORDER1' => 'ASC',
    'SORT_BY2' => 'ID',
    'SORT_ORDER2' => 'ASC',
    'FIELD_CODE' => ['PREVIEW_TEXT'],
    'PROPERTY_CODE' => ['PRICE', 'DURATION', 'CATEGORY'],
    'CHECK_DATES' => 'Y',
    'CACHE_TYPE' => 'A',
    'CACHE_TIME' => 36000,
    'CACHE_FILTER' => 'N',
    'CACHE_GROUPS' => 'N',
    'SET_TITLE' => 'N',
    'SET_BROWSER_TITLE' => 'N',
    'SET_META_KEYWORDS' => 'N',
    'SET_META_DESCRIPTION' => 'N',
    'SET_LAST_MODIFIED' => 'N',
    'SET_STATUS_404' => 'N',
    'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
    'ADD_SECTIONS_CHAIN' => 'N',
    'DISPLAY_TOP_PAGER' => 'N',
    'DISPLAY_BOTTOM_PAGER' => 'N',
    'PREVIEW_TRUNCATE_LEN' => '',
    'ACTIVE_DATE_FORMAT' => 'd.m.Y',
    'HIDE_LINK_WHEN_NO_DETAIL' => 'Y',
], false, ['HIDE_ICONS' => 'N']);
?>

<section class="works" id="works" aria-labelledby="works-title">
    <div class="container works__intro">
        <h2 class="label section-label" id="works-title">Наши работы</h2>
        <figure class="works__card" data-reveal>
            <img src="<?= $tpl ?>/images/work-9.jpg" alt="Две руки с миндальным нюдовым маникюром и тонким кольцом на фоне сухой травы" width="900" height="1350" loading="lazy">
        </figure>
        <p class="works__lead display-l" data-reveal>
            Снимаем работы <em>при дневном свете</em> и без фильтров — вы видите ровно то, что унесёте с собой.
        </p>
        <a class="link-arrow link-arrow--center" href="#booking" data-reveal>
            <span class="link-arrow__circle" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg></span>
            <span class="link-arrow__text">Хочу так же</span>
        </a>
    </div>

    <div class="works__rail" data-hscroll>
        <div class="works__sticky">
            <ul class="works__track" data-hscroll-track tabindex="0" aria-label="Галерея работ: листайте горизонтально">
                <?php
                $works = [
                    ['work-1.jpg', 900, 1436, 'Короткие нюдовые ногти с розовым подтоном на белой ткани', 'Розовый нюд · Алина'],
                    ['work-2.jpg', 900, 1200, 'Миндальные ногти с тёплым розовым гель-лаком на белом полотенце', 'Пыльная роза · Ксения'],
                    ['work-12.jpg', 900, 1200, 'Типсы с образцами оттенков: шоколад, беж, молочный и шиммер', 'Палитра сезона'],
                    ['work-3.jpg', 900, 1350, 'Овальные ногти цвета какао с молоком и часы на запястье', 'Какао с молоком · Алина'],
                    ['work-5.jpg', 900, 1350, 'Миндальный маникюр в оттенке капучино с кольцом на мягком пледе', 'Капучино · Вероника'],
                    ['work-6.jpg', 900, 1200, 'Короткие ногти персикового цвета на фоне пушистого свитера', 'Персик · Дарья'],
                    ['work-7.jpg', 900, 1350, 'Длинные миндальные ногти полупрозрачного бежевого цвета', 'Наращивание «миндаль» · Вероника'],
                    ['work-8.jpg', 900, 1348, 'Молочный маникюр с тонким колечком на руке в чёрном рукаве', 'Молочный френч · Ксения'],
                    ['work-10.jpg', 900, 900, 'Сиреневые ногти с белыми и серебряными акцентами', 'Лаванда и глиттер · Вероника'],
                ];
                foreach ($works as $work): ?>
                    <li class="work">
                        <img class="work__img" src="<?= $tpl ?>/images/<?= $work[0] ?>" alt="<?= htmlspecialcharsbx($work[3]) ?>" width="<?= $work[1] ?>" height="<?= $work[2] ?>" loading="lazy">
                        <p class="work__caption"><?= htmlspecialcharsbx($work[4]) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<?php
$APPLICATION->IncludeComponent('bitrix:news.list', 'masters', [
    'IBLOCK_TYPE' => 'lak_content',
    'IBLOCK_ID' => 'masters',
    'NEWS_COUNT' => 10,
    'SORT_BY1' => 'SORT',
    'SORT_ORDER1' => 'ASC',
    'SORT_BY2' => 'ID',
    'SORT_ORDER2' => 'ASC',
    'FIELD_CODE' => ['PREVIEW_TEXT', 'PREVIEW_PICTURE'],
    'PROPERTY_CODE' => ['SPECIALIZATION', 'EXPERIENCE'],
    'CHECK_DATES' => 'Y',
    'CACHE_TYPE' => 'A',
    'CACHE_TIME' => 36000,
    'CACHE_FILTER' => 'N',
    'CACHE_GROUPS' => 'N',
    'SET_TITLE' => 'N',
    'SET_BROWSER_TITLE' => 'N',
    'SET_META_KEYWORDS' => 'N',
    'SET_META_DESCRIPTION' => 'N',
    'SET_LAST_MODIFIED' => 'N',
    'SET_STATUS_404' => 'N',
    'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
    'ADD_SECTIONS_CHAIN' => 'N',
    'DISPLAY_TOP_PAGER' => 'N',
    'DISPLAY_BOTTOM_PAGER' => 'N',
    'PREVIEW_TRUNCATE_LEN' => '',
    'HIDE_LINK_WHEN_NO_DETAIL' => 'Y',
], false, ['HIDE_ICONS' => 'N']);
?>

<section class="reviews container" id="reviews" aria-labelledby="reviews-title">
    <h2 class="label section-label" id="reviews-title">Отзывы гостей</h2>
    <ul class="reviews__list">
        <li class="review review--img-left">
            <figure class="review__img" data-parallax="-0.08">
                <img src="<?= $tpl ?>/images/review-1.jpg" alt="Рука с молочным маникюром и тонким кольцом на белом фоне" width="1000" height="667" loading="lazy">
            </figure>
            <figure class="review__body" data-reveal>
                <p class="review__label label" aria-hidden="true">Слово гостям</p>
                <blockquote class="review__quote">
                    <p>«Первый маникюр, который <em>дожил до отпуска</em> и вернулся из него без единого скола».</p>
                </blockquote>
                <figcaption class="review__author">— Ирина, маникюр с покрытием у Алины</figcaption>
            </figure>
        </li>
        <li class="review">
            <figure class="review__body" data-reveal>
                <p class="review__label label" aria-hidden="true">Слово гостям</p>
                <blockquote class="review__quote">
                    <p>«Ногти слоились у меня годами. После двух укреплений я <em>забыла, что так бывает</em>».</p>
                </blockquote>
                <figcaption class="review__author">— Марина, укрепление гелем</figcaption>
            </figure>
        </li>
        <li class="review review--img-right">
            <figure class="review__img" data-parallax="-0.08">
                <img src="<?= $tpl ?>/images/review-2.jpg" alt="Блестящий розовый маникюр с искристым покрытием крупным планом" width="1000" height="667" loading="lazy">
            </figure>
            <figure class="review__body" data-reveal>
                <p class="review__label label" aria-hidden="true">Слово гостям</p>
                <blockquote class="review__quote">
                    <p>«Вероника нарастила миндаль, который все принимают за мои ногти. <em>Лучший комплимент мастеру</em>».</p>
                </blockquote>
                <figcaption class="review__author">— Екатерина, наращивание</figcaption>
            </figure>
        </li>
        <li class="review">
            <figure class="review__body" data-reveal>
                <p class="review__label label" aria-hidden="true">Слово гостям</p>
                <blockquote class="review__quote">
                    <p>«Педикюр без замачивания звучал подозрительно, но Даша за час вернула мне <em>желание носить босоножки</em>».</p>
                </blockquote>
                <figcaption class="review__author">— Ольга, педикюр с покрытием</figcaption>
            </figure>
        </li>
        <li class="review">
            <figure class="review__body" data-reveal>
                <p class="review__label label" aria-hidden="true">Слово гостям</p>
                <blockquote class="review__quote">
                    <p>«Тихо, светло, пахнет кофе, а не ацетоном. Сюда хочется <em>возвращаться просто так</em>».</p>
                </blockquote>
                <figcaption class="review__author">— Анна, SPA-уход для рук</figcaption>
            </figure>
        </li>
    </ul>
</section>

<section class="booking container" id="booking" aria-labelledby="booking-title">
    <div class="booking__intro" data-reveal>
        <p class="label section-label" aria-hidden="true">Онлайн-запись</p>
        <h2 class="booking__title" id="booking-title">Запишитесь <em>онлайн</em>.</h2>
        <p class="booking__text">Выберите услугу, мастера и свободное окошко — подтвердим запись звонком накануне визита.</p>
        <ul class="tags" aria-label="Условия записи">
            <li class="tag">Без предоплаты</li>
            <li class="tag">Перенос за 3 часа</li>
            <li class="tag">Одноразовые пилки</li>
        </ul>
    </div>
    <div class="booking__panel">
        <?php if (\Bitrix\Main\ModuleManager::isModuleInstalled('bxmax.booking')): ?>
            <?php
            $APPLICATION->IncludeComponent('bxmax:booking.form', '', [
                'POLICY_URL' => SITE_DIR . 'policy/',
                'CACHE_TIME' => 3600,
            ]);
            ?>
        <?php else: ?>
            <p class="booking__fallback display-l">Онлайн-запись временно недоступна — позвоните нам: <a href="tel:+74951284012">+7 (495) 128-40-12</a>.</p>
        <?php endif; ?>
    </div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
