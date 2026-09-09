<?php

declare(strict_types=1);

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Infrastructure\Repository\ContentRepository;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

final class BxmaxBookingComponent extends CBitrixComponent
{
    public function executeComponent(): void
    {
        if (!Loader::includeModule('bxmax.booking'))
        {
            ShowError('Модуль онлайн-записи не установлен.');
            return;
        }

        /** @var ContentRepository $content */
        $content = ServiceLocator::getInstance()->get(ContentRepository::class);
        $this->arResult = [
            'SERVICES' => $content->getServices(),
            'MASTERS' => $content->getMasters(),
            'SESSID' => bitrix_sessid(),
        ];
        $this->includeComponentTemplate();
    }
}
