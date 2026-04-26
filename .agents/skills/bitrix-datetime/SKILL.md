---
name: bitrix-datetime
description: Покрывает работу с датой и временем в Bitrix — Bitrix\Main\Type\Date и DateTime, парсинг и форматирование по маскам ядра, арифметика дат, таймзоны через toUserTime/disableUserTime/enableUserTime, Context::getCulture() и форматы локали, конвертация в timestamp. Применяется при построении расписаний, пересчёте часовых поясов, сравнении дат, работе с полями ORM DateField и DatetimeField. Ключевые термины — Date, DateTime, toUserTime, format, timezone, Culture, DateField, timestamp.
---

# Дата и время в Bitrix

Bitrix не использует голый `\DateTime` — есть две обёртки, которые учитывают **региональные настройки сайта** и **часовой пояс пользователя**.

- `Bitrix\Main\Type\Date` — только дата (время всегда `00:00:00`).
- `Bitrix\Main\Type\DateTime` — дата + время + таймзона. Наследник `Date`.

Оба наследуют PHP `\DateTime`, поэтому работает `->format(...)`, `->getTimestamp()` и т. п.

```php
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

$date = new Date('25.11.2025', 'd.m.Y');
$dt = new DateTime();                         // now()
$dt = new DateTime('2025-11-25 14:30:00', 'Y-m-d H:i:s');
```

## Форматы: маски Bitrix vs PHP

Региональные настройки используют свой язык масок (`DD.MM.YYYY HH:MI:SS`). Метод `convertFormatToPhp(...)` преобразует его в PHP-формат.

| Маска Bitrix | PHP | Что |
| --- | --- | --- |
| `YYYY` | `Y` | Год |
| `MM` | `m` | Месяц (с ведущим нулём) |
| `MMMM` | `F` | Название месяца |
| `DD` | `d` | День (с ведущим нулём) |
| `HH` | `H` | Час 24 |
| `GG` | `h` | Час 12 |
| `H` / `G` | `G` / `g` | Часы без ведущего нуля |
| `MI` | `i` | Минуты |
| `SS` | `s` | Секунды |
| `TT` / `T` | `A` / `a` | AM/PM |

```php
$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
echo $dt->format($culture->getDateTimeFormat()); // 25.11.2025 14:30:00
echo (string)$dt;                                // приведение к строке = format(culture.DateTime)
```

Используй `Culture::getDateFormat()`/`getDateTimeFormat()` при выводе UI — тогда проект переключается на другой язык без правок кода.

## Создание / парсинг

### Безопасный парсинг

```php
$dt = DateTime::tryParse($request['DATE'], 'd.m.Y H:i'); // null при ошибке
if ($dt === null) { /* ошибка формата */ }

if (!DateTime::isCorrect('31.02.2025', 'd.m.Y')) { /* ... */ }
```

Конструктор с некорректной строкой бросает `Bitrix\Main\ObjectException` — поэтому для пользовательского ввода используй `tryParse`/`isCorrect`.

### Из других источников

```php
$dt = DateTime::createFromPhp(new \DateTime('2025-11-25 14:30:00', new \DateTimeZone('UTC')));
$dt = DateTime::createFromTimestamp(time());
$date = Date::createFromText('end of next week');    // null, если не распарсить; понимает локальный язык
```

## Арифметика

`add($interval)` принимает **и** `DateInterval`-строки (`P10D`, `-P1M`, `P1Y2M10D`), **и** человеческий текст (`+5 days`, `-2 weeks`):

```php
$date = new Date('01.02.2025', 'd.m.Y');
$date->add('P10D');      // +10 дней → 11.02.2025
$date->add('-P1M');      // -1 месяц → 11.01.2025
$date->add('+2 weeks');  // +14 дней
```

Важно: `add` **мутирует объект** и возвращает его же. Если нужен неизменяемый расчёт — клонируй: `$later = (clone $dt)->add('P1D');`.

Установка конкретных значений:

```php
$dt->setDate(2026, 1, 15);
$dt->setTime(9, 30, 0);
```

Диффы:

```php
$diff = $d2->getDiff($d1);   // \DateInterval
echo $diff->days;
```

## Часовые пояса

Bitrix хранит даты в таймзоне **сервера**, а пользователю показывает в его таймзоне (из профиля или автоопределённой браузером). Настраивается в *Настройки → Главный модуль → Часовые пояса*.

### Явная смена таймзоны объекта

