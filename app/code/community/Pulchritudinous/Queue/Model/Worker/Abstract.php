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
     * Payload object.
     *
     * @var Varien_Object
     */
    protected $_payload;

    /**
     * Set labour model to worker.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     *
     * @return Pulchritudinous_Queue_Model_Worker_Abstract
     */
    public function setLabour(Pulchritudinous_Queue_Model_Labour $labour)
    {
        $this->_labour = $labour;

        return $this;
    }

    /**
     * Get labour object.
     *
     * @return Varien_Object
     */
    protected function _getLabour()
    {
        return $this->_labour;
    }

    /**
     * Set labour configuration to worker.
     *
     * @param  Varien_Object $config
     *
     * @return Pulchritudinous_Queue_Model_Worker_Abstract
     */
    public function setConfig(Varien_Object $config)
    {
        $this->_config = $config;

        return $this;
    }

    /**
     * Get labour configuration object.
     *
     * @return Varien_Object
     */
    protected function _getConfig()
    {
        return $this->_config;
    }

    /**
     * Set payload object to worker.
     *
     * @param  Varien_Object $config
     *
     * @return Pulchritudinous_Queue_Model_Worker_Abstract
     */
    public function setPayload(Varien_Object $payload)
    {
        $this->_payload = $payload;

        return $this;
    }

    /**
     * Get payload object.
     *
     * @return Varien_Object
     */
    public function getPayload()
    {
        return $this->_getPayload();
    }

    /**
     * Get payload object.
     *
     * @return Varien_Object
     */
    protected function _getPayload()
    {
        return $this->_payload;
    }

    /**
     * Returns options to pass to $queue->add() function when scheduling
     * recurring jobs.
     *
     * Expected format:
     * [
     *      "payload" => [...],
     *      "options" => [...]
     * ]
     * @see Pulchritudinous_Queue_Model_Queue::add()
     *
     * @param  array $worderConfig
     *
     * @return array
     */
    public static function getRecurringOptions($worderConfig = [])
    {
        return [];
    }

    /**
     * Called before batch job is executed.
     *
     * @param  Pulchritudinous_Queue_Model_Labour_Batch $batch
     *
     * @return null
     */
    public static function beforeBatchExecute(Pulchritudinous_Queue_Model_Labour_Batch $batch)
    {
        return null;
    }

    /**
     * Called after batch job is executed.
     *
     * @param  Pulchritudinous_Queue_Model_Labour_Batch $batch
     *
     * @return null
     */
    public static function afterBatchExecute(Pulchritudinous_Queue_Model_Labour_Batch $batch)
    {
        return null;
    }

    /**
     * Throw Reschedule Exception.
     *
     * @param  string $message
     *
     * @throws Pulchritudinous_Queue_RescheduleException
     */
    protected function _throwRescheduleException($message)
    {
        throw new Pulchritudinous_Queue_RescheduleException($message);
    }
}

