---
name: bitrix-database
description: Covers direct database work in Bitrix — Application::getConnection(), Connection, MysqliConnection, SqlHelper, SqlExpression, raw SQL queries via query()/queryExecute()/queryScalar(), transactions (startTransaction/commitTransaction/rollbackTransaction), DDL and schema migrations, bulk operations (insertBatch, addMulti), additional connections via the connections section in .settings.php. Applied when ORM is insufficient — bulk operations, raw SQL, migrations, working with external databases, and building custom queries. Key terms — Connection, SqlHelper, SqlExpression, transaction, raw SQL, bulk insert, DDL, migration.
---

# Direct Database Work

ORM is the first choice (`bitrix-orm`). Direct SQL is needed for:

- Migrations/DDL in `install/index.php` / `updater.php`,
- Bulk operations (`UPSERT`, `REPLACE`, windows/CTE),
- Reports with `GROUP BY`/aggregates that are cumbersome to build via ORM,
- Working with multiple connections (analytical replica, Redis).

## Connection

```php
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;

/** @var Connection $db */
$db = Application::getConnection();           // default
$db = Application::getConnection('default');
$analytics = Application::getConnection('analytics'); // additional
```

## Configuration in `.settings.php`

```php
'connections' => [
    'value' => [
        'default' => [
            'className' => \Bitrix\Main\DB\MysqliConnection::class,
            'host'      => 'db',
            'database'  => 'bx',
            'login'     => 'bx',
            'password'  => '***',
            'options'   => \Bitrix\Main\DB\Connection::DEFERRED, // 2 — connect on first query
        ],
        'analytics' => [
            'className' => \Bitrix\Main\DB\PgsqlConnection::class,
            'host'      => 'pg',
            'database'  => 'analytics',
            'login'     => 'ro',
            'password'  => '***',
            'options'   => \Bitrix\Main\DB\Connection::DEFERRED,
        ],
        'redis' => [
            'className' => \Bitrix\Main\Data\RedisConnection::class,
            'host'      => 'redis',
            'port'      => 6379,
            'persistent'=> true,
            'serializer'=> \Redis::SERIALIZER_IGBINARY,
            'compression' => \Redis::COMPRESSION_LZ4,
        ],
    ],
    'readonly' => true,
],
```

`options`: `Connection::PERSISTENT = 1`, `Connection::DEFERRED = 2`, combined via bitwise OR (`3`).

Classes:

- `\Bitrix\Main\DB\MysqliConnection` — MySQL (`mysqli`).
- `\Bitrix\Main\DB\PgsqlConnection` — PostgreSQL.
- `\Bitrix\Main\DB\MssqlConnection`, `\Bitrix\Main\DB\OracleConnection` — rare.
- `\Bitrix\Main\Data\MemcacheConnection`, `MemcachedConnection`, `RedisConnection`.
- `\Bitrix\Main\Data\HsphpReadConnection` — HandlerSocket (read-only, for high-load `SELECT` by primary key bypassing SQL).

## SELECT

```php
$rs = $db->query('SELECT ID, NAME FROM b_user WHERE ACTIVE = "Y"');
$rs = $db->query('SELECT ID FROM b_user', 10);     // LIMIT 10
$rs = $db->query('SELECT ID FROM b_user', 0, 100); // LIMIT 0, 100

while ($row = $rs->fetch())
{
    $id = (int)$row['ID'];
}

foreach ($rs as $row) { /* ... */ }

$id = $db->queryScalar('SELECT COUNT(*) FROM b_user WHERE ACTIVE = "Y"');
```

- `fetch()` — values are **processed through field converters** (date → `Bitrix\Main\Type\DateTime`).
- `fetchRaw()` — as received from the driver.
- `$result->getSelectedRowsCount()`, `$result->getFields()`, `$result->getResource()` (low-level `mysqli_result`).

Important: `Result` cannot be "rewound" — if a second pass is needed, materialize it into an array.

### Custom Converters

```php
$rs = $db->query('SELECT ID, ACTIVE, DATE_REGISTER FROM b_user');
$rs->setConverters(['DATE_REGISTER' => static fn ($v) => $v ? strtotime($v) : null]);
$rs->addFetchDataModifier(static function (array $row): array {
    $row['ACTIVE_BOOL'] = $row['ACTIVE'] === 'Y';
    return $row;
});
```

## INSERT/UPDATE/DELETE

```php
$id = $db->add('my_table', [
    'NAME'    => 'example',
    'CONTENT' => $raw,              // automatically escaped
]);

$lastId = $db->addMulti('my_table', [
    ['NAME' => 'a', 'CONTENT' => '1'],
    ['NAME' => 'b', 'CONTENT' => '2'],
]);

$db->queryExecute(
    'UPDATE my_table SET NAME = "' . $db->getSqlHelper()->forSql($name) . '" WHERE ID = ' . (int)$id
);
```

