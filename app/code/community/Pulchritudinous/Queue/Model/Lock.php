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
 * Lock storage.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Lock
{
    /**
     * Lock storage type db.
     *
     * @var string
     */
    const STORAGE_DB    = 'db';

    /**
     * Lock storage type file.
     *
     * @var string
     */
    const STORAGE_FILE  = 'file';

    /**
     * Lock handler.
     *
     * @var Pulchritudinous_Queue_Model_Lock_Interface
     */
    protected $_storage;

    /**
     * Set named lock.
     *
     * @return boolean
     */
    public function setLock()
    {
        return $this->_getLockStorage()->setLock($this->_getLockName());
    }

    /**
     * Release named lock.
     *
     * @return boolean
     */
    public function releaseLock()
    {
        return $this->_getLockStorage()->releaseLock($this->_getLockName());
    }

    /**
     * Check whether the named lock exists
     *
     * @return boolean
     */
    public function isLockExists()
    {
        return $this->_getLockStorage()->isLocked($this->_getLockName());
    }

    /**
     * Get lock name.
     *
     * @return string
     */
    protected function _getLockName()
    {
        return 'pulchqueue';
    }

    /**
     * Get used lock handler.
     *
     * @return Pulchritudinous_Queue_Model_Lock_Interface
     */
    protected function _getLockStorage()
    {
        if ($this->_storage) {
            return $this->_storage;
        }

        $used = Mage::getSingleton('pulchqueue/config')->getUsedLockStorage();

        if ($used == self::STORAGE_DB) {
            $storage = Mage::getResourceHelper('pulchqueue');
        } else {
            $storage = Mage::getSingleton('pulchqueue/lock_file');
        }

        $this->_storage = $storage;

        return $storage;
    }
}

