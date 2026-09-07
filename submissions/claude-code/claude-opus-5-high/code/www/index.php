<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @var CMain $APPLICATION */
global $APPLICATION;

$APPLICATION->SetTitle('Лак&Точка — студия маникюра в Хамовниках, онлайн-запись');
$APPLICATION->SetPageProperty(
	'description',
	'Студия маникюра «Лак&Точка» на Комсомольском проспекте: аппаратный маникюр, наращивание, педикюр и нейл-арт. '
	. 'Выберите мастера и свободное время — запись занимает минуту.'
);

$template = SITE_TEMPLATE_PATH;

$ornament = static function (string $extra = ''): string {
	return '<svg class="ornament ' . $extra . '" viewBox="0 0 120 34" fill="none" aria-hidden="true" focusable="false">'
		. '<path d="M60 4c4.6 0 8.3 3.8 8.3 8.5S64.6 21 60 21s-8.3-3.8-8.3-8.5S55.4 4 60 4Z" stroke="currentColor" stroke-width="1"/>'
		. '<path d="M60 8.5c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4Z" stroke="currentColor" stroke-width="0.8"/>'
		. '<path d="M51.7 12.5c-6 6.4-12 9.6-18 9.6-5.4 0-9.7-2.6-9.7-6.2 0-2.7 2.2-4.6 5-4.6 2.6 0 4.6 1.7 4.6 4 0 1.9-1.4 3.3-3.2 3.3" stroke="currentColor" stroke-width="1"/>'
		. '<path d="M68.3 12.5c6 6.4 12 9.6 18 9.6 5.4 0 9.7-2.6 9.7-6.2 0-2.7-2.2-4.6-5-4.6-2.6 0-4.6 1.7-4.6 4 0 1.9 1.4 3.3 3.2 3.3" stroke="currentColor" stroke-width="1"/>'
		. '<circle cx="7" cy="17" r="2" fill="currentColor"/><circle cx="113" cy="17" r="2" fill="currentColor"/>'
		. '<path d="M14 17h6M100 17h6" stroke="currentColor" stroke-width="1"/>'
		. '<path d="M60 25.5v5M56 28h8" stroke="currentColor" stroke-width="0.8"/></svg>';
};

$reviews = [
	[
		'author' => 'Полина Ветрова',
		'text' => 'Пришла «на один раз» перед свадьбой подруги — хожу третий год. Ника делает такой тонкий слой, '
			. 'что ногти не выглядят покрытыми. И ни одного пропила за всё время.',
	],
	[
		'author' => 'Даша Кириллова',
		'text' => 'Записалась через сайт в 23:40, слот на утро подтвердился сразу. Приехала — меня уже ждали '
			. 'с именем в записи и чаем с бергамотом.',
	],
	[
		'author' => 'Ирина Мельник',
		'text' => 'Марьяна пересобрала мне форму после неудачного наращивания в другом месте. Впервые за год '
			. 'ногти не цепляются за волосы и не ломаются об клавиатуру.',
	],
	[
		'author' => 'Женя Соболева',
		'text' => 'Хожу к Лене на педикюр. У меня чувствительная кожа и вечная проблема с вросшим ногтем — '
			. 'после её работы забываю о стопах на пять недель.',
	],
	[
		'author' => 'Марина Гуськова',
		'text' => 'Ася нарисовала на безымянном пальце мою кошку. Восемь миллиметров, и она узнаваема. '
			. 'Коллеги на работе просили телефон студии.',
	],
];
?>

<section class="hero" aria-labelledby="heroTitle">
	<div class="hero__media">
		<img src="<?= $template ?>/images/hero.jpg"
			 alt="Мастер студии «Лак&amp;Точка» работает над маникюром клиентки за рабочим столом"
			 width="1800" height="1200" fetchpriority="high">
	</div>

	<div class="hero__inner shell">
		<p class="hero__eyebrow">Москва · Комсомольский проспект, 14/1</p>
		<h1 class="hero__title display display--xl" id="heroTitle" data-split data-split-mode="load">Ухоженные руки, точка</h1>

		<div class="hero__actions">
			<a class="btn btn--light" href="#booking">Записаться</a>
			<a class="btn btn--light" href="#services">Услуги и цены</a>
		</div>

		<div class="hero__strip">
			<p><b>4,9</b><span>средняя оценка за 900+ визитов</span></p>
			<p><b>4 мастера</b><span>каждый со своей специализацией</span></p>
			<p><b>10:00–20:00</b><span>без выходных, запись онлайн</span></p>
		</div>
	</div>
