<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

// Подключаем стили и скрипты
$this->addExternalCss($this->GetFolder() . '/style.css');
$this->addExternalJs($this->GetFolder() . '/script.js');

$uniqueId = 'vendor-favorites-' . $arParams['PRODUCT_ID'] . '-' . uniqid();

// Для гостей всегда рендерим неактивную кнопку (из-за кэширования)
// JavaScript при загрузке проверит cookie и обновит состояние
global $USER;
$isAuthorized = $USER instanceof CUser && $USER->IsAuthorized();
$isActive = $isAuthorized ? $arResult['IS_IN_FAVORITES'] : false;

$cssClasses = ['vendor-favorites-btn', 'vendor-favorites-btn--' . $arParams['BUTTON_SIZE']];
if ($isActive) {
    $cssClasses[] = 'vendor-favorites-btn--active';
}
?>

<div class="vendor-favorites-wrapper" id="<?= htmlspecialcharsbx($uniqueId) ?>">
    <button
        type="button"
        class="<?= htmlspecialcharsbx(implode(' ', $cssClasses)) ?>"
        data-product-id="<?= (int)$arParams['PRODUCT_ID'] ?>"
        data-is-active="<?= $isActive ? 'true' : 'false' ?>"
        aria-label="<?= $isActive ? 'Удалить из избранного' : 'Добавить в избранное' ?>"
        title="<?= $isActive ? 'Удалить из избранного' : 'Добавить в избранное' ?>"
    >
        <span class="vendor-favorites-btn__icon">
            <!-- Пустое сердечко -->
            <svg class="vendor-favorites-btn__icon-empty" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" stroke="currentColor" stroke-width="2" fill="none"/>
            </svg>
            <!-- Заполненное сердечко -->
            <svg class="vendor-favorites-btn__icon-filled" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/>
            </svg>
        </span>
        <?php if ($arParams['SHOW_COUNTER'] && $arResult['FAVORITES_COUNT'] > 0): ?>
            <span class="vendor-favorites-btn__counter"><?= (int)$arResult['FAVORITES_COUNT'] ?></span>
        <?php endif; ?>
        <span class="vendor-favorites-btn__ripple"></span>
    </button>
</div>

<script>
    (function() {
        'use strict';
        
        if (typeof window.VendorFavoritesButtons === 'undefined') {
            window.VendorFavoritesButtons = {};
        }
        
        window.VendorFavoritesButtons['<?= $uniqueId ?>'] = {
            productId: <?= (int)$arParams['PRODUCT_ID'] ?>,
            isAuthorized: <?= $isAuthorized ? 'true' : 'false' ?>,
            isInFavorites: <?= $isActive ? 'true' : 'false' ?>,
            showCounter: <?= $arParams['SHOW_COUNTER'] ? 'true' : 'false' ?>,
            buttonSize: '<?= htmlspecialcharsbx($arParams['BUTTON_SIZE']) ?>',
            sessid: '<?= bitrix_sessid() ?>'
        };
        
        // Инициализация после загрузки DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                VendorFavorites.init('<?= $uniqueId ?>');
            });
        } else {
            VendorFavorites.init('<?= $uniqueId ?>');
        }
    })();
</script>
