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
     * List of processes.
     *
     * @var array
     */
    protected $_processes = [];

    /**
     *
     *
     * @var string
     */
    protected $_shellFile;

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
        $queue          = Mage::getSingleton('pulchqueue/queue');
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $configData     = new Varien_Object(
            Mage::getConfig()->getNode('global/pulchqueue')->asArray()
        );

        $cwd    = sys_get_temp_dir();
        $spec   = [
           ['pipe', 'r'],
           ['pipe', 'w'],
           ['pipe', 'w']
       ];

        while (true) {
            $this->_validateProcesses();

            if (!$this->_canStartNext()) {
                usleep($configData->getData('queue/poll'));
                continue;
            }

            $labour     = $queue->reserve();
            $command    = "{$binfile} {$shellfile} --labour {$labour->getId()}";
            $resource   = proc_open($command, $spec, $pipes, $cwd);
            $status     = proc_get_status($resource);

            if ($status && isset($status['pid'])) {
                $this->_processes[] = $status['pid'];
            }

            usleep($configData->getData('queue/poll'));
        }

        exit(0);
    }

    /**
     *
     *
     *
     */
    protected function _validateProcesses()
    {
        foreach ($this->_processes as $key => $process) {
            if (!$this->_validateProcess($process)) {
                unset($this->_processes[$key]);
            }
        }

        return $this;
    }

    /**
     *
     *
     * @return boolean
     */
    protected function _validateProcess($process)
    {
        $status = proc_get_status($process);

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

