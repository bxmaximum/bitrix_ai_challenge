<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult
 *  @var string $templateFolder
 *  @var CMain $APPLICATION
 */

$services = $arResult['SERVICES'];
$masters = $arResult['MASTERS'];
$config = $arResult['CONFIG'];
$uid = 'bw' . substr(md5(uniqid('', true)), 0, 6);

// style.css шаблона компонента подключает сам Битрикс (SetAdditionalCSS).
?>
<div class="bw"
	id="<?= $uid ?>"
	data-booking-widget
	data-ajax-url="<?= htmlspecialcharsbx($arResult['AJAX_URL']) ?>"
	data-sessid="<?= htmlspecialcharsbx($arResult['SESSID']) ?>"
	data-action-slots="<?= htmlspecialcharsbx($arResult['ACTION_SLOTS']) ?>"
	data-action-book="<?= htmlspecialcharsbx($arResult['ACTION_BOOK']) ?>"
	data-week-start="<?= htmlspecialcharsbx($arResult['WEEK_START']) ?>"
	data-weeks="<?= (int)$arResult['WEEKS_AHEAD'] ?>"
	data-config="<?= htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($config)) ?>">

	<form class="bw__form" data-bw-form novalidate>
		<fieldset class="bw__block">
			<legend class="bw__legend"><span class="bw__step">1</span> Услуга</legend>
			<div class="bw__chips">
				<?php foreach ($services as $index => $service): ?>
					<label class="bw-chip">
						<input class="bw-chip__input" type="radio" name="serviceId"
							value="<?= (int)$service['ID'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
						<span class="bw-chip__body">
							<span class="bw-chip__name"><?= htmlspecialcharsbx($service['NAME']) ?></span>
							<span class="bw-chip__meta">
								<?= htmlspecialcharsbx(number_format($service['PRICE'], 0, ',', ' ') . ' ₽') ?>
								· <?= (int)$service['DURATION'] ?> мин
							</span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset class="bw__block">
			<legend class="bw__legend"><span class="bw__step">2</span> Мастер</legend>
			<div class="bw__chips bw__chips--masters">
				<?php foreach ($masters as $index => $master): ?>
					<label class="bw-chip bw-chip--master">
						<input class="bw-chip__input" type="radio" name="masterId"
							value="<?= (int)$master['ID'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
						<span class="bw-chip__body">
							<span class="bw-chip__name"><?= htmlspecialcharsbx($master['NAME']) ?></span>
							<span class="bw-chip__meta"><?= htmlspecialcharsbx($master['SPECIALIZATION']) ?></span>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<fieldset class="bw__block">
			<legend class="bw__legend"><span class="bw__step">3</span> Время</legend>

			<div class="bw__week">
				<button class="bw__week-btn" type="button" data-bw-week-prev aria-label="Предыдущая неделя">
					<svg width="14" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true" focusable="false">
						<path d="M14 5H1m4-4L1 5l4 4" stroke="currentColor" stroke-width="1"/>
					</svg>
				</button>
				<p class="bw__week-label" data-bw-week-label role="status" aria-live="polite"></p>
				<button class="bw__week-btn" type="button" data-bw-week-next aria-label="Следующая неделя">
					<svg width="14" height="10" viewBox="0 0 14 10" fill="none" aria-hidden="true" focusable="false">
						<path d="M0 5h13m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1"/>
					</svg>
				</button>
			</div>

			<div class="bw__calendar" data-bw-calendar role="listbox" aria-label="Свободное время" aria-busy="true"></div>
		</fieldset>

		<fieldset class="bw__block">
			<legend class="bw__legend"><span class="bw__step">4</span> Контакты</legend>

			<div class="bw__fields">
				<p class="bw__field">
					<label class="bw__label" for="<?= $uid ?>-name">Имя</label>
					<input class="bw__input" id="<?= $uid ?>-name" type="text" name="name"
						autocomplete="name" required data-bw-name>
				</p>
				<p class="bw__field">
					<label class="bw__label" for="<?= $uid ?>-phone">Телефон</label>
					<input class="bw__input" id="<?= $uid ?>-phone" type="tel" name="phone"
						autocomplete="tel" inputmode="tel" required data-bw-phone>
				</p>
			</div>

			<label class="bw__consent">
				<input class="bw__checkbox" type="checkbox" name="consent" value="Y" data-bw-consent>
				<span>Согласен на обработку персональных данных и подтверждаю, что ознакомлен с политикой конфиденциальности.</span>
			</label>
		</fieldset>

		<p class="bw__message" data-bw-message role="status" aria-live="polite"></p>
		<p class="visually-hidden" data-bw-live role="status" aria-live="polite"></p>

		<button class="bw__submit" type="submit" data-bw-submit>Записаться</button>
	</form>

	<div class="bw__success" data-bw-success role="status" tabindex="-1" hidden>
		<p class="bw__success-title">Вы записаны</p>
		<p class="bw__success-lead">Мы отправили детали администратору студии. Если что-то изменится — позвоним.</p>
		<dl class="bw__summary" data-bw-summary></dl>
		<button class="bw__again" type="button" data-bw-again>Записаться ещё раз</button>
	</div>
</div>

<?php $scriptVersion = (int)@filemtime(__DIR__ . '/script.js'); ?>
<script defer src="<?= $templateFolder ?>/script.js?v=<?= $scriptVersion ?>"></script>
