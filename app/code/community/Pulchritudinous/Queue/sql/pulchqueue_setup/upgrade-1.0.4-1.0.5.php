<?php
$this->startSetup();

$tableName  = $this->getTable('pulchqueue/labour');
$adapter    = $this->getConnection();

$adapter->addColumn(
    $tableName,
    'batch',
    [
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 255,
        'nullable'  => true,
        'after'     => 'parent_id',
        'comment'   => 'Batch ID'
    ]
);

$adapter->dropColumn(
    $tableName,
    'parent_id'
);

$this->endSetup();

