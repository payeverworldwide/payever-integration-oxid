<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

/**
 * Class PayeverFormInvoice
 */
class PayeverFormInvoice extends PayeverFormBase
{
    use PayeverOrderTransactionHelperTrait;

    /**
     * @inheritDoc
     */
    public function isActionAllowed($order, $actionType = null)
    {
        return $this->isInvoiceActionAllowed($order);
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $oxOrder
     *
     * @return array
     */
    public function isInvoiceActionAllowed($oxOrder)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed(
            $oxOrder,
            ActionDeciderInterface::ACTION_INVOICE
        );
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_INVOICE;
    }

    public function getActionField()
    {
        return PayeverActionTypeInterface::FIELD_INVOICED;
    }

    /**
     * @inheritDoc
     */
    public function partialAmountFormAllowed($order)
    {
        $allowed = $this->isInvoiceActionAllowed($order);
        return $allowed['partialAllowed'];
    }

    /**
     * @inheritDoc
     */
    public function prefillAmountAllowed($order)
    {
        return $this->getActions($order) || $this->getActions($order, payeverorderaction::ACTION_INVOICE);
    }

    /**
     * @inheritDoc
     */
    public function partialItemsFormAllowed($order)
    {
        return false;
    }
}
