<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverViewUtilTrait
{
    /** @var oxutilsview */
    private $viewUtil;

    /**
     * @return object|oxutilsview
     * @codeCoverageIgnore
     */
    public function getViewUtil()
    {
        return null === $this->viewUtil
            ? $this->viewUtil = oxRegistry::get('oxutilsview')
            : $this->viewUtil;
    }

    /**
     * @param oxutilsview $viewUtil
     * @return $this
     * @internal
     */
    public function setViewUtil($viewUtil)
    {
        $this->viewUtil = $viewUtil;

        return $this;
    }
}
