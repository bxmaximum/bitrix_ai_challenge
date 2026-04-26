<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);
\CJSCore::Init(['ajax']);

$this->setFrameMode(true);

$buttonId = 'vendor-favorites-button-' . $arResult['PRODUCT_ID'] . '-' . random_int(1000, 999999);
$title = $arResult['IS_FAVORITE']
    ? Loc::getMessage('VENDOR_FAVORITES_BUTTON_REMOVE')
    : Loc::getMessage('VENDOR_FAVORITES_BUTTON_ADD');
?>
<button
    id="<?= htmlspecialcharsbx($buttonId) ?>"
    class="vf-button vf-button--<?= htmlspecialcharsbx($arResult['BUTTON_SIZE']) ?><?= $arResult['IS_FAVORITE'] ? ' is-active' : '' ?>"
    type="button"
    data-product-id="<?= (int)$arResult['PRODUCT_ID'] ?>"
    data-active="<?= $arResult['IS_FAVORITE'] ? 'Y' : 'N' ?>"
    aria-pressed="<?= $arResult['IS_FAVORITE'] ? 'true' : 'false' ?>"
    aria-label="<?= htmlspecialcharsbx($title) ?>"
>
    <span class="vf-button__icon" aria-hidden="true"></span>
    <span class="vf-button__text">
        <?= htmlspecialcharsbx($title) ?>
    </span>
    <?php if ($arResult['SHOW_COUNTER']): ?>
        <span class="vf-button__counter" data-role="counter" data-counter>
            <?= (int)$arResult['COUNTER'] ?>
        </span>
    <?php endif; ?>
</button>
<script>
BX.ready(function () {
    new BX.VendorFavoritesButton(<?= Json::encode([
        'buttonId' => $buttonId,
        'addTitle' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_ADD'),
        'removeTitle' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_REMOVE'),
        'errorTitle' => Loc::getMessage('VENDOR_FAVORITES_BUTTON_ERROR'),
    ]) ?>);
});
</script>
