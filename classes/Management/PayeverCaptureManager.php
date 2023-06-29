<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverCaptureManager extends PayeverOrderActionManager
{
    /** @var PayeverShippingGoodsHandler */
    protected $shippingGoodsHandler;

    /**
     * @inheritDoc
     */
    public function processAmount($order, $amount)
    {
        try {
            $handler = $this->getShippingGoodsHandler();
            $response = $handler->triggerAmountShippingGoodsPaymentRequest($order, $amount);

            $this->actions[] = ['amount' => $amount, 'type' => payeverorderaction::TYPE_PRODUCT];
            $this->onRequestComplete($order, $response);
        } catch (\Exception $e) {
            $this->onRequestFailed($order, $e->getMessage());
        }

        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function processItems($order, $items)
    {
        try {
            $paymentItems = $this->getPaymentItemEntities($order, $items);

            $handler = $this->getShippingGoodsHandler();
            $response = $handler->triggerItemsShippingGoodsPaymentRequest($order, $paymentItems);

            $this->onRequestComplete($order, $response);
        } catch (\Exception $e) {
            $this->onRequestFailed($order, $e->getMessage());
        }

        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function getTotalAmount($order)
    {
        $total = parent::getTotalAmount($order);
        $cancelled = $this->getSentAmount(
            $order,
            payeverorderaction::ACTION_CANCEL,
            payeverOxArticle::FIELD_CANCELLED
        );

        return $total - $cancelled;
    }

    /**
     * @inheritDoc
     */
    public function partialItemsFormAllowed($order)
    {
        if ($order->getFieldData('oxvoucherdiscount')) {
            return false;
        }

        return !($this->getActions($order) || $this->getActions($order, payeverorderaction::ACTION_CANCEL));
    }

    /**
     * @inheritDoc
     */
    public function partialAmountFormAllowed($order)
    {
        $allow = true;
        $articles = $order->getOrderArticles(true);
        foreach ($articles as $article) {
            if (
                $article->getFieldData($this->getActionField()) ||
                $article->getFieldData(payeverOxArticle::FIELD_CANCELLED)
            ) {
                $allow = false;
                break;
            }
        }

        return $allow;
    }

    /**
     * @inheritDoc
     */
    public function prefillAmountAllowed($order)
    {
        return $this->getActions($order) || $this->getActions($order, payeverorderaction::ACTION_CANCEL);
    }

    /**
     * Calculate available items qnt for partial form
     *
     * @param oxArticle $article
     *
     * @return int
     */
    public function getArticleAvailableQnt($article)
    {
        $qnt = $article->getFieldData('oxamount');
        $qnt -= $article->getFieldData('oxpayevercancelled');

        return $qnt;
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return payeverorderaction::ACTION_SHIPPING_GOODS;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_SHIPPED;
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
}
