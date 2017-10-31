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
 * Class for handling adding and receiving jobs from the queue.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Queue
{
    /**
     * Queue trait.
     */
    use Pulchritudinous_Queue_Model_Trait_Queue;

    /**
     * Add a job to the queue that will be asynchronously handled by a worker.
     *
     * @param  string   $worker
     * @param  array    $payload
     * @param  array    $options
     *
     * @return Pulchritudinous_Queue_Model_Labour
     *
     * @throws Mage_Core_Exception
     */
    public function add($worker, array $payload = [], $options = [])
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfigByName($worker);
        $labourModel    = Mage::getModel('pulchqueue/labour');

        if (!$config) {
            Mage::throwException("Unable to find worker with name {$worker}");
        }

        $options = $this->_getOptions($options, $config);

        ($identity = $options->getIdentity()) || ($identity = "");

        if (!is_string($identity)) {
            Mage::throwException('Identity needs to be of type string');
        }

        if ($options->getDelay()) {
            $options->setExecuteAt(
                $this->_getWhen($config, $options->getDelay())
            );
            $options->unsDelay();
        } else {
            $options->setExecuteAt(
                $this->_getWhen($config)
            );
        }

        if ($config->getRule() == 'ignore') {
            $hasLabour = $labourModel->getResource()->hasUnprocessedWorkerIdentity(
                $worker,
                $options->getIdentity()
            );

            if ($hasLabour == true) {
                return true;
            }
        } elseif ($config->getRule() == 'replace') {
            $labourModel->getResource()->setStatusOnUnprocessedByWorkerIdentity(
                'replaced',
                $worker,
                $options->getIdentity()
            );
        }

        $labourModel
            ->setWorker($worker)
            ->addData($options->getData())
            ->setIdentity($identity)
            ->setAttempts(0)
            ->setPayload($this->_validateArrayData($payload))
            ->setStatus('pending')
            ->save();

        return $labourModel;
    }

    /**
     * Receive next job from the queue.
     *
     * @return Pulchritudinous_Queue_Model_Labour|false
     */
    public function receive()
    {
        $configModel        = Mage::getSingleton('pulchqueue/worker_config');
        $running            = [];
        $pageNr             = 0;
        $runningCollection  = $this->getRunning();

        foreach ($runningCollection as $labour) {
            $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";

            $running[$identity] = $identity;
        }

        $queueCollection = $this->_getQueueCollection();

        $queueCollection->setPageSize(50);

        $pages  = $queueCollection->getLastPageNumber();
        $pageNr = 1;

        do {
            $queueCollection
                ->setCurPage($pageNr)
                ->load();

            foreach ($queueCollection as $labour) {
                $config     = $configModel->getWorkerConfigByName($labour->getWorker());
                $identity   = "{$labour->getWorker()}-{$labour->getIdentity()}";

                if (!$config) {
                    continue;
                }

                if ($config->getRule() == 'wait') {
                    if (isset($running[$identity])) {
                        continue;
                    }
                }

                return $this->_beforeReturn($labour, $config);
            }

            $pageNr++;
            $queueCollection->clear();
        } while ($pageNr <= $pages);

        return false;
    }

    /**
     * Get queued labour collection.
     *
     * @return Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    protected function _getQueueCollection()
    {
        $collection = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['eq' => Pulchritudinous_Queue_Model_Labour::STATUS_PENDING])
            ->addFieldToFilter('execute_at', ['lteq' => time()])
            ->setOrder('priority', 'ASC')
            ->setOrder('created_at', 'ASC');

        return $collection;
    }

    /**
     * Before labour is returned.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  Varien_Object                      $config
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeReturn(Pulchritudinous_Queue_Model_Labour $labour, Varien_Object $config)
    {
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status' => Pulchritudinous_Queue_Model_Labour::STATUS_DEPLOYED,
        ];

        if ($config->getRule() == 'batch') {
            $queueCollection = $this->_getQueueCollection()
                ->addFieldToFilter('identity', ['eq' => $labour->getIdentity()])
                ->addFieldToFilter('worker', ['eq' => $labour->getWorker()]);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $labour->getId()) {
                    $bundle->addData(
                        array_merge(
                            $data,
                            [
                                'parent_id' => $labour->getId(),
                            ]
                        )
                    );

                    $transaction->addObject($bundle);
                }
            }
        }

        $labour->addData($data);

        $transaction->addObject($labour);
        $transaction->save();

        return $labour;
    }

    /**
     * Get all running labours.
     *
     * @param  boolean $includeUnknown
     *
     * @return Varien_Data_Collection|Varien_Object
     */
    public function getRunning($includeUnknown = false)
    {
        $statuses = [
            Pulchritudinous_Queue_Model_Labour::STATUS_DEPLOYED,
            Pulchritudinous_Queue_Model_Labour::STATUS_RUNNING,
        ];

        if (true === $includeUnknown) {
            $statuses[] = Pulchritudinous_Queue_Model_Labour::STATUS_UNKNOWN;
        }

        $collection = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['in' => $statuses]);

        return $collection;
    }

    /**
     * Mark labour as finished.
     *
     * @param  Pulchritudinous_Queue_Model_Labour
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function finish($labour)
    {
        $data = [
            'status'        => Pulchritudinous_Queue_Model_Labour::STATUS_FINISHED,
            'finished_at'   => time(),
        ];

        $labour->addData($data)->save();

        return $labour;
    }

    /**
     * Reschedule labour to be run at a later time.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  false|integer|Zend_Date            $delay
     *
     * @return boolean
     *
     * @throws Mage_Core_Exception
     */
    public function reschedule(Pulchritudinous_Queue_Model_Labour $labour, $delay = false)
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfigByName($labour->getWorker());

        $labour
            ->setStatus(Pulchritudinous_Queue_Model_Labour::STATUS_PENDING)
            ->setAttempts((int) $labour->getAttempts() + 1)
            ->setExecuteAt($this->_getWhen($config, $delay))
            ->save();

        return true;
    }
}

