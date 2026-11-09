<?php

namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;

class BookingModule20261109233418 extends Version
{
    protected $author = "admin";

    protected $description = "Установка модуля bxmax.booking";

    protected $moduleVersion = "5.16.1";

    private const MODULE_ID = 'bxmax.booking';

    public function up()
    {
        if (ModuleManager::isModuleInstalled(self::MODULE_ID)) {
            $this->outInfo('Модуль %s уже установлен', self::MODULE_ID);

            return;
        }

        $module = \CModule::CreateModuleObject(self::MODULE_ID);
        if (!$module) {
            $this->outError('Модуль %s не найден в /local/modules', self::MODULE_ID);

            return false;
        }

        // Таблицы, агент, почтовое событие, файлы админки и компонент — всё внутри установщика.
        if (!$module->install()) {
            $exception = $GLOBALS['APPLICATION']->GetException();
            $this->outError('%s', $exception ? $exception->GetString() : 'Не удалось установить модуль');

            return false;
        }

        $this->outSuccess('Модуль %s установлен', self::MODULE_ID);
    }

    public function down()
    {
        if (!ModuleManager::isModuleInstalled(self::MODULE_ID)) {
            return;
        }

        $module = \CModule::CreateModuleObject(self::MODULE_ID);
        // Откат миграции возвращает площадку в исходное состояние: таблицы модуля удаляются.
        if ($module && !$module->uninstall(false)) {
            $exception = $GLOBALS['APPLICATION']->GetException();
            $this->outError('%s', $exception ? $exception->GetString() : 'Не удалось удалить модуль');

            return false;
        }
    }
}
