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
        'parent_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned'  => true,
            'nullable'  => true,
        ],
        'Parent ID'
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
        'pid',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        [
            'unsigned'  => true,
            'nullable'  => true,
        ],
        'PID'
    )
    ->addColumn(
        'execute_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable'  => false,
        ],
        'Time to execute'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable'  => false,
        ],
        'Created At'
    )
    ->addColumn(
        'updated_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable'  => false,
        ],
        'Updated At'
    )
    ->addColumn(
        'started_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable'  => true,
        ],
        'Updated At'
    )
    ->addColumn(
        'finished_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        [
            'nullable'  => true,
        ],
        'Updated At'
    )
    ->addIndex(
        $this->getIdxName(
            $tableName,
            ['parent_id']
        ),
        ['parent_id']
    )
    ->addIndex(
        $this->getIdxName(
            $tableName,
            ['worker']
        ),
        ['worker']
    )
    ->addIndex(
        $this->getIdxName(
            $tableName,
            ['identity']
        ),
        ['identity']
    )
    ->addForeignKey(
        $this->getFkName(
            $tableName,
            'parent_id',
            'pulchqueue/labour',
            'id'
        ),
        'parent_id',
        $this->getTable('pulchqueue/labour'),
        'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE,
        Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Worker Queue');

if (!$this->getConnection()->isTableExists($installer->getTable($tableName))) {
    $installer->getConnection()->createTable($table);
}

$installer->endSetup();

