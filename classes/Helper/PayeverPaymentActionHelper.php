<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverPaymentActionHelper
{
    use PayeverDatabaseTrait;

    const SOURCE_EXTERNAL = 'external';
    const SOURCE_INTERNAL = 'internal';
    const SOURCE_PSP = 'psp';

    /** @var payeverpaymentaction*/
    public $paymentAction;

    /**
     * Gets all actions for the order by action type
     *
     * @param string $orderId
     * @param string $identifier
     * @param string $source
     *
     * @return bool
     *
     * @throws oxConnectionException
     */
    public function isActionExists($orderId, $identifier, $source)
    {
        $oDb = $this->getDatabase();

        $sSql = "SELECT * FROM {$this->getPaymentAction()->getCoreTableName()}
            WHERE OXORDERID = {$oDb->quote($orderId)}
            AND UNIQUEIDENTIFIER = {$oDb->quote($identifier)}
            AND ACTIONSOURCE = {$oDb->quote($source)}
            LIMIT 1";

        return (bool) $oDb->getOne($sSql);
    }

    /**
     * Insert request response result
     *
     * @param string $orderId
     * @param string $identifier
     * @param string $type
     *
     * @return string
     */
    public function addAction($orderId, $identifier, $type)
    {
        $sQuery = "INSERT {$this->getPaymentAction()->getCoreTableName()} SET
            OXID = REPLACE(UUID( ) , '-', '' ),
            OXORDERID = {$this->getDatabase()->quote($orderId)},
            UNIQUEIDENTIFIER = {$this->getDatabase()->quote($identifier)},
            ACTIONTYPE = {$this->getDatabase()->quote($type)},
            ACTIONSOURCE = " . $this->getDatabase()->quote(self::SOURCE_EXTERNAL) . ",
            OXTIMESTAMP = NOW()";

        $this->getDatabase()->execute($sQuery);

        return $identifier;
    }

    /**
     * @return string
     */
    public function generateIdentifier()
    {
        return uniqid();
    }

    /**
     * @return payeverorderaction
     */
    protected function getPaymentAction()
    {
        if ($this->paymentAction === null) {
            $this->paymentAction = oxRegistry::get('payeverpaymentaction');
        }

        return $this->paymentAction;
    }
}
