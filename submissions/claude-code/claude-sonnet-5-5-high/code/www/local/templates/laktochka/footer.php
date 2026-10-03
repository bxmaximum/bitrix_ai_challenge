<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
?>
</main>

<footer class="lt-footer" id="contacts" aria-labelledby="lt-footer-title">
    <div class="lt-container lt-footer__inner">
        <div class="lt-footer__head">
            <svg class="lt-footer__ornament" aria-hidden="true" focusable="false"><use href="#lt-ornament-wide"/></svg>
            <h2 class="lt-footer__title" id="lt-footer-title">Приходите, <br>мы вас ждём</h2>
        </div>

        <div class="lt-footer__cols">
            <div class="lt-footer__col">
                <h3 class="lt-footer__h">Адрес</h3>
                <address class="lt-footer__text">
                    Москва, Малая Ордынка, 21,<br>вход со двора, 2 этаж
                </address>
                <p class="lt-footer__note">Пять минут пешком от метро «Новокузнецкая»</p>
            </div>
            <div class="lt-footer__col">
                <h3 class="lt-footer__h">Часы работы</h3>
                <p class="lt-footer__text">Ежедневно<br>с 10:00 до 20:00</p>
                <p class="lt-footer__note">Последняя запись на 19:00</p>
            </div>
            <div class="lt-footer__col">
                <h3 class="lt-footer__h">Связь</h3>
                <p class="lt-footer__text">
                    <a class="lt-footer__link" href="tel:+74951234567">+7 (495) 123-45-67</a><br>
                    <a class="lt-footer__link" href="mailto:hello@lak-tochka.example">hello@lak-tochka.example</a>
                </p>
                <ul class="lt-social" aria-label="Мы в соцсетях">
                    <li><a class="lt-social__link" href="https://t.me/" rel="noopener" aria-label="Telegram">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.7 4.3 3.4 11c-.9.4-.9 1 0 1.3l4.4 1.4 1.7 5.2c.2.6.4.8.9.8.4 0 .6-.2.9-.4l2.1-2 4.4 3.2c.8.4 1.4.2 1.6-.7l2.9-13.6c.3-1.1-.4-1.6-1.2-1.3ZM9 13.6l8.3-5.2c.4-.3.8-.1.5.2l-6.9 6.2-.3 3-1.6-4.2Z"/></svg></a></li>
                    <li><a class="lt-social__link" href="https://vk.com/" rel="noopener" aria-label="ВКонтакте">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 18.5C6.8 18.5 3.2 14.3 3 7.5h3c.1 5 2.4 7.1 4.2 7.6V7.5h2.9v4.3c1.7-.2 3.5-2.1 4.1-4.3h2.8c-.5 2.7-2.4 4.6-3.7 5.4 1.3.7 3.5 2.3 4.3 5.6h-3.1c-.7-2.1-2.3-3.7-4.4-3.9v3.9H13Z"/></svg></a></li>
                    <li><a class="lt-social__link" href="https://www.instagram.com/" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="7" r="1.1" fill="currentColor"/></svg></a></li>
                </ul>
            </div>
        </div>

        <details class="lt-privacy" id="privacy" data-privacy>
            <summary>Политика обработки персональных данных</summary>
            <div class="lt-privacy__body">
                <p>Отправляя заявку, вы даёте согласие студии «Лак&Точка» на обработку имени и номера телефона. Мы используем их только для подтверждения записи и связи по ней.</p>
                <p>Данные хранятся до отмены записи или не дольше 12 месяцев, не передаются третьим лицам и удаляются по вашему запросу на hello@lak-tochka.example. Время вашего согласия фиксируется вместе с заявкой.</p>
            </div>
        </details>

        <div class="lt-footer__bottom">
            <p>© <?= date('Y') ?> Лак&amp;Точка. Студия маникюра и педикюра.</p>
            <p><a class="lt-footer__link" href="#top" data-to-top>Наверх</a></p>
        </div>
    </div>
</footer>
</body>
</html>
