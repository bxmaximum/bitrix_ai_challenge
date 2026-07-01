---
name: bitrix-postgresql
description: Covers PostgreSQL support in Bitrix — PgsqlConnection, migration from MySQL, compatible code, module support matrix. Applied when configuring or migrating to PostgreSQL Enterprise editions. Key terms — PostgreSQL, PgsqlConnection, migration, compatible-code.
---

# PostgreSQL in Bitrix

Supported in **Enterprise for PostgreSQL** licenses (B24 and CMS). Connection class: `\Bitrix\Main\DB\PgsqlConnection`.

## Configuration

```php
'connections' => [
    'value' => [
        'default' => [
            'className' => \Bitrix\Main\DB\PgsqlConnection::class,
            'host' => 'localhost',
            'database' => 'bx',
            'login' => 'bx',
            'password' => '***',
            'options' => \Bitrix\Main\DB\Connection::DEFERRED,
        ],
    ],
    'readonly' => true,
],
```

## Before Migration

1. Obtain Enterprise for PostgreSQL license (test key available for 6 months).
2. Update **Performance Monitor** module to 24.0.0+.
3. Project must use UTF-8 encoding.
4. Close site to visitors during migration.
5. Test on staging first — **return to MySQL after production PostgreSQL launch requires manual work**.

## Module Compatibility

Not all kernel and marketplace modules support PostgreSQL. Incompatible modules are disabled during conversion wizard.

Check custom code:
- MySQL-specific SQL (`LIMIT` syntax differences handled by SqlHelper, but raw SQL may break).
- `install/mysql/` vs `install/pgsql/` — modules need pgsql install scripts.

Find modules missing pgsql install:

```bash
for mysql in bitrix/modules/*/install/mysql/install.sql bitrix/modules/*/install/db/mysql/install.sql; do
  pgsql=$(echo $mysql | sed 's#/mysql/#/pgsql/#')
  test -e $pgsql || echo "missing: $pgsql"
done
```

Check kernel module install folders: each module should have matching `install/pgsql/` scripts if it supports PostgreSQL. Inspect `bitrix/modules/<module>/install/` in the project.

## Migration Methods

1. **Wizard** — Admin conversion tool (lists disabled modules on step 1).
2. **CLI** — manual server-side migration via Performance Monitor module tools.

## Writing Compatible Code

- Use ORM and `SqlHelper` — avoid MySQL-specific functions in raw SQL.
- Use `SqlExpression` placeholders instead of string concatenation.
- Test DDL in both `install/mysql/` and `install/pgsql/` if module supports both.
- Avoid `ENGINE=InnoDB`, backticks-specific syntax, `UNSIGNED`.

## Checklist

- [ ] License is Enterprise for PostgreSQL.
- [ ] All custom modules checked for pgsql install scripts.
- [ ] Raw SQL audited for MySQL-specific syntax.
- [ ] Migration tested on copy before production.
- [ ] Marketplace modules verified with vendors.
