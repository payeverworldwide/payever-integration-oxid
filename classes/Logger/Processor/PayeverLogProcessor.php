<?php
// phpcs:ignoreFile

use Monolog\Processor\ProcessorInterface;

if (!interface_exists('\Monolog\Processor\ProcessorInterface')) {
    // Ignore for Oxid v4
    return;
}

class PayeverLogProcessor implements ProcessorInterface
{
    /**
     * Log processor callback.
     *
     * @param array $record
     * @return array The processed record
     */
    public function __invoke(array $record)
    {
        $logsFactory = new PayeverLogsFactory();
        $logs = $logsFactory->create();

        $logs->assign([
            'level' => $record['level_name'],
            'message' => $record['message'],
            'data' => json_encode($record['context']),
            'created_at' => date(DATE_ATOM),
        ]);

        try {
            $logs->save();
        } catch (\Exception $e) {
            // Silence is golden
        }

        return $record;
    }
}
