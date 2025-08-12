<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverActionRequest
{
    use PayeverConfigTrait;

    /**
     * Get action
     *
     * @return float
     */
    public function getAction()
    {
        return $this->getConfig()->getRequestParameter('action');
    }

    /**
     * Get action type
     *
     * @return float
     */
    public function getType()
    {
        return $this->getConfig()->getRequestParameter('type');
    }

    /**
     * Get amount from partial form
     *
     * @return float
     */
    public function getAmount()
    {
        $amount = $this->getConfig()->getRequestParameter('amount') ?: 0;

        return (float)str_replace(',', '.', $amount);
    }

    /**
     * Get active items from partial form
     *
     * @return array
     */
    public function getItems()
    {
        $itemQnt = oxRegistry::getConfig()->getRequestParameter('itemQnt');
        $itemActive = oxRegistry::getConfig()->getRequestParameter('itemActive');

        if (!$itemActive || !$itemQnt) {
            return [];
        }

        return array_filter(array_intersect_key($itemQnt, $itemActive));
    }
}
