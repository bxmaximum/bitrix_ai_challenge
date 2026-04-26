---
name: bitrix-validation
description: "Покрывает валидацию входных данных в Bitrix — ValidationService, атрибуты #[NotEmpty], #[Email], #[Length], #[Range], #[Regex], Request DTO с #[ValidationParameter], кастомные валидаторы на базе ValidatorInterface, агрегация ошибок в ErrorCollection. Применяется при проверке входа контроллеров, сервисов и CLI-команд, валидации форм, DTO и параметров action-методов. Ключевые термины — ValidationService, NotEmpty, Email, Length, ValidationParameter, Request DTO, validator, constraint."
---

# Валидация в Bitrix

Сервис `Bitrix\Main\Validation\ValidationService` валидирует объекты по атрибутам PHP 8. Любой объект с типизированными свойствами можно проверить и получить `ValidationResult` со списком ошибок.

## Атрибуты первого уровня

| Атрибут | Что проверяет |
| --- | --- |
| `#[NotEmpty]` | Не пусто (`!empty`) |
| `#[Length(min, max)]` | Длина строки |
| `#[Min(n)]` / `#[Max(n)]` / `#[Range(min, max)]` | Числовые ограничения |
| `#[PositiveNumber]` | Число > 0 |
| `#[Email]` / `#[Phone]` / `#[PhoneOrEmail]` | Формат |
| `#[Url]` | URL (с опциональными схемами) |
| `#[RegExp('/pattern/')]` | Регулярное выражение |
| `#[Json]` | Строка — валидный JSON |
| `#[Validatable]` | Рекурсивно валидировать вложенный объект |
| `#[ElementsType(Type::class)]` | Тип элементов коллекции/массива |
| `#[AtLeastOnePropertyNotEmpty(['name', 'email'])]` | Минимум одно из полей заполнено (на классе) |

Каждый атрибут принимает необязательный `message` для кастомного текста ошибки.

## DTO с атрибутами

```php
<?php declare(strict_types=1);

namespace Vendor\Module\Application\Dto;

use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\Range;

final class CreateUserDto
{
    public function __construct(
        #[NotEmpty, Length(min: 2, max: 64)]
        public readonly string $name,

        #[NotEmpty, Email]
        public readonly string $email,

        #[Range(min: 18, max: 120)]
        public readonly int $age,
    ) {}
}
```

## Прямая валидация в сервисе

```php
use Bitrix\Main\Validation\ValidationService;

final class UserService
{
    public function __construct(
        private readonly ValidationService $validator,
    ) {}

    public function register(CreateUserDto $dto): \Bitrix\Main\Result
    {
        $result = new \Bitrix\Main\Result();
        $validation = $this->validator->validate($dto);

        if (!$validation->isSuccess())
        {
            foreach ($validation->getErrors() as $error)
            {
                $result->addError(new \Bitrix\Main\Error(
                    $error->getMessage(),
                    $error->getCode(),
                    ['field' => $error->getField()],
                ));
            }
            return $result;
        }

        // ...
        return $result;
    }
}
```

`ValidationService` берётся из `ServiceLocator` по FQCN (зарегистрирован ядром).

## Request DTO в контроллере (`#[ValidationParameter]`)

Движок контроллеров умеет сам создавать DTO из `GET`/`POST` и валидировать его.

```php
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\ValidationParameter;

final class Post extends Controller
{
    public function createAction(
        #[ValidationParameter] CreatePostRequest $request,
    ): array {
        // Сюда мы попадаем, только если валидация прошла успешно.
        // Иначе контроллер вернёт errors автоматически.
        $result = $this->postService->create($request);

        if (!$result->isSuccess())
        {
            $this->addErrors($result->getErrors());
            return [];
        }

        return ['id' => $result->getId()];
    }
}
```

```php
namespace Vendor\Blog\Application\Request;

use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Rule\Length;

final class CreatePostRequest
{
    public function __construct(
        #[NotEmpty, Length(min: 1, max: 255)]
        public readonly string $title,

        public readonly ?string $body = null,
    ) {}
}
```

Генерация: `php bitrix/bitrix.php make:request CreatePost -m vendor.blog --fields=title,body`.

## Коллекции

```php
use Bitrix\Main\Validation\Rule\Validatable;
use Bitrix\Main\Validation\Rule\ElementsType;

final class OrderDto
{
    /**
     * @var OrderItemDto[]
     */
    #[Validatable]
    #[ElementsType(OrderItemDto::class)]
    public array $items = [];
}
```

## Кастомный валидатор

1. Реализуй `Bitrix\Main\Validation\Rule\Rule` + соответствующий валидатор `Bitrix\Main\Validation\ValidatorInterface`:

    ```php
    #[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
    final class EvenNumber implements \Bitrix\Main\Validation\Rule\Rule
    {
        public function __construct(public readonly ?string $message = null) {}
    }

    final class EvenNumberValidator implements \Bitrix\Main\Validation\ValidatorInterface
    {
        public function validate(mixed $value, \Bitrix\Main\Validation\Rule\Rule $rule): \Bitrix\Main\Validation\ValidationResult
        {
            $result = new \Bitrix\Main\Validation\ValidationResult();
            if (!is_int($value) || $value % 2 !== 0)
            {
                $result->addError(new \Bitrix\Main\Validation\ValidationError(
                    $rule->message ?? 'Number must be even',
                    'EVEN_NUMBER',
                ));
            }
            return $result;
        }
    }
    ```

2. Зарегистрируй пару rule→validator в `.settings.php` модуля:

    ```php
    'validation' => [
        'value' => [
            'rules' => [
                \Vendor\Module\Validation\Rule\EvenNumber::class
                    => \Vendor\Module\Validation\Rule\EvenNumberValidator::class,
            ],
        ],
        'readonly' => true,
    ],
    ```

## Получение результата валидации

```php
$validation = $validator->validate($dto);

if ($validation->isSuccess()) { /* ok */ }

foreach ($validation->getErrors() as $error)
{
    $error->getField();    // имя свойства
    $error->getMessage();  // текст
    $error->getCode();     // код правила
    $error->getValue();    // исходное значение
}
```

## Антипаттерны

- Проверки вида `if (empty($request['title']))` в контроллере/сервисе — вместо атрибутов.
- Возврат исключения `InvalidArgumentException` для пользовательской ошибки — возвращай `Result` с `Error`.
- Валидация только на фронте без серверной проверки.
- Дублирование правил в разных местах — вынеси в общий DTO и переиспользуй.

## Чек-лист

- [ ] Пользовательский ввод контроллера описан Request-DTO и валидируется через `#[ValidationParameter]`.
- [ ] Сервисы, принимающие DTO от других слоёв, сами не валидируют тот же DTO — валидация выполняется один раз на границе.
- [ ] Ошибки содержат `code` (`EMAIL_INVALID`, `POST_TITLE_EMPTY`), пригодный для фронта.
- [ ] Кастомные правила зарегистрированы в `.settings.php` и покрыты юнит-тестом.
