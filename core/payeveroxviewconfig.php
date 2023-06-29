<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Getter of Payever configuration inside views
 *
 * @extend oxviewconfig
 */
class payeverOxViewConfig extends payeverOxViewConfig_parent
{
    /**
     * Dispaly Payment description, logo in Payment page
     *
     * @param null
     * @return array
     */
    public function displayPaymentDesc()
    {
        return [
            'payever' => [
                'display_desc' => PayeverConfig::getDisplayDescription(),
                'display_logo' => PayeverConfig::getDisplayIcon(),
            ],
        ];
    }

    /**
     * Check if we should add "SANDBOX MODE" to payment name in checkout
     *
     * @return int
     */
    public function getModeTitle()
    {
        return PayeverConfig::getApiMode();
    }

    /**
     * Whether we should display "Reference" column in admin order grid
     *
     * @return int
     */
    public function displayBasketId()
    {
        return PayeverConfig::getDisplayBasketId();
    }

    /**
     * Display Payment Name in Frontend Order History
     *
     * @param null
     * @return string
     */
    public function displayPayment()
    {
        return $this->getSession()->getBasket()->getPaymentId();
    }

    /**
     * @param string $methodCode
     * @return bool
     */
    public function isPayeverMethod($methodCode)
    {
        return strpos($methodCode, PayeverConfig::PLUGIN_PREFIX) !== false;
    }
}
