<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Enum\PaymentMethod;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverMethodHider
{
    use PayeverRequestHelperTrait;

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
     * @return array
     */
    public function getHiddenMethods()
    {
        return $this->hiddenMethods;
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
        $paymentMethod = PayeverConfigHelper::removeMethodPrefix($paymentMethod);

        if (PaymentMethod::shouldHideOnReject($paymentMethod)) {
            $this->addFailedPaymentMethod($paymentMethod);
        }
    }

    /**
     * Check if user have already failed to process with payment method
     *
     * @param string $paymentMethod
     * @param string $variantId
     * @return bool
     */
    public function isHiddenPaymentMethod($paymentMethod, $variantId)
    {
        $methodName = PayeverConfigHelper::removeMethodPrefix($paymentMethod);
        $hiddenForDevice = PaymentMethod::shouldHideOnCurrentDevice(
            $methodName,
            $this->getRequestHelper()->getServer('HTTP_USER_AGENT', '')
        );

        if ($hiddenForDevice) {
            return true;
        }

        if (
            $this->isCurrentAddressesDifferent()
            && $this->isHiddenMethodOnDifferentAddress($methodName, $variantId)
        ) {
            return true;
        }

        return in_array($methodName, $this->hiddenMethods);
    }

    /**
     * @return bool
     */
    private function isHiddenMethodOnDifferentAddress($paymentMethod, $variantId)
    {
        $checkVariant = PayeverConfig::checkVariantForAddressEquality();
        $hiddenMethodsOnDifferentAddress = $this->getShouldHideOnDifferentAddressMethods();

        if (!$checkVariant) {
            return in_array($paymentMethod, $hiddenMethodsOnDifferentAddress);
        }

        return in_array($variantId, $hiddenMethodsOnDifferentAddress);
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
