<?php declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @var CMain $APPLICATION */

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bxmax\Booking\Repository\CatalogRepository;

$APPLICATION->SetTitle('Лак&Точка — Студия архитектуры маникюра и SPA-ухода в Москве');

$services = [];
$masters = [];

if (Loader::includeModule('bxmax.booking'))
{
    /** @var CatalogRepository $catalogRepository */
    $catalogRepository = ServiceLocator::getInstance()->get(CatalogRepository::class);
    $services = $catalogRepository->listActiveServices();
    $masters = $catalogRepository->listActiveMasters();
}

$reviews = [
    [
        'author' => 'Мария Гончарова',
        'tag' => 'Архитектурный маникюр + гель-лак Luxio',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/reviews/review-1.jpg',
        'text' => '«Алина делает невероятно тонкое покрытие — ногти выглядят как свои, но с идеальным зеркальным бликом. Прошло ровно три с половиной недели: ни одного скола, ни малейшей отслойки у кутикулы. Отдельное удовольствие — тишина в студии и чашка свежезаваренного гречишного чая.»',
    ],
    [
        'author' => 'Елизавета Сорокина',
        'tag' => 'Пилочный маникюр и японский уход P.Shine',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/reviews/review-2.jpg',
        'text' => '«После полугода плотных гелей решила восстановить пластину у Валерии. Пилочный маникюр без единого режущего инструмента в паре с минеральной пастой P.Shine сотворили чудо: кутикула перестала бахромиться, а натуральные ногти блестят как розовый жемчуг.»',
    ],
    [
        'author' => 'Кристина Демидова',
        'tag' => 'Авторский минимализм и микро-френч',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/reviews/review-3.jpg',
        'text' => '«Искала место, где понимают эстетику "тихой роскоши" без страз и толстых пирогов геля. В «Лак&Точка» Кристина Орлова нарисовала мне тончайший молочный микро-френч за 55 минут. Записалась через сайт за две минуты — сразу видно реальные свободные окошки.»',
    ],
    [
        'author' => 'Анна Веденеева',
        'tag' => 'SMART-педикюр с молекулярным маслом',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/reviews/review-4.jpg',
        'text' => '«Карина Лебедева — лучший специалист по SMART-педикюру в Москве. Полировка по молекулярному маслу даёт ощущение шёлковых стоп без перетирания кожи, эффект держится больше месяца. Очень камерная, светлая студия, куда хочется возвращаться.»',
    ],
    [
        'author' => 'Дарья Белоусова',
        'tag' => 'SPA-ритуал La Sultane de Saba + маникюр',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/reviews/review-5.jpg',
        'text' => '«Забежала после тяжёлой рабочей недели на маникюр со SPA-уходом La Sultane de Saba. Тёплый гоммаж, масло карите и массаж кистей снимают напряжение лучше любого спа-курорта, а крафт-пакет со стерильным инструментом вскрывают прямо при тебе.»',
    ],
];

$galleryWorks = [
    [
        'title' => 'Молочный шёлк & микро-френч',
        'meta' => 'Мастер Алина Соколова · Luxio Coy',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-1.jpg',
        'alt' => 'Молочный маникюр с тонким белым микро-френчем на форме мягкий квадрат',
    ],
    [
        'title' => 'Архитектура «Чёткий квадрат»',
        'meta' => 'Мастер Кристина Орлова · Укрепление гелем',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-2.jpg',
        'alt' => 'Тонкое укрепление ногтей в форме чёткий квадрат с полупрозрачным нюдовым оттенком',
    ],
    [
        'title' => 'Японский глянец P.Shine',
        'meta' => 'Мастер Валерия Морозова · Эко-глянец',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-3.jpg',
        'alt' => 'Натуральные ухоженные ногти после японского минерального запечатывания P.Shine',
    ],
    [
        'title' => 'Глазированный жемчуг',
        'meta' => 'Мастер Кристина Орлова · Жемчужная втирка',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-4.jpg',
        'alt' => 'Миндальная форма ногтей с деликатным жемчужным переливом',
    ],
    [
        'title' => 'Глубокий винный Rouge',
        'meta' => 'Мастер Алина Соколова · Покрытие под кутикулу',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-5.jpg',
        'alt' => 'Короткие ногти с глянцевым винно-бордовым покрытием под кутикулу',
    ],
    [
        'title' => 'Пилочный маникюр + SPA',
        'meta' => 'Мастер Карина Лебедева · La Sultane de Saba',
        'photo' => SITE_TEMPLATE_PATH . '/assets/img/gallery/work-6.jpg',
        'alt' => 'Ухоженная кожа рук и натуральное покрытие после пилочного маникюра и SPA-ритуала',
    ],
];
?>

