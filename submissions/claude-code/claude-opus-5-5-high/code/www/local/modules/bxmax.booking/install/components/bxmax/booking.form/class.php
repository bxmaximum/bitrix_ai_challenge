<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Repository\CatalogRepository;
use Bxmax\Booking\Service\ScheduleService;
use Bxmax\Booking\Service\SlotService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * Форма онлайн-записи: выбор услуги, мастера и слота, контакты, согласие.
 * Данные справочников кешируются с тегами инфоблоков; сама разметка не кешируется (в ней sessid).
 */
final class BxmaxBookingFormComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);
        $arParams['POLICY_URL'] = (string)($arParams['POLICY_URL'] ?? '/policy/');
        $arParams['PHONE'] = (string)($arParams['PHONE'] ?? '');

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('bxmax.booking'))
        {
            ShowError(Loc::getMessage('BXMAX_BOOKING_FORM_MODULE_MISSING'));

            return;
        }

        $serviceLocator = ServiceLocator::getInstance();
        $catalog = $this->loadCatalog($serviceLocator->get(CatalogRepository::class));

        $slotService = $serviceLocator->get(SlotService::class);
        $scheduleService = $serviceLocator->get(ScheduleService::class);
        $now = new DateTime();

        $this->arResult = $catalog + [
            'WEEK_MIN' => $slotService->getCurrentWeekStart($now),
            'WEEK_MAX' => $slotService->getCurrentWeekStart($scheduleService->getLastDay($now)),
            'API_SLOTS' => '/bitrix/services/main/ajax.php?action=bxmax:booking.api.slots.list',
            'API_CREATE' => '/bitrix/services/main/ajax.php?action=bxmax:booking.api.bookings.create',
            'SESSID' => bitrix_sessid(),
            'FORM_ID' => 'bf-' . $this->randString(6),
        ];

        $this->includeComponentTemplate();
    }

    /**
     * @return array{SERVICES: array, MASTERS: array}
     */
    private function loadCatalog(CatalogRepository $catalog): array
    {
        $cache = Cache::createInstance();
        $cacheDir = '/bxmax/booking.form';
        $cacheTime = $this->arParams['CACHE_TIME'];

        if ($cacheTime > 0 && $cache->initCache($cacheTime, 'catalog_v1', $cacheDir))
        {
            return $cache->getVars();
        }

        $useCache = $cacheTime > 0 && $cache->startDataCache();
        $taggedCache = Application::getInstance()->getTaggedCache();
        if ($useCache)
        {
            $taggedCache->startTagCache($cacheDir);
        }

        $services = array_map(static fn ($service) => $service->toArray(), $catalog->getServices());

        $masters = [];
        foreach ($catalog->getMasters() as $master)
        {
            $item = $master->toArray();
            $item['photo'] = null;
            if ($master->photoId > 0)
            {
                $photo = CFile::ResizeImageGet($master->photoId, ['width' => 160, 'height' => 160], BX_RESIZE_IMAGE_EXACT);
                $item['photo'] = $photo['src'] ?? null;
            }
            $masters[] = $item;
        }

        $data = ['SERVICES' => $services, 'MASTERS' => $masters];

        if ($useCache)
        {
            foreach ($catalog->getIblockIds() as $iblockId)
            {
                $taggedCache->registerTag('iblock_id_' . $iblockId);
            }
            $taggedCache->endTagCache();
            $cache->endDataCache($data);
        }

        return $data;
    }
}
