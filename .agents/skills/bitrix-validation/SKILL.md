---
name: bitrix-validation
description: "Covers input data validation in Bitrix — ValidationService, attributes #[NotEmpty], #[Email], #[Length], #[Range], #[Regex], Request DTO with #[ValidationParameter], custom validators based on ValidatorInterface, aggregation of errors in ErrorCollection. Applied when checking input of controllers, services and CLI commands, validation of forms, DTO and action method parameters. Key terms — ValidationService, NotEmpty, Email, Length, ValidationParameter, Request DTO, validator, constraint."
---

# Validation in Bitrix

The `Bitrix\Main\Validation\ValidationService` service validates objects using PHP 8 attributes. Any object with typed properties can be checked to obtain a `ValidationResult` with a list of errors.

## First-level Attributes

| Attribute | What it checks |
| --- | --- |
| `#[NotEmpty]` | Not empty (`!empty`) |
| `#[Length(min, max)]` | String length |
| `#[Min(n)]` / `#[Max(n)]` / `#[Range(min, max)]` | Numeric constraints |
| `#[PositiveNumber]` | Number > 0 |
| `#[Email]` / `#[Phone]` / `#[PhoneOrEmail]` | Format |
| `#[Url]` | URL (with optional schemes) |
| `#[RegExp('/pattern/')]` | Regular expression |
| `#[Json]` | String is valid JSON |
| `#[Validatable]` | Recursively validate nested object |
| `#[ElementsType(Type::class)]` | Type of collection/array elements |
| `#[AtLeastOnePropertyNotEmpty(['name', 'email'])]` | At least one of the fields is filled (on class) |

Each attribute accepts an optional `message` for a custom error text.

## DTO with Attributes

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

## Direct Validation in Service

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

`ValidationService` is retrieved from `ServiceLocator` by FQCN (registered by the kernel).

## Request DTO in Controller (`#[ValidationParameter]`)

The controller engine can automatically create a DTO from `GET`/`POST` and validate it.

```php
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\ValidationParameter;

final class Post extends Controller
{
    public function createAction(
        #[ValidationParameter] CreatePostRequest $request,
    ): array {
        // We only get here if validation was successful.
        // Otherwise, the controller will return errors automatically.
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

Generation: `php bitrix/bitrix.php make:request CreatePost -m vendor.blog --fields=title,body`.

## Class-Level Attributes

```php
use Bitrix\Main\Validation\Rule\AtLeastOnePropertyNotEmpty;

#[AtLeastOnePropertyNotEmpty(['email', 'phone'])]
final readonly class ContactRequest
{
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
```

## Collections

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

## Custom Validator

1. Implement `Bitrix\Main\Validation\Rule\Rule` + corresponding validator `Bitrix\Main\Validation\ValidatorInterface`:

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

2. Register the rule→validator pair in the module's `.settings.php`:

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

## Retrieving Validation Result

The `ValidationResult` object contains a list of `ValidationError`. Each error has:
- `getMessage()`: localized message.
- `getCode()`: error code (e.g., `NOT_EMPTY`).
- `getField()`: property name that failed validation.
- `getRule()`: rule instance.

## Checklist

- [ ] Validation is handled via PHP 8 attributes.
- [ ] DTOs are used for complex input structures.
- [ ] `#[ValidationParameter]` is used in controllers to automate DTO creation and validation.
- [ ] Custom rules and validators are registered in `.settings.php`.
- [ ] Error messages are localized or descriptive.
- [ ] `ValidationService` is retrieved from `ServiceLocator`.
- [ ] Collections are validated recursively using `#[Validatable]` and `#[ElementsType]`.
