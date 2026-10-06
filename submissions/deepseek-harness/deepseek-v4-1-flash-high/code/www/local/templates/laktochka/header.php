<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

require_once __DIR__ . '/functions.php';

$ltContent = require $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/bxmax_content.php';
$ltStudio = $ltContent['studio'];
$ltNav = [
	'#services' => 'Услуги',
	'#gallery' => 'Работы',
	'#masters' => 'Мастера',
	'#reviews' => 'Отзывы',
	'#contacts' => 'Контакты',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $APPLICATION->ShowHead(); ?>
	<title><?php $APPLICATION->ShowTitle(); ?></title>
	<link rel="stylesheet" href="<?= lt_asset('/assets/css/fonts.css') ?>">
	<link rel="stylesheet" href="<?= lt_asset('/assets/css/style.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">Перейти к содержанию</a>

<header class="site-header">
	<div class="container site-header__inner">
		<a class="logo" href="#hero" aria-label="Лак&amp;Точка — на главную">
			<span class="logo__mark">Лак&amp;Точка</span>
			<span class="logo__sub">студия маникюра</span>
		</a>

		<nav class="site-nav" id="site-nav" aria-label="Основная навигация" data-site-nav>
			<ul class="site-nav__list" data-nav-list>
				<?php foreach ($ltNav as $href => $label): ?>
					<li><a class="site-nav__link" href="<?= lt_e($href) ?>"><?= lt_e($label) ?></a></li>
				<?php endforeach; ?>
				<li class="site-nav__book"><a class="btn" href="#booking">Записаться</a></li>
			</ul>
		</nav>

		<a class="btn site-header__cta" href="#booking" data-goto-booking>Записаться</a>

		<button class="site-header__burger" type="button" data-menu-toggle
			aria-expanded="false" aria-controls="site-nav" aria-label="Открыть меню">
			<span class="site-header__burger-bars" aria-hidden="true"></span>
		</button>
	</div>
</header>

<main id="main">
