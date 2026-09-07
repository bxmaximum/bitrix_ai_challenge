<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>
</main>

<footer class="site-footer" id="contacts">
	<div class="site-footer__inner">
		<div class="site-footer__top">
			<a class="logo logo--footer" href="/" aria-label="Лак&amp;Точка — на главную">
				<span class="logo__mark" aria-hidden="true">Л&amp;Т</span>
				<span class="logo__text">
					<span class="logo__name">Лак&amp;Точка</span>
					<span class="logo__sub">студия маникюра</span>
				</span>
			</a>

			<nav class="site-footer__nav" aria-label="Навигация в подвале">
				<a href="#services">Услуги</a>
				<a href="#works">Работы</a>
				<a href="#masters">Мастера</a>
				<a href="#reviews">Отзывы</a>
				<a href="#booking">Записаться</a>
			</nav>
		</div>

		<div class="site-footer__contacts">
			<div class="contact-card">
				<span class="contact-card__label">Адрес</span>
				<p class="contact-card__value">Москва, Комсомольский проспект, 14/1<br>вход со двора, 2 этаж</p>
			</div>
			<div class="contact-card">
				<span class="contact-card__label">Телефон</span>
				<p class="contact-card__value"><a href="tel:+74951234567">+7 495 123-45-67</a></p>
			</div>
			<div class="contact-card">
				<span class="contact-card__label">Часы работы</span>
				<p class="contact-card__value">Ежедневно, 10:00–20:00<br>последняя запись в 19:00</p>
			</div>
			<div class="contact-card">
				<span class="contact-card__label">Почта</span>
				<p class="contact-card__value"><a href="mailto:hello@lakitochka.ru">hello@lakitochka.ru</a></p>
			</div>
		</div>

		<div class="site-footer__bottom">
			<p>© <?= date('Y') ?> Студия маникюра «Лак&amp;Точка»</p>
			<p><a href="/privacy/">Политика обработки персональных данных</a></p>
		</div>
	</div>
</footer>
</body>
</html>
