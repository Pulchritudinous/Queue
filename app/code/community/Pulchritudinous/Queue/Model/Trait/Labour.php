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
/**
 * Trait class for the labour.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
trait Pulchritudinous_Queue_Model_Trait_Labour
{
    /**
     * Handle unexpected shutdown.
     */
    public function shutdownHandler()
    {
        $error = error_get_last();

        if (null === $error) {
            return;
        }

        list ($errNo, $errStr, $errFile, $errLine) = array_values($error);

        if ($this->getId()) {
            $errLine .= " and caused by labour ID {$this->getId()}";
        }

        $this->errorHandler($errNo, $errStr, $errFile, $errLine);

        switch ($errNo){
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_ERROR:
            case E_COMPILE_WARNING:
                $this->setAsFailed();
        }
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

