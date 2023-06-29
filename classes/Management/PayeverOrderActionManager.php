<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

use Payever\Sdk\Payments\Http\RequestEntity\PaymentItemEntity;

/**
 * Class PayeverOrderActionManager
 */
abstract class PayeverOrderActionManager
{
    use PayeverOrderActionHelperTrait;
    use PayeverOrderTransactionHelperTrait;
    use PayeverLoggerTrait;
    use PayeverFieldFactoryTrait;
    use PayeverPaymentsApiClientTrait;

    /**
     * @var bool
     */
    public $confirmCheckbox = false;

    /**
     * @var array
     */
    public $response = [];

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @var array
     */
    protected $updateArticles = [];

    /**
     * Add action to actions list table
     *
     * @param array $data
     *
     * @return void
     */
    public function addAction($data)
    {
        $this->getOrderActionHelper()->addAction($data, $this->getActionType());
    }

    /**
     * Gets actions list for the order by action type
     *
     * @param oxOrder $order
     *
     * @return array
     *
     * @throws oxConnectionException
     */
    public function getActions($order, $actionType = null)
    {
        return $this->getOrderActionHelper()->getActions($order->getId(), $actionType ?: $this->getActionType());
    }

    /**
     * Check if action is allowed in payever api
     *
     * @param oxOrder $order
     *
     * @return array
     */
    public function isActionAllowed($order, $actionType = null)
    {
        return $this->getOrderTransactionHelper()->isActionAllowed($order, $actionType ?: $this->getActionType());
    }

    /**
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    public function isWrapCostSent($order)
    {
        return $this->getOrderActionHelper()
            ->isActionExists($order->getId(), $this->getActionType(), payeverorderaction::TYPE_WRAP_COST);
    }

    /**
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    public function isGiftCardCostSent($order)
    {
        return $this->getOrderActionHelper()
            ->isActionExists($order->getId(), $this->getActionType(), payeverorderaction::TYPE_GIFTCARD_COST);
    }

    /**
     * Get the total amount for action
     *
     * @param oxOrder $order
     *
     * @return float
     */
    public function getTotalAmount($order)
    {
        return $this->getOrderTransactionHelper()->getTotal($order);
    }

    /**
     * Get the amount for sent actions
     *
     * @param oxOrder $order
     *
     * @return float
     *
     * @throws oxConnectionException
     */
    public function getSentAmount($order, $actionType = null, $actionField = null)
    {
        $partialTotal = $this->getOrderActionHelper()
            ->getSentAmount($order->getId(), $actionType ?: $this->getActionType());

        $articles = $order->getOrderArticles(true);
        foreach ($articles as $article) {
            /** @var oxArticle $article */
            $partialQnt = $article->getFieldData($actionField ?: $this->getActionField());
            if ($partialQnt) {
                $partialTotal += $article->getFieldData('oxbprice') * $partialQnt;
            }
        }

        return $partialTotal;
    }

    /**
     * Creating payment objects for a request to the payever
     *
     * @param oxOrder $order
     * @param array $items
     *
     * @return array
     */
    public function getPaymentItemEntities($order, $items)
    {
        $paymentItems = [];
        $articles = $order->getOrderArticles(true);
        foreach ($articles as $article) {
            /** @var oxArticle $article */
            if (array_key_exists($article->getId(), $items)) {
                $paymentItems[] = (new PaymentItemEntity())
                    ->setIdentifier($article->oxorderarticles__oxartid->value)
                    ->setName($article->oxorderarticles__oxtitle->value)
                    ->setPrice($article->oxorderarticles__oxbprice->value)
                    ->setQuantity($items[$article->getId()]);

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
                ->setPrice($order->getFieldData('oxgiftcardcost'))
                ->setQuantity(1);

            $this->actions[] = [
                'amount' => $order->getFieldData('oxgiftcardcost'),
                'type' => payeverorderaction::TYPE_GIFTCARD_COST
            ];
        }

        if (isset($items[payeverorderaction::TYPE_WRAP_COST])) {
            $paymentItems[] = (new PaymentItemEntity())
                ->setIdentifier(payeverorderaction::TYPE_WRAP_COST)
                ->setName('Gift Wrapping')
                ->setPrice($order->getFieldData('oxwrapcost'))
                ->setQuantity(1);

            $this->actions[] = [
                'amount' => $order->getFieldData('oxwrapcost'),
                'type' => payeverorderaction::TYPE_WRAP_COST
            ];
        }

        return $paymentItems;
    }

    /**
     * @param oxOrder $order
     * @param \Payever\Sdk\Core\Http\Response $response
     *
     * @return void
     */
    public function onRequestComplete($order, $response)
    {
        $responseArray = $response->getResponseEntity()->toArray();

        //Add row to actions list
        if ($this->actions) {
            foreach ($this->actions as $action) {
                $this->addAction([
                    'orderId' => $order->getId(),
                    'actionId' => $responseArray['call']['id'],
                    'amount' => $action['amount'],
                    'state' => $responseArray['call']['status'],
                    'type' => $action['type']
                ]);
            }
        }

        //Update the count of items
        if ($this->updateArticles) {
            foreach ($this->updateArticles as $item) {
                $item->save();
            }
        }

        $this->getLogger()->debug(
            $this->getActionType() . 'Payment Request Complete: ' . $order->getFieldData('oxtransid'),
            (array) $response
        );

        //Change order status
        $status = str_replace('STATUS_', '', json_decode($response->getData())->result->status);
        $order->oxorder__oxtransstatus = $this->getFieldFactory()->createRaw($status);
        $order->save();

        $this->response['success'] = $response->isSuccessful();
        $this->response['response'] = $responseArray;
    }

    /**
     * @param oxOrder $order
     * @param string $msg
     *
     * @return void
     */
    public function onRequestFailed($order, $msg)
    {
        $this->getLogger()->error($this->getActionType() . 'PaymentRequest Error:', [
            'exception_message' => $msg,
            'paymentId' => $order->getFieldData('oxtransid'),
        ]);

        $this->response['success'] = false;
        $this->response['error'] = $msg;
    }

    /**
     * Process partial amount request
     *
     * @param oxOrder $order
     * @param float $amount
     *
     * @return mixed
     */
    abstract public function processAmount($order, $amount);

    /**
     * Process partial items request
     *
     * @param oxOrder $order
     * @param array $items
     *
     * @return mixed
     */
    abstract public function processItems($order, $items);

    /**
     * Check if the partial amount form is allowed
     * If the partial items form is in progress, the amount form is not allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     */
    abstract public function partialAmountFormAllowed($order);

    /**
     * Check if the prefill form is allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     */
    abstract public function prefillAmountAllowed($order);

    /**
     * Check if the partial items form is allowed
     * If the partial amount form is in progress, the items form is not allowed
     *
     * @param oxOrder $order
     *
     * @return bool
     * @throws oxConnectionException
     */
    abstract public function partialItemsFormAllowed($order);

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
