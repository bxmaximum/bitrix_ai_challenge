<?php

namespace Sprint\Migration;

use Bitrix\Main\ModuleManager;

/**
 * Установка модуля bxmax.booking: таблицы, почтовое событие, агент, админ-страницы,
 * компонент формы и расписание на 14 дней для мастеров из инфоблока masters.
 */
class Version20261013120200 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Лак&Точка: установка модуля онлайн-записи bxmax.booking';
    protected $moduleVersion = '5.15.1';

    public function up()
    {
        $this->checkRequiredVersions(['Version20261013120100']);

        if (ModuleManager::isModuleInstalled('bxmax.booking'))
        {
            $this->outInfo('Модуль bxmax.booking уже установлен');

            return true;
        }

        $module = \CModule::CreateModuleObject('bxmax.booking');
        if (!$module)
        {
            $this->outError('Не найдены файлы модуля local/modules/bxmax.booking');

            return false;
        }

        $module->DoInstall();

        if (!ModuleManager::isModuleInstalled('bxmax.booking'))
        {
            global $APPLICATION;
            $exception = $APPLICATION->GetException();
            $this->outError('%s', $exception ? $exception->GetString() : 'Модуль не установлен');

            return false;
        }

        $this->outSuccess('Модуль bxmax.booking установлен');
    }

    public function down()
    {
        if (!ModuleManager::isModuleInstalled('bxmax.booking'))
        {
            return true;
        }

        \CModule::CreateModuleObject('bxmax.booking')?->DoUninstall();
    }
}
