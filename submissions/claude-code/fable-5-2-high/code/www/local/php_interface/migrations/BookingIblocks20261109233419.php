<?php

namespace Sprint\Migration;

class BookingIblocks20261109233419 extends Version
{
    protected $author = "admin";

    protected $description = "Инфоблоки услуг и мастеров";

    protected $moduleVersion = "5.16.1";

    private const TYPE_ID = 'studio';

    public function up()
    {
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();

        $helper->Iblock()->saveIblockType([
            'ID' => self::TYPE_ID,
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => ['NAME' => 'Студия', 'SECTION_NAME' => 'Разделы', 'ELEMENT_NAME' => 'Элементы'],
                'en' => ['NAME' => 'Studio', 'SECTION_NAME' => 'Sections', 'ELEMENT_NAME' => 'Elements'],
            ],
        ]);

        // API_CODE нужен модулю bxmax.booking: он читает инфоблоки через ORM-классы элементов.
        $servicesId = $helper->Iblock()->saveIblock([
            'NAME' => 'Услуги',
            'CODE' => 'services',
            'API_CODE' => 'services',
            'IBLOCK_TYPE_ID' => self::TYPE_ID,
            'LID' => [$siteId],
            'SORT' => 100,
            'ELEMENTS_NAME' => 'Услуги',
            'ELEMENT_NAME' => 'Услуга',
            'GROUP_ID' => ['2' => 'R'],
        ]);

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
            'HINT' => 'Не больше 60: слот расписания — один час.',
        ]);

        $mastersId = $helper->Iblock()->saveIblock([
            'NAME' => 'Мастера',
            'CODE' => 'masters',
            'API_CODE' => 'masters',
            'IBLOCK_TYPE_ID' => self::TYPE_ID,
            'LID' => [$siteId],
            'SORT' => 200,
            'ELEMENTS_NAME' => 'Мастера',
            'ELEMENT_NAME' => 'Мастер',
            'GROUP_ID' => ['2' => 'R'],
        ]);

        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Специализация',
            'CODE' => 'SPECIALIZATION',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'Y',
            'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Стаж',
            'CODE' => 'EXPERIENCE',
            'PROPERTY_TYPE' => 'S',
            'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Услуги мастера',
            'CODE' => 'SERVICES',
            'PROPERTY_TYPE' => 'E',
            'MULTIPLE' => 'Y',
            'LINK_IBLOCK_ID' => $servicesId,
            'SORT' => 300,
            'HINT' => 'На сайте мастера можно выбрать только для отмеченных услуг. Пусто — для любых.',
        ]);
    }

    public function down()
    {
        $helper = $this->getHelperManager();

        // Сначала мастера: их свойство ссылается на инфоблок услуг.
        $helper->Iblock()->deleteIblockIfExists('masters', self::TYPE_ID);
        $helper->Iblock()->deleteIblockIfExists('services', self::TYPE_ID);
        $helper->Iblock()->deleteIblockTypeIfExists(self::TYPE_ID);
    }
}
