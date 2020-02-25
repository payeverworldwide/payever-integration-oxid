<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Logger\FileLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class PayeverConfig
{
    const LOG_FILENAME = 'payever.log';

    const PLUGIN_CODE = 'payever';

    const PLUGIN_PREFIX = 'oxpe_';

    const VAR_LIVE_KEYS = 'payever_live_keys';
    const KEY_IS_LIVE = 'isset_live';

    const VAR_SANDBOX = 'payever_sandbox_url';
    const KEY_SANDBOX_URL = 'payeverSandboxUrl';

    const VAR_LIVE = 'payever_live_url';
    const KEY_LIVE_URL = 'payeverLiveUrl';

    const VAR_CONFIG = 'payever_config';

    const KEY_DEBUG = 'debugMode';
    const KEY_LOG_LEVEL = 'logLevel';

    const KEY_DISPLAY_DESCRIPTION = 'displayPaymentDescription';
    const KEY_DISPLAY_ICON = 'displayPaymentIcon';
    const KEY_DISPLAY_BASKET_ID = 'displayBasketId';

    const KEY_LANGUAGE = 'defaultLanguage';
    const KEY_IS_REDIRECT = 'redirectToPayever';

    const API_MODE_SANDBOX = 0;
    const API_MODE_LIVE = 1;
    const KEY_API_MODE = 'testMode';

    const KEY_API_SLUG = 'slug';
    const KEY_API_CLIENT_ID = 'clientId';
    const KEY_API_CLIENT_SECRET = 'clientSecrect';

    const VAR_PLUGIN_COMMANDS = 'payever_commands';
    const KEY_PLUGIN_COMMAND_TIMESTAMP = 'payever_command_timestamp';

    /** @var oxConfig */
    private static $config;

    /** @var LoggerInterface */
    private static $logger;

    private function __construct()
    {
        // only static context allowed
    }

    /**
     * @param bool $withPrefix
     *
     * @return array
     */
    public static function getMethodsList($withPrefix = true)
    {
        static $methods = [];

        $prefix = static::PLUGIN_PREFIX;

        if (empty($methods)) {
            $oDb = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
            $methods = $oDb->getCol("SELECT `OXID` FROM `oxpayments` WHERE `OXID` LIKE ?;", ["$prefix%"]);
        }

        if ($withPrefix) {
            return $methods;
        }

        return array_map(
            function ($method) use ($prefix) {
                return substr($method, strlen($prefix));
            },
            $methods
        );
    }

    private static function loadConfig()
    {
        if (!static::$config) {
            static::$config = oxRegistry::getConfig();
        }
    }

    /**
     * @param string $varName
     * @param string|null $keyName
     *
     * @return mixed|null
     */
    public static function get($varName, $keyName = null)
    {
        static::loadConfig();

        $data = static::$config->getShopConfVar($varName);

        return $keyName ? (isset($data[$keyName]) ? $data[$keyName] : null) : $data;
    }

    public static function getApiMode()
    {
        return static::get(static::VAR_CONFIG, static::KEY_API_MODE);
    }

    public static function getApiSlug()
    {
        return static::get(static::VAR_CONFIG, static::KEY_API_SLUG);
    }

    public static function getApiClientId()
    {
        return static::get(static::VAR_CONFIG, static::KEY_API_CLIENT_ID);
    }

    public static function getApiClientSecret()
    {
        return static::get(static::VAR_CONFIG, static::KEY_API_CLIENT_SECRET);
    }

    public static function getLanguage()
    {
        return static::get(static::VAR_CONFIG, static::KEY_LANGUAGE);
    }

    public static function getIsRedirect()
    {
        return static::get(static::VAR_CONFIG, static::KEY_IS_REDIRECT);
    }

    public static function getDisplayDescription()
    {
        return static::get(static::VAR_CONFIG, static::KEY_DISPLAY_DESCRIPTION);
    }

    public static function getDisplayIcon()
    {
        return static::get(static::VAR_CONFIG, static::KEY_DISPLAY_ICON);
    }

    public static function getDisplayBasketId()
    {
        return static::get(static::VAR_CONFIG, static::KEY_DISPLAY_BASKET_ID);
    }

    public static function getDebugMode()
    {
        return static::get(static::VAR_CONFIG, static::KEY_DEBUG);
    }

    public static function getCustomSandboxUrl()
    {
        return static::get(static::VAR_SANDBOX, static::KEY_SANDBOX_URL);
    }

    public static function getCustomLiveUrl()
    {
        return static::get(static::VAR_LIVE, static::KEY_LIVE_URL);
    }

    public static function getIsLiveKeys()
    {
        return static::get(static::VAR_LIVE_KEYS, static::KEY_IS_LIVE);
    }

    public static function getPluginCommandTimestamt()
    {
        return static::get(static::VAR_PLUGIN_COMMANDS, static::KEY_PLUGIN_COMMAND_TIMESTAMP);
    }

    public static function getPluginVersion()
    {
        $versions = static::get('aModuleVersions');

        return $versions[static::PLUGIN_CODE];
    }

    public static function getOxidVersion()
    {
        return static::$config->getVersion();
    }

    public static function getOxidVersionInt()
    {
        $version = static::$config->getVersion();
        $version = explode(".", $version);

        return $version[0] . $version[1];
    }

    public static function getLogFilename()
    {
        static::loadConfig();

        return static::$config->getLogsDir() . static::LOG_FILENAME;
    }

    public static function getLoggingLevel()
    {
        $level = static::get(static::VAR_CONFIG, static::KEY_LOG_LEVEL);

        if (!$level || intval($level).'' === (string) $level) {
            // legacy format check
            $level = LogLevel::INFO;
        }

        return $level;
    }

    /**
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        if (!static::$logger) {
            static::$logger = new FileLogger(static::getLogFilename(), static::getLoggingLevel());
        }

        return static::$logger;
    }

    /**
     * @param string $paymentMethod
     * @return bool
     */
    public static function isPayeverPaymentMethod($paymentMethod)
    {
        return strpos($paymentMethod, static::PLUGIN_PREFIX) !== false;
    }
}
