<?php

use Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var CMain $APPLICATION */

$asset = Asset::getInstance();
$asset->addJs(SITE_TEMPLATE_PATH . '/js/main.js');
$asset->addString('<link rel="preload" href="' . SITE_TEMPLATE_PATH . '/fonts/cormorant-cyr.woff2" as="font" type="font/woff2" crossorigin>');
$asset->addString('<link rel="preload" href="' . SITE_TEMPLATE_PATH . '/fonts/cormorant-italic-cyr.woff2" as="font" type="font/woff2" crossorigin>');
$asset->addString('<link rel="preload" href="' . SITE_TEMPLATE_PATH . '/fonts/opensans-cyr.woff2" as="font" type="font/woff2" crossorigin>');

$phone = '+7 (495) 128-40-12';
$phoneHref = 'tel:+74951284012';
$isMain = $APPLICATION->GetCurPage(false) === SITE_DIR;
$anchor = static fn (string $id): string => ($isMain ? '' : SITE_DIR) . '#' . $id;
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="<?= LANG_CHARSET ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#D5B8A6">
    <title><?php $APPLICATION->ShowTitle() ?></title>
    <?php $APPLICATION->ShowHead(); ?>
</head>
<body class="<?= $isMain ? 'is-landing' : 'is-inner' ?>">
<?php $APPLICATION->ShowPanel(); ?>
<a class="skip-link" href="#main">Перейти к содержанию</a>

<header class="site-header" data-header>
    <div class="site-header__inner">
        <a class="pill pill--header" href="<?= $anchor('booking') ?>">
            <span class="hide-sm">Записаться</span><span class="show-sm" aria-hidden="true">Запись</span>
        </a>
        <a class="logo" href="<?= SITE_DIR ?>" aria-label="Лак&amp;Точка — студия маникюра, на главную">
            <span class="logo__word" aria-hidden="true">Лак</span><span class="logo__amp" aria-hidden="true">&amp;</span><span class="logo__word" aria-hidden="true">Точка</span>
        </a>
        <div class="site-header__right">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" data-menu-open>
                <span class="menu-toggle__text">Меню</span>
                <span class="menu-toggle__lines" aria-hidden="true"></span>
            </button>
            <a class="circle-btn circle-btn--header hide-sm" href="<?= $phoneHref ?>" aria-label="Позвонить в студию: <?= $phone ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6.6 3.5h2.6l1.4 4-1.9 1.3a10.6 10.6 0 0 0 6.5 6.5l1.3-1.9 4 1.4v2.6a1.8 1.8 0 0 1-2 1.8A15.5 15.5 0 0 1 4.8 5.5a1.8 1.8 0 0 1 1.8-2Z" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>
</header>

<div class="site-menu" id="site-menu" role="dialog" aria-modal="true" aria-label="Меню сайта" hidden data-menu>
    <div class="site-menu__top">
        <a class="pill" href="<?= $anchor('booking') ?>" data-menu-link>Записаться</a>
        <span class="logo logo--static" aria-hidden="true"><span class="logo__word">Лак</span><span class="logo__amp">&amp;</span><span class="logo__word">Точка</span></span>
        <button class="menu-toggle is-open" type="button" data-menu-close>
            <span class="menu-toggle__text">Закрыть</span>
            <span class="menu-toggle__lines" aria-hidden="true"></span>
        </button>
    </div>
    <div class="site-menu__body">
        <div class="site-menu__arch">
            <img src="<?= SITE_TEMPLATE_PATH ?>/images/hands.jpg" alt="Руки с нюдовым маникюром на белом меху" width="1200" height="675" loading="lazy">
        </div>
        <nav class="site-menu__nav" aria-label="Разделы лендинга">
            <ul class="dot-list">
                <li><a href="<?= $anchor('services') ?>" data-menu-link>Услуги и цены</a></li>
                <li><a href="<?= $anchor('works') ?>" data-menu-link>Работы</a></li>
                <li><a href="<?= $anchor('masters') ?>" data-menu-link>Мастера</a></li>
                <li><a href="<?= $anchor('about') ?>" data-menu-link>О студии</a></li>
                <li><a href="<?= $anchor('reviews') ?>" data-menu-link>Отзывы</a></li>
                <li><a href="<?= $anchor('booking') ?>" data-menu-link>Онлайн-запись</a></li>
                <li><a href="<?= $anchor('contacts') ?>" data-menu-link>Контакты</a></li>
            </ul>
        </nav>
    </div>
    <div class="site-menu__foot">
        <p class="site-menu__meta">Ежедневно 10:00–20:00<br>Москва, ул. Покровка, 31, стр. 2</p>
        <a class="site-menu__phone" href="<?= $phoneHref ?>"><?= $phone ?></a>
        <p class="site-menu__meta site-menu__meta--right"><a href="https://t.me/" rel="noopener" target="_blank">Telegram</a><br><a href="https://vk.com/" rel="noopener" target="_blank">ВКонтакте</a></p>
    </div>
</div>

<main id="main" tabindex="-1">
