<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Page\Asset;

/** @var CMain $APPLICATION */
global $APPLICATION;

$templateFolder = SITE_TEMPLATE_PATH;

Asset::getInstance()->addString(
	'<link rel="preload" href="' . $templateFolder . '/fonts/playfair-cyrillic.woff2" as="font" type="font/woff2" crossorigin>'
);
Asset::getInstance()->addString(
	'<link rel="preload" href="' . $templateFolder . '/fonts/inter-cyrillic.woff2" as="font" type="font/woff2" crossorigin>'
);
Asset::getInstance()->addJs($templateFolder . '/script.js');
?><!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="<?= LANG_CHARSET ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#ECE8DF">
	<?php $APPLICATION->ShowHead(); ?>
	<title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<a class="skip-link" href="#main">К основному содержанию</a>

<header class="site-header" id="siteHeader">
	<div class="site-header__inner">
		<a class="logo" href="/" aria-label="Лак&amp;Точка — на главную">
			<span class="logo__mark" aria-hidden="true">Л&amp;Т</span>
			<span class="logo__text">
				<span class="logo__name">Лак&amp;Точка</span>
				<span class="logo__sub">студия маникюра</span>
			</span>
		</a>

		<nav class="site-nav" id="siteNav" aria-label="Основная навигация">
			<ul class="site-nav__list">
				<li><a class="site-nav__link" href="#services">Услуги</a></li>
				<li><a class="site-nav__link" href="#works">Работы</a></li>
				<li><a class="site-nav__link" href="#masters">Мастера</a></li>
				<li><a class="site-nav__link" href="#reviews">Отзывы</a></li>
				<li><a class="site-nav__link" href="#contacts">Контакты</a></li>
			</ul>
		</nav>

		<a class="btn btn--pill site-header__cta" href="#booking">Записаться</a>

		<button class="burger" type="button" id="burger" aria-expanded="false" aria-controls="siteNav" aria-label="Открыть меню">
			<span class="burger__line" aria-hidden="true"></span>
			<span class="burger__line" aria-hidden="true"></span>
		</button>
	</div>
</header>

<main id="main">
