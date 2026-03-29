<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $response = ['success' => true, 'data' => $data];
        if (! empty($meta)) {
            $response['meta'] = $meta;
        }
        return response()->json($response, $status);
    }

    protected function created(mixed $data): JsonResponse
    {
        return $this->success($data, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function error(string $message, int $status = 400, string $code = 'BAD_REQUEST', mixed $details = null): JsonResponse
    {
        $payload = ['success' => false, 'error' => ['code' => $code, 'message' => $message]];
        if ($details !== null) {
            $payload['error']['details'] = $details;
        }
        return response()->json($payload, $status);
    }

    protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->error("{$resource} not found", 404, 'NOT_FOUND');
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403, 'FORBIDDEN');
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401, 'UNAUTHORIZED');
    }

    protected function paginated($paginator): JsonResponse
    {
        return $this->success($paginator->items(), 200, [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
        ]);
    }
}
