<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Pulchritudinous
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
 * File lock storage.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Lock_File
    implements Pulchritudinous_Queue_Model_Lock_Interface
{
    /**
     * Lock file resource.
     *
     * @var resource
     */
    protected $_lockFileResource;

    /**
     * Set lock.
     *
     * @param  string $name
     *
     * @return boolean
     */
    public function setLock($name)
    {
        $result = flock($this->_getLockFile($name), LOCK_EX);

        if ($result) {
            return true;
        }

        return false;
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
        if (flock($this->_getLockFile($name), LOCK_UN)) {
            return true;
        }

        return false;
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
        $fp = $this->_getLockFile($name);

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            flock($fp, LOCK_UN);

            return false;
        }

        return true;
    }

    /**
     * Get lock file resource.
     *
     * @param string $name
     *
     * @return resource
     */
    protected function _getLockFile($name)
    {
        if ($this->_lockFileResource === null) {
            $varDir = Mage::getConfig()->getVarDir('locks');
            $file   = $varDir . DS . $name . '.lock';

            if (is_file($file)) {
                $this->_lockFileResource = fopen($file, 'w');
            } else {
                $this->_lockFileResource = fopen($file, 'x');
            }

            fwrite($this->_lockFileResource, date('r'));
        }

        return $this->_lockFileResource;
    }
}

