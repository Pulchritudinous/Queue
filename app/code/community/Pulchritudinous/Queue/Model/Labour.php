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
 * Labour model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Labour
    extends Mage_Core_Model_Abstract
{
    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_PENDING        = 'pending';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_RUNNING        = 'running';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_DEPLOYED       = 'deployed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FAILED         = 'failed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_UNKNOWN        = 'unknown';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FINISHED       = 'finished';

    /**
     * Worker configuration.
     *
     * @return false|Varien_Object
     */
    protected $_workerConfig    = false;

    /**
     * Initial configuration.
     */
    public function __construct()
    {
        $this->_init('pulchqueue/queue_labour');
    }

    /**
     * Load labour by worker code and identity.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  string                             $worker
     * @param  string                             $identity
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function loadByWorkerIdentity($worker, $identity = '')
    {
        $this->getResource()->loadByWorkerIdentity($this, $worker, $identity);

        return $this;
    }

    /**
     * Execute labour.
     */
    public function execute()
    {
        try {
            if (!($this->_workerConfig instanceof Varien_Object)) {
                $this->_setAsFailed();

                Mage::throwException(
                    "Unable to execute labour with ID {$labour->getId()} and worker code {$this->getWorker()}"
                );
            }

            $this->_beforeExecute();
            $this->_execute();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Execute labour.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _execute()
    {
        $config = $this->_workerConfig;
        $model  = $this->getWorkderModel();

        $model
            ->setLabour($this)
            ->setConfig($config)
            ->execute();

        return $this;
    }

    /**
     * Mark labour as failed.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _setAsFailed()
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfig($this->getWorker());
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status'        => self::STATUS_FAILED,
            'started_at'    => now(),
            'finished_at'   => now(),
        ];

        if ($config->getRule() == 'batch') {
            $queueCollection = $this->_getBatchCollection()
                ->addFieldToFilter('identity', ['eq' => $this->getIdentity()])
                ->addFieldToFilter('worker', ['eq' => $this->getWorker()]);

            $this->setChildLabour($queueCollection);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as started.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeExecute()
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfig($this->getWorker());
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status'        => self::STATUS_RUNNING,
            'started_at'    => now(),
        ];

        if ($config->getRule() == 'batch') {
            $queueCollection = $this->_getBatchCollection()
                ->addFieldToFilter('identity', ['eq' => $this->getIdentity()])
                ->addFieldToFilter('worker', ['eq' => $this->getWorker()]);

            $this->setChildLabour($queueCollection);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Get batched labour collection.
     *
     * @return Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    protected function _getBatchCollection()
    {
        return Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('parent_id', ['eq' => $this->getId()]);
    }

    /**
     * Get payload.
     *
     * @return array
     */
    public function getPayload()
    {
        $data = $this->getData('payload');

        if (is_string($data)) {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * Add worker configuration after data is loaded.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _afterLoad()
    {
        $this->_workerConfig = Mage::getSingleton('pulchqueue/worker_config')
            ->getWorkerConfig($this->getWorker());

        return parent::_afterLoad();
    }

    /**
     * Add worker configuration after data is loaded.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeSave()
    {
        if (is_array($this->getData('payload'))) {
            $this->setPayload(serialize($this->getData('payload')));
        }

        return parent::_beforeSave();
    }
}

