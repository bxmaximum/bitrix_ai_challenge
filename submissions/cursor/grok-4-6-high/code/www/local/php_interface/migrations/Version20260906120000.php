<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class Version20260906120000 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Install bxmax.booking module';
    protected $moduleVersion = '5.13.0';

    public function up()
    {
        if (ModuleManager::isModuleInstalled('bxmax.booking')) {
            $this->outSuccess('bxmax.booking already installed');
            return true;
        }

        $installerFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxmax.booking/install/index.php';
        if (!is_file($installerFile)) {
            $this->outError('Module files are missing');
            return false;
        }

        require_once $installerFile;
        $installer = new \bxmax_booking();
        $installer->DoInstall();

        if (!ModuleManager::isModuleInstalled('bxmax.booking')) {
            $this->outError('Failed to install bxmax.booking');
            return false;
        }

        Loader::includeModule('bxmax.booking');
        $this->outSuccess('bxmax.booking installed');
    }

    public function down()
    {
        if (!ModuleManager::isModuleInstalled('bxmax.booking')) {
            return true;
        }

        require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxmax.booking/install/index.php';
        $installer = new \bxmax_booking();
        $installer->DoUninstall();
    }
}
