<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity as BaseEntity;

class PayeverProductRequestEntity extends BaseEntity
{
    /**
     * @inheritDoc
     */
    public function toArray($object = null)
    {
        $isRootObject = $object === null;
        $result = parent::toArray($object);
        if ($result && $isRootObject) {
            $result['salePrice'] = $this->salePrice;
        }

        return $result;
    }
}
