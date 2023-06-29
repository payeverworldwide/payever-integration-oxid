<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverRequestHelperTrait
{
    /** @var PayeverRequestHelper */
    protected $requestHelper;

    /**
     * @param PayeverRequestHelper $requestHelper
     * @return $this
     * @internal
     */
    public function setRequestHelper(PayeverRequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;

        return $this;
    }

    /**
     * @return PayeverRequestHelper
     * @codeCoverageIgnore
     */
    protected function getRequestHelper()
    {
        return null === $this->requestHelper
            ? $this->requestHelper = new PayeverRequestHelper()
            : $this->requestHelper;
    }
}
