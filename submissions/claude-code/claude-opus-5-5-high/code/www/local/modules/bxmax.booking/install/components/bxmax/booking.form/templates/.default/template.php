<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

$id = $arResult['FORM_ID'];
$config = [
    'apiSlots' => $arResult['API_SLOTS'],
    'apiCreate' => $arResult['API_CREATE'],
    'sessid' => $arResult['SESSID'],
    'weekMin' => $arResult['WEEK_MIN'],
    'weekMax' => $arResult['WEEK_MAX'],
    'services' => $arResult['SERVICES'],
    'masters' => $arResult['MASTERS'],
];
$formatPrice = static fn (int $price): string => number_format($price, 0, ',', ' ');
?>
<div class="bf" data-booking-form data-config="<?= htmlspecialcharsbx(Json::encode($config)) ?>">
    <?php if (empty($arResult['SERVICES']) || empty($arResult['MASTERS'])): ?>
        <p class="bf__empty"><?= Loc::getMessage('BXMAX_BOOKING_FORM_EMPTY') ?></p>
    <?php else: ?>
    <form class="bf__form" novalidate aria-describedby="<?= $id ?>-status">
        <input type="hidden" name="slotId" value="">

        <fieldset class="bf__step" data-step="service">
            <legend class="bf__legend">
                <span class="bf__num" aria-hidden="true">01</span>
                <?= Loc::getMessage('BXMAX_BOOKING_FORM_STEP_SERVICE') ?>
            </legend>
            <div class="bf__services">
                <?php foreach ($arResult['SERVICES'] as $service): ?>
                    <label class="bf-service">
                        <input class="bf-service__input" type="radio" name="serviceId" value="<?= (int)$service['id'] ?>">
                        <span class="bf-service__name"><?= htmlspecialcharsbx($service['name']) ?></span>
                        <span class="bf-service__meta">
                            <?= Loc::getMessage('BXMAX_BOOKING_FORM_DURATION', ['#MIN#' => (int)$service['duration']]) ?>
                            <span aria-hidden="true">•</span>
                            <?= Loc::getMessage('BXMAX_BOOKING_FORM_PRICE', ['#PRICE#' => $formatPrice((int)$service['price'])]) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="bf__error" id="<?= $id ?>-serviceId-error" data-error-for="serviceId" hidden></p>
        </fieldset>

        <fieldset class="bf__step" data-step="master">
            <legend class="bf__legend">
                <span class="bf__num" aria-hidden="true">02</span>
                <?= Loc::getMessage('BXMAX_BOOKING_FORM_STEP_MASTER') ?>
            </legend>
            <div class="bf__masters">
                <?php foreach ($arResult['MASTERS'] as $master): ?>
                    <label class="bf-master" data-master-services="<?= htmlspecialcharsbx(implode(',', $master['serviceIds'])) ?>">
                        <input class="bf-master__input" type="radio" name="masterId" value="<?= (int)$master['id'] ?>">
                        <?php if ($master['photo']): ?>
                            <img class="bf-master__photo" src="<?= htmlspecialcharsbx($master['photo']) ?>" alt="" width="48" height="48" loading="lazy">
                        <?php endif; ?>
                        <span class="bf-master__text">
                            <span class="bf-master__name"><?= htmlspecialcharsbx($master['name']) ?></span>
                            <span class="bf-master__spec"><?= htmlspecialcharsbx($master['specialization']) ?></span>
                            <span class="bf-master__na" hidden><?= Loc::getMessage('BXMAX_BOOKING_FORM_MASTER_NA') ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <fieldset class="bf__step" data-step="slot">
            <legend class="bf__legend" id="<?= $id ?>-slots-label">
                <span class="bf__num" aria-hidden="true">03</span>
                <?= Loc::getMessage('BXMAX_BOOKING_FORM_STEP_SLOT') ?>
            </legend>
            <div class="bf-week">
                <button type="button" class="bf-week__btn" data-week="-1" aria-label="<?= Loc::getMessage('BXMAX_BOOKING_FORM_WEEK_PREV') ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M20 12H4m6-6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </button>
                <p class="bf-week__range" aria-live="polite" data-week-range></p>
                <button type="button" class="bf-week__btn" data-week="1" aria-label="<?= Loc::getMessage('BXMAX_BOOKING_FORM_WEEK_NEXT') ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
                </button>
            </div>
            <p class="bf__hint" id="<?= $id ?>-slots-help"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SLOTS_HELP') ?></p>
            <p class="bf__placeholder" data-slots-placeholder><?= Loc::getMessage('BXMAX_BOOKING_FORM_SLOTS_CHOOSE_MASTER') ?></p>
            <div class="bf-slots" role="listbox" hidden
                 aria-labelledby="<?= $id ?>-slots-label"
                 aria-describedby="<?= $id ?>-slots-help"
                 data-slots></div>
            <ul class="bf-legend" aria-hidden="true">
                <li><span class="bf-legend__dot bf-legend__dot--free"></span><?= Loc::getMessage('BXMAX_BOOKING_FORM_LEGEND_FREE') ?></li>
                <li><span class="bf-legend__dot bf-legend__dot--taken"></span><?= Loc::getMessage('BXMAX_BOOKING_FORM_LEGEND_TAKEN') ?></li>
            </ul>
            <p class="bf__error" id="<?= $id ?>-slotId-error" data-error-for="slotId" hidden></p>
        </fieldset>

        <fieldset class="bf__step" data-step="contacts">
            <legend class="bf__legend">
                <span class="bf__num" aria-hidden="true">04</span>
                <?= Loc::getMessage('BXMAX_BOOKING_FORM_STEP_CONTACTS') ?>
            </legend>
            <div class="bf__fields">
                <div class="bf-field">
                    <label class="bf-field__label" for="<?= $id ?>-name"><?= Loc::getMessage('BXMAX_BOOKING_FORM_NAME') ?></label>
                    <input class="bf-field__input" id="<?= $id ?>-name" name="name" type="text"
                           autocomplete="given-name" maxlength="60" required
                           aria-describedby="<?= $id ?>-name-error">
                    <p class="bf__error" id="<?= $id ?>-name-error" data-error-for="name" hidden></p>
                </div>
                <div class="bf-field">
                    <label class="bf-field__label" for="<?= $id ?>-phone"><?= Loc::getMessage('BXMAX_BOOKING_FORM_PHONE') ?></label>
                    <input class="bf-field__input" id="<?= $id ?>-phone" name="phone" type="tel"
                           autocomplete="tel" inputmode="tel" maxlength="20" required
                           placeholder="+7 900 000-00-00"
                           aria-describedby="<?= $id ?>-phone-error">
                    <p class="bf__error" id="<?= $id ?>-phone-error" data-error-for="phone" hidden></p>
                </div>
            </div>
            <div class="bf-consent">
                <input class="bf-consent__input" id="<?= $id ?>-consent" name="consent" type="checkbox" value="Y" required
                       aria-describedby="<?= $id ?>-consent-error">
                <label class="bf-consent__label" for="<?= $id ?>-consent">
                    <?= Loc::getMessage('BXMAX_BOOKING_FORM_CONSENT', ['#URL#' => htmlspecialcharsbx($arParams['POLICY_URL'])]) ?>
                </label>
                <p class="bf__error" id="<?= $id ?>-consent-error" data-error-for="consent" hidden></p>
            </div>
        </fieldset>

        <div class="bf__submit">
            <p class="bf__summary" data-summary aria-live="polite"></p>
            <button class="pill pill--solid bf__button" type="submit">
                <span><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUBMIT') ?></span>
            </button>
        </div>
        <p class="bf__status" id="<?= $id ?>-status" role="status" aria-live="polite" data-status></p>
    </form>

    <div class="bf-success" data-success hidden tabindex="-1" role="status" aria-live="polite">
        <p class="label"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_LABEL') ?></p>
        <h3 class="bf-success__title"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_TITLE') ?></h3>
        <dl class="bf-success__list">
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_NUMBER') ?></dt><dd data-success-field="id"></dd></div>
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_SERVICE') ?></dt><dd data-success-field="service"></dd></div>
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_MASTER') ?></dt><dd data-success-field="master"></dd></div>
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_WHEN') ?></dt><dd data-success-field="when"></dd></div>
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_NAME') ?></dt><dd data-success-field="name"></dd></div>
            <div><dt><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_PHONE') ?></dt><dd data-success-field="phone"></dd></div>
        </dl>
        <p class="bf-success__note"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_NOTE') ?></p>
        <button class="link-arrow" type="button" data-restart>
            <span class="link-arrow__circle" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>
            </span>
            <span class="link-arrow__text"><?= Loc::getMessage('BXMAX_BOOKING_FORM_SUCCESS_AGAIN') ?></span>
        </button>
    </div>
    <?php endif; ?>
</div>
