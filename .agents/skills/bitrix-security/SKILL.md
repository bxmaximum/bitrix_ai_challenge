---
name: bitrix-security
description: Покрывает безопасность в Bitrix — CSRF-токены и фильтр Csrf, SSRF-защита в HttpClient, SQL-инъекции (в том числе через ORM select/filter/SqlExpression/runtime/ExpressionField), XSS через htmlspecialcharsbx, проверка прав доступа пользователей и групп, шифрование полей (CryptoField, Cipher). Применяется при обработке пользовательского ввода, проектировании API, админских действий, аудите кода и работе с персональными данными. Ключевые термины — CSRF, XSS, SSRF, SQL injection, htmlspecialcharsbx, CryptoField, Cipher, permissions, access rights.
---

# Безопасность в Bitrix

## CSRF

### Защита форм/AJAX

- Фильтр `Bitrix\Main\Engine\ActionFilter\Csrf` включён по умолчанию для `POST`-действий контроллеров. Его отключать — только для осознанных случаев (публичный webhook со своей верификацией).
- В HTML-формах:

    ```php
    <?= bitrix_sessid_post() ?> <!-- <input type="hidden" name="sessid" value="..."> -->
    ```

- В запросах `fetch`: заголовок `X-Bitrix-Csrf-Token: <bitrix_sessid()>`.
- Ручная проверка (если пишешь обработчик напрямую): `if (!check_bitrix_sessid()) { die('Invalid sessid'); }`.

### Когда сессии readonly

Если включён `CloseSession` (через фильтр), CSRF-токен ведёт себя как обычно — ядро читает его из запроса, а не из сессии.

### Антипаттерны

- `GET`-эндпоинт, который меняет состояние, без CSRF-токена и проверок.
- Собственное поле `sessid` в форме без `bitrix_sessid_post()`.

## SSRF

- Не обращайся к URL из пользовательского ввода напрямую: `file_get_contents($url)`, `curl` c юзерскими хостами.
- Используй `Bitrix\Main\Web\HttpClient` с явным белым списком схем/хостов и таймаутами:

    ```php
    $client = new \Bitrix\Main\Web\HttpClient([
        'socketTimeout' => 5,
        'streamTimeout' => 10,
        'redirect' => false,
        'disableSslVerification' => false,
    ]);
    ```

- Запрещай локальные адреса (`127.0.0.1`, `169.254.*`, внутренние IP) перед запросом.
- Для вебхуков от пользователя — валидируй хост/схему/порт, подписывай запросы секретом.

## SQL-инъекции

### Сырой SQL (старое ядро)

```php
$conn = \Bitrix\Main\Application::getConnection();
$helper = $conn->getSqlHelper();

$id = (int)$userInput; // для целочисленных — принудительное приведение
$login = $helper->forSql($userLogin); // экранирование строк

$conn->queryExecute("UPDATE b_user SET LOGIN = '{$login}' WHERE ID = {$id}");
```

Для массовых вставок/обновлений:

```php
[$insertFields, $insertValues] = $helper->prepareInsert('b_user', $fields);
$conn->queryExecute("INSERT INTO b_user ({$insertFields}) VALUES ({$insertValues})");

$update = $helper->prepareUpdate('b_user', $fields);
$conn->queryExecute("UPDATE b_user SET {$update[0]} WHERE ID = {$id}", $update[1]);
```

### ORM-запросы — тоже могут быть уязвимы

Опасные места в `getList`/`query()`:

- `select` и `order` — имена полей **не экранируются**. Никогда не клади туда «имя поля из запроса» без белого списка:

    ```php
    $allowedOrder = ['ID', 'CREATED_AT', 'TITLE'];
    $order = in_array(strtoupper($userOrder), $allowedOrder, true) ? strtoupper($userOrder) : 'ID';

    PostTable::getList(['order' => [$order => 'DESC']]);
    ```

- `filter` — значения параметризуются, но **ключи** (имена полей с префиксом `=`, `>`, ) — нет. Тоже белый список.
- `SqlExpression` и `ExpressionField` — второй аргумент подставляется как есть. Никогда не собирай его из пользовательского ввода:

    ```php
    // ОПАСНО:
    new \Bitrix\Main\DB\SqlExpression("IF({$userField} = 1, 'a', 'b')");

    // Безопасно:
    new \Bitrix\Main\DB\SqlExpression('IF(?# = 1, "a", "b")', $userField);
    ```

