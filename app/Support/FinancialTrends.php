<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Support\Collection;

class FinancialTrends
{
    public static function sixMonthRevenue(): Collection
    {
        $firstMonth = now()->subMonths(5)->startOfMonth();
        $payments = Payment::query()
            ->verified()
            ->select(['amount', 'paid_at'])
            ->whereBetween('paid_at', [$firstMonth, now()->endOfMonth()])
            ->get()
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'));

        return collect(range(5, 0))->map(function (int $monthsAgo) use ($payments): array {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->format('M'),
                'value' => (float) $payments->get($month->format('Y-m'), collect())->sum('amount'),
            ];
        });
    }
}
