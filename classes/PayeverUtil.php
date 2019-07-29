<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Payever utilites and helper functions
 */
class PayeverUtil
{
    const FAILED_METHODS_COOKIE_NAME  = 'payever_hidden_methods';

    protected static $instance;

    /**
     * Payment options which will be hidden from customer after first unsuccessful attempt
     *
     * @todo Remove from here once it moved inside SDK
     *
     * @var array
     */
    protected $hideOnFailureMethods = [
        'santander_invoice_de',
        'santander_factoring_de',
    ];

    /**
     * Methods we should hide if shipping and billing addresses is different
     *
     * @todo Remove from here once it moved inside SDK
     *
     * @var array
     */
    protected $hideOnDifferentAddressMethods = [
        'santander_invoice_de',
        'payex_faktura',
        'santander_factoring_de',
    ];

    /**
     * Payment methods which are hidden for the current user session
     * Workaround on Cookies issue
     *
     * @var array $hiddenMethods
     */
    protected $hiddenMethods = [];

    /**
     * Get current payment instance
     *
     * @param null
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Intialize the default function for Payever utilities
     */
    private function __construct()
    {
        $this->loadHiddenMethodsFromCookie();
    }

    /**
     * @return array
     */
    public function getHideOnFailureMethods()
    {
        return $this->hideOnFailureMethods;
    }

    /**
     * @return array
     */
    public function getHideOnDifferentAddressMethods()
    {
        return array_map(
            function ($method) {
                return PayeverConfig::PLUGIN_PREFIX . $method;
            },
            $this->hideOnDifferentAddressMethods
        );
    }

    /**
     * @return array
     */
    public function getHiddenMethods()
    {
        return $this->isCurrentAddressesDifferent()
            ? array_merge($this->hiddenMethods, $this->getHideOnDifferentAddressMethods())
            : $this->hiddenMethods;
    }

    /**
     * Save failed payment method to session if it should be hidden after error
     *
     * @param string $paymentMethod
     * @return void
     */
    public function addFailedPaymentMethod($paymentMethod)
    {
        $this->hiddenMethods[] = PayeverConfig::PLUGIN_PREFIX . $paymentMethod;
        \oxRegistry::get('oxUtilsServer')->setOxCookie(self::FAILED_METHODS_COOKIE_NAME, implode('|', array_unique($this->hiddenMethods)));
    }

    /**
     * Check payment method if user already failed to process with
     *
     * @param string $paymentMethod
     * @return bool
     */
    public function isHiddenPaymentMethod($paymentMethod)
    {
        return in_array($paymentMethod, $this->getHiddenMethods());
    }

    /**
     * @return bool
     */
    private function isCurrentAddressesDifferent()
    {
        static $checkFields = ['oxcountry', 'oxcity', 'oxzip', 'oxstreet', 'oxstreetnr', 'oxfname', 'oxlname'];
        static $result = null;

        if ($result === null) {
            $result = false;
            $session = oxRegistry::getSession();

            if (!$session->getVariable('deladrid')) {
                // no different delivery address is chosen
                return $result;
            }

            // user object holds billing address
            $user = $session->getUser();
            $delivery = $user->getSelectedAddress();

            foreach ($checkFields as $checkField) {
                if ($user->getFieldData($checkField) !== $delivery->getFieldData($checkField)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    private function loadHiddenMethodsFromCookie()
    {
        $methods = \oxRegistry::get('oxUtilsServer')->getOxCookie(self::FAILED_METHODS_COOKIE_NAME);

        if ($methods) {
            $this->hiddenMethods = array_unique(explode('|', $methods));
        }
    }
}
