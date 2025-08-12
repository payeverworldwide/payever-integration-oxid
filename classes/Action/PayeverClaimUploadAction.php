<?php

use Payever\Sdk\Payments\Http\RequestEntity\ClaimUploadPaymentRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\ClaimUploadPaymentResponse;

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */
class PayeverClaimUploadAction implements PayeverActionInterface
{
    use PayeverConfigTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @param oxOrder $oxOrder
     * @return void
     * @throws Exception
     */
    public function processActionRequest($oxOrder)
    {
        $paymentId = $oxOrder->getFieldData('oxtransid');
        $files = $this->getConfig()->getUploadedFile('claim_upload_files');

        if (empty($files)) {
            throw new \InvalidArgumentException('Invoice files were not selected.');
        }

        foreach ($files['name'] as $key => $invoice) {
            $content = file_get_contents($files['tmp_name'][$key]);

            $claimUploadPaymentRequest = new ClaimUploadPaymentRequest();
            $claimUploadPaymentRequest->setFileName($invoice);
            $claimUploadPaymentRequest->setMimeType($files['type'][$key]);
            $claimUploadPaymentRequest->setBase64Content(base64_encode($content));
            $claimUploadPaymentRequest->setDocumentType(ClaimUploadPaymentRequest::DOCUMENT_TYPE_INVOICE);

            /** @var ClaimUploadPaymentResponse $result */
            $this->getPaymentsApiClient()->claimUploadPaymentRequest($paymentId, $claimUploadPaymentRequest);
        }
    }
}
