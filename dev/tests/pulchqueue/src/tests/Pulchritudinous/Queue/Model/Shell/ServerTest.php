<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Pulchritudinous
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
 * Shell server test case.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Shell_ServerTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * Initial setup.
     */
    public static function setUpBeforeClass()
    {
        self::clearQueue();
    }

    /**
     * Tear-down setup.
     */
    public function tearDown()
    {
        self::clearQueue();
    }

    /**
     * Clear queue from all labours.
     *
     * @return Pulchritudinous_Queue_Model_QueueTest
     */
    public static function clearQueue()
    {
        $resource   = Mage::getSingleton('core/resource');
        $adapter    = $resource->getConnection('core_write');
        $table      = $resource->getTableName('pulchqueue/labour');

        $adapter->delete($table);
    }

    /**
     * Shell file location.
     *
     * @return string
     */
    protected function _getShellFile()
    {
        return MAGENTO_ROOT . DS . 'shell' . DS . 'queue.php';
    }

    /**
     * Test can start next process.
     */
    public function testCanStartNext()
    {
        $server         = Mage::getModel('pulchqueue/shell_server', $this->_getShellFile());
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $configData     = $configModel->getQueueConfig();
        $threads        = $configData->getThreads();

        $this->assertTrue($server->canStartNext(0), 'Unexpected result');
        $this->assertTrue($server->canStartNext($threads-1), 'Unexpected result');
        $this->assertFalse($server->canStartNext($threads), 'Unexpected result');
    }

    /**
     * Test recurring labour.
     */
    public function testAddRecurringLabours()
    {
        $model = Mage::getModel('core/flag', ['flag_code' => 'pulchqueue_last_schedule'])->loadSelf();
        $model->setFlagData('')->save();

        $server = Mage::getModel('pulchqueue/shell_server', $this->_getShellFile());

        $server->addRecurringLabours();

        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->getSize();

        $model = Mage::getModel('core/flag', ['flag_code' => 'pulchqueue_last_schedule'])->loadSelf();

        $this->assertGreaterThan(0, $count);
        $this->assertNotEmpty($model->getFlagData());

        $labour = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->getFirstItem();

        $this->assertEquals(['id' => 54321], $labour->getPayload(), 'Unexpected worker payload');
    }
}

