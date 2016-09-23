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
     * @param  string $worker
     * @param  array  $payload
     * @param  string $identity
     *
     * @return boolean
     *
     * @throws Mage_Core_Exception
     */
    public function add(string $worker, array $payload, $identity = '')
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfig($worker);
        $time           = time();

        if (!$config) {
            Mage::throwException("Unable to find worker with name {$worker}");
        }

        if (!is_array($payload)) {
            Mage::throwException("Payload must be of type array");
        }

        if (!is_string($identity)) {
            Mage::throwException("Identity needs to be of type string");
        }

        if ($config->getRule() == 'ignore') {
            $message = $this->_getCollection()->findOne(
                [
                    'worker'    => $worker,
                    'identity'  => $identity,
                ]
            );

            if ($message !== null && array_key_exists('_id', $message)) {
                return true;
            }
        } elseif ($config->getRule() == 'replace') {
            $this->_getCollection()->deleteMany(
                [
                    'finished'  => false,
                    'running'   => false,
                    'worker'    => $worker,
                    'identity'  => $identity,
                ]
            );
        }

        $message = [
            'payload'       => $this->_validateArrayData($payload),
            'worker'        => $worker,
            'identity'      => $identity,
            'running'       => false,
            'started_at'    => null,
            'when'          => min(max(0, $time + $config->getDelay()), self::MONGO_INT32_MAX),
            'priority'      => (integer) $config->getPriority(),
            'created'       => $time,
            'finished'      => false,
            'retries'       => 0,
        ];

        $this->_getCollection()->insertOne($message);

        return true;
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
     * @return Varien_Data_Collection|Varien_Object
     */
    public function reserve()
    {
        $configModel = Mage::getSingleton('pulchqueue/worker_config');

        $query      = ['running' => false];
        $options    = [
            'sort' => [
                'priority'  => 1,
                'created'   => 1
            ]
        ];

        $running    = [];
        $queue      = $this->_getCollection()->find(
            ['running' => true]
        );

        foreach ($queue as $message) {
            $message    = new Varien_Object($message);
            $identity   = "{$message->getWorker()}-{$message->getIdentity()}";

            $running[$identity] = null;
        }

        $queue              = $this->_getCollection()->find($query, $options);
        $batch              = false;
        $messageCollection  = new Varien_Data_Collection();

        foreach ($queue as $message) {
            $message    = new Varien_Object($message);
            $config     = $configModel->getWorkerConfig($message->getWorker());
            $identity   = "{$message->getWorker()}-{$message->getIdentity()}";

            if ($batch == false) {
                if ($config->getRule() == 'batch') {
                    $batch = $message->getWorker();
                }

                if ($config->getRule() == 'run') {
                    return $this->_beforeReturn($message);
                }

                if ($config->getRule() == 'wait') {
                    if (isset($running[$identity])) {
                        continue;
                    }

                    return $this->_beforeReturn($message);
                }
            }

            if ($message->getWorker() != $batch) {
                continue;
            }

            $messageCollection->addItem($message);
        }

        return $this->_beforeReturn($messageCollection);
    }

    /**
     * Marks jobs as started before returning.
     *
     * @param  Varien_Object|Varien_Data_Collection $object
     *
     * @return Varien_Data_Collection|Varien_Object
     */
    protected function _beforeReturn($object)
    {
        $query  = ['$or' => []];
        $update = [
            '$set' => [
                'started_at'    => time(),
                'running'       => true
            ]
        ];

        if ($object instanceof Varien_Data_Collection) {
            foreach ($object as $obj) {
                $query['$or'][] = ['_id' => $obj->getData('_id')];
            }
        } else {
            $query['$or'][] = ['_id' => $object->getData('_id')];
        }

        if (count($query['$or'])) {
            $this->_getCollection()->updateMany($query, $update);
        }

        return $object;
    }

    /**
     * Delete a job from the queue.
     *
     * @param Varien_Object $job
     *
     * @return boolean
     */
    public function delete($job)
    {
        $this->_getCollection()->deleteOne(
            [
                '_id'  => $job->getData('_id'),
            ]
        );
    }

    /**
     * Reschedule a job to be run at a later time.
     *
     * @param Varien_Object $job
     * @param integer       $milliseconds
     *
     * @return boolean
     */
    public function reschedule($job, $milliseconds = 0)
    {
        $update = [
            '$set' => [
                'running'   => false,
                'when'      => min(max(0, $time + $milliseconds), self::MONGO_INT32_MAX),
            ]
        ];

        $this->_getCollection()->updateOne(
            ['_id'  => $job->getData('_id')],
            $update
        );
    }
}

