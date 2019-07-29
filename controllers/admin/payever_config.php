<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\Response;
use Payever\ExternalIntegration\Payments\Http\MessageEntity\ListPaymentOptionsResultEntity;

/**
 * Configure Payever interface
 */
class payever_config extends Shop_Config
{
    /** @var string|null  */
    private $errorMessage = null;

    /**
     * class template.
     * @var string
     */
    protected $_sThisTemplate = 'payever_config.tpl';

    protected $_parameters = [];

    /**
     * Passes shop configuration parameters
     * @extend render
     * @return string
     */
    public function render()
    {
        $this->_aViewData['payever_config'] = PayeverConfig::get(PayeverConfig::VAR_CONFIG);
        $this->_aViewData['isset_live'] = PayeverConfig::getIsLiveKeys();
        $this->_aViewData['payever_error'] = $this->getMerchantConfigErrorId();
        $this->_aViewData['payever_error_message'] = $this->errorMessage;
        $this->_aViewData['payever_version_info'] = $this->getVersionsList();

        if (file_exists(PayeverConfig::getLogFilename())) {
            $this->_aViewData['log_filename'] = substr(PayeverConfig::getLogFilename(), strlen($_SERVER['DOCUMENT_ROOT']));
        }

        return $this->_sThisTemplate;
    }

