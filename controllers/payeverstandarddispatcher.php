<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\RequestEntity;
use Payever\ExternalIntegration\Core\Lock\FileLock;
use Payever\ExternalIntegration\Core\Lock\LockInterface;
use Payever\ExternalIntegration\Payments\Enum\Status;
use Payever\ExternalIntegration\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\CreatePaymentRequest;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\SubmitPaymentRequest;
use Payever\ExternalIntegration\Payments\Http\ResponseEntity\CreatePaymentResponse;
use Payever\ExternalIntegration\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandManager;
use Payever\ExternalIntegration\Plugins\PluginsApiClient;

class payeverStandardDispatcher extends oxUBase
{
    use PayeverAddressFactoryTrait;
    use PayeverConfigHelperTrait;
    use PayeverCountryFactoryTrait;
    use DryRunTrait;
    use PayeverDatabaseTrait;
    use PayeverLoggerTrait;
    use PayeverMethodHiderTrait;
    use PayeverOrderFactoryTrait;
    use PayeverOrderHelperTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverPaymentMethodFactoryTrait;
    use PayeverRequestHelperTrait;

    const STATUS_PARAM = 'sts';

    const LOCK_WAIT_SECONDS = 30;

    const MR_SALUTATION = 'mr';
    const MRS_SALUTATION = 'mrs';
    const MS_SALUTATION = 'ms';

    const HEADER_SIGNATURE = 'X-PAYEVER-SIGNATURE';

    const SESS_PAYMENT_ID = 'payever_payment_id';
    const SESS_IS_REDIRECT_METHOD = 'payever_is_redirect_method';

    /** @var LockInterface */
    private $locker;

    /** @var oxutils */
    private $utils;

    /** @var oxutilsurl */
    private $urlUtil;

    /** @var oxutilsview */
    private $viewUtil;

    /** @var PluginsApiClient */
    private $pluginsApiClient;

    /** @var PluginCommandManager */
    private $pluginCommandManager;

    /** @var oxbasket  */
    private $cart;

    /** @var oxpayment */
    private $paymentMethod;

    /**
     * @return string
     */
    public function processPayment()
    {
        $redirectUrl = $this->getSession()->getVariable('oxidpayever_payment_view_redirect_url');
        if ($redirectUrl) {
            $sUrl = $this->getUrlUtil()->processUrl($redirectUrl);
            $this->getUtils()->redirect($sUrl, false);
        }

        return 'order';
    }

    /**
     * Creates payever payment and returns redirect URL
     *
     * @return bool|string
     */
    public function getRedirectUrl()
    {
        try {
            $isRedirectMethod = (bool) $this->getPaymentMethod()->getFieldData('oxisredirectmethod');
            if ($isRedirectMethod) {
                $paymentRequestEntity = $this->getSubmitPaymentRequestEntity();
                $response = $this->getPaymentsApiClient()->submitPaymentRequest($paymentRequestEntity);
                /** @var RetrievePaymentResponse $responseEntity */
                $responseEntity = $response->getResponseEntity();
                /** @var RetrievePaymentResultEntity $result */
                $result = $responseEntity->getResult();
                $redirectUrl = $result->getPaymentDetails()->getRedirectUrl();
                $this->getSession()->setVariable(self::SESS_PAYMENT_ID, $result->getId());
                $this->getSession()->setVariable(self::SESS_IS_REDIRECT_METHOD, true);
                $this->getLogger()->info('Payment successfully submitted', $responseEntity->toArray());
            } else {
                $paymentRequestEntity = $this->getCreatePaymentRequestEntity();
                $response = $this->getPaymentsApiClient()->createPaymentRequest($paymentRequestEntity);
                /** @var CreatePaymentResponse $responseEntity */
                $responseEntity = $response->getResponseEntity();
                $language = $this->getConfigHelper()->getLanguage()
                    ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                $redirectUrl = $responseEntity->getRedirectUrl() . '?_locale=' . $language;
                $this->getLogger()->info('Payment successfully created', $responseEntity->toArray());
            }

            return $redirectUrl;
        } catch (Exception $exception) {
            $this->getLogger()->error(sprintf('Error while creating payment: %s', $exception->getMessage()));
            $this->addErrorToDisplay($exception->getMessage());

            return false;
        }
    }

