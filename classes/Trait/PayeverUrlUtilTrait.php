<?php

/**
 * PHP version 5.4 and 7
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
            ? $this->urlUtil = oxRegistry::get('oxutilsurl')
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
