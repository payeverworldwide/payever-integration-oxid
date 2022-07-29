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

class payeverOrderList extends payeverOrderList_parent
{
    use DryRunTrait;
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;
    use PayeverOrderFactoryTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        !$this->dryRun && parent::__construct();
    }

    /**
     * Cancel payever transaction (if possible) when order is being canceled
     *
     * @throws \Exception
     */
    public function storno()
    {
        $oOrder = $this->getOrderFactory()->create();
        // @codeCoverageIgnoreStart
        if (!$this->dryRun && !$oOrder->load($this->getEditObjectId())) {
            return parent::storno();
        }
        // @codeCoverageIgnoreEnd
        $paymentMethod = $oOrder->getFieldData('oxpaymenttype');
        if ($this->getConfigHelper()->isPayeverPaymentMethod($paymentMethod)) {
            $paymentId = $oOrder->getFieldData('oxtransid');
            $actionDecider = new ActionDecider($this->getPaymentsApiClient());

            try {
                if ($actionDecider->isRefundAllowed($paymentId, false)) {
                    $this->getPaymentsApiClient()->refundPaymentRequest(
                        $paymentId,
                        $oOrder->getTotalOrderSum()
                    );
                } elseif ($actionDecider->isCancelAllowed($paymentId)) {
                    $this->getPaymentsApiClient()->cancelPaymentRequest($paymentId);
                }
            } catch (Exception $exception) {
                $this->getLogger()->error(
                    sprintf(
                        'Cancel payment error: %s; paymentId %s',
                        $exception->getMessage(),
                        $paymentId
                    )
                );
            }
        }

        !$this->dryRun && parent::storno();
    }
}
