<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

$this->setFrameMode(true);

if (!$arResult['ITEMS'])
{
	return;
}
?>
<div class="carousel" data-carousel>
	<ul class="carousel__track" data-carousel-track tabindex="0" aria-label="Услуги и цены">
		<?php foreach ($arResult['ITEMS'] as $item): ?>
			<?php
			$this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
			$this->AddDeleteAction($item['ID'], $item['DELETE_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_DELETE'));
			?>
			<li class="carousel__slide" id="<?= $this->GetEditAreaId($item['ID']) ?>">
				<article class="service-card">
					<?php if ($item['PICTURE']): ?>
						<img class="service-card__img" src="<?= htmlspecialcharsbx($item['PICTURE']['src']) ?>"
							width="<?= (int)$item['PICTURE']['width'] ?>" height="<?= (int)$item['PICTURE']['height'] ?>"
							loading="lazy" alt="">
					<?php endif; ?>
					<div class="service-card__body">
						<h3 class="service-card__title"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
						<p class="service-card__meta">
							<span class="service-card__price"><?= number_format($item['PRICE'], 0, '', ' ') ?> ₽</span>
							<?php if ($item['DURATION'] > 0): ?>
								<span class="service-card__time"><?= $item['DURATION'] ?> минут</span>
							<?php endif; ?>
						</p>
						<p class="service-card__text"><?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?></p>
						<a class="service-card__link" href="#booking" data-book-service="<?= (int)$item['ID'] ?>">
							Записаться<span class="visually-hidden">: <?= htmlspecialcharsbx($item['NAME']) ?></span>
						</a>
					</div>
				</article>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="carousel__foot">
		<a class="btn" href="#booking">Записаться на услугу</a>
		<div class="carousel__arrows">
			<button class="arrow" type="button" data-carousel-prev aria-label="Предыдущие услуги">
				<svg width="16" height="16" aria-hidden="true"><use href="#arrow-left"/></svg>
			</button>
			<button class="arrow" type="button" data-carousel-next aria-label="Следующие услуги">
				<svg width="16" height="16" aria-hidden="true"><use href="#arrow-right"/></svg>
			</button>
		</div>
	</div>
</div>
