<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxOrderTrait
{
    /**
     * @var oxorder
     */
    private $oxOrder;

    /**
     * @codeCoverageIgnore
     */
    protected function getOxOrder()
    {
        if ($this->oxOrder === null) {
            return oxNew('oxorder');
        }

        return $this->oxOrder;
    }

    /**
     * @param oxorder $oxOrder
     */
    public function setOxOrder($oxOrder)
    {
        $this->oxOrder = $oxOrder;

        return $this;
    }
}
