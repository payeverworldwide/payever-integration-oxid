<?php

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Config;

class ExpressWidget
{
    use DryRunTrait;

    const WIDGET_ACTIVE = true;
    const LIVE_WIDGET_JS = 'https://widgets.payever.org/finance-express/widget.min.js';
    const STAGE_WIDGET_JS = 'https://widgets.staging.devpayever.com/finance-express/widget.min.js';

    const ACTION_SUCCESS = 'success';
    const ACTION_FAILURE = 'failure';
    const ACTION_NOTICE = 'notice';
    const ACTION_CANCEL = 'cancel';
    const ACTION_QUOTE_CALLBACK = 'quoteCallback';
    const CART_PREFIX = 'cart_';
    const PRODUCT_PREFIX = 'prod_';

    /**
     * @var Config|null
     */
    private $config;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var int|float
     */
    private $amount = 0;

    /**
     * @var array
     */
    private $cart = [];

    /**
     * @var string
     */
    private $widgetId;

    /**
     * @var string
     */
    private $widgetTheme;

    /**
     * @var string
     */
    private $checkoutId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $bussinessId;

    /**
     * @var string
     */
    private $productId;

    /**
     * @var string
     */
    private $successUrl;

    /**
     * @var string
     */
    private $failureUrl;

    /**
     * @var string
     */
    private $noticeUrl;

    /**
     * @var string
     */
    private $cancelUrl;

