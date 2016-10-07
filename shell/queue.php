<?php
require_once 'abstract.php';

/**
 *
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Shell
    extends Mage_Shell_Abstract
{
    /**
     *
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
     *
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

        register_shutdown_function([$this, 'exitStrategy']);

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
     *
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
     *
     */
    protected function _runServer()
    {
        $count          = 0;
        $binfile        = (isset($_SERVER['_'])) ? $_SERVER['_'] : 'php';
        $shellfile      = $this->_shellFile;
        $cwd            = sys_get_temp_dir();
        $queue          = Mage::getSingleton('pulchqueue/queue');
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $configData     = new Varien_Object(
            Mage::getConfig()->getNode('global/pulchqueue')->asArray()
        );
        $spec           = [
           ['pipe', 'r'],
           ['pipe', 'w'],
           ['pipe', 'w']
       ];

        self::$configData   = $configData;
        self::$processes    = new Varien_Data_Collection();

        while (true) {
            $this->_validateProcesses();

            $processes = self::$processes;

            if (!$this->_canStartNext()) {
                usleep($configData->getData('queue/poll'));
                continue;
            }

            $labour     = $queue->reserve();
            $command    = "{$binfile} {$shellfile} --labour {$labour->getId()}";
            $resource   = proc_open($command, $spec, $pipes, $cwd);
            $status     = proc_get_status($resource);

            if ($status && $status['running']) {
                $processes->addItem(
                    new Varien_Object([
                        'id'        => $status['pid'],
                        'resource'  => $resource,
                    ])
                );
            }

            usleep($configData->getData('queue/poll'));
        }

        exit(0);
    }

    /**
     *
     *
     * @return Pulchritudinous_Queue_Shell
     */
    protected function _validateProcesses()
    {
        $processes = self::$processes;

        foreach ($processes as $process) {
            if (!$this->_validateProcess($process->getResource())) {
                $process->removeItemByKey($process->getId());
            }
        }

        return $this;
    }

    /**
     *
     *
     * @param  resource $resource
     *
     * @return boolean
     */
    protected function _validateProcess($resource)
    {
        $status = proc_get_status($resource);

        return $status['running'];
    }

    /**
     *
     *
     * @return boolean
     */
    protected function _canStartNext()
    {
        if (count($this->_processes) < 4) {
            return true;
        }

        return false;
    }

    /**
     * Run state.
     */
    public static function exitStrategy()
    {
        echo 'Hello World!';
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
        --help          Print this help.

USAGE;
    }
}

$shell = new Pulchritudinous_Queue_Shell();
$shell->run();

