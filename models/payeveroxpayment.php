<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getPaymentValue($dBasePrice)
    {
        if (in_array($this->oxpayments__oxid->value, PayeverConfig::getMethodsList())) {
            if (!$this->oxpayments__oxacceptfee->value) {
                $percentFee = $this->oxpayments__oxpercentfee->value;
                $fixedFee = $this->oxpayments__oxfixedfee->value;

                return $dBasePrice * $percentFee / 100 + $fixedFee;
            } else {
                return 0;
            }
        }

        return parent::getPaymentValue($dBasePrice);
    }

    /**
     * @return bool
     */
    public function isRedirectMethod()
    {
        return null !== $this->oxpayments__oxisredirectmethod->value;
    }
}
