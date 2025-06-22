<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
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
     * Respuesta exitosa (alias para sendResponse)
     */
    protected function sendResponse($data, $message = null, $code = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $code);
    }

    /**
     * Respuesta de error (alias para sendError)
     */
    protected function sendError($message, $errors = [], $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
} 