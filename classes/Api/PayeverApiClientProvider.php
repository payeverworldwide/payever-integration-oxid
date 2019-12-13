<?php

use Payever\ExternalIntegration\Core\Base\ClientConfigurationInterface;
use Payever\ExternalIntegration\Core\ClientConfiguration;
use Payever\ExternalIntegration\Core\Enum\ChannelSet;
use Payever\ExternalIntegration\Payments\PaymentsApiClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverApiClientProvider
{
    /** @var ClientConfiguration */
    private static $clientConfiguration;

    /** @var PayeverApiOauthTokenList */
    private static $oauthTokenList;

    /** @var PaymentsApiClient */
    private static $paymentsApiClient;

    /**
     * @param bool $forceReload
     * @return PaymentsApiClient
     * @throws Exception
     */
    public static function getPaymentsApiClient($forceReload = false)
    {
        if (null === static::$paymentsApiClient || $forceReload) {
            static::$paymentsApiClient = new PaymentsApiClient(
                static::getClientConfiguration($forceReload),
                static::getOauthTokenList()
            );
        }

        return static::$paymentsApiClient;
    }

    /**
     * @return PayeverApiOauthTokenList
     */
    private static function getOauthTokenList()
    {
        if (null === static::$oauthTokenList) {
            static::$oauthTokenList = new PayeverApiOauthTokenList();
        }

        return static::$oauthTokenList;
    }

    /**
     * @param bool $forceReload
     * @return ClientConfiguration
     * @throws Exception
     */
    private static function getClientConfiguration($forceReload = false)
    {
        if (null === static::$clientConfiguration || $forceReload) {
            static::$clientConfiguration = static::loadClientConfiguration();
        }

        return static::$clientConfiguration;
    }

    /**
     * @return ClientConfiguration
     * @throws Exception
     */
    private static function loadClientConfiguration()
    {
        $clientConfiguration = new ClientConfiguration();

        $isSandbox = PayeverConfig::getApiMode() == PayeverConfig::API_MODE_SANDBOX;
        $apiMode = $isSandbox
            ? ClientConfigurationInterface::API_MODE_SANDBOX
            : ClientConfigurationInterface::API_MODE_LIVE
        ;

        $clientConfiguration->setApiMode($apiMode)
            ->setChannelSet(ChannelSet::CHANNEL_OXID)
            ->setBusinessUuid(PayeverConfig::getApiSlug())
            ->setClientId(PayeverConfig::getApiClientId())
            ->setClientSecret(PayeverConfig::getApiClientSecret())
            ->setLogger(PayeverConfig::getLogger());

        if ($isSandbox && ($sandboxUrl = PayeverConfig::getCustomSandboxUrl())) {
            $clientConfiguration->setCustomApiUrl($sandboxUrl);
        }

        return $clientConfiguration;
    }
}
