<?php

use Payever\Sdk\Core\Logger\FileLogger;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverLogger extends FileLogger
{
    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = [])
    {
        parent::log($level, $message, $context);

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
            parent::log($level, $e->getMessage(), $context);
        }
    }
}
