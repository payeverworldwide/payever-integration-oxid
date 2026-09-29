<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Inventory\Base\InventoryIteratorInterface;

class PayeverInventoryIterator extends \ArrayIterator implements InventoryIteratorInterface
{
    use PayeverInventoryTransformerTrait;

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $item = parent::current();
        if ($item instanceof oxarticle) {
            $item = $this->getInventoryTransformer()->transformCreatedOxidIntoPayever($item);
        }

        return $item;
    }
}
