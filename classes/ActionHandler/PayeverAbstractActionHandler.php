<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Base\MessageEntity;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRemovedRequestEntity;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;
use Payever\ExternalIntegration\ThirdParty\Action\ActionHandlerInterface;
use Payever\ExternalIntegration\ThirdParty\Action\ActionPayload;
use Payever\ExternalIntegration\ThirdParty\Action\ActionResult;
use Psr\Log\LoggerAwareInterface;

abstract class PayeverAbstractActionHandler implements ActionHandlerInterface, LoggerAwareInterface
{
    use PayeverLoggerTrait;

    /** @var ActionResult|null */
    protected $actionResult;

    /**
     * {@inheritDoc}
     */
    public function handle(ActionPayload $actionPayload, ActionResult $actionResult)
    {
        $this->actionResult = $actionResult;
        /** @var ProductRequestEntity|ProductRemovedRequestEntity $productEntity */
        $productEntity = $actionPayload->getPayloadEntity();
        if (!$this->validate($productEntity)) {
            return;
        }
        try {
            $this->process($productEntity);
            $this->incrementActionResult();
        } catch (\Exception $e) {
            $this->actionResult->incrementSkipped();
            $this->actionResult->addError($e->getMessage());
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * @param MessageEntity $entity
     * @return bool
     */
    protected function validate(MessageEntity $entity)
    {
        $result = true;
        if (!$entity->getSku()) {
            $this->actionResult->incrementSkipped();
            $this->actionResult->addError(
                sprintf(
                    'Entity has empty SKU: "%s"',
                    $entity->toString()
                )
            );
            $result = false;
        }

        return $result;
    }

    /**
     * @param MessageEntity $entity
     */
    abstract protected function process($entity);

    /**
     * Increment action result count: created, updated etc.
     */
    abstract protected function incrementActionResult();

    /**
     * @param oxarticle $product
     */
    protected function pushToRegistry($product)
    {
        PayeverRegistry::set(PayeverRegistry::LAST_INWARD_PROCESSED_PRODUCT, $product);
    }
}
