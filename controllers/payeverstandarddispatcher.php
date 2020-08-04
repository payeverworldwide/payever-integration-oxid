<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Lock\FileLock;
use Payever\ExternalIntegration\Core\Lock\LockInterface;
use Payever\ExternalIntegration\Payments\Enum\Status;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\CreatePaymentRequest;
use Payever\ExternalIntegration\Payments\Http\ResponseEntity\CreatePaymentResponse;
use Payever\ExternalIntegration\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\ExternalIntegration\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\ExternalIntegration\Payments\PaymentsApiClient;
use Psr\Log\LoggerInterface;

class payeverStandardDispatcher extends oxUBase
{
    const STATUS_PARAM = 'sts';

    const LOCK_WAIT_SECONDS = 30;

    const MR_SALUATATION = 'mr';
    const MRS_SALUATATION = 'mrs';
    const MS_SALUATATION = 'ms';

    const HEADER_SIGNATURE = 'X-PAYEVER-SIGNATURE';


    /** @var PaymentsApiClient */
    private $api;

    /** @var LoggerInterface */
    private $logger;

    /** @var LockInterface */
    private $locker;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->api = PayeverApiClientProvider::getPaymentsApiClient();
        $this->logger = PayeverConfig::getLogger();
        $this->locker = new FileLock(rtrim(oxRegistry::getConfig()->getLogsDir(), '/'));
    }


    public function processPayment()
    {
        $redirectUrl = $this->getSession()->getVariable('oxidpayever_payment_view_redirect_url');

        if ($redirectUrl) {
            $sUrl = oxRegistry::get("oxUtilsUrl")->processUrl($redirectUrl);
            oxRegistry::getUtils()->redirect($sUrl, false);
        }

        return "order";
    }

    /**
     * Creates payever payment and returns redirect URL
     *
     * @return bool|string
     */
    public function getRedirectUrl()
    {
        try {
            $paymentResponse = $this->api->createPaymentRequest($this->collectOrderDataForCall());
            /** @var CreatePaymentResponse $paymentResponseEntity */
            $paymentResponseEntity = $paymentResponse->getResponseEntity();

            $language = PayeverConfig::getLanguage() ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

            $this->logger->info("Payment successfully created", $paymentResponseEntity->toArray());

            return $paymentResponseEntity->getRedirectUrl() . '?_locale=' . $language;
        } catch (Exception $exception) {
            $this->logger->error(sprintf("Error while creating payment: %s", $exception->getMessage()));

            $this->addErrorToDisplay($exception->getMessage());

            return false;
        }
    }

    /**
     * @return CreatePaymentRequest
     *
     * @throws UnexpectedValueException
     */
    private function collectOrderDataForCall()
    {
        $this->logger->info("Collecting order data for create payment call...");

        $oSession = $this->getSession();
        $oBasket = $oSession->getBasket();
        $oUser = $oBasket->getBasketUser();
        $basketItems = [];

        foreach ($oBasket->getContents() as $item) {
            /** @var oxbasketitem $item */
            $basketItems[] = [
                'name' => $item->getTitle(),
                'price' => $item->getUnitPrice()->getPrice(),
                'priceNetto' => $item->getUnitPrice()->getNettoPrice(),
                'VatRate' => $item->getUnitPrice()->getVat(),
                'quantity' => $item->getAmount(),
                'thumbnail' => $item->getIconUrl(),
                'url' => $item->getLink()
            ];
        }

        if (!count($basketItems)) {
            throw new UnexpectedValueException("Basket is empty");
        }

        $soxAddressId = $oSession->getVariable('deladrid');
        /** @var oxaddress $deliveryAddress */
        $deliveryAddress = oxNew('oxaddress');

        if ($soxAddressId) {
            $deliveryAddress->load($soxAddressId);
        } else {
            $deliveryAddress->oxaddress__oxfname = $oUser->oxuser__oxfname;
            $deliveryAddress->oxaddress__oxlname = $oUser->oxuser__oxlname;
            $deliveryAddress->oxaddress__oxstreet = $oUser->oxuser__oxstreet;
            $deliveryAddress->oxaddress__oxstreetnr = $oUser->oxuser__oxstreetnr;
            $deliveryAddress->oxaddress__oxzip = $oUser->oxuser__oxzip;
            $deliveryAddress->oxaddress__oxcity = $oUser->oxuser__oxcity;
            $deliveryAddress->oxaddress__oxfon = $oUser->oxuser__oxfon;
            /** @var oxCountry $oxCountry */
            $oxCountry = oxNew('oxCountry');
            $oxCountry->load($oUser->oxuser__oxcountryid->value);
            $deliveryAddress->oxaddress__oxcountryid = $oxCountry->oxcountry__oxisoalpha2;
        }

        /**
         * cut off the plugin prefix {@see PayeverConfig::PLUGIN_PREFIX}
         */
        $apiMethod = substr($oBasket->getPaymentId(), strlen(PayeverConfig::PLUGIN_PREFIX));
        $version = PayeverConfig::getOxidVersionInt();

        $deliveryCost = ($version > 47) ? $oBasket->getDeliveryCost()->getPrice() : $oBasket->getCosts('oxdelivery')->getPrice();
        $paymentCost = ($version > 47) ?
            (count($oBasket->getPaymentCost()) ? $oBasket->getPaymentCost()->getPrice() : 0) :
            (count($oBasket->getCosts('oxpayment')) ? $oBasket->getCosts('oxpayment')->getPrice() : 0);

        $discounts = (count($oBasket->getVouchers())) ? $oBasket->getVouchers() : 0;
        if ($discounts) {
            foreach ($discounts as $discount) {
                $basketItems[] = [
                    'name' => "Discount",
                    'price' => $discount->dVoucherdiscount * (-1),
                    'quantity' => 1
                ];
            }
        }

        $createPaymentRequestEntity = new CreatePaymentRequest();

        if (strpos($apiMethod, '-')) {
            $oPayment = oxNew('oxpayment');
            $oPayment->load($oBasket->getPaymentId());
            $oxvariants = json_decode($oPayment->oxpayments__oxvariants->rawValue, true);
            $createPaymentRequestEntity
                ->setPaymentMethod($oxvariants['paymentMethod'])
                ->setVariantId($oxvariants['variantId']);
        } else {
            $createPaymentRequestEntity
                ->setPaymentMethod($apiMethod);
        }

        $createPaymentRequestEntity
            ->setAmount($oBasket->getPrice()->getPrice() - $paymentCost)
            ->setFee($deliveryCost)
            ->setOrderId($oUser->getBasket('savedbasket')->getId())
            ->setCurrency($oBasket->getBasketCurrency()->name)
            ->setFirstName($deliveryAddress->oxaddress__oxfname->value)
            ->setLastName($deliveryAddress->oxaddress__oxlname->value)
            ->setPhone($deliveryAddress->oxaddress__oxfon->value)
            ->setEmail($oUser->oxuser__oxusername->value)
            ->setStreet($deliveryAddress->oxaddress__oxstreet->value . ' ' . $deliveryAddress->oxaddress__oxstreetnr->value)
            ->setCity($deliveryAddress->oxaddress__oxcity->value)
            ->setCountry($deliveryAddress->oxaddress__oxcountryid->value)
            ->setZip($deliveryAddress->oxaddress__oxzip->value)
            ->setSuccessUrl($this->generateCallbackUrl('success'))
            ->setPendingUrl($this->generateCallbackUrl('success', ['is_pending' => true]))
            ->setCancelUrl($this->generateCallbackUrl('cancel'))
            ->setFailureUrl($this->generateCallbackUrl('failure'))
            ->setNoticeUrl($this->generateCallbackUrl('notice'))
            ->setPluginVersion(PayeverConfig::getPluginVersion())
            ->setCart($basketItems);

        $birthdate = $deliveryAddress->getUser()->oxuser__oxbirthdate->value;

        if (!empty($birthdate) && $birthdate != '0000-00-00') {
            $createPaymentRequestEntity->setBirthdate($birthdate);
        }

        $salutation = $this->getSalutation($oUser->oxuser__oxsal->value);
        if ($salutation) {
            $createPaymentRequestEntity->setSalutation($salutation);
        }

        $this->logger->info("Finished collecting order data for create payment call", $createPaymentRequestEntity->toArray());

        return $createPaymentRequestEntity;
    }

    /**
     * @param string $salutation
     * @return string
     */
    private function getSalutation($salutation)
    {
        $salutation = strtolower($salutation);
        return ($salutation == self::MR_SALUATATION
            || $salutation == self::MRS_SALUATATION
            || $salutation == self::MS_SALUATATION) ? $salutation : false;
    }


    public function redirectToThankYou()
    {
        if ($this->_isIframePayeverPayment()) {
            $back_link = $this->getConfig()->getSslShopUrl() . "?cl=thankyou";
            $script = '<script>function iframeredirect(){window.top.location = "' . $back_link . '"} iframeredirect();</script>';

            echo $script;
            exit;
        }

        return 'thankyou';
    }

    public function addErrorToDisplay($errors)
    {
        oxRegistry::getSession()->deleteVariable('Errors');
        $oEx = oxNew('oxException');
        if (is_array($errors)) {
            $errors = array_unique($errors);
            foreach ($errors as $_error) {
                $oEx->setMessage($_error);
            }
        } else {
            $oEx->setMessage($errors);
        }

        oxRegistry::get("oxUtilsView")->addErrorToDisplay($oEx);
    }

    /**
     * Generating callback url.
     *
     * @param string $status
     * @param array $params
     *
     * @return string
     */
    private function generateCallbackUrl($status, $params = [])
    {
        $shopUrl = $this->getConfig()->getSslShopUrl();
        $urlData = [
            'cl' => 'payeverStandardDispatcher',
            'fnc' => 'payeverGatewayReturn',
            self::STATUS_PARAM => $status,
            'payment_id' => '--PAYMENT-ID--',
            'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5')
        ];

        $urlData = array_merge($urlData, $params);

        $shopUrl .= '?' . http_build_query($urlData, "", "&");

        return $shopUrl;
    }

    /**
     * Checks mode.
     *
     * @return bool
     */
    private function _isIframePayeverPayment()
    {
        return !PayeverConfig::getIsRedirect();
    }

    /**
     * Main entry point for all payever callbacks & notifications
     *
     * @return void
     */
    public function payeverGatewayReturn()
    {
        $config = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $sts = $config->getRequestParameter(static::STATUS_PARAM);

        $this->logger->info(sprintf("Handling callback type: %s, paymentId: %s", $sts, $paymentId), $_GET);

        if ($sts == 'cancel') {
            return $this->processCancel();
        }

        $_POST['sDeliveryAddressMD5'] = $config->getRequestParameter('sDeliveryAddressMD5');
        if (!$config->getRequestParameter('ord_agb') && $config->getConfigParam('blConfirmAGB')) {
            $_POST['ord_agb'] = 1;
        }
        if ($config->getConfigParam('blEnableIntangibleProdAgreement')) {
            if (!$config->getRequestParameter('oxdownloadableproductsagreement')) {
                $_POST['oxdownloadableproductsagreement'] = 1;
            }
            if (!$config->getRequestParameter('oxserviceproductsagreement')) {
                $_POST['oxserviceproductsagreement'] = 1;
            }
        }

        $payment = ['paymentId' => $paymentId, 'paymentSts' => $config->getRequestParameter('sts')];

        $this->logger->info("Waiting for lock", $payment);

        $this->locker->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);

        $this->logger->info("Locked", $payment);

        try {
            $retrievePaymentResult = $this->getRetrievePaymentResultEntity($payment);
            $paymentDetails = $retrievePaymentResult->getPaymentDetails();

            $payment['basketId'] = $retrievePaymentResult->getReference();
            $payment['paymentMethod'] = PayeverConfig::PLUGIN_PREFIX . $retrievePaymentResult->getPaymentType();
            $payment['panId'] = isset($paymentDetails['usage_text']) ? $paymentDetails['usage_text'] : null;

            $oxidOrderStatus = $this->getInternalStatus($retrievePaymentResult->getStatus());
            $isPending = $config->getRequestParameter('is_pending');
            $isPaid = $this->isPaidStatus($oxidOrderStatus);

            $order = $this->getOrderByPaymentId($paymentId);

            if ($order) {
                $notificationTimestamp = 0;
                $rawData = $this->getRawData();
                if ($rawData) {
                    $notificationTimestamp = !empty($rawData['created_at'])
                        ? strtotime($rawData['created_at'])
                        : $notificationTimestamp;
                    if ($payment['paymentSts'] == 'notice' && $this->shouldRejectNotification($order, $notificationTimestamp)) {
                        $payment['errorMessage'] = 'Notification rejected: newer notification already processed';
                        return $this->rejectPayment($payment, false);
                    }
                }

                $oParams = ['oxorder__oxtransstatus' => $oxidOrderStatus];

                $hasPaidDate = $order->oxorder__oxpaid
                    && $order->oxorder__oxpaid->rawValue
                    && $order->oxorder__oxpaid->rawValue != '0000-00-00 00:00:00';

                if (!$hasPaidDate && $isPaid && !$isPending) {
                    $oParams['oxorder__oxpaid'] = $this->generatePaidDate();
                }

                if ($notificationTimestamp) {
                    $oParams['oxorder__payever_notification_timestamp'] = $notificationTimestamp;
                }

                $order->assign($oParams);
                $order->save();

                $this->locker->releaseLock($payment['paymentId']);

                $this->logger->info(
                    sprintf("Payment unlocked. Order has been updated: %s, status: %s", $order->getId(), $oxidOrderStatus),
                    $payment
                );

                if ($payment['paymentSts'] == 'notice') {
                    echo json_encode(['result' => 'success', 'message' => 'Order is updated']);
                } else {
                    $this->_webRedirect(
                        $this->getConfig()->getSslShopUrl()
                        . '?cl=payeverStandardDispatcher&fnc=redirectToThankYou'
                    ); // exit
                }

                exit();
            }

            if ($isPaid) {
                if ($isPending) {
                    $this->addErrorToDisplay('Thank you, your order has been received. You will receive an update once your request has been processed.');
                }

                $orderStateId = $this->processSuccess($payment, $oxidOrderStatus, $isPending); // exit

                $this->locker->releaseLock($payment['paymentId']);

                $orderAction = $orderStateId == oxOrder::ORDER_STATE_OK ? 'created' : 'updated';
                $this->logger->info(
                    sprintf("Payment unlocked. Order has been %s, status: %s", $orderAction, $oxidOrderStatus),
                    $payment
                );

                if ($payment['paymentSts'] == 'notice') {
                    oxRegistry::getSession()->deleteVariable('sess_challenge');
                    echo json_encode(['result' => 'success', 'message' => 'Order is ' . $orderAction]);
                } else {
                    $sUrl = $this->getConfig()->getSslShopUrl() . '?cl=payeverStandardDispatcher&fnc=redirectToThankYou';
                    $this->_webRedirect($sUrl);
                }

                exit();
            }

            /**
             * If we got here - payment wasn't successful
             */
            $paymentMethod = $retrievePaymentResult->getPaymentType();

            PayeverMethodHider::getInstance()->processFailedMethod($paymentMethod);

            $message = strpos($paymentMethod, 'santander') !== false
                ? 'Unfortunately, the application was not successful. Please choose another payment option to pay for your order.'
                : 'The payment was not successful. Please try again or choose another payment option.';

            throw new oxException($message, 1);
        } catch (Exception $exception) {
            $this->logger->error(sprintf("Payment unlocked by exception: %s", $exception->getMessage()), $payment);

            $payment['errorMessage'] = $exception->getMessage();
            $this->addErrorToDisplay($exception->getMessage());

            return $this->rejectPayment($payment, $exception->getCode() < 1);
        }
    }

    /**
     * @param $payeverStatus
     * @return string
     */
    private function getInternalStatus($payeverStatus)
    {
        switch ($payeverStatus) {
            case Status::STATUS_PAID:
            case Status::STATUS_ACCEPTED:
                return 'OK';
            case Status::STATUS_IN_PROCESS:
            case Status::STATUS_DECLINED:
            case Status::STATUS_REFUNDED:
            case Status::STATUS_FAILED:
            case Status::STATUS_CANCELLED:
            case Status::STATUS_NEW:
            default:
                return substr($payeverStatus, strlen('STATUS_'));
        }
    }

    /**
     * @param string $oxidStatus
     * @return bool
     */
    private function isPaidStatus($oxidStatus)
    {
        return in_array($oxidStatus, ['OK', 'IN_PROCESS']);
    }

    /**
     * @return false|string
     */
    private function generatePaidDate()
    {
        return date("Y-m-d H:i:s", time());
    }

    /**
     * Returns order object.
     *
     * @param string $paymentId
     *
     * @return oxOrder | false
     *
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function getOrderByPaymentId($paymentId)
    {
        $oDb = oxDb::getDb();
        $sQuery = "SELECT oxid FROM oxorder WHERE oxtransid = '" . $paymentId . "' AND oxordernr > 0 LIMIT 1";
        $iOrderId = $oDb->GetOne($sQuery);

        if ($iOrderId) {
            /** @var oxorder $oOrder */
            $oOrder = oxNew('oxorder');
            $oOrder->load($iOrderId);

            return $oOrder;
        }

        return false;
    }

    /**
     * Returns user id.
     *
     * @param string $basketId
     *
     * @return int
     *
     * @throws oxSystemComponentException
     */
    protected function getUserByBasketId($basketId)
    {
        /** @var oxuserbasket $oUserBasket */
        $oUserBasket = oxNew('oxuserbasket');
        $aWhere = ['oxuserbaskets.oxid' => $basketId, 'oxuserbaskets.oxtitle' => 'savedbasket'];

        // creating if it does not exist
        $oUserBasket->assignRecord($oUserBasket->buildSelectString($aWhere));

        return $oUserBasket->oxuserbaskets__oxuserid;
    }

    /**
     * @param array $payment
     * @param string $oxidOrderStatus
     * @param bool $isPending
     * @return int - order state
     *
     * @throws oxException
     *
     * @throws \Exception
     */
    protected function processSuccess($payment, $oxidOrderStatus, $isPending = false)
    {
        //additional check if we really really have a user now
        $oUser = $this->getUser();
        if (!$oUser) {
            $oUser = oxNew('oxuser');
            $userId = $this->getUserByBasketId($payment['basketId']);
            $oUser->load($userId);
        }

        if (!$oUser) {
            $this->logger->alert(sprintf('NOT FOUND user for basketId "%s"', $payment['basketId']));
            throw new oxException('USER_NOT_FOUND');
        }

        // get basket contents
        /** @var oxBasket $oBasket */
        $oBasket = oxNew('oxbasket');
        $oBasket->setBasketUser($oUser);
        $oBasket->setPayment($payment['paymentMethod']);
        $oBasket->load();
        $oBasket->calculateBasket(true);

        if (!$oBasket->getProductsCount()) {
            // fallback to session data, which does not work for notice callbacks
            $oSession = $this->getSession();
            $oBasket = $oSession->getBasket();
            $oUser = $oBasket->getBasketUser();
        }

        if (!$oBasket->getProductsCount()) {
            $this->logger->alert(sprintf('Got empty basket basketId "%s"', $payment['basketId']));
            throw new oxException('BASKET_EMPTY');
        }

        /** @var payeverOxOrder $oOrder */
        $oOrder = oxNew('oxorder');
        //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
        if ($oOrder instanceof payeverOxOrderCompatible) {
            $orderStateId = $oOrder->setOrderStatus($oxidOrderStatus)->finalizeOrder($oBasket, $oUser, false);
        } else {
            $orderStateId = $oOrder->finalizeOrder($oBasket, $oUser, false, $oxidOrderStatus);
        }

        // performing special actions after user finishes order (assignment to special user groups)
        $oUser->onOrderExecute($oBasket, $orderStateId);

        if (!in_array($orderStateId, [oxOrder::ORDER_STATE_OK, oxOrder::ORDER_STATE_ORDEREXISTS])) {
            throw new oxException('Bad order.');
        }

        $oSession = $this->getSession();

        $oSession->oxidpayever_payment_id = $payment['paymentId'];
        $oSession->setVariable('oxidpayever_payment_id', $payment['paymentId']);
        $oOrder->load($oBasket->getOrderId());
        $up = oxNew('oxUserPayment');
        $up->load((string)$oOrder->oxorder__oxpaymentid);

        $aParams['oxuserpayments__oxpspayever_transaction_id'] = $payment['paymentId'];
        $aParams['oxuserpayments__oxpaymentsid'] = $payment['paymentMethod'];
        $up->assign($aParams);
        $up->save();

        $oParams = [
            'oxorder__basketid' => $payment['basketId'],
            'oxorder__oxpaymenttype' => $payment['paymentMethod'],
            'oxorder__oxtransid' => $payment['paymentId'],
            'oxorder__panid' => $payment['panId'],
        ];

        if (!$isPending) {
            $oParams['oxorder__oxpaid'] = $this->generatePaidDate();
        }

        $oOrder->assign($oParams);
        $oOrder->save();

        $oSession->deleteVariable('oxidpayever_payment_view_iframe_url');
        $oSession->deleteVariable('oxidpayever_payment_view_type');
        $oSession->deleteVariable('oxidpayever_payment_id');

        if ($payment['paymentSts'] == 'notice') {
            $oBasket->deleteBasket();
        }

        return $orderStateId;
    }

    /**
     * @param array $payment
     *
     * @return void
     */
    private function rejectPayment($payment, $badRequest = true)
    {
        $this->locker->releaseLock($payment['paymentId']);

        if ($badRequest) {
            header('HTTP/1.1 400 BAD REQUEST');
        }

        $this->logger->info("Rejecting payment", $payment);

        if ($payment['paymentSts'] == 'notice') {
            echo json_encode(['result' => 'error', 'message' => $payment['errorMessage']]);
        } else {
            $this->processCancel($payment['errorMessage']);
        }

        exit();
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function processCancel($message = '')
    {
        $message = $message ?: 'Payment was cancelled';

        $this->logger->info(sprintf("Canceling payment with message: %s", $message));

        $this->addErrorToDisplay($message);
        $this->_redirectToCart();
    }

    /**
     * @return void
     */
    private function _redirectToCart()
    {
        $sUrl = $this->getConfig()->getSslShopUrl() . '?cl=payment';
        $this->_webRedirect($sUrl);
    }

    /**
     * @param string $url
     *
     * @return void
     */
    private function _webRedirect($url)
    {
        echo "<html><head><script language=\"javascript\">
                <!--
                parent.document.location.href=\"{$url}\";
                //-->
                </script>
                </head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
        exit();
    }

    /**
     * @param $order
     * @param $notificationTimestamp
     * @return bool
     */
    private function shouldRejectNotification($order, $notificationTimestamp)
    {
        return ((int)$order->oxorder__payever_notification_timestamp->rawValue >= $notificationTimestamp);
    }

    /**
     * Executing plugin commands
     *
     * @throws Exception
     */
    public function executePluginCommands()
    {
        try {
            PayeverApiClientProvider::getPluginsApiClient()->registerPlugin();
            PayeverApiClientProvider::getPluginCommandManager()->executePluginCommands(PayeverConfig::getPluginCommandTimestamt());
            $this->getConfig()->saveShopConfVar('arr', PayeverConfig::VAR_PLUGIN_COMMANDS, array(PayeverConfig::KEY_PLUGIN_COMMAND_TIMESTAMP => time()));
            echo json_encode(['result' => 'success', 'message' => 'Plugin commands have been executed']);
        } catch (\oxException $exception) {
            $message = sprintf('Plugin command execution failed: %s', $exception->getMessage());
            echo json_encode(['result' => 'failed', 'message' => $message]);
            $this->logger->warning($message);
        }

        exit();
    }

    /**
     * @param array $payment
     * @return RetrievePaymentResultEntity
     * @throws Exception
     */
    private function getRetrievePaymentResultEntity($payment)
    {
        $paymentId = $payment['paymentId'];
        $signature = null;
        $headers = $this->getRequestHeaders();
        foreach ($headers as $headerName => $headerValue) {
            if (strtolower(self::HEADER_SIGNATURE) === strtolower($headerName)) {
                $signature = $headerValue;
                break;
            }
        }
        if (null === $signature) {
            $retrievePaymentResponse = $this->api->retrievePaymentRequest($paymentId);
            /** @var RetrievePaymentResponse $retrievePaymentEntity */
            $retrievePaymentEntity = $retrievePaymentResponse->getResponseEntity();
            /** @var RetrievePaymentResultEntity $retrievePaymentResult */
            $retrievePaymentResult = $retrievePaymentEntity->getResult();
        } else {
            $expectedSignature = hash_hmac(
                'sha256',
                PayeverConfig::getApiClientId() . $paymentId,
                PayeverConfig::getApiClientSecret()
            );
            if ($expectedSignature === $signature) {
                $data = $this->getRawData();
                $retrievePaymentResult = new RetrievePaymentResultEntity(
                    !empty($data['data']['payment']) ? $data['data']['payment'] : []
                );
            } else {
                $payment['errorMessage'] = 'Notification rejected: invalid signature';
                $this->rejectPayment($payment);
            }
        }

        return $retrievePaymentResult;
    }

    /**
     * @return array
     */
    private function getRawData()
    {
        $data = [];
        $rawContent = file_get_contents('php://input');
        if ($rawContent) {
            $rawData = json_decode($rawContent, true);
            $data = $rawData ?: [];
        }

        return $data;
    }

    /**
     * @return array
     */
    private function getRequestHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }

        return $headers;
    }
}
