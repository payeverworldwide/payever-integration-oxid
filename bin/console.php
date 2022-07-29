#!/usr/bin/env php
<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

if (getenv('OXID_SOURCE_PATH') && !defined('OXID_SOURCE_PATH')) {
    define('OXID_SOURCE_PATH', getenv('OXID_SOURCE_PATH'));
}
defined('OXID_SOURCE_PATH') || define('OXID_SOURCE_PATH', '/var/www/html/source');

require_once OXID_SOURCE_PATH . '/bootstrap.php';
$pluginFiles = [
    'classes/PayeverConfig.php',
    'classes/Log/PayeverLoggerTrait.php',
    'classes/Database/PayeverDatabaseTrait.php',
    'commands/PayeverAbstractModuleCommand.php',
    'commands/PayeverModuleActivateCommand.php',
    'commands/PayeverModuleDeactivateCommand.php',
    'commands/PayeverModuleDeactivateCommand.php',
];
foreach ($pluginFiles as $pluginFile) {
    require_once dirname(__FILE__) . '/../' . $pluginFile;
}

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new PayeverModuleActivateCommand());
$application->add(new PayeverModuleDeactivateCommand());
$application->run();
