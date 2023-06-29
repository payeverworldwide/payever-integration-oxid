<?php

/**
 * Class PayeverCancelManager
 */
class PayeverCancelManager extends PayeverOrderActionManager
{
    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $order
     *
     * @return array
     */
    public function isActionAllowed($order, $actionType = null)
    {
        $actions = parent::isActionAllowed($order, $actionType);

        //Check if order items was shipped
        $shipped = $this->getSentAmount(
            $order,
            payeverorderaction::ACTION_SHIPPING_GOODS,
            payeverOxArticle::FIELD_SHIPPED
        );

        if ($actions['enabled'] && !$actions['partialAllowed'] && $shipped) {
            $actions['enabled'] = false;
        }

        return $actions;
    }

    /**
     * @inheritDoc
     */
    public function processAmount($order, $amount)
    {
        try {
            //Send cancel api request
            $paymentId = $order->getFieldData('oxtransid');
            $response = $this->getPaymentsApiClient()->cancelPaymentRequest($paymentId, $amount);

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

            //Send cancel api request
            $paymentId = $order->getFieldData('oxtransid');
            $response = $this->getPaymentsApiClient()->cancelItemsPaymentRequest($paymentId, $paymentItems);

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
        $shipped = $this->getSentAmount(
            $order,
            payeverorderaction::ACTION_SHIPPING_GOODS,
            payeverOxArticle::FIELD_SHIPPED
        );

        return $total - $shipped;
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
                $article->getFieldData(payeverOxArticle::FIELD_SHIPPED)
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
    public function partialItemsFormAllowed($order)
    {
        if ($order->getFieldData('oxvoucherdiscount')) {
            return false;
        }

        return !($this->getActions($order) || $this->getActions($order, payeverorderaction::ACTION_SHIPPING_GOODS));
    }

    /**
     * @inheritDoc
     */
    public function prefillAmountAllowed($order)
    {
        return $this->getActions($order) || $this->getActions($order, payeverorderaction::ACTION_SHIPPING_GOODS);
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
        return $article->getFieldData('oxamount') - $article->getFieldData('oxpayevershipped');
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return payeverorderaction::ACTION_CANCEL;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_CANCELLED;
    }
}
