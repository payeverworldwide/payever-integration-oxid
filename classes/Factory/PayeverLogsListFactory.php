<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

/**
 * @codeCoverageIgnore
 */
class PayeverLogsListFactory
{
    /**
     * @return object|payeverlogslist
     * @throws oxSystemComponentException
     */
    public function create()
    {
        return oxNew('payeverlogslist');
    }
}
