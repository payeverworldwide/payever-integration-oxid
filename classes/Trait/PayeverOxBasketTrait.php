<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxBasketTrait
{
    /**
     * @var oxbasket
     */
    private $oxBasket;

    /**
     * @codeCoverageIgnore
     * @return oxbasket
     */
    protected function getOxBasket()
    {
        if ($this->oxBasket === null) {
            return oxNew('oxbasket');
        }

        return $this->oxBasket;
    }

    /**
     * @param oxbasket $oxBasket
     */
    public function setOxBasket($oxBasket)
    {
        $this->oxBasket = $oxBasket;

        return $this;
    }
}
