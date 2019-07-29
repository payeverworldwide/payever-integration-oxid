<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * @see oxPayment
 */
class payeverOxPayment extends payeverOxPayment_parent
{
    public function isPayeverFee($method)
    {
        if (in_array($method, PayeverConfig::getMethodsList())) {
            return true;
        }

        return false;
    }

    /**
     * Returns additional taxes to base article price.
     *
     * @param double $dBasePrice Base article price
     *
     * @return double
     */
    public function getPaymentValue($dBasePrice)
    {
        if (in_array($this->oxpayments__oxid->value, PayeverConfig::getMethodsList())) {
            if (!$this->oxpayments__oxacceptfee->value) {
                return $dBasePrice * $this->oxpayments__oxpercentfee->value / 100 + $this->oxpayments__oxfixedfee->value;
            } else {
                return 0;
            }
        }

        return parent::getPaymentValue($dBasePrice);
    }
}
