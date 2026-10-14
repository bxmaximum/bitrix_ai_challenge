<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

/**
 * Услуги из инфоблока services: строка-оглавление и карточки с ценами.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

$arrow = '<span class="link-arrow__circle" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.2"/></svg></span>';
$formatPrice = static fn ($value): string => number_format((float)$value, 0, ',', "\u{00A0}");
?>
<section class="services" id="services" aria-labelledby="services-title">
    <div class="container">
        <h2 class="label section-label" id="services-title">Услуги и цены</h2>
        <ul class="dot-list dot-list--services" data-reveal>
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <li><a href="#service-<?= (int)$item['ID'] ?>"><?= htmlspecialcharsbx($item['NAME']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <a class="link-arrow" href="#booking" data-reveal>
            <?= $arrow ?><span class="link-arrow__text">Записаться онлайн</span>
        </a>
    </div>

    <div class="favorites">
        <p class="favorites__title" aria-hidden="true"><span>Прайс</span> <em>без сюрпризов</em></p>
        <ul class="favorites__list">
            <?php foreach ($arResult['ITEMS'] as $index => $item):
                $this->AddEditAction($item['ID'], $item['EDIT_LINK'], CIBlock::GetArrayByID($item['IBLOCK_ID'], 'ELEMENT_EDIT'));
                $props = $item['PROPERTIES'];
                $titleId = 'service-title-' . (int)$item['ID'];
                ?>
                <li class="fav-card fav-card--<?= $index % 2 ? 'right' : 'left' ?>" id="service-<?= (int)$item['ID'] ?>">
                    <article class="fav-card__inner" id="<?= $this->GetEditAreaId($item['ID']) ?>" aria-labelledby="<?= $titleId ?>">
                        <p class="fav-card__cat"><?= htmlspecialcharsbx((string)$props['CATEGORY']['VALUE']) ?></p>
                        <h3 class="fav-card__title" id="<?= $titleId ?>"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
                        <p class="fav-card__meta">
                            <?= (int)$props['DURATION']['VALUE'] ?>&nbsp;мин <span aria-hidden="true">•</span>
                            <span class="fav-card__price"><?= $formatPrice($props['PRICE']['VALUE']) ?>&nbsp;₽</span>
                        </p>
                        <p class="fav-card__text"><?= htmlspecialcharsbx(strip_tags((string)$item['PREVIEW_TEXT'])) ?></p>
                        <a class="link-arrow link-arrow--center" href="#booking" data-book-service="<?= (int)$item['ID'] ?>"
                           aria-label="Записаться: <?= htmlspecialcharsbx($item['NAME']) ?>">
                            <?= $arrow ?><span class="link-arrow__text">Записаться</span>
                        </a>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
