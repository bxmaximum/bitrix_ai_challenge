<?php

declare(strict_types=1);

namespace Bxmax\Booking\Application\Service;

use Bxmax\Booking\Domain\Repository\CatalogRepositoryInterface;

final class CatalogService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    public function getServices(): array
    {
        return $this->catalog->getServices();
    }

    public function getMasters(): array
    {
        return $this->catalog->getMasters();
    }
}
