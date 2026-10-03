<?php

namespace Sprint\Migration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bxmax\Booking\Service\ScheduleGenerator;

/**
 * Установка модуля bxmax.booking и генерация расписания на 14 дней для каждого мастера.
 */
class Version20261003100100 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Установка модуля bxmax.booking и первичное расписание';
    protected $moduleVersion = '5.15.1';

    public function up()
    {
        $this->checkRequiredVersions(['Version20261003100000']);

        if (!ModuleManager::isModuleInstalled('bxmax.booking')) {
            $module = \CModule::CreateModuleObject('bxmax.booking');
            if (!$module) {
                throw new \Sprint\Migration\Exceptions\MigrationException('Модуль bxmax.booking не найден в /local/modules');
            }
            $module->DoInstall();
            if (!ModuleManager::isModuleInstalled('bxmax.booking')) {
                throw new \Sprint\Migration\Exceptions\MigrationException('Не удалось установить модуль bxmax.booking');
            }
        }

        Loader::requireModule('bxmax.booking');

        // идемпотентно: слоты, которые уже есть, пропускаются
        $created = ServiceLocator::getInstance()->get(ScheduleGenerator::class)->generate();
        $this->outSuccess('Создано слотов: %d', $created);
    }

    public function down()
    {
        if (ModuleManager::isModuleInstalled('bxmax.booking')) {
            \CModule::CreateModuleObject('bxmax.booking')->DoUninstall();
        }
    }
}