<!-- Секция 1: Hero-блок с оффером и CTA «Записаться» (.banner-wrp референса) -->
<section class="banner-wrp" aria-label="Главный экран студии «Лак&amp;Точка»">
    <div class="banner-img">
        <img
            src="<?= htmlspecialcharsbx(SITE_TEMPLATE_PATH . '/assets/img/hero/banner-main.jpg') ?>"
            alt="Ухоженные женские руки с безупречным нюдовым маникюром в студии «Лак и Точка»"
            fetchpriority="high"
        >
    </div>

    <div class="banner-section">
        <div class="container">
            <div class="banner-inner">
                <div class="banner-content color-fff">
                    <div class="main-title">
                        <h1 class="banner-split-text">Маникюр — это диалог.</h1>
                        <p class="banner-split-text" role="heading" aria-level="2">Мы превращаем его в искусство.</p>
                    </div>

                    <div class="common-detail">
                        <p>
                            Камерная студия архитектуры ногтей и бережного ухода «Лак&amp;Точка» на Патриарших прудах.
                            Тонкие прочные покрытия Luxio, атравматичная пилочная техника, стерильность медицинского класса
                            и безупречная носка до четырёх недель.
                        </p>
                    </div>

                    <div>
                        <a href="#booking" class="btn btn-black">Записаться</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Секция 2: Отзывы и философия студии (.testimonial-wrp референса) -->
<section class="testimonial-wrp" id="reviews" aria-labelledby="reviews-heading">
    <div class="container">
        <div class="testimonial-section">
            <div class="main-title color-fff">
                <h2 id="reviews-heading">Отзывы наших гостей</h2>
            </div>

            <div class="circular-title circular-text" data-circular-text="Что говорят наши гости • Лак&amp;Точка • "></div>

            <div class="testimonial-slider" data-testimonial-slider>
                <?php foreach ($reviews as $rIdx => $review): ?>
                    <article
                        class="testimonial-item<?= $rIdx === 0 ? ' is-active' : '' ?>"
                        data-testimonial-slide
                        aria-hidden="<?= $rIdx === 0 ? 'false' : 'true' ?>"
                    >
                        <div class="testimonial-img">
                            <img
                                src="<?= htmlspecialcharsbx($review['photo']) ?>"
                                alt="<?= htmlspecialcharsbx('Отзыв гостьи ' . $review['author'] . ' о процедуре ' . $review['tag']) ?>"
                                loading="lazy"
                            >
                        </div>
                        <div class="tetimonial-content">
                            <div class="common-detail">
                                <p><?= htmlspecialcharsbx($review['text']) ?></p>
                                <p class="testimonial-author">
                                    <?= htmlspecialcharsbx($review['author']) ?>
                                    <span class="testimonial-service-tag"><?= htmlspecialcharsbx($review['tag']) ?></span>
                                </p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <div class="slider-controls">
                    <button type="button" class="slick-prev slick-arrow" data-testimonial-prev aria-label="Предыдущий отзыв">
                        <svg width="21" height="39" viewBox="0 0 21 39" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M20.0979 1.4386L0.999801 19.9394L20.0979 38.4402" stroke="black" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <span class="slider-counter" data-testimonial-counter aria-live="polite">1 / <?= count($reviews) ?></span>
                    <button type="button" class="slick-next slick-arrow" data-testimonial-next aria-label="Следующий отзыв">
                        <svg width="21" height="39" viewBox="0 0 21 39" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M0.9021 1.4386L20.0002 19.9394L0.9021 38.4402" stroke="black" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- О студии -->
            <div class="studio-manifesto" aria-label="Стандарты студии Лак и Точка">
                <div>
                    <span class="studio-manifesto__item-num">Автоклав B-класса</span>
                    <p class="studio-manifesto__item-text">
                        Четырёхступенчатая медицинская стерилизация инструмента и индивидуальные одноразовые наборы пилок для каждого гостя.
                    </p>
                </div>
                <div>
                    <span class="studio-manifesto__item-num">280+ оттенков</span>
                    <p class="studio-manifesto__item-text">
                        Полные палитры канадских гелей Luxio, итальянских лаков Fedua и минеральных систем восстановления P.Shine.
                    </p>
                </div>
                <div>
                    <span class="studio-manifesto__item-num">Гарантия 14 дней</span>
                    <p class="studio-manifesto__item-text">
                        Выверенная архитектура апекса без утолщения пластины. Безупречная носка покрытия до 4 недель с официальной гарантией.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Секция 3: Услуги с ценами из инфоблока services (.services-wrp + .services-list-wrp референса) -->
