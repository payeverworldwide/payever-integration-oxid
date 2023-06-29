<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverSynchronizationQueueFactoryTrait
{
    /** @var PayeverSynchronizationQueueFactory */
    protected $synchronizationQueueFactory;

    /**
     * @param PayeverSynchronizationQueueFactory $synchronizationQueueFactory
     * @return $this
     * @internal
     */
    public function setSynchronizationQueueFactory(PayeverSynchronizationQueueFactory $synchronizationQueueFactory)
    {
        $this->synchronizationQueueFactory = $synchronizationQueueFactory;

        return $this;
    }

    /**
     * @return PayeverSynchronizationQueueFactory
     * @codeCoverageIgnore
     */
    protected function getSynchronizationQueueFactory()
    {
        return null === $this->synchronizationQueueFactory
            ? $this->synchronizationQueueFactory = new PayeverSynchronizationQueueFactory()
            : $this->synchronizationQueueFactory;
    }
}
