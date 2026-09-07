<?php

namespace Sprint\Migration;

/**
 * Наполнение инфоблоков: услуги с ценами и мастера со специализациями.
 * Картинки лежат рядом с миграциями, в media/, поэтому наполнение воспроизводится на любой копии.
 */
class Version20260907100300 extends Version
{
    protected $author = 'bxmaximum';
    protected $description = 'Контент: 7 услуг и 4 мастера студии «Лак&Точка»';

    public function up()
    {
        $this->checkRequiredVersions([Version20260907100200::class]);

        $helper = $this->getHelperManager();

        $servicesId = $helper->Iblock()->getIblockIdIfExists('services', Version20260907100200::IBLOCK_TYPE);
        $mastersId = $helper->Iblock()->getIblockIdIfExists('masters', Version20260907100200::IBLOCK_TYPE);

        foreach ($this->services() as $sort => $service) {
            $helper->Iblock()->saveElementByCode(
                $servicesId,
                [
                    'CODE' => $service['code'],
                    'NAME' => $service['name'],
                    'ACTIVE' => 'Y',
                    'SORT' => ($sort + 1) * 100,
                    'PREVIEW_TEXT' => $service['text'],
                    'PREVIEW_TEXT_TYPE' => 'text',
                    'PREVIEW_PICTURE' => $this->file('services/' . $service['image']),
                ],
                [
                    'PRICE' => $service['price'],
                    'DURATION' => $service['duration'],
                    'PHOTO_ALT' => $service['alt'],
                ]
            );
        }

        foreach ($this->masters() as $sort => $master) {
            $helper->Iblock()->saveElementByCode(
                $mastersId,
                [
                    'CODE' => $master['code'],
                    'NAME' => $master['name'],
                    'ACTIVE' => 'Y',
                    'SORT' => ($sort + 1) * 100,
                    'PREVIEW_TEXT' => $master['text'],
                    'PREVIEW_TEXT_TYPE' => 'text',
                    'PREVIEW_PICTURE' => $this->file('masters/' . $master['image']),
                ],
                [
                    'SPECIALITY' => $master['speciality'],
                    'EXPERIENCE' => $master['experience'],
                    'PHOTO_ALT' => $master['alt'],
                ]
            );
        }

        $this->outSuccess('Загружено услуг: %d, мастеров: %d', count($this->services()), count($this->masters()));

        return true;
    }

    public function down()
    {
        $helper = $this->getHelperManager();

        $servicesId = $helper->Iblock()->getIblockIdIfExists('services', Version20260907100200::IBLOCK_TYPE);
        $mastersId = $helper->Iblock()->getIblockIdIfExists('masters', Version20260907100200::IBLOCK_TYPE);

        foreach ($this->services() as $service) {
            $helper->Iblock()->deleteElementIfExists($servicesId, $service['code']);
        }
        foreach ($this->masters() as $master) {
            $helper->Iblock()->deleteElementIfExists($mastersId, $master['code']);
        }

        return true;
    }

    private function file(string $relative): array
    {
        $path = __DIR__ . '/media/' . $relative;

        if (!is_file($path)) {
            throw new Exceptions\MigrationException('Не найден файл ' . $path);
        }

        return \CFile::MakeFileArray($path);
    }

