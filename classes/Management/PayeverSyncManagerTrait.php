<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverSyncManagerTrait
{
    /** @var PayeverSyncManager */
    protected $syncManager;

    /**
     * @param PayeverSyncManager $syncManager
     * @return $this
     */
    public function setSyncManager(PayeverSyncManager $syncManager)
    {
        $this->syncManager = $syncManager;

        return $this;
    }

    /**
     * @return PayeverSyncManager
     * @codeCoverageIgnore
     */
    protected function getSyncManager()
    {
        return null === $this->syncManager
            ? $this->syncManager = new PayeverSyncManager()
            : $this->syncManager;
    }
}
