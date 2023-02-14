<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Http\MessageEntity\ConvertedPaymentOptionEntity;
use Payever\ExternalIntegration\Payments\Http\MessageEntity\ListPaymentOptionsResultEntity;
use Payever\ExternalIntegration\Payments\Http\ResponseEntity\ListPaymentOptionsResponse;
use Payever\ExternalIntegration\Plugins\Http\ResponseEntity\PluginVersionResponseEntity;
use Psr\Log\LogLevel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 */
class payever_config extends Shop_Config
{
    const THUMBNAILS_PATH = 'out/pictures/%s.png';

    /** @var string|null */
    private $errorMessage = null;

    /** @var array  */
    private $flashMessages = [];

    /** @var PayeverSubscriptionManager */
    protected $subscriptionManager;

    /**
     * class template.
     * @var string
     */
    protected $_sThisTemplate = 'payever_config.tpl';

    /**
     * @var array
     */
    protected $_parameters = [];

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->subscriptionManager = new PayeverSubscriptionManager();
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->_parameters = $parameters;

        return $this;
    }

    /**
     * Passes shop configuration parameters
     * @extend render
     * @return string
     */
    public function render()
    {
        $this->_aViewData['payever_config'] = PayeverConfig::get(PayeverConfig::VAR_CONFIG);
        $this->_aViewData['isset_live'] = PayeverConfig::getIsLiveKeys();
        $this->_aViewData['log_file_exists'] = file_exists(PayeverConfig::getLogFilename());
        $this->_aViewData['payever_error'] = $this->getMerchantConfigErrorId();
        $this->_aViewData['payever_error_message'] = $this->errorMessage;
        $this->_aViewData['payever_flash_messages'] = $this->flashMessages;
        $this->_aViewData['payever_version_info'] = $this->getVersionsList();
        $this->_aViewData['payever_new_version'] = $this->checkLatestVersion();

        if (file_exists(PayeverConfig::getLogFilename())) {
            $this->_aViewData['log_filename'] = substr(
                PayeverConfig::getLogFilename(),
                strlen($_SERVER['DOCUMENT_ROOT'])
            );
        }

        return $this->_sThisTemplate;
    }

    /**
     * Saves shop configuration parameters.
     *
     * @param array $parameters
     *
     * @return void
     * @throws ReflectionException
     */
    public function save($parameters = [])
    {
        /** @var oxConfig $oxConfig */
        $oxConfig = $this->getConfig();
        if (empty($this->_parameters)) {
            $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);
        }
        $wasActive = PayeverConfig::isProductsSyncEnabled();
        $isActive = !empty($this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED])
            ? (bool) $this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED]
            : false;
        if ($wasActive !== $isActive) {
            $this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED] = $this->subscriptionManager
                ->toggleSubscription($isActive);
            $this->_parameters[PayeverConfig::PRODUCTS_SYNC_EXTERNAL_ID] = PayeverConfig::getProductsSyncExternalId();
        }
        $this->_parameters = array_merge($this->_parameters, $parameters);
        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);
    }

    /**
     * @throws ReflectionException
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
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

    /**
     * @throws ReflectionException
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
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
     * @param string $thumbnailUrl
     * @param string $thumbnailName
     * @return false|string
     */
    public function saveThumbnailInDirectory($thumbnailUrl, $thumbnailName)
    {
        $savePath = $this->getConfig()->getConfigParam('sShopDir') . sprintf(self::THUMBNAILS_PATH, $thumbnailName);
        $curl = curl_init($thumbnailUrl);
        $file = fopen($savePath, 'wb');
        curl_setopt($curl, CURLOPT_FILE, $file);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if (false === curl_exec($curl)) {
            return false;
        }
        curl_close($curl);
        fclose($file);

        return $this->getConfig()->getShopUrl() . sprintf(self::THUMBNAILS_PATH, $thumbnailName);
    }

    /**
     * @throws ReflectionException
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function synchronize()
    {
        $prefix = PayeverConfig::PLUGIN_PREFIX;
        oxDb::getDb()->execute(sprintf("DELETE FROM `oxobject2payment` where `oxpaymentid` LIKE '%%%s%%'", $prefix));
        oxDb::getDb()->execute(sprintf("DELETE FROM `oxpayments` where `oxid` LIKE '%%%s%%'", $prefix));
        PayeverInstaller::migrateDB();

        $locales = $this->getLangList();
        $oPayment = oxNew('oxPayment');

        try {
            $methods = $this->retrieveActiveMethods();
            if (!$methods) {
                throw new UnexpectedValueException('Empty payment option list result');
            }
        } catch (Exception $exception) {
            $this->errorMessage = $exception->getMessage();
            if (401 === $exception->getCode()) {
                $this->errorMessage = 'Could not synch - please check if the credentials you entered are correct' .
                    ' and match the mode (live/sandbox)';
            }
            return;
        }

        $checkAddressEqualityMethods = [];
        $shippingNotAllowedMethods = [];
        foreach ($methods as $methodCode => $method) {
            $methodData = $method->toArray();

            $oPayment->load($methodCode);
            $oPayment->setEnableMultilang(false);
            $oPayment->setId($methodCode);
            $oPayment->oxpayments__oxid = new oxField($methodCode, oxField::T_RAW);

            foreach ($locales as $locale => $lang) {
                $oPayment->{'oxpayments__oxdesc' . $lang} = new oxField(
                    'payever ' . $methodData["name_{$locale}"],
                    oxField::T_RAW
                );
                $oPayment->{'oxpayments__oxlongdesc' . $lang} = new oxField(
                    strip_tags($methodData["description_offer_{$locale}"]),
                    oxField::T_RAW
                );
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
            $oPayment->oxpayments__oxisredirectmethod = new oxField($method->isRedirectMethod(), oxField::T_RAW);

            if ($method->getShippingAddressEquality()) {
                $checkAddressEqualityMethods[] = PayeverConfig::removeMethodPrefix($methodCode);
            }

            if (!$method->getShippingAddressAllowed()) {
                $shippingNotAllowedMethods[] = PayeverConfig::removeMethodPrefix($methodCode);
            }

            $thumbnailPath = $this->saveThumbnailInDirectory($method->getThumbnail1(), $oPayment->oxpayments__oxid);
            if ($thumbnailPath) {
                $oPayment->oxpayments__oxthumbnail = new oxField($thumbnailPath, oxField::T_RAW);
            }

            $variants = json_encode(array(
                'variantId' => $method->getVariantId(),
                'variantName' => $method->getVariantName(),
                'paymentMethod' => $method->getPaymentMethod()
            ));

            $oPayment->oxpayments__oxvariants = new oxField($variants, oxField::T_RAW);
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

        $this->save(
            [
                PayeverConfig::ADDRESS_EQUALIY_METHODS => $checkAddressEqualityMethods,
                PayeverConfig::SHIPPING_NOT_ALLOWED_METHODS => $shippingNotAllowedMethods
            ]
        );

        $pluginsApiClient = PayeverApiClientProvider::getPluginsApiClient();
        $pluginsApiClient->registerPlugin();
    }

    /**
     * Sends payever log file
     */
    public function downloadLogFile()
    {
        $filePath = PayeverConfig::getLogFilename();
        $this->sendFile($filePath);
    }

    /**
     * Sends application log file
     */
    public function downloadAppLogFile()
    {
        $filePath = OX_LOG_FILE;
        $this->sendFile($filePath);
    }

    /**
     * Clears cache
     */
    public function clearCache()
    {
        PayeverInstaller::cleanTmp();
    }

    /**
     * Retrieve active methods from payever account
     *
     * @return ListPaymentOptionsResultEntity[]
     *
     * @throws \Exception
     */
    private function retrieveActiveMethods()
    {
        $currency = $this->getConfig()->getActShopCurrencyObject();
        $locales = $this->getLangList();
        $paymentsApiClient = PayeverApiClientProvider::getPaymentsApiClient();
        $payeverMethods = [];

        foreach (array_keys($locales) as $locale) {
            $optionsResponse = $paymentsApiClient->listPaymentOptionsWithVariantsRequest([
                'locale' => $locale,
                '_currency' => $currency->name,
            ]);
            /** @var ListPaymentOptionsResponse $responseEntity */
            $responseEntity = $optionsResponse->getResponseEntity();

            if ($optionsResponse->isFailed()) {
                throw new \UnexpectedValueException(
                    sprintf('%s: %s', $responseEntity->getError(), $responseEntity->getErrorDescription())
                );
            }

            $result = $responseEntity->getResult();

            if (!count($result)) {
                throw new \UnexpectedValueException("Empty payment options list result");
            }

            $convertedOptions = $this->convertPaymentOptionVariants($result);
            foreach ($convertedOptions as $methodCode => $method) {
                /** @var ListPaymentOptionsResultEntity $method */
                $originPaymentMethod = $method->getPaymentMethod();
                $key = $method->getVariantName() ?
                    PayeverConfig::PLUGIN_PREFIX . $methodCode :
                    PayeverConfig::PLUGIN_PREFIX . $originPaymentMethod;

                if (!isset($payeverMethods[$key])) {
                    $payeverMethods[$key] = $method;
                }

                $payeverMethods[$key]->offsetSet(
                    "name_{$locale}",
                    sprintf('%s %s', $method->getName(), $method->getVariantName())
                );
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
     * @return int
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

    /**
     * @return array|false
     */
    private function checkLatestVersion()
    {
        try {
            $pluginsApiClient = PayeverApiClientProvider::getPluginsApiClient();
            $pluginsApiClient->setHttpClientRequestFailureLogLevelOnce(LogLevel::NOTICE);
            /** @var PluginVersionResponseEntity $latestVersion */
            $latestVersion = $pluginsApiClient->getLatestPluginVersion()->getResponseEntity();
            if (version_compare($latestVersion->getVersion(), PayeverConfig::getPluginVersion(), '>')) {
                return $latestVersion->toArray();
            }
        } catch (\Exception $exception) {
            PayeverConfig::getLogger()->notice(sprintf('Plugin version checking failed: %s', $exception->getMessage()));
        }

        return false;
    }

    /**
     * @param array $poWithVariants
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function convertPaymentOptionVariants(array $poWithVariants)
    {
        $result = array();

        foreach ($poWithVariants as $poWithVariant) {
            $convertedPaymentOption = array();
            $baseData = $poWithVariant->toArray();

            $poIndex = 1;
            foreach ($poWithVariant->getVariants() as $variant) {
                $variantName = $variant->getName();
                $convertedOption = new ConvertedPaymentOptionEntity($baseData);
                $convertedOption->setVariantId($variant->getId());
                $convertedOption->setAcceptFee($variant->getAcceptFee());
                $convertedOption->setVariantName($variantName);
                $convertedOption->setShippingAddressAllowed($variant->getShippingAddressAllowed());
                $convertedOption->setShippingAddressEquality($variant->getShippingAddressEquality());

                if (isset($convertedPaymentOption[$poWithVariant->getPaymentMethod()])) {
                    $key = $poIndex
                        ? $poWithVariant->getPaymentMethod() . '-' . $poIndex
                        : $poWithVariant->getPaymentMethod();
                    $convertedPaymentOption[$key] = $convertedOption;
                    $poIndex++;
                } else {
                    /** default variant */
                    $convertedPaymentOption[$poWithVariant->getPaymentMethod()] = $convertedOption;
                }
            }
            $result = array_merge($result, $convertedPaymentOption);
        }

        return $result;
    }

    /**
     * @param string $filePath
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    protected function sendFile($filePath)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        ob_clean();
        flush();
        readfile($filePath);

        exit;
    }
}
// phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore
