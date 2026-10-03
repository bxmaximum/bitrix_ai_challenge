<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/** @var array $arResult */

foreach ($arResult['ITEMS'] as &$item)
{
    $item['LT_PRICE'] = (int)round((float)($item['PROPERTIES']['PRICE']['VALUE'] ?? 0));
    $item['LT_DURATION'] = (int)round((float)($item['PROPERTIES']['DURATION']['VALUE'] ?? 0));

    if (!empty($item['PREVIEW_PICTURE']['ID']))
    {
        $resized = CFile::ResizeImageGet(
            $item['PREVIEW_PICTURE']['ID'],
            ['width' => 800, 'height' => 1000],
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
