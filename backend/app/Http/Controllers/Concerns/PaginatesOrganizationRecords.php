<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PaginatesOrganizationRecords
{
    protected function paginateQuery(Request $request, Builder $query): JsonResponse
    {
        if (! $request->has('page') && ! $request->has('per_page')) {
            return response()->json($query->get());
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return response()->json($query->paginate($perPage));
    }
}
