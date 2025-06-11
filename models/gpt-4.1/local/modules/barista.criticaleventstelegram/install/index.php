<?php
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

Class barista_criticaleventstelegram extends CModule
{
    public $MODULE_ID = 'barista.criticaleventstelegram';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Barista';
    public $PARTNER_URI = 'https://barista.ru';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__.'/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('BARISTA_CET_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BARISTA_CET_MODULE_DESC');
    }

    public function DoInstall()
    {
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }
} 