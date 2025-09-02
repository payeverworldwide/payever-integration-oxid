<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxRequestTrait
{
    /** @var oxconfig */
    protected $request;

    /**
     * @param object|oxconfig|\OxidEsales\Eshop\Core\Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return object|oxconfig|\OxidEsales\Eshop\Core\Request
     * @codeCoverageIgnore
     */
    public function getRequest()
    {
        if (method_exists('oxRegistry', 'getRequest')) {
            return null === $this->request
                ? $this->request = oxRegistry::getRequest()
                : $this->request;
        }

        return null === $this->request
            ? $this->request = oxRegistry::getConfig()
            : $this->request;
    }
}