    private function services(): array
    {
        return [
            [
                'code' => 'classic',
                'name' => 'Классический маникюр',
                'price' => 2200,
                'duration' => 60,
                'image' => 'classic.jpg',
                'alt' => 'Руки с аккуратным классическим маникюром нежно-розового оттенка',
                'text' => 'Обрезная техника, размягчающая ванночка и питательное масло для кутикулы. '
                    . 'Базовый уход, с которого начинают почти все наши постоянные гостьи.',
            ],
            [
                'code' => 'hardware',
                'name' => 'Аппаратный маникюр с покрытием',
                'price' => 3200,
                'duration' => 90,
                'image' => 'hardware.jpg',
                'alt' => 'Мастер наносит покрытие на ногти клиентки',
                'text' => 'Работаем фрезами без пропилов и травм валика. Гель-лак ложится тонким слоем '
                    . 'и держится 3–4 недели без сколов у свободного края.',
            ],
            [
                'code' => 'french',
                'name' => 'Френч и молочные оттенки',
                'price' => 3400,
                'duration' => 90,
                'image' => 'french.jpg',
                'alt' => 'Ногти с френчем и молочным покрытием',
                'text' => 'Тонкая улыбка от руки, без трафаретов. Молочные и телесные базы подбираем '
                    . 'под тон кожи — на фото и вживую выглядит одинаково хорошо.',
            ],
            [
                'code' => 'design',
                'name' => 'Дизайн: роспись и инкрустация',
                'price' => 1500,
                'duration' => 60,
                'image' => 'design.jpg',
                'alt' => 'Ногти с художественной росписью и золотой инкрустацией',
                'text' => 'Ручная роспись, фольга, втирка, камни. Приносите референс или доверьтесь '
                    . 'мастеру — соберём дизайн под ваш гардероб и повод.',
            ],
            [
                'code' => 'extension',
                'name' => 'Наращивание и архитектура формы',
                'price' => 4600,
                'duration' => 150,
                'image' => 'extension.jpg',
                'alt' => 'Наращённые ногти миндалевидной формы с плотным покрытием',
                'text' => 'Верхние формы и гель средней вязкости. Выстраиваем апекс так, чтобы длина '
                    . 'держалась и не мешала печатать и застёгивать пуговицы.',
            ],
            [
                'code' => 'pedicure',
                'name' => 'Комбинированный педикюр',
                'price' => 3800,
                'duration' => 90,
                'image' => 'pedicure.jpg',
                'alt' => 'Ухоженные стопы с цветами после комбинированного педикюра',
                'text' => 'Аппарат для стопы, ручной инструмент для кутикулы. Отдельный кабинет, '
                    . 'индивидуальные насадки и стерилизация в сухожаре.',
            ],
            [
                'code' => 'spa',
                'name' => 'SPA-уход для рук',
                'price' => 1900,
                'duration' => 45,
                'image' => 'spa.jpg',
                'alt' => 'Руки с цветком после SPA-ухода',
                'text' => 'Скраб, тёплая маска в термоперчатках и массаж кистей. Берут как дополнение '
                    . 'к маникюру — особенно с ноября по март.',
            ],
        ];
    }

    private function masters(): array
    {
        return [
            [
                'code' => 'nika-soloveva',
                'name' => 'Ника Соловьёва',
                'speciality' => 'Аппаратный маникюр, тонкие покрытия',
                'experience' => '9 лет в профессии',
                'image' => 'master-1.jpg',
                'alt' => 'Портрет мастера маникюра Ники Соловьёвой',
                'text' => 'Основательница студии. Любит идеально тонкий слой и оттенки, которые '
                    . 'не спорят с одеждой. Ведёт обучение по аппаратной технике.',
            ],
            [
                'code' => 'maryana-kats',
                'name' => 'Марьяна Кац',
                'speciality' => 'Наращивание, архитектура формы',
                'experience' => '7 лет в профессии',
                'image' => 'master-2.jpg',
                'alt' => 'Портрет мастера маникюра Марьяны Кац',
                'text' => 'Считает форму главным в маникюре. Соберёт миндаль или мягкий квадрат так, '
                    . 'чтобы пальцы визуально стали длиннее.',
            ],
            [
                'code' => 'asya-grinko',
                'name' => 'Ася Гринько',
                'speciality' => 'Нейл-арт, ручная роспись',
                'experience' => '5 лет в профессии',
                'image' => 'master-3.jpg',
                'alt' => 'Портрет мастера маникюра Аси Гринько',
                'text' => 'Художник по образованию. Рисует тонкой кистью что угодно — от акварельных '
                    . 'разводов до миниатюр по мотивам ваших фотографий.',
            ],
            [
                'code' => 'lena-dorohova',
                'name' => 'Лена Дорохова',
                'speciality' => 'Педикюр, SPA-уход',
                'experience' => '11 лет в профессии',
                'image' => 'master-4.jpg',
                'alt' => 'Портрет мастера педикюра Лены Дороховой',
                'text' => 'Работает с чувствительной кожей и вросшими ногтями. После её педикюра '
                    . 'гости возвращаются строго по расписанию — раз в пять недель.',
            ],
        ];
    }
}
