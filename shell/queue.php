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
     * Initialize application and parse input parameters
     */
    public function __construct()
    {
        $this->_parseArgs();




        if ($this->_includeMage) {
            require_once $this->_getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
            Mage::app($this->_appCode, $this->_appType);
        }
        $this->_factory = new Mage_Core_Model_Factory();

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

    }

    /**
     *
     */
    protected function _runJob()
    {

    }

    /**
     *
     */
    protected function _runServer()
    {
        while (true) {
            $this->_validateProcesses();

            if ($this->_canStartNext()) {

            }

            sleep(1);
        }
    }

    /**
     *
     *
     *
     */
    protected function _validateProcesses()
    {
        foreach ($this->_processes as $key => $process) {
            if (!$this->_validateProcesses($process)) {
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
