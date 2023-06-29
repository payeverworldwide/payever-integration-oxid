<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverOrderActionValidator
{
    use PayeverConfigTrait;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var PayeverOrderActionManager
     */
    protected $manager;

    /**
     * @param PayeverOrderActionManager $manager
     */
    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get amount from partial form
     *
     * @return float
     */
    public function getAmount()
    {
        $amount = $this->getConfig()->getRequestParameter('amount');

        return (float) str_replace(',', '.', $amount);
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

    /**
     * Validate partial amount form
     *
     * @param oxOrder $order
     * @return bool
     * @throws oxConnectionException
     */
    public function validateAmount($order)
    {
        //Check form confirm checkbox
        if ($this->manager->confirmCheckbox) {
            $confirm = (bool) $this->getConfig()->getRequestParameter('payeverConfirm');
            if (!$confirm) {
                $this->error = 'PAYEVER_ORDER_CHECK_' . strtoupper($this->manager->getActionType()) . '_CHECKBOX';

                return false;
            }
        }

        $amount = $this->getAmount();

        //Check if amount is not empty
        if ($amount <= 0) {
            $this->error = 'PAYEVER_ORDER_INVALID_' . strtoupper($this->manager->getActionType()) . '_AMOUNT';

            return false;
        }

        //Check if total shipped amount <= total amount
        $partialTotal = $this->manager->getSentAmount($order);
        if ($amount + $partialTotal > (float) $order->oxorder__oxtotalordersum->value) {
            $this->error = 'PAYEVER_ORDER_INVALID_' . strtoupper($this->manager->getActionType()) . '_AMOUNT';

            return false;
        }

        return true;
    }

    /**
     * Validate partial items form
     *
     * @return bool
     */
    public function validateItems()
    {
        $formItems = $this->getItems();

        //Check order items was selected
        if (!$formItems) {
            $this->error = 'PAYEVER_ORDER_INVALID_' . strtoupper($this->manager->getActionType()) . '_AMOUNT';

            return false;
        }

        return true;
    }

    /**
     * Get form error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get action manager (cancel|refund|shipping)
     *
     * @return PayeverOrderActionManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
