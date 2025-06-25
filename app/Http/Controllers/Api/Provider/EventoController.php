<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\BaseController;
use App\Models\Evento;
use App\Models\Destino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventoController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/provider/eventos",
     *     operationId="createEvento",
     *     tags={"Provider Content"},
     *     summary="Crear un nuevo evento",
     *     description="Permite a los proveedores crear un nuevo evento turístico",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "start_date", "end_date"},
     *             @OA\Property(property="name", type="string", example="Festival de la Barbacoa", description="Nombre del evento"),
     *             @OA\Property(property="description", type="string", example="El mejor festival de barbacoa del estado", description="Descripción completa"),
     *             @OA\Property(property="short_description", type="string", example="Festival gastronómico", description="Descripción corta"),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2025-03-15T10:00:00", description="Fecha y hora de inicio"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2025-03-17T22:00:00", description="Fecha y hora de fin"),
     *             @OA\Property(property="location", type="string", example="Plaza Principal de Pachuca", description="Ubicación del evento"),
     *             @OA\Property(property="latitude", type="number", format="float", example=20.1234, description="Latitud"),
     *             @OA\Property(property="longitude", type="number", format="float", example=-98.5678, description="Longitud"),
     *             @OA\Property(property="price", type="number", format="float", example=150.00, description="Precio del evento"),
     *             @OA\Property(property="capacity", type="integer", example=500, description="Capacidad máxima"),
     *             @OA\Property(property="destino_id", type="integer", example=1, description="ID del destino asociado"),
     *             @OA\Property(property="organizer_name", type="string", example="Asociación Gastronómica", description="Nombre del organizador"),
     *             @OA\Property(property="organizer_email", type="string", example="info@festival.com", description="Email del organizador"),
     *             @OA\Property(property="organizer_phone", type="string", example="+52 771 123 4567", description="Teléfono del organizador"),
     *             @OA\Property(property="website_url", type="string", example="https://festival.com", description="URL del sitio web"),
     *             @OA\Property(property="categoria_ids", type="array", @OA\Items(type="integer"), example={1,2}, description="IDs de categorías"),
     *             @OA\Property(property="caracteristica_ids", type="array", @OA\Items(type="integer"), example={1,3}, description="IDs de características"),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), example={1,4}, description="IDs de tags"),
     *             @OA\Property(property="main_image", type="string", example="data:image/jpeg;base64,...", description="Imagen principal en base64"),
     *             @OA\Property(property="gallery", type="array", @OA\Items(type="string"), example={"data:image/jpeg;base64,..."}, description="Galería de imágenes"),
     *             @OA\Property(property="contact_info", type="object", description="Información de contacto adicional"),
     *             @OA\Property(property="social_media", type="object", description="Redes sociales")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evento creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="evento", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Festival de la Barbacoa"),
     *                     @OA\Property(property="slug", type="string", example="festival-barbacoa-2025"),
     *                     @OA\Property(property="status", type="string", example="draft"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Evento creado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo los proveedores pueden crear eventos.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar que el usuario esté autenticado y sea proveedor
            if (!$user || !$user->isProvider()) {
                return $this->sendError('Solo los proveedores pueden crear eventos.', [], 403);
            }

            // Autorizar creación de evento usando la policy
            $this->authorize('create', Evento::class);

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:255',
                'start_date' => 'required|date|after:now',
                'end_date' => 'required|date|after:start_date',
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'price' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'destino_id' => 'nullable|integer|exists:destinos,id',
                'organizer_name' => 'nullable|string|max:255',
                'organizer_email' => 'nullable|email',
                'organizer_phone' => 'nullable|string|max:20',
                'website_url' => 'nullable|url',
                'categoria_ids' => 'nullable|array',
                'categoria_ids.*' => 'integer|exists:categorias,id',
                'caracteristica_ids' => 'nullable|array',
                'caracteristica_ids.*' => 'integer|exists:caracteristicas,id',
                'tag_ids' => 'nullable|array',
                'tag_ids.*' => 'integer|exists:tags,id',
                'main_image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
                'contact_info' => 'nullable|array',
                'social_media' => 'nullable|array'
            ]);

            // Verificar que el destino pertenezca al usuario si se especifica
            if (!empty($validated['destino_id'])) {
                $destino = Destino::where('id', $validated['destino_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$destino) {
                    return $this->sendError('El destino especificado no pertenece a tu cuenta.', [], 422);
                }
            }

            // Procesar imagen principal
            $mainImagePath = null;
            if (!empty($validated['main_image'])) {
                $mainImagePath = $this->processBase64Image($validated['main_image'], 'eventos/main');
            }

            // Procesar galería
            $galleryPaths = [];
            if (!empty($validated['gallery'])) {
                foreach ($validated['gallery'] as $image) {
                    $galleryPaths[] = $this->processBase64Image($image, 'eventos/gallery');
                }
            }

            // Crear el evento
            $evento = Evento::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . now()->format('Y'),
                'description' => $validated['description'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'location' => $validated['location'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'price' => $validated['price'] ?? 0.00,
                'capacity' => $validated['capacity'] ?? 0,
                'current_attendees' => 0,
                'status' => 'draft',
                'is_featured' => false,
                'main_image' => $mainImagePath,
                'gallery' => !empty($galleryPaths) ? $galleryPaths : null,
                'contact_info' => $validated['contact_info'] ?? null,
                'organizer_name' => $validated['organizer_name'] ?? null,
                'organizer_email' => $validated['organizer_email'] ?? null,
                'organizer_phone' => $validated['organizer_phone'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'social_media' => $validated['social_media'] ?? null,
                'user_id' => $user->id,
                'destino_id' => $validated['destino_id'] ?? null
            ]);

            // Asociar categorías
            if (!empty($validated['categoria_ids'])) {
                $evento->categorias()->attach($validated['categoria_ids']);
            }

            // Asociar características
            if (!empty($validated['caracteristica_ids'])) {
                $evento->caracteristicas()->attach($validated['caracteristica_ids']);
            }

            // Asociar tags
            if (!empty($validated['tag_ids'])) {
                $evento->tags()->attach($validated['tag_ids']);
            }

            // Log de creación
            Log::info('Evento created', [
                'evento_id' => $evento->id,
                'provider_id' => $user->id,
                'name' => $evento->name
            ]);

            $data = [
                'evento' => [
                    'id' => $evento->id,
                    'name' => $evento->name,
                    'slug' => $evento->slug,
                    'status' => $evento->status,
                    'created_at' => $evento->created_at
                ]
            ];

            return $this->sendResponse($data, 'Evento creado exitosamente.', 201);

        } catch (\Exception $e) {
            Log::error('Error creating evento: ' . $e->getMessage());
            return $this->sendError('Error al crear evento: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Procesar imagen en base64
     */
    private function processBase64Image(string $base64String, string $path): string
    {
        try {
            // Extraer datos de la imagen
            $imageData = explode(',', $base64String);
            $imageData = base64_decode($imageData[1]);

            // Generar nombre único
            $fileName = uniqid() . '_' . time() . '.jpg';
            $fullPath = $path . '/' . $fileName;

            // Guardar en storage
            Storage::disk('public')->put($fullPath, $imageData);

            return Storage::disk('public')->url($fullPath);
        } catch (\Exception $e) {
            Log::error('Error processing base64 image: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/provider/eventos/{evento}",
     *     operationId="updateEvento",
     *     tags={"Provider Content"},
     *     summary="Actualizar un evento existente",
     *     description="Permite a los proveedores actualizar un evento turístico existente",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="evento",
     *         in="path",
     *         required=true,
     *         description="ID del evento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, description="Nombre del evento"),
     *             @OA\Property(property="description", type="string", nullable=true, description="Descripción completa del evento"),
     *             @OA\Property(property="short_description", type="string", maxLength=255, nullable=true, description="Descripción corta del evento"),
     *             @OA\Property(property="start_date", type="string", format="date", description="Fecha de inicio del evento"),
     *             @OA\Property(property="end_date", type="string", format="date", description="Fecha de fin del evento"),
     *             @OA\Property(property="location", type="string", maxLength=255, nullable=true, description="Ubicación del evento"),
     *             @OA\Property(property="latitude", type="number", format="float", nullable=true, description="Latitud de la ubicación"),
     *             @OA\Property(property="longitude", type="number", format="float", nullable=true, description="Longitud de la ubicación"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true, description="Precio del evento"),
     *             @OA\Property(property="capacity", type="integer", nullable=true, description="Capacidad máxima del evento"),
     *             @OA\Property(property="destino_id", type="integer", nullable=true, description="ID del destino asociado"),
     *             @OA\Property(property="organizer_name", type="string", maxLength=255, nullable=true, description="Nombre del organizador"),
     *             @OA\Property(property="organizer_email", type="string", format="email", nullable=true, description="Email del organizador"),
     *             @OA\Property(property="organizer_phone", type="string", maxLength=20, nullable=true, description="Teléfono del organizador"),
     *             @OA\Property(property="website_url", type="string", format="uri", nullable=true, description="URL del sitio web del evento"),
     *             @OA\Property(property="categoria_ids", type="array", @OA\Items(type="integer"), nullable=true, description="IDs de las categorías"),
     *             @OA\Property(property="caracteristica_ids", type="array", @OA\Items(type="integer"), nullable=true, description="IDs de las características"),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), nullable=true, description="IDs de las etiquetas"),
     *             @OA\Property(property="main_image", type="string", nullable=true, description="Imagen principal en base64"),
     *             @OA\Property(property="gallery", type="array", @OA\Items(type="string"), nullable=true, description="Galería de imágenes en base64"),
     *             @OA\Property(property="contact_info", type="object", nullable=true, description="Información de contacto adicional"),
     *             @OA\Property(property="social_media", type="object", nullable=true, description="Redes sociales"),
     *             @OA\Property(property="status", type="string", enum={"draft","published","pending_review"}, description="Estado del evento")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="evento", type="object")
     *             ),
     *             @OA\Property(property="message", type="string", example="Evento actualizado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No tienes permisos para actualizar este evento.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evento no encontrado"
     *     )
     * )
     */
    public function update(Request $request, Evento $evento): JsonResponse
    {
        try {
            // Autorizar actualización del evento usando la policy
            $this->authorize('update', $evento);

            // Validar datos
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:255',
                'start_date' => 'sometimes|date|after:now',
                'end_date' => 'sometimes|date|after:start_date',
                'location' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'price' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'destino_id' => 'nullable|integer|exists:destinos,id',
                'organizer_name' => 'nullable|string|max:255',
                'organizer_email' => 'nullable|email',
                'organizer_phone' => 'nullable|string|max:20',
                'website_url' => 'nullable|url',
                'categoria_ids' => 'nullable|array',
                'categoria_ids.*' => 'integer|exists:categorias,id',
                'caracteristica_ids' => 'nullable|array',
                'caracteristica_ids.*' => 'integer|exists:caracteristicas,id',
                'tag_ids' => 'nullable|array',
                'tag_ids.*' => 'integer|exists:tags,id',
                'main_image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
                'contact_info' => 'nullable|array',
                'social_media' => 'nullable|array',
                'status' => 'sometimes|string|in:draft,published,pending_review'
            ]);

            // Verificar que el destino pertenezca al usuario si se especifica
            if (!empty($validated['destino_id'])) {
                $destino = Destino::where('id', $validated['destino_id'])
                    ->where('user_id', $evento->user_id)
                    ->first();

                if (!$destino) {
                    return $this->sendError('El destino especificado no pertenece a tu cuenta.', [], 422);
                }
            }

            // Procesar imagen principal si se proporciona
            if (!empty($validated['main_image'])) {
                $validated['main_image'] = $this->processBase64Image($validated['main_image'], 'eventos/main');
            }

            // Procesar galería si se proporciona
            if (!empty($validated['gallery'])) {
                $galleryPaths = [];
                foreach ($validated['gallery'] as $image) {
                    $galleryPaths[] = $this->processBase64Image($image, 'eventos/gallery');
                }
                $validated['gallery'] = $galleryPaths;
            }

            // Actualizar el evento
            $evento->update($validated);

            // Actualizar relaciones si se proporcionan
            if (isset($validated['categoria_ids'])) {
                $evento->categorias()->sync($validated['categoria_ids']);
            }

            if (isset($validated['caracteristica_ids'])) {
                $evento->caracteristicas()->sync($validated['caracteristica_ids']);
            }

            if (isset($validated['tag_ids'])) {
                $evento->tags()->sync($validated['tag_ids']);
            }

            // Log de actualización
            Log::info('Evento updated', [
                'evento_id' => $evento->id,
                'provider_id' => $evento->user_id,
                'name' => $evento->name
            ]);

            return $this->sendResponse($evento->load(['categorias', 'caracteristicas', 'tags']), 'Evento actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error updating evento: ' . $e->getMessage());
            return $this->sendError('Error al actualizar evento: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/provider/eventos/{evento}",
     *     operationId="deleteEvento",
     *     tags={"Provider Content"},
     *     summary="Eliminar un evento",
     *     description="Permite a los proveedores eliminar un evento turístico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="evento",
     *         in="path",
     *         required=true,
     *         description="ID del evento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Evento eliminado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No tienes permisos para eliminar este evento.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evento no encontrado"
     *     )
     * )
     */
    public function destroy(Evento $evento): JsonResponse
    {
        try {
            // Autorizar eliminación del evento usando la policy
            $this->authorize('delete', $evento);

            // Log de eliminación
            Log::info('Evento deleted', [
                'evento_id' => $evento->id,
                'provider_id' => $evento->user_id,
                'name' => $evento->name
            ]);

            // Eliminar el evento
            $evento->delete();

            return $this->sendResponse(null, 'Evento eliminado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error deleting evento: ' . $e->getMessage());
            return $this->sendError('Error al eliminar evento: ' . $e->getMessage(), [], 500);
        }
    }
} 