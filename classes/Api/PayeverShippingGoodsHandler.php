<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Payments\Action\ActionDecider;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\PaymentItemEntity;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\ShippingDetailsEntity;
use Payever\ExternalIntegration\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;
use OxidEsales\EshopCommunity\Application\Model\Order;
use OxidEsales\EshopCommunity\Application\Model\OrderArticle;
use OxidEsales\EshopCommunity\Core\Price;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverShippingGoodsHandler
{
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @var ActionDecider
     */
    public $actionDecider;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->actionDecider = new ActionDecider($this->getPaymentsApiClient());
    }

    /**
     * @param Order|oxorder $order
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function triggerShippingGoodsPaymentRequest($order)
    {
        $paymentMethod = $order->getFieldData('oxpaymenttype');
        $this->getLogger()->debug('Start execution ' . __METHOD__);
        if ($this->getConfigHelper()->isPayeverPaymentMethod($paymentMethod)) {
            $orderAmount = 0;
            $paymentItems = array();

            // Get items
            $articles = $order->getOrderArticles(true);
            foreach ($articles as $article) {
                /** @var OrderArticle $article */
                $sku = preg_replace(
                    '#[^0-9a-z_]+#i',
                    '-',
                    $article->oxorderarticles__oxartnum->value
                );
                $price = $article->oxorderarticles__oxbprice->value;
                $qty = $article->oxorderarticles__oxamount->value;

                $paymentEntity = new PaymentItemEntity();
                $paymentEntity->setIdentifier($sku)
                    ->setName($article->oxorderarticles__oxtitle->value)
                    ->setPrice($qty)
                    ->setQuantity($qty);

                $orderAmount += $price * $qty;

                $paymentItems[] = $paymentEntity;
            }

            // Get voucher discounts
            if ($order->oxorder__oxvoucherdiscount && $order->oxorder__oxvoucherdiscount->value > 0) {
                $discount = $order->oxorder__oxvoucherdiscount->value;

                $paymentEntity = new PaymentItemEntity();
                $paymentEntity->setIdentifier('discount')
                    ->setName('Discount')
                    ->setPrice(-1 * $discount)
                    ->setQuantity(1);

                $orderAmount -= $discount;

                $paymentItems[] = $paymentEntity;
            }

            // Get discounts
            if ($order->oxorder__oxdiscount && $order->oxorder__oxdiscount->value > 0) {
                $discount = $order->oxorder__oxdiscount->value;

                $paymentEntity = new PaymentItemEntity();
                $paymentEntity->setIdentifier('discount')
                    ->setName('Discount')
                    ->setPrice(-1 * $discount)
                    ->setQuantity(1);

                $orderAmount -= $discount;

                $paymentItems[] = $paymentEntity;
            }

            // Add delivery fee
            /** @var Price $shippingPrice */
            $deliveryFee = 0;
            $shippingPrice = $order->getOrderDeliveryPrice();
            if ($shippingPrice && $shippingPrice->getPrice() > 0) {
                $deliveryFee = $shippingPrice->getPrice();
                $orderAmount += $deliveryFee;
            }

            try {
                $paymentId = $order->getFieldData('oxtransid');

                $isActionAllowed = $this->actionDecider->isShippingAllowed($paymentId, false);
                if ($isActionAllowed) {
                    $shippingMethod = $order->getFieldData('oxdeltype');
                    $sendDate = $order->getFieldData('oxsenddate');

                    $shippingDetails = new ShippingDetailsEntity();
                    $shippingDetails->setShippingMethod($shippingMethod)
                        ->setShippingDate($sendDate);

                    $shippingGoodsRequestEntity = new ShippingGoodsPaymentRequest();
                    $shippingGoodsRequestEntity->setShippingDetails($shippingDetails);

                    $orderTotal = $order->oxorder__oxtotalordersum ? $order->oxorder__oxtotalordersum->value : 0;
                    if (round($orderTotal, 2) === round($orderAmount, 2)) {
                        // Add payment items
                        $shippingGoodsRequestEntity->setPaymentItems($paymentItems);
                        $shippingGoodsRequestEntity->setDeliveryFee($deliveryFee);
                    } else {
                        $shippingGoodsRequestEntity->setAmount($order->oxorder__oxtotalordersum->value);
                    }

                    $result = $this->getPaymentsApiClient()->shippingGoodsPaymentRequest(
                        $paymentId,
                        $shippingGoodsRequestEntity
                    );

                    if ($result->isSuccessful()) {
                        $order->oxorder__oxfolder = new \OxidEsales\Eshop\Core\Field(
                            'ORDERFOLDER_FINISHED',
                            \OxidEsales\Eshop\Core\Field::T_RAW
                        );
                        $order->save();
                    }

                    $this->getLogger()->debug('ShippingGoodsPaymentRequest has been sent');
                } else {
                    $this->getLogger()->debug('Action is not allowed');
                }
            } catch (Exception $e) {
                $this->getLogger()->error(
                    'Shipping goods error',
                    [
                        'exception_message' => $e->getMessage(),
                        'paymentId' => isset($paymentId) ? $paymentId : 'N/A',
                    ]
                );
            }
        } else {
            $this->getLogger()->debug('Non-payever payment method is ignored');
        }
        $this->getLogger()->debug('Stop execution ' . __METHOD__);
    }
}
