<?php

use Payever\ExternalIntegration\Core\Base\ClientConfigurationInterface;
use Payever\ExternalIntegration\Core\ClientConfiguration;
use Payever\ExternalIntegration\Core\Enum\ChannelSet;
use Payever\ExternalIntegration\Payments\PaymentsApiClient;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandExecutorInterface;
use Payever\ExternalIntegration\Plugins\PluginsApiClient;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandManager;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverApiClientProvider
{
    /** @var ClientConfiguration */
    private static $clientConfiguration;

    /** @var PayeverApiOauthTokenList */
    private static $oauthTokenList;

    /** @var PluginRegistryInfoProviderInterface */
    private static $registryInfoProvider;

    /** @var PaymentsApiClient */
    private static $paymentsApiClient;

    /** @var PluginsApiClient */
    private static $pluginsApiClient;

    /** @var PluginCommandManager */
    private static $pluginCommandManager;

    /** @var PluginCommandExecutorInterface */
    private static $pluginCommandExecutor;

    /**
     * @return PluginsApiClient
     * @throws Exception
     */
    public static function getPluginsApiClient()
    {
        if (static::$pluginsApiClient === null) {
            static::$pluginsApiClient = new PluginsApiClient(
                static::getPayeverPluginRegistryInfoProvider(),
                static::getClientConfiguration(),
                static::getOauthTokenList()
            );
        }

        return static::$pluginsApiClient;
    }

    /**
     * @return PluginCommandManager
     * @throws Exception
     */
    public static function getPluginCommandManager()
    {
        if (static::$pluginCommandManager === null) {
            static::$pluginCommandManager = new PluginCommandManager(
                static::getPluginsApiClient(),
                static::getPayeverPluginCommandExecutor(),
                PayeverConfig::getLogger()
            );
        }

        return static::$pluginCommandManager;
    }

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
     * @return PluginRegistryInfoProviderInterface
     */
    private static function getPayeverPluginRegistryInfoProvider()
    {
        if (null === static::$registryInfoProvider) {
            static::$registryInfoProvider = new PayeverPluginRegistryInfoProvider();
        }

        return static::$registryInfoProvider;
    }

    /**
     * @return PluginCommandExecutorInterface
     */
    private static function getPayeverPluginCommandExecutor()
    {
        if (null === static::$pluginCommandExecutor) {
            static::$pluginCommandExecutor = new PayeverPluginCommandExecutor();
        }

        return static::$pluginCommandExecutor;
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
            : ClientConfigurationInterface::API_MODE_LIVE;

        $clientConfiguration->setApiMode($apiMode)
            ->setChannelSet(ChannelSet::CHANNEL_OXID)
            ->setBusinessUuid(PayeverConfig::getApiSlug())
            ->setClientId(PayeverConfig::getApiClientId())
            ->setClientSecret(PayeverConfig::getApiClientSecret())
            ->setLogger(PayeverConfig::getLogger());

        if ($sandboxUrl = PayeverConfig::getCustomSandboxUrl()) {
            $clientConfiguration->setCustomSandboxUrl($sandboxUrl);
        }
        if ($liveUrl = PayeverConfig::getCustomLiveUrl()) {
            $clientConfiguration->setCustomLiveUrl($liveUrl);
        }

        return $clientConfiguration;
    }
}
