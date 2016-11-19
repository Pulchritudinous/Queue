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
class Success_Pulchritudinous_Queue_Model_QueueTest
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
        $orgLabour  = $queue->add('test_successful_wait_work');
        $secLabour  = $queue->add('test_successful_wait_work');

        $this->assertInstanceOf(Pulchritudinous_Queue_Model_Labour::class, $orgLabour);

        $dbLabour = Mage::getModel('pulchqueue/labour')->load($orgLabour->getId());

        $this->assertEquals('pending', $dbLabour->getStatus(), 'New item status must be "pending"');

        $labour = $queue->receive();

        $this->assertInstanceOf(Pulchritudinous_Queue_Model_Labour::class, $labour);

        $this->assertEquals($orgLabour->getId(), $labour->getId(), 'Unexpected labour received');
        $this->assertEquals('deployed', $labour->getStatus(), 'Received item status must be "deployed"');
        $this->assertEquals('test_successful_wait_work', $labour->getWorker(), 'Unexpected worker code in received item');

        $labour = $queue->receive();

        $this->assertFalse($labour, 'Unexpected worker in received item');
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
    }

    /**
     * Test adding replace labour to queue.
     */
    public function testAddBatchLabourToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        foreach (range(1, 5) as $id) {
            $queue->add('test_successful_batch_work', ['id' => $id]);
        }

        $count = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->getSize();

        $this->assertEquals(5, $count, 'Should be 5 "batch" labour in queue');

        $labour = $queue->receive();

        $this->assertEquals('test_successful_batch_work', $labour->getWorker(), 'Unexpected worker code in received item');

        $batchCollection = $labour->getBatchCollection();

        $this->assertEquals(4, $batchCollection->getSize(), 'Should be 4 labours in batch collection');

        $labour->execute();

        $batchCollection = $labour->getBatchCollection();

        foreach ($batchCollection as $bundle) {
            $this->assertEquals('finished', $bundle->getStatus(), ' Labours status should be "finished"');
        }
    }

    /**
     * Test receiving labour based on priority.
     */
    public function testAddMixedLabourPriorotyToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_wait_work', ['id' => 1], ['priority' => 999]);
        $queue->add('test_successful_wait_work', ['id' => 2], ['priority' => 342]);
        $queue->add('test_successful_wait_work', ['id' => 3], ['priority' => 525]);
        $queue->add('test_successful_wait_work', ['id' => 4], ['priority' => 123]);
        $queue->add('test_successful_wait_work', ['id' => 5], ['priority' => 332]);

        $labour = $queue->receive();

        $this->assertEquals(['id' => 4], $labour->getPayload(), 'Unexpected worker in received item');
    }

    /**
     * Test receiving labour based on execute at.
     */
    public function testAddMixedLabourExecuteAtToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_wait_work', ['id' => 1], ['delay' => 999]);
        $queue->add('test_successful_wait_work', ['id' => 2], ['delay' => 342]);
        $queue->add('test_successful_wait_work', ['id' => 3], ['delay' => 3]);
        $queue->add('test_successful_wait_work', ['id' => 4], ['delay' => 123]);
        $queue->add('test_successful_wait_work', ['id' => 5], ['delay' => 332]);

        $labour = $queue->receive();

        $this->assertFalse($labour, 'Unexpected worker in received item');

        sleep(3.5);

        $labour = $queue->receive();

        $this->assertEquals(['id' => 3], $labour->getPayload(), 'Unexpected worker in received item');
    }

    /**
     * Test receiving labour based on priority.
     */
    public function testAddMixedLabourTypesAndPriorityToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_wait_work',    [], ['priority' => 999]);
        $queue->add('test_successful_ignore_work',  [], ['priority' => 342]);
        $queue->add('test_successful_replace_work', [], ['priority' => 123]);
        $queue->add('test_successful_batch_work',   [], ['priority' => 525]);

        $labour = $queue->receive();

        $this->assertEquals('test_successful_replace_work', $labour->getWorker(), 'Unexpected worker code in received item');
    }

    /**
     * Test receiving labour based on priority.
     */
    public function testAddMixedLabourTypesToQueue()
    {
        $queue = Mage::getSingleton('pulchqueue/queue');

        $queue->add('test_successful_wait_work');
        $queue->add('test_successful_ignore_work');
        $queue->add('test_successful_replace_work');
        $queue->add('test_successful_batch_work');

        $labour = $queue->receive();

        $this->assertEquals('test_successful_wait_work', $labour->getWorker(), 'Unexpected worker code in received item');
    }
}

