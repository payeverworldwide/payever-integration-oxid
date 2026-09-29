<?php

/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 * @see oxorder
 */
class payeverOrder extends payeverOrder_parent
{
    use PayeverOxRequestTrait;
    use PayeverOxConfigTrait;

    /** @var \OxidEsales\Eshop\Core\Session|null */
    private $payeverSession;

    public function setSession($session)
    {
        $this->payeverSession = $session;
        return $this;
    }

    private function getPayeverSession()
    {
        return $this->payeverSession ?? \OxidEsales\Eshop\Core\Registry::getSession();
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $oSession = $this->getPayeverSession();
        $oBasket = $oSession->getBasket();
        $sPaymentId = $oBasket->getPaymentId();
        $deliveryMd5 = $this->getRequest()->getRequestParameter('sDeliveryAddressMD5');
        if ($deliveryMd5) {
            $oSession->setVariable('oxidpayever_delivery_md5', $deliveryMd5);
        }

        if (
            !$this->getRequest()->getRequestParameter('ord_agb')
            && $this->getConfig()->getConfigParam('blConfirmAGB')
        ) {
            \OxidEsales\Eshop\Core\Registry::get('oxUtilsView')->addErrorToDisplay('READ_AND_CONFIRM_TERMS', false, true);

            return null;
        }
        $sPid = $oSession->getVariable('oxidpayever_payment_id');
        if (!$sPid && in_array($sPaymentId, PayeverConfig::getMethodsList())) {
            $oSession->setVariable('paymentid', $sPaymentId);

            return $this->getNextStep(1);
        }

        return parent::execute();
    }

    public function getIframePaymentUrl()
    {
        if ($this->getPayeverSession()->getVariable('oxidpayever_payment_view_iframe_url')) {
            return $this->getPayeverSession()->getVariable('oxidpayever_payment_view_iframe_url');
        }

        return '';
    }

    public function clearIframeSession()
    {
        $this->getPayeverSession()->deleteVariable('oxidpayever_payment_view_type');
    }

    public function isIframePayeverPayment()
    {
        $sessPayeverPaymentView = $this->getPayeverSession()->getVariable('oxidpayever_payment_view_type');

        return $sessPayeverPaymentView === 'iframe';
    }

    /**
     * OXID 7 renamed _getNextStep() to getNextStep(). Provide both so the core
     * call in each version lands in our logic.
     *
     * @param $iSuccess
     * @return string
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getNextStep($iSuccess)
    {
        $nextStep = PayeverConfig::getOxidMajorVersion() >= 7
            ? parent::getNextStep($iSuccess)
            : parent::_getNextStep($iSuccess);

        if ($nextStep == 'thankyou') {
            $oSession = $this->getPayeverSession();
            $oBasket = $oSession->getBasket();
            $sPaymentId = $oBasket->getPaymentId();

            $oOrder = oxNew('oxorder');
            $oOrder->load($oBasket->getOrderId());

            if (strpos($sPaymentId, 'oxpe_') === 0) {
                $dispatcher = new payeverstandarddispatcher();
                $redirectUrl = $dispatcher->getRedirectUrl();

                if (!$redirectUrl) {
                    return 'payment';
                }

                $isRedirectMethod = $this->getPayeverSession()->getVariable(PayeverConfig::SESS_IS_REDIRECT_METHOD);
                if ($isRedirectMethod || PayeverConfig::getIsRedirect() || !PayeverConfig::ALLOW_IFRAME) {
                    $oSession->setVariable('paymentid', $sPaymentId);
                    $oSession->setVariable('oxidpayever_payment_view_redirect_url', $redirectUrl);
                    $nextStep = 'payeverstandarddispatcher?fnc=processPayment';
                } else {
                    $oSession->setVariable('oxidpayever_payment_view_type', 'iframe');
                    $oSession->setVariable('oxidpayever_payment_view_iframe_url', $redirectUrl);
                    $nextStep = 'order';
                }
            }
        }

        return $nextStep;
    }

    /**
     * OXID 6 core Order::execute() calls _getNextStep() — delegate to getNextStep()
     * so our logic runs regardless of which OXID version invokes us.
     *
     * @param $iSuccess
     * @return string
     */
    protected function _getNextStep($iSuccess)
    {
        return $this->getNextStep($iSuccess);
    }
}
