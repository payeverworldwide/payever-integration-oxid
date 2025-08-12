<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Class PayeverActionProcessor
 *
 * @codeCoverageIgnore
 */
class PayeverActionProcessor
{
    use PayeverOrderActionHelperTrait;
    use PayeverPaymentActionHelperTrait;
    use PayeverOrderTransactionHelperTrait;
    use PayeverLoggerTrait;
    use PayeverFieldFactoryTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * Process action request
     *
     * @param oxOrder $oxOrder
     * @param string $action
     *
     * @return array
     */
    public function processAction($oxOrder, $action)
    {
        try {
            $actionExecutor = $this->getActionExecutor($action);
            $response = $actionExecutor->processActionRequest($oxOrder);

            $this->getLogger()->debug(
                $action . 'Payment Request Complete: ' . $oxOrder->getFieldData('oxtransid'),
                (array)$response
            );

            return $response;
        } catch (\Exception $e) {
            $this->getLogger()->error($action . 'PaymentRequest Error:', [
                'exception_message' => $e->getMessage(),
                'paymentId' => $oxOrder->getFieldData('oxtransid'),
            ]);

            throw new \BadMethodCallException($e->getMessage());
        }
    }

    /**
     * @param string $action
     * @return PayeverActionInterface
     * @throws Exception
     */
    private function getActionExecutor($action)
    {
        switch ($action) {
            case ActionDeciderInterface::ACTION_SHIPPING_GOODS:
                $actionExecutor = new PayeverCaptureAction();
                break;
            case ActionDeciderInterface::ACTION_CANCEL:
                $actionExecutor = new PayeverCancelAction();
                break;
            case ActionDeciderInterface::ACTION_REFUND:
                $actionExecutor = new PayeverRefundAction();
                break;
            case ActionDeciderInterface::ACTION_CLAIM:
                $actionExecutor = new PayeverClaimAction();
                break;
            case ActionDeciderInterface::ACTION_CLAIM_UPLOAD:
                $actionExecutor = new PayeverClaimUploadAction();
                break;
            default:
                throw new \InvalidArgumentException('Invalid action type');
        }

        return $actionExecutor;
    }
}
