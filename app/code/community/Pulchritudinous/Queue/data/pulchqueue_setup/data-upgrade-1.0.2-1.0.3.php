<?php
$adapter    = $this->getConnection();
$tableName  = $this->getTable('pulchqueue/labour');

$select = $adapter->select()
    ->from(
        $tableName,
        ['id', 'payload']
    )
    ->where('status = ?', Pulchritudinous_Queue_Model_Labour::STATUS_PENDING);

$offset = 0;

while ($result = $adapter->fetchRow($select->limit(1, $offset))) {
    $id         = $result['id'];
    $payload    = @unserialize($result['payload']);
    $arrData    = Mage::helper('pulchqueue')->objectToArray((array) $payload);
    $newPayload = json_encode($arrData);

    $adapter->update(
        $tableName,
        ['payload' => $newPayload],
        $adapter->quoteInto('id = ?', $id)
    );

    $offset++;
}

