<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Payments\Http\MessageEntity\AttributesEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CartItemV3Entity;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CompanyEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressV3Entity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerEntity;
use Payever\Sdk\Payments\Http\MessageEntity\DimensionsEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PaymentDataEntity;
use Payever\Sdk\Payments\Http\MessageEntity\PurchaseEntity;
use Payever\Sdk\Payments\Http\MessageEntity\ShippingOptionEntity;
use Payever\Sdk\Payments\Http\MessageEntity\UrlsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV3Request;
use Payever\Sdk\Payments\Http\RequestEntity\SubmitPaymentRequestV3;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PayeverPaymentV3RequestBuilder
{
    use PayeverLoggerTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverAddressFactoryTrait;
    use PayeverCountryFactoryTrait;
    use PayeverConfigHelperTrait;
    use PayeverOrderHelperTrait;
    use PayeverPaymentMethodFactoryTrait;
    use DryRunTrait;

    const MR_SALUTATION = 'mr';
    const MRS_SALUTATION = 'mrs';
    const MS_SALUTATION = 'ms';
    const FEMALE_GENDER = 'female';
    const MALE_GENDER = 'male';
    const TYPE_ORGANIZATION = 'organization';
    const TYPE_PERSON = 'person';


    /** @var oxbasket */
    private $cart;

    /** @var oxpayment */
    private $paymentMethod;

    /** @var oxpayment */
    private $shippingMethod;

    /** @var CreatePaymentV3Request $paymentRequest */
    private $paymentRequest;

    /**  @var oxsession */
    private $session;

    /**
     * @var PayeverPaymentUrlBuilder
     */
    private $urlBuilder;

    public function __construct()
    {
        $this->urlBuilder = new PayeverPaymentUrlBuilder();
    }

    /**
     * Creates payever payment and returns redirect URL
     *
     * @return string
     *
     * @throws oxArticleInputException
     * @throws oxNoArticleException
     * @throws oxSystemComponentException
     * @throws Exception
     */
    public function generatePaymentUrl()
    {
        return $this->getPaymentMethod()->getFieldData('oxissubmitmethod')
            ? $this->submitPaymentRequestUrl()
            : $this->createPaymentRequestUrl();
    }

    /**
     * Used in unit tests
     *
     * @param oxSession $session
     *
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @throws oxArticleInputException
     * @throws oxSystemComponentException
     * @throws oxNoArticleException
     * @throws Exception
     */
    private function createPaymentRequestUrl()
    {
        $paymentRequest = $this->buildRequest();
        $response = $this->getPaymentsApiClient()->createPaymentV3Request($paymentRequest);

        $this->getLogger()->info('Payment request created', $response->getResponseEntity()->toArray());

        return $response->getResponseEntity()->getRedirectUrl();
    }

    /**
     * @throws oxArticleInputException
     * @throws oxSystemComponentException
     * @throws oxNoArticleException
     * @throws Exception
     */
    private function submitPaymentRequestUrl()
    {
        $paymentRequest = $this->buildRequest();
        $response = $this->getPaymentsApiClient()->submitPaymentRequestV3($paymentRequest);

        $this->getLogger()->info('Payment request submitted', $response->getResponseEntity()->toArray());

        return $this->urlBuilder->createRedirectUrl($response->getResponseEntity()->getResult());
    }

    /**
     * Build payment request entity
     *
     * @return CreatePaymentV3Request|SubmitPaymentRequestV3
     *
     * @throws oxArticleInputException
     * @throws oxNoArticleException
     * @throws oxSystemComponentException
     */
    private function buildRequest()
    {
        if (!count($this->getCart()->getContents())) {
            throw new UnexpectedValueException('Basket is empty');
        }

        $this->getLogger()->info('Collecting order data for create payment call...');

        $this->paymentRequest = $this->getPaymentMethod()->getFieldData('oxissubmitmethod')
            ? new SubmitPaymentRequestV3()
            : new CreatePaymentV3Request();

        $this
            ->addUrls()
            ->addCart()
            ->addPurchase()
            ->addChannel()
            ->addCustomerData()
            ->addOrderData()
            ->addPaymentData()
            ->addShippingOption()
            ->addCompany()
            ->addAddress();

        $this->getLogger()
            ->info('Finished collecting order data for create payment call', $this->paymentRequest->toArray());

        return $this->paymentRequest;
    }

    /**
     * Add channel entity to request
     *
     * @return $this
     */
    private function addChannel()
    {
        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setType('ecommerce')
            ->setName(ChannelSet::CHANNEL_OXID)
            ->setSource((string) $this->getConfigHelper()->getOxidVersionInt());

        $this->paymentRequest->setChannel($channelEntity);

        return $this;
    }

    /**
     * Add order items to request
     *
     * @return $this
     *
     * @throws oxArticleInputException
     * @throws oxNoArticleException
     */
    private function addCart()
    {
        $basketItems = [];
        /** @var oxbasketitem $item */
        foreach ($this->getCart()->getContents() as $item) {
            $cartItem = new CartItemV3Entity();
            $quantity = (int) $item->getAmount();
            $cartItem
                ->setName($item->getTitle())
                ->setUnitPrice(oxRegistry::getUtils()->fRound($item->getUnitPrice()->getPrice()))
                ->setTaxRate($item->getUnitPrice()->getVat())
                ->setQuantity($quantity)
                ->setTotalAmount(oxRegistry::getUtils()->fRound($item->getUnitPrice()->getPrice() * $quantity))
                ->setDescription($item->getArticle()->getFieldData('oxshortdesc'))
                ->setImageUrl($item->getIconUrl())
                ->setProductUrl($item->getLink())
                ->setSku(preg_replace('#[^0-9a-z_]+#i', '-', $item->getArticle()->getFieldData('oxartnum')))
                ->setIdentifier($item->getProductId());

            if ($item->getArticle()->getManufacturer()) {
                $cartItem->setBrand($item->getArticle()->getManufacturer()->getFieldData('oxtitle'));
            }

            if ($item->getArticle()->getCategory()) {
                $cartItem->setCategory($item->getArticle()->getCategory()->getFieldData('oxtitle'));
            }

            $dimensions = new DimensionsEntity();
            $dimensions->setHeight((float) $item->getArticle()->getFieldData('oxheight'));
            $dimensions->setWidth((float) $item->getArticle()->getFieldData('oxwidth'));
            $dimensions->setLength((float) $item->getArticle()->getFieldData('oxlength'));

            $attributes = new AttributesEntity();
            $attributes->setWeight((float) $item->getArticle()->getWeight());
            $attributes->setDimensions($dimensions);

            $cartItem->setAttributes($attributes);

            $basketItems[] = $cartItem;
        }

        $discount = 0;
        /** @var oxvoucher $voucher */
        foreach ($this->getCart()->getVouchers() as $voucher) {
            $cartItem = new CartItemV3Entity();
            $cartItem
                ->setName('Discount')
                ->setIdentifier('discount')
                ->setUnitPrice($voucher->dVoucherdiscount * (-1))
                ->setTotalAmount($voucher->dVoucherdiscount * (-1))
                ->setQuantity(1);

            $basketItems[] = $cartItem;
            $discount += $voucher->dVoucherdiscount;
        }

        if ($this->getCart()->getGiftCardCost()) {
            $cartItem = new CartItemV3Entity();
            $cartItem
                ->setName('Greeting Card')
                ->setIdentifier(payeverorderaction::TYPE_GIFTCARD_COST)
                ->setUnitPrice($this->getCart()->getGiftCardCost()->getBruttoPrice())
                ->setTotalAmount($this->getCart()->getGiftCardCost()->getBruttoPrice())
                ->setQuantity(1);

            $basketItems[] = $cartItem;
        }

        if ($this->getCart()->getWrappingCost()) {
            $cartItem = new CartItemV3Entity();
            $cartItem
                ->setName('Gift Wrapping')
                ->setIdentifier(payeverorderaction::TYPE_WRAP_COST)
                ->setUnitPrice($this->getCart()->getWrappingCost()->getBruttoPrice())
                ->setTotalAmount($this->getCart()->getWrappingCost()->getBruttoPrice())
                ->setQuantity(1);

            $basketItems[] = $cartItem;
        }

        if (!$this->dryRun) {
            // Save discount in session
            $this->getSession()->oxidpayever_discount = $discount;
            $this->getSession()->setVariable('oxidpayever_discount', $discount);
            $this->getSession()->setVariable(PayeverGatewayManager::SESS_TEMP_BASKET, serialize($this->getCart()));
        }

        $this->paymentRequest->setCart($basketItems);

        return $this;
    }

    /**
     * Add user information to request
     *
     * @return $this
     */
    private function addCustomerData()
    {
        $oUser = $this->getCart()->getBasketUser();
        $gender = strtolower($oUser->getFieldData('oxsal')) === self::MR_SALUTATION ? self::MALE_GENDER :
             self::FEMALE_GENDER;
        $type = $this->getPaymentMethod()->getFieldData('oxisb2bmethod') ? self::TYPE_ORGANIZATION : self::TYPE_PERSON;

        $customerEntity = new CustomerEntity();
        $customerEntity
            ->setType($type)
            ->setPhone($oUser->getFieldData('oxfon'))
            ->setEmail($oUser->getFieldData('oxusername'))
            ->setGender($gender);


        if ($oUser->getFieldData('oxbirthdate')) {
            if ($oUser->getFieldData('oxbirthdate') && $oUser->getFieldData('oxbirthdate') != '0000-00-00') {
                $customerEntity->setBirthdate($oUser->getFieldData('oxbirthdate'));
            }
        }

        $this->paymentRequest->setCustomer($customerEntity);

        return $this;
    }

    /**
     * Add order information to request
     *
     * @return $this
     *
     * @throws oxSystemComponentException
     */
    private function addOrderData()
    {
        $oUser = $this->getCart()->getBasketUser();
        $apiMethod = substr($this->getCart()->getPaymentId(), strlen(PayeverConfig::PLUGIN_PREFIX));
        $language = $this->getConfigHelper()->getLanguage() ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if (\PayeverConfig::LOCALE_STORE_VALUE === $language) {
            $language = oxregistry::getLang()->getLanguageAbbr();
        }

        $this->paymentRequest
            ->setReference($oUser->getBasket('savedbasket')->getId())
            ->setPaymentMethod($apiMethod)
            ->setClientIp(oxRegistry::get("oxUtilsServer")->getRemoteAddress())
            ->setPluginVersion($this->getConfigHelper()->getPluginVersion())
            ->setLocale($language);

        if ($this->getPaymentMethod()->getFieldData('oxvariants')) {
            $oxvariants = json_decode(html_entity_decode($this->getPaymentMethod()->getFieldData('oxvariants')));
            if ($oxvariants) {
                $this->paymentRequest
                    ->setPaymentMethod($oxvariants->paymentMethod)
                    ->setVariantId($oxvariants->variantId);
            }
        }

        if ($this->getPaymentMethod()->getFieldData('oxpaymentissuer')) {
            $this->paymentRequest->setPaymentIssuer($this->getPaymentMethod()->getFieldData('oxpaymentissuer'));
        }

        return $this;
    }

    /**
     * Add callback urls to request
     *
     * @return $this
     */
    private function addUrls()
    {
        $urls = new UrlsEntity();

        $urls
            ->setSuccess($this->urlBuilder->generateCallbackUrl(PayeverPaymentUrlBuilder::STATUS_SUCCESS))
            ->setCancel($this->urlBuilder->generateCallbackUrl(PayeverPaymentUrlBuilder::STATUS_CANCEL))
            ->setFailure($this->urlBuilder->generateCallbackUrl(PayeverPaymentUrlBuilder::STATUS_FAILURE))
            ->setNotification($this->urlBuilder->generateCallbackUrl(PayeverPaymentUrlBuilder::STATUS_NOTICE))
            ->setPending($this->urlBuilder->generateCallbackUrl(PayeverPaymentUrlBuilder::STATUS_PENDING));

        $this->paymentRequest->setUrls($urls);

        return $this;
    }

    /**
     * Add order purchase information to request
     *
     * @return $this
     */
    private function addPurchase()
    {
        $purchaseEntity = new PurchaseEntity();
        $purchaseEntity
            ->setAmount(oxRegistry::getUtils()->fRound($this->getOrderHelper()->getAmountByCart($this->getCart())))
            ->setDeliveryFee(oxRegistry::getUtils()->fRound($this->getOrderHelper()->getFeeByCart($this->getCart())))
            ->setCurrency($this->getCart()->getBasketCurrency()->name);

        $this->paymentRequest->setPurchase($purchaseEntity);

        return $this;
    }

    /**
     * Add payment data to request
     *
     * @return $this
     *
     * @throws oxSystemComponentException
     */
    private function addPaymentData()
    {
        $paymentData = new PaymentDataEntity();
        $isRedirectMethod = true;
        if ($this->getPaymentMethod()->getFieldData('oxisredirectmethod')) {
            $isRedirectMethod = (bool) $this->getPaymentMethod()->getFieldData('oxisredirectmethod');
            $paymentData->setForceRedirect($isRedirectMethod);
        }

        $this->paymentRequest->setPaymentData($paymentData);
        $this->getSession()->setVariable(PayeverConfig::SESS_IS_REDIRECT_METHOD, $isRedirectMethod);

        return $this;
    }

    /**
     * Add shipping to request
     *
     * @return $this
     */
    private function addShippingOption()
    {
        $shippingId = $this->getCart()->getShippingId();
        if ($shippingId) {
            $shippingOptionEntity = new ShippingOptionEntity();
            $shippingOptionEntity
                ->setName($this->getShippingMethod()->getFieldData('oxtitle') ?: 'Standard')
                ->setCarrier($shippingId)
                ->setPrice($this->getCart()->getDeliveryCost()->getPrice())
                ->setTaxAmount($this->getCart()->getDeliveryCost()->getVatValue())
                ->setTaxRate($this->getCart()->getDeliveryCost()->getVat());

            $this->paymentRequest->setShippingOption($shippingOptionEntity);
        }

        return $this;
    }

    /**
     * Add company to request
     *
     * @return $this
     */
    private function addCompany()
    {
        $user = $this->getCart()->getBasketUser();
        $companyName = $user->getFieldData('oxcompany');
        if ($companyName) {
            $companyEntity = new CompanyEntity();
            $companyEntity->setName($companyName);

            if ($this->getConfigHelper()->isCompanySearchAvailable()) {
                $companyEntity->setExternalId($user->getFieldData('oxexternalid'));
                $companyEntity->setTaxId($user->getFieldData('oxvatid'));
            }

            $this->paymentRequest->setCompany($companyEntity);
        }

        return $this;
    }

    /**
     * Add shipping address to request
     *
     * @return $this
     *
     * @throws oxSystemComponentException
     */
    private function addAddress()
    {
        $soxAddressId = $this->getSession()->getVariable('deladrid');
        $billingAddress = $this->getAddressEntity();
        $this->paymentRequest->setBillingAddress($billingAddress);

        if (
            !in_array(
                $this->getPaymentMethod()->getFieldData('oxid'),
                PayeverConfig::getShippingNotAllowedMethods()
            )
        ) {
            $shippingAddress = $this->getAddressEntity($soxAddressId);
            $this->paymentRequest->setShippingAddress($shippingAddress);
        }

        return $this;
    }

    /**
     * Create customer address entity
     *
     * @param string $soxAddressId
     *
     * @return CustomerAddressV3Entity
     *
     * @throws oxSystemComponentException
     */
    private function getAddressEntity($soxAddressId = null)
    {
        $entity = $this->getCart()->getBasketUser();
        if ($soxAddressId) {
            $entity = $this->getAddressFactory()->create();
            $entity->load($soxAddressId);
        }

        $oxCountry = $this->getCountryFactory()->create();
        $oxCountry->load($entity->getFieldData('oxcountryid'));

        $customerAddressEntity = new CustomerAddressV3Entity();
        $customerAddressEntity
            ->setFirstName($entity->getFieldData('oxfname'))
            ->setLastName($entity->getFieldData('oxlname'))
            ->setCity($entity->getFieldData('oxcity'))
            ->setZip($entity->getFieldData('oxzip'))
            ->setStreet($entity->getFieldData('oxstreet') . ' ' . $entity->getFieldData('oxstreetnr'))
            ->setOrganizationName($entity->getFieldData('oxcompany'))
            ->setCountry($oxCountry->getFieldData('oxisoalpha2'));

        if ($entity->getFieldData('oxsal')) {
            $salutation = strtolower($entity->getFieldData('oxsal'));
            if (in_array($salutation, [self::MR_SALUTATION, self::MRS_SALUTATION, self::MS_SALUTATION])) {
                $customerAddressEntity->setSalutation($salutation);
            }
        }

        return $customerAddressEntity;
    }

    /**
     * @return oxpayment
     *
     * @throws oxSystemComponentException
     */
    public function getPaymentMethod()
    {
        if (null === $this->paymentMethod) {
            $this->paymentMethod = $this->getPaymentMethodFactory()->create();
            $this->paymentMethod->load($this->getCart()->getPaymentId());
        }

        return $this->paymentMethod;
    }

    /**
     * @return oxpayment
     */
    private function getShippingMethod()
    {
        if (null === $this->shippingMethod) {
            $this->shippingMethod = oxNew('oxDeliverySet');
            $this->shippingMethod->load($this->getCart()->getShippingId());
        }

        return $this->shippingMethod;
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
     * @return oxSession|string
     */
    private function getSession()
    {
        if (null === $this->session) {
            $this->session = oxNew('oxSession');
        }

        return $this->session;
    }
}
