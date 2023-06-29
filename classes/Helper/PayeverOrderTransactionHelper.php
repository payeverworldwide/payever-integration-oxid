<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverOrderTransactionHelper
{
    use PayeverPaymentsApiClientTrait;

    /** @var array */
    protected $transaction;

    /**
     * @var array
     */
    private static $instance = null;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param oxOrder $order
     * @param bool $refresh
     *
     * @return array
     */
    public function getTransaction($order, $refresh = null)
    {
        if (!$this->transaction || $refresh) {
            try {
                $this->transaction = $this->getPaymentsApiClient()
                    ->getTransactionRequest($order->getFieldData('oxtransid'))
                    ->getResponseEntity()
                    ->getResult()
                    ->toArray();
            } catch (\Exception $e) {
                $this->transaction = [];
            }
        }

        return $this->transaction;
    }

    /**
     * @param oxOrder $order
     *
     * @return array
     */
    public function isActionAllowed($order, $transactionAction)
    {
        $status = ['enabled' => false, 'partialAllowed' => false];

        $transaction = $this->getTransaction($order);
        if (!isset($transaction['actions'])) {
            return $status;
        }

        $actions = [];
        foreach ($transaction['actions'] as $action) {
            $actions[$action->action] = (array) $action;
        }

        if (isset($actions[$transactionAction])) {
            $status['enabled'] = $actions[$transactionAction]['enabled'];
            $status['partialAllowed'] = $actions[$transactionAction]['partialAllowed'];
        }

        return $status;
    }

    /**
     * @param oxOrder $order
     *
     * @return float
     */
    public function getTotal($order)
    {
        $transaction = $this->getTransaction($order);

        return $transaction['total'] ?: $order->getFieldData('oxtotalordersum');
    }
}
