<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Pulchritudinous
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

        set_error_handler([$this, 'errorHandler']);

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

        try {
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

                $validateProcesses = @self::validateProcess($resource);

                if ($validateProcesses) {
                    $status = proc_get_status($resource);

                    $labour->getResource()->updateField($labour, 'pid', $status['pid']);

                    $processes->addItem(
                        new Varien_Object([
                            'id'        => $status['pid'],
                            'resource'  => $resource,
                            'labour'    => $labour,
                            'started'   => time(),
                        ])
                    );
                }

                if (!$server->canStartNext($processes->count())) {
                    sleep($configData->getPoll());
                }
            }
        } catch (Exception $e) {
            exit(0);
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
            if (!self::validateProcess($process)) {
                if (is_resource($process->getResource())) {
                    proc_close($process->getResource());
                }

                $processes->removeItemByKey($process->getId());
            }
        }
    }

    /**
     * Validate single labour processes.
     *
     * @param  Varien_Object|resource $process
     *
     * @return boolean
     */
    public static function validateProcess($process)
    {
        if ($process instanceof Varien_Object) {
            $resource   = $process->getResource();
            $labour     = $process->getLabour();
            $config     = $labour->getWorkerConfig();
            $timeout    = $config->getTimeout();
            $pid        = $labour->getPid();

            if (null === $resource) {
                $labour->setAsUnknown();

                return false;
            }

            if (0 != $timeout && (time() - $process->getStarted()) > $timeout) {
                $labour->setAsUnknown();

                return false;
            }

            if (null !== $pid
                && !$process->getParentId()
                && false === get_resource_type($resource)
                && false === posix_kill($pid, 0)
            ) {
                $labour->setAsFinished();
            }
        } else {
            $resource = $process;
        }

        $status = proc_get_status($resource);

        return (bool) @$status['running'];
    }

    /**
     * Handle any errors from load xml
     *
     * @param integer $errNo
     * @param string  $errStr
     * @param string  $errFile
     * @param integer $errLine
     */
    public function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        switch ($errNo) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_COMPILE_ERROR:
                exit(0);
        }
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

