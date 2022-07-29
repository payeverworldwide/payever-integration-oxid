<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

class payeverOxOrderCompatible extends payeverOxOrderCompatible_parent
{
    use PayeverLoggerTrait;
    use PayeverRequestHelperTrait;

    /**
     * @var string
     */
    private $oxidOrderStatus = 'OK';

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
     * @param OxidEsales\EshopCommunity\Application\Model\Basket $oBasket Shopping basket object
     * @param oxUser $oUser Current user object
     * @param bool $blRecalculatingOrder Order recalculation
     *
     * For OXID >= 6
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
    public function finalizeOrder(
        OxidEsales\Eshop\Application\Model\Basket $oBasket,
        $oUser,
        $blRecalculatingOrder = false
    ) {
        $sPaymentId = $oBasket->getPaymentId();

        if (!in_array($sPaymentId, PayeverConfig::getMethodsList())) {
            return parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        }
        // fixes available payment method list for user on order validation during notification request
        if (!self::$_oActUser && $oUser) {
            $this->setUser($oUser);
        }

        // check if this order is already stored
        $sGetChallenge = oxRegistry::getSession()->getVariable('sess_challenge');
        if ($this->_checkOrderExist($sGetChallenge)) {
            oxRegistry::getUtils()->logger('BLOCKER');
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
                return $iOrderState;
            }
        }

        // copies user info
        $this->_setUser($oUser);

        // copies basket info
        $this->_loadFromBasket($oBasket);

        // payment information
        $oUserPayment = $this->_setPayment($oBasket->getPaymentId());

        // set folder information, if order is new
        // #M575 in recalculating order case folder must be the same as it was
        if (!$blRecalculatingOrder) {
            $this->_setFolder();
        }

        // marking as not finished
        $this->_setOrderStatus('NOT_FINISHED');

        //saving all order data to DB
        $this->save();

        // executing payment (on failure deletes order and returns error code)
        // in case when recalculating order, payment execution is skipped
        if (!$blRecalculatingOrder) {
            $blRet = $this->_executePayment($oBasket, $oUserPayment);
            if ($blRet !== true) {
                return $blRet;
            }
        }

        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        } else {
            oxNew('oxCounter')->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
        }

        $useTsProtection = method_exists($oBasket, 'getTsProductId')
            && method_exists($this, '_executeTsProtection');

        // executing TS protection
        if ($useTsProtection && !$blRecalculatingOrder && $oBasket->getTsProductId()) {
            $blRet = $this->_executeTsProtection($oBasket);
            if ($blRet !== true) {
                return $blRet;
            }
        }

        $fetchMode = $this->getRequestHelper()->getHeader('sec-fetch-dest');
        if ($fetchMode !== 'iframe') {
            $this->getLogger()->debug('Cleanup session');
            // deleting remark info only when order is finished
            oxRegistry::getSession()->deleteVariable('ordrem');
            oxRegistry::getSession()->deleteVariable('stsprotection');
        }

        $sPid = $this->getSession()->getVariable('oxidpayever_payment_id');
        $this->getSession()->deleteVariable('oxidpayever_payment_id');

        //#4005: Order creation time is not updated when order processing is complete
        if (!$blRecalculatingOrder) {
            $this->_updateOrderDate();
        }

        // updating order trans status (success status)
        $oxidOrderStatus = $this->getOrderStatus();
        $this->_setOrderStatus($oxidOrderStatus);
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
        $this->_updateWishlist($oBasket->getContents(), $oUser);

        // updating users notice list
        $this->_updateNoticeList($oBasket->getContents(), $oUser);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        if (!$blRecalculatingOrder) {
            $this->_markVouchers($oBasket, $oUser);

            // send order by email to shop owner and current user
            // skipping this action in case of order recalculation
            $this->_sendOrderByEmail($oUser, $oBasket, $oUserPayment);
        }

        return self::ORDER_STATE_OK;
    }

    /**
     * @param string $oxidOrderStatus
     * @return $this
     */
    public function setOrderStatus($oxidOrderStatus)
    {
        $this->oxidOrderStatus = $oxidOrderStatus;

        return $this;
    }

    /**
     * @return string
     */
    private function getOrderStatus()
    {
        return $this->oxidOrderStatus;
    }
}
