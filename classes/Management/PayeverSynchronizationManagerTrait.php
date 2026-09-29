<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverSynchronizationManagerTrait
{
    /** @var PayeverSynchronizationManager */
    protected $synchronizationManager;

    /**
     * @param PayeverSynchronizationManager $synchronizationManager
     * @return $this
     */
    public function setSynchronizationManager(PayeverSynchronizationManager $synchronizationManager)
    {
        $this->synchronizationManager = $synchronizationManager;

        return $this;
    }

    /**
     * @return PayeverSynchronizationManager
     * @codeCoverageIgnore
     */
    protected function getSynchronizationManager()
    {
        return null === $this->synchronizationManager
            ? $this->synchronizationManager = new PayeverSynchronizationManager()
            : $this->synchronizationManager;
    }
}
