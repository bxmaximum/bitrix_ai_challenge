<?php

/**
 * Страница настроек модуля «Избранное»
 *
 * Доступна в админке: Настройки > Настройки продукта > Модули > Избранное
 */

declare(strict_types=1);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/** @var string $mid Идентификатор модуля, передаётся из ядра */
$moduleId = 'vendor.favorites';

/** @var CMain $APPLICATION */
global $APPLICATION;

// Проверка прав доступа
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

if (!$APPLICATION->GetGroupRight($moduleId) >= 'S') {
    $APPLICATION->AuthForm('Доступ запрещён');
}

// Получение списка инфоблоков
$iblockList = [];
if (Loader::includeModule('iblock')) {
    $result = \Bitrix\Iblock\IblockTable::getList([
        'select' => ['ID', 'NAME', 'IBLOCK_TYPE_ID'],
        'order' => ['IBLOCK_TYPE_ID' => 'ASC', 'NAME' => 'ASC'],
    ]);

    while ($row = $result->fetch()) {
        $iblockList[$row['ID']] = '[' . $row['IBLOCK_TYPE_ID'] . '] ' . $row['NAME'] . ' (ID: ' . $row['ID'] . ')';
    }
}

// Текущие значения
$currentEnabled = Option::get($moduleId, 'enabled', 'Y');
$currentIblockId = Option::get($moduleId, 'catalog_iblock_id', '0');
$currentCookieTtl = Option::get($moduleId, 'cookie_ttl', '2592000');

// Обработка формы
if ($request->isPost() && $request->getPost('save') !== null && check_bitrix_sessid()) {
    $enabled = $request->getPost('enabled') === 'Y' ? 'Y' : 'N';
    $iblockId = (int)$request->getPost('catalog_iblock_id');
    $cookieTtl = max(0, (int)$request->getPost('cookie_ttl'));

    Option::set($moduleId, 'enabled', $enabled);
    Option::set($moduleId, 'catalog_iblock_id', (string)$iblockId);
    Option::set($moduleId, 'cookie_ttl', (string)$cookieTtl);

    LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($moduleId) . '&lang=' . LANGUAGE_ID . '&save=Y');
}

// Вывод сообщения об успешном сохранении
if ($request->get('save') === 'Y') {
    CAdminMessage::ShowMessage([
        'MESSAGE' => 'Настройки сохранены',
        'TYPE' => 'OK',
    ]);
}

// Рендеринг формы
$tabControl = new CAdminTabControl('tabControl', [
    [
        'DIV' => 'general',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_GENERAL'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_GENERAL_TITLE'),
    ],
]);

$tabControl->Begin();
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">
            <label for="enabled"><?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_ENABLED') ?>:</label>
        </td>
        <td width="60%">
            <input type="checkbox"
                   name="enabled"
                   id="enabled"
                   value="Y"
                <?= $currentEnabled === 'Y' ? ' checked' : '' ?>
            >
        </td>
    </tr>

    <tr>
        <td>
            <label for="catalog_iblock_id"><?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_CATALOG_IBLOCK_ID') ?>:</label>
        </td>
        <td>
            <select name="catalog_iblock_id" id="catalog_iblock_id">
                <option value="0"><?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_CHOOSE_IBLOCK') ?></option>
                <?php foreach ($iblockList as $id => $name): ?>
                    <option value="<?= $id ?>"<?= (int)$currentIblockId === $id ? ' selected' : '' ?>>
                        <?= htmlspecialcharsbx($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>

    <tr>
        <td>
            <label for="cookie_ttl"><?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_COOKIE_TTL') ?>:</label>
        </td>
        <td>
            <input type="number"
                   name="cookie_ttl"
                   id="cookie_ttl"
                   value="<?= (int)$currentCookieTtl ?>"
                   min="0"
                   style="width: 200px;"
            >
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit"
           name="save"
           value="<?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_SAVE') ?>"
           class="adm-btn-save"
    >

    <?php $tabControl->End(); ?>
</form>
