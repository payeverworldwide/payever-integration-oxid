<?php

/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

class payeverOxOrder extends payeverOxOrder_parent
{
    use PayeverLoggerTrait;
    use PayeverRequestHelperTrait;

    /**
     * {@inheritDoc}
     */
    public function save()
    {
        $isSendDateChanged = false;
        $emptyDateTimeValues = ['0000-00-00 00:00:00', '-'];
        $sendDateTimeValue = $this->oxorder__oxsenddate->value;
        if (!in_array($sendDateTimeValue, $emptyDateTimeValues, true)) {
            $oDb = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
            if ($oDb) {
                $order = $oDb->getRow("SELECT `OXSENDDATE` FROM `oxorder` WHERE `OXID` = ?;", [$this->getId()]);
                if (isset($order['OXSENDDATE']) && $order['OXSENDDATE'] !== $sendDateTimeValue) {
                    $isSendDateChanged = true;
                }
            }
        }
        $result = parent::save();
        if ($result && $isSendDateChanged) {
            (new PayeverShippingGoodsHandler())->triggerShippingGoodsPaymentRequest($this);
        }

        return $result;
    }

    /**
     * Overrides standard oxid finalizeOrder method
     *
     * @param oxBasket $oBasket Shopping basket object
     * @param oxUser $oUser Current user object
     * @param bool $blRecalculatingOrder Order recalculation
     * @param string $oxidOrderStatus
     *
     * For OXID < 6
     *
     * @return integer
     * @extend finalizeOrder
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function finalizeOrder(oxBasket $oBasket, $oUser, $blRecalculatingOrder = false, $oxidOrderStatus = 'OK')
    {
        $sPaymentId = $oBasket->getPaymentId();

        if (!in_array($sPaymentId, PayeverConfig::getMethodsList())) {
            $this->getLogger()->warning('Payment id not in methods list');
            return parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        }

        // check if this order is already stored
        $sGetChallenge = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('sess_challenge');
        if ($this->checkOrderExist($sGetChallenge)) {
            $this->getLogger()->info('Order is already exists');
            \OxidEsales\Eshop\Core\Registry::getUtils()->logger('BLOCKER');
            // we might use this later, this means that somebody klicked like mad on order button
            return self::ORDER_STATE_ORDEREXISTS;
        }

        // if not recalculating order, use sess_challenge id, else leave old order id
        if (!$blRecalculatingOrder) {
            // use this ID
            $this->setId($sGetChallenge);

            // validating various order/basket parameters before finalizing
            $iOrderState = $this->validateOrder($oBasket, $oUser);
            if ($iOrderState) {
                $this->getLogger()->warning('Various order validation failed');
                return $iOrderState;
            }
        }

        // copies user info
        $this->assignUserInformation($oUser);

        // copies basket info
        $this->loadFromBasket($oBasket);

        // payment information
        $oUserPayment = $this->setPayment($oBasket->getPaymentId());

        // set folder information, if order is new
        // #M575 in recalculating order case folder must be the same as it was
        if (!$blRecalculatingOrder) {
            $this->setFolder();
        }

        // marking as not finished
        $this->setOrderStatus('NOT_FINISHED');

        //saving all order data to DB
        $this->save();

        // executing payment (on failure deletes order and returns error code)
        // in case when recalculating order, payment execution is skipped
        if (!$blRecalculatingOrder) {
            $blRet = $this->executePayment($oBasket, $oUserPayment);
            if ($blRet !== true) {
                $this->getLogger()->warning('Execute payment failed');
                return $blRet;
            }
        }

        return $this->finalizeOrderPostProcessing(
            $oBasket,
            $blRecalculatingOrder,
            $oUser,
            $oUserPayment,
            $oxidOrderStatus
        );
    }

    /**
     * Overrides standard oxid finalizeOrder method
     *
     * @param oxBasket $oBasket Shopping basket object
     * @param bool $blRecalculatingOrder Order recalculation
     * @param oxUser $oUser Current user object
     * @param oxUserPayment $oUserPayment
     * @param string $oxidOrderStatus
     *
     * @return integer
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function finalizeOrderPostProcessing(
        $oBasket,
        $blRecalculatingOrder,
        $oUser,
        $oUserPayment,
        $oxidOrderStatus
    ) {
        if (!$this->oxorder__oxordernr->value) {
            $this->setNumber();
        } else {
            oxNew('oxCounter')->update($this->getCounterIdent(), $this->oxorder__oxordernr->value);
        }

        $useTsProtection = method_exists($oBasket, 'getTsProductId')
            && method_exists($this, '_executeTsProtection');

        // executing TS protection
        if ($useTsProtection && !$blRecalculatingOrder && $oBasket->getTsProductId()) {
            $blRet = $this->_executeTsProtection($oBasket);
            if ($blRet !== true) {
                $this->getLogger()->warning('TS protection failed');
                return $blRet;
            }
        }

        $fetchMode = $this->getRequestHelper()->getHeader('sec-fetch-dest');
        if ($fetchMode !== 'iframe') {
            $this->getLogger()->debug('Cleanup session');
            // deleting remark info only when order is finished
            \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('ordrem');
            \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('stsprotection');
        }

        $sPid = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('oxidpayever_payment_id');
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('oxidpayever_payment_id');

        //#4005: Order creation time is not updated when order processing is complete
        if (!$blRecalculatingOrder) {
            $this->updateOrderDate();
        }

        // updating order trans status (success status)
        $this->setOrderStatus($oxidOrderStatus);
        $userPayment = oxNew('oxUserPayment');
        $userPayment->load((string)$this->oxorder__oxpaymentid);
        $aParams = [
            'oxuserpayments__oxpspayever_transaction_id' => $sPid,
        ];
        $userPayment->assign($aParams);
        $userPayment->save();

        // store orderid
        $oBasket->setOrderId($this->getId());

        // updating wish lists
        $this->updateWishlist($oBasket->getContents(), $oUser);

        // updating users notice list
        $this->updateNoticeList($oBasket->getContents(), $oUser);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        if (!$blRecalculatingOrder) {
            $this->markVouchers($oBasket, $oUser);

            // send order by email to shop owner and current user
            // skipping this action in case of order recalculation
            $this->sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        }

        return self::ORDER_STATE_OK;
    }

    /**
     * OXID < 6 only has _checkOrderExist(). Provide the non-underscore version so our
     * finalizeOrder() call resolves, and delegate _checkOrderExist() back to it.
     */
    public function checkOrderExist($sGetChallenge)
    {
        return parent::_checkOrderExist($sGetChallenge);
    }

    protected function _checkOrderExist($sGetChallenge)
    {
        return $this->checkOrderExist($sGetChallenge);
    }

    /**
     * OXID < 6 only has _validateOrder().
     */
    public function validateOrder($oBasket, $oUser)
    {
        return parent::_validateOrder($oBasket, $oUser);
    }

    protected function _validateOrder($oBasket, $oUser)
    {
        return $this->validateOrder($oBasket, $oUser);
    }

    /**
     * OXID < 6 only has _setOrderStatus().
     */
    public function setOrderStatus($status)
    {
        parent::_setOrderStatus($status);
    }

    protected function _setOrderStatus($status)
    {
        $this->setOrderStatus($status);
    }
}