</section>

<section class="intro" aria-labelledby="introTitle">
	<div class="shell">
		<div class="intro__grid">
			<p class="eyebrow reveal" id="introTitle">Маленькая студия на четыре кресла в Хамовниках</p>

			<div class="intro__arch reveal reveal--d1">
				<img src="<?= $template ?>/images/intro-arch.jpg"
					 alt="Зал студии «Лак&amp;Точка»: арочный проход, стойка администратора и рабочие места мастеров"
					 width="900" height="1200" loading="lazy">
			</div>

			<p class="lede reveal reveal--d2">
				Мы не поточная сеть: в зале одновременно работают максимум четыре мастера, а между гостьями
				остаётся полчаса на стерилизацию и уборку. Поэтому здесь тихо, пахнет не ацетоном, а гарденией,
				и никто не торопит вас закончить кофе.
			</p>

			<div class="intro__facts reveal reveal--d3">
				<div class="fact"><span class="fact__value">2019</span><span class="fact__label">год, с которого мы работаем на Комсомольском</span></div>
				<div class="fact"><span class="fact__value">30&nbsp;мин</span><span class="fact__label">перерыв между гостьями на стерилизацию</span></div>
				<div class="fact"><span class="fact__value">4&nbsp;недели</span><span class="fact__label">средний срок носки нашего покрытия</span></div>
			</div>
		</div>
	</div>
</section>

<section class="section section--cream services" id="services" aria-labelledby="servicesTitle">
	<div class="shell">
		<div class="section__head services__head">
			<?= $ornament() ?>
			<h2 class="display display--lg" id="servicesTitle" data-split>Уход, собранный по минутам</h2>
			<p class="eyebrow">От формы ногтя до последнего слоя топа.</p>
		</div>

		<?php $APPLICATION->IncludeComponent(
			'bitrix:news.list',
			'laktochka-services',
			[
				'IBLOCK_TYPE' => 'bxmax_studio',
				'IBLOCK_ID' => 'services',
				'NEWS_COUNT' => '20',
				'SORT_BY1' => 'SORT',
				'SORT_ORDER1' => 'ASC',
				'SORT_BY2' => 'ID',
				'SORT_ORDER2' => 'ASC',
				'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'],
				'PROPERTY_CODE' => ['PRICE', 'DURATION', 'PHOTO_ALT'],
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => '3600',
				'CACHE_FILTER' => 'N',
				'CACHE_GROUPS' => 'N',
				'DISPLAY_DATE' => 'N',
				'DISPLAY_NAME' => 'Y',
				'DISPLAY_PICTURE' => 'Y',
				'DISPLAY_PREVIEW_TEXT' => 'Y',
				'AJAX_MODE' => 'N',
				'SET_TITLE' => 'N',
				'SET_LAST_MODIFIED' => 'N',
				'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
				'ADD_SECTIONS_CHAIN' => 'N',
				'ACTIVE_DATE_FORMAT' => 'd.m.Y',
				'PARENT_SECTION' => '',
				'PARENT_SECTION_CODE' => '',
				'CHECK_DATES' => 'Y',
				'PAGER_TEMPLATE' => '',
				'DISPLAY_TOP_PAGER' => 'N',
				'DISPLAY_BOTTOM_PAGER' => 'N',
			]
		); ?>
	</div>
</section>

