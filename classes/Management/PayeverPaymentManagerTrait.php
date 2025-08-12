<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverPaymentManagerTrait
{
    /** @var PayeverPaymentManager */
    protected $paymentManager;

    /**
     * @param PayeverPaymentManager $paymentManager
     * @return $this
     */
    public function setPaymentManager(PayeverPaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;

        return $this;
    }

    /**
     * @return PayeverPaymentManager
     * @codeCoverageIgnore
     */
    protected function getPaymentManager()
    {
        return null === $this->paymentManager
            ? $this->paymentManager = new PayeverPaymentManager()
            : $this->paymentManager;
    }
}
