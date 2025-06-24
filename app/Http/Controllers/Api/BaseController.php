<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Vive Hidalgo API",
 *     description="API para la plataforma de turismo Vive Hidalgo",
 *     @OA\Contact(
 *         email="admin@vivehidalgo.com",
 *         name="Soporte Vive Hidalgo"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Respuesta exitosa
     */
    protected function successResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Respuesta de error
     */
    protected function errorResponse($message, $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $code);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    protected function notFoundResponse($message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta de validación
     */
    protected function validationErrorResponse($errors, $message = 'Error de validación'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Success response
     */
    protected function sendResponse($data, $message = '', $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ];

        return response()->json($response, $code)
            ->header('Cache-Control', 'public, max-age=300') // 5 minutos de cache
            ->header('X-API-Version', '1.0.0')
            ->header('X-Response-Time', $this->getResponseTime());
    }

    /**
     * Error response
     */
    protected function sendError($error, $errorMessages = [], $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        // Log error for monitoring
        Log::error('API Error', [
            'error' => $error,
            'messages' => $errorMessages,
            'code' => $code,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        return response()->json($response, $code)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('X-API-Version', '1.0.0')
            ->header('X-Response-Time', $this->getResponseTime());
    }

    /**
     * Get cached data with fallback
     */
    protected function getCachedData(string $key, callable $callback, int $ttl = 300)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear cache for specific patterns
     */
    protected function clearCache(string $pattern): void
    {
        Cache::flush();
    }

    /**
     * Get response time
     */
    private function getResponseTime(): string
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $endTime = microtime(true);
        
        return round(($endTime - $startTime) * 1000, 2) . 'ms';
    }

    /**
     * Validate request with custom rules
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = validator($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Paginate results with cache
     */
    protected function paginateWithCache($query, $perPage = 15, $cacheKey = null, $ttl = 300)
    {
        if ($cacheKey) {
            return $this->getCachedData(
                $cacheKey . '_page_' . request()->get('page', 1),
                fn() => $query->paginate($perPage),
                $ttl
            );
        }
        
        return $query->paginate($perPage);
    }
} 