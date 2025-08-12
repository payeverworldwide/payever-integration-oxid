<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

/**
 * Class PayeverFormCapture
 */
class PayeverFormCapture extends PayeverFormBase
{
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
