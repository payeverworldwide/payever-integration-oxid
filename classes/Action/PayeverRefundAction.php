<?php

use Payever\Sdk\Payments\Action\ActionDeciderInterface;
use Payever\Sdk\Payments\Enum\Status;

/**
 * PHP version 7 and 8.4
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
    protected function getOrderStatus($transaction)
    {
        if ($transaction['status'] !== Status::STATUS_CANCELLED) {
            return \OxidEsales\Eshop\Core\Registry::getLang()->translateString('Partially Refunded');
        }

        return parent::getOrderStatus($transaction);
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
        return PayeverActionTypeInterface::FIELD_REFUNDED;
    }
}
