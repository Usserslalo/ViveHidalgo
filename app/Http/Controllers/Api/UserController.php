<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/profile",
     *     summary="Get the current user's profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile data",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="name", type="string", example="Test User"),
     *                  @OA\Property(property="email", type="string", example="test@example.com"),
     *                  @OA\Property(property="roles", type="array", @OA\Items(type="string", example="tourist")),
     *                  @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view-destinos")),
     *              ),
     *              @OA\Property(property="message", type="string", example="User profile retrieved successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles');
            
            return $this->successResponse($user, 'Perfil obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user/profile",
     *     summary="Update the current user's profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Updated Name"),
     *              @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *          )
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object", ref="#/components/schemas/User"),
     *              @OA\Property(property="message", type="string", example="Profile updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phone' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:500',
                'city' => 'sometimes|nullable|string|max:100',
                'state' => 'sometimes|nullable|string|max:100',
                'postal_code' => 'sometimes|nullable|string|max:10',
                'country' => 'sometimes|nullable|string|max:100',
                'current_password' => 'sometimes|required_with:new_password',
                'new_password' => 'sometimes|required_with:current_password|string|min:8|confirmed',
            ]);

            // Actualizar campos básicos
            $user->fill($validated);
            
            // Cambiar contraseña si se proporciona
            if (isset($validated['current_password']) && isset($validated['new_password'])) {
                if (!Hash::check($validated['current_password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => ['La contraseña actual es incorrecta.'],
                    ]);
                }
                
                $user->password = Hash::make($validated['new_password']);
            }

            $user->save();

            return $this->successResponse(
                $user->load('roles'), 
                'Perfil actualizado exitosamente'
            );

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Error de validación');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al actualizar perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            // Verificar contraseña actual
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('La contraseña actual es incorrecta.', 422);
            }

            // Actualizar contraseña
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Revocar todos los tokens (forzar logout en todos los dispositivos)
            $user->tokens()->delete();

            return $this->successResponse(null, 'Contraseña cambiada exitosamente. Debes iniciar sesión nuevamente.');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Error de validación');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al cambiar contraseña: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar cuenta
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revocar todos los tokens
            $user->tokens()->delete();

            // Eliminar usuario
            $user->delete();

            return $this->successResponse(null, 'Cuenta eliminada exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar cuenta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener estadísticas del usuario
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stats = [
                'favoritos_count' => $user->favoritos()->count(),
                'destinos_visitados' => 0, // Implementar cuando tengamos historial
                'promociones_vistas' => 0, // Implementar cuando tengamos historial
                'member_since' => $user->created_at->format('Y-m-d'),
                'last_login' => $user->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->successResponse($stats, 'Estadísticas obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }
}
