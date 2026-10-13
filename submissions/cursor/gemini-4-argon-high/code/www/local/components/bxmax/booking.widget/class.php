<?php declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Service\SlotService;

final class BxmaxBookingWidgetComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 0);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('bxmax.booking'))
        {
            return;
        }

        $locator = ServiceLocator::getInstance();
        /** @var CatalogRepository $catalogRepository */
        $catalogRepository = $locator->get(CatalogRepository::class);
        /** @var SlotService $slotService */
        $slotService = $locator->get(SlotService::class);

        $services = $catalogRepository->listActiveServices();
        $masters = $catalogRepository->listActiveMasters();

        $today = new \DateTimeImmutable('today');
        $mondayThisWeek = new \DateTimeImmutable('monday this week');
        $defaultWeekStart = $mondayThisWeek->format('Y-m-d');
        $nextWeekStart = $mondayThisWeek->modify('+7 days')->format('Y-m-d');

        $defaultMasterId = !empty($masters) ? (int)$masters[0]['id'] : 0;
        $defaultServiceId = !empty($services) ? (int)$services[0]['id'] : 0;

        $initialSlots = [];
        if ($defaultMasterId > 0)
        {
            $slotsResult = $slotService->getMasterWeekSlots($defaultMasterId, $defaultWeekStart);
            if ($slotsResult->isSuccess())
            {
                $initialSlots = (array)($slotsResult->getData()['slots'] ?? []);
            }
        }

        $this->arResult['SERVICES'] = $services;
        $this->arResult['MASTERS'] = $masters;
        $this->arResult['DEFAULT_SERVICE_ID'] = $defaultServiceId;
        $this->arResult['DEFAULT_MASTER_ID'] = $defaultMasterId;
        $this->arResult['DEFAULT_WEEK_START'] = $defaultWeekStart;
        $this->arResult['NEXT_WEEK_START'] = $nextWeekStart;
        $this->arResult['TODAY_ISO'] = $today->format('Y-m-d');
        $this->arResult['INITIAL_SLOTS'] = $initialSlots;

        $this->includeComponentTemplate();
    }
}
