<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\ThirdParty\Action\BidirectionalActionProcessor;
use Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum;
use Payever\ExternalIntegration\ThirdParty\Enum\DirectionEnum;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverSynchronizationManager
{
    use PayeverGenericManagerTrait;
    use PayeverInventoryTransformerTrait;
    use PayeverProductTransformerTrait;
    use PayeverSynchronizationQueueFactoryTrait;

    /** @var BidirectionalActionProcessor */
    protected $bidirectionalSyncActionProcessor;

    /**
     * @param oxarticle $product
     * @throws oxSystemComponentException
     */
    public function handleProductSave($product)
    {
        if ($this->isProductSupported($product)) {
            $this->handleAction(
                ActionEnum::ACTION_UPDATE_PRODUCT,
                DirectionEnum::OUTWARD,
                $this->getProductTransformer()->transformFromOxidIntoPayever($product)
            );
        }
    }

    /**
     * @param oxarticle $product
     */
    public function handleProductDelete($product)
    {
        if ($this->isProductSupported($product)) {
            $this->handleAction(
                ActionEnum::ACTION_REMOVE_PRODUCT,
                DirectionEnum::OUTWARD,
                $this->getProductTransformer()->transformRemovedOxidIntoPayever($product)
            );
        }
    }

    /**
     * @param oxarticle $product
     * @param int|null $delta
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function handleInventory($product, $delta)
    {
        if ($this->isProductSupported($product)) {
            if ($delta) {
                $this->handleAction(
                    $delta < 0 ? ActionEnum::ACTION_SUBTRACT_INVENTORY : ActionEnum::ACTION_ADD_INVENTORY,
                    DirectionEnum::OUTWARD,
                    $this->getInventoryTransformer()->transformFromOxidIntoPayever($product, $delta)
                );
            } else {
                $inventoryRequestEntity = $this->getInventoryTransformer()->transformCreatedOxidIntoPayever($product);
                $this->handleAction(
                    ActionEnum::ACTION_SET_INVENTORY,
                    DirectionEnum::OUTWARD,
                    $inventoryRequestEntity
                );
            }
        }
    }

    /**
     * @param string $action
     * @param string $direction
     * @param bool $forceHttp
     * @param \Payever\ExternalIntegration\Core\Base\MessageEntity|string $payload
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function handleAction($action, $direction, $payload, $forceHttp = false)
    {
        $this->cleanMessages();
        if (!$this->isEnabled() || ($direction === DirectionEnum::OUTWARD && !$this->isOutwardSyncEnabled())) {
            $this->logMessages();
            return;
        }
        try {
            if ($this->getConfigHelper()->isCronMode() && !$forceHttp) {
                $this->enqueueAction(
                    $action,
                    $direction,
                    is_string($payload) ? $payload : $payload->toString()
                );
            } elseif ($direction === DirectionEnum::INWARD) {
                $this->getBidirectionalSyncActionProcessor()->processInwardAction($action, $payload);
            } else {
                $this->getBidirectionalSyncActionProcessor()->processOutwardAction($action, $payload);
            }
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
        $this->logMessages();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getConfigHelper()->isProductsSyncEnabled();
    }

    /**
     * @return bool
     */
    protected function isOutwardSyncEnabled()
    {
        return $this->getConfigHelper()->isProductsOutwardSyncEnabled();
    }

    /**
     * @param oxarticle $product
     * @return bool
     */
    protected function isProductSupported($product)
    {
        /** @var oxarticle|null $lastProcessedProduct */
        $lastProcessedProduct = PayeverRegistry::get(PayeverRegistry::LAST_INWARD_PROCESSED_PRODUCT);

        return !$lastProcessedProduct ||
            $lastProcessedProduct->getFieldData('oxid') !== $product->getFieldData('oxid');
    }

    /**
     * @param string $action
     * @param string $direction
     * @param string $payload
     */
    protected function enqueueAction($action, $direction, $payload)
    {
        $item = $this->getSynchronizationQueueFactory()->create();
        $item->assign([
            'action' => $action,
            'direction' => $direction,
            'payload' => $payload,
            'created_at' => date(DATE_ATOM),
        ]);
        try {
            $item->save();
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * @param BidirectionalActionProcessor $bidirectionalSyncActionProcessor
     * @return $this
     */
    public function setBidirectionalSyncActionProcessor(BidirectionalActionProcessor $bidirectionalSyncActionProcessor)
    {
        $this->bidirectionalSyncActionProcessor = $bidirectionalSyncActionProcessor;

        return $this;
    }

    /**
     * @return BidirectionalActionProcessor
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getBidirectionalSyncActionProcessor()
    {
        return null === $this->bidirectionalSyncActionProcessor
            ? $this->bidirectionalSyncActionProcessor = PayeverApiClientProvider::getBidirectionalSyncActionProcessor()
            : $this->bidirectionalSyncActionProcessor;
    }
}
