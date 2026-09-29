<?php
/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

$internalVendorAutoloadFile = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Guard against double-loading: OXID registers this file both as a Composer package
// (root vendor autoload) and via the OXID module autoload system (source/modules copy).
// The two copies share identical vendor hashes, causing a fatal composerRequired redeclaration.
if (file_exists($internalVendorAutoloadFile) && !defined('PAYEVER_VENDOR_AUTOLOADED')) {
    define('PAYEVER_VENDOR_AUTOLOADED', true);
    require_once $internalVendorAutoloadFile;
}
