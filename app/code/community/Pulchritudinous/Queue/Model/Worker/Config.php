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
 * Worker configuration model.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Worker_Config
    extends Varien_Simplexml_Config
{
    /**
     * Returns default worker configuration.
     *
     * @param  boolean $asArray
     *
     * @return array|Varien_Object
     */
    public function getWorkerDefaultConfig($asArray = false)
    {
        $config = $this->getWorkerConfig()->getNode("pulchqueue/worker_default");

        if ($config) {
            $config = $config->asArray();
        } else {
            $config = [];
        }

        return ($asArray) ? $config : new Varien_Object($config);
    }

    /**
     * Returns worker configuration.
     *
     * @param  string $name
     *
     * @return Varien_Object|boolean
     */
    public function getWorkerConfigByName($name)
    {
        $workers = $this->getWorkers();

        if (!($config = $workers->getItemByColumnValue('worker_name', $name))) {
            return false;
        }

        $config->setData(
            array_merge(
                $this->getWorkerDefaultConfig(true),
                array_filter($config->getData())
            )
        );

        if (!$this->loadWorkerClass($config)) {
            return false;
        }

        return $config;
    }

    /**
     * Load worker model.
     *
     * @param  Varien_Object $config
     *
     * @return boolean
     */
    public function loadWorkerClass($config)
    {
        $class  = $config->getClass();
        $object = Mage::getModel($class);

        if (!$object || !($object instanceof Pulchritudinous_Queue_Model_Worker_Interface)) {
            return false;
        }

        $config->setWorkerModel($object);

        return true;
    }

    /**
     * Returns all active worker resources.
     *
     * @return Varien_Data_Collection
     */
    public function getWorkers()
    {
        $collection = new Varien_Data_Collection;
        $resources  = $this->getWorkerConfig()->getNode("pulchqueue/worker");

        if (!$resources) {
            return $collection;
        }

        $resources = $resources->asArray();

        foreach ($resources as $key => $resource) {
            $recClass   = null;
            $recMethod  = null;

            $config = new Varien_Object(
                $resource
            );

            if (strtolower($config->getType()) == 'disable') {
                continue;
            }

            if (strtolower($config->getType()) == 'test' && !Mage::getIsDeveloperMode()) {
                continue;
            }

            if ($recHelper = $config->getData('recurring/helper')) {
                if (is_array($recHelper)) {
                    $recClass   = $config->getData('recurring/helper/class');
                    $recMethod  = $config->getData('recurring/helper/method');
                } elseif (is_string($recHelper) && false !== strpos($recHelper, '::')) {
                    list($recClass, $recMethod) = explode('::', $recHelper);
                }
            }

            $recurring = $config->getRecurring();

            if (null !== $recClass && null !== $recMethod) {
                $recurring['is_allowed'] = (boolean) Mage::helper($recClass)->$recMethod;
            }

            if (true === is_array($recurring) && !isset($recurring['is_allowed'])) {
                $recurring['is_allowed'] = true;
            }

            $config->setRecurring($recurring);

            $config->setRule(strtolower($config->getRule()));
            $config->setWorkerName($key);

            $collection->addItem($config);
        }

        return $collection;
    }

    /**
     * Returns worker configuration.
     *
     * @return Varien_Simplexml_Config
     */
    public function getWorkerConfig()
    {
        $this->setCacheId('pulchqueue_worker_config');

        if (!($workerConfig = Mage::app()->loadCache($this->getCacheId()))) {
            $workerConfig = new Varien_Simplexml_Config;
            $workerConfig->loadString('<?xml version="1.0"?><config></config>');

            Mage::getConfig()->loadModulesConfiguration('worker.xml', $workerConfig);

            if (Mage::app()->useCache('config')) {
                Mage::app()->saveCache(
                    $workerConfig->getXmlString(), $this->getCacheId(),
                    [Mage_Core_Model_Config::CACHE_TAG]
                );
            }
        } else {
            $workerConfig = new Varien_Simplexml_Config($workerConfig);
        }

        return $workerConfig;
    }
}

