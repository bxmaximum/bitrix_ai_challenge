<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Repository\CatalogRepository;

/**
 * Форма онлайн-записи. Компонент только отдаёт справочники и настройки: слоты и заявки
 * идут через ajax-действия модуля (bxmax:booking.api.*).
 */
final class BxmaxBookingFormComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['ANCHOR_ID'] = preg_replace('/[^\w-]/', '', (string)($arParams['ANCHOR_ID'] ?? 'booking')) ?: 'booking';
        $arParams['TITLE'] = trim((string)($arParams['TITLE'] ?? '')) ?: 'Выберите время — остальное мы подготовим';
        $arParams['WEEKS_AHEAD'] = max(0, min(8, (int)($arParams['WEEKS_AHEAD'] ?? 2)));
        $arParams['AJAX_URL'] = trim((string)($arParams['AJAX_URL'] ?? '')) ?: '/bitrix/services/main/ajax.php';

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('bxmax.booking'))
        {
            ShowError('Модуль bxmax.booking не установлен');

            return;
        }

        /** @var CatalogRepository $catalog */
        $catalog = ServiceLocator::getInstance()->get(CatalogRepository::class);

        $this->arResult['SERVICES'] = $catalog->getServices();
        $this->arResult['MASTERS'] = $catalog->getMasters();
        $this->arResult['TODAY'] = (new DateTimeImmutable('today'))->format('Y-m-d');

        $this->includeComponentTemplate();
    }
}
