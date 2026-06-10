<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/**
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

Extension::load('ajax');

$isFavorite = (bool)$arResult['IS_FAVORITE'];
$buttonId = 'vendor-favorites-' . $arResult['PRODUCT_ID'] . '-' . $this->randString();
?>
<button
    type="button"
    id="<?= $buttonId ?>"
    class="vendor-favorites-btn vendor-favorites-btn--<?= htmlspecialcharsbx($arResult['BUTTON_SIZE']) ?><?= $isFavorite ? ' is-active' : '' ?>"
    data-product-id="<?= (int)$arResult['PRODUCT_ID'] ?>"
    aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
    title="<?= Loc::getMessage($isFavorite ? 'VENDOR_FAVORITES_BUTTON_REMOVE' : 'VENDOR_FAVORITES_BUTTON_ADD') ?>"
    data-title-add="<?= Loc::getMessage('VENDOR_FAVORITES_BUTTON_ADD') ?>"
    data-title-remove="<?= Loc::getMessage('VENDOR_FAVORITES_BUTTON_REMOVE') ?>"
>
    <span class="vendor-favorites-btn__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false">
            <path class="vendor-favorites-btn__heart" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
    </span>
    <?php if ($arResult['SHOW_COUNTER']): ?>
        <span class="vendor-favorites-btn__counter"><?= (int)$arResult['COUNTER'] ?></span>
    <?php endif; ?>
</button>
<script>
    BX.ready(function () {
        new BX.Vendor.FavoritesButton('<?= CUtil::JSEscape($buttonId) ?>');
    });
</script>
