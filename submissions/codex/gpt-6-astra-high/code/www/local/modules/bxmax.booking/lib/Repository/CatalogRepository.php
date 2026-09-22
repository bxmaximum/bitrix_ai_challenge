<?php declare(strict_types=1);
namespace Bxmax\Booking\Repository;
use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
final class CatalogRepository
{
 public function all(string $code): array
 {
  if (!in_array($code,['services','masters'],true) || !Loader::includeModule('iblock')) return [];
  $ib=IblockTable::getList(['filter'=>['=CODE'=>$code,'=IBLOCK_TYPE_ID'=>'bxmax_booking'],'select'=>['ID','API_CODE'],'cache'=>['ttl'=>3600]])->fetch();
  if (!$ib) return [];
  $class=\Bitrix\Iblock\Iblock::wakeUp((int)$ib['ID'])->getEntityDataClass();
  $select=['ID','NAME','CODE','PREVIEW_TEXT','SORT'];
  $select += $code==='services' ? ['PRICE_VALUE'=>'PRICE.VALUE'] : ['SPECIALTY_VALUE'=>'SPECIALTY.VALUE','PHOTO_VALUE'=>'PHOTO.VALUE'];
  return $class::getList(['select'=>$select,'filter'=>['=ACTIVE'=>'Y'],'order'=>['SORT'=>'ASC','ID'=>'ASC'],'cache'=>['ttl'=>3600,'cache_joins'=>true]])->fetchAll();
 }
 public function find(string $code,int $id): ?array
 {
  foreach ($this->all($code) as $row) if ((int)$row['ID']===$id) return $row;
  return null;
 }
}
