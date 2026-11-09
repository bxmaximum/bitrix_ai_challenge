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
<ul class="masters__list" tabindex="0" aria-label="Мастера студии">
	<?php foreach ($arResult['ITEMS'] as $item): ?>
		<?php
		$this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
		$this->AddDeleteAction($item['ID'], $item['DELETE_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_DELETE'));
		?>
		<li class="master" id="<?= $this->GetEditAreaId($item['ID']) ?>">
			<div class="master__photo">
				<?php if ($item['PICTURE']): ?>
					<img src="<?= htmlspecialcharsbx($item['PICTURE']['src']) ?>"
						width="<?= (int)$item['PICTURE']['width'] ?>" height="<?= (int)$item['PICTURE']['height'] ?>"
						loading="lazy" alt="<?= htmlspecialcharsbx('Мастер ' . $item['NAME']) ?>">
				<?php endif; ?>
			</div>
			<h3 class="master__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
			<p class="master__spec"><?= htmlspecialcharsbx($item['SPECIALIZATION']) ?></p>
			<?php if ($item['EXPERIENCE'] !== ''): ?>
				<p class="master__exp">Стаж <?= htmlspecialcharsbx($item['EXPERIENCE']) ?></p>
			<?php endif; ?>
			<p class="master__about"><?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?></p>
			<a class="master__link" href="#booking" data-book-master="<?= (int)$item['ID'] ?>">
				Записаться<span class="visually-hidden"> к мастеру: <?= htmlspecialcharsbx($item['NAME']) ?></span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
