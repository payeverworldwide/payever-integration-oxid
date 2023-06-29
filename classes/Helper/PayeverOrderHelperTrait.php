<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOrderHelperTrait
{
    /** @var PayeverOrderHelper */
    protected $orderHelper;

    /**
     * @param PayeverOrderHelper $orderHelper
     * @return $this
     * @internal
     */
    public function setOrderHelper(PayeverOrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;

        return $this;
    }

    /**
     * @return PayeverOrderHelper
     * @codeCoverageIgnore
     */
    protected function getOrderHelper()
    {
        return null === $this->orderHelper
            ? $this->orderHelper = new PayeverOrderHelper()
            : $this->orderHelper;
    }
}
