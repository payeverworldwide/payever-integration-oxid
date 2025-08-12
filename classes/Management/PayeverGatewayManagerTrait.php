<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverGatewayManagerTrait
{
    /** @var PayeverGatewayManager */
    protected $gatewayManager;

    /**
     * @param PayeverGatewayManager $gatewayManager
     * @return $this
     */
    public function setGatewayManager(PayeverGatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;

        return $this;
    }

    /**
     * @return PayeverGatewayManager
     * @codeCoverageIgnore
     */
    protected function getGatewayManager()
    {
        return null === $this->gatewayManager
            ? $this->gatewayManager = new PayeverGatewayManager()
            : $this->gatewayManager;
    }
}
