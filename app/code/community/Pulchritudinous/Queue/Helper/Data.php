<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Pulchritudinous
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
 * Queue helper.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    /**
     * Convert nested mixed data array into array data.
     *
     * @param  array $data
     *
     * @return array
     */
    public function objectToArray($data)
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_object($value) && $this->isSerializable($value)) {
                if ($value instanceof Varien_Object) {
                    $value = $value->getData();
                } else {
                    $value = (array) $value;
                }
            }

            if (is_array($value)) {
                $result[$key] = $this->objectToArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Check if value is serializable.
     *
     * @param  mixed $var
     *
     * @return boolean
     */
    function isSerializable($var)
    {
        if (is_resource($var)) {
            return false;
        } else if ($var instanceof Closure) {
            return false;
        }

        return true;
    }
}

