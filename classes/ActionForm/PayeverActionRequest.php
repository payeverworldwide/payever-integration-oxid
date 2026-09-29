<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverActionRequest
{
    use PayeverOxRequestTrait;
    use PayeverConfigTrait;

    /**
     * Get action
     *
     * @return float
     */
    public function getAction()
    {
        return $this->getRequest()->getRequestParameter('action');
    }

    /**
     * Get action type
     *
     * @return float
     */
    public function getType()
    {
        return $this->getRequest()->getRequestParameter('type');
    }

    /**
     * Get amount from partial form
     *
     * @return float
     */
    public function getAmount()
    {
        $amount = $this->getRequest()->getRequestParameter('amount') ?: 0;

        return (float)str_replace(',', '.', $amount);
    }

    /**
     * Get active items from partial form
     *
     * @return array
     */
    public function getItems()
    {
        $itemQnt = \OxidEsales\Eshop\Core\Registry::getRequest()->getRequestParameter('itemQnt');
        $itemActive = \OxidEsales\Eshop\Core\Registry::getRequest()->getRequestParameter('itemActive');

        if (!$itemActive || !$itemQnt) {
            return [];
        }

        return array_filter(array_intersect_key($itemQnt, $itemActive));
    }
}
