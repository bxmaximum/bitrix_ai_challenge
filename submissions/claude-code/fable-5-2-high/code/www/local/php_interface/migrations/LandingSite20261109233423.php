<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;

class LandingSite20261109233423 extends Version
{
    protected $author = "admin";

    protected $description = "Шаблон сайта и настройки лендинга";

    protected $moduleVersion = "5.16.1";

    private const TEMPLATE = 'lak_tochka';
    private const SITE_NAME = 'Лак&Точка';

    public function up()
    {
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();

        $helper->Site()->setSiteTemplates($siteId, [
            ['TEMPLATE' => self::TEMPLATE, 'SORT' => 1, 'CONDITION' => ''],
        ]);

        $site = new \CSite();
        if (!$site->Update($siteId, ['NAME' => self::SITE_NAME, 'SITE_NAME' => self::SITE_NAME])) {
            $this->outError('%s', $site->LAST_ERROR);

            return false;
        }

        // #SITE_NAME# в теме письма о новой заявке.
        Option::set('main', 'site_name', self::SITE_NAME);
    }

    public function down()
    {
        // Шаблон не снимаем: сайт без шаблона через API сохранить нельзя («Неправильный шаблон
        // для сайта»), а главная страница написана под этот шаблон и работает и без модуля записи.
        // Возвращаем только название сайта.
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();

        $site = new \CSite();
        $site->Update($siteId, ['NAME' => 'Сайт по умолчанию', 'SITE_NAME' => '']);
        Option::delete('main', ['name' => 'site_name']);
    }
}
