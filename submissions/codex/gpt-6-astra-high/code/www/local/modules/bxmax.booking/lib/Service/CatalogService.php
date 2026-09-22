<?php declare(strict_types=1);
namespace Bxmax\Booking\Service;
use Bxmax\Booking\Repository\CatalogRepository;
final class CatalogService
{
 public function __construct(private readonly CatalogRepository $catalog) {}
 public function content(): array { return ['SERVICES'=>$this->catalog->all('services'),'MASTERS'=>$this->catalog->all('masters')]; }
}
