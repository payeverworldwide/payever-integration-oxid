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
 * @see oxPaymentList
 */
class payeverOxPaymentList extends payeverOxPaymentList_parent
{
    public function getPaymentList($sShipSetId, $dPrice, $oUser = null)
    {
        $paymentList = parent::getPaymentList($sShipSetId, $dPrice, $oUser);

        /** @var PayeverUtil $payeverUtil */
        $payeverUtil = PayeverUtil::getInstance();

        foreach ($paymentList as $key => $value) {
            if ($payeverUtil->isHiddenPaymentMethod($key)) {
                unset($paymentList[$key]);
            }
        }

        return $paymentList;
    }
}
