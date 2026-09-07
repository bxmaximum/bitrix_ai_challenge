<?php

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

Loc::loadMessages(__FILE__);

$config = [
	'ajaxUrl' => $arResult['AJAX_URL'],
	'actionSlots' => $arResult['ACTION_SLOTS'],
	'actionCreate' => $arResult['ACTION_CREATE'],
	'sessid' => $arResult['SESSID'],
	'weekStart' => $arResult['WEEK_START'],
	'weekMin' => $arResult['WEEK_MIN'],
	'weekMax' => $arResult['WEEK_MAX'],
];
?>
<section class="bk" data-booking data-config="<?= htmlspecialcharsbx(json_encode($config, JSON_UNESCAPED_UNICODE)) ?>"
		 aria-labelledby="bkHeading">
	<h3 class="bk__title" id="bkHeading">Выберите время визита</h3>

	<form class="bk__form" novalidate data-form>
		<div class="bk__row">
			<div class="bk__field">
				<label class="bk__label" for="bkService">Услуга</label>
				<div class="bk__control bk__control--select">
					<select class="bk__input" id="bkService" name="serviceId" required data-service>
						<?php foreach ($arResult['SERVICES'] as $service): ?>
							<option value="<?= (int)$service['id'] ?>"
									data-price="<?= (int)$service['price'] ?>"
									data-duration="<?= (int)$service['duration'] ?>">
								<?= htmlspecialcharsbx($service['name']) ?> — <?= number_format((int)$service['price'], 0, ',', ' ') ?> ₽
							</option>
						<?php endforeach; ?>
					</select>
					<span class="bk__caret" aria-hidden="true"></span>
				</div>
			</div>

			<div class="bk__field">
				<label class="bk__label" for="bkMaster">Мастер</label>
				<div class="bk__control bk__control--select">
					<select class="bk__input" id="bkMaster" name="masterId" required data-master>
						<?php foreach ($arResult['MASTERS'] as $master): ?>
							<option value="<?= (int)$master['id'] ?>">
								<?= htmlspecialcharsbx($master['name']) ?><?= $master['speciality'] !== '' ? ' — ' . htmlspecialcharsbx($master['speciality']) : '' ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="bk__caret" aria-hidden="true"></span>
				</div>
			</div>
		</div>

		<div class="bk__schedule">
			<div class="bk__weeknav">
				<button class="round-btn round-btn--light" type="button" data-week-prev aria-label="Предыдущая неделя">
					<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 2 4 8l6 6" stroke="currentColor" stroke-width="1.5"/></svg>
				</button>
				<p class="bk__week" data-week-label>&nbsp;</p>
				<button class="round-btn round-btn--light" type="button" data-week-next aria-label="Следующая неделя">
					<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m6 2 6 6-6 6" stroke="currentColor" stroke-width="1.5"/></svg>
				</button>
			</div>

			<div class="bk__slots" data-slots role="listbox" aria-label="Свободное время на неделю" aria-busy="true">
				<p class="bk__slots-empty">Загружаем расписание…</p>
			</div>

			<p class="bk__hint" data-slot-hint>Занятое время остаётся в списке, но выбрать его нельзя.</p>
		</div>

		<div class="bk__row">
			<div class="bk__field">
				<label class="bk__label" for="bkName">Как вас зовут</label>
				<div class="bk__control">
					<input class="bk__input" id="bkName" name="name" type="text" required maxlength="120"
						   autocomplete="name" placeholder="Анна Петрова" data-name>
				</div>
			</div>

			<div class="bk__field">
				<label class="bk__label" for="bkPhone">Телефон</label>
				<div class="bk__control">
					<input class="bk__input" id="bkPhone" name="phone" type="tel" required maxlength="32"
						   autocomplete="tel" inputmode="tel" placeholder="+7 999 000-00-00" data-phone>
				</div>
			</div>
		</div>

		<div class="bk__consent">
			<input class="bk__checkbox" id="bkConsent" name="consent" type="checkbox" value="1" required data-consent>
			<label class="bk__consent-text" for="bkConsent">
				Согласна на <a href="<?= htmlspecialcharsbx($arParams['CONSENT_URL']) ?>" target="_blank" rel="noopener">обработку
				персональных данных</a>: имени и телефона — чтобы студия подтвердила запись.
			</label>
		</div>

		<p class="bk__message" role="status" data-message></p>

		<button class="btn btn--solid bk__submit" type="submit" data-submit>Записаться</button>
	</form>

	<div class="bk__success" role="status" hidden data-success>
		<p class="bk__success-kicker">Записано</p>
		<h4 class="bk__success-title" data-success-title>Ждём вас</h4>
		<dl class="bk__success-list">
			<div><dt>Услуга</dt><dd data-success-service></dd></div>
			<div><dt>Мастер</dt><dd data-success-master></dd></div>
			<div><dt>Дата и время</dt><dd data-success-time></dd></div>
			<div><dt>Телефон</dt><dd data-success-phone></dd></div>
			<div><dt>Номер заявки</dt><dd data-success-id></dd></div>
		</dl>
		<p class="bk__success-note">
			Администратор перезвонит, чтобы подтвердить визит. Если планы поменяются — позвоните нам до 20:00.
		</p>
		<button class="btn bk__again" type="button" data-again>Записать ещё кого-то</button>
	</div>
</section>
