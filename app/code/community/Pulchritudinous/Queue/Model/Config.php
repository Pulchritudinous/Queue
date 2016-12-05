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
 * Queue configuration model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Config
{
    /**
     * Returns queue configuration.
     *
     * @return Varien_Object
     */
    public function getQueueConfig()
    {
        $config = Mage::getConfig()->getNode('global/pulchqueue/queue');

        if ($config) {
            $config = $config->asArray();
        } else {
            $config = [];
        }

        return new Varien_Object($config);
    }

    /**
     * Returns recurring configuration.
     *
     * @return Varien_Object
     */
    public function getRecurringConfig()
    {
        $config = Mage::getConfig()->getNode('global/pulchqueue/recurring');

        if ($config) {
            $config = $config->asArray();
        } else {
            $config = [];
        }

        return new Varien_Object($config);
    }

    /**
     * Get used lock storage.
     *
     * @return string
     */
    public function getUsedLockStorage()
    {
        $config = (string) Mage::getConfig()->getNode('global/pulchqueue/lock_storage');
        $valid  = [
            Pulchritudinous_Queue_Model_Lock::STORAGE_DB,
            Pulchritudinous_Queue_Model_Lock::STORAGE_FILE,
        ];

        if (in_array(strtolower($config), $valid)) {
            return strtolower($config);
        }

        return Pulchritudinous_Queue_Model_Lock::STORAGE_DB;
    }
}

