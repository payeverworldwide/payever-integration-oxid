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
     * Cancel payever transaction (if possible) when order is being canceled
     *
     * @throws \Exception
     */
    public function storno()
    {
        /** @var oxOrder $oOrder */
        $oOrder = oxNew("oxorder");

        if (!$oOrder->load($this->getEditObjectId())) {
            return parent::storno();
        }

        $paymentMethod = $oOrder->oxorder__oxpaymenttype->rawValue;

        if (PayeverConfig::isPayeverPaymentMethod($paymentMethod)) {
            $paymentId = $oOrder->oxorder__oxtransid->rawValue;
            $paymentsApiClient = PayeverApiClientProvider::getPaymentsApiClient();
            $actionDecider = new ActionDecider($paymentsApiClient);

            try {
                if ($actionDecider->isActionAllowed($paymentId, ActionDeciderInterface::ACTION_CANCEL, true)) {
                    $paymentsApiClient->cancelPaymentRequest($paymentId);
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
