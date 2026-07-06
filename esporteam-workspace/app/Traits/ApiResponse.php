<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success response with data
     */
    protected function successResponse($data = null, string $message = 'Operação realizada com sucesso', int $statusCode = 200): JsonResponse
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

    /**
     * Error response
     */
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

    /**
     * Created response (201)
     */
    protected function createdResponse($data, string $message = 'Criado com sucesso'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Deleted response (204 No Content)
     */
    protected function deletedResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
