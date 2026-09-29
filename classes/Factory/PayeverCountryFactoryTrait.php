<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverCountryFactoryTrait
{
    /** @var PayeverCountryFactory */
    protected $countryFactory;

    /**
     * @param PayeverCountryFactory $countryFactory
     * @return $this
     * @internal
     */
    public function setCountryFactory(PayeverCountryFactory $countryFactory)
    {
        $this->countryFactory = $countryFactory;

        return $this;
    }

    /**
     * @return PayeverCountryFactory
     * @codeCoverageIgnore
     */
    protected function getCountryFactory()
    {
        return null === $this->countryFactory
            ? $this->countryFactory = new PayeverCountryFactory()
            : $this->countryFactory;
    }
}