    /**
     * Saves shop configuration parameters.
     *
     * @return void
     */
    public function save()
    {
        $oxConfig = $this->getConfig();
        if (empty($this->_parameters)) {
            $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);
        }
        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);
    }

    public function setLive()
    {
        $oxConfig = $this->getConfig();

        $liveApiKeys = $oxConfig->getShopConfVar(PayeverConfig::VAR_LIVE_KEYS);
        $liveApiKeys[PayeverConfig::KEY_IS_LIVE] = 0;

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_LIVE_KEYS, $liveApiKeys);

        $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);
        $this->_parameters[PayeverConfig::KEY_API_MODE] = PayeverConfig::API_MODE_LIVE;
        unset($liveApiKeys[PayeverConfig::KEY_IS_LIVE]);

        $this->_parameters = array_merge($this->_parameters, $liveApiKeys);

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);

        $this->synchronize();
    }

    public function setSandbox()
    {
        $oxConfig = $this->getConfig();
        $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);

        $environment = $this->_parameters[PayeverConfig::KEY_API_MODE];

        if ($environment == PayeverConfig::API_MODE_LIVE) {
            $liveApiKeys = [
                PayeverConfig::KEY_IS_LIVE           => 1,
                PayeverConfig::KEY_API_CLIENT_ID     => $this->_parameters[PayeverConfig::KEY_API_CLIENT_ID],
                PayeverConfig::KEY_API_CLIENT_SECRET => $this->_parameters[PayeverConfig::KEY_API_CLIENT_SECRET],
                PayeverConfig::KEY_API_SLUG          => $this->_parameters[PayeverConfig::KEY_API_SLUG],
            ];
            $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_LIVE_KEYS, $liveApiKeys);
        }

        $this->_parameters[PayeverConfig::KEY_API_MODE] = PayeverConfig::API_MODE_SANDBOX;
        $this->_parameters = array_merge($this->_parameters, $this->getSandboxApiKeys());

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);

        $this->synchronize();
    }

    /**
     * Gets default sandbox api keys
     * @return array
     */
    protected function getSandboxApiKeys()
    {
        return [
            PayeverConfig::KEY_API_CLIENT_ID     => '2746_6abnuat5q10kswsk4ckk4ssokw4kgk8wow08sg0c8csggk4o00',
            PayeverConfig::KEY_API_CLIENT_SECRET => '2fjpkglmyeckg008oowckco4gscc4og4s0kogskk48k8o8wgsc',
            PayeverConfig::KEY_API_SLUG          => 'payever',
        ];
    }

    /**
     * @throws oxConnectionException
     *
     * @return void
     */
    public function synchronize()
    {
        $prefix = PayeverConfig::PLUGIN_PREFIX;
        oxDb::getDb()->execute(sprintf("DELETE FROM `oxobject2payment` where `oxpaymentid` LIKE '%%%s%%'", $prefix));
        oxDb::getDb()->execute(sprintf("DELETE FROM `oxpayments` where `oxid` LIKE '%%%s%%'", $prefix));

        $locales = $this->getLangList();
        $oPayment = oxNew('oxPayment');

        try {
            if (!($methods = $this->retrieveActiveMethods())) {
                throw new UnexpectedValueException("Empty payment option list result");
            }
        } catch (Exception $exception) {
            $this->errorMessage = $exception->getMessage();
            return;
        }

        foreach ($methods as $method) {
            $methodCode = $prefix . $method->getPaymentMethod();
            $methodData = $method->toArray();

            $oPayment->load($methodCode);
            $oPayment->setEnableMultilang(false);
            $oPayment->setId($methodCode);
            $oPayment->oxpayments__oxid = new oxField($methodCode, oxField::T_RAW);

            foreach ($locales as $locale => $lang) {
                $oPayment->{'oxpayments__oxdesc' . $lang} = new oxField('payever ' . $methodData["name_{$locale}"], oxField::T_RAW);
                $oPayment->{'oxpayments__oxlongdesc' . $lang} = new oxField(strip_tags($methodData["description_offer_{$locale}"]), oxField::T_RAW);
            }

            // todo: describe magic values
            $oPayment->oxpayments__oxactive = new oxField(1, oxField::T_RAW);
            $oPayment->oxpayments__oxaddsum = new oxField(0, oxField::T_RAW);
            $oPayment->oxpayments__oxaddsumtype = new oxField('abs', oxField::T_RAW);
            $oPayment->oxpayments__oxaddsumrules = new oxField('31', oxField::T_RAW);
            $oPayment->oxpayments__oxfromboni = new oxField('0', oxField::T_RAW);
            $oPayment->oxpayments__oxfromamount = new oxField($method->getMin(), oxField::T_RAW);
            $oPayment->oxpayments__oxtoamount = new oxField($method->getMax(), oxField::T_RAW);
            $oPayment->oxpayments__oxchecked = new oxField(0, oxField::T_RAW);
            $oPayment->oxpayments__oxsort = new oxField('-300', oxField::T_RAW);
            $oPayment->oxpayments__oxtspaymentid = new oxField('', oxField::T_RAW);
            $oPayment->oxpayments__oxacceptfee = new oxField(($method->getAcceptFee()) ? 1 : 0, oxField::T_RAW);
            $oPayment->oxpayments__oxpercentfee = new oxField($method->getVariableFee(), oxField::T_RAW);
            $oPayment->oxpayments__oxfixedfee = new oxField($method->getFixedFee(), oxField::T_RAW);
            $oPayment->save();

            $sOxId = $oPayment->oxpayments__oxid->value;
            $countryModel = oxNew('oxCountry');

            foreach ($method->getOptions()->getCountries() as $country) {
                $countryId = $countryModel->getIdByCode($country);
                if ($countryId) {
                    $oObject2Payment = oxNew('oxbase');
                    $oObject2Payment->init('oxobject2payment');
                    $oObject2Payment->oxobject2payment__oxpaymentid = new oxField($sOxId);
                    $oObject2Payment->oxobject2payment__oxobjectid = new oxField($countryId);
                    $oObject2Payment->oxobject2payment__oxtype = new oxField("oxcountry");
                    $oObject2Payment->save();
                }
            }
        }

        $this->save();
    }

    /**
     * Retrieve active methods from payever account
     *
     * @return ListPaymentOptionsResultEntity[]
     *
     * @throws \UnexpectedValueException
     */
    private function retrieveActiveMethods()
    {
        $currency = $this->getConfig()->getActShopCurrencyObject();
        $locales = $this->getLangList();

        $payeverMethods = [];

        foreach ($locales as $locale => $langName) {
            /** @var Response $optionsResponse */
            $optionsResponse = PayeverApi::getInstance()->listPaymentOptionsRequest(['_locale' => $locale, '_currency' => $currency->name]);
            $responseEntity = $optionsResponse->getResponseEntity();

            if ($optionsResponse->isFailed()) {
                throw new \UnexpectedValueException(
                    sprintf('%s: %s', $responseEntity->getError(), $responseEntity->getErrorDescription())
                );
            }

            /** @var ListPaymentOptionsResultEntity[] $response */
            $result = $responseEntity->getResult();

            if (!count($result)) {
                throw new \UnexpectedValueException("Empty payment options list result");
            }

            foreach ($result as $method) {
                /** @var ListPaymentOptionsResultEntity $method */
                $key = $method->getPaymentMethod();

                if (!isset($payeverMethods[$key])) {
                    $payeverMethods[$key] = $method;
                }

                $payeverMethods[$key]->offsetSet("name_{$locale}", $method->getName());
                $payeverMethods[$key]->offsetSet("description_offer_{$locale}", $method->getDescriptionOffer());
                $payeverMethods[$key]->offsetSet("description_fee_{$locale}", $method->getDescriptionFee());
            }
        }

        return $payeverMethods;
    }

    /**
     * @return array
     */
    private function getVersionsList()
    {
        return [
            'oxid' => PayeverConfig::getOxidVersion(),
            'payever' => PayeverConfig::getPluginVersion(),
            'php' => PHP_VERSION,
        ];
    }

    /**
     * @return array
     */
    private function getLangList()
    {
        $result = [];
        $aLang = oxRegistry::getLang()->getLanguageArray();

        foreach ($aLang as $oLang) {
            $result[$oLang->abbr] = $oLang->id ? '_' . $oLang->id : '';
        }

        return $result;
    }

    /**
     * Display Error
     *
     * @see ./../../views/admin/tpl/payever_config.tpl
     *
     * @param null
     * @return boolean
     */
    public function getMerchantConfigErrorId()
    {
        $request = $_POST;
        if ($request['fnc'] == 'synchronize') {
            if ($this->errorMessage) {
                return 4;
            }
            return 3;
        } elseif ($request['fnc'] == 'save') {
            return 2;
        } elseif ($request['fnc'] == 'setLive') {
            return 5;
        } elseif ($request['fnc'] == 'setSandbox') {
            return 6;
        }

        return 1;
    }
}
