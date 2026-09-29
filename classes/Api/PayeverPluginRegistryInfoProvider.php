<?php

/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Plugins\Enum\PluginCommandNameEnum;
use Payever\Sdk\Plugins\Base\PluginRegistryInfoProviderInterface;

class PayeverPluginRegistryInfoProvider implements PluginRegistryInfoProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getPluginVersion()
    {
        return PayeverConfig::getPluginVersion();
    }

    /**
     * @inheritDoc
     */
    public function getCmsVersion()
    {
        return PayeverConfig::getOxidVersion();
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return \OxidEsales\Eshop\Core\Registry::getConfig()->getSslShopUrl();
    }

    /**
     * @inheritDoc
     */
    public function getChannel()
    {
        return ChannelSet::CHANNEL_OXID;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedCommands()
    {
        return [
            PluginCommandNameEnum::SET_SANDBOX_HOST,
            PluginCommandNameEnum::SET_LIVE_HOST,
            PluginCommandNameEnum::SET_API_VERSION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCommandEndpoint()
    {
        $urlParams = [
            'cl' => 'payeverstandarddispatcher',
            'fnc' => 'executePluginCommands'
        ];

        return $this->getHost() . '?' . http_build_query($urlParams, "", "&");
    }

    /**
     * @inheritDoc
     */
    public function getBusinessIds()
    {
        return [
            PayeverConfig::getBusinessUuid()
        ];
    }
}
