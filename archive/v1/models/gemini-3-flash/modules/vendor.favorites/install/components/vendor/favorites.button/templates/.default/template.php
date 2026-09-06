<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

$buttonClass = 'fav-btn fav-btn--' . $arParams['BUTTON_SIZE'];
if ($arResult['IS_FAVORITE']) {
    $buttonClass .= ' is-active';
}
?>

<div class="fav-btn-container" 
     data-product-id="<?= $arParams['PRODUCT_ID'] ?>" 
     data-is-favorite="<?= $arResult['IS_FAVORITE'] ? 'Y' : 'N' ?>">
    <button class="<?= $buttonClass ?>" 
            aria-label="<?= $arResult['IS_FAVORITE'] ? 'Удалить из избранного' : 'Добавить в избранное' ?>">
        <svg class="fav-btn__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                  fill="currentColor"/>
        </svg>
        <? if ($arParams['SHOW_COUNTER']): ?>
            <span class="fav-btn__counter"></span>
        <? endif; ?>
    </button>
</div>