`add`/`addMulti` silently **discard keys** with non-existent columns and escape values themselves. Convenient for fixtures and migrations.

> IMPORTANT: the `$binds` parameter in `query/queryScalar/queryExecute` **does not** create prepared statements — these are only placeholders for LOBs in some drivers. Protect against SQL injections via `SqlExpression` or `SqlHelper`.

## SqlHelper — Escaping and Utilities

```php
$h = $db->getSqlHelper();

$h->quote('table.id');            // `table`.`id`
$h->forSql($userInput);           // escapes quotes
$h->convertToDb($value);          // 'v' | 'NULL' | '123'
$h->convertToDbString(null);      // ''
$h->convertToDbString('long', 5); // 'long '  (truncated)
$h->convertToDbInteger('x');      // 0
$h->convertToDbInteger(1e10, 4);  // 2147483647 — 4 byte limit
$h->convertToDbFloat(1.2345, 1);  // '1.2'
$h->convertToDbDate(new \Bitrix\Main\Type\Date('01.01.2025'));      // '2025-01-01'
$h->convertToDbDateTime(new \Bitrix\Main\Type\DateTime());

$h->getCurrentDateTimeFunction();                  // NOW()
$h->addSecondsToDateTime(60, $h->quote('c'));      // DATE_ADD(`c`, INTERVAL 60 SECOND)
$h->addDaysToDateTime(30);                         // DATE_ADD(NOW(), INTERVAL 30 DAY)
$h->getConcatFunction($h->quote('a'), "'-'", $h->quote('b'));
$h->getIsNullFunction($h->quote('a'), 0);          // IFNULL(`a`, 0)
$h->getMatchFunction($h->quote('body'), $h->convertToDb('bitrix')); // MATCH ... AGAINST
```

SQL function arguments **are not automatically escaped** — pass them through `quote`/`convertToDb` yourself.

## UPSERT (`prepareMerge*`)

```php
[$sql] = $h->prepareMerge(
    'b_user_counter',
    ['USER_ID', 'SITE_ID', 'CODE'],
    insertFields: ['USER_ID' => 1, 'SITE_ID' => 's1', 'CODE' => 'visits', 'CNT' => 1],
    updateFields: ['CNT' => new \Bitrix\Main\DB\SqlExpression('?# + ?i', 'CNT', 1)],
);
$db->queryExecute($sql);
```

There are also `prepareMergeValues` (multiple rows at once), `prepareMergeSelect` (from subquery), `prepareMergeMultiple` (`REPLACE INTO`, splits batches for large bulks).

## SqlExpression — Parameterized Queries

```php
use Bitrix\Main\DB\SqlExpression;

$sql = new SqlExpression(
    'SELECT * FROM ?# WHERE (ID = ?i OR ID > ?f) AND NAME = ?s AND CREATED > ?',
    'b_user',
    1,
    1.23,
    'admin',
    new \Bitrix\Main\Type\Date('01.01.2025'),
);

$db->query($sql);
echo (string)$sql; // compiled SQL
```

Placeholders:

- `?` — auto: strings, numbers, `Date/DateTime`, `null` → `NULL`.
- `?s` — string.
- `?i` — integer.
- `?f` — float.
- `?#` — identifier (table/column name, wrapped in quotes).
- `?v` — `VALUES(...)` for INSERT/UPDATE.

For dates in `Date`/`DateTime` use `?` — you'll get `'2025-01-01 00:00:00'`; `?s` will give string representation in site format.

## Transactions

```php
$db = Application::getConnection();
$db->startTransaction();
try {
    $db->queryExecute('...');
    $db->commitTransaction();
} catch (\Throwable $e) {
    $db->rollbackTransaction();
    throw $e;
}
```

Keep transactions short. ORM operations inside a transaction are supported — use the same connection.

## SqlTracker

Enable SQL query logging for debugging:

```php
$tracker = \Bitrix\Main\Application::getInstance()->getConnectionPool()
    ->getConnection()->startTracker();
// ... queries ...
$queries = $tracker->getQueries();
$tracker->stop();
```

Use only in development.

## PostgreSQL

`PgsqlConnection` is supported (Enterprise for PostgreSQL license). Not all kernel/marketplace modules support PostgreSQL — verify before migration. See skill `bitrix-postgresql`.

## after_connect_d7.php

Place in `/local/php_interface/after_connect_d7.php` for code that must run immediately after DB connection (timezone, session variables, compatibility hooks).
