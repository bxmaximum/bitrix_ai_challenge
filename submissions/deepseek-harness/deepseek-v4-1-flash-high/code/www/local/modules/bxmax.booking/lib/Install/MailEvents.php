<?php

namespace Bxmax\Booking\Install;

use Bitrix\Main\Mail\Internal\EventMessageTable;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Bxmax\Booking\Notify\AdminNotifier;

/**
 * Registers (and removes) the mail event of the module.
 *
 * The administrator notification is a regular Bitrix mail event, so the studio
 * can edit the subject and the body of the letter in the admin panel.
 */
final class MailEvents
{
	public const DEFAULT_SITE_ID = 's1';
	public const LANG_ID = 'ru';

	private const EVENT_FIELDS = [
		['NAME' => 'ADMIN_EMAIL', 'TYPE' => 'string'],
		['NAME' => 'BOOKING_ID', 'TYPE' => 'integer'],
		['NAME' => 'MASTER_NAME', 'TYPE' => 'string'],
		['NAME' => 'SERVICE_NAME', 'TYPE' => 'string'],
		['NAME' => 'SLOT_DATE', 'TYPE' => 'string'],
		['NAME' => 'SLOT_TIME', 'TYPE' => 'string'],
		['NAME' => 'CLIENT_NAME', 'TYPE' => 'string'],
		['NAME' => 'CLIENT_PHONE', 'TYPE' => 'string'],
		['NAME' => 'CONSENT_AT', 'TYPE' => 'string'],
	];

	public static function install(string $siteId = self::DEFAULT_SITE_ID): void
	{
		if (!self::isTypeRegistered())
		{
			(new \CEventType())->Add([
				'EVENT_NAME' => AdminNotifier::EVENT_NAME,
				'LID' => self::LANG_ID,
				'EVENT_TYPE' => 'email',
				'NAME' => 'Новая запись в студию «Лак&Точка»',
				'DESCRIPTION' => 'Уведомление администратору студии о новой онлайн-записи',
				'SORT' => 100,
				'FIELDS' => self::EVENT_FIELDS,
			]);
		}

		if (!self::isMessageRegistered($siteId))
		{
			(new \CEventMessage())->Add([
				'ACTIVE' => 'Y',
				'EVENT_NAME' => AdminNotifier::EVENT_NAME,
				'LID' => [$siteId],
				'LANGUAGE_ID' => self::LANG_ID,
				'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO' => '#ADMIN_EMAIL#',
				'SUBJECT' => 'Новая запись: #SERVICE_NAME# — #SLOT_DATE# в #SLOT_TIME#',
				'BODY_TYPE' => 'html',
				'MESSAGE' => self::messageBody(),
			]);
		}
	}

	public static function uninstall(): void
	{
		$messages = EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => ['=EVENT_NAME' => AdminNotifier::EVENT_NAME],
		]);

		while ($row = $messages->fetch())
		{
			\CEventMessage::Delete((int)$row['ID']);
		}

		// CEventType::Delete умеет удалять тип события сразу для всех языков
		\CEventType::Delete(['EVENT_NAME' => AdminNotifier::EVENT_NAME]);
	}

	private static function isTypeRegistered(): bool
	{
		return (bool)EventTypeTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=EVENT_NAME' => AdminNotifier::EVENT_NAME,
				'=LID' => self::LANG_ID,
			],
			'limit' => 1,
		])->fetch();
	}

	private static function isMessageRegistered(string $siteId): bool
	{
		$row = EventMessageTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=EVENT_NAME' => AdminNotifier::EVENT_NAME,
				'=EVENT_MESSAGE_SITE.SITE_ID' => $siteId,
			],
			'limit' => 1,
		])->fetch();

		return (bool)$row;
	}

	private static function messageBody(): string
	{
		return <<<HTML
<p>В студии новая запись через сайт.</p>
<table cellpadding="4" cellspacing="0" border="0">
	<tr><td><b>Услуга:</b></td><td>#SERVICE_NAME#</td></tr>
	<tr><td><b>Мастер:</b></td><td>#MASTER_NAME#</td></tr>
	<tr><td><b>Дата:</b></td><td>#SLOT_DATE#</td></tr>
	<tr><td><b>Время:</b></td><td>#SLOT_TIME#</td></tr>
	<tr><td><b>Имя клиента:</b></td><td>#CLIENT_NAME#</td></tr>
	<tr><td><b>Телефон:</b></td><td>#CLIENT_PHONE#</td></tr>
	<tr><td><b>Согласие на обработку данных:</b></td><td>#CONSENT_AT#</td></tr>
	<tr><td><b>Номер заявки:</b></td><td>#BOOKING_ID#</td></tr>
</table>
HTML;
	}
}
