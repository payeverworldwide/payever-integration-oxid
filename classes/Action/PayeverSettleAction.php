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

class PayeverSettleAction extends PayeverBaseAction
{
    use PayeverOxRequestTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @inheritDoc
     */
    protected function sendAmountRequest($oxOrder, $amount, $identifier)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');
        return $this->getPaymentsApiClient()->settlePaymentRequest($paymentId, null);
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
        return ActionDeciderInterface::ACTION_SETTLE;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return PayeverActionTypeInterface::FIELD_SETTLED;
    }
}
