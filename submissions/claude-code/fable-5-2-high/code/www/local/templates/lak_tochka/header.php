<?php

use Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CMain $APPLICATION */

$asset = Asset::getInstance();
$asset->addJs(SITE_TEMPLATE_PATH . '/js/main.js');

// На главной ссылки меню — якоря; на остальных страницах они ведут на главную.
$home = $APPLICATION->GetCurPage(false) === SITE_DIR ? '' : SITE_DIR;
$menu = [
	'#about' => 'О студии',
	'#services' => 'Услуги',
	'#gallery' => 'Работы',
	'#masters' => 'Мастера',
	'#reviews' => 'Отзывы',
	'#contacts' => 'Контакты',
];
?><!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php $APPLICATION->ShowTitle() ?></title>
	<script>document.documentElement.className += ' js';</script>
	<link rel="preload" href="<?= SITE_TEMPLATE_PATH ?>/fonts/cormorant-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="<?= SITE_TEMPLATE_PATH ?>/fonts/inter-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="icon" href="<?= SITE_TEMPLATE_PATH ?>/img/favicon.svg" type="image/svg+xml">
	<meta name="theme-color" content="#8a7434">
	<?php $APPLICATION->ShowHead() ?>
</head>
<body>
<div id="panel"><?php $APPLICATION->ShowPanel() ?></div>

<a class="skip-link" href="#main">Перейти к содержимому</a>

<svg class="sprite" aria-hidden="true" focusable="false" width="0" height="0">
	<symbol id="orn" viewBox="0 0 113 35">
		<g fill="none" stroke="currentColor" stroke-width="1">
			<path d="M56.5 3.5 68 17.5 56.5 31.5 45 17.5Z"/>
			<path d="M34 8.5 41 17.5 34 26.5 27 17.5Z"/>
			<path d="M79 8.5 86 17.5 79 26.5 72 17.5Z"/>
			<path d="M15 12.5 19 17.5 15 22.5 11 17.5Z"/>
			<path d="M98 12.5 102 17.5 98 22.5 94 17.5Z"/>
			<path d="M41 17.5h4M68 17.5h4M19 17.5h8M86 17.5h8M0 17.5h11M102 17.5h11"/>
		</g>
		<circle cx="56.5" cy="17.5" r="2" fill="currentColor"/>
	</symbol>
	<symbol id="orn-v" viewBox="0 0 35 66">
		<g fill="none" stroke="currentColor" stroke-width="1">
			<path d="M17.5 2 28 15 17.5 28 7 15Z"/>
			<path d="M17.5 34 26 44.5 17.5 55 9 44.5Z"/>
			<path d="M17.5 28v6M17.5 55v9"/>
		</g>
		<circle cx="17.5" cy="15" r="2" fill="currentColor"/>
		<circle cx="17.5" cy="44.5" r="1.5" fill="currentColor"/>
	</symbol>
	<symbol id="arrow-left" viewBox="0 0 24 24">
		<path d="M14.5 5.5 8 12l6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
	</symbol>
	<symbol id="arrow-right" viewBox="0 0 24 24">
		<path d="M9.5 5.5 16 12l-6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
	</symbol>
</svg>

<header class="header<?= $home !== '' ? ' header--solid' : '' ?>" data-header>
	<div class="container header__inner">
		<a class="logo" href="<?= SITE_DIR ?>" aria-label="Лак&amp;Точка — на главную">
			<span class="logo__name">Лак<span class="logo__amp">&amp;</span>Точка</span>
			<span class="logo__caption">студия маникюра</span>
		</a>

		<?php // Кнопка стоит в разметке перед меню: после раскрытия Tab сразу попадает в его ссылки. ?>
		<button class="burger" type="button" aria-expanded="false" aria-controls="site-nav" data-burger>
			<span class="burger__lines" aria-hidden="true"></span>
			<span class="visually-hidden" data-burger-label>Открыть меню</span>
		</button>

		<nav class="nav" id="site-nav" aria-label="Основное меню" data-nav>
			<ul class="nav__list">
				<?php foreach ($menu as $anchor => $title): ?>
					<li><a class="nav__link" href="<?= $home . $anchor ?>"><?= $title ?></a></li>
				<?php endforeach; ?>
			</ul>
			<a class="btn nav__cta" href="<?= $home ?>#booking">Записаться</a>
		</nav>

	</div>
</header>

<main id="main" tabindex="-1">
