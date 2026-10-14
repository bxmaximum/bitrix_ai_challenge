<?php

namespace Sprint\Migration;

/**
 * Инфоблоки «Услуги» (services) и «Мастера» (masters) со свойствами и наполнением.
 * Элементы ищутся по XML_ID, повторный запуск обновляет их, а не дублирует.
 */
class Version20261013120100 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Лак&Точка: инфоблоки services и masters, услуги и мастера';
    protected $moduleVersion = '5.15.1';

    private const TYPE_ID = 'lak_content';

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
                'ru' => ['NAME' => 'Контент студии', 'SECTION_NAME' => 'Разделы', 'ELEMENT_NAME' => 'Элементы'],
                'en' => ['NAME' => 'Studio content', 'SECTION_NAME' => 'Sections', 'ELEMENT_NAME' => 'Elements'],
            ],
        ]);

        $servicesId = $helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => self::TYPE_ID,
            'LID' => [$siteId],
            'CODE' => 'services',
            'API_CODE' => 'services',
            'NAME' => 'Услуги',
            'SORT' => 100,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
            'LIST_PAGE_URL' => '#SITE_DIR#/#services',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/#services',
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Цена, ₽', 'CODE' => 'PRICE', 'PROPERTY_TYPE' => 'N', 'IS_REQUIRED' => 'Y', 'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Длительность, мин', 'CODE' => 'DURATION', 'PROPERTY_TYPE' => 'N', 'IS_REQUIRED' => 'Y', 'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Категория', 'CODE' => 'CATEGORY', 'PROPERTY_TYPE' => 'S', 'SORT' => 300,
        ]);

        $mastersId = $helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => self::TYPE_ID,
            'LID' => [$siteId],
            'CODE' => 'masters',
            'API_CODE' => 'masters',
            'NAME' => 'Мастера',
            'SORT' => 200,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
            'LIST_PAGE_URL' => '#SITE_DIR#/#masters',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/#masters',
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Специализация', 'CODE' => 'SPECIALIZATION', 'PROPERTY_TYPE' => 'S', 'IS_REQUIRED' => 'Y', 'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Стаж, лет', 'CODE' => 'EXPERIENCE', 'PROPERTY_TYPE' => 'N', 'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Выполняет услуги',
            'CODE' => 'SERVICES',
            'PROPERTY_TYPE' => 'E',
            'MULTIPLE' => 'Y',
            'LINK_IBLOCK_ID' => self::TYPE_ID . ':services',
            'SORT' => 300,
        ]);

        $serviceIds = [];
        foreach ($this->getServices() as $sort => $service)
        {
            $serviceIds[$service['xml']] = $helper->Iblock()->saveElementByXmlId($servicesId, [
                'XML_ID' => $service['xml'],
                'CODE' => $service['xml'],
                'NAME' => $service['name'],
                'SORT' => ($sort + 1) * 10,
                'PREVIEW_TEXT' => $service['text'],
                'PREVIEW_TEXT_TYPE' => 'text',
            ], [
                'PRICE' => $service['price'],
                'DURATION' => $service['duration'],
                'CATEGORY' => $service['category'],
            ]);
        }

        foreach ($this->getMasters() as $sort => $master)
        {
            $fields = [
                'XML_ID' => $master['xml'],
                'CODE' => $master['xml'],
                'NAME' => $master['name'],
                'SORT' => ($sort + 1) * 10,
                'PREVIEW_TEXT' => $master['text'],
                'PREVIEW_TEXT_TYPE' => 'text',
            ];

            $photo = __DIR__ . '/data/masters/' . $master['photo'];
            if (is_file($photo))
            {
                $fields['PREVIEW_PICTURE'] = \CFile::MakeFileArray($photo);
                $fields['PREVIEW_PICTURE']['description'] = $master['alt'];
            }

            $helper->Iblock()->saveElementByXmlId($mastersId, $fields, [
                'SPECIALIZATION' => $master['spec'],
                'EXPERIENCE' => $master['experience'],
                'SERVICES' => array_values(array_map(static fn ($xml) => $serviceIds[$xml], $master['services'])),
            ]);
        }

        \CIBlock::clearIblockTagCache($servicesId);
        \CIBlock::clearIblockTagCache($mastersId);

        $this->outSuccess('Услуг: %d, мастеров: %d', count($this->getServices()), count($this->getMasters()));
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $helper->Iblock()->deleteIblockIfExists('masters', self::TYPE_ID);
        $helper->Iblock()->deleteIblockIfExists('services', self::TYPE_ID);
        $helper->Iblock()->deleteIblockTypeIfExists(self::TYPE_ID);
    }

    private function getServices(): array
    {
        return [
            [
                'xml' => 'manicure-gel',
                'name' => 'Маникюр с покрытием гель-лаком',
                'category' => 'Маникюр',
                'price' => 2600,
                'duration' => 90,
                'text' => 'Комбинированный маникюр, выравнивание пластины и однотонное покрытие из палитры на 120 оттенков. Держится три недели — даже если дома маленькие дети и клавиатура.',
            ],
            [
                'xml' => 'manicure-classic',
                'name' => 'Маникюр без покрытия',
                'category' => 'Маникюр',
                'price' => 1400,
                'duration' => 45,
                'text' => 'Аккуратная кутикула, форма и масло. Для тех, кто любит натуральные ногти или берёт паузу между покрытиями.',
            ],
            [
                'xml' => 'gel-strengthening',
                'name' => 'Укрепление и выравнивание гелем',
                'category' => 'Маникюр',
                'price' => 3200,
                'duration' => 120,
                'text' => 'Тонкий слой геля поверх своей пластины: ломкие ногти перестают слоиться, а цвет ложится зеркально ровно и не отслаивается у кутикулы.',
            ],
            [
                'xml' => 'extension',
                'name' => 'Наращивание ногтей',
                'category' => 'Наращивание',
                'price' => 4500,
                'duration' => 150,
                'text' => 'Миндаль, овал или «балерина» нужной длины. Моделируем на верхних формах, поэтому ногти получаются тонкими и лёгкими, а не «пластиковыми».',
            ],
            [
                'xml' => 'design',
                'name' => 'Френч, градиент или ручной дизайн',
                'category' => 'Дизайн',
                'price' => 700,
                'duration' => 30,
                'text' => 'Дополнение к любому покрытию: тонкий френч, мягкий «омбре», минималистичные линии или втирка. Цена — за все десять ногтей.',
            ],
            [
                'xml' => 'pedicure',
                'name' => 'Педикюр с покрытием',
                'category' => 'Педикюр',
                'price' => 3400,
                'duration' => 90,
                'text' => 'Аппаратный педикюр без замачивания, обработка стоп и покрытие гель-лаком. Одноразовые насадки и пилки — для каждой гостьи новые.',
            ],
            [
                'xml' => 'spa-hands',
                'name' => 'SPA-уход для рук',
                'category' => 'Уход',
                'price' => 1200,
                'duration' => 30,
                'text' => 'Скраб с тростниковым сахаром, тёплый парафин и массаж с маслом ши. Хорош сам по себе и как финал любого маникюра.',
            ],
        ];
    }

    private function getMasters(): array
    {
        return [
            [
                'xml' => 'alina-sokolova',
                'name' => 'Алина Соколова',
                'spec' => 'Топ-мастер маникюра, сложные однотонные покрытия',
                'experience' => 8,
                'photo' => 'master-1.jpg',
                'alt' => 'Алина Соколова, топ-мастер маникюра, в бежевом тренче у светлой стены',
                'services' => ['manicure-gel', 'manicure-classic', 'gel-strengthening', 'design', 'spa-hands'],
                'text' => 'Восемь лет в профессии и маниакальная любовь к ровному блику. Алина та, к кому идут, когда «нужно просто идеально».',
            ],
            [
                'xml' => 'veronika-lebedeva',
                'name' => 'Вероника Лебедева',
                'spec' => 'Наращивание и дизайн',
                'experience' => 6,
                'photo' => 'master-2.jpg',
                'alt' => 'Вероника Лебедева, мастер наращивания, портрет на светлом фоне',
                'services' => ['manicure-gel', 'gel-strengthening', 'extension', 'design'],
                'text' => 'Строит форму, которую не отличить от натуральной, и рисует тонкой кистью то, что вы показали на картинке, — только аккуратнее.',
            ],
            [
                'xml' => 'daria-mironova',
                'name' => 'Дарья Миронова',
                'spec' => 'Аппаратный педикюр и уход',
                'experience' => 5,
                'photo' => 'master-3.jpg',
                'alt' => 'Дарья Миронова, мастер педикюра, улыбается в тёплом свете',
                'services' => ['pedicure', 'manicure-classic', 'spa-hands'],
                'text' => 'Бережный педикюр без замачивания и разговоры, после которых хочется записаться снова. Знает всё о сухих стопах и трещинках.',
            ],
            [
                'xml' => 'ksenia-orlova',
                'name' => 'Ксения Орлова',
                'spec' => 'Маникюр, педикюр и SPA-уход',
                'experience' => 4,
                'photo' => 'master-4.jpg',
                'alt' => 'Ксения Орлова, мастер маникюра и педикюра, в белом свитере',
                'services' => ['manicure-gel', 'manicure-classic', 'design', 'pedicure', 'spa-hands'],
                'text' => 'Универсал студии: за один визит сделает руки и ноги в одном оттенке и успеет подобрать уход по сезону.',
            ],
        ];
    }
}
