---
name: bitrix-orm
description: Покрывает ORM Bitrix D7 — DataManager и Table-классы, getMap(), поля и связи (ScalarField, IntegerField, StringField, Reference, UField, ExpressionField), объектная работа через fetchObject/CollectionObject, add/update/delete, события таблиц (onBeforeAdd, onAfterUpdate, onDelete), query() с setSelect/setFilter/runtime/join. Применяется при проектировании сущностей, запросах к БД вместо сырого SQL, построении выборок и связей между таблицами. Ключевые термины — DataManager, Table, getMap, Reference, fetchObject, query, onBeforeAdd, ORM.
---

# Bitrix D7 ORM

Все Table-классы живут в `/local/modules/<m>/lib/Model/`. Имена оканчиваются на `Table` (`PostTable`). Имя без суффикса зарезервировано под класс объекта (`Post`).

Генерация:

```bash
php bitrix/bitrix.php make:tablet my_post vendor.module
php bitrix/bitrix.php orm:annotate -m vendor.module  # аннотации для IDE
```

## Скелет таблета

```php
<?php declare(strict_types=1);

namespace Vendor\Module\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;
use Bitrix\Main\Localization\Loc;

final class PostTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_module_post';
    }

    public static function getUfId(): string
    {
        return 'VENDOR_MODULE_POST'; // если есть пользовательские поля
    }

    public static function isCacheable(): bool
    {
        return true;
    }

    public static function getMap(): array
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new Fields\StringField('TITLE'))
                ->configureRequired()
                ->configureSize(255)
                ->addValidator(new Validators\LengthValidator(null, 255)),

            (new Fields\TextField('BODY'))
                ->configureNullable(),

            (new Fields\BooleanField('ACTIVE'))
                ->configureValues('N', 'Y')
                ->configureDefaultValue('Y'),

            (new Fields\DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(fn () => new \Bitrix\Main\Type\DateTime()),

            (new Fields\IntegerField('AUTHOR_ID'))
                ->configureRequired(),

            (new Fields\Relations\Reference(
                'AUTHOR',
                \Bitrix\Main\UserTable::class,
                ['=this.AUTHOR_ID' => 'ref.ID'],
            ))->configureJoinType('LEFT'),
        ];
    }
}
```

**Методы конфигурации вместо массивов**: `configureRequired`, `configurePrimary`, `configureAutocomplete`, `configureNullable`, `configureSize`, `configureDefaultValue`, `configureColumnName`, `configureTitle`. Старый формат с массивом `['primary' => true, 'required' => true]` всё ещё работает, но в новом коде предпочитай fluent API.

## Типы полей

- `IntegerField`, `FloatField`, `DecimalField` — числовые.
- `StringField`, `TextField` — строки/тексты.
- `BooleanField` — `configureValues('N', 'Y')` хранит Y/N.
- `DateField`, `DatetimeField` — возвращают `Bitrix\Main\Type\Date`/`DateTime`.
- `EnumField` — `configureValues(['draft', 'published'])`.
- `ArrayField` — массив, с собственным сериализатором.
- `CryptoField`, `SecretField` — шифрование из коробки (см. `bitrix-security`).
- `ExpressionField('FULL_NAME', 'CONCAT(%s, " ", %s)', ['NAME', 'LAST_NAME'])` — вычисляемое поле.

## Связи

```php
(new Fields\Relations\Reference('AUTHOR', UserTable::class, ['=this.AUTHOR_ID' => 'ref.ID']))
    ->configureJoinType('LEFT'),

(new Fields\Relations\OneToMany('COMMENTS', CommentTable::class, 'POST'))
    ->configureJoinType('LEFT'),

(new Fields\Relations\ManyToMany('TAGS', TagTable::class))
    ->configureTableName('vendor_module_post_tag')
    ->configureLocalPrimary('ID', 'POST_ID')
    ->configureRemotePrimary('ID', 'TAG_ID'),
```

## Чтение данных

### Массивы (`fetch`)

```php
$rows = PostTable::getList([
    'select' => ['ID', 'TITLE', 'AUTHOR_NAME' => 'AUTHOR.NAME'],
    'filter' => ['=ACTIVE' => 'Y', '>CREATED_AT' => new \Bitrix\Main\Type\DateTime('2026-01-01')],
    'order'  => ['CREATED_AT' => 'DESC'],
    'limit'  => 20,
    'offset' => 0,
    'cache'  => ['ttl' => 3600],
])->fetchAll();
```

