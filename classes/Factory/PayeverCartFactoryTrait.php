<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverCartFactoryTrait
{
    /** @var PayeverCartFactory */
    protected $cartFactory;

    /**
     * @param PayeverCartFactory $cartFactory
     * @return $this
     * @internal
     */
    public function setCartFactory(PayeverCartFactory $cartFactory)
    {
        $this->cartFactory = $cartFactory;

        return $this;
    }

    /**
     * @return PayeverCartFactory
     * @codeCoverageIgnore
     */
    protected function getCartFactory()
    {
        return null === $this->cartFactory
            ? $this->cartFactory = new PayeverCartFactory()
            : $this->cartFactory;
    }
}
