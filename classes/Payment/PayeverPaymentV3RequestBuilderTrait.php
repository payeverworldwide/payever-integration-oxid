<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverPaymentV3RequestBuilderTrait
{
    /** @var PayeverPaymentV3RequestBuilder */
    protected $payeverPaymentV3RequestBuilder;

    /**
     * @param PayeverPaymentV3RequestBuilder $payeverPaymentV3RequestBuilder
     *
     * @return $this
     */
    public function setPaymentV3RequestBuilder(PayeverPaymentV3RequestBuilder $payeverPaymentV3RequestBuilder)
    {
        $this->payeverPaymentV3RequestBuilder = $payeverPaymentV3RequestBuilder;

        return $this;
    }

    /**
     * @return PayeverPaymentV3RequestBuilder
     *
     * @codeCoverageIgnore
     */
    protected function getPaymentV3RequestBuilder()
    {
        return null === $this->payeverPaymentV3RequestBuilder
            ? $this->payeverPaymentV3RequestBuilder = new PayeverPaymentV3RequestBuilder()
            : $this->payeverPaymentV3RequestBuilder;
    }
}
