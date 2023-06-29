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

        foreach (array_keys($paymentList) as $key) {
            $payeverMethod = strpos($key, '-') ? strstr($key, '-', true) : $key;
            if ($payeverUtil->isHiddenPaymentMethod($payeverMethod)) {
                unset($paymentList[$key]);
            }
        }

        return $paymentList;
    }
}