```php
$dt->setTimeZone(new \DateTimeZone('Europe/Berlin'));
$dt->setDefaultTimeZone(); // вернуть серверную
```

### Перевод в/из пользовательского времени

```php
// Ввод от пользователя в его TZ → объект серверного времени
$serverDt = DateTime::createFromUserTime('25.11.2025 18:00');

// Объект серверного времени → строка в TZ пользователя
$userDt = $serverDt->toUserTime();
```

### Авто-конвертация при приведении к строке

Если в настройках модуля `main` включены таймзоны, приведение `DateTime` → строка **автоматически** переведёт в пояс пользователя:

```php
$dt = new DateTime('2025-11-25 12:00:00', 'Y-m-d H:i:s'); // UTC-сервер
echo $dt;  // у пользователя в UTC+3 будет «25.11.2025 15:00:00»
```

**Отключить** (для логов, отладки, системных событий, почты админу):

```php
$dt->disableUserTime();        // серверное время
$dt->enableUserTime();         // включить обратно
$dt->isUserTimeEnabled();      // true|false
```

> ORM-поля типа `DatetimeField` возвращают уже готовый `DateTime` — можно сразу пишешь `echo $post->getCreatedAt();`, но при логировании вызывай `disableUserTime()`.

## Интеграция с ORM

```php
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DateField;

(new DatetimeField('CREATED_AT'))
    ->configureRequired()
    ->configureDefaultValue(static fn () => new DateTime());

(new DateField('BIRTH_DAY'))->configureNullable();
```

В запросах можно сравнивать напрямую с `DateTime`:

```php
PostTable::getList([
    'filter' => [
        '>CREATED_AT' => (new DateTime())->add('-P7D'),  // неделю назад
    ],
]);
```

## Когда `\DateTime`, когда Bitrix `DateTime`

- Публичное API (запись в БД, вывод пользователю, ORM) — **Bitrix `DateTime`**.
- Мост с внешней библиотекой на PSR/Symfony — получай `\DateTimeImmutable` и конвертируй через `DateTime::createFromPhp(...)`.
- Для арифметики и разниц — `DateTime` подходит, и тот и другой API доступны.

## Практические рецепты

### Начало/конец суток

```php
$start = (clone $now)->setTime(0, 0, 0);
$end   = (clone $now)->setTime(23, 59, 59);
```

### Начало недели (понедельник)

```php
$weekStart = (clone $now);
$weekStart->setTime(0, 0, 0);
$weekStart->modify('monday this week');
```

### Тот же день в прошлом году

```php
$lastYear = (clone $now)->add('-P1Y');
```

### Вывод «через 5 минут» в cron-задаче

```php
\CAgent::AddAgent(
    MyAgent::class . '::run();',
    'vendor.module',
    'N',
    60,
    '',
    'Y',
    (new DateTime())->add('+5 minutes')->toString(),
);
```

### Сериализация в JSON API

```php
// ATOM/ISO 8601 в UTC
$dt = (clone $post->getCreatedAt())->setTimeZone(new \DateTimeZone('UTC'));
return ['created_at' => $dt->format(\DateTime::ATOM)];
```

## Антипаттерны

- `date('d.m.Y', ...)` / `strtotime(...)` для пользовательского вывода — игнорирует Culture и TZ пользователя.
- `new DateTime($raw)` без `tryParse` на пользовательском вводе — эксепшен.
- Отсутствие `disableUserTime()` в логах → в логах окажется время, пересчитанное в TZ админа, который открыл страницу.
- Конкатенация строк для «+1 день» вместо `add('P1D')`.
- `setTimeZone(...)` после `format()` — таймзона на уже отформатированную строку не повлияет.
- Приравнивание `DateTime` по `==` — сравнивай `->getTimestamp()` или через `>/<` на объектах.

## Чек-лист

- [ ] Пользовательский ввод проходит через `DateTime::tryParse` / `Date::isCorrect`.
- [ ] Даты для пользователя форматируются по `Culture::getDateFormat()`/`getDateTimeFormat()`.
- [ ] В логах, отладке, системных письмах используется `disableUserTime()`.
- [ ] API-сериализация — через явный UTC + `DateTime::ATOM`/`ISO8601`.
- [ ] Для ORM-полей используется `DateField`/`DatetimeField`, а не `StringField` со своим форматом.
- [ ] `add(...)` и `modify(...)` воспринимаются как мутирующие — где нужен неизменяемый результат, используется `clone`.
