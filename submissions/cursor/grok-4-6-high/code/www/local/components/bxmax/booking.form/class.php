<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bxmax\Booking\Application\Service\CatalogService;

final class BxmaxBookingFormComponent extends CBitrixComponent
{
    public function executeComponent(): void
    {
        if (!Loader::includeModule('bxmax.booking')) {
            ShowError('Module bxmax.booking is not installed');
            return;
        }

        $locator = ServiceLocator::getInstance();
        /** @var CatalogService $catalog */
        $catalog = $locator->get(CatalogService::class);

        $this->arResult['SERVICES'] = $catalog->getServices();
        $this->arResult['MASTERS'] = $catalog->getMasters();
        $this->arResult['SESSID'] = bitrix_sessid();
        $this->arResult['WEEK_START'] = $this->currentMonday();
        $this->arResult['IS_AUTHORIZED'] = (int)CurrentUser::get()->getId() > 0;

        $this->includeComponentTemplate();
    }

    private function currentMonday(): string
    {
        $date = new \DateTimeImmutable('today');
        $day = (int)$date->format('N');
        if ($day !== 1) {
            $date = $date->modify('-' . ($day - 1) . ' days');
        }

        return $date->format('Y-m-d');
    }
}
