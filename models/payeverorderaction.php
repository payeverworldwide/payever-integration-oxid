<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */
class payeverorderaction extends oxBase
{
    const ACTION_REFUND = 'refund';
    const ACTION_SHIPPING_GOODS = 'shipping_goods';
    const ACTION_CANCEL = 'cancel';
    const ACTION_SETTLE = 'settle';
    const ACTION_INVOICE = 'invoice';

    const TYPE_PRODUCT = 'product';
    const TYPE_WRAP_COST = 'wrapcost';
    const TYPE_GIFTCARD_COST = 'giftcardcost';

    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('payeverorderaction');
    }
}
