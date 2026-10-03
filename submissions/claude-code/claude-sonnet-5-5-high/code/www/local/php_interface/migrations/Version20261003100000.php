<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;

/**
 * Инфоблоки «Услуги» (services) и «Мастера» (masters) с наполнением.
 */
class Version20261003100000 extends Version
{
    protected $author = 'bxmax';
    protected $description = 'Инфоблоки services и masters с услугами и мастерами студии';
    protected $moduleVersion = '5.15.1';

    private const TYPE = 'lak_tochka';
    private const IMG_DIR = '/local/templates/laktochka/img/';

    public function up()
    {
        $helper = $this->getHelperManager();
        $iblock = $helper->Iblock();

        $iblock->saveIblockType([
            'ID' => self::TYPE,
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 500,
            'LANG' => [
                'ru' => ['NAME' => 'Лак&Точка', 'SECTION_NAME' => 'Разделы', 'ELEMENT_NAME' => 'Элементы'],
                'en' => ['NAME' => 'Lak&Tochka', 'SECTION_NAME' => 'Sections', 'ELEMENT_NAME' => 'Elements'],
            ],
        ]);

        $servicesId = $iblock->saveIblock([
            'NAME' => 'Услуги',
            'CODE' => 'services',
            'API_CODE' => 'services',
            'IBLOCK_TYPE_ID' => self::TYPE,
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'VERSION' => 2,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'GROUP_ID' => ['1' => 'X', '2' => 'R'],
        ]);
        $iblock->saveProperty($servicesId, [
            'NAME' => 'Цена, ₽', 'CODE' => 'PRICE', 'PROPERTY_TYPE' => 'N', 'SORT' => 100, 'IS_REQUIRED' => 'Y',
        ]);
        $iblock->saveProperty($servicesId, [
            'NAME' => 'Длительность, мин', 'CODE' => 'DURATION', 'PROPERTY_TYPE' => 'N', 'SORT' => 200,
        ]);

        $mastersId = $iblock->saveIblock([
            'NAME' => 'Мастера',
            'CODE' => 'masters',
            'API_CODE' => 'masters',
            'IBLOCK_TYPE_ID' => self::TYPE,
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'SORT' => 200,
            'VERSION' => 2,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'GROUP_ID' => ['1' => 'X', '2' => 'R'],
        ]);
        $iblock->saveProperty($mastersId, [
            'NAME' => 'Специализация', 'CODE' => 'SPECIALIZATION', 'PROPERTY_TYPE' => 'S', 'SORT' => 100, 'IS_REQUIRED' => 'Y',
        ]);
        $iblock->saveProperty($mastersId, [
            'NAME' => 'Стаж, лет', 'CODE' => 'EXPERIENCE', 'PROPERTY_TYPE' => 'N', 'SORT' => 200,
        ]);

        $this->seed($servicesId, $this->services(), static fn (array $row): array => [
            'PRICE' => $row['price'], 'DURATION' => $row['duration'],
        ]);
        $this->seed($mastersId, $this->masters(), static fn (array $row): array => [
            'SPECIALIZATION' => $row['specialization'], 'EXPERIENCE' => $row['experience'],
        ]);
    }

    public function down()
    {
        $iblock = $this->getHelperManager()->Iblock();
        $iblock->deleteIblockIfExists('services', self::TYPE);
        $iblock->deleteIblockIfExists('masters', self::TYPE);
        $iblock->deleteIblockTypeIfExists(self::TYPE);
    }

    private function seed(int $iblockId, array $rows, callable $props): void
    {
        $iblock = $this->getHelperManager()->Iblock();
        $root = Application::getDocumentRoot();

        foreach ($rows as $i => $row) {
            $fields = [
                'NAME' => $row['name'],
                'CODE' => $row['code'],
                'XML_ID' => $row['code'],
                'SORT' => ($i + 1) * 100,
                'ACTIVE' => 'Y',
                'PREVIEW_TEXT' => $row['text'],
                'PREVIEW_TEXT_TYPE' => 'text',
            ];

            $exists = $iblock->getElementId($iblockId, $row['code']);
            // картинки грузим один раз, повторный прогон не плодит файлы
            if (!$exists && is_file($root . self::IMG_DIR . $row['image'])) {
                $fields['PREVIEW_PICTURE'] = $this->makeFile($row['image'], $row['name']);
            }

            $iblock->saveElementByCode($iblockId, $fields, $props($row));
        }
    }

