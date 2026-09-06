<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array<string, mixed> $arResult */
/** @var CBitrixComponentTemplate $this */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

if (!$arResult['ENABLED']) {
    return;
}

$this->addExternalCss($templateFolder . '/style.css');
$this->addExternalJs($templateFolder . '/script.js');

$isFavorite = $arResult['IS_FAVORITE'] ? 'true' : 'false';
$sizeClass = 'vf-btn--' . htmlspecialcharsbx((string) $arResult['BUTTON_SIZE']);

// Динамическая область: не кешировать состояние «в избранном» вместе с карточкой товара.
$frame = $this->createFrame('vf_favorites_' . (int) $arResult['PRODUCT_ID']);
$frame->begin();
?>
<div
    class="vf-favorites <?= $sizeClass ?>"
    data-vf-favorites
    data-product-id="<?= (int) $arResult['PRODUCT_ID'] ?>"
    data-is-favorite="<?= $isFavorite ?>"
    data-config="<?= htmlspecialcharsbx(Json::encode([
        'productId' => (int) $arResult['PRODUCT_ID'],
        'isFavorite' => (bool) $arResult['IS_FAVORITE'],
        'showCounter' => (bool) $arResult['SHOW_COUNTER'],
        'favoriteCount' => (int) $arResult['FAVORITE_COUNT'],
    ])) ?>"
>
    <button
        type="button"
        class="vf-favorites__btn<?= $arResult['IS_FAVORITE'] ? ' vf-favorites__btn--active' : '' ?>"
        data-vf-toggle
        aria-pressed="<?= $arResult['IS_FAVORITE'] ? 'true' : 'false' ?>"
        aria-label="<?= htmlspecialcharsbx(
            $arResult['IS_FAVORITE']
                ? Loc::getMessage('VENDOR_FAVORITES_BUTTON_REMOVE')
                : Loc::getMessage('VENDOR_FAVORITES_BUTTON_ADD'),
        ) ?>"
    >
        <span class="vf-favorites__icon" aria-hidden="true">
            <svg class="vf-favorites__heart vf-favorites__heart--outline" viewBox="0 0 24 24" focusable="false">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <svg class="vf-favorites__heart vf-favorites__heart--filled" viewBox="0 0 24 24" focusable="false">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
        </span>
        <?php if ($arResult['SHOW_COUNTER']): ?>
            <span class="vf-favorites__counter" data-vf-counter><?= (int) $arResult['FAVORITE_COUNT'] ?></span>
        <?php endif; ?>
    </button>
</div>
<?php $frame->end(); ?>
