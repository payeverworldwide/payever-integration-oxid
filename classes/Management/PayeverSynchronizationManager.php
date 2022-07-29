<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\ThirdParty\Action\BidirectionalActionProcessor;
use Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum;
use Payever\ExternalIntegration\ThirdParty\Enum\DirectionEnum;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverSynchronizationManager
{
    use PayeverActionQueueManagerTrait;
    use PayeverGenericManagerTrait;
    use PayeverInventoryTransformerTrait;
    use PayeverProductTransformerTrait;
    use PayeverSubscriptionManagerTrait;

    /** @var BidirectionalActionProcessor */
    protected $bidirectionalSyncActionProcessor;

    /** @var bool  */
    private $isInstantMode = false;

    /**
     * @param bool $isInstantMode
     * @return $this
     */
    public function setIsInstantMode($isInstantMode)
    {
        $this->isInstantMode = $isInstantMode;

        return $this;
    }

    /**
     * @param oxarticle $product
     * @throws oxSystemComponentException
     */
    public function handleProductSave($product)
    {
        if ($this->isProductSupported($product)) {
            $requestEntity = $this->getProductTransformer()->transformFromOxidIntoPayever($product);
            if (!$requestEntity->getSku()) {
                $this->getLogger()->info('Skip handling product with empty sku');
                return;
            }
            $this->handleOutwardAction(
                ActionEnum::ACTION_UPDATE_PRODUCT,
                $requestEntity
            );
        }
    }

    /**
     * @param oxarticle $product
     */
    public function handleProductDelete($product)
    {
        if ($this->isProductSupported($product)) {
            $this->handleOutwardAction(
                ActionEnum::ACTION_REMOVE_PRODUCT,
                $this->getProductTransformer()->transformRemovedOxidIntoPayever($product)
            );
        }
    }

    /**
     * @param oxarticle $product
     * @param int|null $delta
     */
    public function handleInventory($product, $delta)
    {
        if ($this->isProductSupported($product)) {
            $action = ActionEnum::ACTION_SET_INVENTORY;
            if (0.0 === $delta) {
                $this->getLogger()->debug('Skip zero delta');
                return;
            }
            if ($delta) {
                $action = $delta < 0 ? ActionEnum::ACTION_SUBTRACT_INVENTORY : ActionEnum::ACTION_ADD_INVENTORY;
            }
            $this->handleOutwardAction(
                $action,
                $delta !== null
                    ? $this->getInventoryTransformer()->transformFromOxidIntoPayever($product, $delta)
                    : $this->getInventoryTransformer()->transformCreatedOxidIntoPayever($product)
            );
        }
    }

    /**
     * @param string $action
     * @param \Payever\ExternalIntegration\Core\Base\MessageEntity|string $payload $payload
     */
    public function handleInwardAction($action, $payload)
    {
        $this->handleAction($action, DirectionEnum::INWARD, $payload);
    }

    /**
     * @param string $action
     * @param \Payever\ExternalIntegration\Core\Base\MessageEntity|string $payload $payload
     */
    public function handleOutwardAction($action, $payload)
    {
        $this->handleAction($action, DirectionEnum::OUTWARD, $payload);
    }

    /**
     * @param string $action
     * @param string $direction
     * @param \Payever\ExternalIntegration\Core\Base\MessageEntity|string $payload
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function handleAction($action, $direction, $payload)
    {
        $this->cleanMessages();
        if (!$this->isEnabled() || ($direction === DirectionEnum::OUTWARD && !$this->isOutwardSyncEnabled())) {
            $this->logMessages();
            return;
        }
        try {
            if (!$this->isInstantMode && $this->getConfigHelper()->isCronMode()) {
                $this->getActionQueueManager()->addItem(
                    $direction,
                    $action,
                    is_string($payload) ? $payload : $payload->toString()
                );
                return;
            }
            if ($direction === DirectionEnum::INWARD) {
                $this->getBidirectionalSyncActionProcessor()->processInwardAction($action, $payload);
                return;
            }
            try {
                $this->getBidirectionalSyncActionProcessor()->processOutwardAction($action, $payload);
            } catch (\Exception $exception) {
                $this->getSubscriptionManager()->disable();
                throw $exception;
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
     * @param oxarticle $product
     * @return bool
     */
    public function isVariant($product)
    {
        $result = false;
        if ($product->getFieldData('oxparentid')) {
            $this->getLogger()->debug('Skip variant save processing');
            $result = true;
        }

        return $result;
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
