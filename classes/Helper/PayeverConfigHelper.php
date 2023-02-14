<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

/**
 * PayeverConfig non-static wrapper
 * @codeCoverageIgnore
 */
class PayeverConfigHelper
{
    /**
     * @retrun void
     */
    public function reset()
    {
        PayeverConfig::reset();
    }

    /**
     * @return bool
     */
    public function isProductsSyncEnabled()
    {
        return PayeverConfig::isProductsSyncEnabled();
    }

    /**
     * @return bool
     */
    public function isProductsOutwardSyncEnabled()
    {
        return PayeverConfig::isProductsOutwardSyncEnabled();
    }

    /**
     * @return string|null
     */
    public function getProductsSyncExternalId()
    {
        return PayeverConfig::getProductsSyncExternalId();
    }

    /**
     * @param bool $flag
     */
    public function setProductsSyncEnabled($flag)
    {
        PayeverConfig::setConfig(PayeverConfig::PRODUCTS_SYNC_ENABLED, $flag);
    }

    /**
     * @param string|null $externalId
     */
    public function setProductsSyncExternalId($externalId)
    {
        PayeverConfig::setConfig(PayeverConfig::PRODUCTS_SYNC_EXTERNAL_ID, $externalId);
    }

    /**
     * @return mixed|null
     */
    public function getBusinessUuid()
    {
        return PayeverConfig::getBusinessUuid();
    }

    /**
     * @return bool
     */
    public function isCronMode()
    {
        return PayeverConfig::getProductsSyncMode() === PayeverConfig::SYNC_MODE_CRON;
    }

    /**
     * @return bool
     */
    public function isOxidCurrencyRateSource()
    {
        return PayeverConfig::getProductsCurrencyRateSource() === PayeverConfig::CURRENCY_RATE_SOURCE_OXID;
    }

    /**
     * @return string|null
     */
    public function getShopUrl()
    {
        return PayeverConfig::getShopUrl();
    }

    /**
     * @return string
     */
    public function generateUID()
    {
        return oxUtilsObject::getInstance()->generateUId();
    }

    /**
     * @return array
     */
    public function getLanguageIds()
    {
        $ids = [];
        $aLanguages = oxRegistry::getLang()->getLanguageArray();
        foreach ($aLanguages as $aLanguage) {
            if (property_exists($aLanguage, 'id')) {
                $ids[] = $aLanguage->id;
            }
        }

        return $ids;
    }

    /**
     * @return int
     */
    public function getDefaultLanguageId()
    {
        return (int) oxRegistry::getLang()->getBaseLanguage();
    }

    /**
     * @return string|null
     */
    public function getLanguage()
    {
        return PayeverConfig::getLanguage();
    }

    /**
     * @return mixed
     */
    public function getPluginVersion()
    {
        return PayeverConfig::getPluginVersion();
    }

    /**
     * @return int
     */
    public function getOxidVersionInt()
    {
        return PayeverConfig::getOxidVersionInt();
    }

    /**
     * @return mixed|null
     */
    public function getPluginCommandTimestamt()
    {
        return PayeverConfig::getPluginCommandTimestamt();
    }

    /**
     * @return mixed
     */
    public function getIsRedirect()
    {
        return PayeverConfig::getIsRedirect();
    }

    /**
     * @param string $paymentMethod
     * @return bool
     */
    public function isPayeverPaymentMethod($paymentMethod)
    {
        return PayeverConfig::isPayeverPaymentMethod($paymentMethod);
    }

    /**
     * @param string $key
     * @return false|string
     */
    public function getHash($key)
    {
        return hash_hmac(
            'sha256',
            PayeverConfig::getApiClientId() . $key,
            (string) PayeverConfig::getApiClientSecret()
        );
    }

    /**
     * @return int|null
     */
    public function getApiVersion()
    {
        return PayeverConfig::getApiVersion();
    }
}
