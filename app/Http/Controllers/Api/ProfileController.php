<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseController
{
    /**
     * Obtener perfil completo del usuario
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles');
            
            $data = [
                'user' => $user,
                'is_provider' => $user->isProvider(),
                'is_verified_provider' => $user->isVerifiedProvider(),
            ];

            // Agregar estadísticas específicas según el rol
            if ($user->isProvider()) {
                $data['provider_stats'] = $user->provider_stats;
                $data['formatted_business_hours'] = $user->formatted_business_hours;
                $data['is_open_now'] = $user->isOpenAt();
            } elseif ($user->isTourist()) {
                $data['tourist_stats'] = [
                    'favoritos_count' => $user->favoritos()->count(),
                    'reviews_count' => $user->reviews()->count(),
                    'member_since' => $user->created_at->format('Y-m-d'),
                ];
            }

            return $this->sendResponse($data, 'Perfil obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener perfil', $e->getMessage());
        }
    }

    /**
     * Actualizar perfil básico
     */
    public function updateBasic(Request $request): JsonResponse
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
            ]);

            $user->fill($validated);
            $user->save();

            return $this->sendResponse(
                $user->load('roles'), 
                'Perfil básico actualizado exitosamente'
            );

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar perfil', $e->getMessage());
        }
    }

    /**
     * Actualizar perfil de proveedor
     */
    public function updateProvider(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden actualizar este perfil', 403);
            }

            $validated = $request->validate([
                'company_name' => 'sometimes|string|max:255',
                'company_description' => 'sometimes|nullable|string|max:2000',
                'website' => 'sometimes|nullable|url|max:255',
                'tax_id' => 'sometimes|nullable|string|max:50',
                'contact_person' => 'sometimes|nullable|string|max:255',
                'contact_phone' => 'sometimes|nullable|string|max:20',
                'contact_email' => 'sometimes|nullable|email|max:255',
                'business_type' => 'sometimes|nullable|in:hotel,restaurant,tour_operator,transport,activity,other',
                'business_hours' => 'sometimes|nullable|array',
                'business_hours.*.open' => 'required_with:business_hours.*.close|nullable|date_format:H:i',
                'business_hours.*.close' => 'required_with:business_hours.*.open|nullable|date_format:H:i',
                'business_hours.*.closed' => 'boolean',
            ]);

            $user->fill($validated);
            $user->save();

            return $this->sendResponse(
                $user->load('roles'), 
                'Perfil de proveedor actualizado exitosamente'
            );

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar perfil de proveedor', $e->getMessage());
        }
    }

    /**
     * Subir logo del proveedor
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden subir logos', 403);
            }

            $request->validate([
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Eliminar logo anterior si existe
            if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
                Storage::disk('public')->delete($user->logo_path);
            }

            // Guardar nuevo logo
            $path = $request->file('logo')->store('providers/logos', 'public');
            
            $user->logo_path = $path;
            $user->save();

            return $this->sendResponse([
                'logo_url' => $user->logo_url,
                'logo_path' => $path,
            ], 'Logo subido exitosamente');

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al subir logo', $e->getMessage());
        }
    }

    /**
     * Subir licencia de negocio
     */
    public function uploadBusinessLicense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden subir licencias', 403);
            }

            $request->validate([
                'license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            // Eliminar licencia anterior si existe
            if ($user->business_license_path && Storage::disk('public')->exists($user->business_license_path)) {
                Storage::disk('public')->delete($user->business_license_path);
            }

            // Guardar nueva licencia
            $path = $request->file('license')->store('providers/licenses', 'public');
            
            $user->business_license_path = $path;
            $user->save();

            return $this->sendResponse([
                'license_url' => $user->business_license_url,
                'license_path' => $path,
            ], 'Licencia de negocio subida exitosamente');

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al subir licencia', $e->getMessage());
        }
    }

    /**
     * Eliminar logo del proveedor
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden eliminar logos', 403);
            }

            if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
                Storage::disk('public')->delete($user->logo_path);
            }

            $user->logo_path = null;
            $user->save();

            return $this->sendResponse(null, 'Logo eliminado exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar logo', $e->getMessage());
        }
    }

    /**
     * Eliminar licencia de negocio
     */
    public function deleteBusinessLicense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden eliminar licencias', 403);
            }

            if ($user->business_license_path && Storage::disk('public')->exists($user->business_license_path)) {
                Storage::disk('public')->delete($user->business_license_path);
            }

            $user->business_license_path = null;
            $user->save();

            return $this->sendResponse(null, 'Licencia de negocio eliminada exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar licencia', $e->getMessage());
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
                return $this->sendError('Error de validación', ['current_password' => ['La contraseña actual es incorrecta.']]);
            }

            // Actualizar contraseña
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Revocar todos los tokens (forzar logout en todos los dispositivos)
            $user->tokens()->delete();

            return $this->sendResponse(null, 'Contraseña cambiada exitosamente. Debes iniciar sesión nuevamente.');

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al cambiar contraseña', $e->getMessage());
        }
    }

    /**
     * Eliminar cuenta
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Eliminar archivos asociados si es proveedor
            if ($user->isProvider()) {
                if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
                    Storage::disk('public')->delete($user->logo_path);
                }
                if ($user->business_license_path && Storage::disk('public')->exists($user->business_license_path)) {
                    Storage::disk('public')->delete($user->business_license_path);
                }
            }

            // Revocar todos los tokens
            $user->tokens()->delete();

            // Eliminar usuario
            $user->delete();

            return $this->sendResponse(null, 'Cuenta eliminada exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar cuenta', $e->getMessage());
        }
    }
} 