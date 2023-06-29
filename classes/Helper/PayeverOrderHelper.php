<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverOrderHelper
{
    use PayeverConfigHelperTrait;

    /**
     * @param oxbasket $cart
     * @return float
     */
    public function getAmountByCart($cart)
    {
        $version = $this->getConfigHelper()->getOxidVersionInt();
        $paymentCost = $version > 47
            ? ($cart->getPaymentCost() instanceof oxprice ? $cart->getPaymentCost()->getPrice() : 0)
            : ($cart->getCosts('oxpayment') instanceof oxprice ? $cart->getCosts('oxpayment')->getPrice() : 0);

        return (float) ($cart->getPrice()->getPrice() - $paymentCost);
    }

    /**
     * @param oxbasket $cart
     * @return float
     */
    public function getFeeByCart($cart)
    {
        $version = $this->getConfigHelper()->getOxidVersionInt();

        return $version > 47
            ? (float) $cart->getDeliveryCost()->getPrice()
            : (float) $cart->getCosts('oxdelivery')->getPrice();
    }
}
