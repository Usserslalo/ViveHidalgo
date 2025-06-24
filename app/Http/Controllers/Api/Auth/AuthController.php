<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Main API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 */
class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *      path="/api/v1/auth/register",
     *      operationId="registerUser",
     *      tags={"Authentication"},
     *      summary="Registers a new user",
     *      description="Registers a new user and returns an auth token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "email", "password", "password_confirmation"},
     *              @OA\Property(property="name", type="string", example="Test User"),
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="password"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful registration",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example="true"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="token", type="string", example="2|bCdEfGhIjKlMnOpQrStUvWxYz"),
     *                  @OA\Property(property="name", type="string", example="Test User")
     *              ),
     *              @OA\Property(property="message", type="string", example="User registered successfully.")
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Crear el usuario
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'country' => 'México',
                'is_active' => true,
            ]);

            // Asignar rol de turista por defecto
            $user->assignRole('tourist');

            // Generar token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user->load('roles'),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Usuario registrado exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al registrar usuario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/auth/login",
     *      operationId="loginUser",
     *      tags={"Authentication"},
     *      summary="Logs user into the system",
     *      description="Logs in a user and returns an auth token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful login",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example="true"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ"),
     *                  @OA\Property(property="name", type="string", example="Test User")
     *              ),
     *              @OA\Property(property="message", type="string", example="User logged in successfully.")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Verificar credenciales
            if (!Auth::attempt($validated)) {
                throw ValidationException::withMessages([
                    'email' => ['Las credenciales proporcionadas son incorrectas.'],
                ]);
            }

            $user = User::where('email', $validated['email'])->first();

            // Verificar si el usuario está activo
            if (!$user->is_active) {
                return $this->errorResponse('Tu cuenta ha sido desactivada. Contacta al administrador.', 403);
            }

            // Revocar tokens anteriores
            $user->tokens()->delete();

            // Generar nuevo token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user->load('roles'),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Inicio de sesión exitoso');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Error de validación');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al iniciar sesión: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logs out the current user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", example=null),
     *             @OA\Property(property="message", type="string", example="Logged out successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revocar el token actual
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Sesión cerrada exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al cerrar sesión: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Recuperar contraseña
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Aquí implementarías el envío de email
            // Por ahora solo retornamos éxito
            return $this->successResponse(null, 'Si el email existe, recibirás instrucciones para recuperar tu contraseña');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Error de validación');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al procesar la solicitud: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resetear contraseña
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
                'token' => 'required|string',
            ]);

            // Aquí implementarías la lógica de reset de contraseña
            // Por ahora solo retornamos éxito
            return $this->successResponse(null, 'Contraseña actualizada exitosamente');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Error de validación');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al resetear contraseña: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener usuario actual
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles');
            
            return $this->successResponse($user, 'Usuario obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener usuario: ' . $e->getMessage(), 500);
        }
    }
}