    /**
     * @return SubmitPaymentRequest
     * @throws oxSystemComponentException
     */
    private function getSubmitPaymentRequestEntity()
    {
        $requestEntity = new SubmitPaymentRequest();
        $this->populatePaymentRequestEntity($requestEntity);
        $requestEntity->setPaymentData([
            'birthdate' => $requestEntity->getBirthdate()
                ? $requestEntity->getBirthdate()->format('Y-m-d')
                : null,
            'conditionsAccepted' => null,
            'riskSessionId' => null,
            'frontendFinishUrl' => $this->generateCallbackUrl(
                'finish',
                [],
                false
            ),
            'frontendCancelUrl' => $this->generateCallbackUrl(
                'cancel',
                [],
                false
            ),
        ]);

        return $requestEntity;
    }

    /**
     * @return CreatePaymentRequest
     * @throws oxSystemComponentException
     */
    private function getCreatePaymentRequestEntity()
    {
        return $this->populatePaymentRequestEntity(new CreatePaymentRequest());
    }

    /**
     * @param RequestEntity|SubmitPaymentRequest|CreatePaymentRequest $requestEntity
     * @return RequestEntity
     * @throws UnexpectedValueException
     * @throws oxSystemComponentException
     */
    private function populatePaymentRequestEntity(RequestEntity $requestEntity)
    {
        $this->getLogger()->info('Collecting order data for create payment call...');
        $oBasket = $this->getCart();
        $oUser = $oBasket->getBasketUser();
        $basketItems = [];
        /** @var oxbasketitem $item */
        foreach ($oBasket->getContents() as $item) {
            $basketItems[] = [
                'name' => $item->getTitle(),
                'price' => $item->getUnitPrice()->getPrice(),
                'priceNetto' => $item->getUnitPrice()->getNettoPrice(),
                'VatRate' => $item->getUnitPrice()->getVat(),
                'quantity' => $item->getAmount(),
                'thumbnail' => $item->getIconUrl(),
                'url' => $item->getLink(),
            ];
        }

        if (!count($basketItems)) {
            throw new UnexpectedValueException('Basket is empty');
        }
        $oSession = $this->getSession();
        $soxAddressId = $oSession->getVariable('deladrid');
        $deliveryAddress = $this->getAddressFactory()->create();
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
            if ($oUser->oxuser__oxcountryid) {
                $oxCountry = $this->getCountryFactory()->create();
                $oxCountry->load($oUser->oxuser__oxcountryid->value);
                $deliveryAddress->oxaddress__oxcountryid = $oxCountry->oxcountry__oxisoalpha2;
            }
        }

