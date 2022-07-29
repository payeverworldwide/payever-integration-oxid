<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\RequestEntity;
use Payever\ExternalIntegration\Inventory\Http\MessageEntity\InventoryChangedEntity;

class PayeverSetInventoryActionHandler extends PayeverAbstractActionHandler
{
    use PayeverInventoryTransformerTrait;

    /** @var bool */
    protected $considerDiff = false;

    /** @var int */
    protected $sign = 1;

    /**
     * {@inheritDoc}
     */
    public function getSupportedAction()
    {
        return \Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum::ACTION_SET_INVENTORY;
    }

    /**
     * @param RequestEntity|InventoryChangedEntity $inventoryChangedEntity
     */
    protected function process($inventoryChangedEntity)
    {
        $this->changeStock($inventoryChangedEntity);
    }

    /**
     * @param InventoryChangedEntity $entity
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function changeStock(InventoryChangedEntity $entity)
    {
        $this->getLogger()->info(sprintf('Inventory to be changed: "%s"', $entity->toString()));
        $product = $this->getInventoryTransformer()->transformFromPayeverIntoOxid($entity);
        $stock = $product->getFieldData('oxstock');
        if (!$this->considerDiff || !$stock) {
            $data = ['oxstock' => $entity->getStock()];
        } else {
            $diff = (int) abs($entity->getQuantity()) * $this->sign;
            $data = ['oxstock' => $stock + $diff];
        }
        $product->assign($data);
        $product->save();
    }

    /**
     * Increment create count
     */
    protected function incrementActionResult()
    {
        $this->actionResult->incrementCreated();
    }
}