<section class="section section--light about" aria-labelledby="aboutTitle">
	<span class="watermark" style="left:-2%; top:6%;" data-parallax="0.06" aria-hidden="true">Л&amp;Т</span>

	<div class="about__floats" aria-hidden="false">
		<figure class="about__float about__float--a" data-parallax="0.09">
			<img src="<?= $template ?>/images/shelves.jpg"
				 alt="Полка с флаконами лаков в интерьере студии" width="900" height="1200" loading="lazy">
		</figure>
		<figure class="about__float about__float--b" data-parallax="0.14">
			<img src="<?= $template ?>/images/lounge.jpg"
				 alt="Кремовое кресло в зоне ожидания студии" width="900" height="1200" loading="lazy">
		</figure>
		<figure class="about__float about__float--c" data-parallax="0.11">
			<img src="<?= $template ?>/images/polishes.jpg"
				 alt="Лоток с флаконами лаков на рабочем столе мастера" width="900" height="1200" loading="lazy">
		</figure>
	</div>

	<div class="shell">
		<div class="about__inner">
			<?= $ornament('ornament--center') ?>
			<h2 class="display display--lg" id="aboutTitle" data-split>Каждая деталь — удовольствие</h2>
			<p class="lede reveal">
				Инструменты проходят три ступени обработки и хранятся в крафт-пакетах с датой. Пилки и бафы
				одноразовые. Вытяжка стоит у каждого стола, поэтому пыль не летит в лицо, а вечером в зале
				так же свежо, как в десять утра.
			</p>
			<p class="lede reveal reveal--d1">
				Мастер заранее спрашивает про планы на месяц — отпуск, съёмку, свадьбу — и подбирает длину
				и стойкость под них, а не «как обычно».
			</p>
			<a class="btn reveal reveal--d2" href="#booking">Выбрать время</a>
		</div>
	</div>
</section>

<section class="section section--cream works" id="works" aria-labelledby="worksTitle">
	<div class="shell">
		<div class="section__head works__head">
			<?= $ornament() ?>
			<h2 class="display display--lg" id="worksTitle" data-split>Работы мастеров</h2>
			<p class="eyebrow">Снято в студии, без ретуши формы и длины.</p>
		</div>
	</div>

	<div class="shell reveal" data-carousel>
		<ul class="works__track" data-carousel-track>
			<?php
			$works = [
				1 => 'Красный маникюр овальной формы с золотым кольцом на пальце',
				2 => 'Белый глянцевый маникюр квадратной формы',
				3 => 'Тёмный маникюр с серебряным кольцом крупным планом',
				4 => 'Нежный нюдовый маникюр на руке с кольцом',
				5 => 'Руки с кольцами и аккуратным матовым покрытием',
				6 => 'Маникюр с глиттером и серебряным дизайном',
				7 => 'Мастер делает маникюр клиентке в четыре руки',
				8 => 'Розовый маникюр миндалевидной формы крупным планом',
			];
			foreach ($works as $index => $alt): ?>
				<li class="work">
					<img src="<?= $template ?>/images/work-<?= $index ?>.jpg"
						 alt="<?= htmlspecialcharsbx($alt) ?>" width="900" height="1125" loading="lazy">
				</li>
			<?php endforeach; ?>
		</ul>

		<div class="services__controls">
			<p class="lede">Листайте вбок — здесь 8 из почти тысячи визитов.</p>
			<div class="services__arrows">
				<button class="round-btn" type="button" data-carousel-prev aria-label="Предыдущие работы">
					<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 2 4 8l6 6" stroke="currentColor" stroke-width="1.5"/></svg>
				</button>
				<button class="round-btn" type="button" data-carousel-next aria-label="Следующие работы">
					<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m6 2 6 6-6 6" stroke="currentColor" stroke-width="1.5"/></svg>
				</button>
			</div>
		</div>
	</div>
</section>

