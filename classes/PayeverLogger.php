<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

class PayeverLogger
{
    const DATETIME_FORMAT = 'd.m.Y H:i:s';

    const ERROR = 400;

    const INFO = 200;

    const DEBUG = 100;

    const LEVEL_UNKNOWN = 'UNKNOWN';

    protected $levelMap = [
        self::ERROR => 'ERROR',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
    ];

    /** @var int  */
    protected $currentLevel;

    /** @var string */
    private $filename;

    /**
     * PayeverLogger constructor.
     *
     * @param string $filename
     * @param int $level
     */
    public function __construct($filename, $level)
    {
        $this->filename = $filename;
        $this->currentLevel = (int) $level;
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, $context = [])
    {
        if ($level >= $this->currentLevel) {
            $str = sprintf(
                "[%s] %s: %s %s" . PHP_EOL,
                date(static::DATETIME_FORMAT),
                $this->levelToString($level),
                $message,
                json_encode($context, JSON_UNESCAPED_SLASHES)
            );

            file_put_contents($this->filename, $str, FILE_APPEND | LOCK_EX);
        }
    }

    private function levelToString($level)
    {
        return isset($this->levelMap[$level]) ? $this->levelMap[$level] : static::LEVEL_UNKNOWN;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, $context = [])
    {
        $this->log(static::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, $context = [])
    {
        $this->log(static::DEBUG, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, $context = [])
    {
        $this->log(static::ERROR, $message, $context);
    }
}
