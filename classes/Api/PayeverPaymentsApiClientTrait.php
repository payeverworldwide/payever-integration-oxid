<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\PaymentsApiClient;

trait PayeverPaymentsApiClientTrait
{
    /** @var PaymentsApiClient */
    protected $paymentsApiClient;

    /**
     * @param PaymentsApiClient $paymentsApiClient
     * @return $this
     * @internal
     */
    public function setPaymentsApiClient(PaymentsApiClient $paymentsApiClient)
    {
        $this->paymentsApiClient = $paymentsApiClient;

        return $this;
    }

    /**
     * @return PaymentsApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getPaymentsApiClient()
    {
        return null === $this->paymentsApiClient
            ? $this->paymentsApiClient = PayeverApiClientProvider::getPaymentsApiClient()
            : $this->paymentsApiClient;
    }
}
