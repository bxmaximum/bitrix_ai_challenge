<?php declare(strict_types=1);
namespace Bxmax\Booking\Model;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
final class EntryTable extends DataManager
{
 public static function getTableName(): string { return 'bxmax_booking_entry'; }
 public static function getMap(): array { return [
 (new Fields\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
 (new Fields\IntegerField('SLOT_ID'))->configureRequired(),
 (new Fields\IntegerField('SERVICE_ID'))->configureRequired(),
 (new Fields\StringField('NAME'))->configureRequired()->configureSize(100),
 (new Fields\StringField('PHONE'))->configureRequired()->configureSize(20),
 (new Fields\DatetimeField('CONSENT_AT'))->configureRequired(),
 (new Fields\StringField('CONSENT_VERSION'))->configureDefaultValue('2026-09-22')->configureSize(20),
 (new Fields\Relations\Reference('SLOT',SlotTable::class,['=this.SLOT_ID'=>'ref.ID']))->configureJoinType('INNER'),
 ]; }
}