    /**
     * @var string
     */
    private $quoteCallbackUrl;

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        if (isset($args['articleNumber'])) {
            $this->productId = $args['articleNumber'];
            $this->prepareWidgetProduct($this->productId);

            return;
        }
        $this->prepareWidgetCart();
    }

    /**
     * @return boolean
     */
    public static function isWidgetActive()
    {
        return self::WIDGET_ACTIVE;
    }

    /**
     * @return array
     */
    private function getQuoteCallbackProductItems()
    {
        $items = [];
        foreach ($this->cart as $product) {
            $items[$product['identifier']] = $product['quantity'];
        }

        return $items;
    }

    /**
     * @param $productId
     *
     * @return void
     */
    private function prepareWidgetProduct($productId)
    {
        $product         = $this->getProductByNumber($productId);
        $productTitle    = $product->oxarticles__oxtitle->rawValue;
        $productPrice    = (float) $product->oxarticles__oxprice->rawValue;

        $this->reference = time() . '_' . self::PRODUCT_PREFIX . $productId;
        $this->amount    = $productPrice;
        $this->cart      = [
            [
                'amount'     => $productPrice,
                'identifier' => $productId,
                'name'       => $productTitle,
                'price'      => $productPrice,
                'quantity'   => 1,
            ],
        ];
    }

    /**
     * @return void
     */
    private function prepareWidgetCart()
    {
        $session = oxRegistry::getSession();
        $basket = $session->getBasket();
        $aBasketContents = $basket->getContents();

        $this->reference = time() . '_' . self::CART_PREFIX . uniqid();
        $oUser = $basket->getBasketUser();
        if ($oUser) {
            $this->reference = time() . '_' . self::CART_PREFIX . $oUser->getBasket('savedbasket')->getId();
        }

        $this->amount = 0;
        foreach ($aBasketContents as $item) {
            $this->amount    += (float) $item->getUnitPrice()->getBruttoPrice() * (int) $item->getAmount();
            $this->cart[]    = [
                'name'       => $item->getTitle(),
                'quantity'   => (int) $item->getAmount(),
                'identifier' => $item->getArticle()->getFieldData('oxartnum'),
                'price'      => $item->getUnitPrice()->getBruttoPrice(),
                'amount'     => $item->getUnitPrice()->getBruttoPrice() * (int) $item->getAmount(),
            ];
        }
        if (method_exists($session->getBasket(), 'enableSaveToDataBase')) {
            $session->getBasket()->enableSaveToDataBase();
        }
    }

    /**
     * @return array
     */
    private function getHtmlEntitiesArray()
    {
        $widgets = PayeverConfig::getWidgets();
        $widgetIds = PayeverConfig::getFinanceExpressWidgetId();
        $widgetTheme = PayeverConfig::getFinanceExpressWidgetTheme();

        $this->successUrl        = $this->generateCallbackUrl(self::ACTION_SUCCESS);
        $this->failureUrl        = $this->generateCallbackUrl(self::ACTION_FAILURE);
        $this->noticeUrl         = $this->generateCallbackUrl(self::ACTION_NOTICE);
        $this->cancelUrl         = $this->generateCallbackUrl(self::ACTION_CANCEL);
        $this->quoteCallbackUrl  = $this->generateCallbackUrl(
            self::ACTION_QUOTE_CALLBACK,
            ['items' => json_encode($this->getQuoteCallbackProductItems())]
        );

        $widgetDataSources = [];
        foreach ($widgetIds as $key => $widgetCode) {
            $widgetPaymentMethods = null;
            $currentWidgetId = null;
            if (strpos($widgetCode, '#') !== false) {
                $widgetData = explode('#', $widgetCode);
                $currentWidgetId = $widgetData[0];
                $widgetPaymentMethods = explode('+', $widgetData[1]);
            }

            $this->widgetId = $currentWidgetId ?: $widgetCode;
            $this->checkoutId = isset($widgets[$this->widgetId]) ? $widgets[$this->widgetId]['checkout_id'] : '-';
            $this->bussinessId = PayeverConfig::getBusinessUuid();
            $this->type = isset($widgets[$this->widgetId]) ? $widgets[$this->widgetId]['type'] : 'button';

            $currentWidgetData = [
                'data-widgetId'         => $this->widgetId,
                'data-theme'            => $widgetTheme,
                'data-checkoutId'       => $this->checkoutId,
                'data-business'         => $this->bussinessId,
                'data-reference'        => $this->reference,
                'data-amount'           => $this->amount,
                'data-cart'             => htmlspecialchars(json_encode($this->cart), ENT_QUOTES, 'UTF-8'),
                'data-cancelurl'        => $this->cancelUrl,
                'data-failureurl'       => $this->failureUrl,
                'data-pendingurl'       => $this->successUrl,
                'data-successurl'       => $this->successUrl,
                'data-noticeurl'        => $this->noticeUrl,
                'data-quotecallbackurl' => $this->quoteCallbackUrl,
                'data-type'             => $this->type,
            ];

            if ($widgetPaymentMethods) {
                if (count($widgetPaymentMethods) === 1) {
                    $currentWidgetData['data-payment'] = $widgetPaymentMethods[0];
                } else {
                    $filterMethods = array_values(array_diff($widgets[$this->widgetId]['payments'], $widgetPaymentMethods));
                    if (!empty($filterMethods)) {
                        $currentWidgetData['data-filter'] = "['" . implode("','", array_values($filterMethods)) . "']";
                    }
                }
            }

            $widgetDataSources['payever-widget-finexp-' . $key] = $currentWidgetData;
        }

        return $widgetDataSources;
    }

    /**
     * Renders the HTML entities for the widget cart.
     *
     * @return string The rendered HTML entities.
     */
    private function renderHtmlEntities()
    {
        $html = '';
        foreach ($this->getHtmlEntitiesArray() as $key => $htmlEntitiesArray) {
            $attrData = '';
            foreach ($htmlEntitiesArray as $attr => $value) {
                if (!empty($value)) {
                    $attrData .= ' ' . $attr . '="' . $value . '" ';
                }
            }
            $html .= sprintf('<div class="payever-widget-finexp" id="%s" %s></div>', $key, $attrData);
        }

        return $html;
    }

    /**
     * Returns widget js file url
     *
     * @return string
     */
    private function getWidgetJsUrl()
    {
        if (PayeverConfig::getApiMode() == PayeverConfig::API_MODE_SANDBOX) {
            return self::STAGE_WIDGET_JS;
        }

        return self::LIVE_WIDGET_JS;
    }

    /**
     * @return false|string
     */
    public function getWidgetHtml()
    {
        if (!$this->isWidgetActive()) {
            return '';
        }

        if (
            ($this->productId && PayeverConfig::shouldShowExpressWidgetOnProduct()) ||
            PayeverConfig::shouldShowExpressWidgetOnCart()
        ) {
            ob_start();
            ?>
            <div class="payever-widget-wrapper tobasketFunction clear">
                <?php echo $this->renderHtmlEntities(); ?>
                <script>
                    var script = document.createElement('script');
                    script.src = '<?php echo $this->getWidgetJsUrl(); ?>';
                    script.onload = function () {
                        PayeverPaymentWidgetLoader.init(
                            '.payever-widget-finexp',
                        );
                    };
                    document.head.appendChild(script);
                </script>
            </div>
            <?php
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }

        return '';
    }

    /**
     * @param $articleNumber
     *
     * @return oxarticle|string
     */
    private function getProductByNumber($articleNumber)
    {
        $product = oxNew('oxarticle');
        $product->assignRecord($product->buildSelectString(['oxartnum' => $articleNumber]));

        return $product;
    }

    private function generateCallbackUrl($status, $params = [])
    {
        $urlData = [
            'cl'                  => 'payeverfinexpressDispatcher',
            'fnc'                 => 'payeverWidget' . ucfirst($status),
            'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5'),
        ];

        $urlData = array_merge($urlData, $params);
        return $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlData, '', "&");
    }

    /**
     * oxConfig instance getter
     *
     * @return Config
     */
    private function getConfig()
    {
        if (!$this->config) {
            $this->config = oxRegistry::getConfig();
        }

        return $this->config;
    }
}
