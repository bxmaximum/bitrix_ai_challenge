<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var CMain $APPLICATION */

$isMain = $APPLICATION->GetCurPage(false) === SITE_DIR;
$anchor = static fn (string $id): string => ($isMain ? '' : SITE_DIR) . '#' . $id;
?>
</main>

<footer class="site-footer" id="contacts" aria-labelledby="contacts-title">
    <div class="container">
        <h2 class="site-footer__title" id="contacts-title" data-reveal>
            <a href="<?= $anchor('booking') ?>">Ждём <em>вас</em></a>
        </h2>

        <nav class="site-footer__nav" aria-label="Разделы в подвале" data-reveal>
            <ul>
                <li><a href="<?= $anchor('main') ?>">Главная</a></li>
                <li><a href="<?= $anchor('about') ?>">О студии</a></li>
                <li><a href="<?= $anchor('services') ?>">Услуги</a></li>
                <li><a href="<?= $anchor('works') ?>">Работы</a></li>
                <li><a href="<?= $anchor('masters') ?>">Мастера</a></li>
                <li><a href="<?= $anchor('reviews') ?>">Отзывы</a></li>
                <li><a href="<?= $anchor('booking') ?>">Запись</a></li>
            </ul>
        </nav>

        <div class="site-footer__grid">
            <div class="site-footer__lead" data-reveal>
                <p class="site-footer__address">
                    Покровка, 31, стр.&nbsp;2 — <em>пять минут</em> пешком от метро «Китай-город».
                </p>
                <ul class="socials" aria-label="Студия в соцсетях">
                    <li>
                        <a class="circle-btn circle-btn--small" href="https://t.me/" target="_blank" rel="noopener" aria-label="Telegram">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="m21 4-3 16-6-5-3 3v-4l9-8-11 7-5-2 19-7Z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                    <li>
                        <a class="circle-btn circle-btn--small" href="https://vk.com/" target="_blank" rel="noopener" aria-label="ВКонтакте">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 7h3c.5 4 2.3 6 4 6.5V7h3v3.5c1.6-.2 3.2-2 3.8-3.5H20c-.6 2.4-2.5 4.4-3.8 5.1 1.3.6 3.4 2.2 4.1 4.9h-3.2c-.7-1.8-2.2-3.2-4.1-3.4V17h-.4C7 17 3.4 13.6 3 7Z" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                    <li>
                        <a class="circle-btn circle-btn--small" href="https://wa.me/74951284012" target="_blank" rel="noopener" aria-label="WhatsApp">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M4 20l1.2-4A8.5 8.5 0 1 1 8.6 19L4 20Z M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-.9.9a5 5 0 0 1-2.4-2.4l.9-.9-1-2L9.5 9" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/></svg>
                        </a>
                    </li>
                </ul>
            </div>
            <dl class="site-footer__contacts" data-reveal>
                <div>
                    <dt>Телефон</dt>
                    <dd><a href="tel:+74951284012">+7 (495) 128-40-12</a></dd>
                </div>
                <div>
                    <dt>Часы работы</dt>
                    <dd>Ежедневно, 10:00–20:00</dd>
                </div>
                <div>
                    <dt>Почта</dt>
                    <dd><a href="mailto:hello@lak-tochka.ru">hello@lak-tochka.ru</a></dd>
                </div>
                <div>
                    <dt>Адрес</dt>
                    <dd>Москва, ул. Покровка, 31, стр. 2, 2&nbsp;этаж</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="marquee" aria-hidden="true">
        <div class="marquee__track">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <span>Маникюр с характером</span><span class="marquee__dot"></span><span>Лак&amp;Точка</span><span class="marquee__dot"></span>
            <?php endfor; ?>
        </div>
    </div>

    <div class="site-footer__bottom container">
        <p>© 2026 Студия маникюра «Лак&amp;Точка»</p>
        <a href="<?= SITE_DIR ?>policy/">Политика обработки персональных данных</a>
        <a href="mailto:hello@lak-tochka.ru">hello@lak-tochka.ru</a>
        <p>ИП Соколова А. В., ОГРНИП 324770000000000</p>
    </div>
</footer>
</body>
</html>
