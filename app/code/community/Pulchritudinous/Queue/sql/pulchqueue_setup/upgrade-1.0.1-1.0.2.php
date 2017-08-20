<?php
$this->startSetup();

$tableName  = $this->getTable('pulchqueue/labour');
$adapter    = $this->getConnection();
$fkName     = $this->getFkName(
    $tableName,
    'parent_id',
    'pulchqueue/labour',
    'id'
);

$adapter->dropForeignKey(
    $tableName,
    $fkName
);

$adapter->modifyColumn(
    $tableName,
    'id',
    'BIGINT(20) UNSIGNED COMMENT "ID"'
);

$adapter->modifyColumn(
    $tableName,
    'parent_id',
    'BIGINT(20) UNSIGNED COMMENT "Parent ID"'
);

$adapter->addForeignKey(
    $fkName,
    $tableName,
    'parent_id',
    $this->getTable('pulchqueue/labour'),
    'id',
    Varien_Db_Ddl_Table::ACTION_CASCADE,
    Varien_Db_Ddl_Table::ACTION_CASCADE
);

$this->endSetup();

