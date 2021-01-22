<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverAddressFactoryTrait
{
    /** @var PayeverAddressFactory */
    protected $addressFactory;

    /**
     * @param PayeverAddressFactory $addressFactory
     * @return $this
     * @internal
     */
    public function setAddressFactory(PayeverAddressFactory $addressFactory)
    {
        $this->addressFactory = $addressFactory;

        return $this;
    }

    /**
     * @return PayeverAddressFactory
     * @codeCoverageIgnore
     */
    protected function getAddressFactory()
    {
        return null === $this->addressFactory
            ? $this->addressFactory = new PayeverAddressFactory()
            : $this->addressFactory;
    }
}
