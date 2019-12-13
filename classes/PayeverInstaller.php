<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

class PayeverInstaller
{
    /**
     * Execute action on activate event
     *
     * @return void
     *
     * @throws oxException
     */
    public static function onActivate()
    {
        /**
         * In case of plugin version update to SDK version -
         * installer classname changed and uninstall may not be executed properly
         */
        self::deletePaymentMethods();

        self::installDb();
        self::cleanTmp();
    }

    /**
     * Execute action on deactivate event
     *
     * @return void
     *
     * @throws oxException
     */
    public static function onDeactivate()
    {
        self::uninstallDb();
        self::cleanTmp();
    }

    /**
     * @throws oxConnectionException
     *
     * @return void
     */
    private static function installDb()
    {
        $oConfig = oxRegistry::getConfig();
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        $Columns = array('basketid' => 'TEXT', 'panid' => 'TEXT', 'payever_notification_timestamp' => 'int');

        foreach ($Columns as $cval => $type) {
            $sSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $oConfig->getConfigParam('dbName') . "' AND TABLE_NAME = 'oxorder' AND COLUMN_NAME = '" . $cval . "'";
            $aResult = $oDb->getAll($sSql);
            if (empty($aResult)) {
                $sSql = "ALTER TABLE  `oxorder` ADD `" . $cval . "` " . $type . " NOT NULL ";
                $oDb->execute($sSql);
            }
        }

        $columns = array('oxacceptfee' => 'tinyint', 'oxpercentfee' => 'TEXT', 'oxfixedfee' => 'TEXT', 'oxvariants' => 'TEXT');
        self::createColumsForPaymentsTable($columns);
    }

    private static function createColumsForPaymentsTable($columns)
    {
        $oConfig = oxRegistry::getConfig();
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        foreach ($columns as $cval => $type) {
            $sSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $oConfig->getConfigParam('dbName') . "' AND TABLE_NAME = 'oxpayments' AND COLUMN_NAME = '" . $cval . "'";
            $aResult = $oDb->getAll($sSql);
            if (empty($aResult)) {
                $sSql = "ALTER TABLE  `oxpayments` ADD `" . $cval . "` " . $type . " NOT NULL ";
                $oDb->execute($sSql);
            }

            $updateViews = array('oxv_oxpayments', 'oxv_oxpayments_de', 'oxv_oxpayments_en');

            foreach ($updateViews as $viewName) {
                $viewExistsSql = "SELECT * FROM INFORMATION_SCHEMA.TABLES 
                                  WHERE TABLE_SCHEMA = '" . $oConfig->getConfigParam('dbName') . "' 
                                        AND TABLE_NAME = '{$viewName}'";
                $columnExistsSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '" . $oConfig->getConfigParam('dbName') . "' 
                               AND TABLE_NAME = '{$viewName}' AND COLUMN_NAME = '{$cval}'";
                $columnResult = $oDb->getAll($columnExistsSql);

                if ($oDb->getOne($viewExistsSql) && empty($columnResult)) {
                    $sSql = "ALTER VIEW `{$viewName}` AS SELECT * FROM `oxpayments`";
                    $oDb->execute($sSql);
                }
            }
        }
    }

    public static function migrateDB()
    {
        $columns = array('oxvariants' => 'TEXT');
        self::createColumsForPaymentsTable($columns);
    }

    /**
     * Clean all DB traces
     *
     * @return void
     *
     * @throws oxConnectionException
     */
    private static function uninstallDb()
    {
        static::deleteTemplateBlocks();
        static::deletePaymentMethods();
    }

    /**
     * Delete overriden template block rules (OXID won't do if for us)
     *
     * @throws oxConnectionException
     */
    private static function deleteTemplateBlocks()
    {
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        $sSql = "DELETE FROM `oxtplblocks` where oxmodule='payever'";
        $oDb->execute($sSql);
    }

    /**
     * Delete payment methods (OXID won't do if for us)
     *
     * @throws oxConnectionException
     */
    private static function deletePaymentMethods()
    {
        $methods = PayeverConfig::getMethodsList();
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);

        foreach ($methods as $method) {
            $sSql = "DELETE FROM `oxpayments` WHERE `OXID` = '" . $method . "'";
            $oDb->execute($sSql);
        }
    }

    /**
     * @param string $sClearFolderPath
     *
     * @return bool
     */
    private static function cleanTmp($sClearFolderPath = '')
    {
        $sTempFolderPath = oxRegistry::getConfig()->getConfigParam('sCompileDir');

        if (!empty($sClearFolderPath) and
            (strpos($sClearFolderPath, $sTempFolderPath) !== false) and
            is_dir($sClearFolderPath)
        ) {
            // User argument folder path to delete from
            $sFolderPath = $sClearFolderPath;
        } elseif (empty($sClearFolderPath)) {
            // Use temp folder path from settings
            $sFolderPath = $sTempFolderPath;
        } else {
            return false;
        }

        $hDir = opendir($sFolderPath);
        if (!empty($hDir)) {
            while (false !== ($sFileName = readdir($hDir))) {
                $sFilePath = $sFolderPath . '/' . $sFileName;

                if (!in_array($sFileName, array('.', '..', '.htaccess')) and is_file($sFilePath)) {
                    // Delete a file if it is allowed to delete
                    @unlink($sFilePath);
                } elseif ($sFileName == 'smarty' and is_dir($sFilePath)) {
                    // Recursive call to clean Smarty temp
                    self::cleanTmp($sFilePath);
                }
            }
        }

        return true;
    }
}
