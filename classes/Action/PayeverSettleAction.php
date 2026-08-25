<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverSettleAction implements PayeverActionInterface
{
    use PayeverConfigTrait;
    use PayeverFieldFactoryTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @inheritDoc
     */
    public function processActionRequest($oxOrder)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');
        $response =  $this->getPaymentsApiClient()->settlePaymentRequest($paymentId);

        //Change order status
        $status = str_replace('STATUS_', '', $response->getResponseEntity()->getResult()->getStatus());
        $oxOrder->oxorder__oxtransstatus = $this->getFieldFactory()->createRaw($status);
        $oxOrder->save();

        return $response;
    }
}
