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
 * Class PayeverFormCancel
 */
class PayeverFormCancel extends PayeverFormBase
{
    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $order
     *
     * @return array
     * @throws oxConnectionException
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
        return ActionDeciderInterface::ACTION_CANCEL;
    }

    /**
     * @inheritDoc
     */
    public function getActionField()
    {
        return payeverOxArticle::FIELD_CANCELLED;
    }
}