- `runtime`-поля — те же правила.

## XSS

- Выводи всё через `htmlspecialcharsbx($value)`.
- В шаблонах — `<?= htmlspecialcharsbx($item['TITLE']) ?>`.
- Для данных, которые должны содержать HTML, используй `\Bitrix\Main\Text\HtmlFilter::encode`/`decode` или `CBXSanitizer` с белым списком тегов.
- JS-данные передавай через `\Bitrix\Main\Web\Json::encode($data)` + `<script>BX.message({...})</script>` вместо прямой конкатенации.
- `arResult` в шаблоне компонента по умолчанию не экранируется — экранируй сам.

## Права доступа

### Базовые проверки

```php
global $USER;

if (!$USER->IsAuthorized()) { return; }
if (!$USER->IsAdmin()) { /* ... */ }

if (!$USER->CanDoOperation('edit_own_profile')) { /* ... */ }
```

### Модульные права

```php
$module = 'vendor.blog';
$rights = \CMain::GetUserRight($module, $USER->GetUserGroupArray());
if ($rights < 'W') { /* ... */ }
```

### Проверки в контроллере

Используй `ActionFilter\Authentication` и собственный фильтр на основе `Bitrix\Main\Engine\ActionFilter\Base`. Пример:

```php
final class RequireRole extends \Bitrix\Main\Engine\ActionFilter\Base
{
    public function __construct(private readonly string $role) { parent::__construct(); }

    public function onBeforeAction(\Bitrix\Main\Event $event)
    {
        global $USER;
        if (!$USER->IsAuthorized() || !in_array($this->role, $USER->GetUserGroupArray(), true))
        {
            $this->errorCollection->add([new \Bitrix\Main\Error('Forbidden', 'ACCESS_DENIED')]);
            return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::ERROR, null, null, $this);
        }
        return null;
    }
}
```

### Модуль `access`

Для сложной ACL — используй модуль `access`, роли и провайдеры прав (`Access\Role`, `Access\AccessibleItem`).

## Безопасные куки

```php
$response = \Bitrix\Main\Context::getCurrent()->getResponse();
$cookie = new \Bitrix\Main\Web\Cookie('VENDOR_TOKEN', $token, time() + 86400);
$cookie->setHttpOnly(true);
$cookie->setSecure(true);
$cookie->setSpread(\Bitrix\Main\Web\Cookie::SPREAD_DOMAIN); // если нужно на все поддомены
$response->addCookie($cookie);
```

Используй `HttpOnly` + `Secure` + `SameSite=Lax/Strict`. Не клади токены доступа в `localStorage`.

## Шифрование значений

- `CryptoField('SECRET')` — поле таблета, шифруется прозрачно.
- `SecretField('TOKEN')` — не возвращается при `select = '*'`.
- Собственное шифрование: `Bitrix\Main\Security\Cipher`.

## Прочее

- `proactive` firewall ядра проверяет подозрительные параметры — не отключай без крайней необходимости.
- `two-factor-auth` для админов — включено по умолчанию, оставь.
- Храни секреты в `.settings_extra.php` и переменных окружения, **не** в `.settings.php` под git.

## Чек-лист

- [ ] Все `POST`-эндпоинты защищены `Csrf` и/или `sessid`.
- [ ] Внешние URL из юзер-ввода проходят валидацию хостов, используется `HttpClient` с таймаутами.
- [ ] В ORM-запросах имена полей и операторы берутся из белого списка, а не из запроса.
- [ ] В `SqlExpression`/`ExpressionField`/`runtime` нет конкатенации с пользовательским вводом.
- [ ] В шаблонах всё, что идёт от пользователя — через `htmlspecialcharsbx`.
- [ ] Административные действия проверяют `$USER->IsAdmin()` или конкретные `CanDoOperation`.
- [ ] Куки с токенами — `HttpOnly`, `Secure`, `SameSite`.
- [ ] Секреты не коммитятся; доступ к `.settings_extra.php` закрыт.
