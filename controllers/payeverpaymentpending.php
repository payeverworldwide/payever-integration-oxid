<?php

/**
 * PHP version 7 and 8.4
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
 * @SuppressWarnings(PHPMD.ExitExpression)
 *
 * @extend oxBaseClass
 */
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
class payeverpaymentpending extends oxUBase
{
    use PayeverOxRequestTrait;
    use PayeverOxConfigTrait;
    use DryRunTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * Prefix for Santander methods
     */
    const SANTANDER_PREFIX = 'santander';

    /**
     * Executes parent::init(), loads basket from session
     */
    public function init()
    {
        parent::init();

        $paymentId = $this->getRequest()->getRequestParameter('payment_id');
        if (!$paymentId) {
            \OxidEsales\Eshop\Core\Registry::getUtils()->redirect($this->getConfig()->getShopHomeURL() . '&cl=start');
        }

        // get basket we might need some information from it here
        $oBasket = \OxidEsales\Eshop\Core\Registry::getSession()->getBasket();
        $oBasket->setOrderId(\OxidEsales\Eshop\Core\Registry::getSession()->getVariable('sess_challenge'));

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

        $oLang = \OxidEsales\Eshop\Core\Registry::getLang();

        $this->_aViewData['oLang'] = $oLang;
        $this->_aViewData['iLang'] = $oLang->getTplLanguage();
        $this->_aViewData['checkStatusLink'] = $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlParams);
        $this->_aViewData['isLoanTransaction'] = $this->isSantanderMethod();

        return PayeverConfig::getRenderEngine() === PayeverConfig::ENGINE_TWIG ?
            '@payever/page/checkout/payever_payment_pending' : 'payever_payment_pending.tpl';
    }

    public function checkStatus()
    {
        $paymentId = $this->getRequest()->getRequestParameter('payment_id');

        $data = [];
        try {
            $result = $this->getPaymentsApiClient()
                ->retrievePaymentRequest($paymentId)
                ->getResponseEntity()
                ->getResult();

            $urlBuilder = new PayeverPaymentUrlBuilder();

            switch ($result->getStatus()) {
                case Status::STATUS_PAID:
                case Status::STATUS_ACCEPTED:
                    $data['redirect_url'] = $urlBuilder->generateCallbackUrl('success', ['payment_id' => $paymentId]);
                    break;
                case Status::STATUS_FAILED:
                    $data['redirect_url'] =  $urlBuilder->generateCallbackUrl('failure', ['payment_id' => $paymentId]);
                    break;
                case Status::STATUS_CANCELLED:
                    $data['redirect_url'] = $urlBuilder->generateCallbackUrl('cancel', ['payment_id' => $paymentId]);
                    break;
                default:
                    $data['in_progress'] = true;
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

        $iLang = \OxidEsales\Eshop\Core\Registry::getLang()->getBaseLanguage();
        $aPath['title'] = \OxidEsales\Eshop\Core\Registry::getLang()->translateString('ORDER_COMPLETED', $iLang, false);
        $aPath['link'] = $this->getLink();
        $aPaths[] = $aPath;

        return $aPaths;
    }

    /**
     * @return bool
     */
    private function isSantanderMethod()
    {
        $paymentId = $this->getRequest()->getRequestParameter('payment_id');

        try {
            $result = $this->getPaymentsApiClient()
                ->retrievePaymentRequest($paymentId)
                ->getResponseEntity()
                ->getResult();
            $method = $result->getPaymentType();
        } catch (\Exception $e) {
            return false;
        }

        return strpos($method, self::SANTANDER_PREFIX) !== false;
    }
}