<section class="services-wrp" id="services" aria-labelledby="services-heading">
    <div class="container">
        <div class="services-section">
            <div class="services-showcase-grid">
                <div class="service-block top-left-border">
                    <div class="main-title">
                        <h2 id="services-heading" class="split-text">Услуги</h2>
                    </div>
                    <div class="service-img">
                        <img
                            src="<?= htmlspecialcharsbx(SITE_TEMPLATE_PATH . '/assets/img/services/service-1.jpg') ?>"
                            alt="Архитектурный маникюр и покрытие Luxio"
                            loading="lazy"
                        >
                    </div>
                    <div class="common-detail">
                        <p>Архитектурный<br>маникюр</p>
                    </div>
                </div>

                <div class="service-block bottom-right-border">
                    <div class="service-img">
                        <img
                            src="<?= htmlspecialcharsbx(SITE_TEMPLATE_PATH . '/assets/img/services/service-2.jpg') ?>"
                            alt="Японский эко-маникюр P.Shine и пилочная обработка"
                            loading="lazy"
                        >
                    </div>
                    <div class="common-detail">
                        <p>Японский уход<br>&amp; пилочный маникюр</p>
                    </div>
                    <div class="circular-title circular-text1" data-circular-text="Эстетика чистых линий • "></div>
                </div>

                <div class="service-block top-right-border">
                    <div class="service-img">
                        <img
                            src="<?= htmlspecialcharsbx(SITE_TEMPLATE_PATH . '/assets/img/services/service-5.jpg') ?>"
                            alt="SMART-педикюр и SPA-ритуалы La Sultane de Saba"
                            loading="lazy"
                        >
                    </div>
                    <div class="common-detail">
                        <p>SMART-педикюр<br>&amp; SPA-ритуалы</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="services-list-wrp" aria-label="Полный прайс-лист услуг из инфоблока">
    <div class="container">
        <div class="services-list-main">
            <div class="services-list-section">
                <?php foreach ($services as $service): ?>
                    <article class="services-list-block">
                        <div class="services-list-block__head">
                            <div class="service-title">
                                <h3><?= htmlspecialcharsbx((string)$service['name']) ?></h3>
                            </div>
                            <div class="services-list-block__price">
                                <?= htmlspecialcharsbx((string)$service['priceFormatted']) ?>
                            </div>
                        </div>

                        <div class="service-content">
                            <div class="common-detail">
                                <p><?= htmlspecialcharsbx((string)$service['description']) ?></p>
                            </div>
                            <div class="service-list">
                                <ul>
                                    <li><?= htmlspecialcharsbx((string)$service['priceFormatted']) ?></li>
                                    <li><?= htmlspecialcharsbx((string)$service['duration']) ?></li>
                                    <li><?= htmlspecialcharsbx((string)$service['category']) ?></li>
                                    <li class="service-list__action-item">
                                        <button
                                            type="button"
                                            class="service-list__book-btn"
                                            data-pick-service="<?= (int)$service['id'] ?>"
                                        >
                                            Выбрать время →
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($service['photoUrl'])): ?>
                            <div class="service-list-img" aria-hidden="true">
                                <img
                                    src="<?= htmlspecialcharsbx((string)$service['photoUrl']) ?>"
                                    alt=""
                                    loading="lazy"
                                >
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="book-visit">
                <a href="#booking" class="btn btn-black">Записаться онлайн</a>
            </div>
        </div>
    </div>
