<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverActionQueueManagerTrait
{
    /** @var PayeverActionQueueManager */
    protected $actionQueueManager;

    /**
     * @param PayeverActionQueueManager $actionQueueManager
     * @return $this
     * @internal
     */
    public function setActionQueueManager(PayeverActionQueueManager $actionQueueManager)
    {
        $this->actionQueueManager = $actionQueueManager;

        return $this;
    }

    /**
     * @return PayeverActionQueueManager
     * @codeCoverageIgnore
     */
    protected function getActionQueueManager()
    {
        return null === $this->actionQueueManager
            ? $this->actionQueueManager = new PayeverActionQueueManager()
            : $this->actionQueueManager;
    }
}
