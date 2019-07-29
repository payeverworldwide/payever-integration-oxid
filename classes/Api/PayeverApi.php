<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Api as BaseApi;

include_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverApi extends BaseApi
{
    /**
     * @inheritdoc
     */
    protected function loadConfiguration()
    {
        $this->configuration = new PayeverApiConfiguration();
        $this->configuration->load();

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function loadTokens()
    {
        $this->tokens = new PayeverApiTokenList();
        $this->tokens->load();
    }

    /**
     * @inheritdoc
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new PayeverApiClientLoggerDecorator(PayeverConfig::getLogger());
        }

        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function listPaymentOptionsRequest($params = '', $slug = '', $channel = '')
    {
        $slug = $slug ?: $this->configuration->getSlug();
        $channel = $channel ?: $this->configuration->getChannelSet();

        return parent::listPaymentOptionsRequest($slug, $channel, $params);
    }
}
