<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

class PayeverLock
{
    const LOCKFILE_TIME_LOCK  = 60; //sec
    const LOCKFILE_TIME_SLEEP = 1; //sec
    const LOCKFILE_MAX_LIFETIME = 120; //sec

    private function __construct()
    {
        // only static context allowed
    }

    protected static function getLockFileName($paymentId)
    {
        return oxRegistry::getConfig()->getLogsDir() . $paymentId . ".lock";
    }

    public static function waitForUnlock($paymentId)
    {
        // todo: this lock is not safe
        $filename = static::getLockFileName($paymentId);
        if (file_exists($filename)) {
            if ((time() - filectime($filename)) > self::LOCKFILE_MAX_LIFETIME) {
                static::unlock($paymentId);
            } else {
                $waitingTime = 0;
                while ($waitingTime <= self::LOCKFILE_TIME_LOCK && file_exists($filename)) {
                    $waitingTime += self::LOCKFILE_TIME_SLEEP;
                    sleep(self::LOCKFILE_TIME_SLEEP);
                }
            }
        }
    }

    public static function lockAndBlock($paymentId)
    {
        $lockFile = fopen(static::getLockFileName($paymentId), "w");
        fclose($lockFile);
    }

    public static function unlock($paymentId)
    {
        $fileName = static::getLockFileName($paymentId);
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }
}
