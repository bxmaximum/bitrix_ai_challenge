<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * Мастера из инфоблока masters: нумерованный список-вкладки и фото выбранного мастера.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

$arrow = '<span class="link-arrow__circle" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg></span>';
$yearsWord = static function (int $n): string {
    $mod10 = $n % 10;
    $mod100 = $n % 100;
    if ($mod10 === 1 && $mod100 !== 11)
    {
        return 'год';
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14))
    {
        return 'года';
    }

    return 'лет';
};
$count = count($arResult['ITEMS']);
$countWords = [3 => 'Три', 4 => 'Четыре', 5 => 'Пять', 6 => 'Шесть'];
?>
<section class="masters container" id="masters" aria-labelledby="masters-title">
    <h2 class="label section-label" id="masters-title">Мастера</h2>
    <p class="masters__intro display-l" data-reveal>
        <?= $countWords[$count] ?? $count ?> мастера — <em><?= mb_strtolower($countWords[$count] ?? (string)$count) ?> почерка</em>. Выбирайте по душе: работаем по одному стандарту стерильности и никогда не торопимся.
    </p>

    <div class="masters__grid" data-tabs>
        <div class="masters__list" role="tablist" aria-orientation="vertical" aria-label="Мастера студии">
            <?php foreach ($arResult['ITEMS'] as $index => $item): ?>
                <button class="master-tab<?= $index === 0 ? ' is-active' : '' ?>" type="button" role="tab"
                        id="master-tab-<?= (int)$item['ID'] ?>"
                        aria-controls="master-panel-<?= (int)$item['ID'] ?>"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        tabindex="<?= $index === 0 ? '0' : '-1' ?>">
                    <span class="master-tab__num" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                    <span class="master-tab__name"><?= htmlspecialcharsbx($item['NAME']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="masters__panels">
            <?php foreach ($arResult['ITEMS'] as $index => $item):
                $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
                $props = $item['PROPERTIES'];
                $experience = (int)$props['EXPERIENCE']['VALUE'];
                $photo = $item['PREVIEW_PICTURE'] ?: null;
                $resized = $photo ? CFile::ResizeImageGet($photo, ['width' => 1000, 'height' => 840], BX_RESIZE_IMAGE_EXACT, true) : null;
                ?>
                <div class="master-panel" role="tabpanel" id="master-panel-<?= (int)$item['ID'] ?>"
                     aria-labelledby="master-tab-<?= (int)$item['ID'] ?>" tabindex="0"<?= $index === 0 ? '' : ' hidden' ?>>
                    <div class="master-panel__inner" id="<?= $this->GetEditAreaId($item['ID']) ?>">
                        <?php if ($resized): ?>
                            <img class="master-panel__photo" src="<?= htmlspecialcharsbx($resized['src']) ?>"
                                 alt="<?= htmlspecialcharsbx($photo['DESCRIPTION'] ?: $item['NAME']) ?>"
                                 width="<?= (int)$resized['width'] ?>" height="<?= (int)$resized['height'] ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="master-panel__info">
                            <p class="master-panel__spec">
                                <?= htmlspecialcharsbx((string)$props['SPECIALIZATION']['VALUE']) ?>
                                <?php if ($experience > 0): ?>
                                    <span aria-hidden="true">•</span> <?= $experience ?>&nbsp;<?= $yearsWord($experience) ?> в профессии
                                <?php endif; ?>
                            </p>
                            <p class="master-panel__text"><?= htmlspecialcharsbx(strip_tags((string)$item['PREVIEW_TEXT'])) ?></p>
                            <a class="link-arrow" href="#booking" data-book-master="<?= (int)$item['ID'] ?>"
                               aria-label="Записаться к мастеру: <?= htmlspecialcharsbx($item['NAME']) ?>">
                                <?= $arrow ?><span class="link-arrow__text">Записаться к мастеру</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
