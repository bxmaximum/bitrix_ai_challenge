<?php

use Bitrix\Main\ModuleManager;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @global CMain $APPLICATION */

$APPLICATION->SetTitle('Лак&Точка — маникюр ровно за час. Студия в центре Москвы');
$APPLICATION->SetPageProperty('description', 'Студия маникюра «Лак&Точка» на Большой Дмитровке: любая услуга укладывается в час, мастер начинает минута в минуту. Онлайн-запись на удобное время.');

$img = SITE_TEMPLATE_PATH . '/img';
$phone = '+7 (495) 150-24-60';
$address = 'Москва, Большая Дмитровка, 14, стр. 2';

$works = [
	'Нюдовый маникюр на миндальной форме',
	'Молочное покрытие с серебряной втиркой',
	'Нюдовый маникюр на коротких ногтях',
	'Жемчужная втирка на квадратной форме',
	'Классический красный на миндальной форме',
	'Красный гель-лак на длинных ногтях',
	'Сине-золотой дизайн с белыми акцентами',
	'Золотая втирка на овальной форме',
];

$reviews = [
	['Екатерина М.', 'Записалась в обед между встречами и вышла ровно через час, как и обещали. Гель-лак ношу третью неделю, ни одного скола.'],
	['Ольга Синицына', 'Наконец-то студия, где не ждёшь в холле по двадцать минут. Алина выровняла ногти так, что подруги спрашивают, не наращивание ли это.'],
	['Марина К.', 'Дарья сделала френч с такой тонкой линией, какой я ещё ни у кого не видела. Набор инструментов вскрыли при мне, это подкупает.'],
	['Анна Воронцова', 'Пришла с картинкой из Pinterest. Вероника повторила дизайн один в один и уложилась в час. Запись на сайте заняла минуту.'],
	['Юлия Р.', 'Хожу к Марии на уход для рук: кожа после зимы наконец перестала трескаться. Спокойно, чисто, без навязывания допуслуг.'],
];
?>

<section class="hero" data-hero aria-labelledby="hero-title">
	<div class="hero__media">
		<picture>
			<source media="(max-width: 767px)" srcset="<?= $img ?>/hero-m.webp">
			<img src="<?= $img ?>/hero.webp" width="1920" height="1080" alt="" fetchpriority="high">
		</picture>
	</div>
	<div class="hero__blinds" aria-hidden="true"></div>
	<div class="container hero__content">
		<h1 class="h-xl hero__title" id="hero-title">Маникюр ровно<br class="br-wide"> за час. И точка</h1>
		<a class="btn btn--light" href="#booking">Записаться</a>
	</div>
</section>

<section class="about" id="about" aria-labelledby="about-title">
	<div class="container about__inner">
		<p class="lead about__lead reveal">Студия маникюра в центре Москвы</p>
		<div class="orn-line reveal" aria-hidden="true"><svg width="113" height="35"><use href="#orn"/></svg></div>
		<div class="about__photo reveal">
			<img src="<?= $img ?>/about.webp" width="730" height="1000" loading="lazy"
				alt="Интерьер студии: арочная ниша с палитрой лаков между двумя пальмами">
		</div>
		<div class="about__orns reveal" aria-hidden="true">
			<span class="orn-line"><svg width="113" height="35"><use href="#orn"/></svg></span>
			<span class="orn-line"><svg width="113" height="35"><use href="#orn"/></svg></span>
		</div>
		<h2 class="h-lg about__title" id="about-title" data-split>Красиво —<br>значит вовремя.</h2>
		<p class="text about__text reveal">Мы открыли «Лак&amp;Точку» для тех, кто считает свои часы. Здесь не ждут в холле: мастер начинает минута в минуту и вскрывает стерильный набор при вас. Покрытие держится три недели, иначе переделаем бесплатно. Приходите в окно между встречами, успеем.</p>
		<a class="btn reveal" href="#services">Услуги и цены</a>
	</div>
</section>

<section class="services" id="services" aria-labelledby="services-title">
	<div class="container">
		<svg class="orn" width="113" height="35" aria-hidden="true"><use href="#orn"/></svg>
		<h2 class="h-xl services__title" id="services-title" data-split>Всё для ваших рук,<br>и каждая услуга —<br>ровно за час</h2>
		<p class="lead services__lead reveal">От снятия покрытия до ручной росписи.</p>

		<?php $APPLICATION->IncludeComponent(
			'bitrix:news.list',
			'services',
			[
				'IBLOCK_TYPE' => 'studio',
				'IBLOCK_ID' => 'services',
				'NEWS_COUNT' => 20,
				'SORT_BY1' => 'SORT',
				'SORT_ORDER1' => 'ASC',
				'SORT_BY2' => 'ID',
				'SORT_ORDER2' => 'ASC',
				'FIELD_CODE' => ['PREVIEW_TEXT', 'PREVIEW_PICTURE'],
				'PROPERTY_CODE' => ['PRICE', 'DURATION'],
				'CHECK_DATES' => 'Y',
				'ACTIVE_DATE_FORMAT' => 'd.m.Y',
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => 36000000,
				'CACHE_FILTER' => 'N',
				'CACHE_GROUPS' => 'Y',
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
				'DETAIL_URL' => '',
			],
			false
		) ?>
	</div>
</section>

