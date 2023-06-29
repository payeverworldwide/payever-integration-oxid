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
class PayeverFieldFactory
{
    /**
     * @param mixed|null $value
     * @return object|oxfield
     */
    public function create($value = null)
    {
        $field = oxNew('oxfield');
        $field->setValue($value);

        return $field;
    }

    /**
     * @param mixed|null $value
     * @return object|oxfield
     */
    public function createRaw($value = null)
    {
        $field = oxNew('oxfield');
        $field->setValue($value, $field::T_RAW);

        return $field;
    }
}
