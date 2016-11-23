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
class Pulchritudinous_Queue_Model_Shell_ServerTest
    extends PHPUnit_Framework_TestCase
{
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
}

