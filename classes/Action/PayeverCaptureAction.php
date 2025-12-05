<?php

use Payever\Sdk\Payments\Action\ActionDeciderInterface;
use Payever\Sdk\Payments\Enum\Status;

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverCaptureAction extends PayeverBaseAction
{
    /** @var PayeverShippingGoodsHandler */
    protected $shippingGoodsHandler;

    /**
     * @inheritDoc
     */
    protected function sendAmountRequest($oxOrder, $amount, $identifier)
    {
        $handler = $this->getShippingGoodsHandler();

        return $handler->triggerAmountShippingGoodsPaymentRequest($oxOrder, $amount, $identifier);
    }

    /**
     * @inheritDoc
     */
    protected function sendItemsRequest($oxOrder, $paymentItems, $identifier)
    {
        $handler = $this->getShippingGoodsHandler();

        return $handler->triggerItemsShippingGoodsPaymentRequest($oxOrder, $paymentItems, $identifier);
    }

    /**
     * @inheritDoc
     */
    protected function getOrderStatus($transaction)
    {
        if ($transaction['status'] !== Status::STATUS_PAID) {
            return oxRegistry::getLang()->translateString('Partially Shipped');
        }

        return parent::getOrderStatus($transaction);
    }

    /**
     * @param PayeverShippingGoodsHandler $shippingGoodsHandler
     *
     * @return $this
     */
    public function setShippingGoodsHandler($shippingGoodsHandler)
    {
        $this->shippingGoodsHandler = $shippingGoodsHandler;

        return $this;
    }

    /**
     * @return PayeverShippingGoodsHandler
     *
     * @codeCoverageIgnore
     */
    protected function getShippingGoodsHandler()
    {
        return null === $this->shippingGoodsHandler
            ? $this->shippingGoodsHandler = new PayeverShippingGoodsHandler()
            : $this->shippingGoodsHandler;
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_SHIPPING_GOODS;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_SHIPPED;
    }
}
