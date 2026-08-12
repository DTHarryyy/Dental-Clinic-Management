<?php

namespace Tests\Unit;

use App\Support\AnalyticsDateRange;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnalyticsDateRangeTest extends TestCase
{
    #[DataProvider('presetProvider')]
    public function test_presets_are_inclusive_and_compare_with_an_equal_previous_period(string $period, string $from, string $to, int $days): void
    {
        $range = AnalyticsDateRange::fromInput($period, now: CarbonImmutable::parse('2026-08-13 09:00', AnalyticsDateRange::TIMEZONE));

        $this->assertSame($from, $range->from->toDateString());
        $this->assertSame($to, $range->to->toDateString());
        $this->assertSame($days, $range->days());
        $this->assertSame($days, (int) $range->previousFrom->startOfDay()->diffInDays($range->previousTo->startOfDay()) + 1);
        $this->assertTrue($range->previousTo->isSameDay($range->from->subDay()));
    }

    public static function presetProvider(): array
    {
        return [
            ['today', '2026-08-13', '2026-08-13', 1],
            ['7d', '2026-08-07', '2026-08-13', 7],
            ['30d', '2026-07-15', '2026-08-13', 30],
            ['this_month', '2026-08-01', '2026-08-13', 13],
        ];
    }

    public function test_custom_range_exposes_manila_boundaries_as_utc(): void
    {
        $range = AnalyticsDateRange::fromInput('custom', '2026-08-13', '2026-08-13');

        $this->assertSame('2026-08-12 16:00:00', $range->utcFrom()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-13 15:59:59', $range->utcTo()->format('Y-m-d H:i:s'));
    }

    public function test_custom_range_rejects_reversed_or_overlong_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AnalyticsDateRange::fromInput('custom', '2026-08-14', '2026-08-13');
    }

    public function test_custom_range_is_limited_to_366_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AnalyticsDateRange::fromInput('custom', '2025-01-01', '2026-01-02');
    }
}
