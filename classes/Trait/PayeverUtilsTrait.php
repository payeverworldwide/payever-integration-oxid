<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverUtilsTrait
{
    /** @var oxutils */
    private $utils;

    /**
     * @return oxutils
     * @codeCoverageIgnore
     */
    protected function getUtils()
    {
        return null === $this->utils
            ? $this->utils = oxRegistry::getUtils()
            : $this->utils;
    }

    /**
     * @param oxutils $utils
     * @return $this
     * @internal
     */
    public function setUtils($utils)
    {
        $this->utils = $utils;

        return $this;
    }
}
