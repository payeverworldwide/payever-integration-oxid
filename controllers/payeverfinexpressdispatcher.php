<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author    payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Http\ResponseEntity\RetrievePaymentResponse;
use Payever\Sdk\Payments\Http\MessageEntity\RetrievePaymentResultEntity;
use Payever\Sdk\Payments\Enum\Status;
use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\DeliverySetList;
use OxidEsales\Eshop\Application\Model\DeliveryList;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExitExpression)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class payeverFinexpressDispatcher extends payeverStandardDispatcher
{
    const STATUS_PARAM = 'sts';

    const LOCK_WAIT_SECONDS = 30;

    /** @var DeliverySetList */
    private $oxDeliverySetList;

    /** @var DeliveryList */
    private $oxDeliveryList;

    /** @var oxarticle */
    private $oxArticle;


    public function payeverWidgetQuoteCallback()
    {
        $cartItems    = $this->getRequestHelper()->getQueryData('items', '[]');
        $request      = $this->getRequestHelper()->getRequestContent();
        $this->getLogger()->debug($request);
        $data = json_decode($request, true);

        $oHeader = oxNew(\OxidEsales\Eshop\Core\Header::class);
        if (isset($data['shippingMethod'])) {
            $this->getLogger()->info('quoteAction: Selected shippingMethod: ' . $data['shippingMethod']);
            $oHeader->setHeader('HTTP/1.1 200');
            $oHeader->setHeader('Content-Type: application/json; charset=utf-8');
            !$this->dryRun && $oHeader->sendHeader();
            echo json_encode([]);
            $this->dryRun || die();
            return;
        }

        try {
            if (!isset($data['shipping'])) {
                throw new \InvalidArgumentException('[QuoteCallback]: shipping was not set.');
            }

            if (empty($cartItems)) {
                throw new InvalidArgumentException('[QuoteCallback]: Product Items were not set.');
            }

            $cartItems = json_decode($cartItems, true);

            $result = [];
            $country = oxNew(Country::class);
            $shippingAddress = $data['shipping']['shippingAddress'];
            $countryId = $country->getIdByCode($shippingAddress['country']);
            $delSetList = $this->getOxDeliverySetList();
            $deliverySets = $delSetList->getDeliverySetList(null, $countryId);
            foreach ($deliverySets as $setId => $delivery_set) {
                $basket = $this->getOxBasket();
                $deliveryListProvider = $this->getOxDeliveryList();
                foreach ($cartItems as $artNum => $qty) {
                    $product = $this->getProductByNumber($artNum);
                    $basket->addToBasket($product->getId(), $qty);
                    $basket->calculateBasket(true);
                }

                $list = $deliveryListProvider->getDeliveryList($basket, null, $countryId, $setId);
                foreach ($list as $delivery) {
                    if ($deliveryListProvider->hasDeliveries($basket, null, $countryId, $setId)) {
                        $identifier                    = $delivery_set->oxdeliveryset__oxid->value;
                        $title                         = $delivery_set->oxdeliveryset__oxtitle->rawValue;
                        $price                         = $delivery->getDeliveryPrice()->getBruttoPrice();
                        $result[] = [
                            'reference' => $identifier,
                            'name'      => $title,
                            'countries' => [$shippingAddress['country']],
                            'price'     => $price,
                        ];
                    }
                }
            }

            $oHeader->setHeader('HTTP/1.1 200');
            $oHeader->setHeader('Content-Type: application/json; charset=utf-8');
            !$this->dryRun && $oHeader->sendHeader();
            echo json_encode(['shippingMethods' => $result]);
            $this->dryRun || die();
            return;
        } catch (Exception $exception) {
            $oHeader->setHeader('HTTP/1.1 400 BAD REQUEST');
            $oHeader->setHeader('Content-Type: application/json; charset=utf-8');
            !$this->dryRun && $oHeader->sendHeader();
            $this->getLogger()->critical('quoteAction: ' . $exception->getMessage());
            echo json_encode(['error' => $exception->getMessage()]);
            $this->dryRun || die();
            return;
        }
    }

    public function payeverWidgetSuccess()
    {
        $config    = $this->getConfig();
        $paymentId = $config->getRequestParameter('reference');
        $this->getLogger()->info(
            sprintf(
                'Handling Finance Express callback success, paymentId: %s',
                $paymentId
            ),
            $this->getRequestHelper()->getQueryData()
        );

        $this->getLogger()->info('Waiting for lock', [$paymentId]);
        $this->getLocker()->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);
        $this->getLogger()->info('Locked', [$paymentId]);

        try {
            /** @var RetrievePaymentResultEntity $retrievePaymentResult */
            $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);

            $this->processOrder(
                $retrievePaymentResult,
                $config->getRequestParameter('is_pending')
            );
            $this->getLocker()->releaseLock($paymentId);

            $this->getUtils()->redirect(
                $this->getConfig()->getSslShopUrl() . '?cl=thankyou',
                false
            );
        } catch (Exception $exception) {
            $this->getLocker()->releaseLock($paymentId);
            $this->getLogger()->error(
                sprintf(
                    "Payment unlocked by exception: %s",
                    $exception->getMessage()
                ),
                [$paymentId]
            );

            $this->addErrorToDisplay($exception->getMessage());
            $this->redirectToCancel('cart_');
        }
    }

    /**
     * @throws Exception
     */
    public function payeverWidgetFailure()
    {
        $config    = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(
            sprintf(
                'Handling Finance Express callback failure, paymentId: %s',
                $paymentId
            ),
            $this->getRequestHelper()->getQueryData()
        );

        /** @var RetrievePaymentResultEntity $retrievePaymentResult */
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);
        $this->addErrorToDisplay('The payment hasn\'t been successful');
        $this->redirectToCancel($retrievePaymentResult->getReference());
    }

    public function payeverWidgetCancel()
    {
        $config    = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(
            sprintf(
                'Handling Finance Express callback cancel, paymentId: %s',
                $paymentId
            ),
            $this->getRequestHelper()->getQueryData()
        );

        /** @var RetrievePaymentResultEntity $retrievePaymentResult */
        $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);
        $this->addErrorToDisplay('Payment has been cancelled');
        $this->redirectToCancel($retrievePaymentResult->getReference());
    }

    /**
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function payeverWidgetNotice()
    {
        $config    = $this->getConfig();
        $paymentId = $config->getRequestParameter('payment_id');
        $this->getLogger()->info(sprintf("Handling Finance Express callback notice, paymentId: %s", $paymentId), $_GET);
        $this->getLogger()->info('Waiting for lock', [$paymentId]);
        $this->getLocker()->acquireLock($paymentId, static::LOCK_WAIT_SECONDS);
        $this->getLogger()->info('Locked', [$paymentId]);

        $oHeader = oxNew(\OxidEsales\Eshop\Core\Header::class);

        try {
            /** @var RetrievePaymentResultEntity $retrievePaymentResult */
            $retrievePaymentResult = $this->getRetrievePaymentResultEntity($paymentId);

            $order = $this->processOrder(
                $retrievePaymentResult,
                $config->getRequestParameter('is_pending')
            );
            $this->getLocker()->releaseLock($paymentId);

            $oHeader->setHeader('HTTP/1.1 200');
            $oHeader->setHeader('Content-Type: application/json; charset=utf-8');
            !$this->dryRun && $oHeader->sendHeader();
            echo json_encode(
                [
                    'result'   => 'success',
                    'message'  => 'Order has been processed',
                    'order_id' => $order->getId(),
                ]
            );
        } catch (Exception $exception) {
            $this->getLocker()->releaseLock($paymentId);
            $this->getLogger()->error(
                sprintf(
                    'Payment unlocked by exception: %s',
                    $exception->getMessage()
                ),
                [$paymentId]
            );

            $oHeader->setHeader('HTTP/1.1 400 BAD REQUEST');
            $oHeader->setHeader('Content-Type: application/json; charset=utf-8');
            !$this->dryRun && $oHeader->sendHeader();
            echo json_encode(['result' => 'error', 'message' => $exception->getMessage()]);
        }

        $this->dryRun || die();
    }

    /**
     * @param $paymentId
     * @param $sts
     *
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
     *
     * @return object|oxBasket
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     */
    private function createBasket($cartItems, $oUser)
    {
        $oxBasket = $this->getOxBasket();
        $oxBasket->setBasketUser($oUser);
        foreach ($cartItems as $artNum => $qty) {
            $productId = $this->getProductByNumber($artNum)->getId();
            if (!$productId) {
                throw new Exception('Unable to load product');
            }
            $oxBasket->addToBasket($productId, $qty);
        }
        $oxBasket->calculateBasket(true);

        return $oxBasket;
    }

    /**
     * @param  string  $articleNumber
     *
     * @return object|oxarticle
     */
    private function getProductByNumber($articleNumber)
    {
        $product = $this->getOxArticle();
        $query   = $product->buildSelectString(['oxartnum' => $articleNumber]);
        $product->assignRecord($query);

        return $product;
    }

    /**
     * Creates user
     *
     * @param $payment
     *
     * @return object|oxuser|oxUser|null
     * @throws oxSystemComponentException
     */
    private function createUser($payment)
    {
        $address   = $payment->getAddress();
        $userEmail = $address->getEmail();
        $oUser     = $this->loadUserByEmail($userEmail);

        if (!$oUser) {
            $oUser                      = $this->getOxUser();
            $oUser->oxuser__oxactive    = new oxField(1);
            $oUser->oxuser__oxusername  = new oxField($userEmail);
            $oUser->oxuser__oxfname     = new oxField($address->getFirstName());
            $oUser->oxuser__oxlname     = new oxField($address->getLastName());
            $oUser->oxuser__oxfon       = new oxField($address->getPhone());
            $oUser->oxuser__oxsal       = new oxField($this->getOxidSalutation($address->getSalutation()));
            $oUser->oxuser__oxcompany   = new oxField();
            $oUser->oxuser__oxstreet    = new oxField($address->getStreet());
            $oUser->oxuser__oxstreetnr  = new oxField($address->getStreetNumber());
            $oUser->oxuser__oxcity      = new oxField($address->getCity());
            $oUser->oxuser__oxzip       = new oxField($address->getZipCode());
            $oCountry                   = $this->getCountryFactory()->create();
            $sCountryId                 = $oCountry->getIdByCode($address->getCountry());
            $oUser->oxuser__oxcountryid = new oxField($sCountryId);
            $oUser->oxuser__oxstateid   = new oxField('');
            $oUser->oxuser__oxaddinfo   = new oxField('');
            $oUser->oxuser__oxustid     = new oxField('');
            $oUser->oxuser__oxfax       = new oxField('');

            if ($oUser->save()) {
                // and adding to group "oxidnotyetordered"
                $oUser->addToGroup('oxidnotyetordered');
            }
        }

        return $oUser;
    }

    /**
     * @param $salutation
     *
     * @return string
     */
    private function getOxidSalutation($salutation)
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
     * @param  string  $email
     *
     * @return oxuser|null
     */
    private function loadUserByEmail($email)
    {
        $oUser   = oxNew("oxUser");
        $sUserId = $oUser->getIdByUserName($email);
        if ($sUserId) {
            $oUser->load($sUserId);

            return $oUser;
        }

        return null;
    }

    /**
     * @param $retrievePaymentResult
     * @param $isPending
     *
     * @return oxOrder|payeverOxOrder|payeverOxOrderCompatible
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
    private function processOrder($retrievePaymentResult, $isPending = false)
    {
        $oOrder = $this->getOrderByPaymentId($retrievePaymentResult->getId());
        if ($oOrder) {
            $this->getLogger()->debug('Order exists ' . $oOrder->getId());
            return $oOrder;
        }

        $isSuccessfulPayment = $this->isSuccessfulPaymentStatus($retrievePaymentResult->getStatus());
        if (!$isSuccessfulPayment) {
            throw new oxException('The payment hasn\'t been successful');
        }

        $paymentMethod  = PayeverConfig::PLUGIN_PREFIX . $retrievePaymentResult->getPaymentType();
        $oxidOrderStatus = $this->getInternalStatus($retrievePaymentResult->getStatus());
        $isPaid = $this->isPaidStatus($oxidOrderStatus);

        // Create User
        $oUser = $this->createUser($retrievePaymentResult);
        $this->prepareDeliveryAddress($oUser);

        $oSession = $this->getSession();
        $oSession->oxidpayever_payment_id = $retrievePaymentResult->getId();
        $oSession->setVariable('oxidpayever_payment_id', $retrievePaymentResult->getId());

        /** @var payeverOxOrder $oOrder */
        $oOrder = $this->getOxOrder();

        // Create basket
        $cartItems = [];
        $items = $retrievePaymentResult->getItems();
        foreach ($items as $item) {
            $cartItems[$item->getIdentifier()] = $item->getQuantity();
        }
        $oBasket = $this->createBasket($cartItems, $oUser);

        $oBasket->setPayment($paymentMethod);

        // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
        if ($oOrder instanceof payeverOxOrderCompatible) {
            $orderStateId = $oOrder->setOrderStatus($oxidOrderStatus)
                                   ->finalizeOrder($oBasket, $oUser, true);
        } else {
            $orderStateId = $oOrder->finalizeOrder($oBasket, $oUser, true, $oxidOrderStatus);
        }

        // performing special actions after user finishes order (assignment to special user groups)
        $oUser->onOrderExecute($oBasket, $orderStateId);
        if (!in_array($orderStateId, [oxOrder::ORDER_STATE_OK, oxOrder::ORDER_STATE_ORDEREXISTS])) {
            throw new oxException('Bad order.');
        }

        $oOrder->load($oBasket->getOrderId());
        $userPayment = $this->getOxUserPayment();
        $userPayment->load((string)$oOrder->oxorder__oxpaymentid);

        $aParams = [
            'oxuserpayments__oxpspayever_transaction_id' => $retrievePaymentResult->getId(),
            'oxuserpayments__oxpaymentsid'               => $paymentMethod,
        ];
        $userPayment->assign($aParams);
        $userPayment->save();

        $paymentDetails = $retrievePaymentResult->getPaymentDetails();
        $oParams = [
            'oxorder__basketid'      => $oUser->getBasket('savedbasket')->getId(),
            'oxorder__oxpaymenttype' => $paymentMethod,
            'oxorder__oxtransid'     => $retrievePaymentResult->getId(),
            'oxorder__panid'         => isset($paymentDetails['usage_text']) ? $paymentDetails['usage_text'] : null,
        ];

        if ($isPaid && !$isPending) {
            $oParams['oxorder__oxpaid'] = date('Y-m-d H:i:s', time());
        }

        $this->getLogger()->debug('Prepare session before redirect');
        $this->getSession()->setVariable('sess_challenge', $oBasket->getOrderId());

        $basketName = $this->getConfig()->getConfigParam('blMallSharedBasket') == 0
            ? $this->getConfig()->getShopId() . '_basket'
            : 'basket';

        $this->getLogger()->debug('Saved serialized basket to session');
        $this->getSession()->setBasket($oBasket);
        $this->getSession()->setVariable($basketName, serialize($oBasket));

        $oOrder->oxorder__oxfolder = $this->getFieldFactory()->createRaw('ORDERFOLDER_NEW');
        $oOrder->assign($oParams);

        $shippingOption = $retrievePaymentResult->getShippingOption();
        if ($shippingOption) {
            $oOrder->oxorder__oxdeltype->setValue($shippingOption->getCarrier());
            $oOrder->oxorder__oxdelcost->setValue($shippingOption->getPrice());
        }

        $oOrder->save();

        // Reassign product IDs
        /** @var \OxidEsales\Eshop\Application\Model\OrderArticleList $oOrderArticleList */
        $oOrderArticleList = $oOrder->getOrderArticles();
        foreach (array_keys($oOrderArticleList) as $index) {
            /** @var oxarticle $art */
            $oOrderArticleList[$index]->oxorderarticles__oxorderid->setValue($oOrder->getId());
        }

        $oOrder->setOrderArticleList($oOrderArticleList);
        $oOrder->save();

        return $oOrder;
    }

    /**
     * @param $oUser
     *
     * @return string
     */
    private function getDeliveryAddressMD5($oUser)
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
     * @param  string  $payeverStatus
     *
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

    private function redirectToCancel($reference)
    {
        if (strpos($reference, 'prod_') !== false) {
            $productId = str_replace('prod_', '', $reference);
            $product = $this->getProductByNumber($productId);
            $this->getUtils()->redirect($product->getLink(), false);

            return;
        }

        $sUrl = $this->getConfig()->getSslShopUrl() . '?cl=basket';
        $this->getUtils()->redirect($sUrl, false);
    }

    /**
     * @codeCoverageIgnore
     * @return DeliveryList
     */
    private function getOxDeliveryList()
    {
        if ($this->oxDeliveryList === null) {
            return oxNew(DeliveryList::class);
        }

        return $this->oxDeliveryList;
    }

    /**
     * @param DeliveryList
     */
    public function setOxDeliveryList($oxDeliveryList)
    {
        $this->oxDeliveryList = $oxDeliveryList;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return DeliverySetList
     */
    private function getOxDeliverySetList()
    {
        if ($this->oxDeliverySetList === null) {
            return oxNew(DeliverySetList::class);
        }

        return $this->oxDeliverySetList;
    }

    /**
     * @param DeliverySetList
     */
    public function setOxDeliverySetList($oxDeliverySetList)
    {
        $this->oxDeliverySetList = $oxDeliverySetList;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return oxarticle
     */
    private function getOxArticle()
    {
        if ($this->oxArticle === null) {
            return oxNew('oxarticle');
        }

        return $this->oxArticle;
    }

    /**
     * @param oxarticle
     */
    public function setOxArticle($oxArticle)
    {
        $this->oxArticle = $oxArticle;

        return $this;
    }
}
