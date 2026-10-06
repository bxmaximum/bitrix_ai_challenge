<?php

/**
 * Главная страница студии «Лак&Точка».
 *
 * Секции: hero с оффером · арка-интро · о студии · услуги с ценами (инфоблок
 * services) · галерея работ · мастера (инфоблок masters) · отзывы · онлайн-запись
 * (компонент bxmax:booking.slots) · контакты и подвал.
 */

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Лак&Точка — студия маникюра в центре Москвы с онлайн-записью');

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Repository\MasterRepository;
use Bxmax\Booking\Repository\ServiceRepository;

// Лендинг продолжает работать и без модуля записи: секции услуг, мастеров и
// формы просто остаются пустыми, а не роняют страницу.
$moduleReady = Loader::includeModule('bxmax.booking');

$services = [];
$masters = [];
if ($moduleReady)
{
	$locator = ServiceLocator::getInstance();
	$services = array_values($locator->get(ServiceRepository::class)->getAll());
	$masters = array_values($locator->get(MasterRepository::class)->getAll());
}
$gallery = [
	['file' => 'gallery-1.jpg', 'caption' => 'Ароматы и масла студии'],
	['file' => 'gallery-2.jpg', 'caption' => 'Аппаратный маникюр'],
	['file' => 'gallery-3.jpg', 'caption' => 'Ритуал для рук и стоп'],
	['file' => 'gallery-4.jpg', 'caption' => 'Уходовые процедуры'],
	['file' => 'gallery-5.jpg', 'caption' => 'Парфюм и уход'],
	['file' => 'gallery-6.jpg', 'caption' => 'Атмосфера студии'],
];

$heroTexts = $ltContent['texts']['hero'];
$archTexts = $ltContent['texts']['arch'];
$aboutTexts = $ltContent['texts']['about'];
?>

<section class="hero" id="hero">
	<div class="hero__media" role="img" aria-label="Руки с ухоженным маникюром на тёплом фоне"
		style="background-image: url('<?= lt_e(lt_asset('/assets/img/hero.jpg')) ?>')"></div>
	<div class="hero__blinds" aria-hidden="true">
		<?php for ($blind = 0; $blind < 52; $blind++): ?>
			<span class="hero__blind" style="--i: <?= $blind ?>"></span>
		<?php endfor; ?>
	</div>

	<div class="container hero__inner">
		<p class="hero__eyebrow"><?= lt_e($ltStudio['tagline']) ?> · Москва</p>
		<h1 class="display hero__title" data-split>
			<span><?= lt_e($heroTexts['title_line_1']) ?></span>
			<span><?= lt_e($heroTexts['title_line_2']) ?></span>
		</h1>
		<p class="hero__lead"><?= lt_e($heroTexts['lead']) ?></p>
		<div class="hero__actions">
			<a class="btn btn--light" href="#booking">Записаться</a>
			<p class="hero__note"><?= lt_e($heroTexts['note']) ?></p>
		</div>
	</div>
</section>

<section class="arch" aria-labelledby="arch-title">
	<div class="container arch__stage">
		<?= lt_ornament('ornament--center') ?>
		<h2 class="display arch__label" id="arch-title"><?= lt_e($archTexts['title']) ?></h2>
		<figure class="arch__frame" data-reveal>
			<img src="<?= lt_e(lt_asset('/assets/img/studio.jpg')) ?>" width="900" height="1200"
				alt="Интерьер студии «Лак&Точка»: светлые рабочие места и зеркала">
		</figure>
		<p class="arch__caption"><?= lt_e($archTexts['caption']) ?></p>
		<p class="arch__scroll" aria-hidden="true">
			<span>Листайте</span>
			<svg width="12" height="16" viewBox="0 0 12 16" fill="none" focusable="false">
				<path d="M6 1v13M1.5 9.5 6 14l4.5-4.5" stroke="currentColor" stroke-width="1"/>
			</svg>
		</p>
	</div>
</section>

<section class="section about" id="about" aria-labelledby="about-title">
	<div class="container">
		<?= lt_ornament('ornament--center') ?>
		<h2 class="display h2 about__title" id="about-title" data-split><?= lt_e($aboutTexts['title']) ?></h2>
		<p class="about__text" data-fade><?= lt_e($aboutTexts['text']) ?></p>
		<p class="about__second" data-fade><?= lt_e($aboutTexts['second']) ?></p>
		<p class="about__actions" data-fade>
			<a class="btn" href="#services">Смотреть услуги</a>
		</p>
	</div>
</section>

