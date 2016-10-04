<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2016 Pulchritudinous
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
?>
<?php
/**
 *
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Resource_Queue_Labour
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initial configuration.
     */
    protected function _construct()
    {
        $this->_init('pulchqueue/labour', 'id');
    }

    /**
     *
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  string                             $worker
     * @param  string                             $identity
     *
     * @return Pulchritudinous_Queue_Model_Resource_Labour
     */
    public function loadByWorkerIdentity(Pulchritudinous_Queue_Model_Labour $labour, $worker, $identity)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('worker = :worker')
            ->where('identity = :identity');

        $bind = [
            ':worker'   => (string) $worker,
            ':identity' => (string) $identity,
        ];

        $result = $adapter->fetchOne($select, $bind);

        if ($result) {
            $labour->setData($result);
        }

        return $this;
    }

    /**
     *
     *
     * @param  string $status
     * @param  string $worker
     * @param  string $identity
     *
     * @return boolean
     */
    public function setStatusOnUnprocessedByWorkerIdentity($status, $worker, $identity)
    {
        $adapter = $this->_getWriteAdapter();

        $data = [
            'status'   => (string) $status,
            'worker'   => (string) $worker,
            'identity' => (string) $identity,
        ];

        $result = $adapter->update(
            $this->getMainTable(),
            $data,
            [
                'running'  => false,
            ]
        );

        return $result;
    }

    /**
     *
     *
     * @param  string $worker
     * @param  string $identity
     *
     * @return boolean
     */
    public function hasUnprocessedWorkerIdentity($worker, $identity)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable(), 'id')
            ->where('worker = :worker')
            ->where('identity = :identity')
            ->where('running = :running');

        $bind = [
            ':worker'   => (string) $worker,
            ':identity' => (string) $identity,
            ':running'  => false,
        ];

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Prepare data for save
     *
     * @param Mage_Core_Model_Abstract $object
     *
     * @return array
     */
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getId()) {
            $object->setCreatedAt(now());
        }

        $object->setUpdatedAt(now());

        return parent::_prepareDataForSave($object);
    }
}

