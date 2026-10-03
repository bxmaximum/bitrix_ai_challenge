<?php

namespace Sprint\Migration;

/**
 * Привязка шаблона laktochka к сайту s1 и базовые параметры сайта.
 */
class Version20261003100200 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Шаблон сайта laktochka и параметры сайта';
    protected $moduleVersion = '5.15.1';

    public function up()
    {
        $site = \CSite::GetByID('s1')->Fetch();
        if (!$site) {
            throw new \Sprint\Migration\Exceptions\MigrationException('Сайт s1 не найден');
        }

        $obSite = new \CSite();
        $ok = $obSite->Update('s1', [
            'NAME' => 'Лак&Точка',
            'SITE_NAME' => 'Лак&Точка',
            'EMAIL' => (string)\Bitrix\Main\Config\Option::get('main', 'email_from', ''),
            'TEMPLATE' => [
                ['TEMPLATE' => 'laktochka', 'SORT' => 1, 'CONDITION' => ''],
            ],
        ]);
        if (!$ok) {
            throw new \Sprint\Migration\Exceptions\MigrationException($obSite->LAST_ERROR);
        }
    }

    public function down()
    {
        // шаблон остаётся привязанным: откат не нужен
    }
}
