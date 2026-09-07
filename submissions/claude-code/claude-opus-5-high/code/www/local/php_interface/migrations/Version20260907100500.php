<?php

namespace Sprint\Migration;

use Bitrix\Main\SiteTable;

/**
 * Настройки сайта: имя студии и шаблон лендинга.
 */
class Version20260907100500 extends Version
{
    protected $author = 'bxmaximum';
    protected $description = 'Название сайта «Лак&Точка» и шаблон laktochka для s1';

    private const SITE_ID = 's1';
    private const TEMPLATE = 'laktochka';

    public function up()
    {
        $site = SiteTable::getRow(['select' => ['LID'], 'filter' => ['=LID' => self::SITE_ID]]);
        if (!$site) {
            $this->outError('Сайт %s не найден', self::SITE_ID);

            return false;
        }

        $updated = (new \CSite())->Update(self::SITE_ID, [
            'NAME' => 'Лак&Точка',
            'SITE_NAME' => 'Лак&Точка',
            'EMAIL' => 'hello@lakitochka.ru',
            'TEMPLATE' => [
                ['TEMPLATE' => self::TEMPLATE, 'SORT' => 1, 'CONDITION' => ''],
            ],
        ]);

        if (!$updated) {
            $this->outError('Не удалось обновить сайт %s', self::SITE_ID);

            return false;
        }

        $this->outSuccess('Сайт %s: шаблон %s, название «Лак&Точка»', self::SITE_ID, self::TEMPLATE);

        return true;
    }

    public function down()
    {
        (new \CSite())->Update(self::SITE_ID, [
            'NAME' => 'Сайт по умолчанию',
            'SITE_NAME' => 'Сайт по умолчанию',
            'TEMPLATE' => [
                ['TEMPLATE' => '.default', 'SORT' => 1, 'CONDITION' => ''],
            ],
        ]);

        return true;
    }
}
