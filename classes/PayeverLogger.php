<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverLogger
{
    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        $logsFactory = new PayeverLogsFactory();
        $logs = $logsFactory->create();

        $logs->assign([
            'level' => $level,
            'message' => $message,
            'data' => json_encode($context),
            'created_at' => date(DATE_ATOM),
        ]);

        try {
            $logs->save();
        } catch (\Exception $e) {
            // Silence is golden
        }
    }

    public function downloadLogs($addSystemFlag)
    {
        $logsDirectory = OX_BASE_PATH . 'log';
        $zipFilePath = $logsDirectory . DIRECTORY_SEPARATOR
            . 'payever_' . (new \DateTime())->format('Y-m-d-H-i-s') . '.zip';

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($zipFilePath, ZipArchive::CREATE);

        $logsDirectory = OX_BASE_PATH . 'log';
        $logFiles = glob($logsDirectory . '/*payever.log');
        $logFiles[] =  $this->buildPayeverLogFile();

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
}
