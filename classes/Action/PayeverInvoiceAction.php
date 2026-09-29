<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

class PayeverInvoiceAction extends PayeverBaseAction
{
    /**
     * @inheritDoc
     */
    protected function sendAmountRequest($oxOrder, $amount, $identifier)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');

        return $this->getPaymentsApiClient()->invoicePaymentRequest($paymentId, $amount, $identifier);
    }

    /**
     * @inheritDoc
     */
    protected function sendItemsRequest($oxOrder, $paymentItems, $identifier)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_INVOICE;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return PayeverActionTypeInterface::FIELD_INVOICED;
    }
}
