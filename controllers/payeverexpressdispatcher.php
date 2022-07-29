<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\ExternalIntegration\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\ExternalIntegration\Payments\Enum\Status;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class payeverExpressDispatcher extends payeverStandardDispatcher
{
    const STATUS_PARAM = 'sts';
    const LOCK_WAIT_SECONDS = 30;

    public function payeverWidgetSuccess()
    {
        $config = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(sprintf(
            "Handling Finance Express callback success, paymentId: %s",
            $paymentId
        ), $_GET);

        $this->getLogger()->info("Waiting for lock", [$paymentId]);
        $this->getLocker()->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);
        $this->getLogger()->info("Locked", [$paymentId]);

        /** @var RetrievePaymentResultEntity $retrievePaymentResult */
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);
        $product = $this->getProductByNumber($retrievePaymentResult->getReference());

        try {
            $isPending = $config->getRequestParameter('is_pending');

            $this->processOrder($retrievePaymentResult, $product, $isPending);
            $this->getLocker()->releaseLock($paymentId);

            return 'thankyou';
        } catch (Exception $exception) {
            $this->getLocker()->releaseLock($paymentId);
            $this->getLogger()->error([sprintf(
                "Payment unlocked by exception: %s",
                $exception->getMessage()
            ), $paymentId]);

            $this->addErrorToDisplay($exception->getMessage());

            $this->getUtils()->redirect($product->getLink(), false);
        }
    }

    /**
     * @throws Exception
     */
    public function payeverWidgetFailure()
    {
        $config = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(sprintf(
            "Handling Finance Express callback failure, paymentId: %s",
            $paymentId
        ), $_GET);

        /** @var RetrievePaymentResultEntity $retrievePaymentResult */
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);
        $product = $this->getProductByNumber($retrievePaymentResult->getReference());

        $this->addErrorToDisplay('The payment hasn\'t been successful');

        $this->getUtils()->redirect($product->getLink(), false);
    }

    public function payeverWidgetCancel()
    {
        $message = 'Payment has been cancelled';
        $this->getLogger()->info(sprintf("Canceling Finance Express payment with message: %s", $message));
        $this->addErrorToDisplay($message);

        $this->_redirectToCart();
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function payeverWidgetNotice()
    {
        $config = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(sprintf("Handling Finance Express callback notice, paymentId: %s", $paymentId), $_GET);

        $this->getLogger()->info("Waiting for lock", [$paymentId]);
        $this->getLocker()->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);
        $this->getLogger()->info("Locked", [$paymentId]);

        /** @var RetrievePaymentResultEntity $retrievePaymentResult */
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);
        $product = $this->getProductByNumber($retrievePaymentResult->getReference());

        try {
            $oOrder = $this->processOrder($retrievePaymentResult, $product, false, true);

            $this->getLocker()->releaseLock($paymentId);

            echo json_encode(
                [
                    'result' => 'success',
                    'message' => 'Order has been processed',
                    'order_id' => $oOrder->getId()
                ]
            );
        } catch (Exception $exception) {
            $this->getLocker()->releaseLock($paymentId);
            $this->getLogger()->error([sprintf(
                "Payment unlocked by exception: %s",
                $exception->getMessage()
            ), $paymentId]);

            header('HTTP/1.1 400 BAD REQUEST');
            echo json_encode(['result' => 'error', 'message' => $exception->getMessage()]);
        }

        exit();
    }

    /**
     * @param $paymentId
     * @param $sts
     * @return RetrievePaymentResultEntity
     * @throws Exception
     */
    private function getRetrievePaymentResultEntity($paymentId)
    {
        $retrievePaymentResponse = $this->getPaymentsApiClient()->retrievePaymentRequest($paymentId);
        /** @var RetrievePaymentResponse $retrievePaymentEntity */
        $retrievePaymentEntity = $retrievePaymentResponse->getResponseEntity();

        return $retrievePaymentEntity->getResult();
    }

    /**
     * @param $productId
     * @param $oUser
     * @return object|oxBasket
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     */
    protected function createBasket($productId, $oUser)
    {
        $oxBasket = oxNew('oxBasket');
        $oxBasket->setBasketUser($oUser);
        $oxBasket->addToBasket($productId, 1);

        $oxBasket->calculateBasket(true);

        return $oxBasket;
    }

    /**
     * @param string $articleNumber
     * @return object|oxarticle
     */
    protected function getProductByNumber($articleNumber)
    {
        $product = oxNew('oxarticle');
        $query = $product->buildSelectString(['oxartnum' => $articleNumber]);
        $product->assignRecord($query);

        return $product;
    }

    /**
     * Creates user
     *
     * @param $payment
     * @return object|oxuser|oxUser|null
     * @throws oxSystemComponentException
     */
    protected function createUser($payment)
    {
        $address = $payment->getAddress();
        $userEmail = $address->getEmail();
        $oUser = $this->loadUserByEmail($userEmail);

        if (!$oUser) {
            $oUser = oxNew("oxUser");
            $oUser->oxuser__oxactive = new oxField(1);
            $oUser->oxuser__oxusername = new oxField($userEmail);
            $oUser->oxuser__oxfname = new oxField($address->getFirstName());
            $oUser->oxuser__oxlname = new oxField($address->getLastName());
            $oUser->oxuser__oxfon = new oxField($address->getPhone());
            $oUser->oxuser__oxsal = new oxField($this->getOxidSalutation($address->getSalutation()));
            $oUser->oxuser__oxcompany = new oxField();
            $oUser->oxuser__oxstreet = new oxField($address->getStreet());
            $oUser->oxuser__oxstreetnr = new oxField($address->getStreetNumber());
            $oUser->oxuser__oxcity = new oxField($address->getCity());
            $oUser->oxuser__oxzip = new oxField($address->getZipCode());
            $oCountry = $this->getCountryFactory()->create();
            $sCountryId = $oCountry->getIdByCode($address->getCountry());
            $oUser->oxuser__oxcountryid = new oxField($sCountryId);
            $oUser->oxuser__oxstateid = new oxField('');
            $oUser->oxuser__oxaddinfo = new oxField('');
            $oUser->oxuser__oxustid = new oxField('');
            $oUser->oxuser__oxfax = new oxField('');

            if ($oUser->save()) {
                // and adding to group "oxidnotyetordered"
                $oUser->addToGroup("oxidnotyetordered");
            }
        }

        return $oUser;
    }

    /**
     * @param $salutation
     * @return string
     */
    protected function getOxidSalutation($salutation)
    {
        switch ($salutation) {
            case 'MR_SALUATATION':
                return 'mr';
            case 'MRS_SALUATATION':
                return 'mrs';
            case 'MS_SALUATATION':
                return 'ms';
            default:
                return '';
        }
    }

    /**
     * Tries to load user object by email
     *
     * @param string $email
     *
     * @return oxuser|null
     */
    protected function loadUserByEmail($email)
    {
        $oUser = oxNew("oxUser");
        $sUserId = $oUser->getIdByUserName($email);
        if ($sUserId) {
            $oUser->load($sUserId);

            return $oUser;
        }

        return null;
    }

    /**
     * @param $retrievePaymentResult
     * @param $product
     * @param bool $isPending
     * @param bool $isNotice
     * @return false|oxOrder|payeverOxOrder
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     * @throws oxConnectionException
     * @throws oxException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function processOrder($retrievePaymentResult, $product, $isPending = false, $isNotice = false)
    {
        $paymentDetails = $retrievePaymentResult->getPaymentDetails();
        $paymentMethod = PayeverConfig::PLUGIN_PREFIX . $retrievePaymentResult->getPaymentType();

        $oxidOrderStatus = $this->getInternalStatus($retrievePaymentResult->getStatus());
        $isPaid = $this->isPaidStatus($oxidOrderStatus);

        $oUser = $this->createUser($retrievePaymentResult);

        $oSession = $this->getSession();

        $oSession->oxidpayever_payment_id = $retrievePaymentResult->getId();
        $oSession->setVariable('oxidpayever_payment_id', $retrievePaymentResult->getId());
        $oOrder = $this->getOrderByPaymentId($retrievePaymentResult->getId());
        $notificationTimestamp = 0;

        if (!$oOrder) {
            $isSuccessfulPayment = $this->isSuccessfulPaymentStatus($retrievePaymentResult->getStatus());
            if (!$isSuccessfulPayment) {
                throw new oxException('The payment hasn\'t been successful');
            }

            /** @var payeverOxOrder $oOrder */
            $oOrder = oxNew('oxorder');

            $oBasket = $this->createBasket($product->getId(), $oUser);
            $basketAmount = (float) $this->getOrderHelper()->getAmountByCart($oBasket);
            if ($basketAmount != (float) $retrievePaymentResult->getTotal()) {
                $message = sprintf(
                    'The amount really paid (%.2F %s) is not equal to the product amount (%.2F %s).',
                    $retrievePaymentResult->getTotal(),
                    $retrievePaymentResult->getCurrency(),
                    $basketAmount,
                    $oBasket->getBasketCurrency()->name
                );

                throw new oxException($message);
            }

            $this->prepareDeliveryAddress($oUser);
            $oBasket->setPayment($paymentMethod);

            //finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            if ($oOrder instanceof payeverOxOrderCompatible) {
                $orderStateId = $oOrder->setOrderStatus($oxidOrderStatus)->finalizeOrder($oBasket, $oUser, true);
            } else {
                $orderStateId = $oOrder->finalizeOrder($oBasket, $oUser, true, $oxidOrderStatus);
            }

            // performing special actions after user finishes order (assignment to special user groups)
            $oUser->onOrderExecute($oBasket, $orderStateId);

            if (!in_array($orderStateId, [oxOrder::ORDER_STATE_OK, oxOrder::ORDER_STATE_ORDEREXISTS])) {
                throw new oxException('Bad order.');
            }

            $oOrder->load($oBasket->getOrderId());
            $userPayment = oxNew('oxUserPayment');
            $userPayment->load((string)$oOrder->oxorder__oxpaymentid);

            $aParams = [
                'oxuserpayments__oxpspayever_transaction_id' => $retrievePaymentResult->getId(),
                'oxuserpayments__oxpaymentsid' => $paymentMethod,
            ];
            $userPayment->assign($aParams);
            $userPayment->save();

            $oParams = [
                'oxorder__basketid' => $oUser->getBasket('savedbasket')->getId(),
                'oxorder__oxpaymenttype' => $paymentMethod,
                'oxorder__oxtransid' => $retrievePaymentResult->getId(),
                'oxorder__panid' => isset($paymentDetails['usage_text']) ? $paymentDetails['usage_text'] : null,
            ];

            if ($isPaid && !$isPending) {
                $oParams['oxorder__oxpaid'] = date("Y-m-d H:i:s", time());
            }

            if (!$isNotice) {
                $this->getLogger()->debug('Prepare session before redirect');
                $this->getSession()->setVariable('sess_challenge', $oBasket->getOrderId());

                $basketName = $this->getConfig()->getConfigParam('blMallSharedBasket') == 0
                    ? $this->getConfig()->getShopId() . '_basket'
                    : 'basket';

                $this->getLogger()->debug('Saved serialized basket to session');
                $this->getSession()->setBasket($oBasket);
                $this->getSession()->setVariable($basketName, serialize($oBasket));
            }
        } else {
            $this->getLogger()->debug('Order exists ' . $oOrder->getId());
            $oParams = ['oxorder__oxtransstatus' => $oxidOrderStatus];

            $hasPaidDate = $oOrder->oxorder__oxpaid
                && $oOrder->oxorder__oxpaid->rawValue
                && $oOrder->oxorder__oxpaid->rawValue != '0000-00-00 00:00:00';

            if (!$hasPaidDate && $isPaid && !$isPending) {
                $oParams['oxorder__oxpaid'] = date("Y-m-d H:i:s", time());
            }
        }

        if ($isNotice) {
            $rawData = $this->getRawData();
            if ($rawData) {
                $notificationTimestamp = !empty($rawData['created_at'])
                    ? strtotime($rawData['created_at'])
                    : $notificationTimestamp;
                $shouldRejectNotification = $this->shouldRejectNotification($oOrder, $notificationTimestamp);
                if ($shouldRejectNotification) {
                    echo json_encode(
                        [
                            'result' => 'error',
                            'message' => 'Notification rejected: newer notification already processed'
                        ]
                    );

                    exit();
                }
            }
        }

        if ($notificationTimestamp) {
            $oParams['oxorder__payever_notification_timestamp'] = $notificationTimestamp;
        }

        $oOrder->assign($oParams);
        $oOrder->save();

        return $oOrder;
    }

    /**
     * @param oxOrder $order
     * @param int $notificationTimestamp
     * @return bool
     */
    protected function shouldRejectNotification($order, $notificationTimestamp)
    {
        return (int) $order->oxorder__payever_notification_timestamp->rawValue > $notificationTimestamp;
    }

    /**
     * @param $oUser
     * @return string
     */
    protected function getDeliveryAddressMD5($oUser)
    {
        $sDelAddress = $oUser->getEncodedDeliveryAddress();

        // delivery address
        if (oxRegistry::getSession()->getVariable('deladrid')) {
            $oDelAdress = oxNew('oxaddress');
            $oDelAdress->load(oxRegistry::getSession()->getVariable('deladrid'));

            $sDelAddress .= $oDelAdress->getEncodedDeliveryAddress();
        }

        return $sDelAddress;
    }

    /**
     * @param string $payeverStatus
     * @return bool
     */
    private function isSuccessfulPaymentStatus($payeverStatus)
    {
        return in_array($payeverStatus, [
            Status::STATUS_IN_PROCESS,
            Status::STATUS_ACCEPTED,
            Status::STATUS_PAID,
        ]);
    }

    /**
     * @param $oUser
     */
    private function prepareDeliveryAddress($oUser)
    {
        $config = $this->getConfig();
        $_POST['sDeliveryAddressMD5'] = $this->getDeliveryAddressMD5($oUser);
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
    }
}
