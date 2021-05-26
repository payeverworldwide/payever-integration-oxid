<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Base\ClientConfigurationInterface;
use Payever\ExternalIntegration\Core\ClientConfiguration;
use Payever\ExternalIntegration\Core\Enum\ChannelSet;
use Payever\ExternalIntegration\Inventory\InventoryApiClient;
use Payever\ExternalIntegration\Payments\PaymentsApiClient;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandExecutorInterface;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandManager;
use Payever\ExternalIntegration\Plugins\PluginsApiClient;
use Payever\ExternalIntegration\Products\ProductsApiClient;
use Payever\ExternalIntegration\ThirdParty\Action\ActionHandlerPool;
use Payever\ExternalIntegration\ThirdParty\Action\ActionResult;
use Payever\ExternalIntegration\ThirdParty\Action\BidirectionalActionProcessor;
use Payever\ExternalIntegration\ThirdParty\Action\InwardActionProcessor;
use Payever\ExternalIntegration\ThirdParty\Action\OutwardActionProcessor;
use Payever\ExternalIntegration\ThirdParty\ThirdPartyApiClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayeverApiClientProvider
{
    /** @var ClientConfiguration */
    private static $clientConfiguration;

    /** @var ClientConfiguration */
    private static $thirdPartyProductsClientConfig;

    /** @var PayeverApiOauthTokenList */
    private static $oauthTokenList;

    /** @var PayeverPluginRegistryInfoProvider */
    private static $registryInfoProvider;

    /** @var PaymentsApiClient */
    private static $paymentsApiClient;

    /** @var PluginsApiClient */
    private static $pluginsApiClient;

    /** @var PluginCommandManager */
    private static $pluginCommandManager;

    /** @var PluginCommandExecutorInterface */
    private static $pluginCommandExecutor;

    /** @var ThirdPartyApiClient */
    private static $thirdPartyApiClient;

    /** @var InventoryApiClient */
    private static $inventoryApiClient;

    /** @var ProductsApiClient */
    private static $productsApiClient;

    /** @var BidirectionalActionProcessor */
    private static $bidirectionalActionProcessor;

    /** @var InwardActionProcessor */
    private static $inwardActionProcessor;

    /** @var OutwardActionProcessor */
    private static $outwardActionProcessor;

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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
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
     * @param bool $forceReload
     * @return ThirdPartyApiClient
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public static function getThirdPartyApiClient($forceReload = false)
    {
        if (null === static::$thirdPartyApiClient || $forceReload) {
            static::$thirdPartyApiClient = new ThirdPartyApiClient(
                static::getThirdPartyProductsClientConfig($forceReload),
                static::getOauthTokenList()
            );
        }

        return static::$thirdPartyApiClient;
    }

    /**
     * @return ProductsApiClient
     * @throws Exception
     */
    public static function getProductsApiClient()
    {
        if (null === static::$productsApiClient) {
            static::$productsApiClient = new ProductsApiClient(
                static::getThirdPartyProductsClientConfig(),
                static::getOauthTokenList()
            );
        }

        return static::$productsApiClient;
    }

    /**
     * @return InventoryApiClient
     * @throws Exception
     */
    public static function getInventoryApiClient()
    {
        if (null === static::$inventoryApiClient) {
            static::$inventoryApiClient = new InventoryApiClient(
                static::getThirdPartyProductsClientConfig(),
                static::getOauthTokenList()
            );
        }

        return static::$inventoryApiClient;
    }

    /**
     * @return BidirectionalActionProcessor
     * @throws Exception
     */
    public static function getBidirectionalSyncActionProcessor()
    {
        if (null === static::$bidirectionalActionProcessor) {
            static::$bidirectionalActionProcessor = new BidirectionalActionProcessor(
                static::getInwardSyncActionProcessor(),
                static::getOutwardSyncActionProcessor()
            );
        }

        return static::$bidirectionalActionProcessor;
    }

    /**
     * @return InwardActionProcessor
     */
    public static function getInwardSyncActionProcessor()
    {
        if (null === static::$inwardActionProcessor) {
            $actionHandlerPool = new ActionHandlerPool();
            $actionHandlerPool
                ->registerActionHandler(new PayeverAddInventoryActionHandler())
                ->registerActionHandler(new PayeverCreateProductActionHandler())
                ->registerActionHandler(new PayeverDeleteProductActionHandler())
                ->registerActionHandler(new PayeverSetInventoryActionHandler())
                ->registerActionHandler(new PayeverSubtractInventoryActionHandler())
                ->registerActionHandler(new PayeverUpdateProductActionHandler());
            static::$inwardActionProcessor = new InwardActionProcessor(
                $actionHandlerPool,
                new ActionResult(),
                PayeverConfig::getLogger()
            );
        }

        return static::$inwardActionProcessor;
    }

    /**
     * @return OutwardActionProcessor
     * @throws Exception
     */
    public static function getOutwardSyncActionProcessor()
    {
        if (null === static::$outwardActionProcessor) {
            static::$outwardActionProcessor = new OutwardActionProcessor(
                static::getProductsApiClient(),
                static::getInventoryApiClient(),
                PayeverConfig::getLogger()
            );
        }

        return static::$outwardActionProcessor;
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
     * @return PayeverPluginRegistryInfoProvider
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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private static function getClientConfiguration($forceReload = false)
    {
        if (null === static::$clientConfiguration || $forceReload) {
            static::$clientConfiguration = static::loadClientConfiguration();
        }

        return static::$clientConfiguration;
    }

    /**
     * @param bool $forceReload
     * @return ClientConfiguration
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private static function getThirdPartyProductsClientConfig($forceReload = false)
    {
        if (null === static::$thirdPartyProductsClientConfig || $forceReload) {
            static::$thirdPartyProductsClientConfig = static::loadClientConfiguration();
            static::$thirdPartyProductsClientConfig->setCustomSandboxUrl(null);
            $customSandboxUrl = PayeverConfig::getCustomThirdPartyProductsSandboxUrl();
            if ($customSandboxUrl) {
                static::$thirdPartyProductsClientConfig->setCustomSandboxUrl($customSandboxUrl);
            }
            static::$thirdPartyProductsClientConfig->setCustomLiveUrl(null);
            $customLiveUrl = PayeverConfig::getCustomThirdPartyProductsLiveUrl();
            if ($customLiveUrl) {
                static::$thirdPartyProductsClientConfig->setCustomLiveUrl($customLiveUrl);
            }
        }

        return static::$thirdPartyProductsClientConfig;
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
            ->setBusinessUuid(PayeverConfig::getBusinessUuid())
            ->setClientId(PayeverConfig::getApiClientId())
            ->setClientSecret(PayeverConfig::getApiClientSecret())
            ->setLogger(PayeverConfig::getLogger());
        $sandboxUrl = PayeverConfig::getCustomSandboxUrl();
        if ($sandboxUrl) {
            $clientConfiguration->setCustomSandboxUrl($sandboxUrl);
        }
        $liveUrl = PayeverConfig::getCustomLiveUrl();
        if ($liveUrl) {
            $clientConfiguration->setCustomLiveUrl($liveUrl);
        }

        return $clientConfiguration;
    }
}
