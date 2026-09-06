<?php
declare(strict_types=1);

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load('main.core');
Asset::getInstance()->addCss($templateFolder . '/style.css');
Asset::getInstance()->addJs($templateFolder . '/script.js');

$buttonId = 'vendor-favorites-btn-' . (int) ($arResult['PRODUCT_ID'] ?? 0) . '-' . substr(md5((string) microtime(true)), 0, 8);
$isActive = !empty($arResult['IS_FAVORITE']);
$size = (string) ($arResult['BUTTON_SIZE'] ?? 'medium');
?>

<?php if (!empty($arResult['ENABLED'])): ?>
	<button
		type="button"
		id="<?= htmlspecialcharsbx($buttonId) ?>"
		class="vendor-favorites-btn vendor-favorites-btn--<?= htmlspecialcharsbx($size) ?> <?= $isActive ? 'is-active' : '' ?>"
		data-product-id="<?= (int) $arResult['PRODUCT_ID'] ?>"
		data-action-add="<?= htmlspecialcharsbx((string) $arResult['ACTION_ADD']) ?>"
		data-action-remove="<?= htmlspecialcharsbx((string) $arResult['ACTION_REMOVE']) ?>"
		data-show-counter="<?= !empty($arResult['SHOW_COUNTER']) ? 'Y' : 'N' ?>"
		data-counter="<?= (int) ($arResult['COUNTER'] ?? 0) ?>"
		aria-pressed="<?= $isActive ? 'true' : 'false' ?>"
		aria-label="<?= $isActive ? 'Удалить из избранного' : 'Добавить в избранное' ?>"
	>
		<span class="vendor-favorites-btn__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" width="24" height="24">
				<path d="M12 21s-7.2-4.6-9.7-8.4C.2 9.1 1.4 6 4.4 4.8c1.8-.7 3.7-.2 5 1.2L12 8.7l2.6-2.7c1.3-1.4 3.2-1.9 5-1.2 3 1.2 4.2 4.3 2.1 7.8C19.2 16.4 12 21 12 21z"></path>
			</svg>
		</span>

		<?php if (!empty($arResult['SHOW_COUNTER'])): ?>
			<span class="vendor-favorites-btn__counter"><?= (int) ($arResult['COUNTER'] ?? 0) ?></span>
		<?php endif; ?>
	</button>
<?php endif; ?>


