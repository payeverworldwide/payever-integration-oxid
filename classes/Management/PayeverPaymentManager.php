<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Http\RequestEntity;
use Payever\Sdk\Core\Enum\ChannelSet;
use Payever\Sdk\Payments\Http\MessageEntity\ChannelEntity;
use Payever\Sdk\Payments\Http\MessageEntity\CustomerAddressEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CreatePaymentV2Request;
use Payever\Sdk\Payments\Http\ResponseEntity\CreatePaymentV2Response;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayeverPaymentManager
{
    use DryRunTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverLoggerTrait;
    use PayeverConfigTrait;
    use PayeverAddressFactoryTrait;
    use PayeverCountryFactoryTrait;
    use PayeverPaymentMethodFactoryTrait;
    use PayeverConfigHelperTrait;
    use PayeverOrderHelperTrait;
    use PayeverSessionTrait;

    const MR_SALUTATION = 'mr';
    const MRS_SALUTATION = 'mrs';
    const MS_SALUTATION = 'ms';

    const STATUS_PARAM = 'sts';
    const LANG_PARAM = 'lang';

    /** @var oxbasket  */
    private $cart;

    /** @var oxpayment */
    private $paymentMethod;

    public function getPaymentRedirectUrl()
    {
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
            self::LANG_PARAM => oxRegistry::getLang()->getTplLanguage(),
        ];
        if ($appendPlaceholders) {
            $urlData['payment_id'] = '--PAYMENT-ID--';
        }

        $urlData = array_merge($urlData, $params);
        $shopUrl .= '?' . http_build_query($urlData, "", "&");

        return $shopUrl;
    }

    /**
     * @return CreatePaymentV2Request
     * @throws oxSystemComponentException|oxArticleException
     */
    private function getCreatePaymentV2RequestEntity()
    {
        return $this->populatePaymentRequestEntity(new CreatePaymentV2Request());
    }

    /**
     * @param RequestEntity|CreatePaymentV2Request $requestEntity
     * @return RequestEntity
     * @throws UnexpectedValueException
     * @throws oxSystemComponentException|oxArticleException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
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
                    'identifier' => 'discount',
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
            $oSession->setVariable(PayeverGatewayManager::SESS_TEMP_BASKET, serialize($oBasket));
        }

        $this->setRequestEntityDetails($requestEntity, $oBasket, $basketItems);
        $this->setRequestEntityAddress($requestEntity, $shippingAddressEntity, $billingAddressEntity);

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
     * @param RequestEntity|CreatePaymentV2Request $requestEntity
     * @param oxBasket $oBasket
     * @param array $basketItems
     * @return void
     * @throws oxSystemComponentException
     */
    private function setRequestEntityDetails($requestEntity, $oBasket, $basketItems)
    {
        $oUser = $oBasket->getBasketUser();

        /**
         * cut off the plugin prefix {@see PayeverConfig::PLUGIN_PREFIX}
         */
        $apiMethod = substr($oBasket->getPaymentId(), strlen(PayeverConfig::PLUGIN_PREFIX));
        $language = $this->getConfigHelper()->getLanguage() ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

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
            ->setPaymentMethod($apiMethod)
            ->setLocale($language)
            ->setSuccessUrl($this->generateCallbackUrl('success'))
            ->setPendingUrl($this->generateCallbackUrl('pending'))
            ->setCancelUrl($this->generateCallbackUrl('cancel'))
            ->setFailureUrl($this->generateCallbackUrl('failure'))
            ->setNoticeUrl($this->generateCallbackUrl('notice'))
            ->setCart($basketItems);

        if (strpos($apiMethod, '-') && $this->getPaymentMethod()->oxpayments__oxvariants) {
            $oxvariants = json_decode($this->getPaymentMethod()->oxpayments__oxvariants->rawValue, true);
            $requestEntity
                ->setPaymentMethod($oxvariants['paymentMethod'])
                ->setVariantId($oxvariants['variantId']);
        }

        $birthdate = $oUser->oxuser__oxbirthdate ? $oUser->oxuser__oxbirthdate->value : null;
        if (!empty($birthdate) && $birthdate != '0000-00-00') {
            $requestEntity->setBirthdate($birthdate);
        }
    }

    /**
     * @param RequestEntity|CreatePaymentV2Request $requestEntity
     * @param CustomerAddressEntity $shippingAddressEntity
     * @param CustomerAddressEntity $billingAddressEntity
     * @return void
     */
    private function setRequestEntityAddress($requestEntity, $shippingAddressEntity, $billingAddressEntity)
    {
        $channelEntity = new ChannelEntity();
        $channelEntity
            ->setName(ChannelSet::CHANNEL_OXID)
            ->setSource($this->getConfigHelper()->getPluginVersion());

        $requestEntity
            ->setChannel($channelEntity)
            ->setShippingAddress($shippingAddressEntity)
            ->setBillingAddress($billingAddressEntity);
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
}
