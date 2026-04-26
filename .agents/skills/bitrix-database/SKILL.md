---
name: bitrix-database
description: Покрывает прямую работу с базой Bitrix — Application::getConnection(), Connection, MysqliConnection, SqlHelper, SqlExpression, сырые SQL-запросы через query()/queryExecute()/queryScalar(), транзакции (startTransaction/commitTransaction/rollbackTransaction), DDL и миграции схем, bulk-операции (insertBatch, addMulti), дополнительные подключения через секцию connections в .settings.php. Применяется когда ORM недостаточно — массовые операции, raw SQL, миграции, работа с внешними базами и построение кастомных запросов. Ключевые термины — Connection, SqlHelper, SqlExpression, transaction, raw SQL, bulk insert, DDL, migration.
---

# Работа с БД напрямую

ORM — первая линия выбора (`bitrix-orm`). Прямой SQL нужен для:

- миграций/DDL в `install/index.php` / `updater.php`,
- массовых операций (`UPSERT`, `REPLACE`, окна/CTE),
- отчётов с `GROUP BY`/агрегатами, которые громоздко собирать через ORM,
- работы с несколькими соединениями (аналитическая реплика, Redis).

## Соединение

```php
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;

/** @var Connection $db */
$db = Application::getConnection();           // default
$db = Application::getConnection('default');
$analytics = Application::getConnection('analytics'); // дополнительный
```

## Конфигурация в `.settings.php`

```php
'connections' => [
    'value' => [
        'default' => [
            'className' => \Bitrix\Main\DB\MysqliConnection::class,
            'host'      => 'db',
            'database'  => 'bx',
            'login'     => 'bx',
            'password'  => '***',
            'options'   => \Bitrix\Main\DB\Connection::DEFERRED, // 2 — коннект при первом запросе
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

`options`: `Connection::PERSISTENT = 1`, `Connection::DEFERRED = 2`, комбинируются битовой ИЛИ (`3`).

Классы:

- `\Bitrix\Main\DB\MysqliConnection` — MySQL (`mysqli`).
- `\Bitrix\Main\DB\PgsqlConnection` — PostgreSQL.
- `\Bitrix\Main\DB\MssqlConnection`, `\Bitrix\Main\DB\OracleConnection` — редко.
- `\Bitrix\Main\Data\MemcacheConnection`, `MemcachedConnection`, `RedisConnection`.
- `\Bitrix\Main\Data\HsphpReadConnection` — HandlerSocket (read-only, для высоконагруженных `SELECT` по первичному ключу в обход SQL).

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

- `fetch()` — значения **прогоняются через конвертеры полей** (дата → `Bitrix\Main\Type\DateTime`).
- `fetchRaw()` — как пришло из драйвера.
- `$result->getSelectedRowsCount()`, `$result->getFields()`, `$result->getResource()` (низкоуровневый `mysqli_result`).

Важно: `Result` нельзя «перемотать» — если нужен повторный проход, материализуй в массив.

### Пользовательские конвертеры

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
    'CONTENT' => $raw,              // экранируется автоматически
]);

$lastId = $db->addMulti('my_table', [
    ['NAME' => 'a', 'CONTENT' => '1'],
    ['NAME' => 'b', 'CONTENT' => '2'],
]);

$db->queryExecute(
    'UPDATE my_table SET NAME = "' . $db->getSqlHelper()->forSql($name) . '" WHERE ID = ' . (int)$id
);
```

`add`/`addMulti` молча **отбрасывают ключи** с несуществующими колонками и сами экранируют значения. Удобно для фикстур и миграций.

> ВАЖНО: параметр `$binds` в `query/queryScalar/queryExecute` **не** делает подготовленные выражения — это лишь плейсхолдеры для LOB'ов у некоторых драйверов. От SQL-инъекций защищайся через `SqlExpression` или `SqlHelper`.

## SqlHelper — экранирование и утилиты

```php
$h = $db->getSqlHelper();

$h->quote('table.id');            // `table`.`id`
$h->forSql($userInput);           // не " безопасная ' → не \" безопасная \'
$h->convertToDb($value);          // 'v' | 'NULL' | '123'
$h->convertToDbString(null);      // ''
$h->convertToDbString('long', 5); // 'long '  (обрезано)
$h->convertToDbInteger('x');      // 0
$h->convertToDbInteger(1e10, 4);  // 2147483647 — ограничение 4 байта
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

Аргументы SQL-функций **не экранируются автоматически** — пропускай через `quote`/`convertToDb` сам.

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

Есть ещё `prepareMergeValues` (сразу много строк), `prepareMergeSelect` (из подзапроса), `prepareMergeMultiple` (`REPLACE INTO`, делит пачки для больших bulk'ов).

## SqlExpression — параметризованные запросы

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
echo (string)$sql; // скомпилированный SQL
```

Плейсхолдеры:

