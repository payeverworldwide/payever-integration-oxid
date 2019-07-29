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

class payeverOrderList extends payeverOrderList_parent
{
    /**
     * Cancels order and its order articles
     */
    public function storno()
    {
        $oOrder = oxNew("oxorder");

        if ($oOrder->load($this->getEditObjectId())) {
            $paymentId = $oOrder->oxorder__oxtransid->rawValue;
            $api = PayeverApi::getInstance();
            $actionDecider = new ActionDecider($api);

            try {
                if ($actionDecider->isActionAllowed($paymentId, ActionDeciderInterface::ACTION_CANCEL, true)) {
                    $api->cancelPaymentRequest($paymentId);
                }
            } catch (Exception $exception) {
                PayeverConfig::getLogger()->error(
                    sprintf(
                        "Cancel payment error: %s; paymentId %s",
                        $exception->getMessage(),
                        $paymentId
                    )
                );
            }
        }

        parent::storno();
    }
}
