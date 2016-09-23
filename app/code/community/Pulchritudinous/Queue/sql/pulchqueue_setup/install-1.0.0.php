<?php
$installer = $this;
$installer->startSetup();

$tableName = $this->getTable('pulchqueue/labour');

$table = $this->getConnection()
    ->newTable($tableName)
    ->addColumn(
        'id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ],
        'ID'
    )
    ->addColumn(
        'worker',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        255,
        [
            'nullable'  => false,
        ],
        'Worker Code'
    )
    ->addColumn(
        'identity',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        255,
        [
            'nullable'  => false,
        ],
        'Worker Identity'
    )
    ->addColumn(
        'priority',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ],
        'Worker Priority'
    )
    ->addColumn(
        'execute_at',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ],
        'Execute on Unix timestamp'
    )
    ->addColumn(
        'payload',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k',
        [
            'nullable'  => false,
        ],
        'Message'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TEXT,
        255,
        [
            'nullable'  => false,
        ],
        'Status'
    )
    ->addColumn(
        'retries',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ],
        'Retries'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [],
        'Created At'
    )
    ->addColumn(
        'updated_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [],
        'Updated At'
    )
    ->addColumn(
        'started_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [],
        'Updated At'
    )
    ->addColumn(
        'finished_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [],
        'Updated At'
    )
    ->setComment('Worker Queue');

if (!$this->getConnection()->isTableExists($installer->getTable($tableName))) {
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();

