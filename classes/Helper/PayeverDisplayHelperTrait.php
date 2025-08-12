<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverDisplayHelperTrait
{
    /** @var PayeverDisplayHelper */
    protected $displayHelper;

    /**
     * @param PayeverDisplayHelper $displayHelper
     * @return $this
     * @internal
     */
    public function setDisplayHelper(PayeverDisplayHelper $displayHelper)
    {
        $this->displayHelper = $displayHelper;

        return $this;
    }

    /**
     * @return PayeverDisplayHelper
     * @codeCoverageIgnore
     */
    protected function getDisplayHelper()
    {
        return null === $this->displayHelper
            ? $this->displayHelper = new PayeverDisplayHelper()
            : $this->displayHelper;
    }
}