</section>

<!-- Секция 4: Мастера из инфоблока masters (.meet-team-wrp референса) -->
<section class="meet-team-wrp" id="masters" aria-labelledby="masters-heading">
    <div class="container">
        <div class="meet-team-section">
            <div class="meet-team-title">
                <div class="main-title">
                    <h2 id="masters-heading" class="split-text">Мастера студии</h2>
                </div>
                <div class="common-detail">
                    <p>
                        В команде «Лак&amp;Точка» работают сертифицированные мастера со стажем от 5 до 8 лет.
                        Каждый специалист владеет атравматичными техниками обработки и создаёт безупречную архитектуру ногтей.
                    </p>
                </div>
            </div>

            <div class="meet-team-row">
                <?php
                $masterBlockClasses = ['sabrina-block', 'vanessa-block', 'kariana-block', 'kristie-block'];
                ?>
                <div class="meet-team-grid">
                    <?php foreach ($masters as $mIdx => $master):
                        $blockClass = $masterBlockClasses[$mIdx % count($masterBlockClasses)];
                        ?>
                        <article class="meet-team-block <?= $blockClass ?>">
                            <?php if (!empty($master['photoUrl'])): ?>
                                <div class="meet-team-img">
                                    <img
                                        src="<?= htmlspecialcharsbx((string)$master['photoUrl']) ?>"
                                        alt="<?= htmlspecialcharsbx('Мастер ' . (string)$master['name'] . ' — ' . (string)$master['specialization']) ?>"
                                        loading="lazy"
                                    >
                                </div>
                            <?php endif; ?>
                            <div class="meet-team-card-body">
                                <div class="meet-team-head">
                                    <h3 class="meet-team-name"><?= htmlspecialcharsbx((string)$master['name']) ?></h3>
                                    <?php if (!empty($master['experience'])): ?>
                                        <span class="meet-team-exp"><?= htmlspecialcharsbx((string)$master['experience']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="meet-team-spec"><?= htmlspecialcharsbx((string)$master['specialization']) ?></div>
                                <?php if (!empty($master['description'])): ?>
                                    <p class="meet-team-bio"><?= htmlspecialcharsbx((string)$master['description']) ?></p>
                                <?php endif; ?>
                                <div class="read-bio">
                                    <button
                                        type="button"
                                        class="btn-border"
                                        data-pick-master="<?= (int)$master['id'] ?>"
                                    >
                                        Записаться к мастеру
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="circular-title circular-text3" data-circular-text="Искусство в деталях • Лак&amp;Точка • "></div>
            </div>

            <div class="view-all">
                <a href="#booking" class="btn btn-black">Выбрать слот мастера</a>
            </div>
        </div>
    </div>
</section>

<!-- Секция 5: Галерея работ (.our-surgeons-wrp референса) -->
<section class="surgeon-main" id="gallery" aria-labelledby="gallery-heading">
    <div class="our-surgeons-wrp">
        <div class="container">
            <div class="our-surgeons-section">
                <div class="main-title text-center">
                    <h2 id="gallery-heading" class="split-text">Галерея работ: чистые линии и живой глянец</h2>
                </div>

                <div class="our-surgeons-row">
                    <span class="dot-left" aria-hidden="true"></span>
                    <div class="al-logo" aria-hidden="true">
                        <h4>Л&amp;Т</h4>
                    </div>

                    <div class="gallery-medallions-grid">
                        <?php foreach ($galleryWorks as $work): ?>
                            <figure class="our-surgeons-block">
                                <div class="surgeon-img">
                                    <img
                                        src="<?= htmlspecialcharsbx($work['photo']) ?>"
                                        alt="<?= htmlspecialcharsbx($work['alt']) ?>"
                                        loading="lazy"
                                    >
                                </div>
                                <figcaption>
                                    <h3 class="gallery-work-title"><?= htmlspecialcharsbx($work['title']) ?></h3>
                                    <p class="gallery-work-meta"><?= htmlspecialcharsbx($work['meta']) ?></p>
                                    <a href="#booking" class="btn-border">Хочу так же</a>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>

                    <span class="dot-right" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Секция 6: Блок онлайн-записи (.contact-wrp#bookavisit референса + компонент модуля bxmax:booking.widget) -->
<section class="contact-wrp" id="booking" aria-labelledby="booking-heading">
    <div class="container">
        <div class="contact-main">
            <div class="main-title text-center">
                <h2 id="booking-heading" class="split-text">Онлайн-запись на визит</h2>
            </div>
            <p class="common-detail contact-subtitle">
                Выберите желаемую процедуру, мастера и удобное время в расписании.
                Запись подтверждается мгновенно без ожидания звонка.
            </p>

            <?php if (Loader::includeModule('bxmax.booking')): ?>
                <?php
                $APPLICATION->IncludeComponent(
                    'bxmax:booking.widget',
                    '.default',
                    [
                        'CACHE_TYPE' => 'N',
                        'CACHE_TIME' => 0,
                    ],
                    false
                );
                ?>
            <?php else: ?>
                <div class="common-detail text-center" role="status">
                    <p>Виджет онлайн-записи временно недоступен. Позвоните нам по телефону <a href="tel:+74951284420">+7 (495) 128-44-20</a>.</p>
                </div>
            <?php endif; ?>

            <div class="circular-title" data-circular-text="Book a visit • Лак&amp;Точка • "></div>
        </div>
    </div>
</section>

<!-- Секция 7: Контакты (.get-in-touch-wrp референса) -->
<section class="get-in-touch-wrp" id="contacts" aria-labelledby="contacts-heading">
    <div class="container">
        <div class="get-in-touch-section">
            <div class="get-in-touch-grid">
                <div class="get-in-touch-block">
                    <div class="main-title">
                        <h2 id="contacts-heading" class="split-text">Ждём вас в студии</h2>
                    </div>

                    <div class="get-in-touch-content">
                        <div class="get-in-touch-detail">
                            <div class="common-detail">
                                <p>Телефон: <a href="tel:+74951284420">+7 (495) 128-44-20</a></p>
                            </div>
                            <div class="common-detail">
                                <p>WhatsApp / Telegram: <a href="tel:+79991284420">+7 (999) 128-44-20</a></p>
                            </div>
                        </div>

                        <div class="get-in-touch-detail">
                            <div class="common-detail">
                                <p>Москва, Малая Бронная, 24, стр. 1<br>м. Маяковская / Тверская</p>
                            </div>
                            <div class="common-detail">
                                <p><a href="mailto:hello@laktochka.studio">hello@laktochka.studio</a></p>
                            </div>
                        </div>

                        <div class="get-in-touch-detail">
                            <div class="common-detail">
                                <p>Ежедневно с 10:00 до 20:00<br>Собственная парковка во дворе</p>
                            </div>
                            <div class="common-detail">
                                <p><a href="#booking" class="btn-border">Выбрать слот онлайн</a></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="get-in-touch-right">
                    <div class="get-in-touch-collage">
                        <?php foreach (array_slice($masters, 0, 4) as $cIdx => $cMaster):
                            $contactClasses = ['contact-one', 'contact-two', 'contact-three', 'contact-four'];
                            $cClass = $contactClasses[$cIdx % 4];
                            ?>
                            <div class="get-in-touch-img <?= $cClass ?>">
                                <?php if (!empty($cMaster['photoUrl'])): ?>
                                    <div class="contact-img">
                                        <img
                                            src="<?= htmlspecialcharsbx((string)$cMaster['photoUrl']) ?>"
                                            alt="<?= htmlspecialcharsbx('Мастер студии ' . (string)$cMaster['name']) ?>"
                                            loading="lazy"
                                        >
                                    </div>
                                <?php endif; ?>
                                <div class="get-in-touch-name">
                                    <?= htmlspecialcharsbx((string)$cMaster['name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
