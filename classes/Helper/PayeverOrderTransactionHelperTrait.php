<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOrderTransactionHelperTrait
{
    /** @var PayeverOrderTransactionHelper */
    protected $orderTransactionHelper;

    /**
     * @param PayeverOrderTransactionHelper $orderTransactionHelper
     * @return $this
     */
    public function setOrderTransactionHelper(PayeverOrderTransactionHelper $orderTransactionHelper)
    {
        $this->orderTransactionHelper = $orderTransactionHelper;

        return $this;
    }

    /**
     * @return PayeverOrderTransactionHelper
     * @codeCoverageIgnore
     */
    protected function getOrderTransactionHelper()
    {
        return null === $this->orderTransactionHelper
            ? $this->orderTransactionHelper = PayeverOrderTransactionHelper::getInstance()
            : $this->orderTransactionHelper;
    }
}
