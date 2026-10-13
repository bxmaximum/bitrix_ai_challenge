<?php declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var array $arResult */

use Bitrix\Main\Web\Json;

$services = (array)($arResult['SERVICES'] ?? []);
$masters = (array)($arResult['MASTERS'] ?? []);
$defaultServiceId = (int)($arResult['DEFAULT_SERVICE_ID'] ?? 0);
$defaultMasterId = (int)($arResult['DEFAULT_MASTER_ID'] ?? 0);
$defaultWeekStart = (string)($arResult['DEFAULT_WEEK_START'] ?? date('Y-m-d'));
$initialSlots = (array)($arResult['INITIAL_SLOTS'] ?? []);

$widgetConfig = [
    'services' => $services,
    'masters' => $masters,
    'defaultServiceId' => $defaultServiceId,
    'defaultMasterId' => $defaultMasterId,
    'defaultWeekStart' => $defaultWeekStart,
    'todayIso' => (string)($arResult['TODAY_ISO'] ?? date('Y-m-d')),
    'initialSlots' => $initialSlots,
    'endpoints' => [
        'slotsList' => '/bitrix/services/main/ajax.php?action=bxmax:booking.api.slots.list',
        'bookingsCreate' => '/bitrix/services/main/ajax.php?action=bxmax:booking.api.bookings.create',
    ],
];
?>
<div class="bxmax-booking" id="bxmax-booking-widget" data-booking-widget>
    <div class="bxmax-booking__form-view" data-booking-form-view>
        <form class="bxmax-booking__form" id="bxmax-booking-form" novalidate>
            <input type="hidden" name="serviceId" id="bxmax-booking-service-input" value="<?= $defaultServiceId ?>">
            <input type="hidden" name="masterId" id="bxmax-booking-master-input" value="<?= $defaultMasterId ?>">
            <input type="hidden" name="slotId" id="bxmax-booking-slot-input" value="">

            <!-- Шаг 1: Выбор услуги -->
            <div class="bxmax-booking__step">
                <div class="bxmax-booking__step-header">
                    <span class="bxmax-booking__step-num">01</span>
                    <h3 class="bxmax-booking__step-title" id="bxmax-service-listbox-label">Выберите услугу</h3>
                </div>
                <div
                    class="bxmax-booking__services"
                    role="listbox"
                    aria-labelledby="bxmax-service-listbox-label"
                    data-service-listbox
                >
                    <?php foreach ($services as $index => $service):
                        $sId = (int)$service['id'];
                        $isSelected = ($sId === $defaultServiceId);
                        ?>
                        <button
                            type="button"
                            class="bxmax-booking__service-pill<?= $isSelected ? ' is-selected' : '' ?>"
                            role="option"
                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                            tabindex="<?= $isSelected || ($defaultServiceId === 0 && $index === 0) ? '0' : '-1' ?>"
                            data-service-option
                            data-service-id="<?= $sId ?>"
                            data-service-name="<?= htmlspecialcharsbx((string)$service['name']) ?>"
                            data-service-price="<?= htmlspecialcharsbx((string)$service['priceFormatted']) ?>"
                            data-service-duration="<?= htmlspecialcharsbx((string)$service['duration']) ?>"
                        >
                            <span class="bxmax-booking__service-pill-name"><?= htmlspecialcharsbx((string)$service['name']) ?></span>
                            <span class="bxmax-booking__service-pill-meta"><?= htmlspecialcharsbx((string)$service['priceFormatted']) ?> · <?= htmlspecialcharsbx((string)$service['duration']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Шаг 2: Выбор мастера -->
            <div class="bxmax-booking__step">
                <div class="bxmax-booking__step-header">
                    <span class="bxmax-booking__step-num">02</span>
                    <h3 class="bxmax-booking__step-title" id="bxmax-master-listbox-label">Выберите мастера</h3>
                </div>
                <div
                    class="bxmax-booking__masters"
                    role="listbox"
                    aria-labelledby="bxmax-master-listbox-label"
                    data-master-listbox
                >
                    <?php foreach ($masters as $mIndex => $master):
                        $mId = (int)$master['id'];
                        $isMasterSelected = ($mId === $defaultMasterId);
                        $shapeClass = 'bxmax-booking__master-card--shape-' . (($mIndex % 4) + 1);
                        ?>
                        <button
                            type="button"
                            class="bxmax-booking__master-card <?= $shapeClass ?><?= $isMasterSelected ? ' is-selected' : '' ?>"
                            role="option"
                            aria-selected="<?= $isMasterSelected ? 'true' : 'false' ?>"
                            tabindex="<?= $isMasterSelected || ($defaultMasterId === 0 && $mIndex === 0) ? '0' : '-1' ?>"
                            data-master-option
                            data-master-id="<?= $mId ?>"
                            data-master-name="<?= htmlspecialcharsbx((string)$master['name']) ?>"
                            data-master-spec="<?= htmlspecialcharsbx((string)$master['specialization']) ?>"
                        >
                            <?php if (!empty($master['photoUrl'])): ?>
                                <span class="bxmax-booking__master-avatar">
                                    <img
                                        src="<?= htmlspecialcharsbx((string)$master['photoUrl']) ?>"
                                        alt="<?= htmlspecialcharsbx('Мастер ' . (string)$master['name']) ?>"
                                        loading="lazy"
                                    >
                                </span>
                            <?php endif; ?>
                            <span class="bxmax-booking__master-info">
                                <span class="bxmax-booking__master-name"><?= htmlspecialcharsbx((string)$master['name']) ?></span>
                                <span class="bxmax-booking__master-spec"><?= htmlspecialcharsbx((string)$master['specialization']) ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Шаг 3: Расписание слотов (неделя вперёд, переключение недель) -->
            <div class="bxmax-booking__step">
                <div class="bxmax-booking__step-header bxmax-booking__step-header--schedule">
                    <div class="bxmax-booking__step-title-group">
                        <span class="bxmax-booking__step-num">03</span>
                        <h3 class="bxmax-booking__step-title" id="bxmax-slots-listbox-label">Выберите дату и свободный слот</h3>
                    </div>
                    <div class="bxmax-booking__week-nav" role="group" aria-label="Переключение недель расписания">
                        <button
                            type="button"
                            class="bxmax-booking__week-btn"
                            data-week-prev
                            aria-label="Предыдущая неделя"
                        >
                            <svg width="16" height="28" viewBox="0 0 21 39" fill="none" aria-hidden="true">
                                <path d="M20.0979 1.4386L0.999801 19.9394L20.0979 38.4402" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span>Пред. неделя</span>
                        </button>
                        <span class="bxmax-booking__week-label" data-week-label aria-live="polite"></span>
                        <button
                            type="button"
                            class="bxmax-booking__week-btn"
                            data-week-next
                            aria-label="Следующая неделя"
                        >
                            <span>След. неделя</span>
                            <svg width="16" height="28" viewBox="0 0 21 39" fill="none" aria-hidden="true">
                                <path d="M0.9021 1.4386L20.0002 19.9394L0.9021 38.4402" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bxmax-booking__legend" aria-hidden="true">
                    <span class="bxmax-booking__legend-item"><span class="bxmax-booking__legend-dot bxmax-booking__legend-dot--free"></span> Свободно</span>
                    <span class="bxmax-booking__legend-item"><span class="bxmax-booking__legend-dot bxmax-booking__legend-dot--selected"></span> Выбрано</span>
                    <span class="bxmax-booking__legend-item"><span class="bxmax-booking__legend-dot bxmax-booking__legend-dot--taken"></span> Занято</span>
                </div>

                <div
                    class="bxmax-booking__schedule"
                    role="listbox"
                    aria-labelledby="bxmax-slots-listbox-label"
                    data-slots-listbox
                >
                    <!-- Заполняется скриптом на основе initialSlots и AJAX-ответов bxmax:booking.api.slots.list -->
                </div>
            </div>

            <!-- Шаг 4: Контактные данные и согласие -->
            <div class="bxmax-booking__step bxmax-booking__step--contacts">
                <div class="bxmax-booking__step-header">
                    <span class="bxmax-booking__step-num">04</span>
                    <h3 class="bxmax-booking__step-title">Ваши контактные данные</h3>
                </div>

                <div class="bxmax-booking__summary-bar" data-booking-summary aria-live="polite">
                    Выберите свободный слот в расписании выше, чтобы завершить запись.
                </div>

                <div class="bxmax-booking__fields-grid">
                    <div class="bxmax-booking__field">
                        <input
                            type="text"
                            id="bxmax-booking-name"
                            name="name"
                            class="bxmax-booking__input"
                            placeholder=" "
                            autocomplete="name"
                            required
                            data-input-name
                        >
                        <label for="bxmax-booking-name" class="bxmax-booking__label">Ваше имя *</label>
                    </div>

                    <div class="bxmax-booking__field">
                        <input
                            type="tel"
                            id="bxmax-booking-phone"
                            name="phone"
                            class="bxmax-booking__input"
                            placeholder=" "
                            autocomplete="tel"
                            required
                            data-input-phone
                        >
                        <label for="bxmax-booking-phone" class="bxmax-booking__label">Телефон (например, +7 999 123-45-67) *</label>
                    </div>
                </div>

                <div class="bxmax-booking__consent-row">
                    <input
                        type="checkbox"
                        id="bxmax-booking-consent"
                        name="consent"
                        value="1"
                        class="bxmax-booking__checkbox"
                        data-input-consent
                    >
                    <label for="bxmax-booking-consent" class="bxmax-booking__consent-label">
                        Я даю согласие на обработку персональных данных в соответствии с политикой конфиденциальности студии «Лак&amp;Точка»
                    </label>
                </div>

                <div
                    class="bxmax-booking__feedback"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                    data-booking-feedback
                    hidden
                ></div>

                <div class="bxmax-booking__actions">
                    <button type="submit" class="btn btn-black bxmax-booking__submit" data-booking-submit>
                        Записаться на визит
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Экран успеха без перезагрузки страницы -->
    <div
        class="bxmax-booking__success"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        data-booking-success-view
        hidden
    >
        <div class="bxmax-booking__success-arch">
            <div class="bxmax-booking__success-badge">Л&amp;Т</div>
            <h3 class="bxmax-booking__success-title">Вы записаны в «Лак&amp;Точка»</h3>
            <p class="bxmax-booking__success-subtitle">
                Мы забронировали за вами выбранное время и отправили уведомление администратору студии.
            </p>
            <dl class="bxmax-booking__success-details">
                <div class="bxmax-booking__success-row">
                    <dt>Номер записи</dt>
                    <dd data-success-booking-id>—</dd>
                </div>
                <div class="bxmax-booking__success-row">
                    <dt>Услуга</dt>
                    <dd data-success-service>—</dd>
                </div>
                <div class="bxmax-booking__success-row">
                    <dt>Мастер</dt>
                    <dd data-success-master>—</dd>
                </div>
                <div class="bxmax-booking__success-row">
                    <dt>Дата и время</dt>
                    <dd data-success-datetime>—</dd>
                </div>
                <div class="bxmax-booking__success-row">
                    <dt>Гость</dt>
                    <dd data-success-client>—</dd>
                </div>
            </dl>
            <div class="bxmax-booking__success-actions">
                <button type="button" class="btn btn-black" data-booking-reset>
                    Оформить ещё одну запись
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.BXMAX_BOOKING_CONFIG = <?= Json::encode($widgetConfig) ?>;
</script>
