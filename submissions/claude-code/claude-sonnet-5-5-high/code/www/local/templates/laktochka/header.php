<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Page\Asset;

/** @global CMain $APPLICATION */

$asset = Asset::getInstance();
$asset->addJs(SITE_TEMPLATE_PATH . '/js/landing.js');
$asset->addString('<link rel="preload" href="' . SITE_TEMPLATE_PATH . '/fonts/cormorant-500-cyr.woff2" as="font" type="font/woff2" crossorigin>');
$asset->addString('<link rel="preload" href="' . SITE_TEMPLATE_PATH . '/fonts/inter-400-cyr.woff2" as="font" type="font/woff2" crossorigin>');
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="<?= LANG_CHARSET ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#b69f64">
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<body class="lt" id="top">
<?php $APPLICATION->ShowPanel(); ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <!-- авторский орнамент: ромб-капля с точками -->
        <symbol id="lt-ornament" viewBox="0 0 40 72">
            <g fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 4c-5 5-5 10 0 15 5-5 5-10 0-15Z"/>
                <path d="M20 19c-8-1-12-5-14-10 7 0 12 3 14 10Zm0 0c8-1 12-5 14-10-7 0-12 3-14 10Z"/>
                <path d="M20 30 8 46l12 18 12-18L20 30Z"/>
                <path d="M20 30v34M8 46h24"/>
            </g>
            <g fill="currentColor"><circle cx="5" cy="27" r="1"/><circle cx="35" cy="27" r="1"/><circle cx="5" cy="66" r="1"/><circle cx="35" cy="66" r="1"/></g>
        </symbol>
        <symbol id="lt-ornament-wide" viewBox="0 0 120 36">
            <g fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 18 22 6l10 12-10 12L12 18Zm38 0L60 6l10 12-10 12-10-12Zm38 0 10-12 10 12-10 12-10-12Z"/>
                <path d="M32 18h18m20 0h18M2 18h10m96 0h10"/>
                <path d="M22 6v24M60 6v24m38-24v24"/>
            </g>
        </symbol>
        <symbol id="lt-arrow" viewBox="0 0 20 20"><path d="m7.5 4 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    </defs>
</svg>
<a class="lt-skip" href="#content">Перейти к содержимому</a>

<header class="lt-header" data-header>
    <div class="lt-header__bar">
        <a class="lt-logo" href="/" aria-label="Лак&Точка — на главную">
            <span class="lt-logo__mark" aria-hidden="true"><svg><use href="#lt-ornament"/></svg></span>
            <span class="lt-logo__text">Лак<span class="lt-logo__amp">&amp;</span>Точка</span>
            <span class="lt-logo__sub">студия маникюра</span>
        </a>
        <nav class="lt-nav" id="lt-nav" aria-label="Основное меню" data-nav>
            <ul class="lt-nav__list">
                <li><a href="#about">О студии</a></li>
                <li><a href="#services">Услуги</a></li>
                <li><a href="#gallery">Работы</a></li>
                <li><a href="#masters">Мастера</a></li>
                <li><a href="#reviews">Отзывы</a></li>
                <li><a href="#contacts">Контакты</a></li>
            </ul>
            <a class="lt-btn lt-btn--nav" href="#booking">Записаться</a>
        </nav>
        <button class="lt-burger" type="button" aria-expanded="false" aria-controls="lt-nav" data-burger>
            <span class="lt-burger__lines" aria-hidden="true"></span>
            <span class="lt-sr">Меню</span>
        </button>
    </div>
</header>

<main id="content" tabindex="-1">
