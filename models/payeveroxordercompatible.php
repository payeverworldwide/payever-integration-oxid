<?php

/**
 * PHP version 7 and 8.4
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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
        $sGetChallenge = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('sess_challenge');
        if ($this->checkOrderExist($sGetChallenge)) {
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
                return $iOrderState;
            }
        }

        // copies user info
        $this->callOxidOrderMethod('_setUser', 'assignUserInformation', $oUser);

        // copies basket info
        $this->callOxidOrderMethod('_loadFromBasket', 'loadFromBasket', $oBasket);

        // payment information
        $oUserPayment = $this->callOxidOrderMethod('_setPayment', 'setPayment', $oBasket->getPaymentId());

        // set folder information, if order is new
        // #M575 in recalculating order case folder must be the same as it was
        if (!$blRecalculatingOrder) {
            $this->callOxidOrderMethod('_setFolder', 'setFolder');
        }

        // marking as not finished
        $this->callParentSetOrderStatus('NOT_FINISHED');

        //saving all order data to DB
        $this->save();

        // executing payment (on failure deletes order and returns error code)
        // in case when recalculating order, payment execution is skipped
        if (!$blRecalculatingOrder) {
            $blRet = $this->callOxidOrderMethod('_executePayment', 'executePayment', $oBasket, $oUserPayment);
            if ($blRet !== true) {
                return $blRet;
            }
        }

        return $this->finalizeOrderPostProcessing($oBasket, $blRecalculatingOrder, $oUser, $oUserPayment);
    }

    /**
     * Overrides standard oxid finalizeOrder method
     *
     * @param OxidEsales\EshopCommunity\Application\Model\Basket $oBasket Shopping basket object
     * @param bool $blRecalculatingOrder Order recalculation
     * @param oxUser $oUser Current user object
     * @param oxUserPayment $oUserPayment
     *
     * @return integer
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function finalizeOrderPostProcessing($oBasket, $blRecalculatingOrder, $oUser, $oUserPayment)
    {
        if (!$this->oxorder__oxordernr->value) {
            $this->callOxidOrderMethod('_setNumber', 'setNumber');
        } else {
            oxNew('oxCounter')->update($this->getCounterIdent(), $this->oxorder__oxordernr->value);
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
            \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('ordrem');
            \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('stsprotection');
        }

        $sPid = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('oxidpayever_payment_id');
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('oxidpayever_payment_id');

        //#4005: Order creation time is not updated when order processing is complete
        if (!$blRecalculatingOrder) {
            $this->callOxidOrderMethod('_updateOrderDate', 'updateOrderDate');
        }

        // updating order trans status (success status)
        $this->callParentSetOrderStatus($this->getPayeverOrderStatus());
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
        $this->callOxidOrderMethod('_updateWishlist', 'updateWishlist', $oBasket->getContents(), $oUser);

        // updating users notice list
        $this->callOxidOrderMethod('_updateNoticeList', 'updateNoticeList', $oBasket->getContents(), $oUser);

        // marking vouchers as used and sets them to $this->_aVoucherList (will be used in order email)
        // skipping this action in case of order recalculation
        if (!$blRecalculatingOrder) {
            $this->callOxidOrderMethod('_markVouchers', 'markVouchers', $oBasket, $oUser);

            if (!isset($_GET['skipEmail'])) {
                // send order by email to shop owner and current user
                // skipping this action in case of order recalculation
                $this->callOxidOrderMethod('_sendOrderByEmail', 'sendOrderByEmail', $oUser, $oBasket, $oUserPayment);
            }
        }

        return self::ORDER_STATE_OK;
    }

    /**
     * @param string $oxidOrderStatus
     * @return $this
     */
    public function setPayeverOrderStatus($oxidOrderStatus)
    {
        $this->oxidOrderStatus = $oxidOrderStatus;

        return $this;
    }

    /**
     * @return string
     */
    private function getPayeverOrderStatus()
    {
        return $this->oxidOrderStatus;
    }

    /**
     * OXID 6.8 has _checkOrderExist() (protected); OXID 7 renamed it to checkOrderExist() (public).
     * Provide the public non-underscore version so finalizeOrder() works on both.
     * No _checkOrderExist() override here — adding one removes the parent's default parameter value
     * which triggers a PHP 8.0 deprecation warning at class-definition time, disrupting page rendering.
     */
    public function checkOrderExist($sGetChallenge = null)
    {
        return PayeverConfig::getOxidMajorVersion() >= 7
            ? parent::checkOrderExist($sGetChallenge)
            : parent::_checkOrderExist($sGetChallenge);
    }

    /**
     * OXID 6.8 has _setOrderStatus() (protected); OXID 7 renamed it to setOrderStatus() (public).
     */
    private function callParentSetOrderStatus($status)
    {
        if (PayeverConfig::getOxidMajorVersion() >= 7) {
            parent::setOrderStatus($status);
        } else {
            parent::_setOrderStatus($status);
        }
    }

    /**
     * OXID 7 renamed many Order methods, removing the underscore prefix.
     * This helper resolves the correct name and calls it on $this so all
     * visibility levels (protected in OXID 6, public in OXID 7) are accessible.
     *
     * @param string $method6 method name on OXID 6.8 (underscore-prefixed)
     * @param string $method7 method name on OXID 7+ (no underscore)
     * @param mixed  ...$args arguments forwarded to the method
     * @return mixed
     */
    private function callOxidOrderMethod($method6, $method7, ...$args)
    {
        $method = PayeverConfig::getOxidMajorVersion() >= 7 ? $method7 : $method6;
        return $this->$method(...$args);
    }
}
