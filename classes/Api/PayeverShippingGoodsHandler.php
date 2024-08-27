<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDecider;
use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingDetailsEntity;
use Payever\Sdk\Payments\Http\RequestEntity\ShippingGoodsPaymentRequest;
use OxidEsales\EshopCommunity\Application\Model\Order;
use OxidEsales\EshopCommunity\Application\Model\OrderArticle;
use OxidEsales\EshopCommunity\Core\Price;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Class PayeverShippingGoodsHandler
 */
class PayeverShippingGoodsHandler
{
    use PayeverConfigHelperTrait;
    use PayeverLoggerTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverFieldFactoryTrait;
    use PayeverOrderActionHelperTrait;
    use PayeverPaymentActionHelperTrait;

    const REQUEST_TYPE_AMOUNT = 'amount';
    const REQUEST_TYPE_ITEMS = 'items';

    /**
     * @var ActionDecider
     */
    public $actionDecider;

    /**
     * @var array
     */
    protected $paymentItems = [];

    /**
     * @var float
     */
    protected $orderAmount = 0;

    /**
     * @var string
     */
    protected $error;

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
        $this->getLogger()->debug('Start execution ' . __METHOD__);

        $paymentMethod = $order->getFieldData('oxpaymenttype');
        if (!$this->getConfigHelper()->isPayeverPaymentMethod($paymentMethod)) {
            $this->getLogger()->debug('Non-payever payment method is ignored');

            return;
        }

        try {
            // Get items
            $updateItems = [];
            $articles = $order->getOrderArticles(true);
            foreach ($articles as $article) {
                /** @var OrderArticle $article */
                $qnt = $article->getFieldData('oxamount');
                $price = $article->getFieldData('oxbprice');

                //Check if there was a partial shipping for the order items
                $partialShipped = $article->getFieldData('oxpayevershipped');
                if ($partialShipped) {
                    $qnt -= $partialShipped;
                    $this->orderAmount += $price * $partialShipped;
                }

                if ($qnt) {
                    $field = $this->getFieldFactory()->createRaw($qnt + $partialShipped);
                    $article->oxorderarticles__oxpayevershipped = $field;
                    $updateItems[] = $article;

                    $this->createPaymentItemEntity(
                        $article->oxorderarticles__oxartid->value,
                        $article->oxorderarticles__oxtitle->value,
                        $price,
                        $qnt
                    );
                }
            }

            // Get voucher discounts
            if ($order->oxorder__oxvoucherdiscount && $order->oxorder__oxvoucherdiscount->value > 0) {
                $discount = $order->oxorder__oxvoucherdiscount->value;
                $this->createPaymentItemEntity('discount', 'Discount', -1 * $discount);
            }

            // Get discounts
            if ($order->oxorder__oxdiscount && $order->oxorder__oxdiscount->value > 0) {
                $discount = $order->oxorder__oxdiscount->value;
                $this->createPaymentItemEntity('discount', 'Discount', -1 * $discount);
            }

            $deliveryFee = $this->getDeliveryFee($order);
            $orderTotal = $order->oxorder__oxtotalordersum ? $order->oxorder__oxtotalordersum->value : 0;

            //Get all total partial shipping by amount
            $sentAmount = $this->getOrderActionHelper()->getSentAmount(
                $order->getId(),
                payeverorderaction::ACTION_SHIPPING_GOODS
            );
            $shippingGoodsRequest = $this->createShippingGoodsPaymentRequest($order);

            //Check if there was a partial refund by amount
            if (round($orderTotal, 2) === round($this->orderAmount, 2) && !$sentAmount) {
                $requestType = self::REQUEST_TYPE_ITEMS;

                // Add payment items
                $shippingGoodsRequest->setPaymentItems($this->paymentItems);
                $shippingGoodsRequest->setDeliveryFee($deliveryFee);
            } else {
                $requestType = self::REQUEST_TYPE_AMOUNT;
                $shippingGoodsRequest->setAmount($order->oxorder__oxtotalordersum->value - $sentAmount);
            }

            // Add payment action identifier
            $identifier = $this->getPaymentActionHelper()->generateIdentifier();
            $this->getPaymentActionHelper()->addAction(
                $order->getId(),
                $identifier,
                ActionDecider::ACTION_SHIPPING_GOODS
            );

            $result = $this->sendShippingGoodsPaymentRequest($order, $shippingGoodsRequest, $identifier);

            //Change order status
            if ($result->isSuccessful()) {
                $order->oxorder__oxtransstatus = $this->getFieldFactory()->createRaw('SHIPPED');
                $order->save();

                if ($requestType === self::REQUEST_TYPE_ITEMS) {
                    //Update total shipped items
                    foreach ($updateItems as $article) {
                        $article->save();
                    }
                } else {
                    $this->getOrderActionHelper()->addAction([
                        'orderId' => $order->getId(),
                        'actionId' => $result->getResponseEntity()->getCall()->getId(),
                        'amount' => $order->oxorder__oxtotalordersum->value - $sentAmount,
                        'state' => $result->getResponseEntity()->getCall()->getStatus(),
                    ], payeverorderaction::ACTION_SHIPPING_GOODS);
                }
            }
        } catch (Exception $e) {
            $this->getLogger()->error('Shipping goods error', [
                'exception_message' => $e->getMessage(),
                'paymentId' => $order->getFieldData('oxtransid')
            ]);
        }