<section class="pin pin--gallery" id="gallery" data-pin aria-labelledby="gallery-title">
	<div class="pin__stage">
		<span class="pin__mark" aria-hidden="true">&amp;</span>
		<div class="container pin__center">
			<svg class="orn-v" width="35" height="66" aria-hidden="true"><use href="#orn-v"/></svg>
			<h2 class="h-xl pin__title" id="gallery-title">Каждая деталь —<br>на своём месте.</h2>
			<p class="text pin__text">Ровный блик, тонкая кутикула, цвет до самого края. Это работы наших мастеров: листайте и выбирайте, с чем прийти.</p>
		</div>
		<ul class="pin__items pin__items--works">
			<?php foreach ($works as $index => $alt): ?>
				<li class="pin__item" style="--i: <?= $index ?>">
					<img class="arch" src="<?= $img ?>/work-<?= $index + 1 ?>.webp" width="560" height="752" loading="lazy" alt="<?= htmlspecialcharsbx($alt) ?>">
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="visit" id="visit" aria-labelledby="visit-title">
	<div class="container visit__inner">
		<div class="visit__photo reveal">
			<img src="<?= $img ?>/visit.webp" width="1080" height="999" loading="lazy"
				alt="Мастер наносит прозрачное покрытие на ногти клиентки">
		</div>
		<div class="visit__body">
			<svg class="orn" width="113" height="35" aria-hidden="true"><use href="#orn"/></svg>
			<h2 class="h-lg" id="visit-title" data-split>Ваш час<br>в «Лак&amp;Точке»</h2>
			<p class="text reveal">Кофе или матча, кресло у окна и мастер, который не опаздывает. Через час вы выходите с готовым маникюром и успеваете туда, куда собирались.</p>
			<a class="btn reveal" href="#booking">Выбрать время</a>
		</div>
	</div>
</section>

<section class="masters" id="masters" aria-labelledby="masters-title">
	<div class="container">
		<h2 class="h-lg masters__title" id="masters-title" data-split>Мастера студии</h2>

		<?php $APPLICATION->IncludeComponent(
			'bitrix:news.list',
			'masters',
			[
				'IBLOCK_TYPE' => 'studio',
				'IBLOCK_ID' => 'masters',
				'NEWS_COUNT' => 12,
				'SORT_BY1' => 'SORT',
				'SORT_ORDER1' => 'ASC',
				'SORT_BY2' => 'ID',
				'SORT_ORDER2' => 'ASC',
				'FIELD_CODE' => ['PREVIEW_TEXT', 'PREVIEW_PICTURE'],
				'PROPERTY_CODE' => ['SPECIALIZATION', 'EXPERIENCE'],
				'CHECK_DATES' => 'Y',
				'ACTIVE_DATE_FORMAT' => 'd.m.Y',
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => 36000000,
				'CACHE_FILTER' => 'N',
				'CACHE_GROUPS' => 'Y',
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
				'DETAIL_URL' => '',
			],
			false
		) ?>

		<div class="masters__foot">
			<p class="h-lg masters__call" aria-hidden="true">Выберите своего</p>
			<a class="btn" href="#booking">Записаться к мастеру</a>
		</div>
	</div>
</section>

<section class="pin pin--reviews" id="reviews" data-pin aria-labelledby="reviews-title">
	<div class="pin__stage">
		<span class="pin__mark pin__mark--right" aria-hidden="true">&amp;</span>
		<div class="container pin__center">
			<h2 class="h-xl pin__title" id="reviews-title">Более 1 200<br>отзывов на пять<br>звёзд</h2>
			<svg class="orn-v" width="35" height="66" aria-hidden="true"><use href="#orn-v"/></svg>
		</div>
		<ul class="pin__items pin__items--reviews">
			<?php foreach ($reviews as $index => [$author, $text]): ?>
				<li class="pin__item" style="--i: <?= $index ?>">
					<figure class="review">
						<figcaption class="review__author"><?= htmlspecialcharsbx($author) ?></figcaption>
						<blockquote class="review__text">«<?= htmlspecialcharsbx($text) ?>»</blockquote>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="booking" id="booking" aria-labelledby="booking-title">
	<div class="booking__media" aria-hidden="true">
		<picture>
			<source media="(max-width: 767px)" srcset="<?= $img ?>/booking-m.webp">
			<img src="<?= $img ?>/booking.webp" width="1920" height="1400" loading="lazy" alt="">
		</picture>
	</div>
	<div class="container booking__inner">
		<div class="booking__intro">
			<h2 class="h-lg booking__title" id="booking-title">Запишитесь<br>онлайн</h2>
			<p class="text booking__text">Выберите услугу, мастера и свободное окно. Звонить и ждать подтверждения не нужно: время сразу закрепляется за вами.</p>
			<p class="text booking__text">Удобнее голосом? Позвоните: <a href="tel:+74951502460"><?= $phone ?></a>, ежедневно с 10:00 до 20:00.</p>
		</div>
		<div class="booking__form">
			<?php if (ModuleManager::isModuleInstalled('bxmax.booking')): ?>
				<?php $APPLICATION->IncludeComponent(
					'bxmax:booking.form',
					'',
					[
						'POLICY_URL' => SITE_DIR . 'privacy/',
						'ADDRESS' => $address,
						'PHONE' => $phone,
					],
					false
				) ?>
			<?php else: ?>
				<p class="booking__fallback" role="status">Онлайн-запись сейчас недоступна. Позвоните нам — подберём время: <a href="tel:+74951502460"><?= $phone ?></a></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