<section class="section section--light masters" id="masters" aria-labelledby="mastersTitle">
	<div class="shell">
		<div class="section__head section__head--center masters__head">
			<?= $ornament('ornament--center') ?>
			<h2 class="display display--lg" id="mastersTitle" data-split>Кто держит кисть</h2>
			<p class="eyebrow">Четыре мастера — четыре разных почерка.</p>
		</div>

		<?php $APPLICATION->IncludeComponent(
			'bitrix:news.list',
			'laktochka-masters',
			[
				'IBLOCK_TYPE' => 'bxmax_studio',
				'IBLOCK_ID' => 'masters',
				'NEWS_COUNT' => '12',
				'SORT_BY1' => 'SORT',
				'SORT_ORDER1' => 'ASC',
				'SORT_BY2' => 'ID',
				'SORT_ORDER2' => 'ASC',
				'FIELD_CODE' => ['NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE'],
				'PROPERTY_CODE' => ['SPECIALITY', 'EXPERIENCE', 'PHOTO_ALT'],
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => '3600',
				'CACHE_FILTER' => 'N',
				'CACHE_GROUPS' => 'N',
				'DISPLAY_DATE' => 'N',
				'DISPLAY_NAME' => 'Y',
				'DISPLAY_PICTURE' => 'Y',
				'DISPLAY_PREVIEW_TEXT' => 'Y',
				'AJAX_MODE' => 'N',
				'SET_TITLE' => 'N',
				'SET_LAST_MODIFIED' => 'N',
				'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
				'ADD_SECTIONS_CHAIN' => 'N',
				'ACTIVE_DATE_FORMAT' => 'd.m.Y',
				'PARENT_SECTION' => '',
				'PARENT_SECTION_CODE' => '',
				'CHECK_DATES' => 'Y',
				'PAGER_TEMPLATE' => '',
				'DISPLAY_TOP_PAGER' => 'N',
				'DISPLAY_BOTTOM_PAGER' => 'N',
			]
		); ?>
	</div>
</section>

<section class="section section--cream reviews" id="reviews" aria-labelledby="reviewsTitle">
	<span class="watermark" style="right:-6%; top:2%;" data-parallax="0.05" aria-hidden="true">Л&amp;Т</span>

	<div class="reviews__cards" aria-hidden="true">
		<?php foreach (array_slice($reviews, 0, 4) as $index => $review): ?>
			<figure class="review review--<?= $index + 1 ?>" data-parallax="0.<?= 6 + $index ?>">
				<figcaption class="review__author"><?= htmlspecialcharsbx($review['author']) ?></figcaption>
				<p class="review__text">«<?= htmlspecialcharsbx($review['text']) ?>»</p>
			</figure>
		<?php endforeach; ?>
	</div>

	<div class="shell">
		<div class="reviews__inner">
			<?= $ornament('ornament--center') ?>
			<h2 class="display display--lg" id="reviewsTitle" data-split>Больше 900 визитов и ни одного «потом переделаю»</h2>

			<ul class="reviews__list">
				<?php foreach ($reviews as $review): ?>
					<li class="review">
						<p class="review__author"><?= htmlspecialcharsbx($review['author']) ?></p>
						<p class="review__text">«<?= htmlspecialcharsbx($review['text']) ?>»</p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>

<section class="booking" id="booking" aria-labelledby="bookingTitle">
	<div class="booking__bg">
		<img src="<?= $template ?>/images/booking-bg.jpg"
			 alt="Рука с аккуратным маникюром на светлой ткани" width="1800" height="1200" loading="lazy">
	</div>

	<div class="shell">
		<div class="booking__grid">
			<div class="booking__intro">
				<h2 class="display display--lg" id="bookingTitle" data-split>Запись онлайн</h2>
				<p class="lede">
					Выберите услугу, мастера и свободное время. Слот бронируется сразу — администратор
					перезвонит только чтобы подтвердить, а не чтобы искать окно в журнале.
				</p>
				<ul class="booking__points">
					<li>Расписание на две недели вперёд, шаг — один час.</li>
					<li>Занятое время исчезает из выбора мгновенно.</li>
					<li>Перенести или отменить визит можно по телефону до 20:00.</li>
				</ul>
			</div>

			<?php if (\Bitrix\Main\Loader::includeModule('bxmax.booking')): ?>
				<?php $APPLICATION->IncludeComponent(
					'bxmax:booking.form',
					'',
					[
						'CACHE_TYPE' => 'N',
						'CONSENT_URL' => '/privacy/',
					]
				); ?>
			<?php else: ?>
				<div class="booking__fallback">
					<p>Онлайн-запись временно недоступна. Позвоните нам —
						<a href="tel:+74951234567">+7 495 123-45-67</a>, подберём время вручную.</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