<section class="section services" id="services" aria-labelledby="services-title">
	<div class="container services__head">
		<?= lt_ornament() ?>
		<div class="section-head">
			<h2 class="display h2" id="services-title" data-split><?= lt_e($ltContent['texts']['services']['title']) ?></h2>
			<p class="section-head__caption" data-fade><?= lt_e($ltContent['texts']['services']['caption']) ?></p>
		</div>
	</div>

	<ul class="cards" data-scroller="services" aria-label="Услуги студии">
		<?php foreach ($services as $service): ?>
			<li class="card" data-reveal>
				<span class="card__media">
					<img src="<?= lt_e($service['imageSrc']) ?>" width="800" height="1000"
						alt="<?= lt_e($service['name']) ?> — работа студии" loading="lazy">
				</span>
				<h3 class="card__title"><?= lt_e($service['name']) ?></h3>
				<p class="card__price">
					<?= lt_e(lt_price((int)$service['price'])) ?> ·
					<?= (int)$service['duration'] ?> <?= lt_e(lt_plural((int)$service['duration'], ['минута', 'минуты', 'минут'])) ?>
				</p>
				<p class="meta"><?= lt_e($service['description']) ?></p>
				<a class="card__link" href="#booking" data-book-service="<?= (int)$service['id'] ?>">
					Записаться на услугу
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="container services__controls">
		<a class="btn" href="#booking">Все услуги и запись</a>
		<div class="scroller-nav">
			<button class="scroller-nav__btn" type="button" data-scroller-prev="services" aria-label="Предыдущие услуги">
				<svg width="16" height="12" viewBox="0 0 16 12" fill="none" aria-hidden="true" focusable="false">
					<path d="M16 6H1m5-5L1 6l5 5" stroke="currentColor" stroke-width="1"/>
				</svg>
			</button>
			<button class="scroller-nav__btn" type="button" data-scroller-next="services" aria-label="Следующие услуги">
				<svg width="16" height="12" viewBox="0 0 16 12" fill="none" aria-hidden="true" focusable="false">
					<path d="M0 6h15m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1"/>
				</svg>
			</button>
		</div>
	</div>
</section>

<section class="section gallery" id="gallery" aria-labelledby="gallery-title">
	<div class="container">
		<div class="gallery__head section-head">
			<?= lt_ornament() ?>
			<h2 class="display h2" id="gallery-title" data-split><?= lt_e($ltContent['texts']['gallery']['title']) ?></h2>
			<p class="section-head__caption" data-fade><?= lt_e($ltContent['texts']['gallery']['caption']) ?></p>
		</div>

		<div class="gallery__grid">
			<?php foreach ($gallery as $item): ?>
				<figure class="gallery__item" data-reveal>
					<img src="<?= lt_e(lt_asset('/assets/img/' . $item['file'])) ?>" alt="<?= lt_e($item['caption']) ?>" loading="lazy">
					<figcaption><?= lt_e($item['caption']) ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--cream-soft masters" id="masters" aria-labelledby="masters-title">
	<div class="container">
		<div class="masters__head section-head">
			<?= lt_ornament() ?>
			<h2 class="display h2" id="masters-title" data-split><?= lt_e($ltContent['texts']['masters']['title']) ?></h2>
			<p class="section-head__caption" data-fade><?= lt_e($ltContent['texts']['masters']['caption']) ?></p>
		</div>

		<ul class="masters__grid">
			<?php foreach ($masters as $master): ?>
				<li class="master">
					<figure class="master__photo" data-reveal>
						<img src="<?= lt_e($master['photoSrc']) ?>" width="760" height="1000"
							alt="Мастер <?= lt_e($master['name']) ?>" loading="lazy">
					</figure>
					<h3 class="master__name"><?= lt_e($master['name']) ?></h3>
					<p class="master__spec"><?= lt_e($master['specialization']) ?></p>
					<p class="master__exp">
						<?= (int)$master['experience'] ?>
						<?= lt_e(lt_plural((int)$master['experience'], ['год', 'года', 'лет'])) ?> опыта
					</p>
					<p class="master__cta">
						<a class="link-underline" href="#booking" data-book-master="<?= (int)$master['id'] ?>">
							Записаться к мастеру
						</a>
					</p>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="section reviews" id="reviews" aria-labelledby="reviews-title">
	<div class="container">
		<div class="reviews__head section-head">
			<?= lt_ornament() ?>
			<h2 class="display h2" id="reviews-title" data-split><?= lt_e($ltContent['texts']['reviews']['title']) ?></h2>
			<p class="section-head__caption" data-fade><?= lt_e($ltContent['texts']['reviews']['caption']) ?></p>
		</div>
	</div>

	<div class="container">
		<ul class="reviews__row" data-scroller="reviews" aria-label="Отзывы гостей студии">
			<?php foreach ($ltContent['reviews'] as $review): ?>
				<li>
					<blockquote class="review" data-reveal>
						<span class="review__mark" aria-hidden="true">“</span>
						<p class="review__text"><?= lt_e($review['text']) ?></p>
						<footer>
							<p class="review__author"><?= lt_e($review['name']) ?></p>
							<p class="review__caption"><?= lt_e($review['caption']) ?></p>
						</footer>
					</blockquote>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="section booking" id="booking" aria-labelledby="booking-title">
	<div class="container booking__inner">
		<div data-fade>
			<?= lt_ornament() ?>
			<h2 class="display h2 booking__title" id="booking-title"><?= lt_e($ltContent['texts']['booking']['title']) ?></h2>
			<p class="booking__caption"><?= lt_e($ltContent['texts']['booking']['caption']) ?></p>
			<ul class="booking__facts">
				<li>Свободные окна на две недели вперёд</li>
				<li>Один слот — одна запись, без накладок</li>
				<li>Подтверждение приходит администратору студии</li>
			</ul>
		</div>

		<?php if ($moduleReady): ?>
			<?php $APPLICATION->IncludeComponent('bxmax:booking.slots', '', [], false); ?>
		<?php else: ?>
			<p class="booking__caption">Онлайн-запись временно недоступна: позвоните по телефону студии.</p>
		<?php endif; ?>
	</div>
</section>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
