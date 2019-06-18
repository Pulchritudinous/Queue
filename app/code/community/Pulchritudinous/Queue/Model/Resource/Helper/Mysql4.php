<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Pulchritudinous
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
 * Resources helper model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Resource_Helper_Mysql4
    extends Mage_Core_Model_Resource_Helper_Mysql4
    implements Pulchritudinous_Queue_Model_Lock_Interface
{
    /**
     * Set lock.
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function setLock($name)
    {
        return (boolean) $this->getWriteAdapter()->query(
            'SELECT GET_LOCK(?, ?);',
            [$name, self::LOCK_GET_TIMEOUT]
        )->fetchColumn();
    }

    /**
     * Release lock
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function releaseLock($name)
    {
        return (boolean) $this->getWriteAdapter()->query(
            'SELECT RELEASE_LOCK(?);',
            [$name]
        )->fetchColumn();
    }

    /**
     * Is lock exists.
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function isLocked($name)
    {
        return (boolean) $this->getWriteAdapter()->query(
            'SELECT IS_USED_LOCK(?);',
            [$name]
        )->fetchColumn();
    }

    /**
     * Write adapter.
     *
     * @return Varien_Db_Adapter_Pdo_Mysql
     */
    public function getWriteAdapter()
    {
        return $this->_getWriteAdapter();
    }

    /**
     * Get table name.
     *
     * @param  string $name
     *
     * @return string
     */
    public function getTable($name)
    {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }
}

