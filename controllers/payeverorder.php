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
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 * @see oxorder
 */
class payeverOrder extends payeverOrder_parent
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $oSession = $this->getSession();
        $oBasket = $oSession->getBasket();
        $sPaymentId = $oBasket->getPaymentId();
        $deliveryMd5 = $this->getConfig()->getRequestParameter('sDeliveryAddressMD5');
        if ($deliveryMd5) {
            $oSession->setVariable('oxidpayever_delivery_md5', $deliveryMd5);
        }

        if (
            !$this->getConfig()->getRequestParameter('ord_agb')
            && $this->getConfig()->getConfigParam('blConfirmAGB')
        ) {
            oxRegistry::get("oxUtilsView")->addErrorToDisplay('READ_AND_CONFIRM_TERMS', false, true);

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
        if ($this->getSession()->getVariable('oxidpayever_payment_view_iframe_url')) {
            return $this->getSession()->getVariable('oxidpayever_payment_view_iframe_url');
        }

        return '';
    }

    public function clearIframeSession()
    {
        $this->getSession()->deleteVariable('oxidpayever_payment_view_type');
    }

    public function isIframePayeverPayment()
    {
        $sessPayeverPaymentView = $this->getSession()->getVariable('oxidpayever_payment_view_type');

        if ($sessPayeverPaymentView == 'iframe') {
            return true;
        }

        return false;
    }

    public function getNextStep($iSuccess)
    {
        $nextStep = parent::_getNextStep($iSuccess);

        if ($nextStep == 'thankyou') {
            $oSession = $this->getSession();
            $oBasket = $oSession->getBasket();
            $sPaymentId = $oBasket->getPaymentId();

            $oOrder = oxNew('oxorder');
            $oOrder->load($oBasket->getOrderId());

            if (strpos($sPaymentId, 'oxpe_') == 0) {
                $dispatcher = oxNew('payeverStandardDispatcher');
                $redirectUrl = $dispatcher->getRedirectUrl();

                if (!$redirectUrl) {
                    return 'payment';
                }
                $isRedirectMethod = $this->getSession()->getVariable('payever_is_redirect_method');
                if ($isRedirectMethod || PayeverConfig::getIsRedirect()) {
                    $oSession->setVariable('paymentid', $sPaymentId);
                    $oSession->setVariable('oxidpayever_payment_view_redirect_url', $redirectUrl);

                    return 'payeverStandardDispatcher?fnc=processPayment';
                }
                $oSession->setVariable('oxidpayever_payment_view_type', 'iframe');
                $oSession->setVariable('oxidpayever_payment_view_iframe_url', $redirectUrl);

                return 'order';
            }
        }

        return $nextStep;
    }
}
