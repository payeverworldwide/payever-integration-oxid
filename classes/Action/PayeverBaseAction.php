<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;

abstract class PayeverBaseAction implements PayeverActionInterface
{
    use PayeverFieldFactoryTrait;
    use PayeverActionRequestTrait;
    use PayeverOrderActionHelperTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverPaymentActionHelperTrait;
    use PayeverOrderTransactionHelperTrait;

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @var array
     */
    protected $updateArticles = [];

    /**
     * @param oxOrder $oxOrder
     * @return mixed
     * @throws Exception
     */
    public function processActionRequest($oxOrder)
    {
        $identifier = $this->getPaymentActionHelper()->generateIdentifier();
        $this->getPaymentActionHelper()->addAction($oxOrder->getId(), $identifier, $this->getActionType());

        $type = $this->getActionRequest()->getType();
        switch ($type) {
            case self::TYPE_TOTAL:
                $total = $this->getOrderTransactionHelper()->getTotal($oxOrder);
                $response = $this->processAmount($oxOrder, $total, $identifier);
                break;
            case self::TYPE_AMOUNT:
                $amount = $this->getActionRequest()->getAmount();
                $response = $this->processAmount($oxOrder, $amount, $identifier);
                break;
            case self::TYPE_ITEM:
                $items = $this->getActionRequest()->getItems();
                $response = $this->processItems($oxOrder, $items, $identifier);
                break;
            default:
                throw new \InvalidArgumentException('Invalid action type');
        }

        //Change order status
        $status = str_replace('STATUS_', '', json_decode($response->getData())->result->status);
        $oxOrder->oxorder__oxtransstatus = $this->getFieldFactory()->createRaw($status);
        $oxOrder->save();

        return $response;
    }

    /**
     * Process partial amount request
     *
     * @param oxOrder $oxOrder
     * @param float $amount
     * @param string $identifier
     *
     * @return mixed
     * @throws Exception
     */
    public function processAmount($oxOrder, $amount, $identifier = null)
    {
        // Send api request
        /** @var \Payever\Sdk\Core\Http\Response $response */
        $response = $this->sendAmountRequest($oxOrder, $amount, $identifier);
        $responseEntity = $response->getResponseEntity();
        $call = $responseEntity ? $responseEntity->getCall() : null;

        $this->getOrderActionHelper()->addAction([
            'orderId' => $oxOrder->getId(),
            'actionId' => $call ? $call->getId() : null,
            'state' => $call ? $call->getStatus() : null,
            'amount' => $amount,
            'type' => payeverorderaction::TYPE_PRODUCT
        ], $this->getActionType());

        return $response;
    }

    /**
     * Process partial items request
     *
     * @param oxOrder $oxOrder
     * @param array $items
     * @param string $identifier
     *
     * @return mixed
     * @throws Exception
     */
    public function processItems($oxOrder, $items, $identifier = null)
    {
        $paymentItems = $this->getPaymentItemEntities($oxOrder, $items);

        //Send api request
        $response = $this->sendItemsRequest($oxOrder, $paymentItems, $identifier);

        //Update the count of items
        foreach ($this->updateArticles as $item) {
            $item->save();
        }

        //Add row to actions list
        foreach ($this->actions as $action) {
            $this->getOrderActionHelper()->addAction([
                'orderId' => $oxOrder->getId(),
                'actionId' => $response->getResponseEntity()->getCall()->getId(),
                'state' => $response->getResponseEntity()->getCall()->getStatus(),
                'amount' => $action['amount'],
                'type' => $action['type']
            ], $this->getActionType());
        }

        return $response;
    }

    /**
     * Creating payment objects for a request to the payever
     *
     * @param oxOrder $oxOrder
     * @param array $items
     *
     * @return array
     */
    protected function getPaymentItemEntities($oxOrder, $items)
    {
        $paymentItems = [];
        $articles = $oxOrder->getOrderArticles(true);
        foreach ($articles as $article) {
            /** @var oxArticle $article */
            if (array_key_exists($article->getId(), $items)) {
                $paymentItems[] = (new PaymentItemEntity())
                    ->setIdentifier($article->oxorderarticles__oxartid->value)
                    ->setName($article->oxorderarticles__oxtitle->value)
                    ->setPrice($article->oxorderarticles__oxbprice->value)
                    ->setQuantity((int) $items[$article->getId()]);

                $field = 'oxorderarticles__' . $this->getActionField();
                $qnt = $article->getFieldData($this->getActionField()) + $items[$article->getId()];
                $article->$field = $this->getFieldFactory()->createRaw($qnt);
                $this->updateArticles[] = $article;
            }
        }

        if (isset($items[payeverorderaction::TYPE_GIFTCARD_COST])) {
            $paymentItems[] = (new PaymentItemEntity())
                ->setIdentifier(payeverorderaction::TYPE_GIFTCARD_COST)
                ->setName('Greeting Card')
                ->setPrice($oxOrder->getFieldData('oxgiftcardcost'))
                ->setQuantity(1);

            $this->actions[] = [
                'amount' => $oxOrder->getFieldData('oxgiftcardcost'),
                'type' => payeverorderaction::TYPE_GIFTCARD_COST,
            ];
        }

        if (isset($items[payeverorderaction::TYPE_WRAP_COST])) {
            $paymentItems[] = (new PaymentItemEntity())
                ->setIdentifier(payeverorderaction::TYPE_WRAP_COST)
                ->setName('Gift Wrapping')
                ->setPrice($oxOrder->getFieldData('oxwrapcost'))
                ->setQuantity(1);

            $this->actions[] = [
                'amount' => $oxOrder->getFieldData('oxwrapcost'),
                'type' => payeverorderaction::TYPE_WRAP_COST,
            ];
        }

        return $paymentItems;
    }

    /**
     * Process partial amount request
     *
     * @param oxOrder $oxOrder
     * @param float $amount
     * @param string $identifier
     *
     * @return mixed
     *
     * @throws Exception
     */
    abstract protected function sendAmountRequest($oxOrder, $amount, $identifier);

    /**
     * Process partial items request
     *
     * @param oxOrder $oxOrder
     * @param array $paymentItems
     * @param string $identifier
     *
     * @return mixed
     *
     * @throws Exception
     */
    abstract protected function sendItemsRequest($oxOrder, $paymentItems, $identifier);

    /**
     * Get action type (cancel|refund|shipping)
     *
     * @return string
     */
    abstract public function getActionType();

    /**
     * Get action field in order article table
     *
     * @return string
     */
    abstract public function getActionField();
}
