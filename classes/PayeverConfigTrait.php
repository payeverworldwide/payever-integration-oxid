<?php

/**
 * PHP version 7 and 8.4
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
    public function getConfig()
    {
        return null === $this->config
            ? $this->config = \OxidEsales\Eshop\Core\Registry::getConfig()
            : $this->config;
    }
}
