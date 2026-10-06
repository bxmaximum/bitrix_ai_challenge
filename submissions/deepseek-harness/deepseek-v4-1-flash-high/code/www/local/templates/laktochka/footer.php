<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$ltContent ??= require $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/bxmax_content.php';
$ltStudio = $ltContent['studio'];
$ltTexts = $ltContent['texts'];
?>
</main>

<footer class="site-footer" id="contacts">
	<div class="container">
		<section class="contacts" aria-labelledby="contacts-title">
			<h2 class="display h2 contacts__title" id="contacts-title"><?= lt_e($ltTexts['contacts']['title']) ?></h2>

			<div>
				<p class="contacts__label">Адрес</p>
				<p class="contacts__value"><?= lt_e($ltStudio['address_short']) ?></p>
				<p class="contacts__note"><?= lt_e($ltStudio['address']) ?></p>
				<p class="contacts__note"><?= lt_e($ltStudio['metro']) ?></p>
			</div>

			<div>
				<p class="contacts__label">Часы работы</p>
				<p class="contacts__value"><?= lt_e($ltStudio['hours']) ?></p>
				<p class="contacts__note">Запись открыта на две недели вперёд</p>
			</div>

			<div>
				<p class="contacts__label">Связаться</p>
				<p class="contacts__value">
					<a href="tel:<?= lt_e($ltStudio['phone_href']) ?>"><?= lt_e($ltStudio['phone']) ?></a>
				</p>
				<p class="contacts__note">
					<a href="mailto:<?= lt_e($ltStudio['email']) ?>"><?= lt_e($ltStudio['email']) ?></a>
				</p>
			</div>
		</section>

		<nav class="site-footer__nav" aria-label="Навигация в подвале">
			<?php foreach (['#services' => 'Услуги', '#gallery' => 'Работы', '#masters' => 'Мастера', '#reviews' => 'Отзывы', '#booking' => 'Онлайн-запись'] as $href => $label): ?>
				<a href="<?= lt_e($href) ?>"><?= lt_e($label) ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="site-footer__bottom">
			<p>© <?= date('Y') ?> Студия «Лак&amp;Точка». Все права защищены.</p>
			<p>Маникюр, педикюр и уход — Москва, Большая Дмитровка, 12.</p>
		</div>
	</div>
</footer>

<script src="<?= lt_asset('/assets/js/main.js') ?>" defer></script>
</body>
</html>
