<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

/**
 * @codeCoverageIgnore
 */
class PayeverLogsFactory
{
    /**
     * @return object|payeverlogs
     * @throws oxSystemComponentException
     */
    public function create()
    {
        return oxNew(payeverlogs::class);
    }
}
