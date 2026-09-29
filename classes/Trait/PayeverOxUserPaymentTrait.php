<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxUserPaymentTrait
{
    /**
     * @var oxuserpayment
     */
    private $oxUserPayment;

    /**
     * @codeCoverageIgnore
     * @return oxuserpayment
     */
    protected function getOxUserPayment()
    {
        if ($this->oxUserPayment === null) {
            return oxNew('oxUserPayment');
        }

        return $this->oxUserPayment;
    }

    /**
     * @param oxuserpayment $oxUserPayment
     *
     * @return $this
     */
    public function setOxUserPayment($oxUserPayment)
    {
        $this->oxUserPayment = $oxUserPayment;

        return $this;
    }
}
