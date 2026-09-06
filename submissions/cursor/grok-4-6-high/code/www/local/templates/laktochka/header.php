<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

/** @var CMain $APPLICATION */

$asset = Asset::getInstance();
$asset->addCss(SITE_TEMPLATE_PATH . '/fonts.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/template_styles.css');
$asset->addJs(SITE_TEMPLATE_PATH . '/js/landing.js');
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<a class="skip-link" href="#content">К содержимому</a>
<div class="loader" data-loader aria-hidden="true">
    <div class="loader__shape">
        <p class="loader__quote">«Мир подождёт — возьмите час на себя.»</p>
    </div>
</div>
<header class="site-chrome">
    <div class="site-chrome__bar">
        <p class="site-chrome__eyebrow">Студия маникюра</p>
        <a class="site-chrome__mark" href="/" aria-label="Лак и Точка — на главную">
            <svg viewBox="0 0 40 24" width="40" height="24" aria-hidden="true">
                <path d="M4 20 L20 4 L36 20" fill="none" stroke="currentColor" stroke-width="1"/>
                <path d="M12 20 L20 10 L28 20" fill="none" stroke="currentColor" stroke-width="1"/>
            </svg>
        </a>
        <nav class="site-chrome__nav" aria-label="Разделы">
            <a href="#services">• Меню</a>
            <a href="#contacts">• Контакты</a>
        </nav>
    </div>
</header>
<button class="menu-fab" type="button" data-menu-open aria-expanded="false" aria-controls="site-menu">
    <span>Menu</span>
    <span class="menu-fab__bars" aria-hidden="true"></span>
</button>
<div class="site-menu" id="site-menu" hidden data-menu>
    <button class="site-menu__close" type="button" data-menu-close aria-label="Закрыть меню">Закрыть</button>
    <nav class="site-menu__nav">
        <a href="#offer">Записаться</a>
        <a href="#services">Услуги</a>
        <a href="#gallery">Галерея</a>
        <a href="#masters">Мастера</a>
        <a href="#reviews">Отзывы</a>
        <a href="#booking">Онлайн-запись</a>
        <a href="#contacts">Контакты</a>
        <a href="/privacy/">Персональные данные</a>
    </nav>
</div>
<main id="content">
