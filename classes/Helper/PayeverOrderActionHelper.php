<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverOrderActionHelper
{
    use PayeverDatabaseTrait;

    /** @var payeverorderaction*/
    public $orderAction;

    /**
     * Insert request response result
     *
     * @param array $data
     * @param string $action
     *
     * @return void
     */
    public function addAction($data, $action)
    {
        $sQuery = "INSERT {$this->getPaymentAction()->getCoreTableName()} SET 
            OXID = REPLACE(UUID( ) , '-', '' ),
            OXORDERID = {$this->getDatabase()->quote($data['orderId'])},
            PAYEVERACTIONID = {$this->getDatabase()->quote($data['actionId'])},
            AMOUNT = {$data['amount']},
            STATE = {$this->getDatabase()->quote($data['state'])},
            TYPE = {$this->getDatabase()->quote($data['type'])},
            PAYEVERACTION = {$this->getDatabase()->quote($action)},
            TIMESTAMP = NOW()";

        $this->getDatabase()->execute($sQuery);
    }

    /**
     * Gets all actions for the order by action type
     *
     * @param string $orderId
     * @param string $action
     *
     * @return array
     *
     * @throws oxConnectionException
     */
    public function getActions($orderId, $action)
    {
        $oDb = $this->getDatabase();

        $sSql = "SELECT * FROM {$this->getPaymentAction()->getCoreTableName()}
            WHERE OXORDERID = {$oDb->quote($orderId)} 
            AND PAYEVERACTION = {$oDb->quote($action)} 
            AND TYPE = {$oDb->quote(payeverorderaction::TYPE_PRODUCT)}
            ORDER BY TIMESTAMP";

        return $oDb->getAll($sSql);
    }

    /**
     * Gets all actions for the order by action type
     *
     * @param string $orderId
     * @param string $action
     * @param string $type
     *
     * @return bool
     *
     * @throws oxConnectionException
     */
    public function isActionExists($orderId, $action, $type)
    {
        $oDb = $this->getDatabase();

        $sSql = "SELECT * FROM {$this->getPaymentAction()->getCoreTableName()}
            WHERE OXORDERID = {$oDb->quote($orderId)} 
            AND PAYEVERACTION = {$oDb->quote($action)} 
            AND TYPE = {$oDb->quote($type)}
            LIMIT 1";

        return (bool) $oDb->getOne($sSql);
    }

    /**
     * Get the sum amount for all actions for the order
     *
     * @param string $orderId
     * @param string $action
     *
     * @return float
     *
     * @throws oxConnectionException
     */
    public function getSentAmount($orderId, $action)
    {
        $oDb = $this->getDatabase();

        $sSql = "SELECT SUM(`AMOUNT`) as total FROM {$this->getPaymentAction()->getCoreTableName()}
            WHERE OXORDERID = {$oDb->quote($orderId)} AND PAYEVERACTION = {$oDb->quote($action)}";

        return (float) $oDb->getOne($sSql);
    }

    /**
     * @return payeverorderaction
     */
    protected function getPaymentAction()
    {
        if ($this->orderAction === null) {
            $this->orderAction = oxRegistry::get('payeverorderaction');
        }

        return $this->orderAction;
    }
}
