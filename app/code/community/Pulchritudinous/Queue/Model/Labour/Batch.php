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
 * Batch labour model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Labour_Batch
    extends Varien_Object
{
    /**
     * Batch ID.
     *
     * @var string
     */
    protected $_id;

    /**
     * Process ID.
     *
     * @var string
     */
    protected $_pid;

    /**
     * Worker code.
     *
     * @var string
     */
    protected $_worker;

    /**
     * Labour collection.
     *
     * @var Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    protected $_collection;

    /**
     * Labour model trait.
     */
    use Pulchritudinous_Queue_Model_Trait_Labour;

    /**
     * Initial configuration.
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        list ($id, $pid, $worker, $collection) = $args;

        set_error_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);

        $this->_id = $id;
        $this->_pid = $pid;
        $this->_worker = $worker;
        $this->_collection = $collection;
    }

    /**
     * Get labour collection.
     *
     * @return Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    /**
     * Set labour collection.
     *
     * @param  Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection $collection
     *
     * @return Pulchritudinous_Queue_Model_Labour_Batch
     */
    public function setCollection(Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection $collection)
    {
        $this->_collection = $collection;

        return $this;
    }

    /**
     * Get worker configuration.
     *
     * @return Varien_Object|false
     */
    protected function _getWorkerConfig()
    {
        if (!$this->_workerConfig) {
            $this->_workerConfig = Mage::getSingleton('pulchqueue/worker_config')
                ->getWorkerConfigByName($this->_worker);
        }

        return $this->_workerConfig;
    }

    /**
     * Execute labour.
     */
    public function execute()
    {
        try {
            $config = $this->_getWorkerConfig();

            if (!($config instanceof Varien_Object)) {
                Mage::throwException(
                    "Unable to execute batch job with ID {$this->_id} and worker code {$this->_worker}"
                );
            }

            $model = $config->getWorkerModel();

            $model::beforeBatchExecute($this);
            $this->_beforeExecute();

            $this->_execute();

            $model::afterBatchExecute($this);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Synchronize data before start.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeExecute()
    {
        $collection     = $this->_collection;
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = ['pid' => $this->_pid];

        foreach ($collection as $labour) {
            $labour->addData($data);
            $transaction->addObject($labour);
        }

        $transaction->save();

        return $this;
    }

    /**
     * Execute labour.
     *
     * @return Pulchritudinous_Queue_Model_Labour_Batch
     */
    protected function _execute()
    {
        $collection = $this->_collection;

        foreach ($collection as $labour) {
            try {
                $labour->setIsBatchLabour(true)->execute();

                if (Pulchritudinous_Queue_Model_Labour::STATUS_FAILED === $labour->getStatus()) {
                    throw new Exception('Reschedule');
                }
            } catch (Exception $e) {
                try {
                    $labour->setBatch(null);
                    $labour->setFinishedAt(null);
                    $labour->setStartedAt(null);
                    $labour->reschedule();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }

        return $this;
    }
}

