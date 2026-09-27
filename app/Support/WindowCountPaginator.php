<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * paginate() without the separate COUNT query. The total rides along on every row as
 * COUNT(*) OVER () — computed by the database over the filtered set before LIMIT applies —
 * so a list page costs one round trip to the remote database instead of two.
 *
 * Registered as the Eloquent builder macro fastPaginate() in AppServiceProvider.
 */
class WindowCountPaginator
{
    private const TOTAL_COLUMN = '__window_total_rows';

    public static function paginate(Builder $query, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage($pageName);

        $pageQuery = clone $query;
        if ($pageQuery->getQuery()->columns === null) {
            // addSelect() on a builder with no explicit select would drop the table's columns.
            $pageQuery->select($pageQuery->getModel()->qualifyColumn('*'));
        }

        $items = $pageQuery
            ->selectRaw('COUNT(*) OVER () AS '.self::TOTAL_COLUMN)
            ->forPage($page, $perPage)
            ->get();

        // Past the last page no row carries the total, so only then pay for a real count
        // (the paginator needs it to render links back into range).
        $total = $items->isEmpty()
            ? ($page > 1 ? $query->toBase()->getCountForPagination() : 0)
            : (int) $items->first()->getAttribute(self::TOTAL_COLUMN);

        $items->each(fn ($model) => $model->offsetUnset(self::TOTAL_COLUMN));

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
}
