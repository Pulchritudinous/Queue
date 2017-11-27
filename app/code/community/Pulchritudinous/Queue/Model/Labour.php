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
 * Labour model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Labour
    extends Mage_Core_Model_Abstract
{
    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_PENDING        = 'pending';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_RUNNING        = 'running';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_DEPLOYED       = 'deployed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FAILED         = 'failed';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_UNKNOWN        = 'unknown';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_FINISHED       = 'finished';

    /**
     * Labour status.
     *
     * @var string
     */
    const STATUS_SKIPPED        = 'skipped';

    /**
     * Worker configuration.
     *
     * @return false|Varien_Object
     */
    protected $_workerConfig    = false;

    /**
     * Queue model trait.
     */
    use Pulchritudinous_Queue_Model_Trait_Queue;

    /**
     * Initial configuration.
     */
    public function __construct()
    {
        $this->_init('pulchqueue/queue_labour');

        set_error_handler([$this, 'errorHandler']);
    }

    /**
     * Load labour by worker code and identity.
     *
     * @param  Pulchritudinous_Queue_Model_Labour $labour
     * @param  string                             $worker
     * @param  string                             $identity
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function loadByWorkerIdentity($worker, $identity = '')
    {
        $this->getResource()->loadByWorkerIdentity($this, $worker, $identity);

        return $this;
    }

    /**
     * Execute labour.
     */
    public function execute()
    {
        try {
            if (!($this->getWorkerConfig() instanceof Varien_Object)) {
                Mage::throwException(
                    "Unable to execute labour with ID {$this->getId()} and worker code {$this->getWorker()}"
                );
            }

            $this->_beforeExecute();
            $this->_execute();
            $this->_afterExecute();
        } catch (Pulchritudinous_Queue_RescheduleException $e) {
            $this->reschedule();

            Mage::logException($e);
        } catch (Exception $e) {
            $this->setAsFailed();

            Mage::logException($e);
        }
    }

    /**
     * Execute labour.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _execute()
    {
        $config         = $this->getWorkerConfig();
        $model          = $config->getWorkerModel();
        $payload        = $this->getPayload(true);
        $childLabour    = $this->getChildLabour();

        $model
            ->setLabour($this)
            ->setConfig($config)
            ->setPayload($payload)
            ->setChildLabour($childLabour)
            ->execute();

        return $this;
    }

    /**
     * Reschedule labour.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function reschedule()
    {
        $configModel        = Mage::getSingleton('pulchqueue/worker_config');
        $config             = $configModel->getWorkerConfigByName($this->getWorker());
        $currentAttempts    = ($this->getAttempts()) ? $this->getAttempts() : 0;

        if ($config->getAttempts() < $currentAttempts) {
            return $this->setAsFailed();
        }

        $when = $this->_getWhen($config, $config->getReschedule());

        $data = [
            'status'        => self::STATUS_PENDING,
            'execute_at'    => $when,
        ];

        $transaction = Mage::getModel('core/resource_transaction');

        if ($config->getRule() == 'batch') {
            $queueCollection = $this->getBatchCollection();

            $this->setChildLabour($queueCollection);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as failed.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function setAsFailed()
    {
        $configModel    = Mage::getSingleton('pulchqueue/worker_config');
        $config         = $configModel->getWorkerConfigByName($this->getWorker());
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status'        => self::STATUS_FAILED,
            'started_at'    => time(),
            'finished_at'   => time(),
        ];

        if ($config instanceof Varien_Object && $config->getRule() == 'batch') {
            $queueCollection = $this->getBatchCollection();

            $this->setChildLabour($queueCollection);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as unknown.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function setAsUnknown()
    {
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status' => self::STATUS_UNKNOWN,
        ];

        foreach ($this->getBatchCollection() as $bundle) {
            $bundle->addData($data);
            $transaction->addObject($bundle);
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as skipped.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function setAsSkipped()
    {
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status' => self::STATUS_SKIPPED,
        ];

        foreach ($this->getBatchCollection() as $bundle) {
            $bundle->addData($data);
            $transaction->addObject($bundle);
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Mark labour as started.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeExecute()
    {
        $configModel        = Mage::getSingleton('pulchqueue/worker_config');
        $config             = $configModel->getWorkerConfigByName($this->getWorker());
        $currentAttempts    = ($this->getAttempts()) ? $this->getAttempts() : 0;
        $transaction        = Mage::getModel('core/resource_transaction');

        $data               = [
            'status'        => self::STATUS_RUNNING,
            'started_at'    => time(),
            'pid'           => $this->getPid(),
            'attempts'      => $currentAttempts + 1,
        ];

        if ($config->getRule() == 'batch') {
            $queueCollection = $this->getBatchCollection();

            $this->setChildLabour($queueCollection);

            foreach ($queueCollection as $bundle) {
                if ($bundle->getId() != $this->getId()) {
                    $bundle->addData($data);

                    $transaction->addObject($bundle);
                }
            }
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * After a successful execution.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _afterExecute()
    {
        $this->setAsFinished();

        return $this;
    }

    /**
     * Mark labour as finished.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function setAsFinished()
    {
        $transaction    = Mage::getModel('core/resource_transaction');
        $data           = [
            'status'        => self::STATUS_FINISHED,
            'finished_at'   => time(),
        ];

        foreach ($this->getBatchCollection() as $bundle) {
            $bundle->addData($data);
            $transaction->addObject($bundle);
        }

        $this->addData($data);

        $transaction->addObject($this);
        $transaction->save();

        return $this;
    }

    /**
     * Get batched labour collection.
     *
     * @return Pulchritudinous_Queue_Model_Resource_Queue_Labour_Collection
     */
    public function getBatchCollection()
    {
        return Mage::getModel('pulchqueue/labour')
            ->getCollection()
            ->addFieldToFilter('parent_id', ['eq' => $this->getId()]);
    }

    /**
     * Get payload.
     *
     * @param  boolean $asObject
     *
     * @return array|Varien_Object
     */
    public function getPayload($asObject = false)
    {
        $data = $this->getData('payload');

        if (is_string($data)) {
            $data = json_decode($data);
        }

        if ($asObject == true) {
            $data = new Varien_Object($data);
        }

        return $data;
    }

    /**
     * Get worker configuration.
     *
     * @return Varien_Object|false
     */
    public function getWorkerConfig()
    {
        return $this->_workerConfig;
    }

    /**
     * Add worker configuration after data is loaded.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _afterLoad()
    {
        $this->applyWorkerConfig();

        return parent::_afterLoad();
    }

    /**
     * Add worker configuration after data is saved.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _afterSave()
    {
        $this->applyWorkerConfig();

        return parent::_afterSave();
    }

    /**
     * Add worker configuration after data is loaded.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    protected function _beforeSave()
    {
        if (is_array($this->getData('payload'))) {
            $this->setPayload(json_encode($this->getData('payload')));
        }

        return parent::_beforeSave();
    }

    /**
     * Apply worker configuration.
     *
     * @return Pulchritudinous_Queue_Model_Labour
     */
    public function applyWorkerConfig()
    {
        if (!$this->_workerConfig) {
            $this->_workerConfig = Mage::getSingleton('pulchqueue/worker_config')
                ->getWorkerConfigByName($this->getWorker());
        }

        return $this;
    }

    /**
     * Handle any errors.
     *
     * @param integer $errNo
     * @param string  $errStr
     * @param string  $errFile
     * @param integer $errLine
     */
    public function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        $errno = $errNo & error_reporting();

        if ($errno == 0) {
            return false;
        }

        if (!defined('E_STRICT')) {
            define('E_STRICT', 2048);
        }

        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        if (!defined('E_DEPRECATED')) {
            define('E_DEPRECATED', 8192);
        }

        // PEAR specific message handling
        if (stripos($errFile . $errStr, 'pear') !== false) {
             // ignore strict and deprecated notices
            if (($errno == E_STRICT) || ($errno == E_DEPRECATED)) {
                return true;
            }
            // ignore attempts to read system files when open_basedir is set
            if ($errno == E_WARNING && stripos($errStr, 'open_basedir') !== false) {
                return true;
            }
        }

        $errorMessage = '';

        switch($errno){
            case E_ERROR:
                $errorMessage .= "Error";
                break;
            case E_WARNING:
                $errorMessage .= "Warning";
                break;
            case E_PARSE:
                $errorMessage .= "Parse Error";
                break;
            case E_NOTICE:
                $errorMessage .= "Notice";
                break;
            case E_CORE_ERROR:
                $errorMessage .= "Core Error";
                break;
            case E_CORE_WARNING:
                $errorMessage .= "Core Warning";
                break;
            case E_COMPILE_ERROR:
                $errorMessage .= "Compile Error";
                break;
            case E_COMPILE_WARNING:
                $errorMessage .= "Compile Warning";
                break;
            case E_USER_ERROR:
                $errorMessage .= "User Error";
                break;
            case E_USER_WARNING:
                $errorMessage .= "User Warning";
                break;
            case E_USER_NOTICE:
                $errorMessage .= "User Notice";
                break;
            case E_STRICT:
                $errorMessage .= "Strict Notice";
                break;
            case E_RECOVERABLE_ERROR:
                $errorMessage .= "Recoverable Error";
                break;
            case E_DEPRECATED:
                $errorMessage .= "Deprecated functionality";
                break;
            default:
                $errorMessage .= "Unknown error ($errno)";
                break;
        }

        $errorMessage .= ": {$errStr}  in {$errFile} on line {$errLine}";

        Mage::log($errorMessage, Zend_Log::ERR);
    }
}