    private function makeFile(string $name, string $description): array
    {
        // работаем с копией: CFile::SaveFile перемещает временный файл
        $tmp = tempnam(sys_get_temp_dir(), 'lt_');
        copy(Application::getDocumentRoot() . self::IMG_DIR . $name, $tmp);

        return [
            'name' => $name,
            'size' => filesize($tmp),
            'tmp_name' => $tmp,
            'type' => 'image/jpeg',
            'error' => 0,
            'MODULE_ID' => 'iblock',
            'description' => $description,
        ];
    }

    private function services(): array
    {
        return [
            ['code' => 'classic-manicure', 'name' => 'Классический маникюр', 'price' => 1400, 'duration' => 60, 'image' => 'service-1.jpg',
                'text' => 'Бережная обработка кутикулы, форма, уход и лёгкий массаж рук. Без покрытия — для тех, кто любит чистые ухоженные ногти.'],
            ['code' => 'gel-polish', 'name' => 'Маникюр с гель-лаком', 'price' => 2200, 'duration' => 90, 'image' => 'service-2.jpg',
                'text' => 'Обработка, выравнивание ногтевой пластины и стойкое покрытие из палитры в 200+ оттенков. Носится до четырёх недель.'],
            ['code' => 'extension', 'name' => 'Наращивание и укрепление', 'price' => 3600, 'duration' => 120, 'image' => 'service-3.jpg',
                'text' => 'Моделирование формы на гелевых формах или укрепление собственных ногтей базой с каучуком. Лёгкие, прочные, натуральные.'],
            ['code' => 'nail-art', 'name' => 'Авторский дизайн', 'price' => 2900, 'duration' => 120, 'image' => 'service-6.jpg',
                'text' => 'Френч, ручная роспись, втирка, фольга и объёмные элементы. Принесите референс — мастер предложит, как адаптировать его под вашу форму.'],
            ['code' => 'pedicure', 'name' => 'Классический педикюр', 'price' => 2500, 'duration' => 75, 'image' => 'service-4.jpg',
                'text' => 'Аппаратная обработка стоп и ногтей, уход за кожей, скраб и увлажняющая маска. Результат — гладкая кожа и аккуратная форма.'],
            ['code' => 'pedicure-gel', 'name' => 'Педикюр с гель-лаком', 'price' => 3200, 'duration' => 105, 'image' => 'service-5.jpg',
                'text' => 'Полный педикюр и стойкое покрытие. Подходит для отпуска, свадьбы или просто для хорошего настроения на месяц вперёд.'],
            ['code' => 'spa-hands', 'name' => 'SPA-уход для рук', 'price' => 1800, 'duration' => 45, 'image' => 'service-7.jpg',
                'text' => 'Пилинг, парафиновая ванночка и массаж. Возвращает коже мягкость после зимы, работы за клавиатурой или долгого дня.'],
        ];
    }

    private function masters(): array
    {
        return [
            ['code' => 'anastasia-belova', 'name' => 'Анастасия Белова', 'specialization' => 'Авторский дизайн и nail-арт', 'experience' => 9, 'image' => 'master-1.jpg',
                'text' => 'Рисует тонкие линии и сложные композиции. Любит спокойные оттенки и точные детали. Ведёт мастер-классы для начинающих.'],
            ['code' => 'maria-orlova', 'name' => 'Мария Орлова', 'specialization' => 'Европейский и аппаратный маникюр', 'experience' => 7, 'image' => 'master-2.jpg',
                'text' => 'Работает чисто и бережно, даже с тонкими и чувствительными ногтями. Её гель-лак держится по четыре недели.'],
            ['code' => 'ksenia-mironova', 'name' => 'Ксения Миронова', 'specialization' => 'Педикюр и SPA-уходы', 'experience' => 6, 'image' => 'master-3.jpg',
                'text' => 'Специалист по педикюру и уходу за кожей. С ней можно выдохнуть: тихая музыка, тёплый плед и никакой суеты.'],
            ['code' => 'daria-levina', 'name' => 'Дарья Левина', 'specialization' => 'Наращивание и коррекция формы', 'experience' => 11, 'image' => 'master-4.jpg',
                'text' => 'Строит идеальную архитектуру ногтя: от короткого квадрата до длинного миндаля. Всегда честно скажет, что подойдёт именно вам.'],
        ];
    }
}
