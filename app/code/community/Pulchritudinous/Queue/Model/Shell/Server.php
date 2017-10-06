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
 * Shell model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Shell_Server
{
    /**
     * Shell file location.
     *
     * @var string
     */
    protected $_shellFile;

    /**
     * Engine bin.
     *
     * @var string
     */
    protected $_binFile;

    /**
     * Last run time stamp.
     *
     * @var integer
     */
    protected $_lastSchedule;

    /**
     * Lock manager.
     *
     * @var Pulchritudinous_Queue_Model_Lock
     */
    protected $_lockStorage;

    /**
     * Initial configuration.
     *
     * @param  string $shellfile
     */
    public function __construct($shellfile)
    {
        if (!file_exists($shellfile)) {
            Mage::throwException('Shell file does not exist.');
        }

        $logDir             = Mage::getBaseDir('var') . DS . 'log';
        $this->_shellFile   = $shellfile;
        $this->_cwd         = sys_get_temp_dir();
        $this->_binFile     = (isset($_SERVER['_'])) ? $_SERVER['_'] : 'php';
        $this->_errorFile   = $logDir . DS . Mage::getStoreConfig('dev/log/exception_file');

        $this->_startServer();
        $this->_updateLastSchedule();
    }

    /**
     * Update date for last reschedule.
     *
     * @param  integer $time
     *
     * @return Pulchritudinous_Queue_Model_Shell_Server
     */
    protected function _updateLastSchedule($time = false)
    {
        $model = Mage::getModel('core/flag', ['flag_code' => 'pulchqueue_last_schedule'])->loadSelf();

        if (is_int($time)) {
            $this->_lastSchedule = $time;
            $model->setFlagData($time)->save();
        } elseif (is_int($model->getFlagData())) {
            $this->_lastSchedule = $model->getFlagData();
        }

        return $this;
    }

    /**
     * Starting server.
     */
    protected function _startServer()
    {
        if (!$this->_canStart()) {
            Mage::throwException('Not allowed to start server.');
        }

        return $this;
    }

    /**
     * Check if server is allowed to start.
     *
     * @return boolean
     */
    protected function _canStart()
    {
        return $this->_getLockStorage()->setLock();
    }

    /**
     * Lock storage manager.
     *
     * @return Pulchritudinous_Queue_Model_Lock
     */
    protected function _getLockStorage()
    {
        if (!$this->_lockStorage) {
            $this->_lockStorage = Mage::getSingleton('pulchqueue/lock');
        }

        return $this->_lockStorage;
    }

    /**
     * Check if another process is allowed to start.
     *
     * @return boolean
     */
    public function canStartNext($processCount)
    {
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $configData     = $configModel->getQueueConfig();

        if ($processCount < $configData->getThreads()) {
            return true;
        }

        return false;
    }

    /**
     * Add recurring labours to queue.
     *
     * @return Pulchritudinous_Queue_Model_Shell_Server
     */
    public function addRecurringLabours()
    {
        $configModel        = Mage::getSingleton('pulchqueue/config');
        $configData         = $configModel->getQueueConfig();
        $recurringConfig    = $configModel->getRecurringConfig();
        $last               = (int) $this->_lastSchedule;
        $itsTime            = ($last + $recurringConfig->getPlanAheadMin() * 60) <= time();

        if (!$itsTime) {
            return $this;
        }

        $this->_updateLastSchedule(time());

        $workers = Mage::getSingleton('pulchqueue/worker_config')->getWorkers();

        foreach ($workers as $worker) {
            $rec = new Varien_Object((array) $worker->getRecurring());

            if (false === $rec->getIsAllowed()) {
                continue;
            }

            if (!($pattern = $rec->getPattern())) {
                continue;
            }

            $runTimes = $this->generateRunDates($pattern);

            if (empty($runTimes)) {
                continue;
            }

            $result = Mage::getSingleton('pulchqueue/worker_config')
                ->loadWorkerClass($worker);

            if (!$result) {
                continue;
            }

            foreach ($runTimes as $date) {
                $opt        = new Varien_Object($worker->getWorkerModel()->getRecurringOptions());
                $options    = $opt->getOptions();
                ($payload   = $opt->getPayload()) || ($payload = []);

                if (!$options) {
                    $options = [];
                }

                $options['delay'] = $date - time();

                try {
                    Mage::getModel('pulchqueue/queue')->add(
                        (string) $worker->getWorkerName(),
                        $payload,
                        $options
                    );
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }

        return $this;
    }

    /**
     * Generate date times to execute labour at.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     *
     * @return resource
     */
    public function startChildProcess(Pulchritudinous_Queue_Model_Labour $labour)
    {
        $errorFile  = $this->_errorFile;
        $binfile    = $this->_binFile;
        $shellfile  = $this->_shellFile;
        $cwd        = $this->_cwd;

        $spec = [
           ['pipe', 'r'],
           ['pipe', 'w'],
           ['file', $errorFile, 'a']
        ];

        $command    = "{$binfile} {$shellfile} --labour {$labour->getId()}";
        $resource   = proc_open($command, $spec, $pipes, $cwd, null);

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);

        return $resource;
    }

    /**
     * Generate date times to execute labour at.
     *
     * @param  string $pattern
     *
     * @return array
     */
    public function generateRunDates($pattern)
    {
        $recConfig  = Mage::getSingleton('pulchqueue/config')->getRecurringConfig();
        $scheduler  = Mage::getModel('cron/schedule');
        $time       = time();
        $timeAhead  = $time + $recConfig->getPlanAheadMin() * 60;
        $interval   = $recConfig->getPlanningResolution() * 60;

        $scheduler->setCronExpr($pattern);

        $runTimes = [];

        for ($time; $time < $timeAhead; $time += $interval) {
            $shouldAdd = $scheduler->trySchedule($time);

            if ($shouldAdd) {
                $runTimes[] = $time;
            }
        }

        return $runTimes;
    }

    /**
     * Closing server.
     *
     * @return Pulchritudinous_Queue_Model_Shell_Server
     */
    protected function _closeServer()
    {
        $this->_getLockStorage()->releaseLock();

        return $this;
    }

    /**
     * Destruction configuration.
     */
    public function __destruct()
    {
        $this->_closeServer();
    }
}

