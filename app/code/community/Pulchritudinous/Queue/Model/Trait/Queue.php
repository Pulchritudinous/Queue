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
 * Trait class for the queue handler.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
trait Pulchritudinous_Queue_Model_Trait_Queue
{
    /**
     * Get allowed options.
     *
     * @param  array         $options
     * @param  Varien_Object $config
     *
     * @return Varien_Object
     */
    protected function _getOptions($options, $config)
    {
        $allowed = [
            'identity',
            'priority',
            'attempts',
            'delay',
            'execute_at',
            'by_recurring',
        ];

        $options = array_intersect_key(
            $options,
            array_flip($allowed)
        );

        $configOptions = array_intersect_key(
            $config->getData(),
            array_flip($allowed)
        );

        $mergedOptions = array_replace(
            $configOptions,
            $options
        );

        return new Varien_Object($mergedOptions);
    }

    /**
     * Parses when the labour should be executed.
     *
     * @param  Varien_Object           $config
     * @param  false|integer|Zend_Date $delay
     *
     * @return string
     */
    protected function _getWhen(Varien_Object $config, $delay = false)
    {
        $time   = time();
        $when   = $time + $config->getDelay();

        if ($delay !== false) {
            if (is_numeric($delay)) {
                $when = $time + $delay;
            } elseif ($delay instanceof Zend_Date) {
                $when = $delay->toString(Zend_Date::TIMESTAMP);
            } elseif (is_numeric($config->getDelay())) {
                $when = $time + $config->getDelay();
            }
        }

        return $when;
    }

    /**
     * Validate array data to make sure it does not contain objects.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function _validateArrayData($data)
    {
        $return = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->_validateArrayData($value);

                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $return[$key] = $value;
        }

        return $return;
    }
}

