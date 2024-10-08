<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Http\RequestEntity;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Core\Lock\FileLock;
use Payever\Sdk\Core\Lock\LockInterface;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV2Request;
use Payever\Sdk\Payments\Http\ResponseEntity\CreatePaymentV2Response;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\Sdk\Payments\Http\RequestEntity\NotificationRequestEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationActionResultEntity;
use Payever\Sdk\Plugins\Command\PluginCommandManager;
use Payever\Sdk\Plugins\PluginsApiClient;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
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
    use PayeverFieldFactoryTrait;
    use PayeverPaymentActionHelperTrait;

    const STATUS_PARAM = 'sts';

    const LANG_PARAM = 'lang';

    const DEFAULT_LANG = 1;

    const LOCK_WAIT_SECONDS = 30;

    const MR_SALUTATION = 'mr';
    const MRS_SALUTATION = 'mrs';
    const MS_SALUTATION = 'ms';

    const HEADER_SIGNATURE = 'X-PAYEVER-SIGNATURE';

    const SESS_IS_REDIRECT_METHOD = 'payever_is_redirect_method';

    const SESS_TEMP_BASKET = 'payever_temp_basket';

    const ACTION_EXTERNAL_SOURCE = 'external';

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

    /** @var oxbasket  */
    private $oxBasket;

    /** @var payeverOxOrder  */
    private $oxOrder;

    /** @var oxuser  */
    private $oxUser;

    /** @var oxuserpayment  */
    private $oxUserPayment;

    /** @var PayeverOrderActionHelper  */
    private $payeverOrderActionHelper;

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
     *
     */
    public function getRedirectUrl()
    {
        try {
            // v3 API
            if ($this->getConfigHelper()->isApiV3()) {
                $requestBuilder = new PayeverPaymentV3RequestBuilder();

                return $requestBuilder->generatePaymentUrl();
            }

            // v2 API
            $isRedirectMethod = (bool) $this->getPaymentMethod()->getFieldData('oxisredirectmethod');
            $paymentRequestEntity = $this->getCreatePaymentV2RequestEntity();
            $paymentRequestEntity->setPaymentData([
                'force_redirect' => $isRedirectMethod,
            ]);
            $response = $this->getPaymentsApiClient()->createPaymentV2Request($paymentRequestEntity);
            /** @var CreatePaymentV2Response $responseEntity */
            $responseEntity = $response->getResponseEntity();
            $redirectUrl = $responseEntity->getRedirectUrl();
            $this->getLogger()->info('Payment successfully created', $responseEntity->toArray());

            return $redirectUrl;
        } catch (Exception $exception) {
            $this->getLogger()->error(sprintf('Error while creating payment: %s', $exception->getMessage()));
            $this->addErrorToDisplay($exception->getMessage());

            return false;
        }
    }

    /**
     * @return CreatePaymentV2Request
     * @throws oxSystemComponentException
     */
    private function getCreatePaymentV2RequestEntity()
    {
        return $this->populatePaymentRequestEntity(new CreatePaymentV2Request());
    }

    /**
     * @param RequestEntity|CreatePaymentV2Request $requestEntity
     * @return RequestEntity
     * @throws UnexpectedValueException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
                'sku' => preg_replace('#[^0-9a-z_]+#i', '-', $item->getArticle()->getFieldData('oxartnum')),
                'price' => $item->getUnitPrice()->getPrice(),
                'priceNetto' => $item->getUnitPrice()->getNettoPrice(),
                'VatRate' => $item->getUnitPrice()->getVat(),
                'quantity' => $item->getAmount(),
                'thumbnail' => $item->getIconUrl(),
                'url' => $item->getLink(),
                'identifier' => $item->getProductId(),
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
            $deliveryAddress->oxaddress__oxsal = $oUser->oxuser__oxsal;
            $deliveryAddress->oxaddress__oxfname = $oUser->oxuser__oxfname;
            $deliveryAddress->oxaddress__oxlname = $oUser->oxuser__oxlname;
            $deliveryAddress->oxaddress__oxstreet = $oUser->oxuser__oxstreet;
            $deliveryAddress->oxaddress__oxstreetnr = $oUser->oxuser__oxstreetnr;
            $deliveryAddress->oxaddress__oxzip = $oUser->oxuser__oxzip;
            $deliveryAddress->oxaddress__oxcity = $oUser->oxuser__oxcity;
            $deliveryAddress->oxaddress__oxfon = $oUser->oxuser__oxfon;
            if ($oUser->oxuser__oxcountryid) {
                $deliveryAddress->oxaddress__oxcountryid = $oUser->oxuser__oxcountryid;
            }
        }

        $shippingAddressEntity = $this->populateAddressEntity($deliveryAddress);
        $billingAddressEntity = $this->populateAddressEntityByUser($oUser);
        $oxCountry = $this->getCountryFactory()->create();
        $oxCountry->load($oUser->oxuser__oxcountryid->value);

        /**
         * cut off the plugin prefix {@see PayeverConfig::PLUGIN_PREFIX}
         */
        $apiMethod = substr($oBasket->getPaymentId(), strlen(PayeverConfig::PLUGIN_PREFIX));

        // Calculate discount
        $discount = 0;
        $vouchers = count($oBasket->getVouchers()) ? $oBasket->getVouchers() : 0;
        if ($vouchers) {
            /** @var oxvoucher $voucher */
            foreach ($vouchers as $voucher) {
                $basketItems[] = [
                    'name' => 'Discount',
                    'price' => $voucher->dVoucherdiscount * (-1),
                    'quantity' => 1,
                    'identifier' => 'discount'
                ];

                $discount += $voucher->dVoucherdiscount;
            }
        }

        if ($oBasket->getGiftCardCost()) {
            $basketItems[] = [
                'name' => 'Greeting Card',
                'price' => $oBasket->getGiftCardCost()->getBruttoPrice(),
                'quantity' => 1,
                'identifier' => payeverorderaction::TYPE_GIFTCARD_COST,
            ];
        }

        if ($oBasket->getWrappingCost()) {
            $basketItems[] = [
                'name' => 'Gift Wrapping',
                'price' => $oBasket->getWrappingCost()->getBruttoPrice(),
                'quantity' => 1,
                'identifier' => payeverorderaction::TYPE_WRAP_COST,
            ];
        }

        // Save discount in session
        if (!$this->dryRun) {
            $oSession->oxidpayever_discount = $discount;
            $oSession->setVariable('oxidpayever_discount', $discount);
            $oSession->setVariable(self::SESS_TEMP_BASKET, serialize($oBasket));
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

        $language = $this->getConfigHelper()->getLanguage()
            ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if (\PayeverConfig::LOCALE_STORE_VALUE === $language) {
            $language = \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageAbbr();
        }

        $requestEntity
            ->setAmount($this->getOrderHelper()->getAmountByCart($oBasket))
            ->setFee($this->getOrderHelper()->getFeeByCart($oBasket))
            ->setOrderId($this->getSavedBasketId())
            ->setCurrency($oBasket->getBasketCurrency()->name)
            ->setPhone($oUser->oxuser__oxfon->value)
            ->setEmail($oUser->oxuser__oxusername ? $oUser->oxuser__oxusername->value : null)
            ->setLocale($language)
            ->setSuccessUrl($this->generateCallbackUrl('success'))
            ->setPendingUrl($this->generateCallbackUrl('pending'))
            ->setCancelUrl($this->generateCallbackUrl('cancel'))
            ->setFailureUrl($this->generateCallbackUrl('failure'))
            ->setNoticeUrl($this->generateCallbackUrl('notice'))
            ->setCart($basketItems);

        $birthdate = null;
        if ($oUser->oxuser__oxbirthdate) {
            $birthdate = $oUser->oxuser__oxbirthdate->value;
        }
        if (!empty($birthdate) && $birthdate != '0000-00-00') {
            $requestEntity->setBirthdate($birthdate);
        }

        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setName(ChannelSet::CHANNEL_OXID)
            ->setSource($this->getConfigHelper()->getPluginVersion());
        $requestEntity
            ->setChannel($channelEntity)
            ->setShippingAddress($shippingAddressEntity)
            ->setBillingAddress($billingAddressEntity);

        $this->getLogger()
            ->info('Finished collecting order data for create payment call', $requestEntity->toArray());

        return $requestEntity;
    }

    /**
     * @param $deliveryAddress
     *
     * @return CustomerAddressEntity
     */
    private function populateAddressEntity($deliveryAddress)
    {
        $oxCountry = $this->getCountryFactory()->create();
        $oxCountry->load($deliveryAddress->oxaddress__oxcountryid->value);
        $oxcountry = $oxCountry->oxcountry__oxisoalpha2->value;

        $customerAddressEntity = new CustomerAddressEntity();
        $customerAddressEntity
            ->setFirstName($deliveryAddress->oxaddress__oxfname->value)
            ->setLastName($deliveryAddress->oxaddress__oxlname->value)
            ->setCity($deliveryAddress->oxaddress__oxcity->value)
            ->setZip($deliveryAddress->oxaddress__oxzip->value)
            ->setStreet(
                $deliveryAddress->oxaddress__oxstreet->value . ' ' . $deliveryAddress->oxaddress__oxstreetnr->value
            )
            ->setCountry($oxcountry);

        $salutation = null;
        if ($deliveryAddress->oxaddress__oxsal) {
            $salutation = $this->getSalutation($deliveryAddress->oxaddress__oxsal->value);
        }
        if ($salutation) {
            $customerAddressEntity->setSalutation($salutation);
        }

        return $customerAddressEntity;
    }

    /**
     * @param $oUser
     *
     * @return CustomerAddressEntity
     */
    private function populateAddressEntityByUser($oUser)
    {
        $oxCountry = $this->getCountryFactory()->create();
        $oxCountry->load($oUser->oxuser__oxcountryid->value);
        $oxcountry = $oxCountry->oxcountry__oxisoalpha2->value;

        $customerAddressEntity = new CustomerAddressEntity();
        $customerAddressEntity
            ->setFirstName($oUser->oxuser__oxfname->value)
            ->setLastName($oUser->oxuser__oxlname->value)
            ->setCity($oUser->oxuser__oxcity->value)
            ->setZip($oUser->oxuser__oxzip->value)
            ->setStreet(
                $oUser->oxuser__oxstreet->value . ' ' . $oUser->oxuser__oxstreetnr->value
            )
            ->setCountry($oxcountry);

        $salutation = null;
        if ($oUser->oxuser__oxsal) {
            $salutation = $this->getSalutation($oUser->oxuser__oxsal->value);
        }

        if ($salutation) {
            $customerAddressEntity->setSalutation($salutation);
        }

        return $customerAddressEntity;
    }

    /**
     * @param string $salutation
     * @return string
     */
    private function getSalutation($salutation)
    {
        if (!$salutation) {
            return false;
        }

        $salutation = strtolower($salutation);

        return ($salutation == self::MR_SALUTATION
            || $salutation == self::MRS_SALUTATION
            || $salutation == self::MS_SALUTATION) ? $salutation : false;
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function redirectToThankYou()
    {
        $fetchDest = $this->getRequestHelper()->getHeader('sec-fetch-dest');
        $this->getLogger()->debug(
            'Hit redirectToThankYou with fetch dest ' . $fetchDest,
            ['sess_challenge' => $this->getSession()->getVariable('sess_challenge')]
        );
        if ($this->isIframePayeverPayment()) {
            $back_link = $this->getConfig()->getSslShopUrl() . '?cl=thankyou';
            $script = <<<JS
<script>
function iframeredirect(){ window.top.location = "$back_link"; } iframeredirect();
</script>
JS;
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                if ($fetchDest != 'iframe') {
                    echo $script;
                }
                exit;
            }
            // @codeCoverageIgnoreEnd
        }

        return 'thankyou';
    }

    /**
     * @param array|string $errors
     * @SuppressWarnings(PHPMD.ElseExpression)
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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function generateCallbackUrl($status, $params = [], $appendPlaceholders = true)
    {
        $shopUrl = $this->getConfig()->getSslShopUrl();
        $urlData = [
            'cl' => 'payeverStandardDispatcher',
            'fnc' => 'payeverGatewayReturn',
            self::STATUS_PARAM => $status,
            'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5'),
            self::LANG_PARAM => oxRegistry::getLang()->getTplLanguage()
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
            && !$this->getSession()->getVariable(PayeverConfig::SESS_IS_REDIRECT_METHOD);
    }

    /**
     * Main entry point for all payever callbacks & notifications
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function payeverGatewayReturn()
    {
        $config = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        if (!$paymentId) {
            $baskedOxid = $config->getRequestParameter('basketoxid');
            $token = $config->getRequestParameter('token');
            $message = 'Token is invalid';
            if ($this->getConfigHelper()->getHash($baskedOxid) === $token) {
                $message = 'Tokens are matched';
                $paymentId = $this->getDatabase()->getOne(
                    'SELECT oxpayeverpid FROM oxuserbaskets WHERE OXID = ?',
                    [$baskedOxid]
                );
            }
            $this->getLogger()->debug($message);
        }
        $sts = $config->getRequestParameter(PayeverPaymentUrlBuilder::STATUS_PARAM);

        $this->getLogger()->info(sprintf('Handling callback type: %s, paymentId: %s', $sts, $paymentId), $_GET);
        $fetchDest = $this->getRequestHelper()->getHeader('sec-fetch-dest');
        $this->getLogger()->debug(sprintf('Hit with fetch dest: %s', $fetchDest));

        if ($sts == 'cancel') {
            $lang = $config->getRequestParameter(static::LANG_PARAM) ?: oxRegistry::getLang()->getTplLanguage();
            if (!$lang) {
                $lang = self::DEFAULT_LANG;
            }
            $message = oxRegistry::getLang()->translateString(
                'payeverPaymentCancel',
                $lang,
                false
            );
            if ($message === 'payeverPaymentCancel') {
                $message = 'The payment was cancelled. Please try again or choose another payment option.';
            }
            $this->redirectToPaymentPageWithError($message);
        }

        $createPendingOrder = PayeverConfig::shouldCreatePendingOrder();
        if (!$createPendingOrder && $sts == 'pending') {
            $urlData = [
                'cl' => 'payeverpaymentpending',
                'payment_id' => $paymentId,
                'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5'),
            ];

            $sUrl = $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlData);
            $this->getUtils()->redirect($sUrl, false);
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
            $payment['restoreBasketInSession'] = $fetchDest != 'iframe';
            $payeverStatus = $retrievePaymentResult->getStatus();
            if ($sts == 'failure') {
                $messageType = 'payeverPaymentFailed';
                if ($payeverStatus == Status::STATUS_DECLINED) {
                    $messageType = 'payeverPaymentDeclined';
                }
                $lang = $config->getRequestParameter(static::LANG_PARAM) ?: oxRegistry::getLang()->getTplLanguage();
                if (!$lang) {
                    $lang = self::DEFAULT_LANG;
                }
                $message = oxRegistry::getLang()->translateString(
                    $messageType,
                    $lang,
                    false
                );
                if ($message === 'payeverPaymentFailed') {
                    $message = 'The payment has failed. Please choose another payment option, or try again later.';
                }
                if ($message === 'payeverPaymentDeclined') {
                    $message = 'Unfortunately the payment was declined, please choose another payment option.';
                }
                $this->redirectToPaymentPageWithError($message);
            }
            $isNotice = $payment['paymentSts'] == 'notice';
            $this->getLogger()->debug(
                'Processing payever status',
                [
                    'payeverStatus' => $payeverStatus,
                    'isNotice' => $isNotice,
                    'sess_challenge' => $this->getSession()->getVariable('sess_challenge'),
                ]
            );
            $oxidOrderStatus = $this->getInternalStatus($payeverStatus);
            $isPending = $sts === 'pending';
            $isPaid = $this->isPaidStatus($oxidOrderStatus);

            $order = $this->getOrderByPaymentId($paymentId);
            if ($order) {
                if ($isNotice && $payeverStatus === Status::STATUS_NEW) {
                    $this->getLogger()->info('Notification processing is skipped; reason: Stalled new status');
                    // @codeCoverageIgnoreStart
                    if (!$this->dryRun) {
                        echo json_encode(['result' => 'success', 'message' => 'Skipped stalled new status']);
                        exit();
                    } else {
                        return;
                    }
                    // @codeCoverageIgnoreEnd
                }
                $notificationTimestamp = 0;
                $rawData = $this->getRawData();
                if ($rawData) {
                    if (isset($rawData['data']['action'])) {
                        $processedNotice = $this->getPaymentActionHelper()->isActionExists(
                            $order->getId(),
                            $rawData['data']['action']['unique_identifier'],
                            $rawData['data']['action']['source']
                        );

                        if ($processedNotice) {
                            $payment['errorMessage'] = 'Notification rejected: notification already processed';
                            return $this->rejectPayment($payment, false);
                        }
                    }

                    $notificationTimestamp = !empty($rawData['created_at'])
                        ? strtotime($rawData['created_at'])
                        : $notificationTimestamp;
                    $shouldRejectNotification = $this->shouldRejectNotification($order, $notificationTimestamp);
                    if ($isNotice && $shouldRejectNotification) {
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

                if ($rawData && isset($rawData['data']['action'])) {
                    $this->getPaymentActionHelper()->addAction(
                        $order->getId(),
                        $rawData['data']['action']['unique_identifier'],
                        $rawData['data']['action']['type']
                    );
                }

                $notificationRequestEntity = $this->getNotificationRequestEntity(
                    $this->getRequestHelper()->getRequestContent()
                );
                $notificationPayment = $notificationRequestEntity->getPayment();
                /** @var null|NotificationActionResultEntity $action */
                $action = $notificationRequestEntity->getAction();
                // @codeCoverageIgnoreStart
                if (!$this->dryRun) {
                    if ($isNotice) {
                        $this->getLogger()->info('[Notification]: start processing notification...');
                        if (
                            $notificationPayment->getCaptureAmount() &&
                            empty($notificationPayment->getCapturedItems())
                        ) {
                            $this->getLogger()->info('[Notification]: Start capturing by amount...');
                            $this->processAmount(
                                $notificationPayment->toArray(),
                                $order,
                                'shipping_goods',
                                $action
                            );
                        }

                        if (
                            $notificationPayment->getRefundAmount() &&
                            empty($notificationPayment->getRefundedItems())
                        ) {
                            $this->getLogger()->info('[Notification]: Start refunding by amount ...');
                            $this->processAmount(
                                $notificationPayment->toArray(),
                                $order,
                                'refund',
                                $action
                            );
                        }

                        if ($notificationPayment->getCancelAmount()) {
                            $this->getLogger()->info('[Notification]: Start canceling by amount ...');
                            $this->processAmount(
                                $notificationPayment->toArray(),
                                $order,
                                'cancel',
                                $action
                            );
                        }

                        // processing actions by item
                        if (
                            !empty($notificationPayment->getCapturedItems()) &&
                            $notificationPayment->getCaptureAmount()
                        ) {
                            $this->getLogger()->info('[Notification]: Start capturing items ...');
                            $this->processItems(
                                $notificationPayment->getCapturedItems(),
                                $order->getId(),
                                'capture'
                            );
                        }

                        if (
                            !empty($notificationPayment->getRefundedItems()) &&
                            $notificationPayment->getRefundAmount()
                        ) {
                            $this->getLogger()->info('[Notification]: Start refunding items ...');
                            $this->processItems(
                                $notificationPayment->getRefundedItems(),
                                $order->getId(),
                                'refund'
                            );
                        }
                        echo json_encode(['result' => 'success', 'message' => 'Order is updated']);
                    } else {
                        $this->getLogger()->debug('Prepare session before redirect');
                        $this->getSession()->setVariable('sess_challenge', $order->getId());
                        $this->ensureUserAndBasketLoaded($payment);
                        $this->webRedirect(
                            $this->getConfig()->getSslShopUrl()
                            . '?cl=payeverStandardDispatcher&fnc=redirectToThankYou',
                            $fetchDest
                        ); // exit
                    }
                    exit();
                } else {
                    return;
                }
                // @codeCoverageIgnoreEnd
            } else {
                $this->getLogger()->debug('Order does not exist');
            }

            if ($isPaid) {
                $orderStateId = $this->processSuccess($payment, $oxidOrderStatus, $isPending); // exit

                $this->getLocker()->releaseLock($payment['paymentId']);

                $orderAction = $orderStateId == oxOrder::ORDER_STATE_OK ? 'created' : 'updated';
                $this->getLogger()->info(
                    sprintf('Payment unlocked. Order has been %s, status: %s', $orderAction, $oxidOrderStatus),
                    $payment
                );
                // @codeCoverageIgnoreStart
                if (!$this->dryRun) {
                    if ($isNotice) {
                        $payeverStatus !== Status::STATUS_NEW && $this->getSession()->deleteVariable('sess_challenge');
                        echo json_encode(['result' => 'success', 'message' => 'Order is ' . $orderAction]);
                    } else {
                        $sUrl = $this->getConfig()->getSslShopUrl();
                        $sUrl .= '?cl=payeverStandardDispatcher&fnc=redirectToThankYou';

                        if ($sts == 'pending') {
                            $urlData = [
                                'cl' => 'payeverpaymentpending',
                                'payment_id' => $paymentId,
                                'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5'),
                            ];

                            $sUrl = $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlData);
                        }

                        $this->webRedirect($sUrl, $fetchDest);
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
                ? 'Unfortunately, the application was not successful. '
                . 'Please choose another payment option to pay for your order.'
                : 'The payment was not successful. Please try again or choose another payment option.';

            throw new oxException($message, 1);
        } catch (Exception $exception) {
            $this->getLogger()->error(
                sprintf('Payment unlocked by exception: %s', $exception->getMessage()),
                $payment
            );

            $payment['errorMessage'] = $exception->getMessage();

            return $this->rejectPayment($payment, $exception->getCode() < 1);
        }
    }

    private function processItems($items, $orderId, $action)
    {
        $field = "";
        switch ($action) {
            case 'capture':
                $field = 'OXPAYEVERSHIPPED';
                break;
            case 'refund':
                $field = 'OXPAYEVERREFUNDED';
                break;
            case 'cancel':
                $field = 'OXPAYEVERCANCELLED';
                break;
            default:
                throw new InvalidArgumentException(
                    '[Notification]: action not defined: ' . $action
                );
        }
        $dbConnection = $this->getDatabase();
        foreach ($items as $item) {
            $qty = $item['quantity'];
            $identifier = $item['identifier'];
            $sql = "UPDATE oxorderarticles SET `{$field}` = {$qty} 
                       WHERE OXORDERID = '{$orderId}' AND OXARTID = '{$identifier}'";
            $dbConnection->execute($sql);
        }
    }

    /**
     * @param $paymentData
     * @param $order
     * @param $action
     * @param null|NotificationActionResultEntity $notificationAction
     * @return void
     */
    private function processAmount($paymentData, $order, $action, $notificationAction = null)
    {
        if ($notificationAction && $notificationAction->getSource() === self::ACTION_EXTERNAL_SOURCE) {
            $this->getLogger()->info('[Notification]: external notification skipped.');

            return;
        }

        $orderId = $order->getId();
        switch ($action) {
            case 'shipping_goods':
                $amount = $paymentData['capture_amount'];
                break;
            case 'refund':
                $amount = $paymentData['refund_amount'];
                break;
            case 'cancel':
                $amount = $paymentData['cancel_amount'];
                break;
            default:
                throw new InvalidArgumentException(
                    '[Notification]: action not allowed: ' . $action
                );
        }

        // check action in history to prevent duplication
        $orderActionHelper = $this->getPayeverOrderActionHelper();
        $orderActionHelper->addAction([
            'orderId' => $orderId,
            'actionId' => $paymentData['id'],
            'amount' => $amount,
            'state' => 'success',
            'type' => 'product'
        ], $action);

        // Change order status
        $order->oxorder__oxtransstatus = $this->getFieldFactory()->createRaw(
            str_replace('STATUS_', '', $paymentData['status'])
        );
        $order->save();
        $this->getLogger()->info(
            '[Notification]: Processed amount for order. Amount: ' . $amount . ' - OrderId: ' . $orderId
        );
    }

    /**
     * @param $payeverStatus
     * @return string
     */
    protected function getInternalStatus($payeverStatus)
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
    protected function isPaidStatus($oxidStatus)
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function processSuccess($payment, $oxidOrderStatus, $isPending = false)
    {
        list($oUser, $oBasket) = $this->ensureUserAndBasketLoaded($payment);

        /** @var payeverOxOrder $oOrder */
        $oOrder = $this->getOxOrder();
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

        // Verify order totals
        $oBasket = $oOrder->getBasket();
        if ($oBasket) {
            $retrievePaymentResult = $this->getRetrievePaymentResultEntity($payment);

            // Calculate amount
            $basketAmount = $this->getOrderHelper()->getAmountByCart($oBasket);

            $oSession = $this->getSession();

            // Calculate discounts
            $discount = 0;
            $vouchers = count($oBasket->getVouchers()) ? $oBasket->getVouchers() : 0;

            if ($vouchers) {
                /** @var oxvoucher $discount */
                foreach ($vouchers as $voucher) {
                    $discount += $voucher->dVoucherdiscount;
                }
            }

            // Discount bug workaround
            $savedDiscount = $this->getSession()->getVariable('oxidpayever_discount');
            $this->getSession()->deleteVariable('oxidpayever_discount');
            if ($discount === 0 && $savedDiscount > 0) {
                $basketAmount -= $savedDiscount;

                $message = sprintf(
                    'Order: %s. Discount workaround is applied. Discount: %s. Order amount: %s',
                    $oBasket->getOrderId(),
                    $savedDiscount,
                    $basketAmount
                );

                $this->getLogger()->warning($message);
            }

            if (round($basketAmount, 2) !== round($retrievePaymentResult->getTotal(), 2)) {
                $message = sprintf(
                    'Order: %s. The amount really paid (%.2F %s) is not equal to the product amount (%.2F %s).',
                    $oBasket->getOrderId(),
                    $retrievePaymentResult->getTotal(),
                    $retrievePaymentResult->getCurrency(),
                    $basketAmount,
                    $oBasket->getBasketCurrency()->name
                );

                $oOrder->oxorder__oxfolder = $this->getFieldFactory()->createRaw('ORDERFOLDER_PROBLEMS');
                $this->getLogger()->warning($message);
            }
        }

        $userPayment = $this->getOxUserPayment();
        $userPayment->load((string) $oOrder->oxorder__oxpaymentid);
        $aParams = [
            'oxuserpayments__oxpspayever_transaction_id' => $payment['paymentId'],
            'oxuserpayments__oxpaymentsid' => $payment['paymentMethod'],
        ];
        $userPayment->assign($aParams);
        $userPayment->save();

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
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    protected function rejectPayment($payment, $badRequest = true)
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
                $this->redirectToPaymentPageWithError($payment['errorMessage']);
            }
            exit();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @codeCoverageIgnore
     */
    private function redirectToPaymentPageWithError($message)
    {
        $this->getLogger()->info(sprintf('Canceling payment with message: %s', $message));
        $this->addErrorToDisplay($message);
        $sUrl = $this->getConfig()->getSslShopUrl() . "?cl=payment";
        !$this->dryRun && $this->webRedirect($sUrl, '');
    }

    /**
     * @param string $url
     * @param string $fetchDest
     *
     * @return void
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function webRedirect($url, $fetchDest)
    {
        if ($fetchDest == 'iframe') {
            // after request with sec-fetch-desc=iframe header
            // final request with sec-fetch-desc=document header is followed
            // do not redirect to checkout success page to do not destroy session
            $this->getLogger()->info(sprintf('Ignore redirect with fetch dest: %s', $fetchDest));
        } else {
            echo "<html><head><script language=\"javascript\">
                <!--
                parent.document.location.href=\"{$url}\";
                //-->
                </script>
                </head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
        }
        exit();
    }

    /**
     * @param oxOrder $order
     * @param int $notificationTimestamp
     * @return bool
     */
    protected function shouldRejectNotification($order, $notificationTimestamp)
    {
        return (int) $order->oxorder__payever_notification_timestamp->rawValue >= $notificationTimestamp;
    }

    /**
     * Executing plugin commands
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExitExpression)
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
     * @SuppressWarnings(PHPMD.ElseExpression)
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
            if ($this->getConfigHelper()->getHash($paymentId) === $signature) {
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

    private function getNotificationRequestEntity($payload)
    {
        return new NotificationRequestEntity(json_decode($payload, true));
    }

    /**
     * @return array
     */
    protected function getRawData()
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

    /**
     * @return string|null
     */
    private function getSavedBasketId()
    {
        $oBasket = $this->getCart();
        $oUser = $oBasket->getBasketUser();

        return $oUser->getBasket('savedbasket')->getId();
    }

    /**
     * Additional check if we really really have a user now.
     *
     * @param array $payment
     * @return array
     * @throws oxException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function ensureUserAndBasketLoaded(array $payment)
    {
        $restoreBasketInSession = !empty($payment['restoreBasketInSession']) && $payment['restoreBasketInSession'];
        $oUser = $this->getUser();
        if (!$oUser) {
            $oUser = $this->getOxUser();
            $userId = $this->getUserByBasketId($payment['basketId']);
            $oUser->load($userId);
        }

        if (!$oUser) {
            $this->getLogger()->alert(sprintf('NOT FOUND user for basketId "%s"', $payment['basketId']));
            if (!$restoreBasketInSession) {
                throw new oxException('USER_NOT_FOUND');
            }
        }

        $sBasket = $this->getSession()->getVariable(self::SESS_TEMP_BASKET);
        $oBasket = unserialize($sBasket);

        if ($this->dryRun) {
            $oBasket = $this->getOxBasket();
        }

        $this->getSession()->deleteVariable(self::SESS_TEMP_BASKET);
        if (!$oBasket instanceof oxbasket) {
            // get basket contents
            /** @var oxBasket $oBasket */
            $oBasket = oxNew('oxbasket');
            $oBasket->setBasketUser($oUser);
            $oBasket->setPayment($payment['paymentMethod']);
            $oBasket->load();
            $oBasket->calculateBasket(true);
        }

        if (!$oBasket->getProductsCount()) {
            // fallback to session data, which does not work for notice callbacks
            $oSession = $this->getSession();
            $oBasket = $oSession->getBasket();
            $oUser = $oBasket->getBasketUser();
        }
        if (!$oBasket->getProductsCount()) {
            $this->getLogger()->alert(sprintf('Got empty basket basketId "%s"', $payment['basketId']));
            if (!$restoreBasketInSession) {
                throw new oxException('BASKET_EMPTY');
            }
        }
        if (!empty($payment['restoreBasketInSession']) && $payment['restoreBasketInSession']) {
            $basketName = 'basket';
            if ($this->getConfig()->getConfigParam('blMallSharedBasket') == 0) {
                $basketName = $this->getConfig()->getShopId() . '_basket';
            }
            if (!$this->getSession()->getVariable($basketName)) {
                $this->getLogger()->debug('Saved serialized basket to session');
                $this->getSession()->setBasket($oBasket);
                $this->getSession()->setVariable($basketName, serialize($oBasket));
            }
        }

        return [$oUser, $oBasket];
    }

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
     * @param oxbasket
     */
    public function setOxBasket($oxbasket)
    {
        $this->oxBasket = $oxbasket;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getOxOrder()
    {
        if ($this->oxOrder === null) {
            return oxNew('oxorder');
        }

        return $this->oxOrder;
    }

    /**
     * @param payeverOxOrder
     */
    public function setOxOrder($oxorder)
    {
        $this->oxOrder = $oxorder;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return oxuser
     */
    protected function getOxUser()
    {
        if ($this->oxUser === null) {
            return oxNew('oxuser');
        }

        return $this->oxUser;
    }

    /**
     * @param oxuser
     */
    public function setOxUser($oxuser)
    {
        $this->oxUser = $oxuser;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return oxuserpayment
     */
    protected function getOxUserPayment()
    {
        if ($this->oxUserPayment === null) {
            return oxNew('oxUserPayment');
        }

        return $this->oxUserPayment;
    }

    /**
     * @param oxuserpayment
     */
    public function setOxUserPayment($oxUserPayment)
    {
        $this->oxUserPayment = $oxUserPayment;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return PayeverOrderActionHelper
     */
    protected function getPayeverOrderActionHelper()
    {
        if ($this->payeverOrderActionHelper === null) {
            $this->payeverOrderActionHelper = new PayeverOrderActionHelper();
        }

        return $this->payeverOrderActionHelper;
    }

    /**
     * @param PayeverOrderActionHelper
     */
    public function setPayeverOrderActionHelper($payeverOrderActionHelper)
    {
        $this->payeverOrderActionHelper = $payeverOrderActionHelper;

        return $this;
    }
}
