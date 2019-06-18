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
 * Queue test case.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_ConfigTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * Test queue configuration contains data.
     */
    public function testQueueConfig()
    {
        $config = Mage::getModel('pulchqueue/config')
            ->getQueueConfig();

        $this->assertInstanceOf(Varien_Object::class, $config);
        $this->assertNotEmpty($config->getData());

        $this->assertRegExp('/\d+/', $config->getPoll(), 'Poll needs to be a integer');
        $this->assertRegExp('/\d+/', $config->getThreads(), 'Poll needs to be a integer');
    }

    /**
     * Test recurring configuration contains data.
     */
    public function testRecurringConfig()
    {
        $config = Mage::getModel('pulchqueue/config')
            ->getRecurringConfig();

        $this->assertInstanceOf(Varien_Object::class, $config);
        $this->assertNotEmpty($config->getData());

        $this->assertRegExp('/\d+/', $config->getPlanAheadMin(), 'Plan ahead min needs to be a integer');
        $this->assertRegExp('/\d+/', $config->getPlanningResolution(), 'Planning resolution needs to be a integer');
    }
}


