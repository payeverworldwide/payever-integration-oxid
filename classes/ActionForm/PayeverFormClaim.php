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
 * Class PayeverFormClaim
 */
class PayeverFormClaim
{
    use PayeverOrderTransactionHelperTrait;

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $oxOrder
     *
     * @return bool
     */
    public function isActionAllowed($oxOrder)
    {
        $claimAllowed = $this->isClaimActionAllowed($oxOrder);
        $claimUploadAllowed = $this->isClaimUploadActionAllowed($oxOrder);

        return $claimAllowed['enabled'] || $claimUploadAllowed['enabled'];
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $oxOrder
     *
     * @return array
     */
    public function isClaimActionAllowed($oxOrder)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed(
            $oxOrder,
            ActionDeciderInterface::ACTION_CLAIM
        );
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $oxOrder
     *
     * @return array
     */
    public function isClaimUploadActionAllowed($oxOrder)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed(
            $oxOrder,
            ActionDeciderInterface::ACTION_CLAIM_UPLOAD
        );
    }

    /**
     * @inheritDoc
     */
    public function getActionType()
    {
        return ActionDeciderInterface::ACTION_CLAIM;
    }
}
