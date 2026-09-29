<?php
/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

// Detect OXID major version before declaring metadata version.
// This must happen at the top so $sMetadataVersion can be set conditionally.
$oxidVersion = 4;
if (class_exists('OxidEsales\Eshop\Core\ShopVersion')) {
    $oxidVersion = (int) mb_substr(OxidEsales\Eshop\Core\ShopVersion::getVersion(), 0, 1);
}

/**
 * Metadata version:
 * - 1.2 for OXID 4/5: old-style module-relative file-path extends, no controllers key.
 * - 2.0 for OXID 6+: enables the 'controllers' key. MetaDataSchemaValidator in OXID 6.4+
 *   rejects 'controllers' in v1.2, causing module activation failure. OXID 6.8 still uses
 *   old-style slash-separated extends (e.g. 'payever/core/payeveroxviewconfig') because
 *   ModuleChainsGenerator::backwardsCompatibleCreateClassExtension() builds the file path
 *   from them — bare class names as values would fail. Only OXID 7+ uses FQCN extends.
 */
$sMetadataVersion = $oxidVersion >= 6 ? '2.0' : '1.2';

/**
 * Module information
 */
$aModule = [
    'id' => 'payever',
    'title' => 'payever Checkout',
    'description' => [
        'de' => 'Von Finanzierung & Rechnungs-/Ratenkauf bis hin zu Kreditkartenzahlung, PayPal und andere Wallets, lokale Zahlarten sowie Sofort-Banküberweisung auf Basis von Open-Banking-Technologie – alles ohne zusätzliche Kosten bei einfachster Integration und schnellem Onboarding.',
        'en' => 'From Financing & Buy Now Pay Later products to Cards, PayPal and other Wallets, different local payment methods or Open Banking Payments - everything without additional costs and with an easy integration as well as fast onboarding.',
    ],
    'url' => 'https://www.payever.de',
    'email' => 'service@payever.de',
    'thumbnail' => 'payever_logo.png',
    'version' => '4.0.0',
    'author' => 'payever GmbH',
    // Old-style module-relative file-path extends for OXID 4/5/6.
    // OXID 7+: replaced with FQCN extends at the bottom of this file.
    'extend' => [
        'order'        => 'payever/controllers/payeverorder',
        'order_list'   => 'payever/controllers/admin/payeverorderlist',
        'payment'      => 'payever/controllers/payeverpayment',
        'user'         => 'payever/controllers/payeverusercontroller',
        'account_user' => 'payever/controllers/payeverusercontroller',
        'oxviewconfig' => 'payever/core/payeveroxviewconfig',
        'oxarticle'    => 'payever/models/payeveroxarticle',
        'oxorder'      => 'payever/models/payeveroxorder',
        'oxpayment'    => 'payever/models/payeveroxpayment',
        'oxpaymentlist' => 'payever/models/payeveroxpaymentlist',
        'OxidEsales\\Eshop\\Application\\Model\\User\\UserUpdatableFields' => 'payever/models/user/userupdatablefields',
    ],
    'events' => [
        'onActivate'   => 'PayeverInstaller::onActivate',
        'onDeactivate' => 'PayeverInstaller::onDeactivate',
    ],
    'blocks' => [
        [
            'template' => 'page/checkout/basket.tpl',
            'block' => 'checkout_basket_main',
            'file' => '/views/blocks/page/details/inc/productmain.tpl',
        ],
        [
            'template' => 'page/details/inc/productmain.tpl',
            'block' => 'details_productmain_tobasket',
            'file' => '/views/blocks/page/details/inc/productmain.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => '/views/blocks/azure/page/checkout/payment/select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'change_payment',
            'file' => '/views/blocks/azure/page/checkout/payment/change_payment.tpl',
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'checkout_order_details',
            'file' => '/views/blocks/payever_order_iframe.tpl',
        ],
        [
            'template' => 'form/fieldset/user_billing.tpl',
            'block' => 'form_user_billing_country',
            'file' => '/views/blocks/payever_user_billing_company.tpl',
        ],
        [
            'template' => 'order_list.tpl',
            'block' => 'admin_order_list_filter',
            'file' => '/views/blocks/payever_list_filter_actions.tpl',
        ],
        [
            'template' => 'order_list.tpl',
            'block' => 'admin_order_list_sorting',
            'file' => '/views/blocks/payever_list_sorting_actions.tpl',
        ],
        [
            'template' => 'order_list.tpl',
            'block' => 'admin_order_list_item',
            'file' => '/views/blocks/payever_list_items_actions.tpl',
        ],
        [
            'template' => 'order_list.tpl',
            'block' => 'admin_order_list_colgroup',
            'file' => '/views/blocks/payever_list_colgroup_actions.tpl',
        ],
        [
            'template' => 'payment_main.tpl',
            'block' => 'admin_payment_main_form',
            'file' => '/views/blocks/payever_payment_main.tpl',
        ],
    ],
    'templates' => [
        // backend tpl
        'payever_config.tpl' => 'payever/views/admin/tpl/payever_config.tpl',
        'payever/order/tab.tpl' => 'payever/views/admin/tpl/order/tab.tpl',
        'payever/order/info.tpl' => 'payever/views/admin/tpl/order/info.tpl',
        'payever/order/action_cancel.tpl' => 'payever/views/admin/tpl/order/action_cancel.tpl',
        'payever/order/action_capture.tpl' => 'payever/views/admin/tpl/order/action_capture.tpl',
        'payever/order/action_refund.tpl' => 'payever/views/admin/tpl/order/action_refund.tpl',
        'payever/order/action_claim.tpl' => 'payever/views/admin/tpl/order/action_claim.tpl',
        'payever/order/action_settle.tpl' => 'payever/views/admin/tpl/order/action_settle.tpl',
        'payever/order/action_invoice.tpl' => 'payever/views/admin/tpl/order/action_invoice.tpl',
        'payever/order/form_partial_amount.tpl' => 'payever/views/admin/tpl/order/form_partial_amount.tpl',
        'payever/order/form_partial_items.tpl' => 'payever/views/admin/tpl/order/form_partial_items.tpl',
        'payever/order/form_totals.tpl' => 'payever/views/admin/tpl/order/form_totals.tpl',
        // frontend tpl
        'payever_payment_iframe.tpl' => 'payever/views/page/checkout/payment_iframe.tpl',
        'payever_payment_pending.tpl' => 'payever/views/page/checkout/payment_pending.tpl',
    ],
];

