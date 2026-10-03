<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var array $arResult */

$price = static fn (int $value): string => number_format($value, 0, '', "\u{00A0}") . "\u{00A0}₽";
?>
<ul class="lt-cards" data-track>
    <?php foreach ($arResult['ITEMS'] as $item): ?>
        <?php
        $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
        $image = $item['LT_IMAGE']['src'] ?? ($item['PREVIEW_PICTURE']['SRC'] ?? '');
        ?>
        <li class="lt-card" id="<?= $this->GetEditAreaId($item['ID']) ?>" data-slide>
            <?php if ($image): ?>
                <img class="lt-card__img" src="<?= htmlspecialcharsbx($image) ?>" width="400" height="500" loading="lazy" decoding="async"
                     alt="Пример работы: <?= htmlspecialcharsbx(mb_strtolower($item['NAME'])) ?>">
            <?php endif; ?>
            <div class="lt-card__body">
                <h3 class="lt-card__title"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
                <p class="lt-card__text"><?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?></p>
                <p class="lt-card__meta">
                    <span class="lt-card__price"><?= $price($item['LT_PRICE']) ?></span>
                    <?php if ($item['LT_DURATION'] > 0): ?>
                        <span class="lt-card__time"><?= (int)$item['LT_DURATION'] ?>&nbsp;мин</span>
                    <?php endif; ?>
                </p>
                <a class="lt-btn lt-btn--light lt-card__cta" href="#booking" data-booking-service="<?= (int)$item['ID'] ?>">
                    Записаться<span class="lt-sr"> на услугу «<?= htmlspecialcharsbx($item['NAME']) ?>»</span>
                </a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
