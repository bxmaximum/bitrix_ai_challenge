<?php declare(strict_types=1);

use Bitrix\Main\UpdateSystem\Migration;
use Bitrix\Main\UpdateSystem\Migration\CreateTableBuilder;

$migration = Migration::getInstance();

$migration->table('bxmax_booking_slot')->create(static function (CreateTableBuilder $table): void {
    $table->addId();
    $columns = $table->addColumn();
    $columns->int('MASTER_ID')->notNull();
    $columns->datetime('STARTS_AT')->notNull();
    $columns->datetime('ENDS_AT')->notNull();
    $columns->char('IS_CLOSED', 1)->notNull()->default('N');
    $columns->datetime('CREATED_AT')->notNull();

    $table->addUniqueIndex('ux_bxmax_booking_slot_master_start', ['MASTER_ID', 'STARTS_AT']);
    $table->addIndex('ix_bxmax_booking_slot_starts', ['STARTS_AT']);
});

$migration->table('bxmax_booking_entry')->create(static function (CreateTableBuilder $table): void {
    $table->addId();
    $columns = $table->addColumn();
    $columns->int('SLOT_ID')->notNull();
    $columns->int('SERVICE_ID')->notNull();
    $columns->varchar('NAME', 255)->notNull();
    $columns->varchar('PHONE', 64)->notNull();
    $columns->datetime('CONSENT_AT')->notNull();
    $columns->datetime('CREATED_AT')->notNull();

    $table->addUniqueIndex('ux_bxmax_booking_entry_slot', ['SLOT_ID']);
    $table->addIndex('ix_bxmax_booking_entry_service', ['SERVICE_ID']);
});
