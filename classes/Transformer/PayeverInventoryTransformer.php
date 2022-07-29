<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Inventory\Http\MessageEntity\InventoryChangedEntity;
use Payever\ExternalIntegration\Inventory\Http\RequestEntity\InventoryChangedRequestEntity;
use Payever\ExternalIntegration\Inventory\Http\RequestEntity\InventoryCreateRequestEntity;

class PayeverInventoryTransformer
{
    use PayeverConfigHelperTrait;
    use PayeverProductHelperTrait;

    /**
     * @param oxarticle $product
     * @return InventoryCreateRequestEntity
     */
    public function transformCreatedOxidIntoPayever($product)
    {
        $inventoryRequestEntity = new InventoryCreateRequestEntity();
        $qty = (float) $product->getFieldData('oxstock');
        $inventoryRequestEntity
            ->setExternalId($this->getConfigHelper()->getProductsSyncExternalId())
            ->setSku($product->getFieldData('oxartnum'))
            ->setStock($qty);

        return $inventoryRequestEntity;
    }

    /**
     * @param oxarticle $product
     * @param int $delta
     * @return InventoryChangedRequestEntity
     */
    public function transformFromOxidIntoPayever($product, $delta)
    {
        $inventoryRequestEntity = new InventoryChangedRequestEntity();
        $inventoryRequestEntity->setExternalId($this->getConfigHelper()->getProductsSyncExternalId());
        $inventoryRequestEntity
            ->setSku($product->getFieldData('oxartnum'))
            ->setQuantity($delta);

        return $inventoryRequestEntity;
    }

    /**
     * @param InventoryChangedEntity $inventoryChangedEntity
     * @return oxarticle
     * @throws oxSystemComponentException
     */
    public function transformFromPayeverIntoOxid(InventoryChangedEntity $inventoryChangedEntity)
    {
        return $this->getProductHelper()->getProductBySku($inventoryChangedEntity->getSku());
    }
}
