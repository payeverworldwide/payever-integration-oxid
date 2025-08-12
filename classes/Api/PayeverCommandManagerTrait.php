<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Plugins\Command\PluginCommandManager;

trait PayeverCommandManagerTrait
{
    /** @var PluginCommandManager */
    private $pluginCommandManager;

    /**
     * @return PluginCommandManager
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getPluginCommandManager()
    {
        return null === $this->pluginCommandManager
            ? $this->pluginCommandManager = PayeverApiClientProvider::getPluginCommandManager()
            : $this->pluginCommandManager;
    }

    /**
     * @param PluginCommandManager $pluginCommandManager
     * @return $this
     * @internal
     */
    public function setPluginCommandManager($pluginCommandManager)
    {
        $this->pluginCommandManager = $pluginCommandManager;

        return $this;
    }
}
