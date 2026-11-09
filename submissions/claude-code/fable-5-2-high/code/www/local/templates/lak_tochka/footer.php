<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CMain $APPLICATION */

$home = $APPLICATION->GetCurPage(false) === SITE_DIR ? '' : SITE_DIR;
?>
</main>

<footer class="footer" id="contacts">
	<div class="container">
		<div class="footer__top">
			<a class="logo logo--footer" href="<?= SITE_DIR ?>" aria-label="Лак&amp;Точка — на главную">
				<span class="logo__name">Лак<span class="logo__amp">&amp;</span>Точка</span>
				<span class="logo__caption">студия маникюра</span>
			</a>
			<nav aria-label="Меню в подвале">
				<ul class="footer__nav">
					<li><a class="nav__link" href="<?= $home ?>#about">О студии</a></li>
					<li><a class="nav__link" href="<?= $home ?>#services">Услуги</a></li>
					<li><a class="nav__link" href="<?= $home ?>#gallery">Работы</a></li>
					<li><a class="nav__link" href="<?= $home ?>#masters">Мастера</a></li>
					<li><a class="nav__link" href="<?= $home ?>#booking">Запись</a></li>
				</ul>
			</nav>
		</div>

		<h2 class="visually-hidden">Контакты</h2>
		<?php $APPLICATION->IncludeComponent(
			'bitrix:main.include',
			'',
			['AREA_FILE_SHOW' => 'file', 'PATH' => SITE_TEMPLATE_PATH . '/include/contacts.php', 'EDIT_TEMPLATE' => ''],
			false,
			['HIDE_ICONS' => 'N']
		) ?>

		<div class="footer__bottom">
			<ul class="footer__social">
				<li><a class="nav__link" href="https://t.me/lakitochka" target="_blank" rel="noopener nofollow">Telegram</a></li>
				<li><a class="nav__link" href="https://vk.com/lakitochka" target="_blank" rel="noopener nofollow">ВКонтакте</a></li>
				<li><a class="nav__link" href="<?= SITE_DIR ?>privacy/">Политика обработки данных</a></li>
			</ul>
			<p class="footer__copy">© <?= date('Y') ?> Студия маникюра «Лак&amp;Точка»</p>
		</div>
	</div>
</footer>

</body>
</html>
