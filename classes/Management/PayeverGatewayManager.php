<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Enum\Status;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\Sdk\Payments\Http\RequestEntity\NotificationRequestEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationActionResultEntity;
use Payever\Sdk\Payments\Notification\MessageEntity\NotificationResultEntity;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PayeverGatewayManager
{
    use DryRunTrait;
    use PayeverConfigTrait;
    use PayeverConfigHelperTrait;
    use PayeverDatabaseTrait;
    use PayeverLoggerTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverRequestHelperTrait;
    use PayeverOrderFactoryTrait;
    use PayeverPaymentActionHelperTrait;
    use PayeverFieldFactoryTrait;
    use PayeverMethodHiderTrait;
    use PayeverOrderHelperTrait;
    use PayeverFileLockTrait;
    use PayeverDisplayHelperTrait;
    use PayeverSessionTrait;
    use PayeverOrderActionHelperTrait;
    use PayeverOxUserPaymentTrait;
    use PayeverOxBasketTrait;
    use PayeverOxOrderTrait;
    use PayeverOxUserTrait;
    use PayeverUtilsTrait;

    const DEFAULT_LANG = 1;

    const LOCK_WAIT_SECONDS = 30;

    const HEADER_SIGNATURE = 'X-PAYEVER-SIGNATURE';

    const SESS_TEMP_BASKET = 'payever_temp_basket';

    const ACTION_EXTERNAL_SOURCE = 'external';

    /**
     * Main entry point for all payever callbacks & notifications
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws oxConnectionException
     */
    public function processGatewayReturn()
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
            $lang = $config->getRequestParameter(PayeverPaymentUrlBuilder::LANG_PARAM)
                ?: oxRegistry::getLang()->getTplLanguage();
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
                $lang = $config->getRequestParameter(PayeverPaymentUrlBuilder::LANG_PARAM) ?:
                    oxRegistry::getLang()->getTplLanguage();
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
                            $this->rejectPayment($payment, false);
                            return;
                        }
                    }

                    $notificationTimestamp = !empty($rawData['created_at'])
                        ? strtotime($rawData['created_at'])
                        : $notificationTimestamp;
                    $shouldRejectNotification = $this->shouldRejectNotification($order, $notificationTimestamp);
                    if ($isNotice && $shouldRejectNotification) {
                        $payment['errorMessage'] = 'Notification rejected: newer notification already processed';

                        $this->rejectPayment($payment, false);
                        return;
                    }

                    if ($isNotice && !$isPending && $this->shouldRejectIfExpiredStatus($retrievePaymentResult)) {
                        $payment['errorMessage'] = 'Notification rejected: Order status has not been updated. Expired status.'; //phpcs:ignore

                        $this->rejectPayment($payment, false);
                        return;
                    }

                    $notificationResultEntity = new NotificationResultEntity($rawData['data']['payment']);
                    $company = $notificationResultEntity->getCompany();
                    if ($company) {
                        $oUser = oxNew("oxUser");
                        if ($oUser->loadUserByEmail($notificationResultEntity->getAddress()->getEmail())) {
                            $oUser->assign([
                                'oxcompany' => $company->getName() ? : '',
                                'oxexternalid' => $company->getExternalId() ? : ''
                            ]);

                            $oUser->save();
                        }
                    }
                }

                $oParams = ['oxorder__oxtransstatus' => $oxidOrderStatus];

                $hasPaidDate = $order->oxorder__oxpaid
                    && $order->oxorder__oxpaid->rawValue
                    && $order->oxorder__oxpaid->rawValue != '0000-00-00 00:00:00';

                if (!$hasPaidDate && $isPaid && !$isPending) {
                    $oParams['oxorder__oxpaid'] = date("Y-m-d H:i:s", time());
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
                $orderStateId = $this->processSuccess($payment, $oxidOrderStatus, $isPending);

                // @codeCoverageIgnoreStart
                if (!$this->dryRun) {
                    // Create Invoice PDF
                    $oOrder = $this->getOrderByPaymentId($payment['paymentId']);
                    if ($oOrder) {
                        $userPayment = $this->getOxUserPayment();
                        $userPayment->load((string) $oOrder->oxorder__oxpaymentid);

                        //if ($userPayment->oxpayments__oxisb2bmethod) {
                            (new PayeverInvoiceManager())->addInvoice($oOrder);
                            $this->getLogger()->info(
                                sprintf('Invoice has been created for order #%s', $oOrder->getId()),
                                $payment
                            );
                        //}
                    }
                }
                // @codeCoverageIgnoreEnd

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

            $this->rejectPayment($payment, $exception->getCode() < 1);
            return;
        }
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
    public function getOrderByPaymentId($paymentId)
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
     * @param $payeverStatus
     * @return string
     */
    public function getInternalStatus($payeverStatus)
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
     * @return array
     */
    public function getRawData()
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
     * @param string $oxidStatus
     * @return bool
     */
    public function isPaidStatus($oxidStatus)
    {
        return in_array($oxidStatus, ['OK', 'IN_PROCESS']);
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
                throw new \InvalidArgumentException(
                    '[Notification]: action not allowed: ' . $action
                );
        }

        // check action in history to prevent duplication
        $this->getOrderActionHelper()->addAction([
            'orderId' => $orderId,
            'actionId' => $paymentData['id'],
            'amount' => $amount,
            'state' => 'success',
            'type' => 'product',
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
     * @param $items
     * @param $orderId
     * @param $action
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @throws oxConnectionException
     */
    private function processItems($items, $orderId, $action)
    {
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
                throw new \InvalidArgumentException(
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
     * Additional check if we really really have a user now.
     *
     * @param array $payment
     * @return array
     * @throws oxException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function ensureUserAndBasketLoaded(array $payment)
    {
        $restoreBasketInSession = !empty($payment['restoreBasketInSession']) && $payment['restoreBasketInSession'];
        $oUser = $this->loadUser($payment['basketId']);

        if (!$oUser && !$restoreBasketInSession) {
            throw new oxException('USER_NOT_FOUND');
        }

        $oBasket = $this->loadBasket($oUser, $payment);

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
     * @param $basketId
     * @return false|object|oxuser|null
     * @throws oxSystemComponentException
     */
    private function loadUser($basketId)
    {
        $oUser = oxNew('oxuser');
        if (!$oUser->loadActiveUser()) {
            $oUser = $this->getOxUser();
            $userId = $this->getUserByBasketId($basketId);
            $oUser->load($userId);
        }

        if (!$oUser) {
            $this->getLogger()->alert(sprintf('NOT FOUND user for basketId "%s"', $basketId));
        }

        return $oUser;
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
    private function getUserByBasketId($basketId)
    {
        /** @var oxuserbasket $oUserBasket */
        $oUserBasket = oxNew('oxuserbasket');
        $aWhere = ['oxuserbaskets.oxid' => $basketId, 'oxuserbaskets.oxtitle' => 'savedbasket'];

        // creating if it does not exist
        $oUserBasket->assignRecord($oUserBasket->buildSelectString($aWhere));

        return $oUserBasket->oxuserbaskets__oxuserid;
    }

    /**
     * @param $oUser
     * @param $payment
     * @return oxBasket
     * @throws oxSystemComponentException
     */
    private function loadBasket($oUser, $payment)
    {
        $sBasket = $this->getSession()->getVariable(self::SESS_TEMP_BASKET);
        $oBasket = unserialize($sBasket);

        $this->getSession()->deleteVariable(self::SESS_TEMP_BASKET);

        if (!$oBasket || get_class($oBasket) !== get_class(oxNew('oxbasket'))) {
            // get basket contents
            /** @var oxBasket $oBasket */
            $oBasket = $this->getOxBasket();
            $oBasket->setBasketUser($oUser);
            $oBasket->setPayment($payment['paymentMethod']);
            $oBasket->load();
            $oBasket->calculateBasket(true);
        }

        return $oBasket;
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

    /**
     * @param array $payment
     * @param string $oxidOrderStatus
     * @param bool $isPending
     * @return int - order state
     *
     * @throws oxException
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function processSuccess($payment, $oxidOrderStatus, $isPending = false)
    {
        $this->getLogger()->debug('processSuccess', [$payment['paymentId']]);
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
            $this->verifyOrderTotals($payment, $oBasket, $oOrder);
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
            $oParams['oxorder__oxpaid'] = date("Y-m-d H:i:s", time());
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
     * @param oxBasket $oBasket
     * @param oxOrder $oOrder
     * @return void
     * @throws Exception
     */
    private function verifyOrderTotals($payment, $oBasket, $oOrder)
    {
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($payment);

        // Calculate amount
        $basketAmount = $oBasket->getPrice()->getPrice();

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

    /**
     * @param $payload
     * @return NotificationRequestEntity
     */
    private function getNotificationRequestEntity($payload)
    {
        return new NotificationRequestEntity(json_decode($payload, true));
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

    private function shouldRejectIfExpiredStatus(RetrievePaymentResultEntity $retrievePaymentResult)
    {
        if (
            in_array($retrievePaymentResult->getStatus(), [Status::STATUS_DECLINED, Status::STATUS_FAILED]) &&
            in_array($retrievePaymentResult->getSpecificStatus(), ['ORDER_EXPIRED', 'CHECKOUT_EXPIRED'])
        ) {
            return true;
        }

        return false;
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
                $this->redirectToPaymentPageWithError($payment['errorMessage']);
            }
            exit();
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param $message
     * @return void
     *
     * @codeCoverageIgnore
     */
    private function redirectToPaymentPageWithError($message)
    {
        $this->getLogger()->info(sprintf('Canceling payment with message: %s', $message));
        $this->getDisplayHelper()->addErrorToDisplay($message);
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
    private function webRedirect($url, $fetchDest)
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
}
