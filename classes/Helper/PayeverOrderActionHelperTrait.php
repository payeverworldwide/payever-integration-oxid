<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOrderActionHelperTrait
{
    /** @var PayeverOrderActionHelper */
    protected $orderActionHelper;

    /**
     * @param PayeverOrderActionHelper $orderHelper
     * @return $this
     * @internal
     */
    public function setOrderActionHelper(PayeverOrderActionHelper $orderHelper)
    {
        $this->orderActionHelper = $orderHelper;

        return $this;
    }

    /**
     * @return PayeverOrderActionHelper
     * @codeCoverageIgnore
     */
    protected function getOrderActionHelper()
    {
        return null === $this->orderActionHelper
            ? $this->orderActionHelper = new PayeverOrderActionHelper()
            : $this->orderActionHelper;
    }
}
