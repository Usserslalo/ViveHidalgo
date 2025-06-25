<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\BaseController;
use App\Models\Actividad;
use App\Models\Destino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActividadController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/provider/destinos/{id}/actividades",
     *     operationId="createActividad",
     *     tags={"Provider Content"},
     *     summary="Crear una nueva actividad",
     *     description="Permite a los proveedores crear una nueva actividad para un destino específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del destino",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "duration_minutes"},
     *             @OA\Property(property="name", type="string", example="Recorrido en Lancha", description="Nombre de la actividad"),
     *             @OA\Property(property="description", type="string", example="Explora el lago en lancha con guía experto", description="Descripción completa"),
     *             @OA\Property(property="short_description", type="string", example="Explora el lago en lancha", description="Descripción corta"),
     *             @OA\Property(property="duration_minutes", type="integer", example=90, description="Duración en minutos"),
     *             @OA\Property(property="price", type="number", format="float", example=150.00, description="Precio de la actividad"),
     *             @OA\Property(property="currency", type="string", example="MXN", description="Moneda"),
     *             @OA\Property(property="max_participants", type="integer", example=8, description="Máximo de participantes"),
     *             @OA\Property(property="min_participants", type="integer", example=2, description="Mínimo de participantes"),
     *             @OA\Property(property="difficulty_level", type="string", enum={"facil", "moderado", "dificil", "experto"}, example="facil", description="Nivel de dificultad"),
     *             @OA\Property(property="age_min", type="integer", example=8, description="Edad mínima"),
     *             @OA\Property(property="age_max", type="integer", example=65, description="Edad máxima"),
     *             @OA\Property(property="is_available", type="boolean", example=true, description="Si la actividad está disponible"),
     *             @OA\Property(property="is_featured", type="boolean", example=false, description="Si es actividad destacada"),
     *             @OA\Property(property="main_image", type="string", example="data:image/jpeg;base64,...", description="Imagen principal en base64"),
     *             @OA\Property(property="gallery", type="array", @OA\Items(type="string"), example={"data:image/jpeg;base64,..."}, description="Galería de imágenes"),
     *             @OA\Property(property="included_items", type="array", @OA\Items(type="string"), example={"Chaleco salvavidas", "Guía experto"}, description="Elementos incluidos"),
     *             @OA\Property(property="excluded_items", type="array", @OA\Items(type="string"), example={"Alimentos", "Bebidas"}, description="Elementos no incluidos"),
     *             @OA\Property(property="what_to_bring", type="array", @OA\Items(type="string"), example={"Ropa cómoda", "Protector solar"}, description="Qué llevar"),
     *             @OA\Property(property="safety_notes", type="array", @OA\Items(type="string"), example={"Usar chaleco salvavidas", "Seguir instrucciones del guía"}, description="Notas de seguridad"),
     *             @OA\Property(property="cancellation_policy", type="string", example="Cancelación gratuita hasta 24h antes", description="Política de cancelación"),
     *             @OA\Property(property="meeting_point", type="string", example="Muelle principal", description="Punto de encuentro"),
     *             @OA\Property(property="meeting_time", type="string", example="09:00:00", description="Hora de encuentro"),
     *             @OA\Property(property="seasonal_availability", type="array", @OA\Items(type="integer"), example={1,2,3,4,5,6,7,8,9,10,11,12}, description="Meses disponibles (1-12)"),
     *             @OA\Property(property="weather_dependent", type="boolean", example=false, description="Si depende del clima"),
     *             @OA\Property(property="categoria_ids", type="array", @OA\Items(type="integer"), example={1,2}, description="IDs de categorías"),
     *             @OA\Property(property="caracteristica_ids", type="array", @OA\Items(type="integer"), example={1,3}, description="IDs de características"),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), example={1,4}, description="IDs de tags")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Actividad creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="actividad", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Recorrido en Lancha"),
     *                     @OA\Property(property="slug", type="string", example="recorrido-lancha"),
     *                     @OA\Property(property="destino_id", type="integer", example=1),
     *                     @OA\Property(property="is_available", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Actividad creada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo el dueño del destino puede crear actividades.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
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
    public function store(Request $request, int $destinoId): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar que el usuario esté autenticado y sea proveedor
            if (!$user || !$user->isProvider()) {
                return $this->sendError('Solo los proveedores pueden crear actividades.', [], 403);
            }

            // Verificar que el destino pertenezca al usuario
            $destino = Destino::where('id', $destinoId)
                ->where('user_id', $user->id)
                ->first();

            if (!$destino) {
                return $this->sendError('Destino no encontrado o no tienes permisos para este destino.', [], 404);
            }

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:255',
                'duration_minutes' => 'required|integer|min:15|max:1440', // 15 min a 24 horas
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'max_participants' => 'nullable|integer|min:1',
                'min_participants' => 'nullable|integer|min:1|max:max_participants',
                'difficulty_level' => 'nullable|string|in:facil,moderado,dificil,experto',
                'age_min' => 'nullable|integer|min:0|max:100',
                'age_max' => 'nullable|integer|min:0|max:100|gte:age_min',
                'is_available' => 'boolean',
                'is_featured' => 'boolean',
                'main_image' => 'nullable|string',
                'gallery' => 'nullable|array',
                'gallery.*' => 'string',
                'included_items' => 'nullable|array',
                'included_items.*' => 'string|max:255',
                'excluded_items' => 'nullable|array',
                'excluded_items.*' => 'string|max:255',
                'what_to_bring' => 'nullable|array',
                'what_to_bring.*' => 'string|max:255',
                'safety_notes' => 'nullable|array',
                'safety_notes.*' => 'string|max:255',
                'cancellation_policy' => 'nullable|string|max:500',
                'meeting_point' => 'nullable|string|max:255',
                'meeting_time' => 'nullable|date_format:H:i:s',
                'seasonal_availability' => 'nullable|array',
                'seasonal_availability.*' => 'integer|min:1|max:12',
                'weather_dependent' => 'boolean',
                'categoria_ids' => 'nullable|array',
                'categoria_ids.*' => 'integer|exists:categorias,id',
                'caracteristica_ids' => 'nullable|array',
                'caracteristica_ids.*' => 'integer|exists:caracteristicas,id',
                'tag_ids' => 'nullable|array',
                'tag_ids.*' => 'integer|exists:tags,id'
            ]);

            // Procesar imagen principal
            $mainImagePath = null;
            if (!empty($validated['main_image'])) {
                $mainImagePath = $this->processBase64Image($validated['main_image'], 'actividades/main');
            }

            // Procesar galería
            $galleryPaths = [];
            if (!empty($validated['gallery'])) {
                foreach ($validated['gallery'] as $image) {
                    $galleryPaths[] = $this->processBase64Image($image, 'actividades/gallery');
                }
            }

            // Crear la actividad
            $actividad = Actividad::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . uniqid(),
                'description' => $validated['description'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'price' => $validated['price'] ?? 0.00,
                'currency' => $validated['currency'] ?? 'MXN',
                'max_participants' => $validated['max_participants'] ?? null,
                'min_participants' => $validated['min_participants'] ?? null,
                'difficulty_level' => $validated['difficulty_level'] ?? 'moderado',
                'age_min' => $validated['age_min'] ?? null,
                'age_max' => $validated['age_max'] ?? null,
                'is_available' => $validated['is_available'] ?? true,
                'is_featured' => $validated['is_featured'] ?? false,
                'main_image' => $mainImagePath,
                'gallery' => !empty($galleryPaths) ? $galleryPaths : null,
                'included_items' => $validated['included_items'] ?? null,
                'excluded_items' => $validated['excluded_items'] ?? null,
                'what_to_bring' => $validated['what_to_bring'] ?? null,
                'safety_notes' => $validated['safety_notes'] ?? null,
                'cancellation_policy' => $validated['cancellation_policy'] ?? null,
                'meeting_point' => $validated['meeting_point'] ?? null,
                'meeting_time' => $validated['meeting_time'] ?? null,
                'seasonal_availability' => $validated['seasonal_availability'] ?? null,
                'weather_dependent' => $validated['weather_dependent'] ?? false,
                'user_id' => $user->id,
                'destino_id' => $destinoId
            ]);

            // Asociar categorías
            if (!empty($validated['categoria_ids'])) {
                $actividad->categorias()->attach($validated['categoria_ids']);
            }

            // Asociar características
            if (!empty($validated['caracteristica_ids'])) {
                $actividad->caracteristicas()->attach($validated['caracteristica_ids']);
            }

            // Asociar tags
            if (!empty($validated['tag_ids'])) {
                $actividad->tags()->attach($validated['tag_ids']);
            }

            // Log de creación
            Log::info('Actividad created', [
                'actividad_id' => $actividad->id,
                'destino_id' => $destinoId,
                'provider_id' => $user->id,
                'name' => $actividad->name
            ]);

            $data = [
                'actividad' => [
                    'id' => $actividad->id,
                    'name' => $actividad->name,
                    'slug' => $actividad->slug,
                    'destino_id' => $actividad->destino_id,
                    'is_available' => $actividad->is_available,
                    'created_at' => $actividad->created_at
                ]
            ];

            return $this->sendResponse($data, 'Actividad creada exitosamente.', 201);

        } catch (\Exception $e) {
            Log::error('Error creating actividad: ' . $e->getMessage());
            return $this->sendError('Error al crear actividad: ' . $e->getMessage(), [], 500);
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
} 