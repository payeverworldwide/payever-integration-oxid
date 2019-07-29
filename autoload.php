<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'lib' . DS . 'Payever' . DS . 'ExternalIntegration' . DS . 'Core' . DS . 'Engine.php';

\Payever\ExternalIntegration\Core\Engine::getLoader();
