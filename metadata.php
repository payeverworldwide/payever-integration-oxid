<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

$isOxidV6 = @class_exists('OxidEsales\EshopCommunity\Application\Model\Order');

/**
 * Metadata version
 */
$sMetadataVersion = '1.2';

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
    'version' => '3.2.0',
    'author' => 'payever GmbH',
    'extend' => [
        'order'          => 'payever/controllers/payeverorder',
        'payment'        => 'payever/controllers/payeverpayment',
        'order_list'     => 'payever/controllers/admin/payeverorderlist',
        'oxorder'        => $isOxidV6 ? 'payever/models/payeveroxordercompatible' : 'payever/models/payeveroxorder',
        'oxpayment'      => 'payever/models/payeveroxpayment',
        'oxpaymentlist'  => 'payever/models/payeveroxpaymentlist',
        'oxviewconfig'   => 'payever/core/payeveroxviewconfig',
        'oxarticle'      => 'payever/models/payeveroxarticle',
        'OxidEsales\Eshop\Application\Model\User\UserUpdatableFields' => 'payever/models/user/userupdatablefields',
        'user' => 'payever/controllers/payeverusercontroller',
        'account_user' => 'payever/controllers/payeverusercontroller',
    ],
    'files' => [
        // classes map (OXID < 6.0 doesn't support composer autoloading)
        'DryRunTrait' => 'payever/classes/DryRunTrait.php',
        'ExpressWidget' => 'payever/classes/ExpressWidget.php',
        'PayeverPaymentsApiClientTrait' => 'payever/classes/Api/PayeverPaymentsApiClientTrait.php',
        'PayeverProductRequestEntity' => 'payever/classes/Api/PayeverProductRequestEntity.php',
        'payever_config' => 'payever/controllers/admin/payever_config.php',
        'PayeverConfigHelperTrait' => 'payever/classes/Helper/PayeverConfigHelperTrait.php',
        'PayeverCartFactory' => 'payever/classes/Factory/PayeverCartFactory.php',
        'PayeverCartFactoryTrait' => 'payever/classes/Factory/PayeverCartFactoryTrait.php',
        'PayeverOrderHelper' => 'payever/classes/Helper/PayeverOrderHelper.php',
        'PayeverOrderHelperTrait' => 'payever/classes/Helper/PayeverOrderHelperTrait.php',
        'PayeverConfigTrait' => 'payever/classes/PayeverConfigTrait.php',
        'PayeverLoggerTrait' => 'payever/classes/Log/PayeverLoggerTrait.php',
        'PayeverDatabaseTrait' => 'payever/classes/Database/PayeverDatabaseTrait.php',
        'PayeverOrderActionHelper' => 'payever/classes/Helper/PayeverOrderActionHelper.php',
        'PayeverOrderActionHelperTrait' => 'payever/classes/Helper/PayeverOrderActionHelperTrait.php',
        'PayeverPaymentActionHelper' => 'payever/classes/Helper/PayeverPaymentActionHelper.php',
        'PayeverPaymentActionHelperTrait' => 'payever/classes/Helper/PayeverPaymentActionHelperTrait.php',
        'PayeverOrderTransactionHelper' => 'payever/classes/Helper/PayeverOrderTransactionHelper.php',
        'PayeverOrderTransactionHelperTrait' => 'payever/classes/Helper/PayeverOrderTransactionHelperTrait.php',
        'PayeverLogProcessor' => 'payever/classes/Logger/Processor/PayeverLogProcessor.php',
        'PayeverAddressFactory' => 'payever/classes/Factory/PayeverAddressFactory.php',
        'PayeverAddressFactoryTrait' => 'payever/classes/Factory/PayeverAddressFactoryTrait.php',
        'PayeverCountryFactory' => 'payever/classes/Factory/PayeverCountryFactory.php',
        'PayeverCountryFactoryTrait' => 'payever/classes/Factory/PayeverCountryFactoryTrait.php',
        'PayeverOrderFactory' => 'payever/classes/Factory/PayeverOrderFactory.php',
        'PayeverOrderFactoryTrait' => 'payever/classes/Factory/PayeverOrderFactoryTrait.php',
        'PayeverPaymentMethodFactory' => 'payever/classes/Factory/PayeverPaymentMethodFactory.php',
        'PayeverPaymentMethodFactoryTrait' => 'payever/classes/Factory/PayeverPaymentMethodFactoryTrait.php',
        'PayeverArticleListCollectionFactory' => 'payever/classes/Factory/PayeverArticleListCollectionFactory.php',
        'PayeverFieldFactory' => 'payever/classes/Factory/PayeverFieldFactory.php',
        'PayeverFieldFactoryTrait' => 'payever/classes/Factory/PayeverFieldFactoryTrait.php',
        'PayeverArticleFactory' => 'payever/classes/Factory/PayeverArticleFactory.php',
        'PayeverCategoryFactory' => 'payever/classes/Factory/PayeverCategoryFactory.php',
        'PayeverCategoryFactoryTrait' => 'payever/classes/Factory/PayeverCategoryFactoryTrait.php',
        'Object2CategoryFactory' => 'payever/classes/Factory/Object2CategoryFactory.php',
        'PayeverPriceFactory' => 'payever/classes/Factory/PayeverPriceFactory.php',
        'PayeverSynchronizationQueueFactory' => 'payever/classes/Factory/PayeverSynchronizationQueueFactory.php',
        'PayeverSynchronizationQueueListFactory' => 'payever/classes/Factory/PayeverSynchronizationQueueListFactory.php',
        'PayeverSynchronizationQueueFactoryTrait' => 'payever/classes/Factory/PayeverSynchronizationQueueFactoryTrait.php',
        'PayeverLogsFactory' => 'payever/classes/Factory/PayeverLogsFactory.php',
        'PayeverLogsListFactory' => 'payever/classes/Factory/PayeverLogsListFactory.php',
        'PayeverCategoryHelper' => 'payever/classes/Helper/PayeverCategoryHelper.php',
        'PayeverCategoryHelperTrait' => 'payever/classes/Helper/PayeverCategoryHelperTrait.php',
        'PayeverRequestHelper' => 'payever/classes/Helper/PayeverRequestHelper.php',
        'PayeverRequestHelperTrait' => 'payever/classes/Helper/PayeverRequestHelperTrait.php',
        'PayeverInstaller' => 'payever/classes/PayeverInstaller.php',
        'PayeverMethodHider' => 'payever/classes/PayeverMethodHider.php',
        'PayeverMethodHiderTrait' => 'payever/classes/PayeverMethodHiderTrait.php',
        'payeverproductsexport' => 'payever/controllers/admin/payeverproductsexport.php',
        'payeverStandardDispatcher' => 'payever/controllers/payeverstandarddispatcher.php',
        'payeverExpressDispatcher' => 'payever/controllers/payeverexpressdispatcher.php',
        'payevercompanysearch' => 'payever/controllers/payevercompanysearch.php',
        'payeverFinexpressDispatcher' => 'payever/controllers/payeverfinexpressdispatcher.php',
        'payeversynchronizationqueue' => 'payever/models/payeversynchronizationqueue.php',
        'payeversynchronizationqueuelist' => 'payever/models/payeversynchronizationqueuelist.php',
        'payeverlogs' => 'payever/models/payeverlogs.php',
        'payeverlogslist' => 'payever/models/payeverlogslist.php',
        'payeverpaymentaction' => 'payever/models/payeverpaymentaction.php',
        'PayeverLogger' => 'payever/classes/PayeverLogger.php',
        'PayeverConfig' => 'payever/classes/PayeverConfig.php',
        'PayeverRegistry' => 'payever/classes/PayeverRegistry.php',
        'PayeverApiOauthTokenList' => 'payever/classes/Api/PayeverApiOauthTokenList.php',
        'PayeverApiClientProvider' => 'payever/classes/Api/PayeverApiClientProvider.php',
        'PayeverApiApmSecretService' => 'payever/classes/Api/PayeverApiApmSecretService.php',
        'PayeverPluginCommandExecutor' => 'payever/classes/Api/PayeverPluginCommandExecutor.php',
        'PayeverPluginRegistryInfoProvider' => 'payever/classes/Api/PayeverPluginRegistryInfoProvider.php',
        'PayeverShippingGoodsHandler' => 'payever/classes/Api/PayeverShippingGoodsHandler.php',
        'PayeverCompanySearchManager' => 'payever/classes/Management/PayeverCompanySearchManager.php',
        'PayeverGenericManagerTrait' => 'payever/classes/Management/PayeverGenericManagerTrait.php',
        'PayeverCategoryManager' => 'payever/classes/Management/PayeverCategoryManager.php',
        'PayeverGalleryManager' => 'payever/classes/Management/PayeverGalleryManager.php',
        'PayeverPriceManager' => 'payever/classes/Management/PayeverPriceManager.php',
        'PayeverShippingManager' => 'payever/classes/Management/PayeverShippingManager.php',
        'PayeverOrderActionManager' => 'payever/classes/Management/PayeverOrderActionManager.php',
        'PayeverCaptureManager' => 'payever/classes/Management/PayeverCaptureManager.php',
        'PayeverCancelManager' => 'payever/classes/Management/PayeverCancelManager.php',
        'PayeverRefundManager' => 'payever/classes/Management/PayeverRefundManager.php',
        'PayeverOrderActionValidator' => 'payever/classes/Validator/PayeverOrderActionValidator.php',
        'PayeverOrderActionManagerTrait' => 'payever/classes/Management/PayeverOrderActionManagerTrait.php',
        'PayeverActionQueueManager' => 'payever/classes/Management/PayeverActionQueueManager.php',
        'PayeverActionQueueManagerTrait' => 'payever/classes/Management/PayeverActionQueueManagerTrait.php',
        'PayeverSubscriptionManager' => 'payever/classes/Management/PayeverSubscriptionManager.php',
        'PayeverSubscriptionManagerTrait' => 'payever/classes/Management/PayeverSubscriptionManagerTrait.php',
        'PayeverExportManager' => 'payever/classes/Management/PayeverExportManager.php',
        'PayeverOptionManager' => 'payever/classes/Management/PayeverOptionManager.php',
        'PayeverConfigHelper' => 'payever/classes/Helper/PayeverConfigHelper.php',
        'PayeverSeoHelper' => 'payever/classes/Helper/PayeverSeoHelper.php',
        'PayeverProductHelper' => 'payever/classes/Helper/PayeverProductHelper.php',
        'PayeverProductHelperTrait' => 'payever/classes/Helper/PayeverProductHelperTrait.php',
        'PayeverProductTransformer' => 'payever/classes/Transformer/PayeverProductTransformer.php',
        'PayeverProductTransformerTrait' => 'payever/classes/Transformer/PayeverProductTransformerTrait.php',
        'PayeverInventoryTransformer' => 'payever/classes/Transformer/PayeverInventoryTransformer.php',
        'PayeverInventoryTransformerTrait' => 'payever/classes/Transformer/PayeverInventoryTransformerTrait.php',
        'PayeverProductsIterator' => 'payever/classes/Iterator/PayeverProductsIterator.php',
        'PayeverInventoryIterator' => 'payever/classes/Iterator/PayeverInventoryIterator.php',
        'PayeverSynchronizationManager' => 'payever/classes/Management/PayeverSynchronizationManager.php',
        'PayeverSynchronizationManagerTrait' => 'payever/classes/Management/PayeverSynchronizationManagerTrait.php',
        'PayeverImportManager' => 'payever/classes/Management/PayeverImportManager.php',
        'payeverProductsImport' => 'payever/controllers/payeverproductsimport.php',
        'payeverShowLogs' => 'payever/controllers/payevershowlogs.php',
        'payeverpaymentpending' => 'payever/controllers/payeverpaymentpending.php',
        'PayeverAbstractActionHandler' => 'payever/classes/ActionHandler/PayeverAbstractActionHandler.php',
        'PayeverUpdateProductActionHandler' => 'payever/classes/ActionHandler/PayeverUpdateProductActionHandler.php',
        'PayeverSetInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverSetInventoryActionHandler.php',
        'PayeverAddInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverAddInventoryActionHandler.php',
        'PayeverSubtractInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverSubtractInventoryActionHandler.php',
        'PayeverCreateProductActionHandler' => 'payever/classes/ActionHandler/PayeverCreateProductActionHandler.php',
        'PayeverDeleteProductActionHandler' => 'payever/classes/ActionHandler/PayeverDeleteProductActionHandler.php',
        'PayeverPaymentUrlBuilder' => 'payever/classes/Payment/PayeverPaymentUrlBuilder.php',
        'PayeverPaymentV3RequestBuilder' => 'payever/classes/Payment/PayeverPaymentV3RequestBuilder.php',
        'PayeverSyncQueueConsumeCommand' => 'payever/commands/PayeverSyncQueueConsumeCommand.php',
        'PayeverAbstractModuleCommand' => 'payever/commands/PayeverAbstractModuleCommand.php',
        'PayeverModuleActivateCommand' => 'payever/commands/PayeverModuleActivateCommand.php',
        'PayeverModuleDeactivateCommand' => 'payever/commands/PayeverModuleDeactivateCommand.php',

        'payeverordertab' => 'payever/controllers/admin/payeverordertab.php',
        'payeverorderaction' => 'payever/models/payeverorderaction.php',
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
			'template' => 'page/checkout/basket.tpl',
			'block'    => 'checkout_basket_main',
			'file'     => '/views/blocks/page/details/inc/productmain.tpl'
		],
		[
			'template' => 'page/details/inc/productmain.tpl',
			'block'    => 'details_productmain_tobasket',
			'file'     => '/views/blocks/page/details/inc/productmain.tpl'
		],
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
        'payever/order/refund.tpl' => 'payever/views/admin/tpl/order/refund.tpl',
        'payever/order/action.tpl' => 'payever/views/admin/tpl/order/action.tpl',
        // frontend tpl
        'payever_payment_iframe.tpl' => 'payever/views/page/checkout/payment_iframe.tpl',
        'payever_payment_pending.tpl' => 'payever/views/page/checkout/payment_pending.tpl',
    ],
];
