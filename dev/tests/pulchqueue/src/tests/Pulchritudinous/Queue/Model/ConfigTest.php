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
class Pulchritudinous_Queue_Model_ConfigTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * Test queue config data type.
     */
    public function testWorkerDefaultConfigDataType()
    {
        $config = Mage::getModel('pulchqueue/config')
            ->getWorkerDefaultConfig();

        $this->assertInternalType('array', $config);
    }

    /**
     * Test that queue config contains data.
     */
    public function testWorkerDefaultConfigContainsValues()
    {
        $config = Mage::getModel('pulchqueue/config')
            ->getWorkerDefaultConfig();

        $this->assertNotEmpty($config);
    }

    /**
     * Test worker config has workers.
     */
    public function testHasWorkers()
    {
        $workers = Mage::getModel('pulchqueue/config')
            ->getWorkers();

        $this->assertInstanceOf(Varien_Data_Collection::class, $workers);

        $this->assertGreaterThan(0, $workers->count());
    }

    /**
     * Test worker config has workers.
     */
    public function testWorkerConfigDataType()
    {
        $config = Mage::getModel('pulchqueue/config')
            ->getWorkerConfig();

        $this->assertInstanceOf(Varien_Simplexml_Config::class, $config);
    }
}


