<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverRegistry
{
    const LAST_INWARD_PROCESSED_PRODUCT = 'lastInwardProcessedProduct';

    /** @var array */
    private static $allowedKeys = [
        self::LAST_INWARD_PROCESSED_PRODUCT,
    ];

    /** @var array */
    private static $data = [];

    /**
     * Only static context allowed
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get($key)
    {
        return isset(static::$data[$key]) ? static::$data[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed $val
     */
    public static function set($key, $val)
    {
        if (!in_array($key, static::$allowedKeys, true)) {
            throw new \InvalidArgumentException(sprintf('Key %s is not allowed', $key));
        }

        static::$data[$key] = $val;
    }
}
