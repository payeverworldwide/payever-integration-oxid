<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\MessageEntity\GetCurrenciesResultEntity;
use Payever\ExternalIntegration\Core\Http\Response;
use Payever\ExternalIntegration\Core\Http\ResponseEntity\GetCurrenciesResponse;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

class PayeverPriceManager
{
    use PayeverConfigTrait;
    use PayeverGenericManagerTrait;
    use PayeverPaymentsApiClientTrait;

    const DEFAULT_CURRENCY_ISO_CODE = 'EUR';
    const DEFAULT_VAT_RATE = 7.00;

    /** @var PayeverPriceFactory */
    protected $priceFactory;

    /** @var GetCurrenciesResultEntity[] */
    private $currenciesResultEntityListCache;

    /**
     * @return string
     */
    public function getCurrencyName()
    {
        $currency = $this->getCurrency();

        return !empty($currency->name) ? (string) $currency->name : 'N/A';
    }

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity $requestEntity
     * @throws oxSystemComponentException
     */
    public function setPrice($product, ProductRequestEntity $requestEntity)
    {
        $sourceIsoCode = $requestEntity->getCurrency()
            ? strtoupper($requestEntity->getCurrency())
            : self::DEFAULT_CURRENCY_ISO_CODE;
        $price = (float) $requestEntity->getPrice();
        $salesPrice = $requestEntity->getOnSales() ? (float) $requestEntity->getSalePrice() : null;
        $vatRate = $this->getVatFromRequest($requestEntity);
        $currencyRate = $this->getCurrencyRate($sourceIsoCode);
        $priceCarrier = $this->getPriceFactory()->create();
        $priceCarrier->setBruttoPriceMode();
        $priceCarrier->setVat($vatRate);
        $priceCarrier->setPrice($price);
        $priceCarrier->multiply($currencyRate);
        $data = [
            'oxprice' => $priceCarrier->getPrice(),
            'oxtprice' => null,
        ];
        if (null !== $salesPrice) {
            $salesPriceCarrier = $this->getPriceFactory()->create();
            $salesPriceCarrier->setBruttoPriceMode();
            $salesPriceCarrier->setVat($vatRate);
            $salesPriceCarrier->setPrice($salesPrice);
            $salesPriceCarrier->multiply($currencyRate);
            $data['oxprice'] = $salesPriceCarrier->getPrice();
            $data['oxtprice'] = $priceCarrier->getPrice();
        }
        $product->assign($data);
        unset($_POST['currency']);
    }

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity $requestEntity
     */
    public function setVat($product, ProductRequestEntity $requestEntity)
    {
        $product->assign(['oxvat' => $this->getVatFromRequest($requestEntity)]);
    }

    /**
     * @param ProductRequestEntity $requestEntity
     * @return float
     */
    protected function getVatFromRequest(ProductRequestEntity $requestEntity)
    {
        return $requestEntity->getVatRate() ? (float) $requestEntity->getVatRate() : self::DEFAULT_VAT_RATE;
    }

    /**
     * @param string $currencyIsoCode
     * @return float
     * @throws Exception
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function getCurrencyRate($currencyIsoCode)
    {
        if ($this->getConfigHelper()->isOxidCurrencyRateSource()) {
            $currencyCode = 0; // Default EUR
            if ($currencyIsoCode === 'GBP') {
                $currencyCode = 1;
            } elseif ($currencyIsoCode === 'CHF') {
                $currencyCode = 2;
            }
            $_POST['currency'] = $currencyCode;
            $currencyObject = $this->getCurrency();
            $rate = !empty($currencyObject->rate) && $currencyObject->rate > 0 ? $currencyObject->rate : 1;
        } else {
            $rate = 1.0;
            $result = $this->getCurrenciesResultEntityList();
            $currencyResultEntity = $result[$currencyIsoCode] ?: null;
            if ($currencyResultEntity instanceof GetCurrenciesResultEntity) {
                $currencyRate = $currencyResultEntity->getRate();
                if (null !== $currencyRate) {
                    $rate *= $currencyRate;
                }
            }
        }

        return 1 / $rate;
    }

    /**
     * @return object
     */
    protected function getCurrency()
    {
        return $this->getConfig()->getActShopCurrencyObject();
    }

    /**
     * @return GetCurrenciesResultEntity[]
     * @throws \Exception
     */
    private function getCurrenciesResultEntityList()
    {
        if (null === $this->currenciesResultEntityListCache) {
            /** @var Response $response */
            $response = $this->getPaymentsApiClient()->getCurrenciesRequest();
            /** @var GetCurrenciesResponse $responseEntity */
            $responseEntity = $response->getResponseEntity();
            $this->currenciesResultEntityListCache = $responseEntity->getResult();
        }

        return $this->currenciesResultEntityListCache ?: [];
    }

    /**
     * @param PayeverPriceFactory $priceFactory
     * @return $this
     */
    public function setPriceFactory(PayeverPriceFactory $priceFactory)
    {
        $this->priceFactory = $priceFactory;

        return $this;
    }

    /**
     * @return PayeverPriceFactory
     * @codeCoverageIgnore
     */
    protected function getPriceFactory()
    {
        return null === $this->priceFactory
            ? $this->priceFactory = new PayeverPriceFactory()
            : $this->priceFactory;
    }
}
