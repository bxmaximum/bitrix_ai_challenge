<?php

declare(strict_types=1);

namespace Bxmax\Booking\Controller;

/**
 * Чтение входных параметров действия: query/form, а при JSON-теле — его верхнеуровневые ключи.
 */
trait InputTrait
{
    private function input(string $key): mixed
    {
        $request = $this->getRequest();

        $value = $request->get($key);
        if ($value === null)
        {
            $value = $request->getJsonList()->get($key);
        }

        return is_array($value) ? null : $value;
    }
}
