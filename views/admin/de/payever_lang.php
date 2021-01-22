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

$sLangName = 'Deutsch';
// -------------------------------
// RESOURCE IDENTITFIER = STRING
// -------------------------------
$aLang = [
    'charset' => 'UTF-8',
    'PAYEVER' => 'Payever',
    'PAYEVER_CONFIG' => 'Allgemeine Konfiguration',
    'PAYEVER_LOGO' => 'payever_logo.png',
    'PAYEVER_ADMIN' => 'Loggen Sie sich hier mit Ihren Payever H&auml;ndler-Zugangsdaten ein.
    Um neue Zahlungsarten zu aktivieren, kontaktieren Sie bitte <a href="mailto:support@payever.de" style="text-decoration: underline; font-weight: bold; color:#fff;">support@payever.de</a>',
    'PAYEVER_BASIC_CONFIGURATION' => 'payever Allgemeine Konfiguration',
    'PAYEVER_API_CONFIGURATION' => 'API Schlüssel',
    'PAYEVER_API_APPEARANCE' => 'Aussehen und Verhalten',
    'PAYEVER_API_PRODUCT_AND_INVENTORY' => 'Produkte und Inventar',
    'PAYEVER_API_LOGGING' => 'Protokollieren',
    'PAYEVER_CLIENTID' => 'Client ID',
    'PAYEVER_CLIENTID_DESCRIPTION' => 'Client ID Schlüssel',
    'PAYEVER_CLIENT_SECRECT' => 'Client Secret',
    'PAYEVER_CLIENT_SECRECT_DESCRIPTION' => 'Client Secret Schlüssel',
    'PAYEVER_SLUG' => 'Business UUID',
    'PAYEVER_SLUG_DESCRIPTION' => 'Business UUID Schlüssel',
    'PAYEVER_TEST_MODE' => 'Arbeitsmodus',
    'PAYEVER_TEST_MODE_DESCRIPTION' => 'Wählen Sie Modus',
    'PAYEVER_DISPLAY_ICON' => 'Icon der Zahlart sichtbar ',
    'PAYEVER_LOG_LEVEL' => 'Protokollierebene',
    'PAYEVER_LOG_ERRORS' => 'Errors only',
    'PAYEVER_LOG_INFO' => 'Info',
    'PAYEVER_LOG_DEBUG' => 'Debug',
    'PAYEVER_LOG_FILEPATH' => 'Sie finden die Protokolldatei hier',
    'PAYEVER_DISPLAY_DESCRIPTION' => 'Automatische Beschreibung wählen ',
    'PAYEVER_IS_REDIRECT' => 'Zu payever weiterleiten',
    'PAYEVER_DISPLAY_BASKET_ID' => 'Spalte „Referenz“ in Bestellungen anzeigen',
    'PAYEVER_PRODUCTS_SYNC_ENABLED' => 'Produktsynchronisation aktiviert',
    'PAYEVER_PRODUCTS_OUTWARD_SYNC_ENABLED' => 'Exportieren Sie Änderungen an payever',
    'PAYEVER_PRODUCTS_SYNC_MODE' => 'Verarbeitungsmodus',
    'PAYEVER_PRODUCTS_SYNC_MODE_INSTANT' => 'Sofort bei HTTP-Anfragen',
    'PAYEVER_PRODUCTS_SYNC_MODE_CRON' => 'Cron-Warteschlangenverarbeitung',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE' => 'Wechselkursquelle',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_OXID' => 'oxid',
    'PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_PAYEVER' => 'payever',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT' => 'Exportieren Sie Produkte und Inventar',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT_CONFIRM' => '"Willst du wirklich Produkte und Inventar nach payever exportieren?"',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT_DISABLED' => 'Der Export von Produkten und Inventar ist deaktiviert.',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORTED_TOTAL_PRODUCTS' => 'Insgesamt erfolgreich exportierte Produkte:',
    'PAYEVER_PRODUCTS_AND_INVENTORY_EXPORTED_TOTAL_INVENTORY' => 'Insgesamt erfolgreich exportiertes Inventar:',
    'PAYEVER_SYNCHRONIZE' => 'Einstellungen synchronisieren',
    'PAYEVER_ADMIN_ERROR_SYNC' => 'Synchronisieren Einstellungen ist fehlgeschlagen.',
    'PAYEVER_ADMIN_SUCCESS' => 'Einstellungen werden gespeichert.',
    'PAYEVER_ADMIN_SUCCESS_SYNC' => 'Synchronisieren Einstellungen ist der Erfolg.',
    'PAYEVER_ORDER_BASKETID' => 'Referenz',
    'PAYEVER_SYNCHRONIZE_CONFIRM' => '"Willst du wirklich die Einstellungen mit deinem payever Konto synchronisieren? Dabei werden die Zahlarten in den Versandarten gelöscht."',
    'PAYEVER_SET_LIVE_CONFIRM' => '"Willst du wirklich die Live einstellungen mit deinem payever Konto zurücksetzen? Dabei werden die Zahlarten in den Versandarten gelöscht."',
    'PAYEVER_SET_SANDBOX_CONFIRM' => '"Willst du wirklich die Sandbox einstellungen mit deinem payever Konto synchronisieren? Dabei werden die Zahlarten in den Versandarten gelöscht."',
    'PAYEVER_SET_SANDBOX' => 'Richten Sie Sandbox-API-Schlüssel',
    'PAYEVER_SET_LIVE' => 'Setzen Sie die Live-API-Tasten zurück',
    'PAYEVER_DOWNLOAD_LOG' => 'Protokolldatei herunterladen',
    'PAYEVER_ADMIN_SUCCESS_SET_SANDBOX' => 'Sandbox API Schlüssel wurde erfolgreich eingerichtet',
    'PAYEVER_ADMIN_SUCCESS_SET_LIVE' => 'Live-API-Schlüssel wurden erfolgreich wiederhergestellt',
    'PAYEVER_PAYMENT_ACCEPT_FEE' => 'Akzeptieren Gebühr',
    'PAYEVER_PAYMENT_MAIN_FEE' => 'Gebühr',
    'PAYEVER_PAYMENT_MAIN_PERCENT' => 'Variable Gebühr',
    'PAYEVER_PAYMENT_MAIN_FIXED_FEE' => 'Fixgebühr',
    'PAYEVER_PAYMENT_MAIN_FEE_SEP' => '%, und',
    'PAYEVER_PAYMENT_IS_REDIRECT_METHOD' => 'Ist Umleitungsmethode',
    'PAYEVER_PAYMENT_NO' => 'Nein',
    'PAYEVER_PAYMENT_YES' => 'Ja',
    'PAYEVER_LIVE_MODE' => 'Live',
    'PAYEVER_SANDBOX_MODE' => 'Sandbox',
    'PAYEVER_SHOP_ID' => 'Shop ID',
    'PAYEVER_SHOP_ID_DESCRIPTION' => 'Shop ID Schlüssel',
    'PAYEVER_DEFAULT_LANGUAGE_TEXT' => 'Standard',
    'PAYEVER_DEFAULT_LANGUAGE' => 'Standardsprache bei Payever-Auschecken',
    'PAYEVER_EN_TEXT' => 'English',
    'PAYEVER_DE_TEXT' => 'Deutsch',
    'PAYEVER_ES_TEXT' => 'Español',
    'PAYEVER_NO_TEXT' => 'Norsk',
    'PAYEVER_DA_TEXT' => 'Dansk',
    'PAYEVER_SV_TEXT' => 'Svenska',
    'SUBMIT_ORDER' => 'PAYEVER ORDER',
    'PAYEVER_VERSION_MESSAGE' => 'Eine neue Version des payever Plugins ist jetzt verfügbar.',
    'PAYEVER_VERSION_DOWNLOAD' => 'Bitte laden Sie',
    'PAYEVER_VERSION_AND_UPDATE' => 'herunter und aktualisieren Sie Ihr Plugin.',
    'PAYEVER_CHAT_TITLE' => 'Hilfe benötigt? Kontaktiere uns!',
    'PAYEVER_LOADING_CHAT' => 'Chat wird geladen...',
    'PAYEVER_CHAT_DESCRIPTION' => 'Unser kostenloser deutsch- und englisch-sprachiger Tech Support ist Montags bis Freitags zwischen 8 und 19 Uhr für dich da. Wenn du ein bestimmtes technisches Problem melden möchtest, erwähne in deiner Nachricht an uns bitte deine OXID eShop Version und die Version deines payever Plugins. Füge deiner Nachricht bitte auch die Plugin-Logs bei (die Log-Datei kannst du durch Klick auf den "Download Logs" Button auf dieser Seite herunterladen).'
];
