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
 * Class PayeverFormSettle
 */
class PayeverFormSettle extends PayeverFormBase
{
    use PayeverOrderTransactionHelperTrait;

    /**
     * @inheritDoc
     */
    public function isActionAllowed($order, $actionType = null)
    {
        return $this->isSettleActionAllowed($order);
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $oxOrder
     *
     * @return array
     */
    public function isSettleActionAllowed($oxOrder)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed(
            $oxOrder,
            ActionDeciderInterface::ACTION_SETTLE
        );
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_SETTLE;
    }

    public function getActionField()
    {
        return PayeverActionTypeInterface::FIELD_SETTLED;
    }

    /**
     * @inheritDoc
     */
    public function partialAmountFormAllowed($order)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prefillAmountAllowed($order)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function partialItemsFormAllowed($order)
    {
        return false;
    }
}
