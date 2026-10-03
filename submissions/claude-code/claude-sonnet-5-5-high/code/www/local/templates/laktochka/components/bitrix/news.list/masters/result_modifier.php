<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var array $arResult */

foreach ($arResult['ITEMS'] as &$item)
{
    $item['LT_SPECIALIZATION'] = (string)($item['PROPERTIES']['SPECIALIZATION']['VALUE'] ?? '');
    $item['LT_EXPERIENCE'] = (int)($item['PROPERTIES']['EXPERIENCE']['VALUE'] ?? 0);

    if (!empty($item['PREVIEW_PICTURE']['ID']))
    {
        $resized = CFile::ResizeImageGet(
            $item['PREVIEW_PICTURE']['ID'],
            ['width' => 720, 'height' => 900],
            BX_RESIZE_IMAGE_EXACT,
            false,
            false,
            false,
            78
        );
        $item['LT_IMAGE'] = $resized ?: null;
    }
}
unset($item);
