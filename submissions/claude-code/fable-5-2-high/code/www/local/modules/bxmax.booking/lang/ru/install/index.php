<?php

$MESS['BXMAX_BOOKING_MODULE_NAME'] = 'Онлайн-запись (BXMax)';
$MESS['BXMAX_BOOKING_MODULE_DESCRIPTION'] = 'Расписание мастеров, слоты и онлайн-запись клиентов студии.';
$MESS['BXMAX_BOOKING_INSTALL_TITLE'] = 'Установка модуля «Онлайн-запись»';
$MESS['BXMAX_BOOKING_UNINSTALL_TITLE'] = 'Удаление модуля «Онлайн-запись»';
$MESS['BXMAX_BOOKING_RIGHT_D'] = 'доступ закрыт';
$MESS['BXMAX_BOOKING_RIGHT_R'] = 'просмотр заявок и слотов';
$MESS['BXMAX_BOOKING_RIGHT_W'] = 'управление заявками и слотами';
$MESS['BXMAX_BOOKING_MAIL_TYPE_NAME'] = 'Новая онлайн-запись';
$MESS['BXMAX_BOOKING_MAIL_TYPE_DESCRIPTION'] = "#EMAIL_TO# — e-mail администратора студии\n#ENTRY_ID# — номер заявки\n#MASTER_NAME# — мастер\n#SERVICE_NAME# — услуга\n#SLOT_DATE# — дата визита\n#SLOT_TIME# — время визита\n#NAME# — имя клиента\n#PHONE# — телефон клиента\n#CONSENT_AT# — когда клиент дал согласие на обработку персональных данных\n#ADMIN_URL# — ссылка на список заявок в админке";
$MESS['BXMAX_BOOKING_MAIL_SUBJECT'] = '#SITE_NAME#: новая запись на #SLOT_DATE# в #SLOT_TIME#';
$MESS['BXMAX_BOOKING_MAIL_BODY'] = '<p>На сайте #SITE_NAME# оставлена новая заявка №#ENTRY_ID#.</p>
<table cellpadding="6" cellspacing="0" border="0">
<tr><td>Мастер</td><td><b>#HTML_MASTER_NAME#</b></td></tr>
<tr><td>Услуга</td><td><b>#HTML_SERVICE_NAME#</b></td></tr>
<tr><td>Дата и время</td><td><b>#SLOT_DATE#, #SLOT_TIME#</b></td></tr>
<tr><td>Клиент</td><td><b>#HTML_NAME#</b></td></tr>
<tr><td>Телефон</td><td><b>#HTML_PHONE#</b></td></tr>
<tr><td>Согласие на обработку ПДн</td><td>#CONSENT_AT#</td></tr>
</table>
<p><a href="#ADMIN_URL#">Открыть список заявок</a></p>';
