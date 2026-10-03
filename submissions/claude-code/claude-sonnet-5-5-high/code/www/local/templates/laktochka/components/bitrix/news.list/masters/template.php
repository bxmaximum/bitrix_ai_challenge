<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var array $arResult */

$years = static function (int $n): string {
    $mod10 = $n % 10;
    $mod100 = $n % 100;
    if ($mod10 === 1 && $mod100 !== 11) {
        return $n . ' год';
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return $n . ' года';
    }

    return $n . ' лет';
};
?>
<ul class="lt-masters">
    <?php foreach ($arResult['ITEMS'] as $item): ?>
        <?php
        $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
        $image = $item['LT_IMAGE']['src'] ?? ($item['PREVIEW_PICTURE']['SRC'] ?? '');
        ?>
        <li class="lt-master" id="<?= $this->GetEditAreaId($item['ID']) ?>" data-reveal>
            <div class="lt-master__photo">
                <?php if ($image): ?>
                    <img src="<?= htmlspecialcharsbx($image) ?>" width="360" height="450" loading="lazy" decoding="async"
                         alt="Портрет мастера: <?= htmlspecialcharsbx($item['NAME']) ?>">
                <?php endif; ?>
            </div>
            <h3 class="lt-master__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
            <p class="lt-master__spec"><?= htmlspecialcharsbx($item['LT_SPECIALIZATION']) ?></p>
            <?php if ($item['LT_EXPERIENCE'] > 0): ?>
                <p class="lt-master__exp">Стаж — <?= $years($item['LT_EXPERIENCE']) ?></p>
            <?php endif; ?>
            <p class="lt-master__text"><?= htmlspecialcharsbx($item['PREVIEW_TEXT']) ?></p>
            <a class="lt-btn lt-btn--dark" href="#booking" data-booking-master="<?= (int)$item['ID'] ?>">
                Записаться<span class="lt-sr"> к мастеру <?= htmlspecialcharsbx($item['NAME']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
