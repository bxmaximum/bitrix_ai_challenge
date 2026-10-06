<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;
use Bxmax\Booking\Service\ScheduleService;

/**
 * Развёртывание лендинга «Лак&Точка» на чистой копии площадки.
 *
 * Скрипт идемпотентен: его можно запускать сколько угодно раз. Он создаёт
 * тип инфоблоков, инфоблоки services/masters и их наполнение, устанавливает
 * модуль bxmax.booking, генерирует расписание слотов на 14 дней вперёд и
 * назначает сайту шаблон лендинга.
 *
 * Запускается автоматически из local/php_interface/init.php при первом
 * запросе после копирования площадки; версия развёртывания хранится в опции
 * bxmax.booking/deploy_revision, поэтому повторные запросы ничего не делают.
 * Принудительный перезапуск: php local/tools/deploy.php --force
 */

const BXMAX_DEPLOY_REVISION = 7;
const BXMAX_DEPLOY_OPTION = 'deploy_revision';

const BXMAX_TEMPLATE_ID = 'laktochka';

/**
 * Точка входа для init.php: дешёвая проверка + защита от исключений.
 */
function bxmaxDeployMaybeRun(): void
{
	if (defined('BXMAX_DEPLOY_DISABLED'))
	{
		return;
	}

	try
	{
		if (bxmaxDeployIsDone())
		{
			return;
		}

		bxmaxDeployRun();
	}
	catch (\Throwable $exception)
	{
		// Сломанное развёртывание не должно ронять сайт: пишем в лог и живём дальше.
		error_log('[bxmax.deploy] ' . $exception->getMessage() . ' @ ' . $exception->getFile() . ':' . $exception->getLine());
	}
}

function bxmaxDeployIsDone(): bool
{
	return Option::get('bxmax.booking', BXMAX_DEPLOY_OPTION, '') === (string)BXMAX_DEPLOY_REVISION;
}

function bxmaxDeployRun(): void
{
	$content = require __DIR__ . '/bxmax_content.php';

	$siteId = bxmaxDeploySiteId();

	bxmaxDeployEnsureIblockType($content['iblock_type']);

	$servicesId = bxmaxDeployEnsureIblock('services', 'Услуги', 100, [
		['CODE' => 'PRICE', 'NAME' => 'Цена, руб.', 'PROPERTY_TYPE' => 'N'],
		['CODE' => 'DURATION', 'NAME' => 'Длительность, мин', 'PROPERTY_TYPE' => 'N'],
		['CODE' => 'DESCRIPTION', 'NAME' => 'Короткое описание', 'PROPERTY_TYPE' => 'S', 'ROW_COUNT' => 3, 'COL_COUNT' => 60],
		['CODE' => 'IMAGE', 'NAME' => 'Фотография услуги', 'PROPERTY_TYPE' => 'F', 'FILE_TYPE' => 'jpg, jpeg, png, webp'],
	], $siteId);

	$mastersId = bxmaxDeployEnsureIblock('masters', 'Мастера', 200, [
		['CODE' => 'SPECIALIZATION', 'NAME' => 'Специализация', 'PROPERTY_TYPE' => 'S'],
		['CODE' => 'EXPERIENCE', 'NAME' => 'Опыт, лет', 'PROPERTY_TYPE' => 'N'],
		['CODE' => 'ABOUT', 'NAME' => 'О мастере', 'PROPERTY_TYPE' => 'S', 'ROW_COUNT' => 3, 'COL_COUNT' => 60],
		['CODE' => 'PHOTO', 'NAME' => 'Фотография мастера', 'PROPERTY_TYPE' => 'F', 'FILE_TYPE' => 'jpg, jpeg, png, webp'],
	], $siteId);

	bxmaxDeploySyncServices($servicesId, $content['services']);
	bxmaxDeploySyncMasters($mastersId, $content['masters']);

	bxmaxDeployEnsureModule();
	bxmaxDeployEnsureSchedule();
	bxmaxDeployEnsureSiteTemplate($siteId, $content);

	Option::set('bxmax.booking', BXMAX_DEPLOY_OPTION, (string)BXMAX_DEPLOY_REVISION);
}

/* ------------------------------------------------------------------ инфоблоки */

