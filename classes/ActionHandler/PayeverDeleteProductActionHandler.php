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
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRemovedRequestEntity;

class PayeverDeleteProductActionHandler extends PayeverUpdateProductActionHandler
{
    /**
     * {@inheritDoc}
     */
    public function getSupportedAction()
    {
        return \Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum::ACTION_REMOVE_PRODUCT;
    }

    /**
     * @param RequestEntity|ProductRemovedRequestEntity $productEntity
     * @throws Exception
     */
    protected function process($productEntity)
    {
        $product = $this->getProductTransformer()->transformRemovedPayeverIntoOxid($productEntity);
        if ($product->getId()) {
            $this->pushToRegistry($product);
            $product->delete();
        }
    }

    /**
     * Increment created count
     */
    protected function incrementActionResult()
    {
        $this->actionResult->incrementDeleted();
    }
}
