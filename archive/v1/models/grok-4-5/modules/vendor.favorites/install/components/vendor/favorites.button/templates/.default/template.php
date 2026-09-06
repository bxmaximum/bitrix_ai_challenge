<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

$buttonId = htmlspecialcharsbx((string)$arResult['BUTTON_ID']);
$size = htmlspecialcharsbx((string)$arResult['BUTTON_SIZE']);
$inFavorites = !empty($arResult['IN_FAVORITES']);
$showCounter = !empty($arResult['SHOW_COUNTER']);
$count = $arResult['COUNT'];
$productId = (int)$arResult['PRODUCT_ID'];

$label = $inFavorites
	? (string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_REMOVE')
	: (string)Loc::getMessage('VENDOR_FAVORITES_BUTTON_ADD');

$config = [
	'productId' => $productId,
	'showCounter' => $showCounter,
	'inFavorites' => $inFavorites,
	'count' => $count,
];
?>
<button
	type="button"
	id="<?= $buttonId ?>"
	class="vf-fav-btn vf-fav-btn--<?= $size ?><?= $inFavorites ? ' is-active' : '' ?>"
	aria-pressed="<?= $inFavorites ? 'true' : 'false' ?>"
	aria-label="<?= htmlspecialcharsbx($label) ?>"
	data-product-id="<?= $productId ?>"
>
	<span class="vf-fav-btn__icon" aria-hidden="true">
		<svg class="vf-fav-btn__heart vf-fav-btn__heart--outline" viewBox="0 0 24 24" width="24" height="24" focusable="false">
			<path d="M12 21s-6.7-4.35-9.33-7.4C.7 11.3.5 8.2 2.4 6.3c1.7-1.7 4.4-1.7 6.1 0L12 9.8l3.5-3.5c1.7-1.7 4.4-1.7 6.1 0 1.9 1.9 1.7 5-.27 7.3C18.7 16.65 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
		</svg>
		<svg class="vf-fav-btn__heart vf-fav-btn__heart--filled" viewBox="0 0 24 24" width="24" height="24" focusable="false">
			<path d="M12 21s-6.7-4.35-9.33-7.4C.7 11.3.5 8.2 2.4 6.3c1.7-1.7 4.4-1.7 6.1 0L12 9.8l3.5-3.5c1.7-1.7 4.4-1.7 6.1 0 1.9 1.9 1.7 5-.27 7.3C18.7 16.65 12 21 12 21z" fill="currentColor"/>
		</svg>
	</span>
	<span class="vf-fav-btn__label"><?= htmlspecialcharsbx($label) ?></span>
	<?php if ($showCounter): ?>
		<span class="vf-fav-btn__counter" data-role="counter"><?= (int)$count ?></span>
	<?php endif; ?>
	<span class="vf-fav-btn__burst" aria-hidden="true"></span>
</button>
<script>
	BX.ready(function () {
		if (window.VendorFavoritesButton) {
			window.VendorFavoritesButton.init(
				'<?= CUtil::JSEscape($buttonId) ?>',
				<?= Json::encode($config) ?>
			);
		}
	});
</script>
