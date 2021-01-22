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
        $Columns = ['basketid' => 'TEXT', 'panid' => 'TEXT', 'payever_notification_timestamp' => 'int'];

        foreach ($Columns as $cval => $type) {
            $sSql = <<<SQL
SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'oxorder' AND COLUMN_NAME = ?
SQL;
            $aResult = $oDb->getAll(
                $sSql,
                [
                    $oConfig->getConfigParam('dbName'),
                    $cval
                ]
            );
            if (empty($aResult)) {
                $sSql = "ALTER TABLE  `oxorder` ADD `" . $cval . "` " . $type . " NOT NULL ";
                $oDb->execute($sSql);
            }
        }

        $columns = [
            'oxacceptfee' => [
                'type' => 'tinyint',
                'nullable' => false,
            ],
            'oxpercentfee' => [
                'type' => 'TEXT',
                'nullable' => false,
            ],
            'oxfixedfee' => [
                'type' => 'TEXT',
                'nullable' => false,
            ],
            'oxvariants' => [
                'type' => 'TEXT',
                'nullable' => false,
            ],
            'oxthumbnail' => [
                'type' => 'TEXT',
                'nullable' => false,
            ],
            'oxisredirectmethod' => [
                'type' => 'tinyint',
                'nullable' => true,
            ],
        ];
        self::createColumsForPaymentsTable($columns);
        self::createDefaultPayeverCategory();
        self::createSynchronizationQueueTable();
    }

    /**
     * @param array $columns
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    private static function createColumsForPaymentsTable($columns)
    {
        $oConfig = oxRegistry::getConfig();
        $oDb = oxDb::getDb(oxDb::FETCH_MODE_ASSOC);
        foreach ($columns as $cval => $defenition) {
            $sSql = <<<SQL
SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'oxpayments' AND COLUMN_NAME = ?
SQL;
            $aResult = $oDb->getAll(
                $sSql,
                [
                    $oConfig->getConfigParam('dbName'),
                    $cval
                ]
            );
            if (empty($aResult)) {
                $sSql = sprintf(
                    'ALTER TABLE `oxpayments` ADD `%s` %s %s',
                    $cval,
                    $defenition['type'],
                    $defenition['nullable'] ? '' : 'NOT NULL'
                );
                $oDb->execute($sSql);
            }

            $updateViews = ['oxv_oxpayments', 'oxv_oxpayments_de', 'oxv_oxpayments_en'];

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

    /**
     * Create default payever category
     *
     * @throws Exception
     */
    public static function createDefaultPayeverCategory()
    {
        $defaultPayeverCategory = oxNew('oxcategory');
        $data = [
            'oxparentid' => 'oxrootid',
            'oxtitle' => 'payever',
        ];
        $aLanguages = oxRegistry::getLang()->getLanguageArray();
        foreach ($aLanguages as $aLanguage) {
            if (property_exists($aLanguage, 'id')) {
                $defaultPayeverCategory->assign($data);
                $defaultPayeverCategory->setLanguage($aLanguage->id);
                $defaultPayeverCategory->save();
            }
        }
    }

    /**
     * Creates payeversynchronizationqueue table
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    private static function createSynchronizationQueueTable()
    {
        $oDb = oxDb::getDb();
        $oDb->execute(
            "CREATE TABLE IF NOT EXISTS `payeversynchronizationqueue` (
                `oxid` CHAR(32) NOT NULL COMMENT 'Queue Id id' PRIMARY KEY,
                `direction` VARCHAR(64) NOT NULL COMMENT 'Record direction',
                `action` VARCHAR(255) NOT NULL COMMENT 'Synchronization action',
                `payload` BLOB COMMENT 'Synchronization action payload',
                `inc` INT UNSIGNED AUTO_INCREMENT,
                `attempt` SMALLINT NOT NULL DEFAULT 0 COMMENT 'How many times we have failed to process this record',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
                key `PAYEVERSQ_INC_IDX` (`inc`)
            ) COMMENT 'Payever Synchronization Queue'"
        );
    }

    /**
     * @retrun void
     */
    public static function migrateDB()
    {
        $columns = ['oxvariants' => 'TEXT', 'oxthumbnail' => 'TEXT'];
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
        static::deleteDefaultPayeverCategory();
        static::dropSynchronizationQueueTable();
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
     * Deletes default payever category
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    private static function deleteDefaultPayeverCategory()
    {
        $row = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)
            ->getRow(
                'SELECT OXID as oxid FROM oxcategories WHERE OXTITLE = ?',
                ['payever']
            );
        $categoryId = !empty($row['oxid']) ? $row['oxid'] : null;
        if (!$categoryId && !empty($row[0])) {
            $categoryId = $row[0];
        }
        if ($categoryId) {
            $category = oxNew('oxcategory');
            $category->load($categoryId);
            $category->delete();
        }
    }

    /**
     * Drops synchronization_queue table
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    private static function dropSynchronizationQueueTable()
    {
        $oDb = oxDb::getDb();
        $oDb->execute("DROP TABLE IF EXISTS `payeversynchronizationqueue`");
    }

    /**
     * @param string $sClearFolderPath
     *
     * @return bool
     */
    public static function cleanTmp($sClearFolderPath = '')
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
