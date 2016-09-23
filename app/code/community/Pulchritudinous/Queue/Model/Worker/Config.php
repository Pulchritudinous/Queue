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
class Pulchritudinous_Queue_Model_Worker_Config
{
    /**
     * Returns worker configuration.
     *
     * @param  string $name
     *
     * @return Varien_Object|boolean
     */
    public function getWorkerConfig($name)
    {
        $configModel    = Mage::getSingleton('pulchqueue/config');
        $workers        = $configModel->getWorkers();

        if (!($config = $workers->getItemByColumnValue('worker_name', $name))) {
            return false;
        }

        $config->setData(
            array_merge(
                $configModel->getWorkerDefaultConfig(),
                $config->getData()
            )
        );

        if (!$this->_loadWorkerClass($config)) {
            return false;
        }

        return $config;
    }

    /**
     *
     *
     * @param  Varien_Object $config
     *
     * @return boolean
     */
    protected function _loadWorkerClass($config)
    {
        $class  = $config->getClass();
        $object = Mage::getSingleton($class);

        if (!$object || !($object instanceof Pulchritudinous_Queue_Model_Worker_Interface)) {
            return false;
        }

        $config->setWorkerModel($object);

        return true;
    }
}

