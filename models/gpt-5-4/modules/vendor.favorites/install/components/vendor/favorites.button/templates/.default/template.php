<?php
declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

\Bitrix\Main\UI\Extension::load('main.core');
$this->setFrameMode(true);
$frame = $this->createFrame()->begin(false);

$componentId = 'vendor-favorites-' . $this->randString(8);
$isDisabled = !$arResult['IS_ENABLED'] || (int) $arResult['PRODUCT_ID'] < 1;
$buttonClasses = [
    'vfavorites-button',
    'vfavorites-button--' . $arResult['BUTTON_SIZE'],
];

if ($arResult['IS_FAVORITE']) {
    $buttonClasses[] = 'is-active';
}

if ($isDisabled) {
    $buttonClasses[] = 'is-disabled';
}

$config = [
    'actionAdd' => $arResult['ACTION_ADD'],
    'actionRemove' => $arResult['ACTION_REMOVE'],
    'actionList' => $arResult['ACTION_LIST'],
    'productId' => (int) $arResult['PRODUCT_ID'],
    'sessid' => (string) $arResult['SESSID'],
    'isFavorite' => (bool) $arResult['IS_FAVORITE'],
];
?>
<div id="<?= htmlspecialcharsbx($componentId) ?>" class="vfavorites">
    <button
        class="<?= htmlspecialcharsbx(implode(' ', $buttonClasses)) ?>"
        type="button"
        aria-pressed="<?= $arResult['IS_FAVORITE'] ? 'true' : 'false' ?>"
        <?= $isDisabled ? 'disabled' : '' ?>
    >
        <span class="vfavorites-button__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09A6.03 6.03 0 0 1 16.5 3C19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
            </svg>
        </span>
        <span class="vfavorites-button__label">
            <?= $arResult['IS_FAVORITE'] ? 'В избранном' : 'В избранное' ?>
        </span>
        <?php if ($arResult['SHOW_COUNTER']): ?>
            <span class="vfavorites-button__counter" data-role="counter">
                <?= (int) $arResult['COUNT'] ?>
            </span>
        <?php endif; ?>
    </button>
</div>
<script>
    BX.ready(function () {
        if (window.VendorFavoritesButton) {
            window.VendorFavoritesButton.init(
                '<?= \CUtil::JSEscape($componentId) ?>',
                <?= \Bitrix\Main\Web\Json::encode($config) ?>
            );
        }
    });
</script>
<?php $frame->end(); ?>