        $this->getLogger()->debug('Stop execution ' . __METHOD__);
    }

    /**
     * @param Order|oxorder $order
     * @param array $paymentItems
     * @param string $identifier
     *
     * @return \Payever\Sdk\Core\Http\Response
     * @throws Exception
     */
    public function triggerItemsShippingGoodsPaymentRequest($order, $paymentItems, $identifier = null)
    {
        //Create shipping goods request entity
        $shippingGoodsRequest = $this->createShippingGoodsPaymentRequest($order);
        $shippingGoodsRequest->setPaymentItems($paymentItems);

        //Send shipping api request
        return $this->sendShippingGoodsPaymentRequest($order, $shippingGoodsRequest, $identifier);
    }

    /**
     *
     * @param Order|oxorder $order
     * @param float|int $amount
     * @param string $identifier
     *
     * @return \Payever\Sdk\Core\Http\Response
     * @throws Exception
     */
    public function triggerAmountShippingGoodsPaymentRequest($order, $amount, $identifier = null)
    {
        //Create shipping goods request entity
        $shippingGoodsRequest = $this->createShippingGoodsPaymentRequest($order);
        $shippingGoodsRequest->setAmount($amount);

        //Send shipping api request
        return $this->sendShippingGoodsPaymentRequest($order, $shippingGoodsRequest, $identifier);
    }

    /**
     * Send request to api with shipping entities
     *
     * @param Order|oxorder $order
     * @param ShippingGoodsPaymentRequest $shippingGoodsRequest
     * @param string $identifier
     *
     * @return \Payever\Sdk\Core\Http\Response
     * @throws Exception
     */
    protected function sendShippingGoodsPaymentRequest($order, $shippingGoodsRequest, $identifier = null)
    {
        //Check if shipping is allowed
        $paymentId = $order->getFieldData('oxtransid');
        if (!$this->actionDecider->isShippingAllowed($paymentId, false)) {
            throw new \Exception('Shipping method is not allowed');
        }

        //Send shipping api request
        $result = $this->getPaymentsApiClient()->shippingGoodsPaymentRequest(
            $paymentId,
            $shippingGoodsRequest,
            $identifier
        );

        $this->getLogger()->debug('ShippingGoodsPaymentRequest has been sent');

        return $result;
    }

    /**
     * Get order delivery fee
     *
     * @param Order|oxorder $order
     *
     * @return float|int
     */
    protected function getDeliveryFee($order)
    {
        // Add delivery fee
        /** @var Price $shippingPrice */
        $deliveryFee = 0;
        $shippingPrice = $order->getOrderDeliveryPrice();
        if ($shippingPrice && $shippingPrice->getPrice() > 0) {
            $deliveryFee = $shippingPrice->getPrice();
            $this->orderAmount += $deliveryFee;
        }

        return $deliveryFee;
    }

    /**
     * Create shipping request entity
     *
     * @param Order|oxorder $order
     *
     * @return ShippingGoodsPaymentRequest
     */
    protected function createShippingGoodsPaymentRequest($order)
    {
        $shippingMethod = $order->getDelSet()->getFieldData('oxtitle');

        $shippingDetails = new ShippingDetailsEntity();
        $shippingDetails
            ->setShippingMethod($shippingMethod)
            ->setShippingCarrier($shippingMethod)
            ->setShippingDate((new DateTime())->format(DateTime::ISO8601))
            ->setTrackingNumber('')
            ->setTrackingUrl('');

        $shippingGoodsRequest = new ShippingGoodsPaymentRequest();
        $shippingGoodsRequest->setShippingDetails($shippingDetails);

        return $shippingGoodsRequest;
    }

    /**
     * Create shipping item entity
     *
     * @param string $identity
     * @param string $name
     * @param float|int $price
     * @param int $qnt
     *
     * @return PaymentItemEntity
     */
    protected function createPaymentItemEntity($identity, $name, $price, $qnt = 1)
    {
        $paymentEntity = new PaymentItemEntity();
        $paymentEntity
            ->setIdentifier($identity)
            ->setName($name)
            ->setPrice($price)
            ->setQuantity($qnt);

        //Calculate total amount
        $this->orderAmount += $price * $qnt;

        //Collect payment item
        $this->paymentItems[] = $paymentEntity;

        return $paymentEntity;
    }
}
