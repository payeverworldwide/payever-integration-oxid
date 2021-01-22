<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverPaymentMethodFactoryTrait
{
    /** @var PayeverPaymentMethodFactory */
    protected $paymentMethodFactory;

    /**
     * @param PayeverPaymentMethodFactory $paymentMethodFactory
     * @return $this
     * @internal
     */
    public function setPaymentMethodFactory(PayeverPaymentMethodFactory $paymentMethodFactory)
    {
        $this->paymentMethodFactory = $paymentMethodFactory;

        return $this;
    }

    /**
     * @return PayeverPaymentMethodFactory
     * @codeCoverageIgnore
     */
    protected function getPaymentMethodFactory()
    {
        return null === $this->paymentMethodFactory
            ? $this->paymentMethodFactory = new PayeverPaymentMethodFactory()
            : $this->paymentMethodFactory;
    }
}