        /**
         * cut off the plugin prefix {@see PayeverConfig::PLUGIN_PREFIX}
         */
        $apiMethod = substr($oBasket->getPaymentId(), strlen(PayeverConfig::PLUGIN_PREFIX));
        $discounts = count($oBasket->getVouchers()) ? $oBasket->getVouchers() : 0;
        if ($discounts) {
            /** @var oxvoucher $discount */
            foreach ($discounts as $discount) {
                $basketItems[] = [
                    'name' => 'Discount',
                    'price' => $discount->dVoucherdiscount * (-1),
                    'quantity' => 1,
                ];
            }
        }
        if (strpos($apiMethod, '-') && $this->getPaymentMethod()->oxpayments__oxvariants) {
            $oxvariants = json_decode($this->getPaymentMethod()->oxpayments__oxvariants->rawValue, true);
            $requestEntity
                ->setPaymentMethod($oxvariants['paymentMethod'])
                ->setVariantId($oxvariants['variantId']);
        } else {
            $requestEntity
                ->setPaymentMethod($apiMethod);
        }
        $requestEntity
            ->setAmount($this->getOrderHelper()->getAmountByCart($oBasket))
            ->setFee($this->getOrderHelper()->getFeeByCart($oBasket))
            ->setOrderId($oUser->getBasket('savedbasket')->getId())
            ->setCurrency($oBasket->getBasketCurrency()->name)
            ->setFirstName($deliveryAddress->oxaddress__oxfname->value)
            ->setLastName($deliveryAddress->oxaddress__oxlname->value)
            ->setPhone($deliveryAddress->oxaddress__oxfon->value)
            ->setEmail($oUser->oxuser__oxusername ? $oUser->oxuser__oxusername->value : null)
            ->setStreet(
                $deliveryAddress->oxaddress__oxstreet->value . ' ' . $deliveryAddress->oxaddress__oxstreetnr->value
            )
            ->setCity($deliveryAddress->oxaddress__oxcity->value)
            ->setCountry($deliveryAddress->oxaddress__oxcountryid->value)
            ->setZip($deliveryAddress->oxaddress__oxzip->value)
            ->setSuccessUrl($this->generateCallbackUrl('success'))
            ->setPendingUrl($this->generateCallbackUrl('success', ['is_pending' => true]))
            ->setCancelUrl($this->generateCallbackUrl('cancel'))
            ->setFailureUrl($this->generateCallbackUrl('failure'))
            ->setNoticeUrl($this->generateCallbackUrl('notice'))
            ->setPluginVersion($this->getConfigHelper()->getPluginVersion())
            ->setCart($basketItems);
        $birthdate = null;
        if ($deliveryAddress->getUser()->oxuser__oxbirthdate) {
            $birthdate = $deliveryAddress->getUser()->oxuser__oxbirthdate->value;
        }
        if (!empty($birthdate) && $birthdate != '0000-00-00') {
            $requestEntity->setBirthdate($birthdate);
        }
        $salutation = null;
        if ($oUser->oxuser__oxsal) {
            $salutation = $this->getSalutation($oUser->oxuser__oxsal->value);
        }
        if ($salutation) {
            $requestEntity->setSalutation($salutation);
        }
        $this->getLogger()
            ->info('Finished collecting order data for create payment call', $requestEntity->toArray());

