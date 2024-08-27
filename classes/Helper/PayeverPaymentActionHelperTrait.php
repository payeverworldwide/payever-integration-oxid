<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverPaymentActionHelperTrait
{
    /** @var PayeverPaymentActionHelper */
    protected $paymentActionHelper;

    /**
     * @param PayeverPaymentActionHelper $paymentActionHelper
     * @return $this
     * @internal
     */
    public function setPaymentActionHelper(PayeverPaymentActionHelper $paymentActionHelper)
    {
        $this->paymentActionHelper = $paymentActionHelper;

        return $this;
    }

    /**
     * @return PayeverPaymentActionHelper
     * @codeCoverageIgnore
     */
    protected function getPaymentActionHelper()
    {
        return null === $this->paymentActionHelper
            ? $this->paymentActionHelper = new PayeverPaymentActionHelper()
            : $this->paymentActionHelper;
    }
}
