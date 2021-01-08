<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2020 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Action\ActionDecider;
use Payever\ExternalIntegration\Payments\Action\ActionDeciderInterface;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;
use Payever\ExternalIntegration\Payments\PaymentsApiClient;
use Psr\Log\LoggerInterface;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverShippingGoodsHandler
{
    /** @var PaymentsApiClient */
    private $paymentsApiClient;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param oxorder $order
     */
    public function triggerShippingGoodsPaymentRequest($order)
    {
        $paymentMethod = $order->oxorder__oxpaymenttype->rawValue;
        $this->getLogger()->debug('Start execution ' . __METHOD__);
        if (PayeverConfig::isPayeverPaymentMethod($paymentMethod)) {
            try {
                $paymentId = $order->oxorder__oxtransid->rawValue;
                $actionDecider = new ActionDecider($this->getPaymentsApiClient());
                $isActionAllowed = $actionDecider->isActionAllowed(
                    $paymentId,
                    ActionDeciderInterface::ACTION_SHIPPING_GOODS,
                    false
                );
                if ($isActionAllowed) {
                    $requestEntity = new ShippingGoodsPaymentRequest();
                    $requestEntity
                        ->setCustomerId($order->oxorder__oxuserid->rawValue)
                        ->setInvoiceId($order->oxorder__oxinvoicenr->rawValue);

                    $this->getPaymentsApiClient()->shippingGoodsPaymentRequest($paymentId, $requestEntity);
                    $this->getLogger()->debug('ShippingGoodsPaymentRequest has been sent');
                } else {
                    $this->getLogger()->debug('Action is not allowed');
                }
            } catch (Exception $e) {
                $this->getLogger()->error(
                    'Shipping goods error',
                    [
                        'exception_message' => $e->getMessage(),
                        'paymentId' => $paymentId,
                    ]
                );
            }
        } else {
            $this->getLogger()->debug('Non-payever payment method is ignored');
        }
        $this->getLogger()->debug('Stop execution ' . __METHOD__);
    }

    /**
     * @param PaymentsApiClient $paymentsApiClient
     * @return $this
     */
    public function setPaymentsApiClient(PaymentsApiClient $paymentsApiClient)
    {
        $this->paymentsApiClient = $paymentsApiClient;

        return $this;
    }

    /**
     * @return PaymentsApiClient
     * @throws Exception
     */
    protected function getPaymentsApiClient()
    {
        return null === $this->paymentsApiClient
            ? $this->paymentsApiClient = PayeverApiClientProvider::getPaymentsApiClient()
            : $this->paymentsApiClient;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return null === $this->logger
            ? $this->logger = PayeverConfig::getLogger()
            : $this->logger;
    }
}
