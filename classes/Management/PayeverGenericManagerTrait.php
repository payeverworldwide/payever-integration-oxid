<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverGenericManagerTrait
{
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;

    /** @var array */
    private $errors = [];

    /** @var array */
    private $debugMessages = [];

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Cleans messages
     */
    private function cleanMessages()
    {
        $this->errors = $this->debugMessages = [];
    }

    /**
     * Logs messages
     */
    private function logMessages()
    {
        foreach ($this->errors as $error) {
            $this->getLogger()->warning($error);
        }
        foreach ($this->debugMessages as $debugMessage) {
            $this->getLogger()->debug(
                !empty($debugMessage['message']) ? $debugMessage['message'] : '',
                !empty($debugMessage['context']) ? $debugMessage['context'] : []
            );
        }
    }

    /**
     * @return bool
     */
    private function isProductsSyncEnabled()
    {
        $result = $this->getConfigHelper()->isProductsSyncEnabled();
        if (!$result) {
            $this->errors[] = 'Products and inventory synchronization is disabled';
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
            $this->errors[] = 'Products and inventory outwards synchronization is disabled';
        }

        return $result;
    }
}
