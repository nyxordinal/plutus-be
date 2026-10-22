<?php

use App\Logging\Formatters\CustomFormatter;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class CustomFormatterTest extends PHPUnitTestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    public function testItFormatsTheNormalizedLogRecordAsJson(): void
    {
        $formatter = new CustomFormatter(null, 'Y-m-d H:i:s');
        $formatted = $formatter->format($this->record(
            new DateTimeImmutable('2025-03-04 05:06:07', new DateTimeZone('UTC')),
            'req-123'
        ));

        $this->assertStringEndsWith("\n", $formatted);
        $this->assertSame([
            'timestamp' => '2025-03-04 12:06:07 WIB',
            'correlation_id' => 'req-123',
            'level' => 'INFO',
            'message' => 'Expense saved',
            'context' => ['expense_id' => 42],
            'extra' => ['source' => 'api'],
        ], json_decode($formatted, true, 512, JSON_THROW_ON_ERROR));
    }

    public function testItUsesNullWhenTheRecordHasNoCorrelationId(): void
    {
        $formatter = new CustomFormatter(null, 'Y-m-d H:i:s');
        $record = $this->record(new DateTimeImmutable('2025-03-04 05:06:07'));

        $this->assertNull(json_decode($formatter->format($record), true, 512, JSON_THROW_ON_ERROR)['correlation_id']);
    }

    private function record(DateTimeImmutable $datetime, ?string $correlationId = null): array
    {
        $record = [
            'datetime' => $datetime,
            'level_name' => 'INFO',
            'message' => 'Expense saved',
            'context' => ['expense_id' => 42],
            'extra' => ['source' => 'api'],
        ];

        if ($correlationId !== null) {
            $record['correlation_id'] = $correlationId;
        }

        return $record;
    }
}
