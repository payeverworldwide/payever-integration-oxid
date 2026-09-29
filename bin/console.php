#!/usr/bin/env php
<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

if (getenv('OXID_SOURCE_PATH') && !defined('OXID_SOURCE_PATH')) {
    define('OXID_SOURCE_PATH', getenv('OXID_SOURCE_PATH'));
}

// Search the oxid source in the typical directories
if (!defined('OXID_SOURCE_PATH')) {
    if (file_exists('/var/www/html/source')) {
        define('OXID_SOURCE_PATH', '/var/www/html/source');
    } elseif (file_exists('/app/source')) {
        define('OXID_SOURCE_PATH', '/app/source');
    }
}

if (!defined('OXID_SOURCE_PATH') || !file_exists(OXID_SOURCE_PATH . '/bootstrap.php')) {
    throw new RuntimeException('OXID source path not found');
}

require_once OXID_SOURCE_PATH . '/bootstrap.php';
$pluginFiles = [
    'classes/PayeverLogger.php',
    'classes/PayeverConfig.php',
    'classes/Log/PayeverLoggerTrait.php',
    'classes/Database/PayeverDatabaseTrait.php',
    'commands/PayeverAbstractModuleCommand.php',
    'commands/PayeverModuleActivateCommand.php',
    'commands/PayeverModuleDeactivateCommand.php',
    'commands/PayeverModuleDeactivateCommand.php',
    'commands/PayeverModuleStatusCommand.php',
    'commands/PayeverSubscriptionToggleCommand.php',
    'commands/PayeverSyncQueueConsumeCommand.php',
];
foreach ($pluginFiles as $pluginFile) {
    require_once dirname(__FILE__) . '/../' . $pluginFile;
}

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new PayeverModuleActivateCommand());
$application->add(new PayeverModuleDeactivateCommand());
$application->add(new PayeverModuleStatusCommand());
$application->add(new PayeverSubscriptionToggleCommand());
$application->add(new PayeverSyncQueueConsumeCommand());

$application->run();
