<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

interface PayeverActionTypeInterface
{
    const FIELD_REFUNDED = 'oxpayeverrefunded';
    const FIELD_CANCELLED = 'oxpayevercancelled';
    const FIELD_SHIPPED = 'oxpayevershipped';
    const FIELD_INVOICED = 'oxpayeverinvoiced';
    const FIELD_SETTLED = 'oxpayeversettled';

    /**
     * Get action type (cancel|refund|shipping)
     *
     * @return string
     */
    public function getActionType();

    /**
     * Get action field in order article table
     *
     * @return string
     */
    public function getActionField();
}
