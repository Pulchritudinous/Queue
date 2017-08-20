<?php
$adapter    = $this->getConnection();
$tableName  = $this->getTable('pulchqueue/labour');

$select = $adapter->select()->from(
    $tableName,
    ['id', 'payload']
);

$offset = 0;

while ($result = $adapter->fetchRow($select->limit(1, $offset))) {
    $id         = $result['id'];
    $payload    = @unserialize($result['payload']);
    $newPayload = json_encode((array) $payload);

    $adapter->update(
        $tableName,
        ['payload' => $newPayload],
        $adapter->quoteInto('id = ?', $id)
    );

    $offset++;
}

