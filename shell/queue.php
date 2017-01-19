<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'abstract.php';

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
     * Server model.
     *
     * @var Pulchritudinous_Queue_Model_Shell_Server
     */
    public static $server;

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
     *
     * @param integer
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
        $server             = Mage::getModel('pulchqueue/shell_server', $this->_shellFile);
        $queue              = Mage::getModel('pulchqueue/queue');
        $configModel        = Mage::getSingleton('pulchqueue/config');
        $configData         = $configModel->getQueueConfig();

        self::$configData   = $configData;
        self::$server       = $server;
        self::$processes    = new Varien_Data_Collection();
        $processes          = self::$processes;

        while (true) {
            self::validateProcesses();

            $server->addRecurringLabours();

            if (!$server->canStartNext($processes->count())) {
                sleep($configData->getPoll());
                continue;
            }

            $labour = $queue->receive();

            if ($labour === false) {
                sleep($configData->getPoll());
                continue;
            }

            $resource = $server->startChildProcess($labour);

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
                $processes->removeItemByKey($process->getId());
            }
        }
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
     * Make sure all labours is finished before closing the server.
     */
    public static function exitStrategy()
    {
        $configData = self::$configData;
        $processes  = self::$processes;

        if ($configData) {
            $configData->setThreads(0);
        }

        echo "Closing open processes\n";

        if ($processes) {
            while ($processes->count()) {
                if ($processes->count() < $configData->getThreads()) {
                    break;
                }

                self::validateProcesses();
                usleep(500);
            }
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

