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
 * @see oxPaymentList
 */
class payeverOxPaymentList extends payeverOxPaymentList_parent
{
    public function getPaymentList($sShipSetId, $dPrice, $oUser = null)
    {
        $paymentList = parent::getPaymentList($sShipSetId, $dPrice, $oUser);

        /** @var PayeverMethodHider $payeverUtil */
        $payeverUtil = PayeverMethodHider::getInstance();

        foreach ($paymentList as $key => $method) {
            if (strpos($key, PayeverConfig::PLUGIN_PREFIX) === false) {
                continue;
            }

            $payeverMethod = strpos($key, '-') ? strstr($key, '-', true) : $key;
            $oxPaymentsVariants = json_decode($method->oxpayments__oxvariants->rawValue, true);
            if ($payeverUtil->isHiddenPaymentMethod($payeverMethod, $oxPaymentsVariants['variantId'])) {
                unset($paymentList[$key]);
            }
            if ($payeverUtil->validateB2BMethods($method)) {
                unset($paymentList[$key]);
            }
        }

        return $paymentList;
    }
}
