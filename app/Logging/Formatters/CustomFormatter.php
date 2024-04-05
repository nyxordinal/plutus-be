<?php

namespace App\Logging\Formatters;

use Monolog\Formatter\LineFormatter;

class CustomFormatter extends LineFormatter
{
    public function format(array $record): string
    {
        $correlationId = isset($record['correlation_id']) ? $record['correlation_id'] : null;
        $timezone = date_default_timezone_get();
        $record['datetime'] = $record['datetime']->setTimezone(new \DateTimeZone($timezone));
        $normalized = [
            'timestamp' => $record['datetime']->format($this->dateFormat . ' T'),
            'correlation_id' => $correlationId,
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        return json_encode($normalized) . "\n";
    }
}
