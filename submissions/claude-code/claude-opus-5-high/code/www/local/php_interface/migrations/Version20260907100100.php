<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;

/**
 * Установка модуля онлайн-записи bxmax.booking.
 */
class Version20260907100100 extends Version
{
    protected $author = 'bxmaximum';
    protected $description = 'Установка модуля bxmax.booking (таблицы, почтовое событие, страницы админки, компонент)';

    private const MODULE_ID = 'bxmax.booking';

    public function up()
    {
        $module = $this->makeModule();

        if (ModuleManager::isModuleInstalled(self::MODULE_ID)) {
            $this->outNotice('Модуль %s уже установлен', self::MODULE_ID);

            return true;
        }

        $module->InstallDB();
        $module->InstallEvents();
        $module->InstallFiles();
        ModuleManager::registerModule(self::MODULE_ID);

        $this->outSuccess('Модуль %s установлен', self::MODULE_ID);

        return true;
    }

    public function down()
    {
        $module = $this->makeModule();

        ModuleManager::unRegisterModule(self::MODULE_ID);
        $module->UnInstallFiles();
        $module->UnInstallEvents();
        $module->UnInstallDB();

        $this->outSuccess('Модуль %s удалён', self::MODULE_ID);

        return true;
    }

    private function makeModule(): \bxmax_booking
    {
        $path = Application::getDocumentRoot() . '/local/modules/' . self::MODULE_ID . '/install/index.php';

        if (!is_file($path)) {
            throw new Exceptions\MigrationException('Не найден установщик модуля ' . self::MODULE_ID);
        }

        require_once $path;

        return new \bxmax_booking();
    }
}
