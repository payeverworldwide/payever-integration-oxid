<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverActionRequestTrait
{
    /** @var PayeverActionRequest */
    protected $actionActionRequest;

    /**
     * @param PayeverActionRequest $actionActionRequest
     * @return $this
     * @internal
     */
    public function setActionRequest(PayeverActionRequest $actionActionRequest)
    {
        $this->actionActionRequest = $actionActionRequest;

        return $this;
    }

    /**
     * @return PayeverActionRequest
     * @codeCoverageIgnore
     */
    protected function getActionRequest()
    {
        return null === $this->actionActionRequest
            ? $this->actionActionRequest = new PayeverActionRequest()
            : $this->actionActionRequest;
    }
}
