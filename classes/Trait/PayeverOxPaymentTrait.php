<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxPaymentTrait
{
    /**
     * @var oxpayment
     */
    private $oxPayment;

    /**
     * @codeCoverageIgnore
     *
     * @return oxpayment
     */
    protected function getOxPayment()
    {
        if ($this->oxPayment === null) {
            return oxNew('oxPayment');
        }

        return $this->oxPayment;
    }

    /**
     * @param oxpayment $oxPayment
     */
    public function setOxPayment($oxPayment)
    {
        $this->oxPayment = $oxPayment;

        return $this;
    }
}
