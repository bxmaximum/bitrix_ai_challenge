<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */

Loc::loadMessages(__FILE__);

// В разметке sessid и «сегодня»: композитный кеш для блока не подходит.
$this->setFrameMode(false);

$uid = 'bk-' . $this->randString();
$hasCatalog = $arResult['SERVICES'] && $arResult['MASTERS'];
$phone = $arParams['PHONE'];
$phoneHref = 'tel:' . preg_replace('/[^\d+]/', '', $phone);
?>
<div class="bk" data-booking-form data-config="<?= htmlspecialcharsbx(Json::encode($arResult['CONFIG'])) ?>">
	<?php if (!$hasCatalog): ?>
		<p class="bk-message bk-message--info" role="status">
			<?= Loc::getMessage('BXMAX_BOOKING_FORM_EMPTY') ?>
			<?php if ($phone !== ''): ?>
				<a href="<?= htmlspecialcharsbx($phoneHref) ?>"><?= htmlspecialcharsbx($phone) ?></a>
			<?php endif; ?>
		</p>
	<?php else: ?>
		<form class="bk__form" data-role="form" novalidate>
			<div class="bk__row bk__row--stack">
				<div class="bk-field">
					<label class="bk-label" for="<?= $uid ?>-service"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SERVICE') ?></label>
					<select class="bk-control bk-control--select" id="<?= $uid ?>-service" name="serviceId" data-role="service" required
						aria-describedby="<?= $uid ?>-service-error">
						<?php foreach ($arResult['SERVICES'] as $service): ?>
							<option value="<?= (int)$service['id'] ?>">
								<?= htmlspecialcharsbx($service['name']) ?> — <?= number_format($service['price'], 0, '', ' ') ?> ₽
							</option>
						<?php endforeach; ?>
					</select>
					<p class="bk-error" id="<?= $uid ?>-service-error" data-error-for="serviceId" hidden></p>
				</div>
				<div class="bk-field">
					<label class="bk-label" for="<?= $uid ?>-master"><?= Loc::getMessage('BXMAX_BOOKING_FORM_MASTER') ?></label>
					<select class="bk-control bk-control--select" id="<?= $uid ?>-master" name="masterId" data-role="master" required>
						<?php foreach ($arResult['MASTERS'] as $master): ?>
							<option value="<?= (int)$master['id'] ?>"><?= htmlspecialcharsbx($master['name']) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="bk-cal">
				<div class="bk-cal__head">
					<p class="bk-label bk-cal__title" id="<?= $uid ?>-cal-title"><?= Loc::getMessage('BXMAX_BOOKING_FORM_TIME') ?></p>
					<div class="bk-cal__nav">
						<button type="button" class="bk-cal__arrow" data-role="prev" aria-label="<?= Loc::getMessage('BXMAX_BOOKING_FORM_PREV_WEEK') ?>">
							<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M14.5 5.5 8 12l6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<p class="bk-cal__range" data-role="range" aria-live="polite"></p>
						<button type="button" class="bk-cal__arrow" data-role="next" aria-label="<?= Loc::getMessage('BXMAX_BOOKING_FORM_NEXT_WEEK') ?>">
							<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M9.5 5.5 16 12l-6.5 6.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
					</div>
				</div>

				<div class="bk-grid" role="grid" data-role="grid" aria-busy="true"
					aria-labelledby="<?= $uid ?>-cal-title"
					aria-describedby="<?= $uid ?>-cal-hint <?= $uid ?>-slot-error"></div>

				<p class="bk-cal__hint" id="<?= $uid ?>-cal-hint"><?= Loc::getMessage('BXMAX_BOOKING_FORM_GRID_HINT') ?></p>
				<p class="bk-error" id="<?= $uid ?>-slot-error" data-error-for="slotId" hidden></p>
				<p class="bk-sr" role="status" data-role="grid-status"></p>
			</div>

			<p class="bk-choice" data-role="choice" aria-live="polite"><?= Loc::getMessage('BXMAX_BOOKING_FORM_NO_CHOICE') ?></p>

			<div class="bk__row">
				<div class="bk-field">
					<label class="bk-label" for="<?= $uid ?>-name"><?= Loc::getMessage('BXMAX_BOOKING_FORM_NAME_LABEL') ?></label>
					<input class="bk-control" type="text" id="<?= $uid ?>-name" name="name" autocomplete="given-name"
						maxlength="60" required placeholder="<?= Loc::getMessage('BXMAX_BOOKING_FORM_NAME_PLACEHOLDER') ?>"
						aria-describedby="<?= $uid ?>-name-error">
					<p class="bk-error" id="<?= $uid ?>-name-error" data-error-for="name" hidden></p>
				</div>
				<div class="bk-field">
					<label class="bk-label" for="<?= $uid ?>-phone"><?= Loc::getMessage('BXMAX_BOOKING_FORM_PHONE_LABEL') ?></label>
					<input class="bk-control" type="tel" id="<?= $uid ?>-phone" name="phone" autocomplete="tel" inputmode="tel"
						maxlength="20" required placeholder="+7 900 000-00-00"
						aria-describedby="<?= $uid ?>-phone-error">
					<p class="bk-error" id="<?= $uid ?>-phone-error" data-error-for="phone" hidden></p>
				</div>
			</div>

			<div class="bk-field bk-consent">
				<input class="bk-consent__input" type="checkbox" id="<?= $uid ?>-consent" name="consent" value="Y" required
					aria-describedby="<?= $uid ?>-consent-error">
				<label class="bk-consent__label" for="<?= $uid ?>-consent">
					<?= Loc::getMessage('BXMAX_BOOKING_FORM_CONSENT', ['#URL#' => htmlspecialcharsbx($arParams['POLICY_URL'])]) ?>
				</label>
				<p class="bk-error" id="<?= $uid ?>-consent-error" data-error-for="consent" hidden></p>
			</div>

			<div class="bk-message" role="status" aria-live="polite" data-role="message"></div>

			<button type="submit" class="bk-submit" data-role="submit">
				<span data-role="submit-text"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUBMIT') ?></span>
			</button>

			<noscript>
				<p class="bk-message bk-message--info">
					<?= Loc::getMessage('BXMAX_BOOKING_FORM_NOSCRIPT') ?>
					<?php if ($phone !== ''): ?>
						<a href="<?= htmlspecialcharsbx($phoneHref) ?>"><?= htmlspecialcharsbx($phone) ?></a>
					<?php endif; ?>
				</p>
			</noscript>
		</form>

		<div class="bk-success" role="status" tabindex="-1" data-role="success" hidden>
			<p class="bk-success__title" data-role="success-title"></p>
			<dl class="bk-success__list" data-role="success-list"></dl>
			<p class="bk-success__note" data-role="success-note"></p>
			<button type="button" class="bk-submit bk-submit--ghost" data-role="again"><?= Loc::getMessage('BXMAX_BOOKING_FORM_AGAIN') ?></button>
		</div>
	<?php endif; ?>
</div>