function bxmaxDeployEnsureIblockType(array $type): void
{
	if (!Loader::includeModule('iblock'))
	{
		throw new \RuntimeException('Модуль iblock недоступен');
	}

	if (CIBlockType::GetByID($type['id'])->Fetch())
	{
		return;
	}

	$iblockType = new CIBlockType();
	$iblockType->Add([
		'ID' => $type['id'],
		'SECTIONS' => 'N',
		'IN_RSS' => 'N',
		'SORT' => 500,
		'LANG' => [
			'ru' => [
				'NAME' => $type['name'],
				'ELEMENT_NAME' => 'Элемент',
				'SECTION_NAME' => 'Раздел',
			],
			'en' => [
				'NAME' => $type['name'],
				'ELEMENT_NAME' => 'Element',
				'SECTION_NAME' => 'Section',
			],
		],
	]);
}

function bxmaxDeployEnsureIblock(string $code, string $name, int $sort, array $properties, string $siteId): int
{
	$row = CIBlock::GetList([], ['CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();

	$fields = [
		'ACTIVE' => 'Y',
		'NAME' => $name,
		'CODE' => $code,
		'IBLOCK_TYPE_ID' => 'bxmax_studio',
		'SITE_ID' => [$siteId],
		'SORT' => $sort,
		'VERSION' => 2,
		'GROUP_ID' => [2 => 'R'],
		'INDEX_ELEMENT' => 'N',
		'INDEX_SECTION' => 'N',
		'WORKFLOW' => 'N',
		'BIZPROC' => 'N',
		'LIST_PAGE_URL' => '',
		'DETAIL_PAGE_URL' => '',
	];

	$iblock = new CIBlock();

	if ($row)
	{
		$iblockId = (int)$row['ID'];
		$iblock->Update($iblockId, $fields);
	}
	else
	{
		$iblockId = (int)$iblock->Add($fields);
	}

	if ($iblockId <= 0)
	{
		throw new \RuntimeException('Не удалось создать инфоблок ' . $code . ': ' . $iblock->LAST_ERROR);
	}

	$property = new CIBlockProperty();
	$propertySort = 100;

	foreach ($properties as $propertyFields)
	{
		$exists = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyFields['CODE']])->Fetch();
		if ($exists)
		{
			continue;
		}

		$property->Add($propertyFields + [
			'IBLOCK_ID' => $iblockId,
			'ACTIVE' => 'Y',
			'SORT' => $propertySort += 100,
			'MULTIPLE' => 'N',
			'IS_REQUIRED' => 'N',
			'FILTRABLE' => 'N',
			'SEARCHABLE' => 'N',
		]);
	}

	return $iblockId;
}

function bxmaxDeploySyncServices(int $iblockId, array $services): void
{
	$sort = 100;

	foreach ($services as $service)
	{
		bxmaxDeploySyncElement($iblockId, $service['code'], [
			'NAME' => $service['name'],
			'SORT' => $sort += 100,
			'PREVIEW_TEXT' => $service['description'],
			'PROPERTY_VALUES' => [
				'PRICE' => $service['price'],
				'DURATION' => $service['duration'],
				'DESCRIPTION' => $service['description'],
			],
		], ['IMAGE' => $service['image']]);
	}
}

function bxmaxDeploySyncMasters(int $iblockId, array $masters): void
{
	$sort = 100;

	foreach ($masters as $master)
	{
		bxmaxDeploySyncElement($iblockId, $master['code'], [
			'NAME' => $master['name'],
			'SORT' => $sort += 100,
			'PREVIEW_TEXT' => $master['about'],
			'PROPERTY_VALUES' => [
				'SPECIALIZATION' => $master['specialization'],
				'EXPERIENCE' => $master['experience'],
				'ABOUT' => $master['about'],
			],
		], ['PHOTO' => $master['image']]);
	}
}

/**
 * Создаёт или обновляет элемент инфоблока. Картинки перезаписываются только
 * если у элемента их ещё нет, чтобы повторный запуск не плодил файлы.
 */
