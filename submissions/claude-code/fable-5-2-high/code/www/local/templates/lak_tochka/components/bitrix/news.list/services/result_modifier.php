<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */

foreach ($arResult['ITEMS'] as &$item)
{
	$item['PICTURE'] = null;
	if (!empty($item['PREVIEW_PICTURE']['ID']))
	{
		// Карточка 388×545, отдаём двукратный размер под плотные экраны.
		$item['PICTURE'] = CFile::ResizeImageGet(
			$item['PREVIEW_PICTURE']['ID'],
			['width' => 776, 'height' => 1090],
			BX_RESIZE_IMAGE_EXACT,
			true
		);
	}

	$item['PRICE'] = (int)round((float)($item['PROPERTIES']['PRICE']['VALUE'] ?? 0));
	$item['DURATION'] = (int)($item['PROPERTIES']['DURATION']['VALUE'] ?? 0);
}
unset($item);
