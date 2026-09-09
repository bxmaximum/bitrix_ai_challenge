<?php

declare(strict_types=1);

define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss('/local/assets/lak-tochka/site.css');
Asset::getInstance()->addJs('/local/assets/lak-tochka/site.js');
$APPLICATION->SetTitle('Лак&Точка — студия маникюра в Саратове');
$APPLICATION->SetPageProperty('description', 'Бережный маникюр и педикюр в камерной студии. Онлайн-запись к мастеру на удобное время.');
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#d0b09c">
    <title><?php $APPLICATION->ShowTitle() ?></title>
    <?php $APPLICATION->ShowHead() ?>
</head>
<body>
<?php $APPLICATION->ShowPanel() ?>
<a class="skip-link" href="#main">К основному содержанию</a>
<header class="site-header" data-site-header>
    <a class="header-cta" href="#booking">Записаться</a>
    <a class="brand" href="#top" aria-label="Лак&Точка — на главную"><span>Л</span><i>•</i></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-menu-toggle>Меню <span aria-hidden="true"></span></button>
    <nav class="site-nav" id="site-nav" aria-label="Основная навигация" data-menu>
        <a href="#services">Услуги</a><a href="#works">Работы</a><a href="#masters">Мастера</a><a href="#reviews">Отзывы</a><a href="#contacts">Контакты</a>
    </nav>
</header>

<main id="main">
    <section class="hero" id="top" aria-labelledby="hero-title">
        <div class="hero__media" aria-hidden="true"></div><div class="hero__shade"></div>
        <div class="hero__content">
            <p class="hero__kicker">Камерная nail-студия · Саратов</p>
            <h1 id="hero-title">Точка, где<br>начинается <em>красота</em></h1>
            <div class="hero__bottom">
                <p>Бережный маникюр, безупречная форма и час, который принадлежит только вам.</p>
                <a class="hero__button" href="#booking">Выбрать время <span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <span class="hero__scroll" aria-hidden="true">Листайте</span>
    </section>

    <?php if (\Bitrix\Main\Loader::includeModule('bxmax.booking')): ?>
        <?php $APPLICATION->IncludeComponent('bxmax:booking', '.default', [], false); ?>
    <?php else: ?>
        <section class="section-shell module-note" role="status">Онлайн-запись временно недоступна. Позвоните нам: <a href="tel:+78452260026">+7 8452 26-00-26</a>.</section>
    <?php endif; ?>

    <section class="contacts" id="contacts" aria-labelledby="contacts-title">
        <div class="contacts__image reveal"><img src="/local/assets/lak-tochka/images/work-5.jpg" alt="Интерьер и детали уютной студии маникюра" loading="lazy"></div>
        <div class="contacts__content reveal">
            <span class="eyebrow">Ждём вас</span><h2 id="contacts-title">Приходите<br><em>за настроением</em></h2>
            <div class="contacts__grid">
                <div><span>Адрес</span><p>Саратов, ул. Волжская, 28<br>вход со стороны набережной</p></div>
                <div><span>Часы</span><p>Ежедневно<br>10:00–20:00</p></div>
                <div><span>Телефон</span><a href="tel:+78452260026">+7 8452 26-00-26</a></div>
                <div><span>Написать</span><a href="mailto:hello@laktochka.ru">hello@laktochka.ru</a></div>
            </div>
            <a class="pill-button pill-button--light" href="#booking">Записаться онлайн</a>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="footer__marquee" aria-hidden="true"><span>Почувствуйте себя прекрасной • Почувствуйте себя прекрасной • </span></div>
    <div class="footer__bottom section-shell">
        <a class="footer__logo" href="#top">Лак<span>&</span>Точка</a><p>Маникюр без спешки.<br>Красота без лишнего.</p>
        <div><a href="#services">Услуги</a><a href="#masters">Команда</a><a href="#booking">Онлайн-запись</a></div>
        <div><a href="tel:+78452260026">+7 8452 26-00-26</a><a href="mailto:hello@laktochka.ru">hello@laktochka.ru</a></div>
        <small>© 2026 Лак&Точка</small>
    </div>
</footer>
</body>
</html>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php'; ?>
