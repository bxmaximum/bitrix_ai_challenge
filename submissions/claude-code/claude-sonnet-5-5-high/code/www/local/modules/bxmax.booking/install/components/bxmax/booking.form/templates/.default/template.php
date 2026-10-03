<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;

/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */

Loc::loadMessages(__FILE__);

$formatPrice = static fn (int $price): string => number_format($price, 0, '', "\u{00A0}") . "\u{00A0}₽";

$config = [
    'ajaxUrl' => $arParams['AJAX_URL'],
    'today' => $arResult['TODAY'],
    'weeksAhead' => $arParams['WEEKS_AHEAD'],
    'services' => array_column($arResult['SERVICES'], null, 'id'),
    'masters' => array_column($arResult['MASTERS'], null, 'id'),
    'messages' => [
        'loading' => Loc::getMessage('BXMAX_BK_LOADING'),
        'noDay' => Loc::getMessage('BXMAX_BK_NO_DAY'),
        'noSlots' => Loc::getMessage('BXMAX_BK_NO_SLOTS'),
        'taken' => Loc::getMessage('BXMAX_BK_TAKEN'),
        'selected' => Loc::getMessage('BXMAX_BK_SELECTED'),
        'errService' => Loc::getMessage('BXMAX_BK_ERR_SERVICE'),
        'errMaster' => Loc::getMessage('BXMAX_BK_ERR_MASTER'),
        'errSlot' => Loc::getMessage('BXMAX_BK_ERR_SLOT'),
        'errName' => Loc::getMessage('BXMAX_BK_ERR_NAME'),
        'errPhone' => Loc::getMessage('BXMAX_BK_ERR_PHONE'),
        'errConsent' => Loc::getMessage('BXMAX_BK_ERR_CONSENT'),
        'errNetwork' => Loc::getMessage('BXMAX_BK_ERR_NETWORK'),
        'errSlotTaken' => Loc::getMessage('BXMAX_BK_ERR_SLOT_TAKEN'),
        'errGeneric' => Loc::getMessage('BXMAX_BK_ERR_GENERIC'),
        'sending' => Loc::getMessage('BXMAX_BK_SENDING'),
        'submit' => Loc::getMessage('BXMAX_BK_SUBMIT'),
        'weekFree' => Loc::getMessage('BXMAX_BK_WEEK_FREE'),
    ],
];

