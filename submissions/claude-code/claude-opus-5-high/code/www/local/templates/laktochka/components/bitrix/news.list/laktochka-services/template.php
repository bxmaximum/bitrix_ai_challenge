<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Карточки услуг с ценой. Данные — инфоблок services.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */
?>
<div class="services__carousel reveal" data-carousel>
	<ul class="services__track" data-carousel-track>
		<?php foreach ($arResult['ITEMS'] as $index => $item): ?>
			<?php
			$price = (int)($item['DISPLAY_PROPERTIES']['PRICE']['VALUE'] ?? $item['PROPERTIES']['PRICE']['VALUE'] ?? 0);
			$duration = (int)($item['DISPLAY_PROPERTIES']['DURATION']['VALUE'] ?? $item['PROPERTIES']['DURATION']['VALUE'] ?? 0);
			$alt = (string)($item['PROPERTIES']['PHOTO_ALT']['VALUE'] ?? $item['NAME']);
			$picture = $item['PREVIEW_PICTURE']['SRC'] ?? '';
			?>
			<li class="service-card" tabindex="0">
				<?php if ($picture !== ''): ?>
					<img class="service-card__img" src="<?= htmlspecialcharsbx($picture) ?>"
						 alt="<?= htmlspecialcharsbx($alt) ?>"
						 width="<?= (int)($item['PREVIEW_PICTURE']['WIDTH'] ?? 900) ?>"
						 height="<?= (int)($item['PREVIEW_PICTURE']['HEIGHT'] ?? 1200) ?>" loading="lazy">
				<?php endif; ?>

				<div class="service-card__body">
					<h3 class="service-card__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
					<p class="service-card__text"><?= htmlspecialcharsbx((string)($item['PREVIEW_TEXT'] ?? '')) ?></p>
					<p class="service-card__meta">
						<span><?= $duration > 0 ? $duration . ' мин' : 'по записи' ?></span>
						<span class="service-card__price"><?= number_format($price, 0, ',', ' ') ?>&nbsp;₽</span>
					</p>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="services__controls">
		<a class="btn" href="#booking">Записаться на услугу</a>
		<div class="services__arrows">
			<button class="round-btn" type="button" data-carousel-prev aria-label="Предыдущие услуги">
				<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 2 4 8l6 6" stroke="currentColor" stroke-width="1.5"/></svg>
			</button>
			<button class="round-btn" type="button" data-carousel-next aria-label="Следующие услуги">
				<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m6 2 6 6-6 6" stroke="currentColor" stroke-width="1.5"/></svg>
			</button>
		</div>
	</div>
</div>
