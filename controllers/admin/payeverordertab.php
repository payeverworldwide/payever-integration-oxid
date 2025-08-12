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
    use PayeverActionFormTrait;
    use PayeverActionRequestTrait;
    use PayeverActionProcessorTrait;
    use PayeverLangTrait;

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
        !$this->dryRun && parent::render();

        /** @var oxOrder $oxOrder */
        $oxOrder = $this->getOrderFactory()->create();
        $oCurrency = $this->getConfig()->getActShopCurrencyObject();

        $sId = $this->getEditObjectId();
        if ((int)$sId !== -1 && $sId !== null && $oxOrder->load($sId)) {
            $isPayeverOrder = $this->isPayeverOrder($oxOrder);

            $this->_aViewData['edit'] = $oxOrder;
            $this->_aViewData['aProductVats'] = $oxOrder->getProductVats();
            $this->_aViewData['orderArticles'] = $oxOrder->getOrderArticles();
            $this->_aViewData['giftCard'] = $oxOrder->getGiftCard();
            $this->_aViewData['paymentType'] = $oxOrder->getPaymentType();
            $this->_aViewData['isPayeverOrder'] = $isPayeverOrder;
            $this->_aViewData['deliveryType'] = $oxOrder->getDelSet();

            if ($isPayeverOrder) {
                $this->_aViewData['transaction'] = $this->getOrderTransactionHelper()->getTransaction($oxOrder, true);
            }

            $sTsProtectedCosts = $oxOrder->getFieldData('oxtsprotectcosts');
            if ($sTsProtectedCosts) {
                $this->_aViewData['tsprotectcosts'] = $this->getLanguage()->formatCurrency(
                    $sTsProtectedCosts,
                    $oCurrency
                );
            }
        }

        // orders today
        $dSum = $oxOrder->getOrderSum(true);
        $this->_aViewData['ordersum'] = $this->getLanguage()->formatCurrency($dSum, $oCurrency);
        $this->_aViewData['ordercnt'] = $oxOrder->getOrderCnt(true);

        // ALL orders
        $dSum = $oxOrder->getOrderSum();
        $this->_aViewData['ordertotalsum'] = $this->getLanguage()->formatCurrency($dSum, $oCurrency);
        $this->_aViewData['ordertotalcnt'] = $oxOrder->getOrderCnt();
        $this->_aViewData['afolder'] = $this->getConfig()->getConfigParam('aOrderfolder');
        $this->_aViewData['currency'] = $oCurrency;

        return 'payever/order/tab.tpl';
    }

    /**
     * @return void
     * @throws oxSystemComponentException
     */
    public function processAction()
    {
        $sId = $this->getEditObjectId();

        /** @var oxOrder $oxOrder */
        $oxOrder = $this->getOrderFactory()->create();
        if (!$oxOrder->load($sId)) {
            return;
        }

        $action = $this->getActionRequest()->getAction();
        $type = $this->getActionRequest()->getType();

        try {
            if ($this->getValidator($action)->validate($oxOrder, $type)) {
                //Send partial amount request (ship, cancel, refund) to api
                $this->getActionProcessor()->processAction($oxOrder, $action);
            }
        } catch (\Exception $e) {
            $this->_aViewData['formError'][$action] = $this->getLanguage()->translateString($e->getMessage());
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
}
