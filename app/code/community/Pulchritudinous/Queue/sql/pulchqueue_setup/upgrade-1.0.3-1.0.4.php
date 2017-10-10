<?php
$this->startSetup();

$tableName  = $this->getTable('pulchqueue/labour');
$adapter    = $this->getConnection();

$adapter->addColumn(
    $tableName,
    'by_recurring',
    Varien_Db_Ddl_Table::TYPE_SMALLINT,
    null,
    [
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ],
    'Is created by recurring'
);

$this->endSetup();

