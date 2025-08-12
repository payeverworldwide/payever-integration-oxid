<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 * @codeCoverageIgnore
 */
class payeverShowLogs extends oxUBase
{
    use PayeverConfigHelperTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverRequestHelperTrait;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function render()
    {
        try {
            $this->validateRequest();
            $this->send($this->getLogZipFile(false));
        } catch (\Exception $e) {
            header("HTTP/1.1 401 Unauthorized");

            //@codeCoverageIgnoreStart
            echo 'Error: ' . $e->getMessage();
            exit;
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @return void
     */
    public function shop()
    {
        try {
            $this->validateRequest();
            $this->send($this->getLogZipFile(true));
        } catch (\Exception $e) {
            header("HTTP/1.1 401 Unauthorized");

            //@codeCoverageIgnoreStart
            echo 'Error: ' . $e->getMessage();
            exit;
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * @param $zipFile
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @return void
     */
    private function send($zipFile)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip;');
        header('Content-Disposition: attachment; filename="' . basename($zipFile) . '";');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($zipFile);
        exit();
    }

    /**
     * @param $addSystemFlag
     * @return ZipArchive
     * @throws Exception
     */
    private function getLogZipFile($addSystemFlag)
    {
        $logsDirectory = OX_BASE_PATH . 'log';
        $zipFilePath = $logsDirectory . DIRECTORY_SEPARATOR
            . 'payever_' . (new \DateTime())->format('Y-m-d-H-i-s') . '.zip';

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFilePath, ZipArchive::CREATE);

        $logFiles = [
            $this->buildPayeverLogFile()
        ];

        if ($addSystemFlag) {
            $logFiles = glob($logsDirectory . DIRECTORY_SEPARATOR . '*.log');
        }

        foreach ($logFiles as $filename) {
            $zipArchive->addFile($filename, basename($filename));
        }

        $zipArchive->close();

        return $zipFilePath;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function buildPayeverLogFile()
    {
        $logsListFactory = new PayeverLogsListFactory();
        $collection = $logsListFactory->create();
        $collection->clear();

        $logsFactory = new PayeverLogsFactory();
        $logsObject = $logsFactory->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($logsObject);

        $query = $logsObject->buildSelectString(null);
        $query .= ' ORDER BY `log_id`';
        $collection->selectString($query);

        $logsDirectory = OX_BASE_PATH . 'log';
        $logFile = $logsDirectory . DIRECTORY_SEPARATOR . 'payever_export.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $items = $collection->getArray();

        foreach ($items as $item) {
            /** @var payeverlogs $item */
            $data = html_entity_decode($item->getFieldData('data'));
            $createdAt = $item->getFieldData('created_at');

            if (
                (new DateTime($createdAt)) > (new DateTime(date('Y-m-d 00:00:00', strtotime('-90 days')))) && //phpcs:ignore
                (new DateTime($createdAt)) < (new DateTime(date('Y-m-d 23:59:59')))
            ) {
                $context = \json_decode($data, true);

                if (\json_last_error() !== JSON_ERROR_NONE) {
                    $context = $data;
                }

                file_put_contents(
                    $logFile,
                    sprintf(
                        "[%s] payever.%s: %s %s []\n",
                        $createdAt,
                        $item->getFieldData('level'),
                        $item->getFieldData('message'),
                        json_encode($context)
                    ),
                    FILE_APPEND
                );
            }
        }

        return $logFile;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function validateRequest()
    {
        $token = $this->getRequestHelper()->getHeader('Authorization');

        if (empty($token)) {
            $token = $this->getRequestHelper()->getStringParam('token');
        }

        if (
            !$this->getThirdPartyPluginsApiClient()
                ->validateToken(
                    $this->getConfigHelper()->getBusinessUuid(),
                    $token
                )
        ) {
            throw new \BadMethodCallException('Access denied.');
        }

        return $this;
    }
}
