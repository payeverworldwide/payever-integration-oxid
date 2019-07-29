<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\ChannelSet;
use Payever\ExternalIntegration\Payments\Configuration as BaseConfigurtaion;

class PayeverApiConfiguration extends BaseConfigurtaion
{
    /**
     * {@inheritdoc}
     */
    public function load()
    {
        $this->setApiMode(PayeverConfig::getApiMode() == PayeverConfig::API_MODE_SANDBOX ? static::MODE_SANDBOX : static::MODE_LIVE)
            ->setChannelSet(ChannelSet::CHANNEL_OXID)
            ->setSlug(PayeverConfig::getApiSlug())
            ->setClientId(PayeverConfig::getApiClientId())
            ->setClientSecret(PayeverConfig::getApiClientSecret())
            ->setDebugMode((bool) PayeverConfig::getDebugMode())
        ;

        if (($sandboxUrl = PayeverConfig::getCustomSandboxUrl())) {
            $this->setSandboxUrl($sandboxUrl);
        }
    }
}
