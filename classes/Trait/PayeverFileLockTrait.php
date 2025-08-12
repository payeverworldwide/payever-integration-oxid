<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Lock\FileLock;
use Payever\Sdk\Core\Lock\LockInterface;

trait PayeverFileLockTrait
{
    /** @var LockInterface */
    private $locker;

    /**
     * @return FileLock|LockInterface
     * @codeCoverageIgnore
     */
    protected function getLocker()
    {
        return null === $this->locker
            ? $this->locker = new FileLock(rtrim(oxRegistry::getConfig()->getLogsDir(), '/'))
            : $this->locker;
    }

    /**
     * @param LockInterface $locker
     * @return $this
     * @internal
     */
    public function setLocker(LockInterface $locker)
    {
        $this->locker = $locker;

        return $this;
    }
}
