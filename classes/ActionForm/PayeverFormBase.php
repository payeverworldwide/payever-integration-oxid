<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Class PayeverBaseForm
 *
 * @codeCoverageIgnore
 */
abstract class PayeverFormBase
{
    use PayeverOrderActionHelperTrait;
    use PayeverPaymentActionHelperTrait;
    use PayeverOrderTransactionHelperTrait;
    use PayeverLoggerTrait;
    use PayeverFieldFactoryTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @var bool
     */
    public $confirmCheckbox = false;

    /**
     * Gets actions list for the order by action type
     *
     * @param oxOrder $order
     *
     * @return array
     *
     * @throws oxConnectionException
     */
    public function getActions($order, $actionType = null)
    {
        return $this->getOrderActionHelper()->getActions($order->getId(), $actionType ?: $this->getActionType());
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $order
     *
     * @return array
     */
    public function isActionAllowed($order, $actionType = null)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed($order, $actionType ?: $this->getActionType());
    }

    /**
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    public function isWrapCostSent($order)
    {
        return $this->getOrderActionHelper()
            ->isActionExists($order->getId(), $this->getActionType(), payeverorderaction::TYPE_WRAP_COST);
    }

    /**
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    public function isGiftCardCostSent($order)
    {
        return $this->getOrderActionHelper()
            ->isActionExists($order->getId(), $this->getActionType(), payeverorderaction::TYPE_GIFTCARD_COST);
    }

    /**
     * Get the total amount for action
     *
     * @param oxOrder $order
     *
     * @return float
     */
    public function getTotalAmount($order)
    {
        return $this->getOrderTransactionHelper()->getTotal($order);
    }

    /**
     * Get the amount for sent actions
     *
     * @param oxOrder $order
     *
     * @return float
     *
     * @throws oxConnectionException
     */
    public function getSentAmount($order, $actionType = null, $actionField = null)
    {
        $partialTotal = $this->getOrderActionHelper()
            ->getSentAmount($order->getId(), $actionType ?: $this->getActionType());

        $articles = $order->getOrderArticles(true);
        foreach ($articles as $article) {
            /** @var oxArticle $article */
            $partialQnt = $article->getFieldData($actionField ?: $this->getActionField());
            if ($partialQnt) {
                $partialTotal += $article->getFieldData('oxbprice') * $partialQnt;
            }
        }

        return $partialTotal;
    }

    /**
     * Check if the partial amount form is allowed
     * If the partial items form is in progress, the amount form is not allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     */
    abstract public function partialAmountFormAllowed($order);

    /**
     * Check if the prefill form is allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     */
    abstract public function prefillAmountAllowed($order);

    /**
     * Check if the partial items form is allowed
     * If the partial amount form is in progress, the items form is not allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    abstract public function partialItemsFormAllowed($order);

    /**
     * Get action type (cancel|refund|shipping)
     *
     * @return string
     */
    abstract public function getActionType();

    /**
     * Get action field in order article table
     *
     * @return string
     */
    abstract public function getActionField();
}
