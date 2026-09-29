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
 */
class payeverPayment extends payeverPayment_parent
{
    public function render()
    {
        $clearSession = \OxidEsales\Eshop\Core\Registry::getRequest()->getRequestParameter('clearIframeSession');

        if ($clearSession) {
            \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('oxidpayever_payment_view_type');
        }

        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('oxidpayever_payment_view_iframe_url');
        \OxidEsales\Eshop\Core\Registry::getSession()->deleteVariable('oxidpayever_payment_view_type');

        return parent::render();
    }
}
