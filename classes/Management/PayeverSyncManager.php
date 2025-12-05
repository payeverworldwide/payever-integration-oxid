<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;
use Payever\Sdk\Payments\Http\MessageEntity\ConvertedPaymentOptionEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ListPaymentOptionsResultEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\ListPaymentOptionsResponse;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayeverSyncManager
{
    use DryRunTrait;
    use PayeverDatabaseTrait;
    use PayeverConfigTrait;
    use PayeverLangTrait;
    use PayeverOxPaymentTrait;
    use PayeverPaymentsApiClientTrait;

    const THUMBNAILS_PATH = 'out/pictures/%s.png';

    /**
     * @throws ReflectionException
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     * @throws DatabaseErrorException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function synchronize()
    {
        $currentMethods = PayeverConfig::getMethods();
        PayeverInstaller::migrateDB();

        $locales = $this->getLangList();

        $methods = $this->retrieveActiveMethods();
        if (!$methods) {
            throw new UnexpectedValueException('Empty payment option list result');
        }

        $wlMethods = $this->getWLSupportedPaymentMethods();

        $b2bCountries = [];
        $activeMethods = [];
        $shippingNotAllowedMethods = [];
        $checkAddressEqualityMethods = [];
        foreach ($methods as $methodCode => $method) {
            if ($wlMethods && !in_array(PayeverConfigHelper::removeMethodPrefix($methodCode), $wlMethods)) {
                continue;
            }

            $activeMethods[] = $methodCode;
            $this->createOrUpdatePayment($methodCode, $method, $currentMethods, $locales);

            if ($method->getShippingAddressEquality()) {
                $checkAddressEqualityMethods[] = $method->getVariantId();
            }

            if (!$method->getShippingAddressAllowed()) {
                $shippingNotAllowedMethods[] = $method->getVariantId();
            }

            if ($method->isB2BMethod()) {
                $variantOptions = $method->getVariantOptions();
                $variantB2bCountries = $variantOptions ? $variantOptions->getCountries() : [];

                // Use old method as failback
                if (empty($variantB2bCountries)) {
                    $variantB2bCountries = $method->getOptions()->getCountries();
                }

                $b2bCountries += $variantB2bCountries;
            }
        }

        $deleteList = array_diff(array_keys($currentMethods), $activeMethods);
        $this->getDatabase()->execute(
            sprintf("DELETE FROM `oxobject2payment` where `OXPAYMENTID` IN('%s')", implode("','", $deleteList))
        );
        $this->getDatabase()->execute(
            sprintf("DELETE FROM `oxpayments` where `OXID` IN('%s')", implode("','", $deleteList))
        );

        PayeverConfig::set(
            PayeverConfig::VAR_B2B_CONFIG,
            PayeverConfig::KEY_B2B_COUNTRIES,
            $b2bCountries
        );

        $parameters = array_merge(
            $this->getConfig()->getShopConfVar(PayeverConfig::VAR_CONFIG),
            [
                PayeverConfig::ADDRESS_EQUALIY_METHODS => $checkAddressEqualityMethods,
                PayeverConfig::SHIPPING_NOT_ALLOWED_METHODS => $shippingNotAllowedMethods,
                PayeverConfig::CHECK_VARIANT_FOR_ADDRESS_EQUALITY => true
            ]
        );

        $this->getConfig()->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $parameters);
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
        $paymentsApiClient = $this->getPaymentsApiClient();
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
                $key = PayeverConfig::PLUGIN_PREFIX . $methodCode;

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
     * @param array $poWithVariants
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function convertPaymentOptionVariants(array $poWithVariants)
    {
        $result = array();

        $convertedPaymentOption = array();
        foreach ($poWithVariants as $poWithVariant) {
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

                $key = $poWithVariant->getPaymentMethod();
                if (isset($convertedPaymentOption[$key])) {
                    $key .= $poIndex ? '-' . $poIndex : '';
                    $poIndex++;
                }

                /** default variant */
                $convertedPaymentOption[$key] = $convertedOption;
            }
            $result = array_merge($result, $convertedPaymentOption);
        }

        return $result;
    }

    /**
     * @param $methodCode
     * @param $method
     * @param $currentMethods
     * @param $locales
     *
     * @return object
     *
     * @throws oxSystemComponentException|Exception
     */
    private function createOrUpdatePayment($methodCode, $method, $currentMethods, $locales)
    {
        $methodData = $method->toArray();
        $overwritePaymentLabels = PayeverConfig::getOverwritePaymentLabels();

        $oPayment = $this->getOxPayment();
        $oPayment->load($methodCode);
        $oPayment->setEnableMultilang(false);
        $oPayment->setId($methodCode);
        $oPayment->oxpayments__oxid = new oxField($methodCode, oxField::T_RAW);

        foreach ($locales as $locale => $lang) {
            $desc = 'payever ' . $methodData["name_{$locale}"];
            $longdesc = $methodData["description_offer_{$locale}"];

            if (!$overwritePaymentLabels && !empty($currentMethods[$methodCode]['OXDESC' . $lang])) {
                $desc = ($currentMethods[$methodCode]['OXDESC' . $lang]);
            }

            if (!$overwritePaymentLabels && !empty($currentMethods[$methodCode]['OXLONGDESC' . $lang])) {
                $longdesc = ($currentMethods[$methodCode]['OXLONGDESC' . $lang]);
            }

            $oPayment->{'oxpayments__oxdesc' . $lang} = new oxField($desc, oxField::T_RAW);
            $oPayment->{'oxpayments__oxlongdesc' . $lang} = new oxField(strip_tags($longdesc), oxField::T_RAW);
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
        $oPayment->oxpayments__oxisb2bmethod = new oxField($method->isB2BMethod(), oxField::T_RAW);
        $oPayment->oxpayments__oxpaymentissuer = new oxField($method->getPaymentIssuer(), oxField::T_RAW);

        if ($method->isSubmitMethod()) {
            $oPayment->oxpayments__oxissubmitmethod = new oxField($method->isSubmitMethod(), oxField::T_RAW);
        }

        if ($method->isRedirectMethod()) {
            $oPayment->oxpayments__oxisredirectmethod = new oxField($method->isRedirectMethod(), oxField::T_RAW);
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
                $oObject2Payment->oxobject2payment__oxtype = new oxField('oxcountry');
                $oObject2Payment->save();
            }
        }

        return $oPayment;
    }

    /**
     * @return array
     */
    private function getLangList()
    {
        $result = [];
        $aLang = $this->getLanguage()->getLanguageArray();

        foreach ($aLang as $oLang) {
            $result[$oLang->abbr] = $oLang->id ? '_' . $oLang->id : '';
        }

        return $result;
    }

    /**
     * @param string $thumbnailUrl
     * @param string $thumbnailName
     * @codeCoverageIgnore
     * @return false|string
     */
    private function saveThumbnailInDirectory($thumbnailUrl, $thumbnailName)
    {
        if ($this->dryRun) {
            return false;
        }

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
     * @return array
     */
    private function getWLSupportedPaymentMethods()
    {
        try {
            $wlPluginApiClient = PayeverApiClientProvider::getWhiteLabelPluginApiClient();
            $wlPlugin = $wlPluginApiClient
                ->getWhiteLabelPlugin(PayeverConfig::PLUGIN_CODE, PayeverConfig::SHOP_SYSTEM)
                ->getResponseEntity();

            return $wlPlugin->getSupportedMethods();
        } catch (\Exception $exception) {
            return [];
        }
    }
}
