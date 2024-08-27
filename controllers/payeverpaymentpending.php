<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Enum\Status;

/**
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 */
class payeverpaymentpending extends oxUBase
{
    use DryRunTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * class template.
     *
     * @var string
     */
    protected $_sThisTemplate = 'payever_payment_pending.tpl';

    /**
     * Executes parent::init(), loads basket from session
     */
    public function init()
    {
        parent::init();

        $paymentId = $this->getConfig()->getRequestParameter('payment_id');
        if (!$paymentId) {
            oxRegistry::getUtils()->redirect($this->getConfig()->getShopHomeURL() . '&cl=start');
        }

        // get basket we might need some information from it here
        $oBasket = $this->getSession()->getBasket();
        $oBasket->setOrderId(oxRegistry::getSession()->getVariable('sess_challenge'));

        // copying basket object
        $this->_oBasket = clone $oBasket;
    }

    /**
     * @return  string  current template file name
     */
    public function render()
    {
        parent::render();

        $urlParams = $_GET;
        $urlParams['fnc'] = 'checkStatus';

        $oLang = oxRegistry::getLang();

        $this->_aViewData['oLang'] = $oLang;
        $this->_aViewData['iLang'] = $oLang->getTplLanguage();
        $this->_aViewData['checkStatusLink'] = $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlParams);

        return $this->_sThisTemplate;
    }

    public function checkStatus()
    {
        $paymentId = $this->getConfig()->getRequestParameter('payment_id');

        $data = [];
        try {
            $result = $this->getPaymentsApiClient()
                ->retrievePaymentRequest($paymentId)
                ->getResponseEntity()
                ->getResult();

            $dispatcher = oxNew('payeverStandardDispatcher');

            switch ($result->getStatus()) {
                case Status::STATUS_PAID:
                case Status::STATUS_ACCEPTED:
                    $data['redirect_url'] = $dispatcher->generateCallbackUrl('success', ['payment_id' => $paymentId]);
                    break;
                case Status::STATUS_FAILED:
                    $data['redirect_url'] =  $dispatcher->generateCallbackUrl('failure', ['payment_id' => $paymentId]);
                    break;
                case Status::STATUS_CANCELLED:
                    $data['redirect_url'] = $dispatcher->generateCallbackUrl('cancel', ['payment_id' => $paymentId]);
                    break;
            }
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($data, true);
        $this->dryRun || die();
    }

    /**
     * Template variable getter. Returns active basket
     *
     * @return oxBasket
     */
    public function getBasket()
    {
        return $this->_oBasket;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];

        $iLang = oxRegistry::getLang()->getBaseLanguage();
        $aPath['title'] = oxRegistry::getLang()->translateString('ORDER_COMPLETED', $iLang, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }
}
