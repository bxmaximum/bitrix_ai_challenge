---
name: bitrix-datetime
description: Covers date and time in Bitrix — Bitrix\Main\Type\Date and DateTime, parsing and formatting by kernel masks, date arithmetic, time zones via toUserTime/disableUserTime/enableUserTime, Context::getCulture() and locale formats, conversion to timestamp. Applied when building schedules, converting time zones, comparing dates, working with ORM fields DateField and DatetimeField. Key terms — Date, DateTime, toUserTime, format, timezone, Culture, DateField, timestamp.
---

# Date and Time in Bitrix

Bitrix does not use raw `\DateTime` — there are two wrappers that take into account **site regional settings** and **user time zone**.

- `Bitrix\Main\Type\Date` — date only (time is always `00:00:00`).
- `Bitrix\Main\Type\DateTime` — date + time + time zone. Inherits from `Date`.

Both inherit from PHP `\DateTime`, so `->format(...)`, `->getTimestamp()`, etc., work.

```php
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

$date = new Date('25.11.2025', 'd.m.Y');
$dt = new DateTime();                         // now()
$dt = new DateTime('2025-11-25 14:30:00', 'Y-m-d H:i:s');
```

## Formats: Bitrix Masks vs PHP

Regional settings use their own mask language (`DD.MM.YYYY HH:MI:SS`). The `convertFormatToPhp(...)` method converts it to PHP format.

| Bitrix Mask | PHP | Description |
| --- | --- | --- |
| `YYYY` | `Y` | Year |
| `MM` | `m` | Month (with leading zero) |
| `MMMM` | `F` | Month name |
| `DD` | `d` | Day (with leading zero) |
| `HH` | `H` | Hour 24 |
| `GG` | `h` | Hour 12 |
| `H` / `G` | `G` / `g` | Hour without leading zero |
| `MI` | `i` | Minutes |
| `SS` | `s` | Seconds |
| `TT` / `T` | `A` / `a` | AM/PM |

```php
$culture = \Bitrix\Main\Context::getCurrent()->getCulture();
echo $dt->format($culture->getDateTimeFormat()); // 25.11.2025 14:30:00
echo (string)$dt;                                // string conversion = format(culture.DateTime)
```

Use `Culture::getDateFormat()`/`getDateTimeFormat()` for UI output — this allows the project to switch to another language without code changes.

## Creation / Parsing

### Safe Parsing

```php
$dt = DateTime::tryParse($request['DATE'], 'd.m.Y H:i'); // null on error
if ($dt === null) { /* format error */ }

if (!DateTime::isCorrect('31.02.2025', 'd.m.Y')) { /* ... */ }
```

Constructor with an incorrect string throws `Bitrix\Main\ObjectException` — so use `tryParse`/`isCorrect` for user input.

### From Other Sources

```php
$dt = DateTime::createFromPhp(new \DateTime('2025-11-25 14:30:00', new \DateTimeZone('UTC')));
$dt = DateTime::createFromTimestamp(time());
$date = Date::createFromText('end of next week');    // null if unparseable; understands local language
```

## Arithmetic

`add($interval)` accepts **both** `DateInterval` strings (`P10D`, `-P1M`, `P1Y2M10D`) **and** human text (`+5 days`, `-2 weeks`):

```php
$date = new Date('01.02.2025', 'd.m.Y');
$date->add('P10D');      // +10 days → 11.02.2025
$date->add('-P1M');      // -1 month → 11.01.2025
$date->add('+2 weeks');  // +14 days
```

Important: `add` **mutates the object** and returns it. If you need an immutable calculation — clone it: `$later = (clone $dt)->add('P1D');`.

Setting specific values:

```php
$dt->setDate(2026, 1, 15);
$dt->setTime(9, 30, 0);
```

Diffs:

```php
$diff = $d2->getDiff($d1);   // \DateInterval
echo $diff->days;
```

## Time Zones

Bitrix stores dates in **server** time zone and shows them to the user in their time zone (from profile or auto-detected by browser). Configured in *Settings → Main Module → Time Zones*.

### Explicitly Changing Object Time Zone

```php
$dt->setTimeZone(new \DateTimeZone('Europe/Berlin'));
$dt->setDefaultTimeZone(); // return to server time zone
```

### Converting To/From User Time

```php
// User input in their TZ → server time object
$serverDt = DateTime::createFromUserTime('25.11.2025 18:00');

// Server time object → string in user's TZ
$userDt = $serverDt->toUserTime();
```

### Auto-conversion on String Cast

If time zones are enabled in `main` module settings, `DateTime` → string cast **automatically** converts to user's time zone:

```php
$dt = new DateTime('2025-11-25 12:00:00', 'Y-m-d H:i:s'); // UTC server
echo $dt;  // for a user in UTC+3 it will be "25.11.2025 15:00:00"
```

**Disable** (for logs, debugging, system events, email to admin):

```php
$dt->disableUserTime();        // server time
$dt->enableUserTime();         // enable back
$dt->isUserTimeEnabled();      // true|false
```

> ORM fields of `DatetimeField` type return a ready-to-use `DateTime` — you can immediately write `echo $post->getCreatedAt();`, but call `disableUserTime()` when logging.

## ORM Integration

```php
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DateField;

(new DatetimeField('CREATED_AT'))
    ->configureRequired()
    ->configureDefaultValue(static fn () => new DateTime());

(new DateField('BIRTH_DAY'))->configureNullable();
```

In queries, you can compare directly with `DateTime`:

```php
PostTable::getList([
    'filter' => [
        '>CREATED_AT' => (new DateTime())->add('-P7D'),  // a week ago
    ],
]);
```

## When to use `\DateTime`, when Bitrix `DateTime`

- Public API (writing to DB, UI output, ORM) — **Bitrix `DateTime`**.
- Bridge with an external library using PSR/Symfony — get `\DateTimeImmutable` and convert via `DateTime::createFromPhp(...)`.
- For arithmetic and differences — `DateTime` works, both APIs are available.

## Practical Recipes

### Start/End of Day

```php
$start = (clone $now)->setTime(0, 0, 0);
$end   = (clone $now)->setTime(23, 59, 59);
```

### Start of Week (Monday)

```php
$weekStart = (clone $now);
$weekStart->setTime(0, 0, 0);
$weekStart->modify('monday this week');
```

### Same Day Last Year

```php
$lastYear = (clone $now)->add('-P1Y');
```

### Outputting "in 5 minutes" in a Cron Task

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

Use `Culture::getFormat()` / `Context::getCurrent()->getCulture()` for locale-aware date formatting instead of hardcoded `date()` masks.
