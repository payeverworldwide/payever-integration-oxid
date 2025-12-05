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
 * Getting dynamic values and params for Payever payment types
 *
 * @SuppressWarnings(PHPMD.ExitExpression)
 *
 * @extend oxBaseClass
 *
 * @codeCoverageIgnore
 */
class payeverclaim extends oxUBase
{
    use PayeverOxConfigTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverRequestHelperTrait;
    use PayeverInvoiceManagerTrait;

    const ACTION_SHOW_INVOICES = 'show_invoices';
    const ACTION_INVOICE = 'show_invoice';

    /**
     * @return void
     *
     * @throws oxSystemComponentException
     */
    public function render()
    {
        $this->requestMustBeAuthorized();

        $act = $this->getRequestHelper()->getQueryData('act');
        switch ($act) {
            case self::ACTION_SHOW_INVOICES:
                $buyerId = $this->getRequestHelper()->getQueryData('buyerId');
                $invoices = $this->getInvoiceManager()->getInvoicesByExternalID($buyerId);

                $result = [];
                foreach ($invoices as $invoice) {
                    $result[] = [
                        'url' => sprintf(
                            '%s?cl=payeverclaim&fnc=render&act=show_invoice&key=%s',
                            $this->getConfig()->getSslShopUrl(),
                            $invoice->getFieldData('OXINVOICEKEY')
                        ),
                        'paymentId' => $invoice->getFieldData('OXPAYMENTID'),
                    ];
                }

                echo json_encode($result);
                exit();
                break;
            case self::ACTION_INVOICE:
                $invoiceKey = $this->getRequestHelper()->getQueryData('key');
                $invoice = $this->getInvoiceManager()->getInvoiceByKey($invoiceKey);
                if (!$invoice) {
                    throw new \BadMethodCallException('Invoice is not found');
                }

                // Force browser to download the pdf
                $paymentId = $invoice->getFieldData('OXPAYMENTID');
                $contents = $invoice->getFieldData('OXCONTENTS');
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename=invoice_' . $paymentId . '.pdf');
                header('Content-length: ' . strlen($contents));
                header('Pragma: no-cache');
                header('Expires: 0');

                echo $contents;
                exit();
        }
    }

    /**
     * Checks if the request must be authorized and returns an error response if not.
     */
    private function requestMustBeAuthorized()
    {
        $user = $this->getRequestHelper()->getServer('PHP_AUTH_USER');
        $password = $this->getRequestHelper()->getServer('PHP_AUTH_PW');
        if (empty($user) || empty($password) || !$this->validateUserCredentials($user, $password)) {
            $this->createAuthenticateResponse();
        }
    }

    /**
     * Creates an authentication response with 401 Unauthorized status code and www-authenticate header.
     */
    private function createAuthenticateResponse()
    {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo "Unauthorized";
        exit();
    }

    private function validateUserCredentials($login, $password)
    {
        $oAuthUser = oxNew('oxUser');

        return $oAuthUser->login($login, $password, false);
    }
}
