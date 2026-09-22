<?php declare(strict_types=1);
namespace Bxmax\Booking\Model;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
final class SlotTable extends DataManager
{
 public static function getTableName(): string { return 'bxmax_booking_slot'; }
 public static function getMap(): array { return [
 (new Fields\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
 (new Fields\IntegerField('MASTER_ID'))->configureRequired(),
 (new Fields\DatetimeField('STARTS_AT'))->configureRequired(),
 (new Fields\DatetimeField('ENDS_AT'))->configureRequired(),
 (new Fields\BooleanField('CLOSED'))->configureValues('N','Y')->configureDefaultValue('N'),
 ]; }
}
