<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverUrlUtilTrait
{
    /** @var oxutilsurl */
    private $urlUtil;

    /**
     * @return oxutilsurl
     * @codeCoverageIgnore
     */
    protected function getUrlUtil()
    {
        return null === $this->urlUtil
            ? $this->urlUtil = \OxidEsales\Eshop\Core\Registry::get('oxutilsurl')
            : $this->urlUtil;
    }

    /**
     * @param oxutilsurl $urlUtil
     * @return $this
     * @internal
     */
    public function setUrlUtil($urlUtil)
    {
        $this->urlUtil = $urlUtil;

        return $this;
    }
}
