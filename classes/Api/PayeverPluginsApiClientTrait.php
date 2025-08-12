<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Plugins\PluginsApiClient;

trait PayeverPluginsApiClientTrait
{
    /** @var PluginsApiClient */
    private $pluginsApiClient;

    /**
     * @return PluginsApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getPluginsApiClient()
    {
        return null === $this->pluginsApiClient
            ? $this->pluginsApiClient = PayeverApiClientProvider::getPluginsApiClient()
            : $this->pluginsApiClient;
    }

    /**
     * @param PluginsApiClient $pluginsApiClient
     * @return $this
     * @internal
     */
    public function setPluginsApiClient($pluginsApiClient)
    {
        $this->pluginsApiClient = $pluginsApiClient;

        return $this;
    }
}
