<?php

namespace Sprint\Migration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Service\ScheduleService;

class BookingSchedule20261109233422 extends Version
{
    protected $author = "admin";

    protected $description = "Расписание мастеров на 14 дней";

    protected $moduleVersion = "5.16.1";

    public function up()
    {
        if (!Loader::includeModule('bxmax.booking')) {
            $this->outError('Модуль bxmax.booking не установлен');

            return false;
        }

        // Логика генерации — в сервисе модуля: каждому мастеру слоты на 14 дней, 10:00–20:00, шаг 60 минут.
        // Вызов идемпотентен; дальше горизонт поддерживает агент ScheduleAgent.
        $created = ServiceLocator::getInstance()->get(ScheduleService::class)->generate();

        $this->outSuccess('Создано слотов: %d', $created);
    }

    public function down()
    {
        // Слоты живут в таблицах модуля и удаляются вместе с ним (откат BookingModule).
        // Отдельно не чистим: в расписании уже могут быть заявки клиентов.
    }
}
