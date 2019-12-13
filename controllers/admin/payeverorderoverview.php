<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Action\ActionDecider;
use Payever\ExternalIntegration\Payments\Action\ActionDeciderInterface;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;

class payeverOrderOverview extends payeverOrderOverview_parent
{
    /**
     * Trigger Santander payments shipping_goods action (if possible) when order is being shipped
     *
     * @throws \Exception
     */
    public function sendorder()
    {
        /** @var oxOrder $oOrder */
        $oOrder = oxNew("oxorder");

        if (!$oOrder->load($this->getEditObjectId())) {
            return parent::sendorder();
        }

        $paymentMethod = $oOrder->oxorder__oxpaymenttype->rawValue;

        if (PayeverConfig::isPayeverPaymentMethod($paymentMethod)) {
            $paymentId = $oOrder->oxorder__oxtransid->rawValue;
            $paymentsApiClient = PayeverApiClientProvider::getPaymentsApiClient();
            $actionDecider = new ActionDecider($paymentsApiClient);

            try {
                if ($actionDecider->isActionAllowed($paymentId, ActionDeciderInterface::ACTION_SHIPPING_GOODS, false)) {
                    $requestEntity = new ShippingGoodsPaymentRequest();
                    $requestEntity
                        ->setCustomerId($oOrder->oxorder__oxuserid->rawValue)
                        ->setInvoiceId($oOrder->oxorder__oxinvoicenr->rawValue)
                    ;

                    $paymentsApiClient->shippingGoodsPaymentRequest($paymentId, $requestEntity);
                }
            } catch (Exception $exception) {
                PayeverConfig::getLogger()->error(
                    sprintf(
                        "Shipping goods error: %s; paymentId %s",
                        $exception->getMessage(),
                        $paymentId
                    ),
                    isset($shipmentData) ? $shipmentData : []
                );
            }
        }

        parent::sendorder();
    }
}
