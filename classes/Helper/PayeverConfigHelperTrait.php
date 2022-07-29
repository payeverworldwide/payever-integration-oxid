<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverConfigHelperTrait
{
    /** @var PayeverConfigHelper */
    protected $configHelper;

    /**
     * @param PayeverConfigHelper $configHelper
     * @return $this
     */
    public function setConfigHelper(PayeverConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;

        return $this;
    }

    /**
     * @return PayeverConfigHelper
     * @codeCoverageIgnore
     */
    protected function getConfigHelper()
    {
        return null === $this->configHelper
            ? $this->configHelper = new PayeverConfigHelper()
            : $this->configHelper;
    }
}
