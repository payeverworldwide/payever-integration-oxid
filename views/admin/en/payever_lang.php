<?php
/**
 * Payever payment method module
 * This module is used for real time processing of
 * Payever transaction of customers.
 *
 * Copyright (c) payever GmbH
 *
 * Released under the GNU General Public License
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * Script : payever_lang.php
 *
 */

$sLangName = 'English';



$aLang = [
    'charset' => 'UTF-8',
    'PAYEVER' => 'Payever',
    'PAYEVER_CONFIG' => 'General configuration',
    'PAYEVER_LOGO' => 'payever_logo.png',
    'PAYEVER_ADMIN' => 'Login here with Payever merchant credentials. For the activation of new payment methods please contact <a href="mailto:support@payever.de" style="text-decoration: underline; font-weight: bold; color:#fff;">support@payever.de</a>.',
    'PAYEVER_BASIC_CONFIGURATION' => 'payever Global Configuration',
    'PAYEVER_API_CONFIGURATION' => 'API Credentials',
    'PAYEVER_API_APPEARANCE' => 'Appearance and behaviour',
    'PAYEVER_API_PRODUCT_AND_INVENTORY' => 'Products and inventory',
    'PAYEVER_API_LOGGING' => 'Logging',
    'PAYEVER_CLIENTID' => 'Client ID',
    'PAYEVER_CLIENTID_DESCRIPTION' => 'Enter payever Client ID',
    'PAYEVER_CLIENT_SECRECT' => 'Client Secret',
    'PAYEVER_CLIENT_SECRECT_DESCRIPTION' => 'Enter payever Client Secret',
    'PAYEVER_SLUG' => 'Business UUID',
    'PAYEVER_SLUG_DESCRIPTION' => 'Enter payever Business UUID',
    'PAYEVER_TEST_MODE_DESCRIPTION' => 'Choose the mode',
    'PAYEVER_LOG_LEVEL' => 'Logging level',
    'PAYEVER_LOG_ERRORS' => 'Errors only',
    'PAYEVER_LOG_INFO' => 'Info',
    'PAYEVER_LOG_DEBUG' => 'Debug',
    'PAYEVER_LOG_FILEPATH' => 'You can find the log file here',
    'PAYEVER_TEST_MODE' => 'Choose the mode',
    'PAYEVER_DISPLAY_ICON' => 'Display payment icon',
    'PAYEVER_DISPLAY_DESCRIPTION' => 'Display payment description',
    'PAYEVER_IS_REDIRECT' => 'Redirect to payever',
    'PAYEVER_DISPLAY_BASKET_ID' => 'Display "Reference" in order grid',
    'PAYEVER_PRODUCTS_SYNC_ENABLED' => 'Products synchronization enabled',
    'PAYEVER_PRODUCTS_OUTWARD_SYNC_ENABLED' => 'Export changes to payever',
    'PAYEVER_PRODUCTS_SYNC_MODE' => 'Processing mode',
    'PAYEVER_PRODUCTS_SYNC_MODE_INSTANT' => 'Instantly on HTTP requests',
    'PAYEVER_PRODUCTS_SYNC_MODE_CRON' => 'Cron queue processing',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE' => 'Currency rate source',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_OXID' => 'oxid',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_PAYEVER' => 'payever',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT' => 'Export products and inventory',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT_CONFIRM' => '"Do you really want to export products and inventory to payever?"',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT_DISABLED' => 'Export of products and inventory is disabled.',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORTED_TOTAL_PRODUCTS' => 'Total successfully exported products:',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORTED_TOTAL_INVENTORY' => 'Total successfully exported inventory:',
    'PAYEVER_SYNCHRONIZE' => 'Synchronize Settings',
    'PAYEVER_ADMIN_ERROR_SYNC' => 'Synchronize settings failed.',
    'PAYEVER_ADMIN_SUCCESS' => 'Settings are saved.',
    'PAYEVER_ADMIN_SUCCESS_SYNC' => 'Settings synchronize success.',
    'PAYEVER_ORDER_BASKETID' => 'Reference',
    'PAYEVER_SYNCHRONIZE_CONFIRM' => '"Do you really want to synchronize payever settings? This will delete associated payment methods in your shipping methods."',
    'PAYEVER_SET_LIVE_CONFIRM' => '"Do you really want to reset the live payever settings? This will delete associated payment methods in your shipping methods."',
    'PAYEVER_SET_SANDBOX_CONFIRM' => '"Do you really want to set sandbox payever settings? This will delete associated payment methods in your shipping methods."',
    'PAYEVER_SET_SANDBOX' => 'Set up sandbox API keys',
    'PAYEVER_DOWNLOAD_LOG' => 'Download log file',
    'PAYEVER_SET_LIVE' => 'Reset live API keys',
    'PAYEVER_ADMIN_SUCCESS_SET_SANDBOX' => 'Sandbox API keys was set up successfully',
    'PAYEVER_ADMIN_SUCCESS_SET_LIVE' => 'Live API keys was restored successfully',
    'PAYEVER_PAYMENT_ACCEPT_FEE' => 'Accept fee',
    'PAYEVER_PAYMENT_MAIN_FEE' => 'Fee',
    'PAYEVER_PAYMENT_MAIN_PERCENT' => 'fee',
    'PAYEVER_PAYMENT_MAIN_FIXED_FEE' => 'fixed fee',
    'PAYEVER_PAYMENT_MAIN_FEE_SEP' => '%, and',
    'PAYEVER_PAYMENT_IS_REDIRECT_METHOD' => 'Is redirect method',
    'PAYEVER_PAYMENT_NO' => 'No',
    'PAYEVER_PAYMENT_YES' => 'Yes',
    'PAYEVER_LIVE_MODE' => 'Live',
    'PAYEVER_SANDBOX_MODE' => 'Sandbox',
    'PAYEVER_SHOP_ID' => 'Shop ID',
    'PAYEVER_SHOP_ID_DESCRIPTION' => 'Enter shop ID',
    'PAYEVER_DEFAULT_LANGUAGE_TEXT' => 'Default',
    'PAYEVER_DEFAULT_LANGUAGE' => 'Default language on checkout',
    'PAYEVER_EN_TEXT' => 'English',
    'PAYEVER_DE_TEXT' => 'Deutsch',
    'PAYEVER_ES_TEXT' => 'Español',
    'PAYEVER_NO_TEXT' => 'Norsk',
    'PAYEVER_DA_TEXT' => 'Dansk',
    'PAYEVER_SV_TEXT' => 'Svenska',
    'SUBMIT_ORDER' => 'PAYEVER ORDER',
    'PAYEVER_VERSION_MESSAGE' => 'There is a new version of payever module available.',
    'PAYEVER_VERSION_DOWNLOAD' => 'Download',
    'PAYEVER_VERSION_AND_UPDATE' => 'and update now, please!',
    'PAYEVER_CHAT_TITLE' => 'Need help? Chat with us!',
    'PAYEVER_LOADING_CHAT' => 'Loading chat...',
    'PAYEVER_CHAT_DESCRIPTION' => 'Our free english and german speaking support is there for you from Monday to Friday, 8am-7pm. If you want to report a specific technical problem, please include your OXID eShop version and payever plugin version in your message to us, and attach your plugin logs to it (can be downloaded by clicking “Download Logs” button on this page).'
];
