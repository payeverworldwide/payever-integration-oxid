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
class Object2CategoryFactory
{
    /**
     * @return object|oxBase
     * @throws oxSystemComponentException
     */
    public function create()
    {
        $oNew = oxNew('oxbase');
        $oNew->init('oxobject2category');

        return $oNew;
    }
}
