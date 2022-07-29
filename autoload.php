<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

define('DS', DIRECTORY_SEPARATOR);

$internalVendorAutoloadFile = __DIR__ . DS . 'vendor' . DS . 'autoload.php';

if (file_exists($internalVendorAutoloadFile)) {
    require_once $internalVendorAutoloadFile;
}
