<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

interface PayeverActionInterface
{
    const TYPE_ITEM = 'item';
    const TYPE_AMOUNT = 'amount';
    const TYPE_TOTAL = 'total';

    /**
     * @param oxOrder $oOrder
     *
     * @return \Payever\Sdk\Core\Http\Response
     */
    public function processActionRequest($oOrder);
}
