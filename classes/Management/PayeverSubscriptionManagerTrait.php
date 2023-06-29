<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverSubscriptionManagerTrait
{
    /** @var PayeverSubscriptionManager */
    protected $subscriptionManager;

    /**
     * @param PayeverSubscriptionManager $subscriptionManager
     * @return $this
     */
    public function setSubscriptionManager(PayeverSubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;

        return $this;
    }

    /**
     * @return PayeverSubscriptionManager
     * @codeCoverageIgnore
     */
    protected function getSubscriptionManager()
    {
        return null === $this->subscriptionManager
            ? $this->subscriptionManager = new PayeverSubscriptionManager()
            : $this->subscriptionManager;
    }
}
