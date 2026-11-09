<?php

namespace Sprint\Migration;

class BookingContent20261109233421 extends Version
{
    protected $author = "admin";

    protected $description = "Наполнение: услуги и мастера";

    protected $moduleVersion = "5.16.1";

    private const TYPE_ID = 'studio';

    /** XML_ID => [название, цена, минуты, описание] */
    private const SERVICES = [
        'manicure-classic' => ['Маникюр без покрытия', 1600, 45, 'Комбинированная обработка, форма и полировка до блеска. Руки выглядят ухоженно и без лака.'],
        'manicure-gel' => ['Маникюр с гель-лаком', 2600, 60, 'Обработка, выравнивание и однотонное покрытие. Носится три недели без сколов — или переделаем бесплатно.'],
        'gel-strengthening' => ['Укрепление гелем', 3100, 60, 'Для тонких и ломких ногтей: плотная основа держит форму и помогает отрастить длину.'],
        'french-ombre' => ['Френч и омбре', 3000, 60, 'Тонкая линия улыбки или мягкая растяжка цвета. Рисуем кистью, без наклеек и трафаретов.'],
        'nail-design' => ['Маникюр с дизайном', 3400, 60, 'Покрытие и дизайн на всех ногтях: «кошачий глаз», втирка, фольга или ручная роспись.'],
        'removal-care' => ['Снятие и уход', 1200, 30, 'Бережно снимаем старое покрытие, придаём форму и питаем кутикулу маслом.'],
        'spa-hands' => ['SPA-уход для рук', 1900, 40, 'Пилинг, маска и массаж кистей с кремом. Десять минут тишины входят в стоимость.'],
    ];

    /** XML_ID => [имя, специализация, стаж, о мастере, услуги] */
    private const MASTERS = [
        'alina-sokolova' => [
            'Алина Соколова',
            'Комбинированный маникюр и укрепление гелем',
            '8 лет',
            'Выравнивает ногтевую пластину так, что блик ложится одной ровной линией. К Алине приходят за формой, которую не хочется менять.',
            ['manicure-classic', 'manicure-gel', 'gel-strengthening', 'removal-care'],
        ],
        'darya-klimova' => [
            'Дарья Климова',
            'Френч, омбре и минималистичный дизайн',
            '6 лет',
            'Рисует линию улыбки тоньше волоса и с первого раза подбирает оттенок нюда под тон кожи.',
            ['manicure-classic', 'manicure-gel', 'french-ombre', 'removal-care'],
        ],
        'veronika-orlova' => [
            'Вероника Орлова',
            'Сложный дизайн и ручная роспись',
            '7 лет',
            'Втирки, «кошачий глаз», роспись кистью — повторит референс из вашей подборки и уложится в час.',
            ['manicure-gel', 'french-ombre', 'nail-design', 'removal-care'],
        ],
        'maria-efimova' => [
            'Мария Ефимова',
            'Уход за руками и маникюр без покрытия',
            '5 лет',
            'Самые бережные руки студии. Работает с чувствительной кожей и тонкой кутикулой.',
            ['manicure-classic', 'manicure-gel', 'removal-care', 'spa-hands'],
        ],
    ];

    public function up()
    {
        $helper = $this->getHelperManager();
        $servicesId = $helper->Iblock()->getIblockIdIfExists('services', self::TYPE_ID);
        $mastersId = $helper->Iblock()->getIblockIdIfExists('masters', self::TYPE_ID);

        $serviceIds = [];
        $sort = 0;
        foreach (self::SERVICES as $xmlId => [$name, $price, $duration, $text]) {
            $sort += 100;
            $serviceIds[$xmlId] = $helper->Iblock()->saveElementByXmlId(
                $servicesId,
                [
                    'XML_ID' => $xmlId,
                    'CODE' => $xmlId,
                    'NAME' => $name,
                    'ACTIVE' => 'Y',
                    'SORT' => $sort,
                    'PREVIEW_TEXT' => $text,
                    'PREVIEW_TEXT_TYPE' => 'text',
                    'PREVIEW_PICTURE' => $this->picture('services/service-' . ($sort / 100) . '.jpg', $name),
                ],
                ['PRICE' => $price, 'DURATION' => $duration]
            );
        }

        $sort = 0;
        foreach (self::MASTERS as $xmlId => [$name, $specialization, $experience, $text, $services]) {
            $sort += 100;
            $helper->Iblock()->saveElementByXmlId(
                $mastersId,
                [
                    'XML_ID' => $xmlId,
                    'CODE' => $xmlId,
                    'NAME' => $name,
                    'ACTIVE' => 'Y',
                    'SORT' => $sort,
                    'PREVIEW_TEXT' => $text,
                    'PREVIEW_TEXT_TYPE' => 'text',
                    'PREVIEW_PICTURE' => $this->picture('masters/master-' . ($sort / 100) . '.jpg', $name),
                ],
                [
                    'SPECIALIZATION' => $specialization,
                    'EXPERIENCE' => $experience,
                    'SERVICES' => array_map(static fn(string $code): int => $serviceIds[$code], $services),
                ]
            );
        }
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $servicesId = $helper->Iblock()->getIblockId('services', self::TYPE_ID);
        $mastersId = $helper->Iblock()->getIblockId('masters', self::TYPE_ID);

        foreach (array_keys(self::MASTERS) as $xmlId) {
            $mastersId && $helper->Iblock()->deleteElementByXmlId($mastersId, $xmlId);
        }
        foreach (array_keys(self::SERVICES) as $xmlId) {
            $servicesId && $helper->Iblock()->deleteElementByXmlId($servicesId, $xmlId);
        }
    }

    /**
     * Фото лежат в репозитории рядом с миграциями (свободная лицензия Unsplash) и копируются в upload.
     */
    private function picture(string $file, string $description): array
    {
        $path = __DIR__ . '/seed/' . $file;
        if (!is_file($path)) {
            throw new Exceptions\MigrationException('Нет файла изображения: ' . $path);
        }

        $picture = \CFile::MakeFileArray($path);
        $picture['description'] = $description;

        return $picture;
    }
}