        return $requestEntity;
    }

    /**
     * @param string $salutation
     * @return string
     */
    private function getSalutation($salutation)
    {
        $salutation = strtolower($salutation);

        return ($salutation == self::MR_SALUTATION
            || $salutation == self::MRS_SALUTATION
            || $salutation == self::MS_SALUTATION) ? $salutation : false;
    }

    /**
     * @return string
     */
    public function redirectToThankYou()
    {
        if ($this->isIframePayeverPayment()) {
            $back_link = $this->getConfig()->getSslShopUrl() . "?cl=thankyou";
            $js = <<<JS
function iframeredirect(){ window.top.location = "$back_link"; } iframeredirect();
JS;
            $script = "<script>$js</script>";
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                echo $script;
                exit;
            }
            // @codeCoverageIgnoreEnd
        }

        return 'thankyou';
    }

    /**
     * @param array|string $errors
     */
    public function addErrorToDisplay($errors)
    {
        $this->getSession()->deleteVariable('Errors');
        $oEx = new oxException();
        if (is_array($errors)) {
            $errors = array_unique($errors);
            foreach ($errors as $_error) {
                $oEx->setMessage($_error);
            }
        } else {
            $oEx->setMessage($errors);
        }

        $this->getViewUtil()->addErrorToDisplay($oEx);
    }

    /**
     * Generating callback url.
     *
     * @param string $status
     * @param array $params
     * @param bool $appendPlaceholders
     *
     * @return string
     */
    private function generateCallbackUrl($status, $params = [], $appendPlaceholders = true)
    {
        $shopUrl = $this->getConfig()->getSslShopUrl();
        $urlData = [
            'cl' => 'payeverStandardDispatcher',
            'fnc' => 'payeverGatewayReturn',
            self::STATUS_PARAM => $status,
            'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5')
        ];
        if ($appendPlaceholders) {
            $urlData['payment_id'] = '--PAYMENT-ID--';
        }

        $urlData = array_merge($urlData, $params);

        $shopUrl .= '?' . http_build_query($urlData, "", "&");

        return $shopUrl;
    }

    /**
     * Checks mode.
     *
     * @return bool
     */
    private function isIframePayeverPayment()
    {
        return !$this->getConfigHelper()->getIsRedirect()
            && !$this->getSession()->getVariable(self::SESS_IS_REDIRECT_METHOD);
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
        if (!$paymentId && $this->getSession()->getVariable(self::SESS_IS_REDIRECT_METHOD)) {
            $paymentId = $this->getSession()->getVariable(self::SESS_PAYMENT_ID);
        }
        $sts = $config->getRequestParameter(static::STATUS_PARAM);

        $this->getLogger()->info(sprintf('Handling callback type: %s, paymentId: %s', $sts, $paymentId), $_GET);

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

        $this->getLogger()->info('Waiting for lock', $payment);

        $this->getLocker()->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);

        $this->getLogger()->info('Locked', $payment);

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
                    $shouldRejectNotification = $this->shouldRejectNotification($order, $notificationTimestamp);
                    if ($payment['paymentSts'] == 'notice' && $shouldRejectNotification) {
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

                $this->getLocker()->releaseLock($payment['paymentId']);

                $this->getLogger()->info(
                    sprintf(
                        'Payment unlocked. Order has been updated: %s, status: %s',
                        $order->getId(),
                        $oxidOrderStatus
                    ),
                    $payment
                );
                // @codeCoverageIgnoreStart
                if (!$this->dryRun) {
                    if ($payment['paymentSts'] == 'notice') {
                        echo json_encode(['result' => 'success', 'message' => 'Order is updated']);
                    } else {
                        $this->webRedirect(
                            $this->getConfig()->getSslShopUrl()
                            . '?cl=payeverStandardDispatcher&fnc=redirectToThankYou'
                        ); // exit
                    }
                    exit();
                } else {
                    return;
                }
                // @codeCoverageIgnoreEnd
            }

            if ($isPaid) {
                if ($isPending) {
                    $this->addErrorToDisplay('Thank you, your order has been received. You will receive an update once your request has been processed.');
                }

                $orderStateId = $this->processSuccess($payment, $oxidOrderStatus, $isPending); // exit

                $this->getLocker()->releaseLock($payment['paymentId']);

                $orderAction = $orderStateId == oxOrder::ORDER_STATE_OK ? 'created' : 'updated';
                $this->getLogger()->info(
                    sprintf('Payment unlocked. Order has been %s, status: %s', $orderAction, $oxidOrderStatus),
                    $payment
                );
                // @codeCoverageIgnoreStart
                if (!$this->dryRun) {
                    if ($payment['paymentSts'] == 'notice') {
                        $this->getSession()->deleteVariable('sess_challenge');
                        echo json_encode(['result' => 'success', 'message' => 'Order is ' . $orderAction]);
                    } else {
                        $sUrl = $this->getConfig()
                                ->getSslShopUrl() . '?cl=payeverStandardDispatcher&fnc=redirectToThankYou';
                        $this->webRedirect($sUrl);
                    }
                    exit();
                } else {
                    return;
                }
                // @codeCoverageIgnoreEnd
            }

            /**
             * If we got here - payment wasn't successful
             */
            $paymentMethod = $retrievePaymentResult->getPaymentType();

            $this->getMethodHider()->processFailedMethod($paymentMethod);

            $message = strpos($paymentMethod, 'santander') !== false
                ? 'Unfortunately, the application was not successful. Please choose another payment option to pay for your order.'
                : 'The payment was not successful. Please try again or choose another payment option.';

            throw new oxException($message, 1);
        } catch (Exception $exception) {
            $this->getLogger()->error(
                sprintf('Payment unlocked by exception: %s', $exception->getMessage()),
                $payment
            );

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
        $iOrderId = $this->getDatabase()->GetOne(
            'SELECT oxid FROM oxorder WHERE oxtransid = ? AND oxordernr > 0 LIMIT 1',
            [$paymentId]
        );
        if ($iOrderId) {
            $oOrder = $this->getOrderFactory()->create();
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
            $this->getLogger()->alert(sprintf('NOT FOUND user for basketId "%s"', $payment['basketId']));
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
            $this->getLogger()->alert(sprintf('Got empty basket basketId "%s"', $payment['basketId']));
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
     * @param bool $badRequest
     *
     * @return void
     */
    private function rejectPayment($payment, $badRequest = true)
    {
        $this->getLocker()->releaseLock($payment['paymentId']);

        if ($badRequest) {
            !$this->dryRun && header('HTTP/1.1 400 BAD REQUEST');
        }

        $this->getLogger()->info('Rejecting payment', $payment);
        // @codeCoverageIgnoreStart
        if (!$this->dryRun) {
            if ($payment['paymentSts'] == 'notice') {
                echo json_encode(['result' => 'error', 'message' => $payment['errorMessage']]);
            } else {
                $this->processCancel($payment['errorMessage']);
            }
            exit();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $message
     *
     * @return void
     */
    private function processCancel($message = '')
    {
        $message = $message ?: 'Payment was cancelled';

        $this->getLogger()->info(sprintf('Canceling payment with message: %s', $message));

        $this->addErrorToDisplay($message);
        $this->redirectToCart();
    }

    /**
     * @return void
     */
    private function redirectToCart()
    {
        $sUrl = $this->getConfig()->getSslShopUrl() . '?cl=payment';
        !$this->dryRun && $this->webRedirect($sUrl);
    }

    /**
     * @param string $url
     *
     * @return void
     * @codeCoverageIgnore
     */
    private function webRedirect($url)
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
     * @param oxOrder $order
     * @param int $notificationTimestamp
     * @return bool
     */
    private function shouldRejectNotification($order, $notificationTimestamp)
    {
        return (int) $order->oxorder__payever_notification_timestamp->rawValue >= $notificationTimestamp;
    }

    /**
     * Executing plugin commands
     *
     * @throws Exception
     */
    public function executePluginCommands()
    {
        try {
            $this->getPluginsApiClient()->registerPlugin();
            $this->getPluginCommandManager()
                ->executePluginCommands($this->getConfigHelper()->getPluginCommandTimestamt());
            $this->getConfig()->saveShopConfVar(
                'arr',
                PayeverConfig::VAR_PLUGIN_COMMANDS,
                [PayeverConfig::KEY_PLUGIN_COMMAND_TIMESTAMP => time()]
            );
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                echo json_encode(['result' => 'success', 'message' => 'Plugin commands have been executed']);
            }
            // @codeCoverageIgnoreEnd
        } catch (\oxException $exception) {
            $message = sprintf('Plugin command execution failed: %s', $exception->getMessage());
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                echo json_encode(['result' => 'failed', 'message' => $message]);
            }
            // @codeCoverageIgnoreEnd
            $this->getLogger()->warning($message);
        }
        !$this->dryRun && exit();
    }

    /**
     * @param array $payment
     * @return RetrievePaymentResultEntity
     * @throws Exception
     */
    private function getRetrievePaymentResultEntity($payment)
    {
        $paymentId = $payment['paymentId'];
        $signature = $this->getRequestHelper()->getHeader(self::HEADER_SIGNATURE);
        if (null === $signature) {
            $retrievePaymentResponse = $this->getPaymentsApiClient()->retrievePaymentRequest($paymentId);
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
        $rawContent = $this->getRequestHelper()->getRequestContent();
        if ($rawContent) {
            $rawData = json_decode($rawContent, true);
            $data = $rawData ?: [];
        }

        return $data;
    }

    /**
     * @return oxbasket
     */
    private function getCart()
    {
        if (null === $this->cart) {
            $this->cart = $this->getSession()->getBasket();
            // to be compatible with oxid v4.7.5, let's check if method exists
            method_exists($this->cart, 'enableSaveToDataBase') && $this->cart->enableSaveToDataBase();
            $this->cart->calculateBasket(true);
        }

        return $this->cart;
    }

    /**
     * @return oxpayment
     * @throws oxSystemComponentException
     */
    private function getPaymentMethod()
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->getPaymentMethodFactory()->create();
            $this->paymentMethod->load($this->getCart()->getPaymentId());
        }

        return $this->paymentMethod;
    }

    /**
     * @return FileLock|LockInterface
     * @codeCoverageIgnore
     */
    protected function getLocker()
    {
        return null === $this->locker
            ? $this->locker = new FileLock(rtrim(oxRegistry::getConfig()->getLogsDir(), '/'))
            : $this->locker;
    }

    /**
     * @param LockInterface $locker
     * @return $this
     * @internal
     */
    public function setLocker(LockInterface $locker)
    {
        $this->locker = $locker;

        return $this;
    }

    /**
     * @return oxutils
     * @codeCoverageIgnore
     */
    protected function getUtils()
    {
        return null === $this->utils
            ? $this->utils = oxRegistry::getUtils()
            : $this->utils;
    }

    /**
     * @param oxutils $utils
     * @return $this
     * @internal
     */
    public function setUtils($utils)
    {
        $this->utils = $utils;

        return $this;
    }

    /**
     * @return oxutilsurl
     * @codeCoverageIgnore
     */
    protected function getUrlUtil()
    {
        return null === $this->urlUtil
            ? $this->urlUtil = oxRegistry::get('oxutilsurl')
            : $this->urlUtil;
    }

    /**
     * @param oxutilsurl $urlUtil
     * @return $this
     * @internal
     */
    public function setUrlUtil($urlUtil)
    {
        $this->urlUtil = $urlUtil;

        return $this;
    }

    /**
     * @return object|oxutilsview
     * @codeCoverageIgnore
     */
    protected function getViewUtil()
    {
        return null === $this->viewUtil
            ? $this->viewUtil = oxRegistry::get('oxutilsview')
            : $this->viewUtil;

    }

    /**
     * @param oxutilsview $viewUtil
     * @return $this
     * @internal
     */
    public function setViewUtil($viewUtil)
    {
        $this->viewUtil = $viewUtil;

        return $this;
    }

    /**
     * @return PluginsApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getPluginsApiClient()
    {
        return null === $this->pluginsApiClient
            ? $this->pluginsApiClient = PayeverApiClientProvider::getPluginsApiClient()
            : $this->pluginsApiClient;
    }

    /**
     * @param PluginsApiClient $pluginsApiClient
     * @return $this
     * @internal
     */
    public function setPluginsApiClient($pluginsApiClient)
    {
        $this->pluginsApiClient = $pluginsApiClient;

        return $this;
    }

    /**
     * @return PluginCommandManager
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getPluginCommandManager()
    {
        return null === $this->pluginCommandManager
            ? $this->pluginCommandManager = PayeverApiClientProvider::getPluginCommandManager()
            : $this->pluginCommandManager;
    }

    /**
     * @param PluginCommandManager $pluginCommandManager
     * @return $this
     * @internal
     */
    public function setPluginCommandManager($pluginCommandManager)
    {
        $this->pluginCommandManager = $pluginCommandManager;

        return $this;
    }
}
