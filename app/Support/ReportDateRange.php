<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class ReportDateRange
{
    public const TIMEZONE = AnalyticsDateRange::TIMEZONE;

    public const PERIODS = ['today', '7d', '30d', 'this_month', 'this_quarter', 'this_year', 'custom'];

    public readonly CarbonImmutable $previousFrom;

    public readonly CarbonImmutable $previousTo;

    private function __construct(
        public readonly string $period,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {
        $this->previousFrom = $from->subDays($this->days())->startOfDay();
        $this->previousTo = $from->subDay()->endOfDay();
    }

    public static function fromInput(
        string $period = 'this_month',
        ?string $from = null,
        ?string $to = null,
        ?CarbonImmutable $now = null,
    ): self {
        if (! in_array($period, self::PERIODS, true)) {
            throw new InvalidArgumentException('Unknown report period.');
        }

        $today = ($now ?? CarbonImmutable::now(self::TIMEZONE))->setTimezone(self::TIMEZONE)->startOfDay();
        [$rangeFrom, $rangeTo] = match ($period) {
            'today' => [$today, $today->endOfDay()],
            '7d' => [$today->subDays(6), $today->endOfDay()],
            '30d' => [$today->subDays(29), $today->endOfDay()],
            'this_month' => [$today->startOfMonth(), $today->endOfDay()],
            'this_quarter' => [$today->startOfQuarter(), $today->endOfDay()],
            'this_year' => [$today->startOfYear(), $today->endOfDay()],
            'custom' => [self::parseDate($from), self::parseDate($to)->endOfDay()],
        };

        if ($rangeFrom->greaterThan($rangeTo)) {
            throw new InvalidArgumentException('The start date must be before or equal to the end date.');
        }

        if ($rangeTo->greaterThan($today->endOfDay())) {
            throw new InvalidArgumentException('Reports cannot include future dates.');
        }

        if ($rangeFrom->lessThan($rangeTo->subYears(5)->startOfDay())) {
            throw new InvalidArgumentException('Report ranges cannot exceed five calendar years.');
        }

        return new self($period, $rangeFrom->startOfDay(), $rangeTo->endOfDay());
    }

    public function days(): int
    {
        return (int) $this->from->startOfDay()->diffInDays($this->to->startOfDay()) + 1;
    }

    public function utcFrom(): CarbonImmutable
    {
        return $this->from->setTimezone('UTC');
    }

    public function utcTo(): CarbonImmutable
    {
        return $this->to->setTimezone('UTC');
    }

    public function previousUtcFrom(): CarbonImmutable
    {
        return $this->previousFrom->setTimezone('UTC');
    }

    public function previousUtcTo(): CarbonImmutable
    {
        return $this->previousTo->setTimezone('UTC');
    }

    public function contains(CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->from, $this->to);
    }

    public function containsPrevious(CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->previousFrom, $this->previousTo);
    }

    public function key(): string
    {
        return $this->period.':'.$this->from->toDateString().':'.$this->to->toDateString();
    }

    public function label(): string
    {
        if ($this->from->isSameDay($this->to)) {
            return $this->from->format('M j, Y');
        }

        return $this->from->format('M j, Y').'–'.$this->to->format('M j, Y');
    }

    public function previousLabel(): string
    {
        if ($this->previousFrom->isSameDay($this->previousTo)) {
            return $this->previousFrom->format('M j, Y');
        }

        return $this->previousFrom->format('M j, Y').'–'.$this->previousTo->format('M j, Y');
    }

    private static function parseDate(?string $date): CarbonImmutable
    {
        if (! $date) {
            throw new InvalidArgumentException('Custom report ranges require both dates.');
        }

        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, self::TIMEZONE);
        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Report dates must use YYYY-MM-DD format.');
        }

        return $parsed;
    }
}
