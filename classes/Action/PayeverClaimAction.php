<?php

use Payever\Sdk\Payments\Http\RequestEntity\ClaimPaymentRequest;

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverClaimAction implements PayeverActionInterface
{
    use PayeverOxRequestTrait;
    use PayeverConfigTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @inheritDoc
     */
    public function processActionRequest($oxOrder)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');
        $isDisputed = (bool) $this->getRequest()->getRequestParameter('is_disputed');

        $claimPaymentRequest = new ClaimPaymentRequest();
        $claimPaymentRequest->setIsDisputed($isDisputed);

        return $this->getPaymentsApiClient()->claimPaymentRequest($paymentId, $claimPaymentRequest);
    }
}
