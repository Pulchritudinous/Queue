<?php
$this->startSetup();

$tableName  = $this->getTable('pulchqueue/labour');
$adapter    = $this->getConnection();

$adapter->addColumn(
    $tableName,
    'by_recurring',
    [
        'type'      => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'nullable'  => false,
        'unsigned'  => true,
        'default'   => '0',
        'after'     => 'pid',
        'comment'   => 'Is created by recurring'
    ]
);

$this->endSetup();

