<?php

declare(strict_types=1);

use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * Страница настроек модуля "Избранное"
 *
 * Позволяет настроить:
 * - Включение/выключение модуля
 * - ID инфоблока каталога товаров
 * - Время жизни cookie для гостей
 */

$moduleId = 'vendor.favorites';

// Проверка прав доступа
global $APPLICATION, $USER;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

// Получаем список инфоблоков для выбора
$iblockList = [];
if (Loader::includeModule('iblock')) {
    $result = \Bitrix\Iblock\IblockTable::getList([
        'select' => ['ID', 'NAME', 'IBLOCK_TYPE_ID'],
        'order' => ['IBLOCK_TYPE_ID' => 'ASC', 'NAME' => 'ASC'],
    ]);
    while ($row = $result->fetch()) {
        $iblockList[$row['ID']] = sprintf(
            '[%d] %s (%s)',
            $row['ID'],
            $row['NAME'],
            $row['IBLOCK_TYPE_ID']
        );
    }
}

// Описание полей настроек
$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_GENERAL'),
        'TITLE' => Loc::getMessage('VENDOR_FAVORITES_OPTIONS_TAB_GENERAL_TITLE'),
        'OPTIONS' => [
            [
                'enabled',
                Loc::getMessage('VENDOR_FAVORITES_OPTIONS_ENABLED'),
                'Y',
                ['checkbox'],
            ],
            [
                'iblock_id',
                Loc::getMessage('VENDOR_FAVORITES_OPTIONS_IBLOCK_ID'),
                '',
                ['selectbox', $iblockList],
            ],
            [
                'cookie_lifetime',
                Loc::getMessage('VENDOR_FAVORITES_OPTIONS_COOKIE_LIFETIME'),
                '30',
                ['text', 10],
            ],
        ],
    ],
];

// Обработка сохранения
$request = HttpApplication::getInstance()->getContext()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    if ($request->getPost('save') !== null || $request->getPost('apply') !== null) {
        foreach ($aTabs as $tab) {
            foreach ($tab['OPTIONS'] as $option) {
                if (!is_array($option) || count($option) < 4) {
                    continue;
                }

                $optionName = $option[0];
                $optionType = $option[3][0];
                $value = $request->getPost($optionName);

                if ($optionType === 'checkbox') {
                    $value = $value === 'Y' ? 'Y' : 'N';
                }

                Option::set($moduleId, $optionName, $value ?? '');
            }
        }

        if ($request->getPost('apply') !== null) {
            LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($moduleId) . '&lang=' . LANGUAGE_ID . '&' . bitrix_sessid_get());
        }
    }

    if ($request->getPost('restore') !== null) {
        Option::delete($moduleId);
    }
}

// Отображение формы настроек
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($moduleId) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->Begin(); ?>

    <?php foreach ($aTabs as $tab): ?>
        <?php $tabControl->BeginNextTab(); ?>

        <?php foreach ($tab['OPTIONS'] as $option): ?>
            <?php if (!is_array($option) || count($option) < 4): ?>
                <?php continue; ?>
            <?php endif; ?>

            <?php
            $optionName = $option[0];
            $optionLabel = $option[1];
            $optionDefault = $option[2];
            $optionType = $option[3];
            $currentValue = Option::get($moduleId, $optionName, $optionDefault);
            ?>

            <tr>
                <td width="40%">
                    <label for="<?= htmlspecialcharsbx($optionName) ?>">
                        <?= htmlspecialcharsbx($optionLabel) ?>:
                    </label>
                </td>
                <td width="60%">
                    <?php if ($optionType[0] === 'checkbox'): ?>
                        <input
                            type="checkbox"
                            name="<?= htmlspecialcharsbx($optionName) ?>"
                            id="<?= htmlspecialcharsbx($optionName) ?>"
                            value="Y"
                            <?= $currentValue === 'Y' ? 'checked' : '' ?>
                        >
                    <?php elseif ($optionType[0] === 'text'): ?>
                        <input
                            type="text"
                            name="<?= htmlspecialcharsbx($optionName) ?>"
                            id="<?= htmlspecialcharsbx($optionName) ?>"
                            value="<?= htmlspecialcharsbx($currentValue) ?>"
                            size="<?= (int)($optionType[1] ?? 30) ?>"
                        >
                    <?php elseif ($optionType[0] === 'selectbox'): ?>
                        <select
                            name="<?= htmlspecialcharsbx($optionName) ?>"
                            id="<?= htmlspecialcharsbx($optionName) ?>"
                        >
                            <option value="">-- Не выбрано --</option>
                            <?php foreach ($optionType[1] as $key => $value): ?>
                                <option
                                    value="<?= htmlspecialcharsbx((string)$key) ?>"
                                    <?= (string)$currentValue === (string)$key ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialcharsbx($value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php $tabControl->Buttons(); ?>

    <input
        type="submit"
        name="save"
        value="<?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_SAVE') ?>"
        class="adm-btn-save"
    >
    <input
        type="submit"
        name="apply"
        value="<?= Loc::getMessage('MAIN_OPT_APPLY') ?: 'Применить' ?>"
    >
    <input
        type="submit"
        name="restore"
        value="<?= Loc::getMessage('VENDOR_FAVORITES_OPTIONS_RESET') ?>"
        onclick="return confirm('Сбросить настройки?')"
    >

    <?php $tabControl->End(); ?>
</form>





