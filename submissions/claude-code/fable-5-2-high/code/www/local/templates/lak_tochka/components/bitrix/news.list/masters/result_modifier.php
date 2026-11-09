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
		$item['PICTURE'] = CFile::ResizeImageGet(
			$item['PREVIEW_PICTURE']['ID'],
			['width' => 600, 'height' => 750],
			BX_RESIZE_IMAGE_EXACT,
			true
		);
	}

	$item['SPECIALIZATION'] = (string)($item['PROPERTIES']['SPECIALIZATION']['VALUE'] ?? '');
	$item['EXPERIENCE'] = (string)($item['PROPERTIES']['EXPERIENCE']['VALUE'] ?? '');
	// «Алина Соколова» -> «Алина»: для подписи кнопки и alt.
	$item['FIRST_NAME'] = explode(' ', trim($item['NAME']))[0];
}
unset($item);
