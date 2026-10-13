<?php declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var CMain $APPLICATION */
/** @var CUser $USER */

use Bitrix\Main\Page\Asset;

$asset = Asset::getInstance();
$asset->addCss(SITE_TEMPLATE_PATH . '/assets/css/fonts.css');
$asset->addCss(SITE_TEMPLATE_PATH . '/assets/css/landing.css');
$asset->addJs(SITE_TEMPLATE_PATH . '/assets/js/landing.js');
?><!DOCTYPE html>
<html lang="<?= LANGUAGE_ID ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $APPLICATION->ShowTitle(); ?></title>
    <?php $APPLICATION->ShowHead(); ?>
</head>
<body class="home inner-page-header">
<?php if ($USER->IsAdmin()): ?>
    <div class="bx-admin-panel-wrap">
        <?php $APPLICATION->ShowPanel(); ?>
    </div>
<?php endif; ?>

<a class="skip-link" href="#booking">Перейти сразу к онлайн-записи</a>

<!-- Header Start (воспроизводит .header референса Aesthetic Lab) -->
<header class="header" id="top">
    <div class="container">
        <nav class="navbar" aria-label="Основная навигация">
            <a class="navbar-brand" href="#top" aria-label="Лак и Точка — наверх страницы">
                <svg class="brand-logo-svg" width="210" height="44" viewBox="0 0 210 44" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Лак&amp;Точка">
                    <text x="0" y="27" fill="#000000" font-family="'Playfair Display', Georgia, serif" font-size="25" letter-spacing="2.5">ЛАК &amp; ТОЧКА</text>
                    <text x="2" y="40" fill="#000000" font-family="'Manrope', sans-serif" font-size="8.5" font-weight="500" letter-spacing="4.2">NAIL &amp; HAND ATELIER</text>
                </svg>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Открыть меню навигации"
                data-nav-toggler
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="navbar-collapse" id="navbarSupportedContent" data-nav-collapse>
                <ul class="navbar-nav">
                    <li class="nav-item"><a href="#reviews" class="nav-link">Отзывы</a></li>
                    <li class="nav-item"><a href="#services" class="nav-link">Услуги и цены</a></li>
                    <li class="nav-item"><a href="#masters" class="nav-link">Мастера</a></li>
                    <li class="nav-item"><a href="#gallery" class="nav-link">Галерея работ</a></li>
                    <li class="nav-item"><a href="#booking" class="nav-link">Онлайн-запись</a></li>
                    <li class="nav-item"><a href="#contacts" class="nav-link">Контакты</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>
<!-- Header End -->

<main class="content" id="main-content">
