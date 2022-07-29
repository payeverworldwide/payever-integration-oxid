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
 */
class payeverPayment extends payeverPayment_parent
{
    public function render()
    {
        $clearSession = oxRegistry::getConfig()->getRequestParameter('clearIframeSession');

        if ($clearSession) {
            $this->getSession()->deleteVariable('oxidpayever_payment_view_type');
        }

        $this->getSession()->deleteVariable('oxidpayever_payment_view_iframe_url');
        $this->getSession()->deleteVariable('oxidpayever_payment_view_type');

        return parent::render();
    }
}
