<?php

$MESS['BXMAX_BOOKING_MODULE_NAME'] = 'Онлайн-запись (Лак&Точка)';
$MESS['BXMAX_BOOKING_MODULE_DESCRIPTION'] = 'Расписание мастеров, онлайн-запись на услуги и заявки клиентов.';
$MESS['BXMAX_BOOKING_INSTALL_TITLE'] = 'Установка модуля «Онлайн-запись»';
$MESS['BXMAX_BOOKING_UNINSTALL_TITLE'] = 'Удаление модуля «Онлайн-запись»';
$MESS['BXMAX_BOOKING_RIGHT_DENIED'] = 'доступ закрыт';
$MESS['BXMAX_BOOKING_RIGHT_READ'] = 'просмотр заявок и расписания';
$MESS['BXMAX_BOOKING_RIGHT_WRITE'] = 'управление заявками и слотами';
$MESS['BXMAX_BOOKING_EVENT_NAME'] = 'Онлайн-запись: новая заявка';
$MESS['BXMAX_BOOKING_EVENT_DESCRIPTION'] = '#BOOKING_ID# — номер заявки
#ADMIN_EMAIL# — e-mail администратора студии
#SERVICE_NAME# — услуга
#SERVICE_PRICE# — цена услуги, ₽
#MASTER_NAME# — мастер
#SLOT_DATE# — дата визита
#SLOT_TIME# — время визита
#NAME# — имя клиента
#PHONE# — телефон клиента
#CONSENT_AT# — дата и время согласия на обработку ПД
#ADMIN_URL# — ссылка на список заявок в админке';
$MESS['BXMAX_BOOKING_MESSAGE_SUBJECT'] = '#SITE_NAME#: новая запись на #SLOT_DATE# #SLOT_TIME#';
$MESS['BXMAX_BOOKING_MESSAGE_BODY'] = 'Новая онлайн-запись №#BOOKING_ID#

Услуга: #SERVICE_NAME# (#SERVICE_PRICE# ₽)
Мастер: #MASTER_NAME#
Когда: #SLOT_DATE#, #SLOT_TIME#

Клиент: #NAME#
Телефон: #PHONE#
Согласие на обработку персональных данных: #CONSENT_AT#

Все заявки: #SERVER_NAME##ADMIN_URL#';
$MESS['BXMAX_BOOKING_UNINSTALL_WARN'] = 'Внимание! Модуль будет удалён из системы.';
$MESS['BXMAX_BOOKING_UNINSTALL_SAVE'] = 'Сохранить таблицы с заявками и расписанием';
$MESS['BXMAX_BOOKING_UNINSTALL_DEL'] = 'Удалить';
$MESS['BXMAX_BOOKING_INSTALL_DONE'] = 'Модуль установлен. Расписание мастеров на 14 дней сгенерировано.';
$MESS['BXMAX_BOOKING_UNINSTALL_DONE'] = 'Модуль удалён.';
$MESS['BXMAX_BOOKING_BACK'] = 'Вернуться в список модулей';
