<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverInventoryTransformerTrait
{
    /** @var PayeverInventoryTransformer */
    protected $inventoryTransformer;

    /**
     * @param PayeverInventoryTransformer $inventoryTransformer
     * @return $this
     */
    public function setInventoryTransformer(PayeverInventoryTransformer $inventoryTransformer)
    {
        $this->inventoryTransformer = $inventoryTransformer;

        return $this;
    }

    /**
     * @return PayeverInventoryTransformer
     * @codeCoverageIgnore
     */
    protected function getInventoryTransformer()
    {
        return null === $this->inventoryTransformer
            ? $this->inventoryTransformer = new PayeverInventoryTransformer()
            : $this->inventoryTransformer;
    }
}
