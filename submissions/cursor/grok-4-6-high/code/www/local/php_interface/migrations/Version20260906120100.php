<?php

namespace Sprint\Migration;

use CFile;

class Version20260906120100 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Iblocks services and masters with content';
    protected $moduleVersion = '5.13.0';

    public function up()
    {
        $helper = $this->getHelperManager();
        $siteId = $helper->Site()->getDefaultSiteIdIfExists();

        $helper->Iblock()->saveIblockType([
            'ID' => 'content',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Контент',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы',
                ],
            ],
        ]);

        $servicesId = $helper->Iblock()->saveIblock([
            'NAME' => 'Услуги',
            'CODE' => 'services',
            'API_CODE' => 'Services',
            'IBLOCK_TYPE_ID' => 'content',
            'LID' => [$siteId],
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'INDEX_ELEMENT' => 'N',
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
        ]);

        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Цена',
            'CODE' => 'PRICE',
            'PROPERTY_TYPE' => 'N',
            'IS_REQUIRED' => 'Y',
        ]);
        $helper->Iblock()->saveProperty($servicesId, [
            'NAME' => 'Длительность, мин',
            'CODE' => 'DURATION',
            'PROPERTY_TYPE' => 'N',
        ]);

        $mastersId = $helper->Iblock()->saveIblock([
            'NAME' => 'Мастера',
            'CODE' => 'masters',
            'API_CODE' => 'Masters',
            'IBLOCK_TYPE_ID' => 'content',
            'LID' => [$siteId],
            'ACTIVE' => 'Y',
            'SORT' => 110,
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'INDEX_ELEMENT' => 'N',
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
        ]);

        $helper->Iblock()->saveProperty($mastersId, [
            'NAME' => 'Специализация',
            'CODE' => 'SPECIALIZATION',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'Y',
        ]);

        $img = static fn (string $file): array => CFile::MakeFileArray(
            $_SERVER['DOCUMENT_ROOT'] . '/local/templates/laktochka/images/' . $file
        );

        $services = [
            ['classic-manicure', 'Классический маникюр', 'Аккуратная форма, уход за кутикулой и стойкий лак — база, с которой хочется выходить из студии.', 2500, 60, 'service-manicure.jpg'],
            ['hardware-manicure', 'Аппаратный маникюр', 'Мягкая обработка фрезой без среза: подходит чувствительной коже и тонкой кутикуле.', 2900, 70, 'service-repair.jpg'],
            ['gel-coating', 'Покрытие гель-лак', 'Плотный цвет на 3 недели. Каучуковая база и топ без липкости — носится, не скользит.', 3200, 75, 'service-gel.jpg'],
            ['nail-art', 'Дизайн / nail art', 'От тонкой линии до плотной втирки. Рисуем под ваш референс, не копируем ленту один в один.', 4500, 90, 'service-art.jpg'],
            ['spa-pedicure', 'SPA-педикюр', 'Распаривание, умный пилинг и массаж стоп. Лак или гель — как решите на месте.', 3800, 80, 'service-pedicure.jpg'],
            ['japanese-manicure', 'Японский маникюр', 'Полировка пастой с пчелиным воском: блеск своих ногтей, без плёнки и акрила.', 2700, 50, 'service-spa.jpg'],
            ['repair', 'Ремонт и укрепление', 'Скол, трещина, наращивание одного края. Укрепим, не превращая палец в «балку».', 800, 30, 'service-repair.jpg'],
            ['combo', 'Маникюр + педикюр', 'Синхронно два мастера или один слот подлиннее — экономия времени без гонки.', 5900, 120, 'service-combo.jpg'],
        ];

        foreach ($services as $i => $row) {
            $helper->Iblock()->saveElement($servicesId, [
                'NAME' => $row[1],
                'CODE' => $row[0],
                'XML_ID' => $row[0],
                'ACTIVE' => 'Y',
                'SORT' => ($i + 1) * 10,
                'PREVIEW_TEXT' => $row[2],
                'PREVIEW_PICTURE' => $img($row[5]),
            ], [
                'PRICE' => $row[3],
                'DURATION' => $row[4],
            ]);
        }

        $masters = [
            ['alina-vetrova', 'Алина Ветрова', 'Старший мастер, сложные формы и архитектура', 'Держит линию улыбки так, будто чертит её линейкой. Работает с тонкими ногтями и не любит «перепиленные» бока.', 'master-alina.jpg'],
            ['maria-khan', 'Мария Хан', 'Nail art и втирки', 'Собирает палитры как костюмер: сначала кожа и одежда, потом цвет. Если принесёте референс — соберёт спокойнее оригинала.', 'master-maria.jpg'],
            ['kira-leonova', 'Кира Леонова', 'Педикюр и SPA', 'Глубокий педикюр без героизма: стопы после неё ходят, а не «отдыхают три дня».', 'master-kira.jpg'],
            ['sofia-belova', 'Софья Белова', 'Японский уход и покрытие', 'Мастер тишины в кабинете. Если нужно «чтобы как свои, только лучше» — её слот.', 'master-sofia.jpg'],
        ];

        foreach ($masters as $i => $row) {
            $helper->Iblock()->saveElement($mastersId, [
                'NAME' => $row[1],
                'CODE' => $row[0],
                'XML_ID' => $row[0],
                'ACTIVE' => 'Y',
                'SORT' => ($i + 1) * 10,
                'PREVIEW_TEXT' => $row[3],
                'PREVIEW_PICTURE' => $img($row[4]),
            ], [
                'SPECIALIZATION' => $row[2],
            ]);
        }

        $this->outSuccess('Iblocks services and masters filled');
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $helper->Iblock()->deleteIblockIfExists('services');
        $helper->Iblock()->deleteIblockIfExists('masters');
    }
}
