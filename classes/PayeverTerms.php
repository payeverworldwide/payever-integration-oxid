<?php

class PayeverTerms
{
    const MODE_LIVE = 'production';
    const MODE_STAGE = 'stage';

    const LIVE_TERMS_JS = 'https://checkout.payever.org/terms.public.js';
    const STAGE_TERMS_JS = 'https://checkout.staging.devpayever.com/terms.public.js';

    /**
     * @param array $paymentMethods
     *
     * @return array
     */
    public static function collectPaymentOptionsTerms($paymentMethods)
    {
        $termsMethods = array_filter($paymentMethods, function ($method) {
            return strpos($method->getId(), 'resurs_installment') !== false;
        });

        if (!$termsMethods) {
            return [];
        }

        $cart = oxNew('oxSession')->getBasket();

        $token = self::getToken();
        $locale = self::getLocale();
        $env = self::getEnvironment();
        $country = self::getCountry($cart);

        $orderHelper = new PayeverOrderHelper();
        $amount = oxRegistry::getUtils()->fRound($orderHelper->getAmountByCart($cart));

        $paymentTerms = [
            'data' => [],
            'terms_js' => $env === self::MODE_LIVE ? self::LIVE_TERMS_JS : self::STAGE_TERMS_JS,
        ];

        foreach ($paymentMethods as $method) {
            $oxvariants = null;
            if ($method->getFieldData('oxvariants')) {
                $oxvariants = json_decode(html_entity_decode($method->getFieldData('oxvariants')));
            }

            $paymentTerms['data'][] = [
                'env' => [
                    'locale' => $locale,
                    'environment' => $env ,
                ],
                'payment_key' => $method->getId(),
                'payment' => [
                    'paymentMethod' => PayeverConfigHelper::removeMethodPrefix($method->getId()),
                    'connectionId' => $oxvariants ? $oxvariants->variantId : null,
                    'amount' => $amount,
                    'country' => $country,
                    'accessToken' => $token,
                    'styles' => [
                        'backgroundColor' => 'transparent',
                        'fontColor' => '#751d1d',
                        'buttonColor' => 'black',
                        'padding' => '1px 10px',
                    ],
                ]
            ];
        }

        return $paymentTerms;
    }

    /**
     * @param $cart
     *
     * @return string
     */
    private static function getCountry($cart)
    {
        $user = $cart->getBasketUser();
        if (!$user) {
            return 'DE';
        }

        try {
            $countryFactory = new PayeverCountryFactory();

            $oxCountry = $countryFactory->create();
            $oxCountry->load($user->getFieldData('oxcountryid'));

            return $oxCountry->getFieldData('oxisoalpha2');
        } catch (\Exception $e) {
            return 'DE';
        }
    }

    /**
     * @return string
     */
    private static function getToken()
    {
        try {
            return PayeverApiClientProvider::getPaymentsApiClient()
                ->getToken()
                ->getAccessToken();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    private static function getEnvironment()
    {
        return PayeverConfig::getApiMode() === PayeverConfig::API_MODE_LIVE
            ? self::MODE_LIVE
            : self::MODE_STAGE;
    }

    /**
     * @return false|string
     */
    private static function getLocale()
    {
        $language = PayeverConfig::getLanguage() ?: substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if (\PayeverConfig::LOCALE_STORE_VALUE === $language) {
            $language = oxregistry::getLang()->getLanguageAbbr();
        }

        return strtolower($language);
    }
}
