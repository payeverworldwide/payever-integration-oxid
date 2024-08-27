<?php

/**
 * Class PayeverRefundManager
 */
class PayeverRefundManager extends PayeverOrderActionManager
{
    /**
     * @var bool
     */
    public $confirmCheckbox = true;

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

        //Disable form if order items was not shipped
        $total = $this->getTotalAmount($order);
        if (!$total) {
            $actions['enabled'] = false;
        }

        return $actions;
    }

    /**
     * @inheritDoc
     */
    public function processAmount($order, $amount, $identifier = null)
    {
        $paymentId = $order->getFieldData('oxtransid');

        return $this->getPaymentsApiClient()->refundPaymentRequest($paymentId, $amount, $identifier);
    }

    /**
     * @inheritDoc
     */
    public function processItems($order, $items, $identifier = null)
    {
        $paymentId = $order->getFieldData('oxtransid');
        $paymentItems = $this->getPaymentItemEntities($order, $items);

        //Send cancel api request
        return $this->getPaymentsApiClient()->refundItemsPaymentRequest(
            $paymentId,
            $paymentItems,
            null,
            $identifier
        );
    }

    /**
     * @inheritDoc
     */
    public function getTotalAmount($order)
    {
        $amount = $this->getSentAmount(
            $order,
            payeverorderaction::ACTION_SHIPPING_GOODS,
            payeverOxArticle::FIELD_SHIPPED
        );

        $method = $order->getFieldData('oxpaymenttype');
        if ($method === 'oxpe_santander_factoring_de') {
            $amount = parent::getTotalAmount($order);
        }

        return $amount;
    }

    /**
     * @inheritDoc
     */
    public function partialAmountFormAllowed($order)
    {
        $shippingAllow = parent::isActionAllowed($order, payeverorderaction::ACTION_SHIPPING_GOODS);

        $allow = true;
        $articles = $order->getOrderArticles(true);
        foreach ($articles as $article) {
            /** @var oxArticle $article */
            //Check if refund by items in progress
            //Or shipping by items in progress
            if (
                $article->getFieldData($this->getActionField()) ||
                $article->getFieldData(payeverOxArticle::FIELD_SHIPPED) && $shippingAllow['enabled']
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
        $qnt = $article->getFieldData(payeverOxArticle::FIELD_SHIPPED);

        $method = $article->getOrder()->getFieldData('oxpaymenttype');
        if ($method === 'oxpe_santander_factoring_de') {
            $qnt = $article->getFieldData('oxamount');
        }

        return $qnt;
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return payeverorderaction::ACTION_REFUND;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_REFUNDED;
    }
}
