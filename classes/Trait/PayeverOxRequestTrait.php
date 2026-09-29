<?php

/**
 * PHP version 7 and 8.4
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
        return null === $this->request
            ? $this->request = \OxidEsales\Eshop\Core\Registry::getRequest()
            : $this->request;
    }
}
