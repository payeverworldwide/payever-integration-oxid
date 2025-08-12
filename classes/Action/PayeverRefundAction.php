<?php

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverRefundAction extends PayeverBaseAction
{
    /**
     * @inheritDoc
     */
    protected function sendAmountRequest($oxOrder, $amount, $identifier)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');

        return $this->getPaymentsApiClient()->refundPaymentRequest($paymentId, $amount, $identifier);
    }

    /**
     * @inheritDoc
     */
    protected function sendItemsRequest($oxOrder, $paymentItems, $identifier)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');

        return $this->getPaymentsApiClient()->refundItemsPaymentRequest(
            $paymentId,
            $paymentItems,
            null,
            $identifier
        );
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_REFUND;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_REFUNDED;
    }
}
