<?php

/**
 * Class payeverordertab
 */
class payeverordertab extends oxAdminDetails
{
    use DryRunTrait;
    use PayeverConfigTrait;
    use PayeverConfigHelperTrait;
    use PayeverOrderFactoryTrait;
    use PayeverOrderTransactionHelperTrait;
    use PayeverOrderActionManagerTrait;

    /** @var oxLang */
    protected $lang;

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        !$this->dryRun && parent::__construct();
    }

    /**
     * @return string
     * @throws oxSystemComponentException
     */
    public function render()
    {
        parent::render();

        /** @var oxOrder $oOrder */
        $oOrder = $this->getOrderFactory()->create();
        $oCurrency = $this->getConfig()->getActShopCurrencyObject();

        $sId = $this->getEditObjectId();
        if ((int)$sId !== -1 && $sId !== null && $oOrder->load($sId)) {
            $isPayeverOrder = $this->isPayeverOrder($oOrder);

            $this->_aViewData['edit'] = $oOrder;
            $this->_aViewData['aProductVats'] = $oOrder->getProductVats();
            $this->_aViewData['orderArticles'] = $oOrder->getOrderArticles();
            $this->_aViewData['giftCard'] = $oOrder->getGiftCard();
            $this->_aViewData['paymentType'] = $oOrder->getPaymentType();
            $this->_aViewData['isPayeverOrder'] = $isPayeverOrder;
            $this->_aViewData['deliveryType'] = $oOrder->getDelSet();

            if ($isPayeverOrder) {
                $this->_aViewData['transaction'] = $this->getOrderTransactionHelper()->getTransaction($oOrder, true);
            }

            $sTsProtectedCosts = $oOrder->getFieldData('oxtsprotectcosts');
            if ($sTsProtectedCosts) {
                $this->_aViewData['tsprotectcosts'] = $this->getLanguage()->formatCurrency(
                    $sTsProtectedCosts,
                    $oCurrency
                );
            }
        }

        // orders today
        $dSum = $oOrder->getOrderSum(true);
        $this->_aViewData['ordersum'] = $this->getLanguage()->formatCurrency($dSum, $oCurrency);
        $this->_aViewData['ordercnt'] = $oOrder->getOrderCnt(true);

        // ALL orders
        $dSum = $oOrder->getOrderSum();
        $this->_aViewData['ordertotalsum'] = $this->getLanguage()->formatCurrency($dSum, $oCurrency);
        $this->_aViewData['ordertotalcnt'] = $oOrder->getOrderCnt();
        $this->_aViewData['afolder'] = $this->getConfig()->getConfigParam('aOrderfolder');
        $this->_aViewData['currency'] = $oCurrency;

        return 'payever/order/tab.tpl';
    }

    /**
     * Send order action request to Payever
     *
     * @return void
     * @throws Exception
     */
    public function processTotal()
    {
        $sId = $this->getEditObjectId();

        /** @var oxOrder $oOrder */
        $oOrder = $this->getOrderFactory()->create();
        if (!$oOrder->load($sId)) {
            return;
        }

        $type = $this->getConfig()->getRequestParameter('actionType');
        $total = $this->getOrderTransactionHelper()->getTotal($oOrder);

        //Send request (ship, cancel, refund) to api
        $response = $this->getManager($type)->processAmount($oOrder, $total);
        if (isset($response['error'])) {
            $this->_aViewData['formError'][$type] = $response['error'];
        }
    }

    /**
     * Send partial amount request to Payever
     *
     * @return void
     * @throws Exception
     */
    public function processAmount()
    {
        $sId = $this->getEditObjectId();

        /** @var oxOrder $oOrder */
        $oOrder = $this->getOrderFactory()->create();
        if (!$oOrder->load($sId)) {
            return;
        }

        $type = $this->getConfig()->getRequestParameter('actionType');

        $validator = $this->getValidator($type);
        if (!$validator->validateAmount($oOrder)) {
            $this->_aViewData['formError'][$type] = $this->getLanguage()->translateString($validator->getError());

            return;
        }

        $amount = $validator->getAmount();

        //Send partial amount request (ship, cancel, refund) to api
        $response = $validator->getManager()->processAmount($oOrder, $amount);
        if (isset($response['error'])) {
            $this->_aViewData['formError'][$type] = $response['error'];
        }
    }

    /**
     * Send partial items request to Payever
     *
     * @return void
     * @throws Exception
     */
    public function processItems()
    {
        $sId = $this->getEditObjectId();

        /** @var oxOrder $oOrder */
        $oOrder = $this->getOrderFactory()->create();
        if (!$oOrder->load($sId)) {
            return;
        }

        $type = $this->getConfig()->getRequestParameter('actionType');

        $validator = $this->getValidator($type);
        if (!$validator->validateItems()) {
            $this->_aViewData['formError'][$type] = $this->getLanguage()->translateString($validator->getError());

            return;
        }

        //Send partial items request (ship, cancel, refund) to api
        $items = $validator->getItems();
        $response = $validator->getManager()->processItems($oOrder, $items);

        if (isset($response['error'])) {
            $this->_aViewData['formError'][$type] = $response['error'];
        }
    }

    /**
     * @param oxOrder $order
     *
     * @return bool
     */
    protected function isPayeverOrder($order)
    {
        $paymentMethod = $order->getFieldData('oxpaymenttype');

        return $this->getConfigHelper()->isPayeverPaymentMethod($paymentMethod);
    }

    /**
     * Returns the language object.
     *
     * @return oxLang
     */
    protected function getLanguage()
    {
        return null === $this->lang
            ? $this->lang = oxregistry::getLang()
            : $this->lang;
    }
}
