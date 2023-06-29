<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOrderFactoryTrait
{
    /** @var PayeverOrderFactory */
    protected $orderFactory;

    /**
     * @param PayeverOrderFactory $orderFactory
     * @return $this
     * @internal
     */
    public function setOrderFactory(PayeverOrderFactory $orderFactory)
    {
        $this->orderFactory = $orderFactory;

        return $this;
    }

    /**
     * @return PayeverOrderFactory
     * @codeCoverageIgnore
     */
    protected function getOrderFactory()
    {
        return null === $this->orderFactory
            ? $this->orderFactory = new PayeverOrderFactory()
            : $this->orderFactory;
    }
}
