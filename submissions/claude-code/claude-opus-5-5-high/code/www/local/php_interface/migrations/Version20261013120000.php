<?php

namespace Sprint\Migration;

use Bitrix\Main\SiteTable;

/**
 * Сайт s1: название студии и шаблон «lak» для всего сайта.
 */
class Version20261013120000 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Лак&Точка: шаблон сайта lak и название сайта';
    protected $moduleVersion = '5.15.1';

    public function up()
    {
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();

        $helper->Site()->setSiteTemplates($siteId, [
            ['TEMPLATE' => 'lak', 'SORT' => 1, 'CONDITION' => ''],
        ]);

        // Обновлять сайты sprint-хелпер не умеет; D7-API для записи сайта нет — используется CSite.
        $site = new \CSite();
        if (!$site->Update($siteId, [
            'NAME' => 'Лак&Точка',
            'SITE_NAME' => 'Студия маникюра «Лак&Точка»',
        ]))
        {
            $this->outError('%s', $site->LAST_ERROR);

            return false;
        }

        SiteTable::cleanCache();
        $this->outSuccess('Сайт %s: шаблон lak', $siteId);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();
        $helper->Site()->setSiteTemplates($siteId, []);
    }
}
