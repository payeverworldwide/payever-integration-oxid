<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Metadata version
 */

$isOxidV6 = @class_exists('OxidEsales\EshopCommunity\Application\Model\Order');

$sMetadataVersion = '1.2';
/**
 * Module information
 */
$aModule = [
    'id' => 'payever',
    'title' => 'payever',
    'description' => [
        'de' => 'payever Zahlungsmodul',
        'en' => 'payever payment module',
    ],
    'url' => 'https://www.payever.de',
    'email' => 'service@payever.de',
    'thumbnail' => 'payever_logo.png',
    'version' => '2.5.0',
    'author' => 'payever GmbH',
    'extend' => [
        'order'          => 'payever/controllers/payeverorder',
        'payment'        => 'payever/controllers/payeverpayment',
        'order_overview' => 'payever/controllers/admin/payeverorderoverview',
        'order_list'     => 'payever/controllers/admin/payeverorderlist',
        'oxorder'        => $isOxidV6 ? 'payever/models/payeveroxordercompatible' : 'payever/models/payeveroxorder',
        'oxpayment'      => 'payever/models/payeveroxpayment',
        'oxpaymentlist'  => 'payever/models/payeveroxpaymentlist',
        'oxuser'         => 'payever/models/payeveroxuser',
        'oxviewconfig'   => 'payever/core/payeveroxviewconfig',
    ],
    'files' => [
        // classes map (OXID < 6.0 doesn't support composer autoloading)
        'payever_config' => 'payever/controllers/admin/payever_config.php',
        'payeverStandardDispatcher' => 'payever/controllers/payeverstandarddispatcher.php',
        'PayeverInstaller' => 'payever/classes/PayeverInstaller.php',
        'payevercheckorder' => 'payever/controllers/payevercheckorder.php',
        'PayeverUtil' => 'payever/classes/PayeverUtil.php',
        'PayeverLock' => 'payever/classes/PayeverLock.php',
        'PayeverConfig' => 'payever/classes/PayeverConfig.php',
        'PayeverLogger' => 'payever/classes/PayeverLogger.php',
        'PayeverApi' => 'payever/classes/Api/PayeverApi.php',
        'PayeverApiToken' => 'payever/classes/Api/PayeverApiToken.php',
        'PayeverApiTokenList' => 'payever/classes/Api/PayeverApiTokenList.php',
        'PayeverApiConfiguration' => 'payever/classes/Api/PayeverApiConfiguration.php',
        'PayeverApiClientThrowerDecorator' => 'payever/classes/Api/PayeverApiClientThrowerDecorator.php',
        'PayeverApiClientLoggerDecorator' => 'payever/classes/Api/PayeverApiClientLoggerDecorator.php',
    ],
    'events' => [
        /**
         * @see PayeverInstaller::onActivate()
         */
        'onActivate' => 'PayeverInstaller::onActivate',
        /**
         * @see PayeverInstaller::onDeactivate()
         */
        'onDeactivate' => 'PayeverInstaller::onDeactivate',
    ],
    'blocks' => [
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => '/views/blocks/azure/page/checkout/payment/select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/order.tpl',
            'block' => 'checkout_order_details',
            'file' => '/views/blocks/payever_order_iframe.tpl',
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
        // frontend tpl
        'payever_payment_iframe.tpl' => 'payever/views/page/checkout/payment_iframe.tpl',
    ],
];
