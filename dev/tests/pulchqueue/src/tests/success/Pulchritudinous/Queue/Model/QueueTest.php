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
 * Queue test case.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_QueueTest
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
     * Test if labour queue is empty.
     */
    public function testQueueIsEmpty()
    {
        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->getSize();

        $this->assertEquals(0, $count);
    }

    /**
     * Test if labour queue is empty.
     */
    public function testAddLabourToQueue()
    {
        $queue      = Mage::getSingleton('pulchqueue/queue');
        $orgLabour  = $queue->add('test_successful_work');

        $this->assertInstanceOf(Pulchritudinous_Queue_Model_Labour::class, $orgLabour);

        $dbLabour = Mage::getModel('pulchqueue/labour')->load($orgLabour->getId());

        $this->assertEquals('pending', $dbLabour->getStatus(), 'New item status must be "pending"');
    }

    /**
     * Receive next labour and verify type.
     *
     * @depends testAddLabourToQueue
     */
    public function testReceiveLabourFromQueue()
    {
        $queue  = Mage::getSingleton('pulchqueue/queue');
        $labour = $queue->receive();

        $this->assertInstanceOf(Pulchritudinous_Queue_Model_Labour::class, $labour);

        $this->assertEquals('deployed', $labour->getStatus(), 'Received item status must be "deployed"');
        $this->assertEquals('test_successful_work', $labour->getWorker(), 'Unexpected worker code in received item');

        $labour->delete();
    }

    /**
     * Test adding ignore labour to queue.
     */
    public function testAddIgnoreLabourToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_ignore_work');
        $queue->add('test_successful_ignore_work');
        $queue->add('test_successful_ignore_work');
        $queue->add('test_successful_ignore_work');
        $queue->add('test_successful_ignore_work');

        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->getSize();

        $this->assertEquals(1, $count);

        self::clearQueue();
    }

    /**
     * Test adding replace labour to queue.
     */
    public function testAddReplaceLabourToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_replace_work', ['id' => 1]);
        $queue->add('test_successful_replace_work', ['id' => 2]);

        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['eq' => 'pending'])
            ->getSize();

        $this->assertEquals(1, $count, 'Should be one "pending" labour in queue');

        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['eq' => 'replaced'])
            ->getSize();

        $this->assertEquals(1, $count, 'Should be one "replaced" labour in queue');

        $labour = $queue->receive();

        $this->assertEquals(['id' => 2], $labour->getPayload(), 'Unexpected worker payload');

        self::clearQueue();
    }
}

