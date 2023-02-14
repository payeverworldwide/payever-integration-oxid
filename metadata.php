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
    'title' => 'payever',
    'description' => [
        'de' => 'payever Zahlungsmodul',
        'en' => 'payever payment module',
    ],
    'url' => 'https://www.payever.de',
    'email' => 'service@payever.de',
    'thumbnail' => 'payever_logo.png',
    'version' => '2.16.0',
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
    ],
    'files' => [
        // classes map (OXID < 6.0 doesn't support composer autoloading)
        'DryRunTrait' => 'payever/classes/DryRunTrait.php',
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
        'PayeverArticleFactory' => 'payever/classes/Factory/PayeverArticleFactory.php',
        'PayeverCategoryFactory' => 'payever/classes/Factory/PayeverCategoryFactory.php',
        'PayeverCategoryFactoryTrait' => 'payever/classes/Factory/PayeverCategoryFactoryTrait.php',
        'Object2CategoryFactory' => 'payever/classes/Factory/Object2CategoryFactory.php',
        'PayeverPriceFactory' => 'payever/classes/Factory/PayeverPriceFactory.php',
        'PayeverSynchronizationQueueFactory' => 'payever/classes/Factory/PayeverSynchronizationQueueFactory.php',
        'PayeverSynchronizationQueueListFactory' => 'payever/classes/Factory/PayeverSynchronizationQueueListFactory.php',
        'PayeverSynchronizationQueueFactoryTrait' => 'payever/classes/Factory/PayeverSynchronizationQueueFactoryTrait.php',
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
        'payeversynchronizationqueue' => 'payever/models/payeversynchronizationqueue.php',
        'payeversynchronizationqueuelist' => 'payever/models/payeversynchronizationqueuelist.php',
        'PayeverConfig' => 'payever/classes/PayeverConfig.php',
        'PayeverRegistry' => 'payever/classes/PayeverRegistry.php',
        'PayeverApiOauthTokenList' => 'payever/classes/Api/PayeverApiOauthTokenList.php',
        'PayeverApiClientProvider' => 'payever/classes/Api/PayeverApiClientProvider.php',
        'PayeverPluginCommandExecutor' => 'payever/classes/Api/PayeverPluginCommandExecutor.php',
        'PayeverPluginRegistryInfoProvider' => 'payever/classes/Api/PayeverPluginRegistryInfoProvider.php',
        'PayeverShippingGoodsHandler' => 'payever/classes/Api/PayeverShippingGoodsHandler.php',
        'PayeverGenericManagerTrait' => 'payever/classes/Management/PayeverGenericManagerTrait.php',
        'PayeverCategoryManager' => 'payever/classes/Management/PayeverCategoryManager.php',
        'PayeverGalleryManager' => 'payever/classes/Management/PayeverGalleryManager.php',
        'PayeverPriceManager' => 'payever/classes/Management/PayeverPriceManager.php',
        'PayeverShippingManager' => 'payever/classes/Management/PayeverShippingManager.php',
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
        'PayeverAbstractActionHandler' => 'payever/classes/ActionHandler/PayeverAbstractActionHandler.php',
        'PayeverUpdateProductActionHandler' => 'payever/classes/ActionHandler/PayeverUpdateProductActionHandler.php',
        'PayeverSetInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverSetInventoryActionHandler.php',
        'PayeverAddInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverAddInventoryActionHandler.php',
        'PayeverSubtractInventoryActionHandler' => 'payever/classes/ActionHandler/PayeverSubtractInventoryActionHandler.php',
        'PayeverCreateProductActionHandler' => 'payever/classes/ActionHandler/PayeverCreateProductActionHandler.php',
        'PayeverDeleteProductActionHandler' => 'payever/classes/ActionHandler/PayeverDeleteProductActionHandler.php',
        'PayeverSyncQueueConsumeCommand' => 'payever/commands/PayeverSyncQueueConsumeCommand.php',
        'PayeverAbstractModuleCommand' => 'payever/commands/PayeverAbstractModuleCommand.php',
        'PayeverModuleActivateCommand' => 'payever/commands/PayeverModuleActivateCommand.php',
        'PayeverModuleDeactivateCommand' => 'payever/commands/PayeverModuleDeactivateCommand.php',
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