- `?` — авто: строки, числа, `Date/DateTime`, `null` → `NULL`.
- `?s` — строка.
- `?i` — целое.
- `?f` — float.
- `?#` — идентификатор (имя таблицы/колонки, обёрнуто в кавычки).
- `?v` — `VALUES(...)` для INSERT/UPDATE.

Для дат в `Date`/`DateTime` используй `?` — получишь `'2025-01-01 00:00:00'`; `?s` даст строковое представление в формате сайта.

## Транзакции

```php
$db = Application::getConnection();

try
{
    $db->startTransaction();

    $db->queryExecute("UPDATE b_user SET ACTIVE = 'N' WHERE ID = " . (int)$id);
    \Bitrix\Main\UserTable::update($id, ['LAST_LOGIN' => null]);

    $db->commitTransaction();
}
catch (\Throwable $e)
{
    $db->rollbackTransaction();
    throw $e;
}
```

Правила:

- Держи транзакции **короткими**. Никакого HTTP или долгой логики внутри.
- ORM-операции участвуют в транзакции того же соединения. Если у `DataManager` переопределено `getConnectionName` — следи, что транзакция открыта на **том же** соединении.

### Вложенные транзакции

Вложенные `startTransaction` создают `SAVEPOINT`. `commitTransaction` внутренней — ничего не коммитит в БД. `rollbackTransaction` внутренней — откат до точки + `TransactionException`.

Рекомендуемый паттерн — **не откатывать внутри** вложенной:

```php
try
{
    $db->startTransaction();

    try { updateOrders($id, $db); }
    catch (\Throwable $e) { $db->rollbackTransaction(); throw $e; }

    try { updateAccounts($id, $db); }
    catch (\Throwable $e) { $db->rollbackTransaction(); throw $e; }

    $db->commitTransaction();
}
catch (\Bitrix\Main\DB\TransactionException $e)
{
    $db->rollbackTransaction();  // гарантированный глобальный rollback
    throw $e;
}
```

## DDL (в `install/index.php`, `updater.php`, командах миграций)

```php
use Bitrix\Main\ORM\Fields;

$db->createTable('vendor_module_post', [
    'ID'         => new Fields\IntegerField('ID',         ['primary' => true, 'autocomplete' => true]),
    'TITLE'      => new Fields\StringField('TITLE',       ['size' => 255]),
    'CREATED_AT' => new Fields\DatetimeField('CREATED_AT'),
]);

$db->createPrimaryIndex('vendor_module_post', ['ID']);
$db->createIndex('ix_post_title', 'vendor_module_post', ['TITLE'], unique: false);

$db->dropColumn('vendor_module_post', 'TITLE');
$db->renameTable('old', 'new');
$db->truncateTable('vendor_module_post');
$db->dropTable('vendor_module_post');

if (!$db->isTableExists('vendor_module_post')) { /* ... */ }
$fields = $db->getTableFields('vendor_module_post');
```

Предпочтительно вызывать `getEntity()->createDbTable()` для классов ORM — они знают свою схему:

```php
\Vendor\Module\Data\PostTable::getEntity()->createDbTable();
```

## Key-value соединения

```php
/** @var \Bitrix\Main\Data\RedisConnection $redis */
$redis = Application::getConnection('redis');
$handle = $redis->getResource();   // нативный \Redis — используй его API
$handle->set('k', 'v', 60);
```

Memcache/Memcached аналогично: `MemcacheConnection`/`MemcachedConnection` с полями `host`/`port` (или массив `servers` для кластера) и опцией `persistent`.

## Антипаттерны

- Конкатенация пользовательских значений в SQL без `forSql`/`SqlExpression`. 
- `query($sql, $binds)` с ожиданием подготовленных выражений — этот `binds` не защищает от инъекций.
- `add('my_table', $_POST)` — прилетят произвольные колонки (метод их отбросит) и строковые значения с управляющими байтами без дополнительной валидации.
- Транзакция, охватывающая отправку HTTP/ожидание внешнего API → блокировка таблиц на секунды.
- Кеш вокруг `query()` без инвалидации по тегам — собирает «устаревший суп».
- Прямой доступ к `mysqli`/`\Redis` через `$db->getResource()` без необходимости — ломается на смене драйвера.

## Чек-лист

- [ ] Пользовательский ввод → только через `SqlExpression` (`?s`, `?i`, `?#`) или `SqlHelper::forSql/convertToDb`.
- [ ] В `.settings.php` подключения имеют `options => Connection::DEFERRED`, секция `readonly`.
- [ ] Сырые `query()` применяются только там, где ORM неудобен; в репозиториях — инкапсулированы.
- [ ] Транзакции держатся короткими и обёрнуты в `try/finally` с `rollbackTransaction`.
- [ ] UPSERT — через `prepareMerge*`, а не конкатенация `ON DUPLICATE KEY`.
- [ ] Bulk-операции — `addMulti` / `prepareMergeMultiple`, а не циклы с `INSERT` по одной.
- [ ] DDL в установке/обновлении — через `DataManager::getEntity()->createDbTable()` или `Connection::createTable`, а не сырой `CREATE TABLE`.
