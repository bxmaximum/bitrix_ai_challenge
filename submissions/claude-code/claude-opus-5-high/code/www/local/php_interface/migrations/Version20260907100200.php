<?php

namespace Sprint\Migration;

/**
 * Структура контента лендинга: тип инфоблоков и инфоблоки «Услуги» и «Мастера».
 */
class Version20260907100200 extends Version
{
    protected $author = 'bxmaximum';
    protected $description = 'Инфоблоки services и masters со свойствами';

    public const IBLOCK_TYPE = 'bxmax_studio';

    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Iblock()->saveIblockType([
            'ID' => self::IBLOCK_TYPE,
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Студия',
                    'ELEMENT_NAME' => 'Элементы',
                ],
                'en' => [
                    'NAME' => 'Studio',
                    'ELEMENT_NAME' => 'Elements',
                ],
            ],
        ]);

        $servicesId = $helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE,
            'LID' => ['s1'],
            'CODE' => 'services',
            'API_CODE' => 'Services',
            'NAME' => 'Услуги',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'LIST_PAGE_URL' => '/#services',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'VERSION' => 2,
        ]);

        $helper->Iblock()->saveGroupPermissions($servicesId, [2 => 'R']);

        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Цена, ₽',
            'CODE' => 'PRICE',
            'PROPERTY_TYPE' => 'N',
            'IS_REQUIRED' => 'Y',
            'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Длительность, мин',
            'CODE' => 'DURATION',
            'PROPERTY_TYPE' => 'N',
            'IS_REQUIRED' => 'Y',
            'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Alt изображения',
            'CODE' => 'PHOTO_ALT',
            'PROPERTY_TYPE' => 'S',
            'SORT' => 300,
        ]);

        $mastersId = $helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => self::IBLOCK_TYPE,
            'LID' => ['s1'],
            'CODE' => 'masters',
            'API_CODE' => 'Masters',
            'NAME' => 'Мастера',
            'ACTIVE' => 'Y',
            'SORT' => 200,
            'LIST_PAGE_URL' => '/#masters',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'VERSION' => 2,
        ]);

        $helper->Iblock()->saveGroupPermissions($mastersId, [2 => 'R']);

        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Специализация',
            'CODE' => 'SPECIALITY',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'Y',
            'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Опыт',
            'CODE' => 'EXPERIENCE',
            'PROPERTY_TYPE' => 'S',
            'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Alt изображения',
            'CODE' => 'PHOTO_ALT',
            'PROPERTY_TYPE' => 'S',
            'SORT' => 300,
        ]);

        $this->outSuccess('Инфоблоки services (#%d) и masters (#%d) готовы', $servicesId, $mastersId);

        return true;
    }

    public function down()
    {
        $helper = $this->getHelperManager();

        $helper->Iblock()->deleteIblockIfExists('services', self::IBLOCK_TYPE);
        $helper->Iblock()->deleteIblockIfExists('masters', self::IBLOCK_TYPE);
        $helper->Iblock()->deleteIblockTypeIfExists(self::IBLOCK_TYPE);

        return true;
    }
}
