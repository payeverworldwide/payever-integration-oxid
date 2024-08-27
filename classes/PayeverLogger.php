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
}