$id = $arParams['ANCHOR_ID'];
?>
<section class="bk" id="<?= htmlspecialcharsbx($id) ?>" aria-labelledby="<?= htmlspecialcharsbx($id) ?>-title"
         data-bk data-config="<?= htmlspecialcharsbx(json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
    <div class="bk__inner">
        <header class="bk__head">
            <p class="bk__eyebrow"><?= Loc::getMessage('BXMAX_BK_EYEBROW') ?></p>
            <h2 class="bk__title" id="<?= htmlspecialcharsbx($id) ?>-title"><?= htmlspecialcharsbx($arParams['TITLE']) ?></h2>
            <p class="bk__lead"><?= Loc::getMessage('BXMAX_BK_LEAD') ?></p>
        </header>

        <form class="bk__form" data-bk-form novalidate autocomplete="on">
            <fieldset class="bk__fieldset">
                <legend class="bk__legend"><span class="bk__step">01</span> <?= Loc::getMessage('BXMAX_BK_STEP_SERVICE') ?></legend>
                <div class="bk__choices">
                    <?php foreach ($arResult['SERVICES'] as $service): ?>
                        <label class="bk__choice">
                            <input type="radio" name="serviceId" value="<?= (int)$service['id'] ?>" class="bk__choice-input">
                            <span class="bk__choice-face">
                                <span class="bk__choice-name"><?= htmlspecialcharsbx($service['name']) ?></span>
                                <span class="bk__choice-meta"><?= $formatPrice($service['price']) ?><?php if ($service['duration'] > 0): ?> · <?= (int)$service['duration'] ?>&nbsp;<?= Loc::getMessage('BXMAX_BK_MIN') ?><?php endif; ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="bk__error" id="bk-err-service" role="status" data-bk-error="serviceId"></p>
            </fieldset>

            <fieldset class="bk__fieldset">
                <legend class="bk__legend"><span class="bk__step">02</span> <?= Loc::getMessage('BXMAX_BK_STEP_MASTER') ?></legend>
                <div class="bk__choices">
                    <?php foreach ($arResult['MASTERS'] as $i => $master): ?>
                        <label class="bk__choice">
                            <input type="radio" name="masterId" value="<?= (int)$master['id'] ?>" class="bk__choice-input"<?= $i === 0 ? ' checked' : '' ?>>
                            <span class="bk__choice-face">
                                <span class="bk__choice-name"><?= htmlspecialcharsbx($master['name']) ?></span>
                                <span class="bk__choice-meta"><?= htmlspecialcharsbx($master['specialization']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="bk__error" id="bk-err-master" role="status" data-bk-error="masterId"></p>
            </fieldset>

            <div class="bk__fieldset bk__slots" data-bk-slots-block>
                <h3 class="bk__legend" id="<?= htmlspecialcharsbx($id) ?>-slots-title"><span class="bk__step">03</span> <?= Loc::getMessage('BXMAX_BK_STEP_SLOT') ?></h3>
                <div class="bk__week">
                    <button type="button" class="bk__arrow" data-bk-prev aria-label="<?= Loc::getMessage('BXMAX_BK_PREV_WEEK') ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M12.5 4 6.5 10l6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <p class="bk__week-label" data-bk-week-label aria-live="polite"></p>
                    <button type="button" class="bk__arrow" data-bk-next aria-label="<?= Loc::getMessage('BXMAX_BK_NEXT_WEEK') ?>">
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m7.5 4 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="bk__days" role="listbox" aria-labelledby="<?= htmlspecialcharsbx($id) ?>-slots-title" aria-describedby="bk-err-slot" data-bk-days></div>
                <p class="bk__selected" role="status" data-bk-selected></p>
                <p class="bk__error" id="bk-err-slot" role="status" data-bk-error="slotId"></p>
            </div>

            <fieldset class="bk__fieldset bk__fieldset--contacts">
                <legend class="bk__legend"><span class="bk__step">04</span> <?= Loc::getMessage('BXMAX_BK_STEP_CONTACTS') ?></legend>
                <div class="bk__fields">
                    <div class="bk__field">
                        <label for="bk-name" class="bk__label"><?= Loc::getMessage('BXMAX_BK_NAME') ?></label>
                        <input type="text" id="bk-name" name="name" class="bk__input" autocomplete="name" maxlength="100"
                               placeholder="<?= Loc::getMessage('BXMAX_BK_NAME_PH') ?>" aria-describedby="bk-err-name" required>
                        <p class="bk__error" id="bk-err-name" role="status" data-bk-error="name"></p>
                    </div>
                    <div class="bk__field">
                        <label for="bk-phone" class="bk__label"><?= Loc::getMessage('BXMAX_BK_PHONE') ?></label>
                        <input type="tel" id="bk-phone" name="phone" class="bk__input" autocomplete="tel" inputmode="tel" maxlength="25"
                               placeholder="+7 (900) 000-00-00" aria-describedby="bk-err-phone" required>
                        <p class="bk__error" id="bk-err-phone" role="status" data-bk-error="phone"></p>
                    </div>
                </div>
                <div class="bk__consent">
                    <input type="checkbox" id="bk-consent" name="consent" value="Y" class="bk__check" aria-describedby="bk-err-consent" required>
                    <label for="bk-consent" class="bk__consent-label"><?= Loc::getMessage('BXMAX_BK_CONSENT') ?></label>
                </div>
                <p class="bk__error" id="bk-err-consent" role="status" data-bk-error="consent"></p>
            </fieldset>

            <p class="bk__status" role="status" data-bk-status></p>
            <button type="submit" class="bk__submit" data-bk-submit><?= Loc::getMessage('BXMAX_BK_SUBMIT') ?></button>
        </form>

        <div class="bk__success" data-bk-success hidden tabindex="-1" role="status">
            <p class="bk__eyebrow"><?= Loc::getMessage('BXMAX_BK_OK_EYEBROW') ?></p>
            <h3 class="bk__success-title"><?= Loc::getMessage('BXMAX_BK_OK_TITLE') ?></h3>
            <dl class="bk__details" data-bk-details></dl>
            <p class="bk__lead"><?= Loc::getMessage('BXMAX_BK_OK_TEXT') ?></p>
            <button type="button" class="bk__submit bk__submit--ghost" data-bk-again><?= Loc::getMessage('BXMAX_BK_AGAIN') ?></button>
        </div>
    </div>
</section>
