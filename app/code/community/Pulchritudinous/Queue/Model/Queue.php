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
 * Class for handling adding and receiving jobs from the queue.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Queue
{
    /**
     * Add a job to the queue that will be asynchronously handled by a worker.
     *
     * @param  string                  $worker
     * @param  array                   $payload
     * @param  string                  $identity
     * @param  false|integer|Zend_Date $delay
     *
     * @return boolean
     *
     * @throws Mage_Core_Exception
     */
    public function add(string $worker, array $payload = [], $identity = '', $delay = false)
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfig($worker);

        if (!$config) {
            Mage::throwException("Unable to find worker with name {$worker}");
        }

        if (!is_string($identity)) {
            Mage::throwException('Identity needs to be of type string');
        }

        if ($config->getRule() == 'ignore') {
            $hasLabour = $this->getResource()->hasUnprocessedWorkerIdentity(
                $worker,
                $identity
            );

            if ($hasLabour == true) {
                return true;
            }
        } elseif ($config->getRule() == 'replace') {
            $this->getResource()->setStatusOnUnprocessedByWorkerIdentity(
                'replaced',
                $worker,
                $identity
            );
        }

        Mage::getModel('pulchqueue/labour')
            ->setWorker($worker)
            ->setIdentity($identity)
            ->setPriority($config->getPriority())
            ->setPayload(serialize($this->_validateArrayData($payload)))
            ->setStatus('pending')
            ->setExecuteAt($this->_getWhen($config, $delay))
            ->save();

        return true;
    }

    /**
     * Parses when the labour should be executed.
     *
     * @param  Varien_Object           $config
     * @param  false|integer|Zend_Date $delay
     *
     * @return string
     */
    protected function _getWhen($config, $delay = false)
    {
        $time           = time();
        $when           = date('Y-m-d H:i:s', $time + $config->getDelay());

        if ($delay !== false) {
            if (is_numeric($delay)) {
                $when = date('Y-m-d H:i:s', $time + $delay);
            } elseif ($delay instanceof Zend_Date) {
                $when = $delay->toString('Y-m-d H:i:s');
            } elseif (is_numeric($config->getDelay())) {
                $when = date('Y-m-d H:i:s', $time + $config->getDelay());
            }
        }

        return $when;
    }

    /**
     * Validate array data to make sure it does not contain objects.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function _validateArrayData($data)
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->_validateArrayData($value);

                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Receive next job from the queue.
     *
     * @return Varien_Object|false
     */
    public function receive()
    {
        $configModel        = Mage::getSingleton('pulchqueue/worker_config');
        $running            = [];
        $runningCollection  = $this->getRunning();
        $queueCollection    = $this->_getQueueCollection();

        foreach ($runningCollection as $labour) {
            $identity = "{$labour->getWorker()}-{$labour->getIdentity()}";

            $running[$identity] = null;
        }

        foreach ($queueCollection as $labour) {
            $config     = $configModel->getWorkerConfig($labour->getWorker());
            $identity   = "{$labour->getWorker()}-{$labour->getIdentity()}";

            if ($config->getRule() == 'wait') {
                if (isset($running[$identity])) {
                    continue;
                }
            }

            return $this->_beforeReturn($labour, $config);
        }

        return false;
    }

    /**
     * Get queued labour collection.
     *
     * @return Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    protected function _getQueueCollection()
    {
        return Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['eq' => Pulchritudinous_Queue_Model_Labour::STATUS_PENDING])
            ->addFieldToFilter('execute_at', ['lteq' => now()])
            ->setOrder('priority', 'ASC')
            ->setOrder('created_at', 'ASC');
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
     * @return Varien_Data_Collection|Varien_Object
     */
    public function getRunning()
    {
        $collection = Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('status', ['eq' => 'running']);

        $transaction    = Mage::getModel('core/resource_transaction');
        $hasUnknown     = false;

        foreach ($collection as $labour) {
            if (!posix_kill($labour->getPid(), 0)) {
                $hasUnknown = true;

                $labour->setFinishedAt(now());
                $labour->setStatus(Pulchritudinous_Queue_Model_Labour::STATUS_UNKNOWN);

                $transaction->addObject($labour);

                $collection->removeItemByKey($labour->getId());
            }
        }

        if ($hasUnknown == true) {
            $transaction->save();
        }

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
            'finished_at'   => now(),
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
        $config         = $configModel->getWorkerConfig($labour->getWorker());

        $labour
            ->setStatus(Pulchritudinous_Queue_Model_Labour::STATUS_PENDING)
            ->setRetries((int) $labour->getRetries() + 1)
            ->setExecuteAt($this->_getWhen($config, $delay))
            ->save();

        return true;
    }
}

