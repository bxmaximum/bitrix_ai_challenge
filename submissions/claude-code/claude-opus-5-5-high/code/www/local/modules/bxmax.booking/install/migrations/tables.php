<?php

use Bitrix\Main\UpdateSystem\Migration;
use Bitrix\Main\UpdateSystem\Migration\CreateTableBuilder;

$migration = Migration::getInstance();

$migration->table('bxmax_booking_slot')->create(function (CreateTableBuilder $table) {
    $table->addId();
    $columns = $table->addColumn();
    $columns->int('MASTER_ID')->notNull();
    $columns->datetime('STARTS_AT')->notNull();
    $columns->datetime('ENDS_AT')->notNull();
    $columns->char('IS_CLOSED', 1)->notNull()->default('N');
    $table->addUniqueIndex('ux_bxmax_booking_slot_master_start', ['MASTER_ID', 'STARTS_AT']);
    $table->addIndex('ix_bxmax_booking_slot_starts', ['STARTS_AT']);
});

$migration->table('bxmax_booking_entry')->create(function (CreateTableBuilder $table) {
    $table->addId();
    $columns = $table->addColumn();
    $columns->int('SLOT_ID')->notNull();
    $columns->int('SERVICE_ID')->notNull();
    $columns->varchar('NAME', 100)->notNull();
    $columns->varchar('PHONE', 20)->notNull();
    $columns->datetime('CONSENT_AT')->notNull();
    $columns->datetime('CREATED_AT')->notNull();
    // Один слот — одна запись: гарантия на уровне БД.
    $table->addUniqueIndex('ux_bxmax_booking_entry_slot', ['SLOT_ID']);
});
