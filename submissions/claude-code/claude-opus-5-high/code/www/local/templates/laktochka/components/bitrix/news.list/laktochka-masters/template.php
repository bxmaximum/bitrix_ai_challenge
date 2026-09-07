<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Мастера студии. Данные — инфоблок masters.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */
?>
<ul class="masters__grid">
	<?php foreach ($arResult['ITEMS'] as $index => $item): ?>
		<?php
		$speciality = (string)($item['PROPERTIES']['SPECIALITY']['VALUE'] ?? '');
		$experience = (string)($item['PROPERTIES']['EXPERIENCE']['VALUE'] ?? '');
		$alt = (string)($item['PROPERTIES']['PHOTO_ALT']['VALUE'] ?? $item['NAME']);
		$about = (string)($item['PREVIEW_TEXT'] ?? '');
		$picture = $item['PREVIEW_PICTURE']['SRC'] ?? '';
		?>
		<li class="master reveal reveal--d<?= min(3, $index % 4) ?>">
			<?php if ($picture !== ''): ?>
				<div class="master__portrait">
					<img src="<?= htmlspecialcharsbx($picture) ?>" alt="<?= htmlspecialcharsbx($alt) ?>"
						 width="<?= (int)($item['PREVIEW_PICTURE']['WIDTH'] ?? 800) ?>"
						 height="<?= (int)($item['PREVIEW_PICTURE']['HEIGHT'] ?? 1000) ?>" loading="lazy">
				</div>
			<?php endif; ?>

			<h3 class="master__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
			<?php if ($speciality !== ''): ?>
				<p class="master__speciality"><?= htmlspecialcharsbx($speciality) ?></p>
			<?php endif; ?>
			<?php if ($about !== ''): ?>
				<p class="master__about"><?= htmlspecialcharsbx($about) ?></p>
			<?php endif; ?>
			<?php if ($experience !== ''): ?>
				<p class="master__experience"><?= htmlspecialcharsbx($experience) ?></p>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
