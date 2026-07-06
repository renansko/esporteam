<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(string $message, $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function createdResponse($data, string $message = 'Created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function deletedResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Paginated response — extrai data/links/meta de um LengthAwarePaginator
     * ou de uma ResourceCollection que envolve um paginator.
     */
    protected function paginatedResponse($collection, string $message = 'Success'): JsonResponse
    {
        if ($collection instanceof JsonResource || $collection instanceof ResourceCollection) {
            $payload = $collection->response(request())->getData(true);
        } elseif ($collection instanceof AbstractPaginator) {
            $payload = $collection->toArray();
        } else {
            $payload = (array) $collection;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $payload['data'] ?? [],
            'links'   => $payload['links'] ?? null,
            'meta'    => $payload['meta'] ?? null,
        ]);
    }
}