function bxmaxDeploySyncElement(int $iblockId, string $code, array $fields, array $images): void
{
	$existing = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code], false, false, ['ID', 'PREVIEW_PICTURE'])
		->Fetch();

	$fields['IBLOCK_ID'] = $iblockId;
	$fields['CODE'] = $code;
	$fields['ACTIVE'] = 'Y';
	$fields['PREVIEW_TEXT_TYPE'] = 'text';
	$fields['CREATED_BY'] = 1;
	$fields['MODIFIED_BY'] = 1;

	$propertyValues = $fields['PROPERTY_VALUES'] ?? [];
	unset($fields['PROPERTY_VALUES']);

	$imageFiles = [];
	foreach ($images as $propertyCode => $fileName)
	{
		if ($fileName === '')
		{
			continue;
		}

		$path = bxmaxDeployImagePath($fileName);
		if ($path === null)
		{
			continue;
		}

		$hasImage = $existing && bxmaxDeployHasImage($iblockId, (int)$existing['ID'], $propertyCode);
		if (!$hasImage)
		{
			$imageFiles[$propertyCode] = $path;
		}
	}

	foreach ($imageFiles as $propertyCode => $path)
	{
		$propertyValues[$propertyCode] = ['n0' => CFile::MakeFileArray($path)];
	}

	if ($propertyValues !== [])
	{
		$fields['PROPERTY_VALUES'] = $propertyValues;
	}

	$element = new CIBlockElement();

	if ($existing)
	{
		$element->Update((int)$existing['ID'], $fields);

		return;
	}

	$elementId = (int)$element->Add($fields);

	if ($elementId <= 0)
	{
		throw new \RuntimeException('Не удалось создать элемент ' . $code . ': ' . $element->LAST_ERROR);
	}
}

function bxmaxDeployHasImage(int $iblockId, int $elementId, string $propertyCode): bool
{
	$value = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => $propertyCode])->Fetch();

	return !empty($value['VALUE']);
}

function bxmaxDeployImagePath(string $fileName): ?string
{
	$path = dirname(__DIR__) . '/templates/' . BXMAX_TEMPLATE_ID . '/assets/img/' . $fileName;

	return is_file($path) ? $path : null;
}

/* --------------------------------------------------------------------- модуль */

function bxmaxDeployEnsureModule(): void
{
	if (!ModuleManager::isModuleInstalled('bxmax.booking'))
	{
		$module = CModule::CreateModuleObject('bxmax.booking');
		if (!is_object($module))
		{
			throw new \RuntimeException('Модуль bxmax.booking не найден в local/modules');
		}

		$module->DoInstall();
	}

	// Файлы публичной части и админки модуля держим в актуальном состоянии:
	// они лежат в модуле и раскладываются по площадке при каждом развёртывании.
	$modulePath = dirname(__DIR__) . '/modules/bxmax.booking/install';

	CopyDirFiles($modulePath . '/components', $_SERVER['DOCUMENT_ROOT'] . '/local/components', true, true);
	CopyDirFiles($modulePath . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);

	Loader::includeModule('bxmax.booking');
}

function bxmaxDeployEnsureSchedule(): void
{
	$schedule = ServiceLocator::getInstance()->get(ScheduleService::class);

	$schedule->generateHorizon(new DateTime());
}

/* ----------------------------------------------------------------------- сайт */

function bxmaxDeployEnsureSiteTemplate(string $siteId, array $content): void
{
	$templates = CSite::GetTemplateList($siteId);
	$hasTemplate = false;
	$list = [];

	foreach ($templates as $template)
	{
		if (($template['TEMPLATE'] ?? '') === BXMAX_TEMPLATE_ID)
		{
			$hasTemplate = true;
		}

		$list[] = [
			'TEMPLATE' => $template['TEMPLATE'],
			'CONDITION' => $template['CONDITION'],
			'SORT' => $template['SORT'],
		];
	}

	$fields = [
		'SITE_NAME' => $content['studio']['name'] . ' — ' . $content['studio']['tagline'],
		'EMAIL' => $content['studio']['email'],
	];

	if (!$hasTemplate)
	{
		array_unshift($list, ['TEMPLATE' => BXMAX_TEMPLATE_ID, 'CONDITION' => '', 'SORT' => 1]);
		$fields['TEMPLATE'] = $list;
	}

	$site = new CSite();
	if (!$site->Update($siteId, $fields))
	{
		throw new \RuntimeException('Не удалось назначить шаблон сайта: ' . $site->LAST_ERROR);
	}
}

function bxmaxDeploySiteId(): string
{
	if (defined('SITE_ID') && SITE_ID !== '')
	{
		return SITE_ID;
	}

	$row = \Bitrix\Main\SiteTable::getList([
		'select' => ['LID'],
		'filter' => ['=ACTIVE' => 'Y'],
		'order' => ['DEF' => 'DESC', 'SORT' => 'ASC'],
		'limit' => 1,
	])->fetch();

	return $row ? (string)$row['LID'] : 's1';
}
