# SOLUTION.md

## Reference

**URL:** https://antaraspawellness.com/  
**Awwwards:** [Antara Spa Wellness, Honorable Mention, 7 Feb 2025](https://www.awwwards.com/sites/antara-spa-wellness)

Почему он: живой бьюти-ретрит с узнаваемой композицией — полноэкранное фото-хиро, гигантский трекинг serif-вордмарк, оливковая арка-манифест и вертикальная пилюля Menu. Палитра `#425e40` + крем, пара шрифтов Fraunces / Manrope, приёмы параллакса и появления текста совпадают с задачей «студия + запись», а не с витриной e-commerce.

### Разбор секций

| Референс | У «Лак&Точка» |
| --- | --- |
| Хиро: фото, вордмарк, верхняя линейка, tagline, Scroll | Hero с оффером и CTA «Записаться» |
| Оливковая арка с italic-манифестом | Блок о студии |
| Sanctuary + rounded photo | Описание студии |
| Past wisdom / Modern experiences overlay | Двухколоночный split |
| Antara Menu / tall cards | Услуги с ценами из инфоблока |
| Step into Tranquility | Галерея работ |
| Exclusive treats cards | Карточки мастеров |
| What’s happening / цитаты | Отзывы |
| Your self-care journey + Reach us | Онлайн-запись, контакты, футер |

**Шрифты:** Fraunces (100 / italic 400–600) + Manrope 400–700, локально.  
**Палитра:** `--olive #425e40`, `--cream #c5beb1`, `--beige #e8e2d6`, `--ink #221f1e`.

**Приёмы (чек-лист):** (1) полноэкранное хиро и letter-spacing вордмарка; (2) тонкая верхняя линейка + геометрический mark + пункты «• Меню / • Контакты»; (3) вертикальная cream-пилюля Menu на десктопе, снизу на 360px; (4) оливковая арка `border-radius` и italic Fraunces; (5) fade-up `.reveal` при скролле, hover кнопок (заливка cream↔прозрачность), `prefers-reduced-motion` отключает прелоадер и анимации.

## Assumptions / Questions / Deviations

- Референс Cure Unique Nail (SOTD 2019) мёртв (домен занят азартным сайтом). Взят живой Antara Spa Wellness с карточки Awwwards; страница открыта целиком, desktop и 360px.
- Закрытый слот: поле `IS_CLOSED` (`Y`/`N`). В `slots.list` статус только `free`/`taken`; закрытый, занятый и прошедший — `taken`.
- Гонка: `UNIQUE` на `SLOT_ID` + `SELECT … FOR UPDATE` в транзакции; дубликат → `SLOT_TAKEN`.
- Расписание: 14 дней от даты миграции, 10:00–19:00 старт, шаг 60 мин. Повторный `up` не плодит слоты (есть слоты мастера — skip).
- Почта `BXMAX_BOOKING_NEW`: `EMAIL_TO` из `email_from` main, иначе `notify_email` модуля.
- Админка: `admin/menu.php` + `CAdminList`, права `D/R/W`. Стабы копируются в `/bitrix/admin/` при установке (штатный приём, не правка ядра).
- После удаления модуля лендинг жив; компонент записи показывает ошибку модуля.
- `weekStart` не понедельник — нормализуется к понедельнику той недели.
- Согласие: не истина → `CONSENT_REQUIRED`; `CONSENT_AT` = now.
- Отзывы и галерея не в инфоблоках (в ТЗ только `services` / `masters`).
- Прелоадер референса укорочен (~0.9s), без фоновой музыки.
- Свойства инфоблока читаются через `GetList` + `PROPERTY_*`: `GetProperties()` на этой площадке отдаёт пустой массив.
- Скрипты шаблона и компонента в `<head>` — обёрнуты в `DOMContentLoaded`.
- Время слота на фронте берётся из ISO как wall-clock студии, без сдвига в TZ браузера.
- У `.bk__form` задан `display: grid`, поэтому `[hidden]` явно сбрасывается в `display: none`.
