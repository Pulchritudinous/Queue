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
 *
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Config
    extends Varien_Simplexml_Config
{
    /**
     * Returns default worker configuration.
     *
     * @return array
     */
    public function getWorkerDefaultConfig()
    {
        $config = $this->getWorkerConfig()->getNode("pulchqueue/worker_default")->asArray();

        return $config;
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
            $config = new Varien_Object(
                $resource
            );

            if (strtolower($config->getType()) == 'disable') {
                continue;
            }

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
        }

        return $workerConfig;
    }
}

