<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverConfigTrait
{
    /** @var oxconfig */
    protected $config;

    /**
     * @param oxconfig $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return oxconfig
     * @codeCoverageIgnore
     */
    protected function getConfig()
    {
        return null === $this->config
            ? $this->config = oxRegistry::getConfig()
            : $this->config;
    }
}