### Объекты (`fetchObject`, `fetchCollection`)

```php
$post = PostTable::getByPrimary($id, [
    'select' => ['*', 'AUTHOR'],
])->fetchObject();

$title = $post->getTitle();
$authorName = $post->getAuthor()->getName();

$collection = PostTable::query()
    ->setSelect(['*', 'COMMENTS'])
    ->where('ACTIVE', 'Y')
    ->fetchCollection();

foreach ($collection as $post)
{
    echo $post->getTitle(), PHP_EOL;
    foreach ($post->getComments() as $comment) { /* ... */ }
}
```

### Query builder

```php
$query = PostTable::query()
    ->setSelect(['ID', 'TITLE', new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(%s)', 'COMMENTS.ID')])
    ->registerRuntimeField(
        new \Bitrix\Main\ORM\Fields\ExpressionField('IS_NEW', 'CASE WHEN %s > NOW() - INTERVAL 7 DAY THEN 1 ELSE 0 END', 'CREATED_AT')
    )
    ->where('ACTIVE', 'Y')
    ->whereIn('AUTHOR_ID', [1, 2, 3])
    ->addOrder('CREATED_AT', 'DESC')
    ->setLimit(50)
    ->setGroup(['ID'])
    ->having('CNT', '>', 0);

$result = $query->fetchAll();
```

## Запись

### Массивы

```php
$add = PostTable::add(['TITLE' => 'Hi', 'AUTHOR_ID' => 1]);
if (!$add->isSuccess())
{
    $this->addErrors($add->getErrors());
    return;
}
$id = $add->getId();

PostTable::update($id, ['TITLE' => 'Hello']);
PostTable::delete($id);
```

### Объекты

```php
$post = new \Vendor\Module\Model\EO_Post();  // или PostTable::createObject();
$post->setTitle('Title')
     ->setBody('Body')
     ->setAuthorId($currentUserId);

$save = $post->save();
if (!$save->isSuccess()) { /* ... */ }

$loaded = PostTable::getByPrimary(10)->fetchObject();
$loaded->setTitle('Updated');
$loaded->save();

$loaded->delete();
```

Коллекции:

```php
$collection = PostTable::query()->where('ACTIVE', 'N')->fetchCollection();
foreach ($collection as $post) { $post->setActive('Y'); }
$collection->save();
$collection->delete();  // групповое удаление
```

## События таблеты

Кастомизируй из таблета:

```php
public static function onBeforeAdd(\Bitrix\Main\ORM\Event $event): \Bitrix\Main\ORM\EventResult
{
    $result = new \Bitrix\Main\ORM\EventResult();
    $data = $event->getParameter('fields');

    if (empty($data['SLUG']) && !empty($data['TITLE']))
    {
        $result->modifyFields(['SLUG' => \CUtil::translit($data['TITLE'], 'ru')]);
    }

    return $result;
}
```

Также: `onBeforeUpdate`, `onAfterUpdate`, `onBeforeDelete`, `onAfterDelete`, `onAfterAdd`. Для системных событий регистрируй обработчики через `EventManager` (см. `bitrix-events`).

## Пользовательские поля (UF_*)

Подключи `getUfId()` в таблете — тогда они автоматически читаются/пишутся в ORM и видны в админке.

## Кеширование запросов

- `['cache' => ['ttl' => 3600, 'cache_joins' => true]]` — кеш запроса на TTL.
- Таблет с `isCacheable()` + `ManagedCache` + `TaggedCache` по tag `ORM_VENDOR_MODULE_POST` — см. `bitrix-caching`.

## Чек-лист

- [ ] Имя класса — `*Table`, таблица — `snake_case` с префиксом модуля.
- [ ] Поля сконфигурированы fluent-API, указаны `nullable`/`required`/`size`.
- [ ] Связи используют `Reference`/`OneToMany`/`ManyToMany` вместо ручных `JOIN`-строк.
- [ ] `isCacheable()` включён там, где данные редко меняются.
- [ ] Чтение — через `fetchObject`/`fetchCollection`; работа со `stdClass`-массивами — только при массовых выборках для отчётов.
- [ ] Запись/изменение проходят через сервис, не прямо из контроллера.
- [ ] Пользовательский ввод **не** кладётся сырьём в `select`, `filter`, `SqlExpression`, `ExpressionField`, `runtime` (см. `bitrix-security`).
