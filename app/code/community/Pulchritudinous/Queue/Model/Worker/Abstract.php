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
 * Abstract worker model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
abstract class Pulchritudinous_Queue_Model_Worker_Abstract
    extends Varien_Object
    implements Pulchritudinous_Queue_Model_Worker_Interface
{
    /**
     * Labour model.
     *
     * @var Pulchritudinous_Queue_Model_Labour
     */
    protected $_labour;

    /**
     * Config model.
     *
     * @var Varien_Object
     */
    protected $_config;

    /**
     * Set labour model to worker.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     *
     * @return Pulchritudinous_Queue_Model_Worker_Abstract
     */
    public function setLabour(Pulchritudinous_Queue_Model_Labour $labour)
    {
        $this->_labour = $this;

        return $this;
    }

    /**
     * Set labour model to worker.
     *
     * @param  Varien_Object $config
     *
     * @return Pulchritudinous_Queue_Model_Worker_Abstract
     */
    public function setConfig(Varien_Object $config)
    {
        $this->_labour = $this;

        return $this;
    }
}

