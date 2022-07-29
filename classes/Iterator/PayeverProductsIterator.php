<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Base\ProductsIteratorInterface;

class PayeverProductsIterator extends \ArrayIterator implements ProductsIteratorInterface
{
    use PayeverProductTransformerTrait;

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $item = parent::current();
        if ($item instanceof oxarticle) {
            $item = $this->getProductTransformer()->transformFromOxidIntoPayever($item);
        }

        return $item;
    }
}
