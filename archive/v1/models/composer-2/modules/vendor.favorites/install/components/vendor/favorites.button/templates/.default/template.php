<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var array $arParams */

$productId = (int) $arResult['PRODUCT_ID'];
$inFav = (bool) $arResult['IN_FAVORITES'];
$count = (int) $arResult['FAVORITES_COUNT'];
$size = (string) $arResult['BUTTON_SIZE'];
$showCounter = ($arResult['SHOW_COUNTER'] ?? 'N') === 'Y';
$actionBase = (string) $arResult['AJAX_ACTION_BASE'];
$syncClient = ($arResult['SYNC_STATE_ON_CLIENT'] ?? 'Y') === 'Y';
$sizeClass = 'vendor-fav-btn--' . htmlspecialcharsbx($size);
$pendingClass = $syncClient ? ' vendor-fav-wrap--pending' : '';

$this->addExternalCss($templateFolder . '/style.css');
$this->addExternalJs($templateFolder . '/script.js');

$uid = 'vf_' . md5($templateFolder . '_' . $productId);
?>
<div class="vendor-fav-wrap <?= $sizeClass ?><?= $pendingClass ?>"
     id="<?= htmlspecialcharsbx($uid) ?>"
     data-product-id="<?= $productId ?>"
     data-in-favorites="<?= $inFav ? '1' : '0' ?>"
     data-action-base="<?= htmlspecialcharsbx($actionBase) ?>"
     data-show-counter="<?= $showCounter ? '1' : '0' ?>"
     data-count="<?= $count ?>"
     data-sync-on-client="<?= $syncClient ? '1' : '0' ?>">
    <button type="button"
            class="vendor-fav-btn<?= $inFav ? ' vendor-fav-btn--active' : '' ?>"
            aria-pressed="<?= $inFav ? 'true' : 'false' ?>"
            aria-label="<?= $inFav ? 'Удалить из избранного' : 'Добавить в избранное' ?>">
        <span class="vendor-fav-btn__icon" aria-hidden="true">
            <svg class="vendor-fav-btn__heart vendor-fav-btn__heart--line" viewBox="0 0 24 24" width="1em" height="1em" fill="none">
                <path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"
                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 10-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <svg class="vendor-fav-btn__heart vendor-fav-btn__heart--fill" viewBox="0 0 24 24" width="1em" height="1em">
                <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
        </span>
        <?php if ($showCounter) { ?>
            <span class="vendor-fav-btn__counter"><?= $count ?></span>
        <?php } ?>
    </button>
</div>
