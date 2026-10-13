<?php declare(strict_types=1);

namespace Sprint\Migration;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bxmax\Booking\Service\SlotService;

class LakTochkaSetup20261013205816 extends Version
{
    protected $author = 'Bxmax';
    protected $description = 'Setup Lak and Tochka iblocks module template and schedule';
    protected $moduleVersion = '5.15.1';

    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Iblock()->saveIblockType([
            'ID' => 'lak_tochka',
            'SECTIONS' => 'N',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Студия маникюра «Лак&Точка»',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы',
                ],
                'en' => [
                    'NAME' => 'Lak & Tochka Nail Studio',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
            ],
        ]);

        // 1. Инфоблок «Услуги» (services)
        $servicesIblockId = (int)$helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => 'lak_tochka',
            'LID' => ['s1'],
            'CODE' => 'services',
            'API_CODE' => 'Services',
            'NAME' => 'Услуги',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'VERSION' => 2,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'GROUP_ID' => [
                2 => 'R',
            ],
        ]);

        $helper->Iblock()->saveProperty($servicesIblockId, [
            'NAME' => 'Стоимость, ₽',
            'CODE' => 'PRICE',
            'PROPERTY_TYPE' => 'N',
            'IS_REQUIRED' => 'Y',
            'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($servicesIblockId, [
            'NAME' => 'Длительность',
            'CODE' => 'DURATION',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($servicesIblockId, [
            'NAME' => 'Категория',
            'CODE' => 'CATEGORY',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 300,
        ]);
        $helper->Iblock()->saveProperty($servicesIblockId, [
            'NAME' => 'Путь к фото',
            'CODE' => 'PHOTO_PATH',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 400,
        ]);

        $servicesData = [
            [
                'XML_ID' => 'service_arch_gel',
                'CODE' => 'architectural-gel-manicure',
                'NAME' => 'Архитектурный маникюр + гель-лак Luxio',
                'SORT' => 100,
                'PREVIEW_TEXT' => 'Бережная комбинированная обработка кутикулы, выравнивание архитектуры ногтевой пластины и ультратонкое покрытие канадским гель-лаком Luxio под кутикулу.',
                'PRICE' => 3200,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Маникюр и покрытие',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-1.jpg',
            ],
            [
                'XML_ID' => 'service_japanese_pshine',
                'CODE' => 'japanese-eco-manicure-pshine',
                'NAME' => 'Японский эко-маникюр P.Shine',
                'SORT' => 200,
                'PREVIEW_TEXT' => 'Глубокое глянцевое запечатывание натуральной пластины минеральной пастой с диатомитовой глиной и пудрой на основе пчелиного воска. Идеально для восстановления.',
                'PRICE' => 2900,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Эко-уход и глянец',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-2.jpg',
            ],
            [
                'XML_ID' => 'service_saw_manicure',
                'CODE' => 'saw-manicure-christina-fitzgerald',
                'NAME' => 'Пилочный атравматичный маникюр',
                'SORT' => 300,
                'PREVIEW_TEXT' => 'Ювелирная сухая обработка одноразовыми абразивными пилками без режущих инструментов и размачивания: кутикула отрастает медленнее и остаётся гладкой до трёх недель.',
                'PRICE' => 2600,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Атравматичный маникюр',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-3.jpg',
            ],
            [
                'XML_ID' => 'service_gel_modeling',
                'CODE' => 'hard-gel-architecture-modeling',
                'NAME' => 'Моделирование и укрепление архитектуры гелем',
                'SORT' => 400,
                'PREVIEW_TEXT' => 'Создание тонких, прочных форм «чёткий квадрат» или «мягкий миндаль» низкотемпературным жёстким гелем с естественной полупрозрачной глубиной у кутикулы.',
                'PRICE' => 4500,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Моделирование',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-4.jpg',
            ],
            [
                'XML_ID' => 'service_smart_pedicure',
                'CODE' => 'smart-pedicure-molecular-oil',
                'NAME' => 'SMART-педикюр с молекулярным маслом и покрытием',
                'SORT' => 500,
                'PREVIEW_TEXT' => 'Деликатная полировка стоп запатентованными SMART-дисками по натуральному молекулярному маслу с эффектом шёлковой кожи, обработка пальчиков и стойкое покрытие.',
                'PRICE' => 4100,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Эстетический педикюр',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-5.jpg',
            ],
            [
                'XML_ID' => 'service_micro_french_art',
                'CODE' => 'minimalist-nail-art-micro-french',
                'NAME' => 'Авторский минимализм и микро-френч',
                'SORT' => 600,
                'PREVIEW_TEXT' => 'Тончайшая прорисовка французской линии, эффект «глазированного жемчуга», воздушное молочное омбре или хромированные акценты с покрытием и укреплением.',
                'PRICE' => 3700,
                'DURATION' => '60 мин',
                'CATEGORY' => 'Дизайн и покрытие',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-6.jpg',
            ],
            [
                'XML_ID' => 'service_spa_ritual',
                'CODE' => 'spa-ritual-la-sultane-manicure',
                'NAME' => 'SPA-ритуал La Sultane de Saba + маникюр',
                'SORT' => 700,
                'PREVIEW_TEXT' => 'Бережный гигиенический маникюр в сочетании с тёплым сахарным гоммажем, обёртыванием на основе масла карите и расслабляющим лимфодренажным массажем кистей.',
                'PRICE' => 3500,
                'DURATION' => '60 мин',
                'CATEGORY' => 'SPA-ритуалы',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/services/service-7.jpg',
            ],
        ];

        $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');
        foreach ($servicesData as $item)
        {
            $fields = [
                'NAME' => $item['NAME'],
                'CODE' => $item['CODE'],
                'XML_ID' => $item['XML_ID'],
                'ACTIVE' => 'Y',
                'SORT' => $item['SORT'],
                'PREVIEW_TEXT' => $item['PREVIEW_TEXT'],
                'PREVIEW_TEXT_TYPE' => 'text',
            ];
            $absPhoto = $docRoot . $item['PHOTO'];
            if (is_file($absPhoto))
            {
                $fields['PREVIEW_PICTURE'] = \CFile::MakeFileArray($absPhoto);
            }

            $props = [
                'PRICE' => $item['PRICE'],
                'DURATION' => $item['DURATION'],
                'CATEGORY' => $item['CATEGORY'],
                'PHOTO_PATH' => $item['PHOTO'],
            ];

            $helper->Iblock()->saveElementByXmlId($servicesIblockId, $fields, $props);
        }

        // 2. Инфоблок «Мастера» (masters)
        $mastersIblockId = (int)$helper->Iblock()->saveIblock([
            'IBLOCK_TYPE_ID' => 'lak_tochka',
            'LID' => ['s1'],
            'CODE' => 'masters',
            'API_CODE' => 'Masters',
            'NAME' => 'Мастера',
            'ACTIVE' => 'Y',
            'SORT' => 200,
            'VERSION' => 2,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
            'GROUP_ID' => [
                2 => 'R',
            ],
        ]);

        $helper->Iblock()->saveProperty($mastersIblockId, [
            'NAME' => 'Специализация',
            'CODE' => 'SPECIALIZATION',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'Y',
            'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($mastersIblockId, [
            'NAME' => 'Стаж работы',
            'CODE' => 'EXPERIENCE',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($mastersIblockId, [
            'NAME' => 'Грейд',
            'CODE' => 'BADGE',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 300,
        ]);
        $helper->Iblock()->saveProperty($mastersIblockId, [
            'NAME' => 'Портфолио / соцсети',
            'CODE' => 'INSTAGRAM',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 400,
        ]);
        $helper->Iblock()->saveProperty($mastersIblockId, [
            'NAME' => 'Путь к фото',
            'CODE' => 'PHOTO_PATH',
            'PROPERTY_TYPE' => 'S',
            'IS_REQUIRED' => 'N',
            'SORT' => 500,
        ]);

        $mastersData = [
            [
                'XML_ID' => 'master_alina_sokolova',
                'CODE' => 'alina-sokolova',
                'NAME' => 'Алина Соколова',
                'SORT' => 100,
                'PREVIEW_TEXT' => 'Основатель эстетического стандарта «Лак&Точка». Специализируется на ультратонком гелевом укреплении, идеальной геометрии квадрата и невесомом микро-френче.',
                'SPECIALIZATION' => 'Арт-директор · Архитектура форм и микро-френч',
                'EXPERIENCE' => 'Стаж 8 лет',
                'BADGE' => 'Art Director',
                'INSTAGRAM' => '@alina.laktochka',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/masters/master-1.jpg',
            ],
            [
                'XML_ID' => 'master_valeria_morozova',
                'CODE' => 'valeria-morozova',
                'NAME' => 'Валерия Морозова',
                'SORT' => 200,
                'PREVIEW_TEXT' => 'Эксперт по атравматичной пилочной технике Christina Fitzgerald и глубокому восстановлению ногтевой пластины минеральными составами P.Shine.',
                'SPECIALIZATION' => 'Топ-мастер · Пилочный и японский маникюр P.Shine',
                'EXPERIENCE' => 'Стаж 6 лет',
                'BADGE' => 'Top Master',
                'INSTAGRAM' => '@valeria.laktochka',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/masters/master-2.jpg',
            ],
            [
                'XML_ID' => 'master_karina_lebedeva',
                'CODE' => 'karina-lebedeva',
                'NAME' => 'Карина Лебедева',
                'SORT' => 300,
                'PREVIEW_TEXT' => 'Сертифицированный специалист по дисковому SMART-педикюру и бережной работе с чувствительной кожей стоп. Создаёт безупречные нюдовые покрытия.',
                'SPECIALIZATION' => 'Ведущий мастер · SMART-педикюр и SPA-уход',
                'EXPERIENCE' => 'Стаж 5 лет',
                'BADGE' => 'Senior Master',
                'INSTAGRAM' => '@karina.laktochka',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/masters/master-3.jpg',
            ],
            [
                'XML_ID' => 'master_kristina_orlova',
                'CODE' => 'kristina-orlova',
                'NAME' => 'Кристина Орлова',
                'SORT' => 400,
                'PREVIEW_TEXT' => 'Виртуоз чистого покрытия под кутикулу, полупрозрачных молочных текстур, жемчужных втирок и тончайшей минималистичной графики.',
                'SPECIALIZATION' => 'Топ-мастер · Тонкое покрытие Luxio и минимализм',
                'EXPERIENCE' => 'Стаж 6 лет',
                'BADGE' => 'Top Master',
                'INSTAGRAM' => '@kristina.laktochka',
                'PHOTO' => '/local/templates/lak_tochka/assets/img/masters/master-4.jpg',
            ],
        ];

        foreach ($mastersData as $mItem)
        {
            $fields = [
                'NAME' => $mItem['NAME'],
                'CODE' => $mItem['CODE'],
                'XML_ID' => $mItem['XML_ID'],
                'ACTIVE' => 'Y',
                'SORT' => $mItem['SORT'],
                'PREVIEW_TEXT' => $mItem['PREVIEW_TEXT'],
                'PREVIEW_TEXT_TYPE' => 'text',
            ];
            $absPhoto = $docRoot . $mItem['PHOTO'];
            if (is_file($absPhoto))
            {
                $fields['PREVIEW_PICTURE'] = \CFile::MakeFileArray($absPhoto);
            }

            $props = [
                'SPECIALIZATION' => $mItem['SPECIALIZATION'],
                'EXPERIENCE' => $mItem['EXPERIENCE'],
                'BADGE' => $mItem['BADGE'],
                'INSTAGRAM' => $mItem['INSTAGRAM'],
                'PHOTO_PATH' => $mItem['PHOTO'],
            ];

            $helper->Iblock()->saveElementByXmlId($mastersIblockId, $fields, $props);
        }

        // 3. Установка модуля bxmax.booking и генерация расписания на 14 дней
        $installerFile = $docRoot . '/local/modules/bxmax.booking/install/index.php';
        if (is_file($installerFile))
        {
            require_once $installerFile;
            if (class_exists('bxmax_booking'))
            {
                $moduleInstaller = new \bxmax_booking();
                if (!ModuleManager::isModuleInstalled('bxmax.booking'))
                {
                    $moduleInstaller->DoInstall();
                }
                else
                {
                    $moduleInstaller->InstallDB();
                    $moduleInstaller->InstallEvents();
                    $moduleInstaller->InstallFiles();
                }
            }
        }

        if (Loader::includeModule('bxmax.booking'))
        {
            /** @var SlotService $slotService */
            $slotService = ServiceLocator::getInstance()->get(SlotService::class);
            $genResult = $slotService->generateScheduleForAllMasters(14);
            $this->outSuccess('Сгенерировано новых слотов: %d', (int)($genResult->getData()['created'] ?? 0));
        }

        // 4. Привязка шаблона сайта lak_tochka к сайту s1
        $helper->Site()->setSiteTemplates('s1', [
            [
                'TEMPLATE' => 'lak_tochka',
                'SORT' => 1,
                'CONDITION' => '',
            ],
        ]);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');

        $installerFile = $docRoot . '/local/modules/bxmax.booking/install/index.php';
        if (is_file($installerFile) && ModuleManager::isModuleInstalled('bxmax.booking'))
        {
            require_once $installerFile;
            if (class_exists('bxmax_booking'))
            {
                $moduleInstaller = new \bxmax_booking();
                $moduleInstaller->DoUninstall();
            }
        }

        $helper->Iblock()->deleteIblockIfExists('services', 'lak_tochka');
        $helper->Iblock()->deleteIblockIfExists('masters', 'lak_tochka');
    }
}
