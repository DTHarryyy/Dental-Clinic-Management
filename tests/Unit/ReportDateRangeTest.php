<?php

namespace Tests\Unit;

use App\Support\ReportDateRange;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReportDateRangeTest extends TestCase
{
    #[DataProvider('presetProvider')]
    public function test_presets_are_normalized_in_manila(string $period, string $from, string $to): void
    {
        $range = ReportDateRange::fromInput($period, now: CarbonImmutable::parse('2026-08-13 10:00', 'Asia/Manila'));

        $this->assertSame($from, $range->from->toDateString());
        $this->assertSame($to, $range->to->toDateString());
        $this->assertSame('Asia/Manila', $range->from->timezoneName);
    }

    public static function presetProvider(): array
    {
        return [
            ['today', '2026-08-13', '2026-08-13'],
            ['7d', '2026-08-07', '2026-08-13'],
            ['30d', '2026-07-15', '2026-08-13'],
            ['this_month', '2026-08-01', '2026-08-13'],
            ['this_quarter', '2026-07-01', '2026-08-13'],
            ['this_year', '2026-01-01', '2026-08-13'],
        ];
    }

    public function test_custom_boundaries_are_inclusive_and_previous_period_is_equal(): void
    {
        $range = ReportDateRange::fromInput('custom', '2026-08-01', '2026-08-13', CarbonImmutable::parse('2026-08-13', 'Asia/Manila'));

        $this->assertSame(13, $range->days());
        $this->assertSame('2026-07-19', $range->previousFrom->toDateString());
        $this->assertSame('2026-07-31', $range->previousTo->toDateString());
        $this->assertSame('2026-07-31 16:00:00', $range->utcFrom()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-13 15:59:59', $range->utcTo()->format('Y-m-d H:i:s'));
    }

    public function test_exact_five_year_custom_range_is_allowed(): void
    {
        $range = ReportDateRange::fromInput('custom', '2021-08-13', '2026-08-13', CarbonImmutable::parse('2026-08-13', 'Asia/Manila'));
        $this->assertSame('2021-08-13', $range->from->toDateString());
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid_custom_ranges_are_rejected(?string $from, ?string $to): void
    {
        $this->expectException(InvalidArgumentException::class);
        ReportDateRange::fromInput('custom', $from, $to, CarbonImmutable::parse('2026-08-13', 'Asia/Manila'));
    }

    public static function invalidProvider(): array
    {
        return [
            ['2026-08-14', '2026-08-13'],
            ['2026-08-01', '2026-08-14'],
            ['2021-08-12', '2026-08-13'],
            [null, '2026-08-13'],
            ['not-a-date', '2026-08-13'],
        ];
    }
}
