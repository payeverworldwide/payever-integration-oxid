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

class payeverOrderOverview extends payeverOrderOverview_parent
{
    /**
     * Sends order.
     */
    public function sendorder()
    {
        $oOrder = oxNew("oxorder");

        if ($oOrder->load($this->getEditObjectId())) {
            $paymentId = $oOrder->oxorder__oxtransid->rawValue;
            $api = PayeverApi::getInstance();
            $actionDecider = new ActionDecider($api);

            try {
                if ($actionDecider->isActionAllowed($paymentId, ActionDeciderInterface::ACTION_SHIPPING_GOODS, false)) {
                    $shipmentData = [
                        'customer_id' => $oOrder->oxorder__oxuserid->rawValue,
                        'invoice_id' => $oOrder->oxorder__oxinvoicenr->rawValue,
                        'invoice_date' => ''
                    ];

                    $api->shippingGoodsPaymentRequest($paymentId, $shipmentData);
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
