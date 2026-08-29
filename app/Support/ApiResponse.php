<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
        ], $status);
    }

    public static function paginated(string $resourceClass, LengthAwarePaginator $paginator): JsonResponse
    {
        return self::success(
            $resourceClass::collection($paginator)->resolve(request()),
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        );
    }

    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => [],
            'errors' => $errors === [] ? ['message' => $message] : $errors,
        ], $status);
    }
}
