<?php
require_once 'abstract.php';

/**
 * Shell for handling queue and labour execution.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Shell
    extends Mage_Shell_Abstract
{
    /**
     * Shell file location.
     *
     * @var string
     */
    protected $_shellFile;

    /**
     * List of executed processes.
     *
     * @var Varien_Data_Collection
     */
    public static $processes;

    /**
     * Server configuration.
     *
     * @var Varien_Object
     */
    public static $configData;

    /**
     * Last time scheduling happened
     *
     * @param int
     */
    public static $lastSchedule;

    /**
     * Initialize application and parse input parameters
     */
    public function __construct()
    {
        $this->_parseArgs();

        if (!$this->getArg('labour')) {
            register_shutdown_function([$this, 'exitStrategy']);
        }

        if ($this->_includeMage) {
            require_once $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
            Mage::app($this->_appCode, $this->_appType);
        }

        $this->_factory     = new Mage_Core_Model_Factory();
        $this->_shellFile   = __FILE__;

        $this->_applyPhpVariables();
        $this->_construct();
        $this->_validate();
        $this->_showHelp();
    }

    /**
     * Run state.
     */
    public function run()
    {
        if ($id = $this->getArg('labour')) {
            return $this->_runLabour($id);
        }

        if ($this->getArg('help')) {
            echo $this->usageHelp();
            exit(0);
        }

        $this->_runServer();
    }

    /**
     * Run labour.
     */
    protected function _runLabour($id)
    {
        try {
            $labour = Mage::getModel('pulchqueue/labour')->load($id);
            $labour->execute();
        } catch (Exception $e) {
            exit(1);
        }

        exit(0);
    }

    /**
     * Run server.
     */
    protected function _runServer()
    {
        $count          = 0;
        $binfile        = (isset($_SERVER['_'])) ? $_SERVER['_'] : 'php';
        $shellfile      = $this->_shellFile;
        $cwd            = sys_get_temp_dir();
        $logDir         = Mage::getBaseDir('var') . DS . 'log';
        $errorFile      = $logDir . DS . Mage::getStoreConfig('dev/log/exception_file');
        $queue          = Mage::getSingleton('pulchqueue/queue');
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $configData     = $configModel->getQueueConfig();

        self::$configData   = $configData;
        self::$processes    = new Varien_Data_Collection();

        while (true) {
            self::validateProcesses();
            self::addRecurringJobs($configData);

            $processes = self::$processes;

            if (!$this->_canStartNext()) {
                sleep($configData->getPoll());
                continue;
            }

            $labour = $queue->receive();

            if ($labour === false) {
                sleep($configData->getPoll());
                continue;
            }

            $spec = [
               ['pipe', 'r'],
               ['pipe', 'w'],
               ['file', $errorFile, 'a']
            ];

            $command    = "{$binfile} {$shellfile} --labour {$labour->getId()}";
            $resource   = proc_open($command, $spec, $pipes, $cwd, null);

            stream_set_blocking($pipes[0], 0);
            stream_set_blocking($pipes[1], 0);

            if (self::validateProcess($resource)) {
                $status = proc_get_status($resource);

                $labour->getResource()->updateField($labour, 'pid', $status['pid']);

                $processes->addItem(
                    new Varien_Object([
                        'id'        => $status['pid'],
                        'resource'  => $resource,
                        'labour'    => $labour,
                    ])
                );

            }

            sleep($configData->getPoll());
        }

        exit(0);
    }

    /**
     * Validate all labour processes.
     *
     * @return Pulchritudinous_Queue_Shell
     */
    public static function validateProcesses()
    {
        $processes = self::$processes;

        foreach ($processes as $process) {
            if (!self::validateProcess($process->getResource())) {
                proc_close($process->getResource());

                $labour->setAsUnknown();
                $processes->removeItemByKey($process->getId());
            }
        }
    }

    /**
     *
     *
     *
     */
    public static function addRecurringJobs($config)
    {
        $recurringConfig = Mage::getSingleton('pulchqueue/config')->getRecurringConfig();

        $last       = self::$lastSchedule;
        $itsTime    = ($last + $recurringConfig->getPlanAheadMin() * 60) <= time();

        if (!$itsTime) {
            return;
        }

        self::$lastSchedule = time();

        $workers    = Mage::getSingleton('pulchqueue/config')->getWorkers();

        foreach ($workers as $worker) {
            $rec = $worker->getRecurring();

            if (!isset($rec['pattern'])) {
                continue;
            }

            $pattern    = $rec['pattern'];
            $runTimes   = self::generateRunDates($pattern);

            if ($runTimes) {
                $workerModel = Mage::getSingleton('pulchqueue/worker_config')
                    ->loadWorkerClass($worker);

                if (!$workerModel) {
                    continue;
                }

                foreach ($runTimes as $date) {
                    $opt        = new Varien_Object($worker->getWorkerModel()->getRecurringOptions());
                    $options    = $opt->getOptions();
                    ($payload = $opt->getPayload()) || ($payload = []);

                    if (!$options) {
                        $options = [];
                    }

                    $options['delay'] = $date;

                    Mage::getModel('pulchqueue/queue')->add(
                        (string)$worker->getWorkerName(),
                        $payload,
                        $options
                    );
                }
            }
        }
    }

    /**
     * Generate date times to run job at
     *
     * @param string $pattern
     *
     * @return array
     */
    public static function generateRunDates($pattern)
    {
        $recurringConfig = Mage::getSingleton('pulchqueue/config')->getRecurringConfig();

        $scheduler  = Mage::getModel('cron/schedule');
        $time       = time();
        $timeAhead  = $time + $recurringConfig->getPlanAheadMin() * 60;
        $interval   = $recurringConfig->getPlanningResolution() * 60;

        $scheduler->setCronExpr($pattern);

        $runTimes = [];

        for ($time; $time < $timeAhead; $time += $interval) {
            $shouldAdd = $scheduler->trySchedule($time);
            if ($shouldAdd) {
                $date = new Zend_Date($time, Zend_Date::TIMESTAMP);
                $runTimes[] = $date;
            }
        }

        return $runTimes;
    }

    /**
     * Validate single labour processes.
     *
     * @param  resource $resource
     *
     * @return boolean
     */
    public static function validateProcess($resource)
    {
        $status = proc_get_status($resource);

        return $status['running'];
    }

    /**
     * Check if it is possible to start next labour process.
     *
     * @return boolean
     */
    protected function _canStartNext()
    {
        $configData = self::$configData;
        $processes  = self::$processes;

        if ($processes->count() < $configData->getThreads()) {
            return true;
        }

        return false;
    }

    /**
     * Make sure all labours is finished before closing the server.
     */
    public static function exitStrategy()
    {
        $configData = self::$configData;
        $processes  = self::$processes;

        $configData->setThreads(0);

        echo "Closing open processes\n";

        while ($processes->count()) {
            if ($processes->count() < $configData->getThreads()) {
                break;
            }

            self::validateProcesses();
            usleep(500);
        }

        echo "Finished!\n";
    }

    /**
     * Helper text.
     *
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:
    php queue.php [options]
        --help          Shows this help.

USAGE;
    }
}

$shell = new Pulchritudinous_Queue_Shell();
$shell->run();


