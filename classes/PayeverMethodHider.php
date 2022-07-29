<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Enum\PaymentMethod;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverMethodHider
{
    const FAILED_METHODS_COOKIE_NAME  = 'payever_hidden_methods';

    protected static $instance;

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
            self::$instance = new self();
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
     * @param string $paymentMethod
     */
    public function processFailedMethod($paymentMethod)
    {
        $paymentMethod = PayeverConfig::removeMethodPrefix($paymentMethod);

        if (PaymentMethod::shouldHideOnReject($paymentMethod)) {
            $this->addFailedPaymentMethod($paymentMethod);
        }
    }

    /**
     * Check if user have already failed to process with payment method
     *
     * @param string $paymentMethod
     * @return bool
     */
    public function isHiddenPaymentMethod($paymentMethod)
    {
        return in_array(PayeverConfig::removeMethodPrefix($paymentMethod), $this->getHiddenMethods());
    }

    /**
     * @return array
     */
    private function getHiddenMethods()
    {
        return $this->isCurrentAddressesDifferent()
            ? array_merge($this->hiddenMethods, $this->getShouldHideOnDifferentAddressMethods())
            : $this->hiddenMethods;
    }

    /**
     *
     */
    private function getShouldHideOnDifferentAddressMethods()
    {
        return PayeverConfig::getAddressEqualityMethods() ? : PaymentMethod::getShouldHideOnDifferentAddressMethods();
    }

    /**
     * Save failed payment method to session if it should be hidden after error
     *
     * @param string $paymentMethod
     * @return void
     */
    private function addFailedPaymentMethod($paymentMethod)
    {
        $this->hiddenMethods[] = $paymentMethod;
        \oxRegistry::get('oxUtilsServer')->setOxCookie(
            self::FAILED_METHODS_COOKIE_NAME,
            implode('|', array_unique($this->hiddenMethods))
        );
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
