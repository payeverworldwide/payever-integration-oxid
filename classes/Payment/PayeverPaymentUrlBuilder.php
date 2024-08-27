<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Http\MessageEntity\ResultEntity;
use Payever\Sdk\Payments\Enum\Status;
use Payever\Stub\BehatExtension\Context\ConfigTrait;

/**
 * Class PayeverPaymentUrlBuilder
 */
class PayeverPaymentUrlBuilder
{
    use PayeverConfigTrait;

    const STATUS_PARAM = 'sts';

    const STATUS_SUCCESS = 'success';
    const STATUS_CANCEL = 'cancel';
    const STATUS_FAILURE = 'failure';
    const STATUS_NOTICE = 'notice';
    const STATUS_PENDING = 'pending';

    /**
     * Generating callback url.
     *
     * @param string $status
     * @param array $params
     *
     * @return string
     */
    public function generateCallbackUrl($status, $params = [])
    {
        $urlData = array_merge([
            'cl' => 'payeverStandardDispatcher',
            'fnc' => 'payeverGatewayReturn',
            self::STATUS_PARAM => $status,
            'payment_id' => '--PAYMENT-ID--',
            'sDeliveryAddressMD5' => $this->getConfig()->getRequestParameter('sDeliveryAddressMD5'),
        ], $params);

        return $this->getConfig()->getSslShopUrl() . '?' . http_build_query($urlData, "", "&");
    }

    /**
     * @param ResultEntity $result
     *
     * @return string
     */
    public function createRedirectUrl($result)
    {
        $status = null;
        $params = ['payment_id' => $result->getId()];

        switch ($result->getStatus()) {
            case Status::STATUS_NEW:
            case Status::STATUS_IN_PROCESS:
                $status = self::STATUS_PENDING;
                break;
            case Status::STATUS_ACCEPTED:
            case Status::STATUS_PAID:
                $status = self::STATUS_SUCCESS;
                break;
            case Status::STATUS_REFUNDED:
            case Status::STATUS_CANCELLED:
            case Status::STATUS_DECLINED:
                $status = self::STATUS_CANCEL;
                break;
            case Status::STATUS_FAILED:
                $status = self::STATUS_FAILURE;
                break;
        }

        return $this->generateCallbackUrl($status, $params);
    }
}
