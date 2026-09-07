<?php

namespace Sprint\Migration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Service\ScheduleGenerator;

/**
 * Расписание: каждому мастеру слоты на ближайшие 14 дней,
 * ежедневно с 10:00 до 20:00 с шагом 60 минут.
 */
class Version20260907100400 extends Version
{
    protected $author = 'bxmaximum';
    protected $description = 'Генерация слотов расписания на 14 дней';

    public function up()
    {
        $this->checkRequiredVersions([
            Version20260907100100::class,
            Version20260907100300::class,
        ]);

        if (!Loader::includeModule('bxmax.booking')) {
            $this->outError('Модуль bxmax.booking не установлен');

            return false;
        }

        /** @var ScheduleGenerator $generator */
        $generator = ServiceLocator::getInstance()->get(ScheduleGenerator::class);
        $stat = $generator->generate();

        if ($stat['masters'] === 0) {
            $this->outError('Не найдено ни одного мастера — расписание не построено');

            return false;
        }

        $this->outSuccess(
            'Мастеров: %d, слотов запланировано: %d, создано новых: %d',
            $stat['masters'],
            $stat['planned'],
            $stat['created']
        );

        return true;
    }

    public function down()
    {
        if (!Loader::includeModule('bxmax.booking')) {
            return true;
        }

        \Bitrix\Main\Application::getConnection()->queryExecute(
            'DELETE FROM ' . \Bxmax\Booking\Model\SlotTable::getTableName()
        );

        $this->outSuccess('Расписание очищено');

        return true;
    }
}
