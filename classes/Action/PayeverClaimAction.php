<?php

use Payever\Sdk\Payments\Http\RequestEntity\ClaimPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ClaimPaymentResponse;

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverClaimAction implements PayeverActionInterface
{
    use PayeverConfigTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @param oxOrder $oxOrder
     * @return ClaimPaymentResponse
     *
     * @throws Exception
     */
    public function processActionRequest($oxOrder)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');
        $isDisputed = (bool) $this->getConfig()->getRequestParameter('is_disputed');

        $claimPaymentRequest = new ClaimPaymentRequest();
        $claimPaymentRequest->setIsDisputed($isDisputed);

        return $this->getPaymentsApiClient()->claimPaymentRequest($paymentId, $claimPaymentRequest);
    }
}
