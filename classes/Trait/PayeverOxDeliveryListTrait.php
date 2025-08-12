<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxDeliveryListTrait
{
    /** @var oxdeliverysetlist */
    private $oxDeliverySetList;

    /** @var oxdeliverylist */
    private $oxDeliveryList;

    /**
     * @codeCoverageIgnore
     * @return oxdeliverylist
     */
    protected function getOxDeliveryList()
    {
        if ($this->oxDeliveryList === null) {
            return oxNew(oxdeliverylist::class);
        }

        return $this->oxDeliveryList;
    }

    /**
     * @param oxdeliverylist $oxDeliveryList
     */
    public function setOxDeliveryList($oxDeliveryList)
    {
        $this->oxDeliveryList = $oxDeliveryList;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return oxdeliverysetlist
     */
    protected function getOxDeliverySetList()
    {
        if ($this->oxDeliverySetList === null) {
            return oxNew(oxdeliverysetlist::class);
        }

        return $this->oxDeliverySetList;
    }

    /**
     * @param oxdeliverysetlist $oxDeliverySetList
     */
    public function setOxDeliverySetList($oxDeliverySetList)
    {
        $this->oxDeliverySetList = $oxDeliverySetList;

        return $this;
    }
}