// $oxidVersion is detected at the top of this file (before $sMetadataVersion).

// OXID 6: switch oxorder to the compatibility shim (keeps old-style file-path extend).
if ($oxidVersion === 6) {
    $aModule['extend']['oxorder'] = 'payever/models/payeveroxordercompatible';
}

// OXID 6+: register controllers. MetaDataSchemaValidator in OXID 6.4+ rejects
// 'controllers' in v1.2 metadata; the v2.0 version declared above allows it.
// OXID 6.8 extend block stays old-style (file paths) because backwardsCompatibleCreate-
// ClassExtension() builds the include path from the value — bare class names break it.
if ($oxidVersion >= 6) {
    $aModule['controllers'] = [
        'payever_config'              => 'payever_config',
        'payeverordertab'             => 'payeverordertab',
        'payeverproductsexport'       => 'payeverproductsexport',
        'payeverstandarddispatcher'   => 'payeverstandarddispatcher',
        'payeverexpressdispatcher'    => 'payeverexpressdispatcher',
        'payeverfinexpressdispatcher' => 'payeverfinexpressdispatcher',
        'payevercompanysearch'        => 'payevercompanysearch',
        'payeverpaymentpending'       => 'payeverpaymentpending',
        'payeverproductsimport'       => 'payeverproductsimport',
        'payevershowlogs'             => 'payevershowlogs',
        'payeverclaim'                => 'payeverclaim',
    ];
}

// OXID 7+: replace the entire extend block with FQCN class names (required by
// ModuleChainsGenerator's non-backwards-compatible path via Composer autoloader).
if ($oxidVersion >= 7) {
    $aModule['extend'] = [
        \OxidEsales\Eshop\Application\Controller\OrderController::class            => payeverOrder::class,
        \OxidEsales\Eshop\Application\Controller\PaymentController::class          => payeverPayment::class,
        \OxidEsales\Eshop\Application\Controller\Admin\OrderList::class            => payeverOrderList::class,
        \OxidEsales\Eshop\Application\Model\Order::class                           => payeverOxOrderCompatible::class,
        \OxidEsales\Eshop\Application\Model\Payment::class                         => payeverOxPayment::class,
        \OxidEsales\Eshop\Application\Model\PaymentList::class                     => payeverOxPaymentList::class,
        \OxidEsales\Eshop\Core\ViewConfig::class                                   => payeveroxviewconfig::class,
        \OxidEsales\Eshop\Application\Model\Article::class                         => payeverOxArticle::class,
        \OxidEsales\Eshop\Application\Model\User\UserUpdatableFields::class        => userupdatablefields::class,
        \OxidEsales\Eshop\Application\Controller\UserController::class             => payeverusercontroller::class,
        \OxidEsales\Eshop\Application\Controller\AccountUserController::class      => payeverusercontroller::class,
    ];
}
