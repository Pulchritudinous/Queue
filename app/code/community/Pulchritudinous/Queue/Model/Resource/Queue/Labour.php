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
 * Labour resources model.
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
     * Load labour by worker code and identity.
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
            ->from($this->getMainTable())
            ->where('worker = :worker')
            ->where('identity = :identity');

        $bind = [
            ':worker'   => (string) $worker,
            ':identity' => (string) $identity,
        ];

        $result = $adapter->fetchRow($select, $bind);

        if ($result) {
            $labour->setData($result);
        }

        return $this;
    }

    /**
     * Set status to a unprocessed labour by worker and identity.
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
            'status' => (string) $status,
        ];

        $result = $adapter->update(
            $this->getMainTable(),
            $data,
            [
                'status = ?'    => Pulchritudinous_Queue_Model_Labour::STATUS_PENDING,
                'worker = ?'    => (string) $worker,
                'identity = ?'  => (string) $identity,
            ]
        );

        return $result;
    }

    /**
     * Update single field to labour.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  string                             $field
     * @param  string                             $value
     *
     * @return boolean
     */
    public function updateField(Pulchritudinous_Queue_Model_Labour $labour, $field, $value)
    {
        $adapter    = $this->_getWriteAdapter();
        $data       = [
            $field => $value,
        ];

        $result = $adapter->update(
            $this->getMainTable(),
            $data,
            [
                'id' => $labour->getId(),
            ]
        );

        if ($result) {
            $labour->setData($field, $value);
        }

        return $result;
    }

    /**
     * Checks if labour exists in queue by worker code and identity.
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
            ->where('status = :status');

        $bind = [
            ':worker'   => (string) $worker,
            ':identity' => (string) $identity,
            ':status'   => 'pending',
        ];

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Prepare data for save.
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

        $object->unsetData('pid');
        $object->setUpdatedAt(now());

        return parent::_prepareDataForSave($object);
    }
}

