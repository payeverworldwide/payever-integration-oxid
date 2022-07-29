<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Psr\Log\LogLevel;

trait PayeverGenericManagerTrait
{
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;

    /** @var array */
    protected $logMessages = [];

    /**
     * @param array|string $error
     * @return $this
     */
    public function addError($error)
    {
        if (!isset($this->logMessages[LogLevel::ERROR])) {
            $this->logMessages[LogLevel::ERROR] = [];
        }
        $this->logMessages[LogLevel::ERROR][] = $error;

        return $this;
    }

    /**
     * @param array|string $info
     * @return $this
     */
    public function addInfo($info)
    {
        if (!isset($this->logMessages[LogLevel::INFO])) {
            $this->logMessages[LogLevel::INFO] = [];
        }
        $this->logMessages[LogLevel::INFO][] = $info;

        return $this;
    }

    /**
     * @param array|string $debug
     * @return $this
     */
    public function addDebug($debug)
    {
        if (!isset($this->logMessages[LogLevel::DEBUG])) {
            $this->logMessages[LogLevel::DEBUG] = [];
        }
        $this->logMessages[LogLevel::DEBUG][] = $debug;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return !empty($this->logMessages[LogLevel::ERROR]) ? $this->logMessages[LogLevel::ERROR] : [];
    }

    /**
     * Cleans messages
     */
    private function cleanMessages()
    {
        $this->logMessages = [];
    }

    /**
     * Logs messages
     */
    private function logMessages()
    {
        foreach ($this->logMessages as $level => $messages) {
            foreach ($messages as $item) {
                $message = is_array($item) && !empty($item['message']) ? $item['message'] : $item;
                $context = is_array($item) && !empty($item['context']) ? $item['context'] : [];
                $this->getLogger()->log($level, $message, $context);
            }
        }
    }

    /**
     * @return bool
     */
    private function isProductsSyncEnabled()
    {
        $result = $this->getConfigHelper()->isProductsSyncEnabled();
        if (!$result) {
            $this->addError('Products and inventory synchronization is disabled');
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isProductsOutwardSyncEnabled()
    {
        $result = $this->getConfigHelper()->isProductsOutwardSyncEnabled();
        if (!$result) {
            $this->addError('Products and inventory outwards synchronization is disabled');
        }

        return $result;
    }
}
