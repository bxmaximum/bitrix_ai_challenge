---
name: bitrix-orm
description: Covers Bitrix D7 ORM — DataManager and Table classes, getMap(), fields and relations (ScalarField, IntegerField, StringField, Reference, UField, ExpressionField), object-oriented work via fetchObject/CollectionObject, add/update/delete, table events (onBeforeAdd, onAfterUpdate, onDelete), query() with setSelect/setFilter/runtime/join. Applied when designing entities, database queries instead of raw SQL, building selections and relationships between tables. Key terms — DataManager, Table, getMap, Reference, fetchObject, query, onBeforeAdd, ORM.
---

# Bitrix D7 ORM

All Table classes live in `/local/modules/<m>/lib/Model/`. Names end in `Table` (`PostTable`). The name without the suffix is reserved for the object class (`Post`).

Generation:

```bash
php bitrix/bitrix.php make:tablet my_post vendor.module
php bitrix/bitrix.php orm:annotate -m vendor.module  # IDE annotations
```

## Tablet Skeleton

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
        return 'VENDOR_MODULE_POST'; // if user fields are present
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

**Configuration methods instead of arrays**: `configureRequired`, `configurePrimary`, `configureAutocomplete`, `configureNullable`, `configureSize`, `configureDefaultValue`, `configureColumnName`, `configureTitle`. The old format with array `['primary' => true, 'required' => true]` still works, but prefer fluent API in new code.

## Field Types

- `IntegerField`, `FloatField`, `DecimalField` — numeric.
- `StringField`, `TextField` — strings/texts.
- `BooleanField` — `configureValues('N', 'Y')` stores Y/N.
- `DateField`, `DatetimeField` — return `Bitrix\Main\Type\Date`/`DateTime`.
- `EnumField` — `configureValues(['draft', 'published'])`.
- `ArrayField` — array, with its own serializer.
- `CryptoField`, `SecretField` — built-in encryption (see `bitrix-security`).
- `ExpressionField('FULL_NAME', 'CONCAT(%s, " ", %s)', ['NAME', 'LAST_NAME'])` — computed field.

## Relations

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

## Reading Data

### Arrays (`fetch`)

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

### Objects (`fetchObject`, `fetchCollection`)

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

### Query Builder

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

## Writing

### Arrays

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

### Objects

```php
$post = new \Vendor\Module\Model\EO_Post();  // or PostTable::createObject();
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

Collections:

```php
$collection = PostTable::query()->whereIn('ID', [1, 2])->fetchCollection();
foreach ($collection as $post)
{
    $post->setActive(false);
}
$collection->save(); // one query for all
```

## Collections and Annotations

After `orm:annotate`, IDE gets types like `EO_Post`, `EO_Post_Collection`, `EO_Post_Query`:

```php
/** @var \Vendor\Module\Model\EO_Post $post */
$post = PostTable::getByPrimary($id)->fetchObject();

/** @var \Vendor\Module\Model\EO_Post_Collection $posts */
$posts = PostTable::query()->where('ACTIVE', 'Y')->fetchCollection();
```

Collection methods: `save()`, `delete()`, `fill()` (eager load relations). Use `fetchCollection()` instead of looping `fetchObject()` to avoid N+1.

### Query builder vs `getList`

- `getList(['select' => ..., 'filter' => ...])` — array API, good for simple queries.
- `PostTable::query()->setSelect()->where()->fetchCollection()` — fluent builder, better for dynamic conditions and runtime fields.

## Events

In the tablet class:

```php
public static function onBeforeAdd(\Bitrix\Main\ORM\Event $event): \Bitrix\Main\ORM\EventResult
{
    $result = new \Bitrix\Main\ORM\EventResult();
    $fields = $event->getParameter('fields');

    if (empty($fields['TITLE']))
    {
        $result->addError(new \Bitrix\Main\ORM\EntityError('Title is required'));
    }

    // Modification:
    $result->modifyFields(['TITLE' => strtoupper($fields['TITLE'])]);

    return $result;
}
```

Events: `onBeforeAdd`, `onAfterAdd`, `onBeforeUpdate`, `onAfterUpdate`, `onBeforeDelete`, `onAfterDelete`.

## Caching

```php
'cache' => [
    'ttl' => 3600,
    'cache_joins' => true,
]
```

To automatically clear cache on update, the tablet must return `true` in `isCacheable()`.

## User Fields (UF)

If `getUfId()` returns a string, UF fields are automatically available in `select` and `filter`. In the object, they are accessed via `get('UF_FIELD')` / `set('UF_FIELD', $v)`.

## Checklist

- [ ] Tablet class lives in `lib/Model/` and ends in `Table`.
- [ ] Primary keys are correctly defined (`configurePrimary`).
- [ ] Relationships use `Reference`, `OneToMany`, or `ManyToMany`.
- [ ] Fluent API (`configureXxx`) is used for field descriptions.
- [ ] Objects (`fetchObject`) are used for business logic, arrays (`fetch`) for simple lists.
- [ ] Cache is enabled (`isCacheable`) where necessary.
- [ ] Table creation/deletion is handled via Entity.
- [ ] Events are implemented in the tablet, not in the service.
- [ ] Validations use `addValidator`.
- [ ] `orm:annotate` is run to support IDE.
