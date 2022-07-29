<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Action\ActionDecider;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverShippingGoodsHandler
{
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @param oxorder $order
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function triggerShippingGoodsPaymentRequest($order)
    {
        $paymentMethod = $order->getFieldData('oxpaymenttype');
        $this->getLogger()->debug('Start execution ' . __METHOD__);
        if ($this->getConfigHelper()->isPayeverPaymentMethod($paymentMethod)) {
            try {
                $paymentId = $order->getFieldData('oxtransid');
                $actionDecider = new ActionDecider($this->getPaymentsApiClient());
                $isActionAllowed = $actionDecider->isShippingAllowed($paymentId, false);
                if ($isActionAllowed) {
                    $requestEntity = new ShippingGoodsPaymentRequest();
                    $requestEntity
                        ->setCustomerId($order->getFieldData('oxuserid'))
                        ->setInvoiceId($order->getFieldData('oxinvoicenr'));

                    $this->getPaymentsApiClient()->shippingGoodsPaymentRequest($paymentId, $requestEntity);
                    $this->getLogger()->debug('ShippingGoodsPaymentRequest has been sent');
                } else {
                    $this->getLogger()->debug('Action is not allowed');
                }
            } catch (Exception $e) {
                $this->getLogger()->error(
                    'Shipping goods error',
                    [
                        'exception_message' => $e->getMessage(),
                        'paymentId' => isset($paymentId) ? $paymentId : 'N/A',
                    ]
                );
            }
        } else {
            $this->getLogger()->debug('Non-payever payment method is ignored');
        }
        $this->getLogger()->debug('Stop execution ' . __METHOD__);
    }
}
