<?php
$this->startSetup();

$tableName  = $this->getTable('pulchqueue/labour');
$adapter    = $this->getConnection();

$adapter->changeColumn(
    $tableName,
    'execute_at',
    'execute_at',
    'int(10) unsigned DEFAULT NULL COMMENT "Execute at"'
);

$adapter->changeColumn(
    $tableName,
    'created_at',
    'created_at',
    'int(10) unsigned DEFAULT NULL COMMENT "Created at"'
);

$adapter->changeColumn(
    $tableName,
    'updated_at',
    'updated_at',
    'int(10) unsigned DEFAULT NULL COMMENT "Updated at"'
);

$adapter->changeColumn(
    $tableName,
    'started_at',
    'started_at',
    'int(10) unsigned DEFAULT NULL COMMENT "Started at"'
);

$adapter->changeColumn(
    $tableName,
    'finished_at',
    'finished_at',
    'int(10) unsigned DEFAULT NULL COMMENT "Finished at"'
);

$adapter->changeColumn(
    $tableName,
    'retries',
    'attempts',
    'int(10) unsigned DEFAULT NULL COMMENT "Attempt"'
);

$this->endSetup();

