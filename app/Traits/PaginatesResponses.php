<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait PaginatesResponses
{
    /**
     * Default per page if not provided.
     */
    protected int $defaultPerPage = 15;

    /**
     * Paginate a query or collection and return paginated result with metadata.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|\Illuminate\Support\Collection  $query
     * @param  \Illuminate\Http\Request|null  $request
     * @return array{data: mixed, pagination: array}
     */
    protected function paginateData($query, $pageParam = 'page'): array
    {
        $request = request();
        $perPage = $request?->input('per_page', $this->defaultPerPage);
        $currentPage = $request?->input($pageParam, 1);

        // لو Collection
        if ($query instanceof \Illuminate\Support\Collection) {
            $items = $query->forPage($currentPage, $perPage);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $query->count(),
                $perPage,
                $currentPage,
                ['path' => $request?->url(), 'query' => $request?->query()]
            );
        } else {
            // لو Query Builder أو Eloquent Builder
            $paginator = $query->paginate(
                $perPage,
                ['*'],
                $pageParam, // <-- page parameter name
                $currentPage
            )->appends($request?->query() ?? []);
        }

        return [
            'data' => $paginator->items(),
            'pagination' => $this->formatPagination($paginator),
        ];
    }

    /**
     * Format pagination metadata.
     */
    protected function formatPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    /**
     * Simple helper: returns only pagination meta without data.
     */
    protected function onlyPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return $this->formatPagination($paginator);
    }
}
